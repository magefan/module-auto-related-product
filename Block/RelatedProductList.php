<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Block;

use Magento\Catalog\Block\Product\Context;
use Magefan\AutoRelatedProduct\Api\ConfigInterface as Config;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magefan\AutoRelatedProduct\Model\RuleManager;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Catalog\Model\Product;

class RelatedProductList extends AbstractProduct implements IdentityInterface
{
    /**
     * @var Collection
     */
    protected $_itemCollection;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $_template = 'Magento_Catalog::product/list/items.phtml';

    /**
     * @var string
     */
    protected $_hTemplate = 'Magento_Catalog::product/slider/product-slider-container.phtml';

    protected $rule = null;

    protected $ruleValidator;

    public function __construct(
        Context $context,
        Config $config,
        RuleManager $ruleManager,
        array $data = []
    ) {
        $this->config = $config;
        $this->ruleManager = $ruleManager;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        $theme = $this->_design->getDesignTheme();
        while ($theme) {
            if ('Hyva/default' == $theme->getCode()) {
                return $this->_hTemplate;
            }
            $theme = $theme->getParentTheme();
        }

        return $this->getPassedTemplate() ?: $this->getTemplateFromRule() ?: parent::getTemplate();
    }

    /**
     * @return string
     */
    private function getPassedTemplate(): string
    {
        return $this->_data['template'] ?? '';
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getTemplateFromRule(): string
    {
        $rule = $this->getRule();
        return $rule ? (string)$rule->getTemplate() : '';
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toHtml(): string
    {
        if (!$this->config->isEnabled() || !$this->getRule()) {
            return '';
        }

        $html = parent::_toHtml();

        if (!$html) {
            return '';
        }

        $html = str_replace((string)__('Related Products'), (string)__($this->getTitle()), $html);

        $replaceFrom = $replaceTo = [];
        $ruleId = $this->getRule()->getId();

        if (!$this->canItemsAddToCart()
            || 'catalog_product_view' != $this->getRequest()->getFullActionName()
        ) {
            $replaceFrom = array_merge(
                $replaceFrom,
                ['block-actions', 'field choice related']
            );
            $replaceTo = array_merge(
                $replaceTo,
                ['block-actions hide-by-rule-' . $ruleId, 'field choice related hide-by-rule-' . $ruleId]
            );
        }

        if (!$this->canItemsAddToCart()) {

            $replaceFrom = array_merge(
                $replaceFrom,
                [' tocart ']
            );
            $replaceTo = array_merge(
                $replaceTo,
                [' tocart hide-by-rule-' . $ruleId]
            );
        }

        if ($replaceFrom) {

            $html = str_replace($replaceFrom, $replaceTo, $html);
            $html .= '<style>.hide-by-rule-' . $ruleId . '{display:none!important}</style>';
        }

        return $html;

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
            $this->_itemCollection =
                $this->ruleManager->getReletedProductsColletion(
                    $this->getRule(),
                    [
                        'current_category' => $this->getCategory(),
                        'current_product' => $this->getProduct()
                    ]
                );
        }

        return $this->_itemCollection;
    }

    /**
     * Synonim to getItems
     *
     * @return Collection
     */
    public function getItemCollection()
    {
        return $this->getItems();
    }

    /**
     * Check if there is any items
     *
     * @return bool
     */
    public function hasItems(): bool
    {
        return count($this->getItems()) ? true : false;
    }

    public function getIdentities()
    {
        $identities = [];

        if ($this->getProduct()) {
            $identities = [Product::CACHE_TAG . '_' . $this->getProduct()->getId()];
        }

        if (count($this->getItems())) {
            foreach ($this->getItems() as $item) {
                foreach ($item->getIdentities() as $identity) {
                    $identities[] = $identity;
                }
            }
        } else {
            $identities[] = Product::CACHE_TAG;
        }

        return $identities;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getBlockModelData('items_type', 'getItemsType') ?: 'related';
    }

    /**
     * @return bool
     */
    public function canItemsAddToCart(): bool
    {
        return $this->getBlockModelData('display_add_to_cart', 'getDisplayAddToCart') ? true : false;
    }

    /**
     * Retrieve currently viewed category object
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategory()
    {
        if (!$this->hasData('category')) {
            $this->setData('category', $this->_coreRegistry->registry('current_category'));
        }
        return $this->getData('category');
    }

    /**
     * @return array|mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRule()
    {
        $rule = $this->getData('rule');

        $ruleId = ($rule && $rule->getId()) ? $rule->getId() : $this->getData('rule_id');

        if ($ruleId) {
            $rule = $this->ruleManager->getRuleById((int)$ruleId);

            $this->setData('rule', $rule);
        }

        return $this->getData('rule');
    }
}
