<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Api\Data;

/**
 * Interface RuleInterface
 * @package Magefan\AutoRelatedProduct\Api\Data
 */
interface RuleInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const ID = 'id';
    const RULE_ID = 'id';

    /**
     * Get customer group id's
     * @return string|null
     */
    public function getStoreIds();

    /**
     * Set customer group id's
     * @param string
     * @return mixed
     */
    public function setStoreIds($storeIds);

    /**
     * Set rule_id
     * @param string $ruleId
     * @return \Magefan\AutoRelatedProduct\Api\Data\RuleInterface
     */
    public function setRuleId($ruleId);

    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param string $id
     * @return \Magefan\AutoRelatedProduct\Api\Data\RuleInterface
     */
    public function setId($id);

    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string
     * @return mixed
     */
    public function setName($name);
}
