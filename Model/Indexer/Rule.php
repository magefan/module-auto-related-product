<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model\Indexer;

use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterface;
use Magefan\AutoRelatedProduct\Model\AutoRelatedProductAction;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Indexer\IndexMutexInterface;

class Rule implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    const INDEXER_ID = 'magefan_autorelatedproduct_indexer';

    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var IndexMutexInterface
     */
    private $indexMutex;

    private $autoRelatedProductAction;

    /**
     * @var CacheContext
     * @since 100.0.11
     */
    protected $cacheContext;

    /**
     * @var RelatedCollectionInterface
     */
    private $relatedCollection;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param AutoRelatedProductAction $autoRelatedProductAction
     * @param RelatedCollectionInterface $relatedCollection
     * @param RuleFactory $ruleFactory
     * @param ProductRepositoryInterface $productRepository
     * @param IndexMutexInterface|null $indexMutex
     */
    public function __construct(
        IndexerRegistry                 $indexerRegistry,
        AutoRelatedProductAction $autoRelatedProductAction,
        RelatedCollectionInterface $relatedCollection,
        RuleFactory                     $ruleFactory,
        ProductRepositoryInterface            $productRepository,
        ?IndexMutexInterface                                       $indexMutex = null
    )
    {
        $this->indexerRegistry = $indexerRegistry;
        $this->autoRelatedProductAction = $autoRelatedProductAction;
        $this->relatedCollection = $relatedCollection;
        $this->ruleFactory = $ruleFactory;
        $this->productRepository = $productRepository;
        $this->indexMutex = $indexMutex ?? ObjectManager::getInstance()->get(IndexMutexInterface::class);
    }

    /**
     * @param $ids
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute($ids)
    {
        $this->getIndexRuleByProduct($ids);
        /*
        * ????
       $this->executeAction([])
           */
    }

    /**
     * @return void
     */
    public function executeFull()
    {
        $this->executeAction([]);
    }

    /**
     * @param array $ids
     * @return void
     * @throws NoSuchEntityException
     */
    public function executeList(array $ids)
    {
        var_dump('dgjksgj');exit();
        $this->getIndexRuleByProduct($ids);
        /*
        * ????
       $this->executeAction([])
           */
    }

    /**
     * @param $id
     * @return void
     * @throws NoSuchEntityException
     */
    public function executeRow($id)
    {
        $this->getIndexRuleByProduct([$id]);
    }

    /**
     * @param $ids
     * @return $this
     */
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

    /**
     * @param array $ids
     * @return void
     * @throws NoSuchEntityException
     */
    public function getIndexRuleByProduct(array $ids)
    {
        $ruleIdFoIndex = [];
        $autoRelatedProductRules = $this->relatedCollection->addFieldToFilter('status', 1);
        $ids = array_unique($ids);
        foreach ($ids as $id) {
            $product = $this->productRepository->getById($id);

            foreach ($autoRelatedProductRules as $autoRelatedProductRule) {
                if (in_array($autoRelatedProductRule->getId(), $ruleIdFoIndex)) {
                    continue;
                }
                $rule = $this->ruleFactory->create();
                $rule->setData('conditions_serialized', $autoRelatedProductRule->getConditions());
                $rule->setData('store_ids', $autoRelatedProductRule->getStoreIds());
                if ($rule->getConditions()->validate($product)) {
                    $ruleIdFoIndex[] = $autoRelatedProductRule->getId();
                }
            }
        }
        $this->executeAction($ruleIdFoIndex);
    }
}