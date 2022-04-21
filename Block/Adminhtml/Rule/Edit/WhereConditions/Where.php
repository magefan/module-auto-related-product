<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\AutoRelatedProduct\Block\Adminhtml\Rule\Edit\WhereConditions;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Where implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    /**
     * @param AbstractElement $element
     * @return array|string|string[]
     */
    public function render(AbstractElement $element)
    {
        $html = '';

        if ($element->getRule() && $element->getRule()->getConditions()) {
            $html = str_replace('conditions', 'actions', $element->getRule()->getConditions()->asHtmlRecursive());
        }

        return $html;
    }
}
