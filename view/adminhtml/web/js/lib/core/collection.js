/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

define([
    'underscore',
    'mageUtils',
    'uiRegistry',
    'uiComponent',
    'jquery',
    'Magento_Ui/js/lib/view/utils/async'
], function (_, utils, registry, Element, $, async) {
    'use strict';

    return Element.extend({
        defaults: {
            visible: true
        },

        /**
         * Called when another element was added to current component.
         *
         * @param {Object} elem - Instance of an element that was added.
         * @returns {Collection} Chainable.
         */
        initElement: function (elem) {

            if (elem.additionalClasses) {
                elem.additionalClasses[this.className] = true;
            }

            elem.initContainer(this);

            return this;
        },

        /**
         * Show element.
         *
         * @returns {Abstract} Chainable.
         */
        show: function () {
            $('.' + this.className).show();

            return this;
        },

        /**
         * Hide element.
         *
         * @returns {Abstract} Chainable.
         */
        hide: function () {
            var element = $('.' + this.className),
                self = this;

            if (element.length) {
                element.hide();
            } else {
                async.async('.' + self.className, function (item) {
                    $(item).hide();
                }.bind(this));
            }

            return this;
        }
    });
});
