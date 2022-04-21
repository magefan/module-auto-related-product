<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\AutoRelatedProduct\Observer\Frontend\Layout;

use Arsal\CustomTab\Model\TabConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magefan\AutoRelatedProduct\Model\Rule;
use Magefan\AutoRelatedProduct\Model\RuleRepository;
use Magento\Framework\App\RequestInterface;
use Magefan\AutoRelatedProduct\Api\ConfigInterface as Config;
use Magefan\AutoRelatedProduct\Model\ActionValidator;
use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterfaceFactory as RuleCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class GenerateBlocksAfter implements ObserverInterface
{
    /**
     * @var string PARENT_BlOCK_NAME
     */
    const PARENT_BlOCK_NAME = 'product.info.details';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ActionValidator
     */
    protected $validator;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @param Config $config
     * @param ActionValidator $validator
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config                $config,
        ActionValidator       $validator,
        RuleCollectionFactory $ruleCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->validator=$validator;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        /* @var $layout \Magento\Framework\View\Layout */
        $layout = $observer->getLayout();

        $block = $layout->getBlock(self::PARENT_BlOCK_NAME);

        if (!$block) {
            return;
        }

        $storeId = $this->storeManager->getStore()->getId();
        $rules = $this->ruleCollectionFactory->create()
            ->addActiveFilter()
            ->addStoreFilter($storeId)
            ->addPositionFilter('product_content_tab')
            ->setOrder('priority', 'ASC');

        $rule = false;

        foreach ($rules as $item) {
            if (!$this->validator->isRestricted($item)) {
                $rule = $item;
                break;
            }
        }

        if (!$rule) {
            return;
        }

        $block->addChild(
            'autorp_tab',
            \Magefan\AutoRelatedProduct\Block\RelatedProductList::class,
            [
                'title' =>$rule->getData('block_title'),
                'isTab'=>1,
                'rule' => $rule
            ]
        );
    }
}
