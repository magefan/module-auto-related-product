<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Plugin\Magento\SalesRule\Model\Rule\Condition\Product;

use Magento\SalesRule\Model\Rule\Condition\Product\Combine as SubjectCombine;
use Magento\SalesRule\Model\Rule\Condition\Product;
use Magento\Framework\App\RequestInterface;

class Combine
{
    /**
     * @var Product
     */
    protected $product;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param Product $product
     * @param RequestInterface $request
     */
    public function __construct(
        Product $product,
        RequestInterface $request
    ) {
        $this->product = $product;
        $this->request = $request;
    }

    public function afterGetNewChildSelectOptions(SubjectCombine $subject, $result)
    {
        if ($this->request->getModuleName() == 'autorp' || 'autorp_rule_form' == $this->request->getParam('form_namespace')) {

            $productAttributes = $this->product->loadAttributeOptions()->getAttributeOption();
            $pAttributes = [];

            foreach ($productAttributes as $code => $label) {
                if (strpos($code, 'quote_item_') !== 0) {
                    $pAttributes[] = [
                        'value' => \Magento\SalesRule\Model\Rule\Condition\Product::class . '|' . $code,
                        'label' => $label,
                    ];
                }
            }

            $conditions = [];
            $conditions = array_merge_recursive(
                $conditions,
                [
                    [
                        'value' => \Magento\SalesRule\Model\Rule\Condition\Product\Combine::class,
                        'label' => __('Conditions Combination'),
                    ],
                    ['label' => __('Product Attribute'), 'value' => $pAttributes]
                ]
            );

            $result = $conditions;
        }

        return $result;
    }
}
