<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\AutoRelatedProduct\Block\Adminhtml\Rule\Edit;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;

/**
 * Work with conditions
*/
class WhereConditions extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Ui\Component\Layout\Tabs\TabInterface
{
    /**
     * @var Fieldset
     */
    protected $rendererFieldset;

    /**
     * @var \Magento\Rule\Block\Conditions
     */
    protected $conditions;

    /**
     * @var string
     */
    protected $_nameInLayout = 'actions_apply_to';

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param WhereConditions\Where $conditions
     * @param Fieldset $rendererFieldset
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context              $context,
        \Magento\Framework\Registry                          $registry,
        \Magento\Framework\Data\FormFactory                  $formFactory,
        WhereConditions\Where                                $conditions,
        Fieldset $rendererFieldset,
        \Magento\SalesRule\Model\RuleFactory                 $ruleFactory,
        array                                                $data = []
    ) {
        $this->rendererFieldset = $rendererFieldset;
        $this->conditions = $conditions;
        $this->ruleFactory = $ruleFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getTabClass()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabUrl()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Where Conditions');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Where Conditions');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return \Magento\Backend\Block\Widget\Form\Generic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_model');
        $form = $this->addTabToForm($model);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * @param $model
     * @param $fieldsetId
     * @param $formName
     * @return \Magento\Framework\Data\Form
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addTabToForm($model, $fieldsetId = 'actions_fieldset', $formName = 'autorp_rule_form')
    {
        if (!$this->getRequest()->getParam('js_form_object')) {
            $this->getRequest()->setParam('js_form_object', $formName);
        }

        $model = $this->_coreRegistry->registry('current_model');
        $rule = $this->ruleFactory->create();
        $rule->setData('conditions_serialized', $model->getData('actions_serialized'));
        $model = $rule;
        $fieldSetId = 'autorp_rule_formrule_actions_fieldset_' . $model->getId();
        $newChildUrl = $this->getUrl(
            'sales_rule/promo_quote/newActionHtml/form/' . $fieldSetId,
            ['form_namespace' => $formName]
        );
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_where');
        $renderer = $this->getLayout()->createBlock(Fieldset::class);
        $renderer = $renderer->setNameInLayout(
            'mfautorp_where_actions_serialized'
        )->setTemplate(
            'Magento_CatalogRule::promo/fieldset.phtml'
        )->setNewChildUrl(
            $newChildUrl
        )->setFieldSetId(
            $fieldSetId
        );
        $fieldset = $form->addFieldset(
            $fieldsetId,
            [
                'legend' => __(
                    'Apply the rule only if the following conditions are met (leave blank for all products).'
                )
            ]
        )->setRenderer(
            $renderer
        );
        $fieldset->addField(
            'actions',
            'text',
            [
                'name'           => 'actions',
                'label'          => __('actions'),
                'title'          => __('actions'),
                'required'       => true,
                'data-form-part' => $formName
            ]
        )->setRule(
            $model
        )->setRenderer(
            $this->conditions
        );
        $form->setValues($model->getData());
        $this->setConditionFormName($model->getConditions(), $formName);
        return $form;
    }

    /**
     * Handles addition of form name to condition and its conditions.
     *
     * @param \Magento\Rule\Model\Condition\AbstractCondition $conditions
     * @param string $formName
     * @return void
     */
    private function setConditionFormName(\Magento\Rule\Model\Condition\AbstractCondition $conditions, $formName)
    {
        $conditions->setFormName($formName);
        if ($conditions->getConditions() && is_array($conditions->getConditions())) {
            foreach ($conditions->getConditions() as $condition) {
                $this->setConditionFormName($condition, $formName);
            }
        }
    }
}
