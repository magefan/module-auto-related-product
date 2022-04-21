<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model\Config\Source;

use Magefan\AutoRelatedProduct\Api\PositionsInterface;

/**
 * Class CustomerGroup
 */
class Positions implements \Magento\Framework\Data\OptionSourceInterface, PositionsInterface
{
    /**
     * Templates objects
     *
     * @var []
     */
    private $options;

    /**
     * TemplatePool constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->options as $option) {
            $option['label'] = __($option['label']);
            if (isset($option['value']) && is_array($option['value'])) {
                $option['value'] = array_values($option['value']);
                foreach ($option['value'] as $key => $item) {
                    $option['value'][$key]['label'] = __($option['value'][$key]['label']);
                }
            }
            $options[] = $option;
        }
        return $options;
    }
}
