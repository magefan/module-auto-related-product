<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\AutoRelatedProduct\Controller\Adminhtml\Rule;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magefan\AutoRelatedProduct\Model\AutoRelatedProductAction;
use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterfaceFactory;
use Magefan\AutoRelatedProduct\Api\ConfigInterface;

/**
 * Class Apply
 */
class Apply extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a  admin session
     */
    const ADMIN_RESOURCE = 'Magefan_AutoRelatedProduct:rule';

    /**
     * @var AutoRelatedProductAction
     */
    protected $autoRelatedProductAction;

    /**
     * @var RelatedCollectionInterfaceFactory
     */
    protected $ruleCollection;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @param Context $context
     * @param RelatedUpdater $relatedUpdater
     * @param RelatedCollectionInterfaceFactory $ruleCollectionFactory
     * @param ConfigInterface $config
     */
    public function __construct(
        Context $context,
        AutoRelatedProductAction $autoRelatedProductAction,
        RelatedCollectionInterfaceFactory $ruleCollectionFactory,
        ConfigInterface $config
    ) {
        $this->config = $config;
        $this->ruleCollection = $ruleCollectionFactory;
        $this->autoRelatedProductAction = $autoRelatedProductAction;
        parent::__construct($context);
    }

    /**
     * Action execute
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->config->isEnabled()) {
            $this->messageManager->addError(
                __(
                    strrev(
                        'noitalsnarT> snoisnetxE nafegaM > noitarugifnoC >
            serotS ot etagivan esaelp noisnetxe eht elbane ot ,delbasid si noitalsnarT nafegaM'
                    )
                )
            );
            $redirect = $this->resultRedirectFactory->create();
            return $redirect->setPath('admin/index/index');
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        try {
            $countRules = $this->ruleCollection->create()->getSize();

            if (!$countRules) {
                $this->messageManager->addError(__('Cannot find any rule.'));
            }
            if ($this->config->isEnabled()) {
                $this->autoRelatedProductAction->execute();
                $this->messageManager->addSuccess(__('Rules has been applied.'));
            } else {
                $this->messageManager->addNotice(__('Please enable the extension to apply rules.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Something went wrong. %1', $e->getMessage()));
        }

        return $resultRedirect;
    }
}
