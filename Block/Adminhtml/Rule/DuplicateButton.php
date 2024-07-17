<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Block\Adminhtml\Rule;

use Magefan\Community\Block\Adminhtml\Edit\GenericButton;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DuplicateButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array|string
     */
    public function getButtonData()
    {
        $data = [];

        if (!$this->authorization->isAllowed("Magefan_AutoRelatedProduct::rule")) {
            return $data;
        }

        if ($this->getObjectId()) {
            $data = [
                'label' => __('Duplicate (Plus)'),
                'class' => 'duplicate',
                'on_click' => '(typeof versionsManager !== "undefined" && versionsManager._currentPlan == "Basic") ? versionsManager.showAlert("Plus or Extra") : window.location=\'' . $this->getDuplicateUrl() . '\'',
                'sort_order' => 40,
            ];
        }
        return $data;
    }

    /**
     * @return string
     */
    public function getDuplicateUrl()
    {
        return $this->getUrl('mfautorp/*/duplicate', ['id' => $this->getObjectId()]);
    }
}
