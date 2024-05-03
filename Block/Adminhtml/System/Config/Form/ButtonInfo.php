<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Block\Adminhtml\System\Config\Form;


use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ButtonInfo implements ButtonProviderInterface
{
    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Duplicate (Plus)'),
            'class' => 'my-custom-class',
            'on_click' => 'versionsManager.showAlert("Plus or Extra")',
            'sort_order' => 10,
        ];
    }
}

