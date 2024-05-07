<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Class Categories
 * @package Magefan\AutoRelatedProductPlus\Ui\Component\Listing\Column
 */
class Categories extends Column
{

    /**
     * @var CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * Categories constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $categoryCollectionFactory
     * @param array $components
     * @param array $data
     * @param string $storeKey
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory $categoryCollectionFactory,
        array $components = [],
        array $data = [],
        $storeKey = 'store_id'
    ) {
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item['categories'] = $this->_getCategories($item['entity_id']);
            }
        }

        return $dataSource;
    }

    /**
     * @param $entityId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getCategories($entityId)
    {
        $categoryCollection = $this->_categoryCollectionFactory->create();
        $categoryCollection->addNameToResult();
        $categoryCollection->joinField(
            'ccp_product_id',
            'catalog_category_product',
            'product_id',
            'category_id=entity_id',
            null,
            'left'
        );

        $categoryCollection->addFieldToFilter('ccp_product_id', $entityId);

        $categoriesHtml = '';
        $categories = $categoryCollection;

        if ($categories) {
            foreach ($categories as $category) {
                $path = '';
                $pathInStore = $category->getPathInStore();
                $pathIds = array_reverse(explode(',', $pathInStore));
                $categories = $category->getParentCategories();
                foreach ($pathIds as $categoryId) {
                    if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                        $path .= $categories[$categoryId]->getName() . '/';
                    }
                }

                if ($path) {
                    $path = substr($path, 0, -1);
                    $path =
                        '<div style="font-size: 90%; margin-bottom: 8px; border-bottom: 1px dotted #bcbcbc;">' . $path
                        . '</div>';
                }

                $categoriesHtml .= $path;
            }
        }

        return $categoriesHtml;
    }
}
