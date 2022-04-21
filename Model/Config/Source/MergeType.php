<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model\Config\Source;

/**
 * Class MergeType
 */
class MergeType implements \Magento\Framework\Data\OptionSourceInterface
{
    const MERGE   = 'Merge';
    const INSTEAD = 'Instead';

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray(): array
    {
        $options = [];
        $options = array_merge_recursive($options, [
            ['label' => __('Add to Current(Native) Related Products'), 'value' => self::MERGE],
            ['label' => __('Add Instead Current Related Products'), 'value' => self::INSTEAD]
        ]);

        return $options;
    }
}
