<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\AutoRelatedProduct\Plugin\Frontend\Magento\TargetRule\Block\Catalog\Product\ProductList;

use Magefan\AutoRelatedProduct\Api\RelatedItemsProcessorInterface;

class Related
{
    /**
     * @param RelatedItemsProcessorInterface $relatedItemsProcessor
     */
    private $relatedItemsProcessor;

    /**
     * @param RelatedItemsProcessorInterface $relatedItemsProcessor
     */
    public function __construct(
        RelatedItemsProcessorInterface $relatedItemsProcessor
    )
    {
        $this->relatedItemsProcessor = $relatedItemsProcessor;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetAllItems($subject, $result)
    {
        return $this->relatedItemsProcessor->execute($subject, $result, 'product_into_related');
    }
}