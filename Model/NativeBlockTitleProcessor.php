<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\AbstractBlock;

class NativeBlockTitleProcessor
{
    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param Escaper $escaper
     */
    public function __construct(
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
    }

    /**
     * Replace native block heading with rule block title
     *
     * @param AbstractBlock $subject
     * @param mixed $html
     * @return mixed
     */
    public function execute(AbstractBlock $subject, $html)
    {
        if (!$html || !is_string($html)) {
            return $html;
        }

        // Title is set by RelatedItemsProcessor while the block renders its items
        $title = trim((string)$subject->getData('mfautorp_title'));
        if ('' === $title) {
            return $html;
        }

        $title = $this->escaper->escapeHtml((string)__($title), ['span', 'p']);

        $result = preg_replace_callback(
            '#(<strong\b[^>]*\bid="block-[^"]*-heading"[^>]*>)(.*?)(</strong>)#s',
            function ($matches) use ($title) {
                return $matches[1] . $title . $matches[3];
            },
            $html,
            1
        );

        return null === $result ? $html : $result;
    }
}
