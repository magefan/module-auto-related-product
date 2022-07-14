<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Model;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\SalesRule\Model\RuleFactory;

/**
 * Class Validator model
 */
class ActionValidator
{

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\SalesRule\Model\Rule
     */
    protected $ruleFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @param DateTime $date
     * @param Session $checkoutSession
     * @param RuleFactory $ruleFactory
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param Registry $registry
     * @param UrlInterface|null $urlInterface
     */
    public function __construct(
        DateTime $date,
        Session $checkoutSession,
        RuleFactory $ruleFactory,
        ProductRepositoryInterface $productRepository,
        RequestInterface $request,
        Registry $registry,
        UrlInterface $urlInterface = null
    ) {
        $this->date = $date;
        $this->checkoutSession = $checkoutSession;
        $this->ruleFactory = $ruleFactory;
        $this->productRepository =$productRepository;
        $this->request = $request;
        $this->registry = $registry;
        $this->urlInterface = $urlInterface ?:  \Magento\Framework\App\ObjectManager::getInstance()->get(UrlInterface::class);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $rule
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isRestricted(\Magento\Framework\Model\AbstractModel $rule): bool
    {
        if (null !== $rule->getData('is_actions_restricted')) {
            return $rule->getData('is_actions_restricted');
        }

        if (!$this->isConditionsTrue($rule)) {
            $rule->setData('is_actions_restricted', true);
            return true;
        }
        $rule->setData('is_actions_restricted', false);
        return false;
    }

    /**
     * @param $actionRule
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isConditionsTrue($actionRule): bool
    {
        if (!$actionRule->getData('actions_serialized')) {
            return true;
        }

        $rule = $this->ruleFactory->create();
        $rule->setData('conditions_serialized', $actionRule->getData('actions_serialized'));

        $conditions = $rule->getConditions();

        if (empty($conditions['conditions'])) {
            return true;
        }

        $quote = $this->checkoutSession->getQuote();

        if ($quote->getItemsQty() == $quote->getItemVirtualQty()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }

        $address->setTotalQty($quote->getItemsQty());

        $product = $actionRule->getProduct();

        if (!$product) {
            $product = $this->registry->registry('product');
        }

        $isMfcmsdrGetController = ($this->request->getFullActionName() == 'autorp_block_get');

        if (!$product) {
            try {
                $productId = $this->request->getParam('product_id');
                if ($productId) {
                    $product = $this->productRepository->getById($productId);
                    if ($isMfcmsdrGetController) {
                        $this->registry->register('product', $product);
                    }
                } else {
                    $product = false;
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $product = false;
            }
        }

        $address = clone $address;

        if ($product && $product->getId()) {
            foreach ($product->getData() as $k => $v) {
                if (!$address->getData($k)) {
                    $address->setData($k, $v);
                }
                if ($k == 'quantity_and_stock_status') {
                    if ($v['is_in_stock'] == false) {
                        $address->setData('quantity_and_stock_status', 0);
                    } else {
                        $address->setData('quantity_and_stock_status', 1);
                    }
                }
            }
        }

        if ($isMfcmsdrGetController) {
            $address->setData('page_action_name', $this->request->getParam('fan'));
            $address->setData('page_uri', $this->request->getParam('p'));

            if ($categoryId = $this->request->getParam('category_id')) {
                $address->setData('catalog_category_ids', $categoryId);
            }
        } else {
            $address->setData('page_action_name', $this->request->getFullActionName());
            $address->setData('page_uri', $this->urlInterface->getCurrentUrl());

            if ($this->request->getFullActionName() == 'catalog_category_view') {
                $address->setData('catalog_category_ids', $this->request->getParam('id'));
            }
        }

        return $rule->validate($address);
    }

    /**
     * @param $current
     * @param $start
     * @param $finish
     * @return bool
     */
    public function isInTimeFrame($current, $start, $finish): bool
    {
        if ($start != $finish) {
            if ($start && $finish) {
                return ($current >= $start && $current <= $finish);
            } elseif ($start) {
                return ($start <= $current);
            } elseif ($finish) {
                return ($finish >= $current);
            }
        }
        return true;
    }
}
