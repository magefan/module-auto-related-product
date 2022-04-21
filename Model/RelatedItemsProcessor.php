<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model;

use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterfaceFactory as RuleCollectionFactory;
use Magefan\AutoRelatedProduct\Api\RelatedItemsProcessorInterface;
use Magefan\AutoRelatedProduct\Model\Config\Source\MergeType;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Store\Model\StoreManagerInterface;

class RelatedItemsProcessor implements RelatedItemsProcessorInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ActionValidator
     */
    protected $validator;

    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Config $config
     * @param ActionValidator $validator
     * @param RuleCollectionFactory $ruleCollectionFactory
     */
    public function __construct(
        Config                $config,
        ActionValidator       $validator,
        RuleCollectionFactory $ruleCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->validator = $validator;
        $this->ruleCollectionFactory=$ruleCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param AbstractBlock $subject
     * @param $result
     * @param $blockPosition
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(AbstractBlock $subject, $result, $blockPosition)
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        $storeId = $this->storeManager->getStore()->getId();
        $rules = $this->ruleCollectionFactory->create()
            ->addActiveFilter()
            ->addPositionFilter($blockPosition)
            ->addStoreFilter($storeId)
            ->setOrder('priority', 'ASC');
        $rule = false;

        foreach ($rules as $item) {
            if (!$this->validator->isRestricted($item)) {
                $rule = $item;
                break;
            }
        }

        if (!$rule) {
            return $result;
        }

        if ($rule->getMergeType() == MergeType::MERGE) {
            $resultCount = count($result);
            $limit = $rule->getNumberOfProducts();

            if ($resultCount >= $limit) {
                return $result;
            }

            $products = $subject->getLayout()->createBlock(
                \Magefan\AutoRelatedProduct\Block\RelatedProductList::class
            )->setData('rule', $rule)->getItems();

            $resultIds = [];

            foreach ($result as $r) {
                $resultIds[] = $r->getId();
            }

            foreach ($products as $product) {
                if ($resultCount >= $limit) {
                    break;
                }

                if (in_array($product->getId(), $resultIds)) {
                    continue;
                }

                if (is_object($result)) {
                    $result->addItem($product);
                } else {
                    $result[] = $product;
                }

                $resultCount++;
            }
        } elseif ($rule->getMergeType() == MergeType::INSTEAD) {
            $result = $subject->getLayout()->createBlock(
                \Magefan\AutoRelatedProduct\Block\RelatedProductList::class
            )->setData('rule', $rule)->getItems();
        }

        return $result;
    }
}
