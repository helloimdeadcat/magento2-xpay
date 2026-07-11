define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'xpay',
        component: 'Stagem_Xpay/js/view/payment/method-renderer/xpay-method'
    });

    return Component.extend({});
});
