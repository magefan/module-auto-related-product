<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model;

use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterfaceFactory as RuleCollectionFactory;
use Magefan\Community\Model\Magento\Rule\Model\Condition\Sql\Builder;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magefan\Community\Api\GetParentProductIdsInterface;
use Magefan\Community\Api\GetWebsitesMapInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

class AutoRelatedProductAction
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    const ORDER_BY_ASC = 'ASC';

    /**
     * @var RuleRepository
     */
    protected $ruleRepository;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var
     */
    protected $productIds;

    /**
     * @var Builder|mixed
     */
    private $sqlBuilder;

    /**
     * @var RuleCollectionFactory
     */
    private $ruleCollectionFactory;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var \Magento\CatalogRule\Model\RuleFactory
     */
    private $catalogRuleFactory;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var GetParentProductIdsInterface
     */
    private $getParentProductIds;

    /**
     * @var GetWebsitesMapInterface
     */
    private $getWebsitesMap;

    /**
     * @var null
     */
    private $validationFilter;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * @param ResourceConnection $resourceConnection
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param RuleRepository $ruleRepository
     * @param CollectionFactory $productCollectionFactory
     * @param Builder $sqlBuilder
     * @param RuleFactory $catalogRuleFactory
     * @param ModuleManager $moduleManager
     * @param GetParentProductIdsInterface $getParentProductIds
     * @param GetWebsitesMapInterface $getWebsitesMap
     * @param EventManagerInterface $eventManager
     * @param $validationFilter
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        RuleCollectionFactory $ruleCollectionFactory,
        RuleRepository $ruleRepository,
        CollectionFactory $productCollectionFactory,
        Builder $sqlBuilder,
        RuleFactory $catalogRuleFactory,
        ModuleManager $moduleManager,
        GetParentProductIdsInterface $getParentProductIds,
        GetWebsitesMapInterface $getWebsitesMap,
        EventManagerInterface $eventManager,
        $validationFilter = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->connection = $resourceConnection->getConnection();
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->ruleRepository = $ruleRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->validationFilter = $validationFilter;
        $this->sqlBuilder = $sqlBuilder ?:  \Magento\Framework\App\ObjectManager::getInstance()
            ->get(Builder::class);
        $this->catalogRuleFactory = $catalogRuleFactory;

        $this->getParentProductIds = $getParentProductIds;
        $this->getWebsitesMap = $getWebsitesMap;
        $this->moduleManager = $moduleManager;
        $this->eventManager = $eventManager;

        if ($this->moduleManager->isEnabled('Magefan_DynamicProductAttributes')) {
            $this->validationFilter =
                \Magento\Framework\App\ObjectManager::getInstance()->get('Magefan\DynamicProductAttributes\Api\AddCustomValidationFiltersInterface');
        }
    }

    public function execute()
    {
        $productIdsToCleanCache = [];
        $oldProductToRuleData = [];

        $connection = $this->resourceConnection->getConnection();
        $tableNameArpIndex = $this->resourceConnection->getTableName('magefan_autorp_index');

        $ruleCollection = $this->ruleCollectionFactory->create()
            ->addFieldToFilter('status', 1);

        if ($ruleCollection) {
            $oldProductToRuleCollection = $this->connection->fetchAll($this->connection->select()->from($tableNameArpIndex));

            foreach ($oldProductToRuleCollection as $value) {
                $relatedIds = explode(',', $value['related_ids']);

                foreach ($relatedIds as $productId) {
                    $oldProductToRuleData[$value['rule_id'] . '_' . $productId] = $productId;
                }
            }
        }

        foreach ($ruleCollection as $item) {
            if ($conditionsSerialized = $item->getData('conditions_serialized')) {
                $ruleId = $item->getId();

                $rule = $this->catalogRuleFactory->create();
                $rule->setData('conditions_serialized', $conditionsSerialized);
                $rule->setData('store_ids', $item->getStoreIds());

                $relatedIds = $this->getListProductIds($rule);

                foreach ($relatedIds as $productId) {
                    if (!isset($oldProductToRuleData[$ruleId . '_' . $productId])) {
                        $productIdsToCleanCache[$productId] = $productId;
                    } else {
                        unset($oldProductToRuleData[$ruleId . '_' . $productId]);
                    }
                }

                $connection->insertOnDuplicate(
                    $tableNameArpIndex,
                    [
                        'rule_id' => $ruleId,
                        'identifier' => $item->getRuleBlockIdentifier(),
                        'related_ids' => implode(',', $relatedIds),
                    ],
                    [
                        'rule_id',
                        'identifier',
                        'related_ids'
                    ]
                );
            }
        }

        foreach ($oldProductToRuleData as $productId) {
            $productIdsToCleanCache[$productId] = $productId;
        }

        if ($productIdsToCleanCache) {
            $this->cleanCacheByProductIds($productIdsToCleanCache);
        }

    }

    /**
     * @param $rule
     * @param null $params
     * @return array
     * @throws LocalizedException
     */
    /**
     * @param $rule
     * @param null $params
     * @return array
     */
    public function getListProductIds($rule)
    {
        $this->productIds = [];
        $conditions = $rule->getConditions();

        if (!empty($conditions['conditions'])) {
            if ($rule->getWebsiteIds()) {
                $storeIds = [];
                $websites = $this->getWebsitesMap->execute();

                foreach ($websites as $websiteId => $defaultStoreId) {
                    if (in_array($websiteId, $rule->getWebsiteIds())) {
                        $storeIds[] = $defaultStoreId;
                    }
                }
            } else {
                $storeIds = [0];
            }

            $conditions = $rule->getConditions()->asArray();

            if ($this->validationFilter !== null) {
                $conditions = $this->validationFilter->processCustomValidator($conditions);
            }

            $rule->getConditions()->setConditions([])->loadArray($conditions);

            foreach ($storeIds as $storeId) {

                $productCollection = $this->productCollectionFactory->create();

                if ($storeId) {
                    $productCollection->setStoreId($storeId);
                }

                $conditions = $rule->getConditions();

                $conditions->collectValidatedAttributes($productCollection);
                $this->sqlBuilder->attachConditionToCollection($productCollection, $conditions);

                if ($this->validationFilter !== null) {
                    $this->validationFilter->addCustomValidationFilters($productCollection);
                }

                $productCollection->getSelect()->group('e.entity_id');

                foreach ($productCollection as $item) {
                    $this->productIds[] = (int) $item->getId();
                }
            }
        }

        $this->productIds = array_merge(
            $this->productIds,
            $this->getParentProductIds->execute($this->productIds)
        );

        return array_unique($this->productIds);
    }

    /**
     * @param array $productIds
     * @return void
     */
    private function cleanCacheByProductIds(array $productIds): void
    {
        $productCollection = $this->productCollectionFactory->create()
            ->addAttributeToFilter('entity_id', ['in' => $productIds]);

        foreach ($productCollection as $product) {
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $product]);
        }
    }
}
