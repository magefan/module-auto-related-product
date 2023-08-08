<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model;

use Magefan\AutoRelatedProduct\Model\RuleManager;
use Magefan\AutoRelatedProduct\Api\RelatedItemsProcessorInterface;
use Magefan\AutoRelatedProduct\Model\Config\Source\MergeType;
use Magento\Framework\View\Element\AbstractBlock;

class RelatedItemsProcessor implements RelatedItemsProcessorInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RuleManager
     */
    protected $ruleManager;

    /**
     * @param Config $config
     * @param \Magefan\AutoRelatedProduct\Model\RuleManager $ruleManager
     */
    public function __construct(
        Config $config,
        RuleManager $ruleManager
    ) {
        $this->config = $config;
        $this->ruleManager = $ruleManager;
    }

    /**
     * @param AbstractBlock $subject
     * @param $result
     * @param $blockPosition
     * @return \Magefan\AutoRelatedProduct\Block\Collection|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(AbstractBlock $subject, $result, $blockPosition)
    {
        if (!$this->config->isEnabled() || !$rule = $this->ruleManager->getRuleForPosition($blockPosition)) {
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
