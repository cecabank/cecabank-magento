

/*browser:true*/
/*global define*/
define(
[
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/model/place-order',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/checkout-data',
    'mage/url',
],
function (
    $,
    Component,
    setPaymentInformationAction,
    additionalValidators,
    placeOrderService,
    selectPaymentMethodAction,
    customer,
    checkoutData,
    url) {
        'use strict';

        var cecabankConfigProvider = window.checkoutConfig.payment.cecabank;
        return Component.extend({
            defaults: {
                template: 'Cecabank_TPV/payment/cecabank'
            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    $.when(
                        setPaymentInformationAction(
                            this.messageContainer,
                            {
                                method: this.getCode()
                            }
                        )
                    )
                    .then(this.afterPlaceOrder.bind(this))
                    .fail(
                        function () {
                            self.isPlaceOrderActionAllowed(true)
                        }
                    )
                    return true;
                }
                return false;
            },

            selectPaymentMethod: function() {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },
            
            afterPlaceOrder: function () {
                window.location.replace(url.build('cecabank/checkout/redirect/'));
            },

            getDescription: function () {
                return cecabankConfigProvider.description;
            },

            getImage: function () {
                return cecabankConfigProvider.image;
            }


        });
    }
);