<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\AutoRelatedProduct\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Framework\Escaper;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;

use Magefan\AutoRelatedProduct\Model\Config;
use Magefan\AutoRelatedProduct\Model\AutoRelatedProductAction;


class ApplyRules extends Command
{
    const RULE_IDS_PARAM = 'ids';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var AutoRelatedProductAction
     */
    protected $autoRelatedProductAction;

    /**
     * @var Escaper
     */
    private $escaper;
    /**
     * @var State
     */
    private $state;

    /**
     * @param AutoRelatedProductAction $autoRelatedProductAction
     * @param Config $config
     * @param Escaper $escaper
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        AutoRelatedProductAction $autoRelatedProductAction,
        Config $config,
        Escaper $escaper,
        State $state,
        ?string $name = null
    ) {
        $this->config = $config;
        $this->autoRelatedProductAction = $autoRelatedProductAction;
        $this->escaper = $escaper;
        $this->state = $state;
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->config->isEnabled()) {
            try {
                $this->state->setAreaCode(Area::AREA_GLOBAL);
            } catch (LocalizedException $e) {
                $output->writeln((string)__('Something went wrong. %1', $this->escaper->escapeHtml($e->getMessage())));
            }

            $ruleIDs = (string)$input->getOption(self::RULE_IDS_PARAM);

            $ruleIDs = $ruleIDs
                ? array_map('intval', explode(',', $ruleIDs))
                : [];

            if ($ruleIDs) {
                $output->writeln('<info>' . __('The provided rule IDs: %1', '`' . implode(',', $ruleIDs) . '`') . '</info>');
                $this->autoRelatedProductAction->execute(['rule_ids' => $ruleIDs]);
            } else {
                $this->autoRelatedProductAction->execute();
            }

            $output->writeln("Rules have been applied.");
        } else {
            $output->writeln("Extension is disabled. Please turn on it.");
        }
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::RULE_IDS_PARAM,
                null,
                InputOption::VALUE_OPTIONAL,
                'Rule Ids'
            )
        ];

        $this->setDefinition($options);

        $this->setName("magefan:arp:apply");
        $this->setDescription("Apply by Rule IDs (comma separated)");

        parent::configure();
    }
}
