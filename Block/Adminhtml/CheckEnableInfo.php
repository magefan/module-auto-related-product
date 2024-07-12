<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magefan\AutoRelatedProduct\Api\ConfigInterface;

/**
 * Class Check EnableInfo Block
 */
class CheckEnableInfo extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magefan\AutoRelatedProduct\Model\Config
     */
    protected $config;

    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }
}
