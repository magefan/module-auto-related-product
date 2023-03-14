<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\AutoRelatedProduct\Setup;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

/**
 * Class InstallData
 */
class InstallData implements InstallDataInterface
{

    private $eavSetupFactory;

    /**
     * Constructor
     *
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'mfarp_from_one_category',
            [
                'type' => 'int',
                'label' => 'From One Category',
                'input' => 'boolean',
                'source' => '\Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'frontend' => '',
                'required' => false,
                'backend' => '',
                'sort_order' => '30',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => null,
                'visible' => false,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => '',
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_used_for_promo_rules' => true,
                'option' => ['values' => []]
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'mfarp_with_higher_price',
            [
                'type' => 'int',
                'label' => 'With Higher Price',
                'input' => 'boolean',
                'source' => '\Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'frontend' => '',
                'required' => false,
                'backend' => '',
                'sort_order' => '30',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => null,
                'visible' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => '',
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_used_for_promo_rules' => true,
                'option' => ['values' => []]
            ]
        );

        $setup->getConnection()->insert(
            $setup->getTable('magefan_autorp_rule'),
            [
                'id' => 1,
                'status' => 1,
                'name' => 'Products from The Same Category',
                'priority' => 20,
                'store_ids' => 0,
                'block_position' => 'product_into_related',
                'merge_type' => 'Merge',
                'from_one_category_only' => 1,
                'block_title' => 'Related Products',
                'sort_by' => 1,
                'number_of_products' => 6
            ]
        );

        $setup->getConnection()->insert(
            $setup->getTable('magefan_autorp_rule_store'),
            [
                'rule_id' => 1,
                'store_id' => 0,
            ]
        );
    }
}
