<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\AutoRelatedProduct\Setup;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;

/**
 * Class InstallData
 */
class InstallData implements InstallDataInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
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
