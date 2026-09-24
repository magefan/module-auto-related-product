<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\AutoRelatedProduct\Plugin\Frontend\Magento\Catalog\Block\Product\ProductList;

use Magefan\AutoRelatedProduct\Api\RelatedItemsProcessorInterface;
use Magefan\AutoRelatedProduct\Model\NativeBlockTitleProcessor;

class Upsell
{
    /**
     * @param RelatedItemsProcessorInterface $relatedItemsProcessor
     */
    private $relatedItemsProcessor;

    /**
     * @var NativeBlockTitleProcessor
     */
    private $nativeBlockTitleProcessor;

    /**
     * @param RelatedItemsProcessorInterface $relatedItemsProcessor
     * @param NativeBlockTitleProcessor $nativeBlockTitleProcessor
     */
    public function __construct(
        RelatedItemsProcessorInterface $relatedItemsProcessor,
        NativeBlockTitleProcessor $nativeBlockTitleProcessor
    ) {
        $this->relatedItemsProcessor = $relatedItemsProcessor;
        $this->nativeBlockTitleProcessor = $nativeBlockTitleProcessor;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetItemCollection($subject, $result)
    {
        return $this->relatedItemsProcessor->execute($subject, $result, 'product_into_upsell');
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterToHtml($subject, $result)
    {
        return $this->nativeBlockTitleProcessor->execute($subject, $result);
    }
}
