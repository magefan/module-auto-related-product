<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Observer\Product;

use Magento\Framework\Event\Observer;
use Magefan\AutoRelatedProduct\Model\Indexer\Rule;
class SaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magefan\AutoRelatedProduct\Model\Indexer\Rule
     */
    private $ruleIndexer;

    /**
     * @param \Magefan\AutoRelatedProduct\Model\Indexer\Rule $ruleIndexer
     */
    public function __construct(
       Rule $ruleIndexer
    )
    {
        $this->ruleIndexer = $ruleIndexer;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $productId = $observer->getEvent()->getProduct()->getId();
        $this->ruleIndexer->executeRow($productId);
    }
}