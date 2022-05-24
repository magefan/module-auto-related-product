<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Plugin\Frontend\Magento\Framework\View\Result;

use Magefan\AutoRelatedProduct\Block\RelatedProductList;
use Magefan\AutoRelatedProduct\Model\ActionValidator;
use Magefan\AutoRelatedProduct\Api\ConfigInterface as Config;
use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterfaceFactory as RuleCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Result\Layout as SubjectLayout;
use Magefan\AutoRelatedProduct\Api\PositionsInterface;

class Layout
{
    /**
     * @var ActionValidator
     */
    private $validator;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RuleCollectionFactory
     */
    private $ruleCollectionFactory;

    /**
     * @var PositionsInterface
     */
    private $positionOptions;

    /**
     * @param ActionValidator $validator
     * @param Config $config
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param PositionsInterface $positionOptions
     */
    public function __construct(
        ActionValidator            $validator,
        Config                     $config,
        RuleCollectionFactory      $ruleCollectionFactory,
        StoreManagerInterface      $storeManager,
        PositionsInterface         $positionOptions
    ) {
        $this->positionOptions = $positionOptions;
        $this->validator = $validator;
        $this->config =$config;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Layout $subject
     * @param $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeRenderResult(SubjectLayout $subject, $response)
    {
        if (!$this->config->isEnabled()) {
            return null;
        }

        /* @var $layout \Magento\Framework\View\Layout */
        $layout = $subject->getLayout();

        $currentLayout = '';
        if (isset($layout->getUpdate()->getHandles()[1])) {
            $currentLayout = $layout->getUpdate()->getHandles()[1];
        }

        $containers = [
            'content.top',
            'content.bottom',
        ];

        $parentsArray = [];
        $options = $this->positionOptions->toOptionArray();

        for ($i = 0; $i < count($options); $i++) {
            if (is_array($options[$i]['value'])) {
                foreach ($options[$i]['value'] as $item) {
                    if (isset($item['parent'])) {
                        $parentsArray[$item['parent']][][$item['value']] = $item['value'];
                    }
                }
            }
        }

        $layoutElements = [];
        foreach ($parentsArray as $blockName => $positions) {
            if ($layout->getBlock($blockName)) {
                $layoutElements[] = $blockName;
            }
        }

        foreach ($containers as $container) {
            if (!$layout->isContainer($container)) {
                continue;
            }
            $layoutElements[] = $container;
        }

        foreach ($layoutElements as $layoutElement) {
            foreach ($parentsArray[$layoutElement] as $position) {
                $containerName = $layout->getParentName($layoutElement);

                if (!$containerName || !$this->isBlockAvailableOnCurrentPageType($position, $currentLayout)) {
                    continue;
                }

                $rules = $this->ruleCollectionFactory->create()
                    ->addActiveFilter()
                    ->addStoreFilter($this->storeManager->getStore()->getId())
                    ->addPositionFilter($position)
                    ->setOrder('priority', 'ASC');

                $rule = false;

                foreach ($rules as $item) {
                    if (!$this->validator->isRestricted($item)) {
                        $rule = $item;
                        break;
                    }
                }

                if (!$rule) {
                    continue;
                }


                $after = str_contains($rule->getBlockPosition(), 'after');
                $ruleBlockName = $rule->getRuleBlockIdentifier();
                $layout
                    ->addBlock(RelatedProductList::class, $ruleBlockName, $containerName)
                    ->setData('rule', $rule);
                $layout->reorderChild($containerName, $ruleBlockName, $layoutElement, $after);
            }
        }
    }

    /**
     * Check block position page type is the same as current page type [category | product | cart]
     * @param array $position
     * @param string $currentLayout
     * @return bool
     */
    private function isBlockAvailableOnCurrentPageType(array $position, string $currentLayout): bool
    {
        $position = array_shift($position);
        $position = explode('_', $position)[0];
        $currentLayout = explode('_', $currentLayout)[1];

        return (bool)($position === $currentLayout);
    }
}
