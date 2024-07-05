<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model;

use Magefan\AutoRelatedProduct\Api\Data\RuleInterface;

class Rule extends \Magento\Framework\Model\AbstractModel implements \Magefan\AutoRelatedProduct\Api\Data\RuleInterface
{
    /**
     * Rule's Status
     */
    const STATUS_ENABLED = 1;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'magefan_arp_rule';

    /*
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getObject() in this case
     *
     * @var string
     */
    protected $_eventObject = 'rule';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magefan\AutoRelatedProduct\Api\RelatedResourceModelInterface::class);
    }

    /**
     * Retrieve model title
     * @param  boolean $plural
     * @return string
     */
    public function getOwnTitle($plural = false)
    {
        return $plural ? __('Auto Related Product Rules') : __('Auto Related Product Rule');
    }

    /**
     * @return array|mixed|null
     */
    public function getId()
    {
        return $this->getData('id');
    }

    /**
     * @param string $id
     * @return RuleInterface
     */
    public function setId($id)
    {
        return $this->setData('id', $id);
    }

    /**
     * @param $ruleId
     * @return RuleInterface|Rule
     */
    public function setRuleId($ruleId)
    {
        return $this->setData(self::RULE_ID, $ruleId);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getName()
    {
        return $this->getData('name');
    }

    /**
     * @param $name
     * @return Rule|mixed
     */
    public function setName($name)
    {
        return $this->setData('name', $name);
    }

    /**
     * @return array|mixed|null
     */
    public function getDescription()
    {
        return $this->getData('description');
    }

    /**
     * @return array|mixed|null
     */
    public function getStatus()
    {
        return $this->getData('status');
    }



    /**
     * @return false|string|string[]|null
     */
    public function getStoreIds()
    {
        return $this->getData('store_ids');
    }

    /**
     * @param $storeIds
     * @return Rule|mixed
     */
    public function setStoreIds($storeIds)
    {
        return $this->setData('store_ids', $storeIds);
    }

    /**
     * @return array|mixed|null
     */
    public function getPriority()
    {
        return $this->getData('priority');
    }

    /**
     * @return array|mixed|null
     */
    public function getDisplayContainer()
    {
        return $this->getData('block_position');
    }

    /**
     * @return array|mixed|null
     */
    public function getConditions()
    {
        return $this->getData('conditions_serialized');
    }

    /**
     * @return array|mixed|null
     */
    public function getActions()
    {
        return $this->getData('actions_serialized');
    }

    /**
     * @return $this
     */
    public function getDataModel()
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return ($this->getStatus() == self::STATUS_ENABLED);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isVisibleOnStore($storeId): bool
    {
        return  $this->isActive()
            && (null === $storeId || array_intersect([0, $storeId], $this->getStoreIds()));
    }

    /**
     * @return string
     */
    public function getRuleBlockIdentifier(): string
    {
        $identifier = $this->getBlockPosition();

        if ((0 !== $this->getData('from_one_category_only') || 0 !== $this->getData('only_with_higher_price')) && 'custom' != $this->getBlockPosition()) {
            $identifier .= '_' . '1';

        }
        if ($this->getId()) {
            $identifier .= '_' . $this->getId();
        }

        return $identifier;
    }

    /**
     * @return array|mixed|null
     */
    public function getRelatedIds()
    {
        $key = 'related_ids';

        if (!$this->hasData($key)) {
            $ids = $this->getResource()->getRelatedIds($this);
            $this->setData($key, $ids);
        }

        return $this->getData($key);
    }
}
