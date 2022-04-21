<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Plugin\Magento\SalesRule\Model\Rule\Condition;

use Magento\CatalogRule\Model\Rule\Condition\ProductFactory;
use Magento\Framework\App\RequestInterface;
use Magento\SalesRule\Model\Rule\Condition\Address;
use Magento\SalesRule\Model\Rule\Condition\Combine as SubjectCombine;
use Magento\SalesRule\Model\RuleFactory as SaleRuleFactory;

/**
 * Class CombinePlugin
 */
class Combine
{
    /**
     * @var SaleRuleFactory
     */
    protected $saleRuleFactory;

    /**
     * @var Address
     */
    protected $ruleAddress;

    /**
     * @var ProductFactory
     */
    protected $ruleProduct;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * CombinePlugin constructor.
     * @param SaleRuleFactory $saleRuleFactory
     * @param Address $ruleAddress
     * @param ProductFactory $ruleProduct
     * @param RequestInterface $request
     */
    public function __construct(
        SaleRuleFactory $saleRuleFactory,
        Address $ruleAddress,
        ProductFactory $ruleProduct,
        RequestInterface $request
    ) {
        $this->saleRuleFactory = $saleRuleFactory;
        $this->ruleAddress = $ruleAddress;
        $this->ruleProduct = $ruleProduct;
        $this->request = $request;
    }

    /**
     * @param Combine $subject
     * @param $result
     * @return array
     */
    public function afterGetNewChildSelectOptions(SubjectCombine $subject, $result)
    {
        if ($this->request->getModuleName() == 'autorp') {
            $conditions = [];
            $conditions = array_merge_recursive(
                $conditions,
                [
                    [
                        'value' => \Magento\SalesRule\Model\Rule\Condition\Product\Found::class,
                        'label' => __('Product attribute combination')
                    ],
                    [
                        'value' => \Magento\SalesRule\Model\Rule\Condition\Product\Combine::class,
                        'label' => __('Conditions Combination')
                    ]
                ]
            );

            $productAttributes = $this->ruleProduct->create()->loadAttributeOptions()->getAttributeOption();
            $attributesProduct = [];

            foreach ($productAttributes as $code => $label) {
                $attributesProduct[] = [
                    'value' => 'Magento\CatalogRule\Model\Rule\Condition\Product|' . $code,
                    'label' => $label,
                ];
            }

            $conditions = array_merge_recursive(
                $conditions,
                [
                    ['label' => __('Product Attribute'), 'value' => $attributesProduct]
                ]
            );

            $result = $conditions;
        }

        return $result;
    }
}
