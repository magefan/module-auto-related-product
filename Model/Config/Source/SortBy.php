<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model\Config\Source;

/**
 * Class SortBy
 * @package Magefan\AutoRelatedProduct\Model\Config
 */
class SortBy implements \Magento\Framework\Data\OptionSourceInterface
{
    const NONE   =  'None';
    const RANDOM =  1;
    const NAME   =  2;
    const NEWEST =  3;
    const PRICE_DESC =  4;
    const PRICE_ASC  =  5;

    /**
     * @return array[]
     */
    public function toOptionArray():array
    {
        return [
            ['value' =>  self::NONE, 'label' => __('None')],
            ['value' =>  self::RANDOM, 'label' => __('Random')],
            ['value' =>  self::NAME, 'label' => __('Name')],
            ['value' =>  self::NEWEST, 'label' => __('Newest')],
            ['value' =>  self::PRICE_DESC, 'label' => __('Price (high to low)')],
            ['value' =>  self::PRICE_ASC, 'label' => __('Price (low to high)')],
        ];
    }
}
