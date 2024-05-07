<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model\Config\Source;

class RelatedTemplate implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @const string
     */
    const DEFAULT = 'Magento_Catalog::product/list/items.phtml';

    /**
     * @const string
     */
    const COMPARE = 'Magefan_AutoRelatedProductExtra::product/list/compare.phtml';

    /**
     * @const string
     */
    const FBT = 'Magefan_AutoRelatedProductExtra::product/list/frequently-bought-together.phtml';

    /**
     * Options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
            ['value' => self::DEFAULT, 'label' => __('Default Related Template')],
            ['value' => self::COMPARE, 'label' => __('Compare Template (Extra)')],
            ['value' => self::FBT, 'label' => __('Frequently Bought Together Template (Extra)')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this->toOptionArray() as $item) {
            $array[$item['value']] = $item['label'];
        }
        return $array;
    }
}
