<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Observer\Backend\Magefan;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogRule\Model\RuleFactory;

class ArpRuleSaveBefore implements ObserverInterface
{

    protected $propertiesToUnset = [
        'category_ids',
        'apply_same_as_condition',
        'same_as_conditions_apply_to',
        'from_one_category_only',
        'only_with_lower_price',
        'only_with_higher_price',
        'who_bought_this_also_bought',
        'who_viewed_this_also_viewed',
        'customer_group_ids',
        'start_date',
        'finish_date',
        'sort_by'
    ];

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $rule = $observer->getRule();

        foreach ($this->propertiesToUnset as $property) {
            $rule->unsetData($property);
        }

        $rule->setData('template',\Magefan\AutoRelatedProduct\Model\Config\Source\RelatedTemplate::DEFAULT);
    }
}
