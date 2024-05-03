<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Observer\Backend\Magefan;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogRule\Model\RuleFactory;

class ArpRuleSaveBefore implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var RuleFactory
     */
    protected $catalogRuleFactory;

    /**
     * @param RequestInterface $request
     * @param RuleFactory $catalogRuleFactory
     */
    public function __construct(
        RequestInterface $request,
        RuleFactory $catalogRuleFactory
    ) {
        $this->request=$request;
        $this->catalogRuleFactory = $catalogRuleFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
var_dump('gggggggggggggggggggggggg');
        $rule = $observer->getRule();
//        $rule->unsetData('customer_group_ids');
//        $rule->unsetData('category_ids');
//        $rule->unsetData('apply_same_as_condition');
//        $rule->unsetData('start_date');
//        $rule->unsetData('finish_date');
//var_dump($rule->getData());exit();

         if (is_array($rule->getData('customer_group_ids'))) {
             $rule->setData('customer_group_ids', implode(',', $rule->getData('customer_group_ids')));
         }

       if (is_array($rule->getData('category_ids'))) {
            $arr = $rule->getData('category_ids');

            if ($arr[0] == '') {
                unset($arr[0]);
            }

            $rule->setData('category_ids', implode(',', $arr));
        }

        if (!$rule->getData('duplicated')) {
            /* Same As Conditions */
            if ($rule->getData('apply_same_as_condition') == 'true' && $rule->getRule('same_as_conditions')) {
                $catalogRule = $this->catalogRuleFactory->create();
                $catalogRule->loadPost(['conditions' => $rule->getRule('same_as_conditions')]);
                $catalogRule->beforeSave();
                $rule->setData('same_as_conditions_serialized', $catalogRule->getConditionsSerialized());
            } else {
                $rule->setData('same_as_conditions_serialized', null);
            }

            if ($this->request->getParam('category_ids') === null) {
                $rule->setData('category_ids', '');
            }
        }
    }
}
