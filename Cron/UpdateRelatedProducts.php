<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\AutoRelatedProduct\Cron;

use Magefan\AutoRelatedProduct\Model\AutoRelatedProductAction;
use Magefan\AutoRelatedProduct\Api\ConfigInterface as Config;

class UpdateRelatedProducts
{
    /**
     * @var AutoRelatedProductAction
     */
    protected $autoRelatedProductAction;

    /**
     * @var Config
     */
    protected $config;

    /**
     * BoughtUpdate constructor.
     * @param RelatedUpdater $relatedUpdater
     */
    public function __construct(
        AutoRelatedProductAction $autoRelatedProductAction,
        Config $config
    ) {
        $this->config = $config;
        $this->autoRelatedProductAction = $autoRelatedProductAction;
    }

    /**
     *
     */
    public function execute()
    {
        if ($this->config->isEnabled()) {
            $this->autoRelatedProductAction->execute();
        }
    }
}
