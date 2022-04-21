<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\AutoRelatedProduct\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $tableName = $installer->getTable('magefan_autorp_rule');

        if ($installer->getConnection()->isTableExists($tableName) != true) {
            $table = $installer->getConnection()->newTable($tableName)
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'name',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => ''],
                'Name'
            )
            ->addColumn(
                'description',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => ''],
                'Description'
            )
            ->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Status'
            )
            ->addColumn(
                'store_ids',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '2M',
                [],
                'Store View Ids'
            )
            ->addColumn(
                'priority',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                [],
                'Rule Priority'
            )
            ->addColumn(
                'block_position',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                100,
                [],
                'Position Where Display Product'
            )
            ->addColumn(
                'merge_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                10,
                [],
                'Type Of Merge Related Products'
            )
            ->addColumn(
                'from_one_category_only',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'From One Category Only'
            )
            ->addColumn(
                'only_with_higher_price',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Only With Higher Price'
            )
            ->addColumn(
                'only_with_lower_price',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Only With Lower Price'
            )
            ->addColumn(
                'conditions_serialized',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '2M',
                [],
                'Conditions Serialized'
            )
            ->addColumn(
                'actions_serialized',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '2M',
                [],
                'Actions Serialized'
            )
            ->addColumn(
                'block_title',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '2M',
                [],
                'Block Title'
            )
            ->addColumn(
                'sort_by',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '2M',
                [],
                'Sort Product By'
            )
            ->addColumn(
                'display_add_to_cart',
                Table::TYPE_SMALLINT,
                null,
                [],
                'Display Add To Cart'
            )
            ->addColumn(
                'number_of_products',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                [],
                'Product Number'
            )
            ->addColumn(
                'display_out_of_stock',
                Table::TYPE_SMALLINT,
                null,
                [],
                'Display Out Of Stock Products'
            )
                ->addIndex(
                    $installer->getIdxName('magefan_autorp_rule', ['status']),
                    ['status']
                )
                ->addIndex(
                    $installer->getIdxName('magefan_autorp_rule', ['block_position']),
                    ['block_position']
                )
            ->setComment('Related Product Rules')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);
        }
        $tableNameStore = $installer->getTable('magefan_autorp_rule_store');
        if ($installer->getConnection()->isTableExists($tableNameStore) != true) {
            $tableStore = $installer->getConnection()->newTable(
                $tableNameStore
            )->addColumn(
                'rule_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Rule ID'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Store ID'
            )->addIndex(
                $installer->getIdxName('magefan_autorp_rule_store', ['store_id']),
                ['store_id']
            )->addForeignKey(
                $installer->getFkName(
                    'magefan_autorp_rule_store',
                    'rule_id',
                    'magefan_autorp_rule',
                    'id'
                ),
                'rule_id',
                $installer->getTable('magefan_autorp_rule'),
                'id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName('magefan_autorp_rule_store', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Magefan Automatic Related Products To Store Linkage Table'
            );
            $installer->getConnection()->createTable($tableStore);
        }
        $autoRelatedIndexTable = $installer->getTable('magefan_autorp_index');

        if ($installer->getConnection()->isTableExists($autoRelatedIndexTable) != true) {
            $table = $installer->getConnection()->newTable($autoRelatedIndexTable)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
                )
                ->addColumn(
                    'rule_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Rule ID'
                )
                ->addColumn(
                    'identifier',
                    Table::TYPE_TEXT,
                    '2M',
                    [],
                    'Identifier'
                )
                ->addColumn(
                    'related_ids',
                    Table::TYPE_TEXT,
                    '2M',
                    [],
                    'Related Product IDs'
                )
                // Create Foreign KEY issue!!
                ->setComment('Related Product Index Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8')
                ->addForeignKey(
                    $installer->getFkName('magefan_autorp_index', 'rule_id', 'magefan_autorp_rule', 'id'),
                    'rule_id',
                    $installer->getTable('magefan_autorp_rule'), /* main table name */
                    'id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )->addIndex(
                    $installer->getIdxName(
                        'magefan_autorp_index',
                        ['rule_id'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    ['rule_id'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                );

            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }
}
