<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\AutoRelatedProduct\Api;

use Magento\Framework\View\Element\AbstractBlock;

interface RelatedItemsProcessorInterface
{
    /**
     * @param AbstractBlock $subject
     * @param $result
     * @param $blockPosition
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(AbstractBlock $subject, $result, $blockPosition);
}
