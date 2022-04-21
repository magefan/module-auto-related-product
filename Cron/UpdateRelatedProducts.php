<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\AutoRelatedProduct\Cron;

use Magefan\AutoRelatedProduct\Model\ResourceModel\RelatedUpdater;
use Magefan\AutoRelatedProduct\Api\ConfigInterface as Config;

class UpdateRelatedProducts
{
    /**
     * @var RelatedUpdater
     */
    protected $relatedUpdater;

    /**
     * @var Config
     */
    protected $config;

    /**
     * BoughtUpdate constructor.
     * @param RelatedUpdater $relatedUpdater
     */
    public function __construct(
        RelatedUpdater $relatedUpdater,
        Config $config
    ) {
        $this->config = $config;
        $this->relatedUpdater = $relatedUpdater;
    }

    /**
     *
     */
    public function execute()
    {
        if ($this->config->isEnabled()) {
            $this->relatedUpdater->execute();
        }
    }
}
