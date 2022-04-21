<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Block;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magefan\AutoRelatedProduct\Api\ConfigInterface as Config;
use Magefan\AutoRelatedProduct\Model\ActionValidator;
use Magefan\AutoRelatedProduct\Model\Config\Source\DisplayMode;
use Magefan\AutoRelatedProduct\Model\Config\Source\SortBy;
use Magefan\AutoRelatedProduct\Model\RuleRepository;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogInventory\Helper\Stock;

class RelatedProductList extends AbstractProduct
{
    /**
     * @var Collection
     */
    protected $_itemCollection;

    /**
     * @var RuleRepository
     */
    protected $ruleRepository;

    /**
     * @var ActionValidator
     */
    protected $ruleValidator;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var Visibility
     */
    protected $catalogProductVisibility;

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var string
     */
    protected $_template = 'Magefan_AutoRelatedProduct::product/list/related_product_items.phtml';

    /**
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param RuleRepository $ruleRepository
     * @param ActionValidator $ruleValidator
     * @param Config $config
     * @param Visibility $catalogProductVisibility
     * @param Manager $moduleManager
     * @param StoreManagerInterface $storeManager
     * @param Stock $stockFilter
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        RuleRepository $ruleRepository,
        ActionValidator $ruleValidator,
        Config $config,
        Visibility $catalogProductVisibility,
        Manager $moduleManager,
        StoreManagerInterface $storeManager,
        Stock $stockFilter,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->ruleRepository = $ruleRepository;
        $this->ruleValidator = $ruleValidator;
        $this->config = $config;
        $this->moduleManager = $moduleManager;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->storeManager = $storeManager;
        $this->stockFilter = $stockFilter;
        parent::__construct($context, $data);
    }
    /**
     * @return string
     */
    public function getTitle(): string
    {
        return (string)$this->getBlockModelData('title', 'getBlockTitle');
    }

    /**
     * @return int
     */
    public function getNumberOfProducts(): int
    {
        return (int)$this->getBlockModelData('number_of_products', 'getNumberOfProducts');
    }

    /**
     * @return bool
     */
    public function isDisplayAddToCart(): bool
    {
        return (bool)$this->getBlockModelData('display_add_to_cart', 'getDisplayAddToCart');
    }

    /**
     * @param $key
     * @param $method
     * @return array|mixed|null
     */
    protected function getBlockModelData($key, $method)
    {
        if (null === $this->getData($key)) {
            if ($rule = $this->getRule()) {
                $this->setData($key, $rule->$method() ?: '');
            } else {
                $this->setData($key, '');
            }
        }
        return $this->getData($key);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toHtml(): string
    {
        return (!$this->config->isEnabled() || !$this->getRule() || $this->ruleValidator->isRestricted($this->getRule()))
            ? ''
            : parent::_toHtml();
    }

    /**
     * @return array|false|\Magefan\AutoRelatedProduct\Api\Data\RuleInterface|mixed|null
     */
    public function getRule()
    {
        $rule = $this->getData('rule');

        if (null === $rule) {
            $rule = false;

            if ($ruleId = (int)$this->getData('rule_id')) {
                try {
                    $rule = $this->ruleRepository->get($ruleId);

                    if (!$rule->isVisibleOnStore($this->storeManager->getStore()->getId())) {
                        $rule = false;
                    }
                } catch (NoSuchEntityException $e) {
                    $rule = false;
                }
            }
        }

        $this->setData('rule', $rule);

        return $rule;
    }

    /**
     * Prepare data
     *
     * @return $this
     */
    protected function _prepareData()
    {
        $this->_itemCollection = $this->productCollectionFactory->create()
            ->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
            ->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds())
            ->addStoreFilter();

        if (!$this->getRule()->getData('display_out_of_stock')) {
            $this->_itemCollection = $this->_addProductAttributesAndPrices($this->_itemCollection);
            $this->stockFilter->addInStockFilterToCollection($this->_itemCollection);
        }

        $this->_itemCollection
            ->setPageSize($this->getNumberOfProducts() ?: 10); ///!!!! 10

        if ($relatedIds = $this->getRule()->getRelatedIds()) {
            $this->_itemCollection->addFieldToFilter('entity_id', ['in' =>  $relatedIds]);
        }

        if ($product = $this->getProduct()) {
            $this->_itemCollection->addFieldToFilter('entity_id', ['neq' => $product->getId()]);

            switch ($this->getRule()->getDisplayMode()) {
                case DisplayMode::FROM_ONE_CATEGORY:
                    $this->_itemCollection->addCategoriesFilter(['in' => $product->getCategoryIds() ?: [-1]]);
                    break;
                case DisplayMode::HIGHER_PRICE:
                    $price = $product->getFinalPrice();

                    if (is_array($price)) {
                        $price = array_shift($price);
                    }

                    $this->_itemCollection->getSelect()->where("price_index.final_price > ?", $price);
                    break;
            }
        }

        switch ($this->getRule()->getData('sort_by')) {
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


        $this->_eventManager->dispatch('autorp_relatedproducts_block_load_collection_before', [
            'block' => $this,
            'collection' => $this->_itemCollection,
            'product' => $product
        ]);

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

    /**
     * Get collection items
     *
     * @return Collection
     */
    public function getItems()
    {
        /**
         * getIdentities() depends on _itemCollection populated, but it can be empty if the block is hidden
         * @see https://github.com/magento/magento2/issues/5897
         */
        if ($this->_itemCollection === null) {
            $this->_prepareData();
        }
        return $this->_itemCollection;
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [];
    }
}
