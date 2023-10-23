<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Ui\DataProvider\Rule\Form;

use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterface as Collection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\RequestInterface;

/**
 * Class DataProvider
 */
class RuleDataProvider extends AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * RuleDataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Collection $ruleCollection
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        Collection $ruleCollection,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->request = $request;
        $this->collection = $ruleCollection;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        return parent::getMeta();
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $rule) {
            try {
                $rule = $rule->load($rule->getId());
            } catch (NoSuchEntityException $e) {
                return;
            }

            $data = $rule->getData();

            /* Set data */
            $this->loadedData[$rule->getId()] = $data;
        }

        return $this->loadedData;
    }
}
