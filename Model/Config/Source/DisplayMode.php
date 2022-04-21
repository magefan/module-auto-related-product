<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model\Config\Source;

/**
 * Class DisplayMode
 * @package Magefan\AutoRelatedProduct\Model\Config
 */
class DisplayMode implements \Magento\Framework\Data\OptionSourceInterface
{
    const NONE = 'none';
    const FROM_ONE_CATEGORY = 1;
    const HIGHER_PRICE = 2;

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' =>  self::NONE, 'label' => __('Only Condition Combination')],
            ['value' =>  self::FROM_ONE_CATEGORY, 'label' => __('From One Category Only')],
            ['value' =>  self::HIGHER_PRICE, 'label' => __('Only With Higher Price')]
        ];
    }
}
