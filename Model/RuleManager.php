<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model;

use Magefan\AutoRelatedProduct\Model\Config\Source\SortBy;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magefan\AutoRelatedProduct\Api\RuleRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magefan\AutoRelatedProduct\Model\ActionValidator;
use Magefan\AutoRelatedProduct\Api\Data\RuleInterface;
use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterfaceFactory as RuleCollectionFactory;

class RuleManager
{

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var CatalogConfig
     */
    protected $catalogConfig;

    /**
     * @var Visibility
     */
    protected $catalogProductVisibility;

    /**
     * @var Stock
     */
    protected $stockFilter;


    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magefan\AutoRelatedProduct\Model\ActionValidator
     */
    protected $ruleValidator;

    /**
     * @var
     */
    protected $_itemCollection;

    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var
     */
    protected $getCategoryByProduct;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CatalogConfig $catalogConfig
     * @param Visibility $catalogProductVisibility
     * @param Stock $stockFilter
     * @param EventManagerInterface $_eventManager
     * @param RuleRepositoryInterface $ruleRepository
     * @param StoreManagerInterface $storeManager
     * @param \Magefan\AutoRelatedProduct\Model\ActionValidator $ruleValidator
     */
    public function __construct
    (
        ProductCollectionFactory $productCollectionFactory,
        CatalogConfig $catalogConfig,
        Visibility $catalogProductVisibility,
        Stock $stockFilter,
        EventManagerInterface $_eventManager,
        RuleRepositoryInterface $ruleRepository,
        StoreManagerInterface $storeManager,
        ActionValidator $ruleValidator,
        RuleCollectionFactory $ruleCollectionFactory,
        \Magefan\Community\Api\GetCategoryByProductInterface $getCategoryByProduct = null
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogConfig = $catalogConfig;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->stockFilter = $stockFilter;
        $this->_eventManager = $_eventManager;
        $this->ruleRepository = $ruleRepository;
        $this->storeManager = $storeManager;
        $this->ruleValidator = $ruleValidator;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->getCategoryByProduct = $getCategoryByProduct ?:\Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magefan\Community\Api\GetCategoryByProductInterface::class);
    }

    /**
     * @param $rule
     * @param array $params
     * @return array|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getReletedProductsColletion(RuleInterface $rule, array $params = [])
    {
        if (!$rule) {
            return [];
        }

        $currentProduct = $params['current_product'] ?? false;
        $currentCategory = $params['current_category'] ?? false;
        $pageSize = $params['page_size'] ?? false;
        $currentPage = $params['current_page'] ?? false;

        if (!$pageSize) {
            $pageSize = $rule->getData('number_of_products') ?: 10;
        }

        $this->_itemCollection = $this->productCollectionFactory->create()
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds())
            ->addStoreFilter()
            ->setPageSize((int)$pageSize);

        if ($currentPage) {
            $this->_itemCollection->setCurPage((int)$currentPage);
        }

        if (!$rule->getData('display_out_of_stock')) {
            $this->addOutOfStockFilter($rule);
        }

        if ($relatedIds = $rule->getRelatedIds()) {
            $this->_itemCollection->addFieldToFilter('entity_id', ['in' =>  $relatedIds]);
        }

        if ($currentProduct) {
            $this->_itemCollection->addFieldToFilter('entity_id', ['neq' => $currentProduct->getId()]);
        }

        $this->addSortBy((int)$rule->getData('sort_by'));

        $this->_eventManager->dispatch('autorp_relatedproducts_block_load_collection_before', [
            'rule' => $rule,
            'collection' => $this->_itemCollection,
            'product' => $currentProduct,
            'category' => $currentCategory
        ]);

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $item) {
            $item->setDoNotUseCategoryId(true);
        }

        return $this->_itemCollection;
    }

    /**
     * @param RuleInterface $rule
     */
    protected function addOutOfStockFilter(RuleInterface $rule): void
    {
        $this->_itemCollection->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite();

        $this->stockFilter->addInStockFilterToCollection($this->_itemCollection);
    }

    /**
     * @param $sortBy.
     */
    protected function addSortBy(int $sortBy): void
    {
        switch ($sortBy) {
            case SortBy::RANDOM:
                $this->_itemCollection->getSelect()->order('rand()');
                break;
        }
    }

    /**
     * @param int $ruleId
     * @return false|RuleInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRuleById(int $ruleId)
    {
        try {
            $rule = $this->ruleRepository->get($ruleId);

            if (!$rule->isVisibleOnStore($this->storeManager->getStore()->getId()) || $this->ruleValidator->isRestricted($rule)) {
                $rule = false;
            }
        } catch (NoSuchEntityException $e) {
            $rule = false;
        }

        return $rule;
    }


    /**
     * @param string $blockPosition
     * @return false|mixed
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRuleForPosition($blockPosition = '')
    {
        $rule = false;
        $storeId = $this->storeManager->getStore()->getId();

        $rules = $this->ruleCollectionFactory->create()
            ->addActiveFilter()
            ->addPositionFilter($blockPosition)
            ->addStoreFilter($storeId)
            ->setOrder('priority', 'ASC');

        foreach ($rules as $item) {
            if (!$this->ruleValidator->isRestricted($item)) {
                $rule = $item;
                break;
            }
        }

        return $rule;
    }
}
