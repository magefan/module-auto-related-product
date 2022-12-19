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
use Magefan\AutoRelatedProduct\Model\RuleRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magefan\AutoRelatedProduct\Model\ActionValidator;

class RuleManager
{

    protected $productCollectionFactory;
    protected $catalogConfig;
    protected $catalogProductVisibility;
    protected $_eventManager;
    protected $ruleRepository;
    protected $storeManager;
    protected $categoryRepository;

    public function __construct
    (
        ProductCollectionFactory $productCollectionFactory,
        CatalogConfig $catalogConfig,
        Visibility $catalogProductVisibility,
        Stock $stockFilter,
        EventManagerInterface $_eventManager,
        RuleRepository $ruleRepository,
        StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository = null,
        ActionValidator $ruleValidator

    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogConfig = $catalogConfig;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->stockFilter = $stockFilter;
        $this->_eventManager = $_eventManager;
        $this->ruleRepository = $ruleRepository;
        $this->storeManager = $storeManager;
        $this->ruleValidator = $ruleValidator;

        $this->categoryRepository = $categoryRepository ?:\Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
    }

    public function getReletedProductsColletion($rule, array $params = [])
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

        if ($rule->getIsFromOneCategory()) {
            $this->addProductsFromTheSameCategoryFilter($currentCategory, $currentProduct, $rule);
        }

        if ($currentProduct) {
            $this->_itemCollection->addFieldToFilter('entity_id', ['neq' => $currentProduct->getId()]);

            if (($higher = $rule->getIsOnlyWithHigherPrice()) || $rule->getIsOnlyWithLowerPrice()) {
                $this->addPriceFilter($higher, $currentProduct->getFinalPrice());
            }
        }

        $this->addSortBy($rule->getData('sort_by'));

        $this->_eventManager->dispatch('autorp_relatedproducts_block_load_collection_before', [
            'rule' => $rule,
            'collection' => $this->_itemCollection,
            'product' => $currentProduct
        ]);


        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $item) {
            $item->setDoNotUseCategoryId(true);
        }

        return $this->_itemCollection;
    }

    protected function addOutOfStockFilter($rule)
    {
        $this->_itemCollection->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite();

        $this->stockFilter->addInStockFilterToCollection($this->_itemCollection);
    }

    protected function addProductsFromTheSameCategoryFilter($currentCategory, $currentProduct, $rule)
    {
        $productCategoryId = false;

        if ($currentCategory) {
            $productCategoryId = $currentCategory->getId();
        } elseif ($currentProduct) {
            $categoryIds = $currentProduct->getCategoryIds();

            if ($categoryIds) {

                $productCategory = null;
                $level = -1;
                $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();

                foreach ($categoryIds as $categoryId) {
                    try {
                        $category = $this->categoryRepository->get($categoryId);
                        if ($category->getIsActive()
                            && $category->getLevel() > $level
                            && in_array($rootCategoryId, $category->getPathIds())
                        ) {
                            $level = $category->getLevel();
                            $productCategory = $category;
                        }
                    } catch (\Exception $e) {}
                }

                if ($productCategory) {
                    $productCategoryId = $productCategory->getId();
                }
            }
        }

        if ($productCategoryId) {
            $this->_itemCollection->addCategoriesFilter(['eq' => $productCategoryId]);
        }
    }

    protected function addPriceFilter($higher, $price)
    {
        if (is_array($price)) {
            $price = array_shift($price);
        }
        $where = $higher ? "price_index.final_price > ?" :  "price_index.final_price < ?";
        $this->_itemCollection->getSelect()->where($where, $price);
    }

    protected function addSortBy($sortBy)
    {
        switch ($sortBy) {
            case SortBy::RANDOM:
                $this->_itemCollection->getSelect()->order('rand()');
                break;
            case SortBy::NAME:
                $this->_itemCollection->addAttributeToSort('name', 'ASC');
                break;
            case SortBy::NEWEST:
                $this->_itemCollection->addAttributeToSort('created_at', 'DESC');
                break;
            case SortBy::PRICE_DESC:
                $this->_itemCollection->addAttributeToSort('price', 'DESC');
            case SortBy::PRICE_ASC:
                $this->_itemCollection->addAttributeToSort('price', 'ASC');
                break;
        }
    }

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
}
