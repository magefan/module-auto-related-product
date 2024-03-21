<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model;

use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterfaceFactory as RuleCollectionFactory;
use Magefan\Community\Model\Magento\Rule\Model\Condition\Sql\Builder;
use Magefan\DynamicProductAttributes\Api\AddCustomValidationFiltersInterface;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
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
     * @var AddCustomValidationFiltersInterface
     */
    private AddCustomValidationFiltersInterface $validationFilter;

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
     * @param ResourceConnection $resourceConnection
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param RuleRepository $ruleRepository
     * @param CollectionFactory $productCollectionFactory
     * @param Builder $sqlBuilder
     * @param JsonSerializer $jsonSerializer
     * @param RuleFactory $catalogRuleFactory
     * @param AddCustomValidationFiltersInterface $validationFilter
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        RuleCollectionFactory $ruleCollectionFactory,
        RuleRepository $ruleRepository,
        CollectionFactory $productCollectionFactory,
        Builder $sqlBuilder,
        RuleFactory $catalogRuleFactory,
        AddCustomValidationFiltersInterface $validationFilter
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

    }

    public function execute()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('magefan_autorp_index');

        $rules = $this->ruleCollectionFactory->create()
            ->addFieldToFilter('status', 1);
        foreach ($rules as $rule) {
            $relatedIds = $this->getListProductIds($rule);

            /* $connection->delete(
                $tableName,
                ['rule_id' => $rule->getId()]
            );

            if ($relatedIds) {
                $connection->insertOnDuplicate(
                    $tableName,
                    [
                        'rule_id' => $rule->getId(),
                        'identifier' => $rule->getRuleBlockIdentifier(),
                        'related_ids' => implode(',', $relatedIds),
                    ]
                );
            }*/

            $connection->insertOnDuplicate(
                $tableName,
                [
                    'rule_id' => $rule->getId(),
                    'identifier' => $rule->getRuleBlockIdentifier(),
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

    /**
     * @param $rule
     * @param null $params
     * @return array
     * @throws LocalizedException
     */
    public function getListProductIds($rule, $params = null)
    {
        $this->productIds = [];
        $productCollection = $this->productCollectionFactory->create();
        $conditions = $rule->getConditions();

        if (is_string($rule->getConditions())) {
            $conditions = json_decode($rule->getConditions(),true);
        }

        if (!empty($conditions['conditions'])) {
            if ($rule->getWebsiteIds()) {
                $storeIds = [];
                $websites = $this->validationFilter->_getWebsitesMap();
                foreach ($websites as $websiteId => $defaultStoreId) {
                    if (in_array($websiteId, $rule->getWebsiteIds())) {
                        $storeIds[] = $defaultStoreId;
                    }
                }
            } else {
                $storeIds = [0];
            }

            if ($rule->getConditionsSerialized()) {
                $catalogRule = $this->catalogRuleFactory->create();
                $conditions = $this->validationFilter->processCustomValidator(json_decode($rule->getConditionsSerialized(),true));
                $catalogRule->setData('conditions_serialized', json_encode($conditions));
                $catalogRule->loadPost($catalogRule->getData());
                $conditions = $catalogRule->getConditions();
                $conditions->collectValidatedAttributes($productCollection);

                $this->sqlBuilder->attachConditionToCollection($productCollection, $conditions);
                $this->validationFilter->addCustomValidationFilters($productCollection);
                $productCollection->getSelect()->group('e.entity_id');

                $relatedProductIds = $productCollection->getAllIds();
                if (empty($relatedProductIds)) {
                    return [-1];
                }
            } else {
                $conditions = $rule->getConditions()->asArray();

                $conditions = $this->validationFilter->processCustomValidator($conditions);

                $rule->getConditions()->setConditions([])->loadArray($conditions);
            }


            foreach ($storeIds as $storeId) {

                $productCollection = $this->productCollectionFactory->create();

                if ($storeId) {
                    $productCollection->setStoreId($storeId);
                }

                if ($this->checkItFromProductPage($params)) {
                    $productCollection
                        ->addFieldToFilter('entity_id', $params['product_id']);
                }

                if ($rule->getWebsiteIds()) {
                    $productCollection->addWebsiteFilter($rule->getWebsiteIds());
                }

                $conditions = $rule->getConditions();
                if (is_string($rule->getConditions())) {
                    $conditions = $catalogRule->getConditions();
                }
                $conditions->collectValidatedAttributes($productCollection);
                $this->sqlBuilder->attachConditionToCollection($productCollection, $conditions);

                $this->validationFilter->addCustomValidationFilters($productCollection);

                $productCollection->getSelect()->group('e.entity_id');

                foreach ($productCollection as $item) {
                    $this->productIds[] = (int) $item->getId();
                }
            }
        }

        $this->productIds = array_merge(
            $this->productIds,
            $this->validationFilter->getParentProductIds($this->productIds)
        );

        return array_unique($this->productIds);
    }

    /**
     * @param $params
     * @return bool
     */
    protected function checkItFromProductPage($params)
    {

        return $params && isset($params['type']) && ($params['type'] == 'product');
    }
}
