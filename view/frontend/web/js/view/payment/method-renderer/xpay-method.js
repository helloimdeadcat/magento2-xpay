define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/payment/additional-validators',
    'mage/url',
    'Magento_Checkout/js/model/full-screen-loader'
], function (
    Component,
    placeOrderAction,
    additionalValidators,
    url,
    fullScreenLoader
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Stagem_Xpay/payment/xpay-form'
        },

        getImageSrc: window.xpay.xpayImageUrl,

        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            if (!this.validate() || !additionalValidators.validate()) {
                return false;
            }

            this.isPlaceOrderActionAllowed(false);
            fullScreenLoader.startLoader();

            placeOrderAction(this.getData(), false, this.messageContainer)
                .fail(this.handlePlaceOrderFail.bind(this))
                .done(this.afterPlaceOrder.bind(this))
                .always(function () {
                    fullScreenLoader.stopLoader();
                });

            return true;
        },

        handlePlaceOrderFail: function () {
            this.isPlaceOrderActionAllowed(true);
        },

        afterPlaceOrder: function (orderId) {
            window.location.replace(
                url.build('xpay/payment/checkout?nocache=' + Date.now() + '&order=' + orderId)
            );
        }
    });
});
