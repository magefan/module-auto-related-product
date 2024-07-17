<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magefan\AutoRelatedProduct\Api\ConfigInterface;
use Magefan\Community\Api\GetModuleVersionInterface;
use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterfaceFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magefan\AutoRelatedProduct\Model\Config\Source\SortBy;

class TmpInfo extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magefan\AutoRelatedProduct\Model\Config
     */
    protected $config;

    /**
     * @var GetModuleVersionInterface
     */
    protected $getModuleVersion;

    /**
     * @var RelatedCollectionInterfaceFactory
     */
    protected $ruleCollection;

    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param GetModuleVersionInterface $getModuleVersion
     * @param RelatedCollectionInterfaceFactory $ruleCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        GetModuleVersionInterface $getModuleVersion,
        RelatedCollectionInterfaceFactory $ruleCollection,
        SessionManagerInterface $session,
        array $data = []
    ) {
        $this->config = $config;
        $this->getModuleVersion = $getModuleVersion;
        $this->ruleCollection = $ruleCollection;
        $this->session = $session;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isSomeFeaturesRestricted(): bool
    {
        if ($this->getModuleVersion->execute('Magefan_AutoRelatedProductExtra') || $this->getModuleVersion->execute('Magefan_AutoRelatedProductPlus')) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getAffectedRulesByDisplayModes(): string
    {
        $rules = $this->ruleCollection->create()
            ->addFieldToFilter('status', 1);

        $connection = $rules->getConnection();
        $tableName = $rules->getMainTable();

        $conditions = [];

        if ($connection->tableColumnExists($tableName, 'from_one_category_only')) {
            $conditions[] = 'from_one_category_only = 1';
        }

        if ($connection->tableColumnExists($tableName, 'only_with_higher_price')) {
            $conditions[] = 'only_with_higher_price = 1';
        }

        if ($connection->tableColumnExists($tableName, 'only_with_lower_price')) {
            $conditions[] = 'only_with_lower_price = 1';
        }

        if (!empty($conditions)) {
            $rules->getSelect()->where(implode(' OR ', $conditions));
        }

        return implode(',', $rules->getAllIds());
    }

    /**
     * @return string
     */
    public function getAffectedRulesBySortBy(): string
    {
        $restrictedSortByOptionsIds = [
            SortBy::NAME,
            SortBy::NEWEST,
            SortBy::PRICE_DESC,
            SortBy::PRICE_ASC
        ];

        $rules = $this->ruleCollection->create()
            ->addFieldToFilter('status', 1)
            ->addFieldToFilter('sort_by', ['in' => $restrictedSortByOptionsIds]);

        return implode(',', $rules->getAllIds());
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if (!$this->config->isEnabled() || $this->session->getIsNeedToShowAlert() === false) {
            return '';
        }

        $this->session->setIsNeedToShowAlert(
            !$this->isSomeFeaturesRestricted() && ($this->getAffectedRulesByDisplayModes() || $this->getAffectedRulesBySortBy())
        );

        return parent::_toHtml();
    }
}
