/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

define([
    'jquery',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'domReady!'
], function ($, uiRegistry, select) {
    'use strict';
    return select.extend({
        defaults: {
            customName: '${ $.parentName }.${ $.index }_input'
        },

        initialize: function () {
            this._super();
            var self = this;
            setTimeout(function () {
                self.fieldDepend(self.value())
            }, 1000);
        },

        onUpdate: function(value) {
            this.fieldDepend(value);
            return this._super();
        },

        fieldDepend: function(value) {
            if (!value) {
                $('div[class="rule-tree"]').hide();
                $('.container.sample').hide();
                $('div[data-index="merge_type"]').hide();
                $('button[data-index="preview_button"]').hide();
                $('fieldset[data-index="container_categories"]').hide();
                $('#autorp_rule_formrule_same_as_conditions_fieldset_').hide();
                $('[data-index="apply_same_as_condition"]').hide();
                $('[data-index="display_mode"]').hide();
                return;
            }

            if (value.startsWith("custom")) {
                $('div[class="rule-tree"]').show();
                $('.container.sample').show();
                $('div[data-index="merge_type"]').hide();
                $('button[data-index="preview_button"]').show();
                $('fieldset[data-index="container_categories"]').hide();
                $('#autorp_rule_formrule_same_as_conditions_fieldset_').show();
                $('[data-index="apply_same_as_condition"]').show();

                if (uiRegistry.get('autorp_rule_form.autorp_rule_form.what_to_display').source.data.apply_same_as_condition) {
                    $('#autorp_rule_formrule_same_as_conditions_fieldset_').show();
                } else {
                    $('#autorp_rule_formrule_same_as_conditions_fieldset_').hide();
                }

                $('[data-index="where_to_display_product"] div[class="rule-tree"]').hide();
                $('[data-index="display_mode"]').show();
                return;
            }

            if (value.startsWith("product_")) {
                $('div[class="rule-tree"]').show();
                $('.container.sample').hide();
                if(value.startsWith("product_into")){
                    $('div[data-index="merge_type"]').show();
                }
                else{
                    $('div[data-index="merge_type"]').hide();
                }
                $('button[data-index="preview_button"]').show();
                $('fieldset[data-index="container_categories"]').hide();

                $('[data-index="apply_same_as_condition"]').show();

                if (uiRegistry.get('autorp_rule_form.autorp_rule_form.what_to_display').source.data.apply_same_as_condition) {
                    $('#autorp_rule_formrule_same_as_conditions_fieldset_').show();
                } else {
                    $('#autorp_rule_formrule_same_as_conditions_fieldset_').hide();
                }

                //$('[data-index="where_to_display_product"] [label="Product Attribute"], [data-index="where_to_display_product"] [value="Magento\\\\SalesRule\\\\Model\\\\Rule\\\\Condition\\\\Product\\\\Combine"]').prop( "disabled", false);
                $('[data-index="display_mode"]').show();
                return;
            }

            if (value.startsWith("cart_")) {
                $('div[class="rule-tree"]').show();
                $('.container.sample').hide();
                if(value.startsWith("cart_into")){
                    $('div[data-index="merge_type"]').show();
                }
                else{
                    $('div[data-index="merge_type"]').hide();
                }
                $('button[data-index="preview_button"]').show();
                $('fieldset[data-index="container_categories"]').hide();
                $('#autorp_rule_formrule_same_as_conditions_fieldset_').hide();
                $('[data-index="apply_same_as_condition"]').hide();
                //$('[data-index="where_to_display_product"] [label="Product Attribute"], [data-index="where_to_display_product"] [value="Magento\\\\SalesRule\\\\Model\\\\Rule\\\\Condition\\\\Product\\\\Combine"]').prop( "disabled", true );
                $('[data-index="display_mode"]').hide();
                return;
            }

            if (value.startsWith("category_")) {
                $('div[class="rule-tree"]').show();
                $('.container.sample').hide();
                $('div[data-index="merge_type"]').hide();
                $('button[data-index="preview_button"]').show();
                $('fieldset[data-index="container_categories"]').show();
                $('#autorp_rule_formrule_same_as_conditions_fieldset_').hide();
                $('[data-index="apply_same_as_condition"]').hide();
                //$('[data-index="where_to_display_product"] [label="Product Attribute"], [data-index="where_to_display_product"] [value="Magento\\\\SalesRule\\\\Model\\\\Rule\\\\Condition\\\\Product\\\\Combine"]').prop( "disabled", true );
                $('[data-index="display_mode"]').hide();
                return;
            }
        },
    });
});
