<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model\Indexer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Indexer\IndexMutexInterface;

class Rule implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    const INDEXER_ID = 'magefan_autorelatedproduct_indexer';

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     * php bin/magento indexer:reindex magefan_autorelatedproduct_indexer
     */
    protected $indexerRegistry;

    /**
     * @var IndexMutexInterface
     */
    private $indexMutex;

    private $autoRelatedProductAction;

    /**
     * @var \Magento\Framework\Indexer\CacheContext
     * @since 100.0.11
     */
    protected $cacheContext;

    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magefan\AutoRelatedProduct\Model\AutoRelatedProductAction $autoRelatedProductAction,
        ?IndexMutexInterface $indexMutex = null
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->autoRelatedProductAction = $autoRelatedProductAction;
        $this->indexMutex = $indexMutex ?? ObjectManager::getInstance()->get(IndexMutexInterface::class);
    }


    public function execute($ids)
    {
        $this->executeList($ids);
    }

    public function executeFull()
    {
        $this->executeAction([]);
    }

    public function executeList(array $ids){
        $this->executeAction($ids);
    }


    public function executeRow($id)
    {
        $this->executeAction([$id]);
    }

    protected function executeAction($ids)
    {
        $ids = array_unique($ids);
        $indexer = $this->indexerRegistry->get(static::INDEXER_ID);

        if ($indexer->isScheduled()) {
            $this->indexMutex->execute(
                static::INDEXER_ID,
                function () use ($ids) {
                    $this->autoRelatedProductAction->execute($ids);
                }
            );
        } else {
            $this->autoRelatedProductAction->execute($ids);
        }

        return $this;
    }
}