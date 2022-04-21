<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model\ResourceModel\Rule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterface;

/**
 * Class Collection
 */
class Collection extends AbstractCollection implements RelatedCollectionInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'magefan_arp_rule_collection';

    /*
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getObject() in this case
     *
     * @var string
     */
    protected $_eventObject = 'collection';

    /**
     * Define resource model
     *
     * @return void
     */

    /**
     * @var int
     */
    protected $_storeId;

    protected function _construct()
    {
        $this->_init(\Magefan\AutoRelatedProduct\Model\Rule::class, \Magefan\AutoRelatedProduct\Api\RelatedResourceModelInterface::class);
        $this->_map['fields']['id'] = 'main_table.id';
        $this->_map['fields']['store']   = 'store_table.store_id';
        $this->_map['fields']['group']   = 'group_table.group_id';
    }

    /**
     * Join store relation table if there is store filter
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        foreach (['store', 'group'] as $key) {
            if ($this->getFilter($key)) {
                $joinOptions = new \Magento\Framework\DataObject();
                $joinOptions->setData([
                    'key' => $key,
                    'fields' => [],
                    'fields' => [],
                ]);
                $this->_eventManager->dispatch(
                    'autorp_post_collection_render_filter_join',
                    ['join_options' => $joinOptions]
                );
                $this->getSelect()->join(
                    [$key . '_table' => $this->getTable('magefan_autorp_rule_' . $key)],
                    'main_table.id = ' . $key . '_table.rule_id',
                    $joinOptions->getData('fields')
                )->group(
                    'main_table.id'
                );
            }
        }
        parent::_renderFiltersBefore();
    }

    /**
     * Perform operations after collection load
     *
     * @return $this
     */
    protected function _afterLoad()
    {
      /*  $items = $this->getColumnValues('id');
        if (count($items)) {
            $connection = $this->getConnection();
            $tableName = $this->getTable('magefan_autorp_rule_store');
            $select = $connection->select()
                ->from(['cps' => $tableName])
                ->where('cps.rule_id IN (?)', $items);
            $result = [];
            foreach ($connection->fetchAll($select) as $item) {
                if (!isset($result[$item['rule_id']])) {
                    $result[$item['rule_id']] = [];
                }
                $result[$item['rule_id']][] = $item['store_id'];
            }
            if ($result) {
                foreach ($this as $item) {
                    $ruleId = $item->getData('id');
                    if (!isset($result[$ruleId])) {
                        continue;
                    }
                    if ($result[$ruleId] == 0) {
                        $stores = $this->_storeManager->getStores(false, true);
                        $storeId = current($stores)->getId();
                    } else {
                        $storeId = $result[$item->getData('id')];
                    }
                    $item->setData('_first_store_id', $storeId);
                    $item->setData('store_ids', $result[$ruleId]);
                }
            }
        }

        $this->_previewFlag = false; */
        return parent::_afterLoad();
    }

    /**
     * Add store filter to collection
     * @param array|int|\Magento\Store\Model\Store  $store
     * @param boolean $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if ($store === null) {
            return $this;
        }

        if (!$this->getFlag('store_filter_added')) {
            $this->setFlag('store_filter_added', 1);

            if (is_array($store)) {
                foreach ($store as $k => $v) {
                    if ($k == 'like') {
                        if (is_object($v) && $v instanceof \Zend_Db_Expr && (string)$v == "'%0%'") {
                            return $this;
                        } else {
                            $this->addFilter('store', $store, 'public');
                            return $this;
                        }
                    }
                }
            }

            if ($store instanceof \Magento\Store\Model\Store) {
                $this->_storeId = $store->getId();
                $store = [$store->getId()];
            }

            if (!is_array($store)) {
                $this->_storeId = $store;
                $store = [$store];
            }

            if (in_array(\Magento\Store\Model\Store::DEFAULT_STORE_ID, $store)) {
                return $this;
            }

            if ($withAdmin) {
                $store[] = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            }

            $this->addFilter('store', ['in' => $store], 'public');
        }

        return $this;
    }

    /**
     * Add customer group filter to collection
     * @param array|int|null $groupId
     * @return $this
     */
    public function addGroupFilter($groupId = null)
    {
        if (!$this->getFlag('group_filter_added') && $groupId !== null) {
            $this->addFilter('group', ['in' => $groupId], 'public');
            $this->setFlag('group_filter_added', true);
        }

        return $this;
    }

    /**
     * Add field filter to collection
     *
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (is_array($field)) {
            if (count($field) > 1) {
                return parent::addFieldToFilter($field, $condition);
            } elseif (count($field) === 1) {
                $field = $field[0];
                $condition = isset($condition[0]) ? $condition[0] : $condition;
            }
        }

        if ($field === 'store_id' || $field === 'store_ids') {
            return $this->addStoreFilter($condition);
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Add status filter to collection
     * @return $this
     */
    public function addActiveFilter()
    {
        return $this->addFieldToFilter('status', 1);
    }

    /**
     * @param $blockPosition
     * @return $this
     */
    public function addPositionFilter($blockPosition)
    {
        return $this->addFieldToFilter('block_position', $blockPosition);
    }
}
