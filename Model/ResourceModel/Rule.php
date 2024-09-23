<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\SalesRule\Model\RuleFactory as SalesRuleFactory;
use Magento\CatalogRule\Model\RuleFactory as CatalogRuleFactory;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magefan\AutoRelatedProduct\Api\RelatedResourceModelInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\UrlInterface;

/**
 * Class Rule
 */
class Rule extends AbstractDb implements RelatedResourceModelInterface
{
    /**
     * @var SalesRuleFactory
     */
    private $salesRuleFactory;

    /**
     * @var CatalogRuleFactory
     */
    private $catalogRuleFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param Context $context
     * @param SalesRuleFactory $salesRuleFactory
     * @param CatalogRuleFactory $catalogRuleFactory
     * @param RequestInterface $request
     * @param MessageManager $messageManager
     * @param UrlInterface $url
     */
    public function __construct(
        Context $context,
        SalesRuleFactory $salesRuleFactory,
        CatalogRuleFactory $catalogRuleFactory,
        RequestInterface $request,
        MessageManager $messageManager,
        UrlInterface $url
    ) {
        $this->salesRuleFactory = $salesRuleFactory;
        $this->catalogRuleFactory = $catalogRuleFactory;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->url = $url;
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('magefan_autorp_rule', 'id');
    }

    /**
     * Process post data before deleting
     * @param AbstractModel $object
     * @return Rule
     */
    protected function _beforeDelete(AbstractModel $object)
    {
        $condition = ['rule_id = ?' => (int)$object->getId()];
        $tables = [
            'magefan_autorp_index',
            'magefan_autorp_rule_store'
        ];

        foreach ($tables as $table) {
            $this->getConnection()->delete(
                $this->getTable($table),
                $condition
            );
        }

        return parent::_beforeDelete($object);
    }

    /**
     * Get ids to which specified item is assigned
     * @param  int $postId
     * @param  string $tableName
     * @param  string $field
     * @return array
     */
    protected function _lookupIds($ruleId, $tableName, $field)
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()->from(
            $this->getTable($tableName),
            $field
        )->where(
            'rule_id = ?',
            (int)$ruleId
        );

        return $adapter->fetchCol($select);
    }

    public function lookupIds($ruleId, $tableName, $field)
    {
        return $this->_lookupIds($ruleId, $tableName, $field);
    }

    /**
     * Get store ids to which specified item is assigned
     *
     * @param int $postId
     * @return array
     */
    public function lookupStoreIds($ruleId)
    {
        return $this->_lookupIds($ruleId, 'magefan_autorp_rule_store', 'store_id');
    }

    /**
     * Update post connections
     * @param  AbstractModel $object
     * @param  Array $newRelatedIds
     * @param  Array $oldRelatedIds
     * @param  String $tableName
     * @param  String  $field
     * @param  Array  $rowData
     * @return void
     */
    protected function _updateLinks(AbstractModel $object, array $newRelatedIds, array $oldRelatedIds, $tableName, $field, $rowData = [])
    {
        $table = $this->getTable($tableName);

        if ($object->getId() && empty($rowData)) {
            $currentData = $this->_lookupAll($object->getId(), $tableName, '*');
            foreach ($currentData as $item) {
                $rowData[$item[$field]] = $item;
            }
        }

        $insert = $newRelatedIds;
        $delete = $oldRelatedIds;

        if ($delete) {
            $where = ['rule_id = ?' => (int)$object->getId(), $field.' IN (?)' => $delete];

            $this->getConnection()->delete($table, $where);
        }

        if ($insert) {
            $data = [];
            foreach ($insert as $id) {
                $id = (int)$id;
                $data[] = array_merge(
                    ['rule_id' => (int)$object->getId(), $field => $id],
                    (isset($rowData[$id]) && is_array($rowData[$id])) ? $rowData[$id] : []
                );
            }
            /* Fix if some rows have extra data */
            $allFields = [];

            foreach ($data as $i => $row) {
                foreach ($row as $key => $value) {
                    $allFields[$key] = $key;
                }
            }

            foreach ($data as $i => $row) {
                foreach ($allFields as $key) {
                    if (!array_key_exists($key, $row)) {
                        $data[$i][$key] = null;
                    }
                }
            }
            /* End fix */
            $this->getConnection()->insertMultiple($table, $data);
        }
    }

    /**
     * Update post connections
     * @param  AbstractModel $object
     * @param  Array $newRelatedIds
     * @param  Array $oldRelatedIds
     * @param  String $tableName
     * @param  String  $field
     * @param  Array  $rowData
     * @return void
     */
    public function updateLinks(AbstractModel $object, array $newRelatedIds, array $oldRelatedIds, $tableName, $field, $rowData = [])
    {
        $this->_updateLinks($object, $newRelatedIds, $oldRelatedIds, $tableName, $field, $rowData);
    }

    protected function _beforeSave(AbstractModel $object)
    {
        if (is_array($object->getData('customer_group_ids'))) {
            $object->setData('customer_group_ids', implode(',', $object->getData('customer_group_ids')));
        }

        if (is_array($object->getData('category_ids'))) {
            $arr = $object->getData('category_ids');

            if ($arr[0] == '') {
                unset($arr[0]);
            }

            $object->setData('category_ids', implode(',', $arr));
        }

        /* Conditions */
        if ($object->getRule('conditions')) {
            $catalogRule = $this->catalogRuleFactory->create();
            $catalogRule->loadPost(['conditions' => $object->getRule('conditions')]);
            $catalogRule->beforeSave();

            if ($catalogRule->getConditionsSerialized() != $object->getConditionsSerialized()) {
                $appyRulesLink = $this->url->getUrl('*/*/apply');

                $this->messageManager->addNotice(
                    __('You have modified conditions for "Products To Display", to apply new conditions <a href="%1" >click here</a>', $appyRulesLink)
                );
            }

            $object->setData(
                'conditions_serialized',
                $catalogRule->getConditionsSerialized()
            );
        }

        /* Actions */
        if ($object->getRule('actions') && $object->getData('block_position') != 'custom') {
            $salesRule = $this->salesRuleFactory->create();
            $salesRule->loadPost(['conditions' => $object->getRule('actions')]);
            $salesRule->beforeSave();
            $object->setData(
                'actions_serialized',
                $salesRule->getConditionsSerialized()
            );
        } else {
            $object->setData('actions_serialized', null);
        }

        /* Store View IDs */
        if (is_array($object->getStoreIds())) {
            $object->setStoreIds(
                implode(',', $object->getStoreIds())
            );
        }
        return parent::_beforeSave($object);
    }

    /**
     * Assign post to store views, categories, related posts, etc.
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $oldIds = (array)$this->lookupStoreIds($object->getId());
        $newIds =explode(',', $object->getStoreIds());

        if (!$newIds || in_array(0, $newIds)) {
            $newIds = [0];
        }

        $this->_updateLinks($object, $newIds, $oldIds, 'magefan_autorp_rule_store', 'store_id');

        return parent::_afterSave($object);
    }

    /**
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object)
    {
        if ($object->getId()) {
            $storeIds = $this->lookupStoreIds($object->getId());
            $object->setData('store_ids', $storeIds);
        }

        return parent::_afterLoad($object);
    }


    /**
     * Get rows to which specified item is assigned
     * @param  int $postId
     * @param  string $tableName
     * @param  string $field
     * @return array
     */
    protected function _lookupAll($postId, $tableName, $field)
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()->from(
            $this->getTable($tableName),
            $field
        )->where(
            'rule_id = ?',
            (int)$postId
        );

        return $adapter->fetchAll($select);
    }

    /**
     * @param $object
     * @return array
     */
    public function getRelatedIds($object): array
    {
        $result =$this->_lookupIds($object->getId(), 'magefan_autorp_index', 'related_ids');
        if (empty($result) || empty($result[0])) {
            return [];
        }

        $result = explode(',', $result[0]);
        return $result;
    }
}
