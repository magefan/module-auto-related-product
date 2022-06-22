<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model\ResourceModel;

use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterfaceFactory as RuleCollectionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class RelatedUpdater
 */
class RelatedUpdater
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var RuleCollectionFactory
     */
    private $ruleCollectionFactory;

    /**
     * @var Visibility
     */
    private $catalogProductVisibility;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private $productCollection;

    /**
     * @var \Magento\CatalogRule\Model\RuleFactory
     */
    private $catalogRuleFactory;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CollectionFactory $productCollectionFactory
     * @param ConditionValidator $validator
     * @param Visibility $catalogProductVisibility
     * @param RuleCollectionFactory $ruleCollectionFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        RuleCollectionFactory $ruleCollectionFactory,
        RuleFactory $catalogRuleFactory,
        JsonSerializer $jsonSerializer
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->catalogRuleFactory = $catalogRuleFactory;
        $this->connection = $resourceConnection->getConnection();
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('magefan_autorp_index');

        $rules = $this->ruleCollectionFactory->create()
            ->addFieldToFilter('status', 1);

        foreach ($rules as $rule) {
            $relatedIds = $this->getRelatedProducts($rule);
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
     * @return array
     */
    private function getRelatedProducts($rule): array
    {
        $productCollection = $this->productCollectionFactory->create();
        $relatedProductIds = [];

        $ruleConditions = $rule->getConditions() ?: '';
        if (!$ruleConditions) {
            return $relatedProductIds;
        }

        try {
            $conditionsUnserialized = $this->jsonSerializer->unserialize($ruleConditions);
        } catch (\InvalidArgumentException $e) {
            return $relatedProductIds;
        }

        if (!isset($conditionsUnserialized['conditions'])) {
            return $relatedProductIds;
        }

        if ($rule->getConditionsSerialized()) {
            $catalogRule = $this->catalogRuleFactory->create();
            $catalogRule->setData('conditions_serialized', $rule->getConditionsSerialized());
            $catalogRule->loadPost($catalogRule->getData());
            $conditions = $catalogRule->getConditions();
            $conditions->collectValidatedAttributes($productCollection);

            foreach ($productCollection as $product) {
                if ($conditions->validate($product)) {
                    $relatedProductIds[] = $product->getId();
                }
            }

            if (!$relatedProductIds) {
                return [-1];
            }
        }

        $relatedProductIds = array_merge(
            $relatedProductIds,
            $this->getParentProductIds($relatedProductIds)
        );

        return $relatedProductIds;
    }

    /**
     * @param $productIds
     * @return array
     */
    private function getParentProductIds($productIds)
    {
        $parentProductIds = [];

        /* Fix for configurable, bundle, grouped */
        if ($productIds) {
            $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
            $entityIdColumn = $this->connection->tableColumnExists($productTable, 'row_id') ? 'row_id' : 'entity_id';

            $select = $this->connection->select()
                ->from(
                    ['main_table' => $this->resourceConnection->getTableName('catalog_product_relation')],
                    []
                )->join(
                    ['e' => $productTable],
                    'e.' . $entityIdColumn . ' = main_table.parent_id',
                    ['e.' .$entityIdColumn]
                )
                ->where('main_table.child_id IN (?)', $productIds)
                ->where('e.entity_id IS NOT NULL');

            foreach ($this->connection->fetchAll($select) as $product) {
                $parentProductIds[$product[$entityIdColumn]] = $product[$entityIdColumn];
            }
        }
        /* End fix */

        return $parentProductIds;
    }
}
