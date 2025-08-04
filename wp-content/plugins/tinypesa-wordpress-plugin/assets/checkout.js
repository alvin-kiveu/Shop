jQuery(document).ready(function($) {
    'use strict';

    // TinyPesa checkout functionality
    var tinypesa_checkout = {
        init: function() {
            this.bindEvents();
            this.validatePhoneField();
        },

        bindEvents: function() {
            // Update checkout when payment method changes
            $('body').on('change', 'input[name="payment_method"]', function() {
                if ($(this).val() === 'tinypesa') {
                    tinypesa_checkout.showTinyPesaFields();
                } else {
                    tinypesa_checkout.hideTinyPesaFields();
                }
            });

            // Phone number validation
            $('body').on('input', '#tinypesa_phone', function() {
                tinypesa_checkout.validatePhoneNumber($(this));
            });

            // Handle form submission
            $('form.checkout').on('checkout_place_order_tinypesa', function() {
                return tinypesa_checkout.validateForm();
            });
        },

        showTinyPesaFields: function() {
            $('.tinypesa-payment-fields').slideDown(300);
        },

        hideTinyPesaFields: function() {
            $('.tinypesa-payment-fields').slideUp(300);
        },

        validatePhoneField: function() {
            // Auto-format phone number
            $('body').on('input', '#tinypesa_phone', function() {
                var phone = $(this).val().replace(/\D/g, '');
                
                // Format phone number as user types
                if (phone.length > 0) {
                    if (phone.charAt(0) === '0') {
                        phone = phone.substring(1);
                    }
                    if (phone.charAt(0) === '2' && phone.charAt(1) === '5' && phone.charAt(2) === '4') {
                        phone = phone.substring(3);
                    }
                    
                    // Add formatting
                    if (phone.length >= 3) {
                        phone = phone.substring(0, 3) + ' ' + phone.substring(3);
                    }
                    if (phone.length >= 7) {
                        phone = phone.substring(0, 7) + ' ' + phone.substring(7);
                    }
                    
                    phone = '0' + phone;
                    $(this).val(phone);
                }
            });
        },

        validatePhoneNumber: function($field) {
            var phone = $field.val().replace(/\D/g, '');
            var isValid = false;

            // Remove leading zeros and country code
            if (phone.charAt(0) === '0') {
                phone = phone.substring(1);
            }
            if (phone.length === 12 && phone.substring(0, 3) === '254') {
                phone = phone.substring(3);
            }

            // Validate Kenyan mobile numbers
            if (phone.length === 9) {
                var validPrefixes = ['1', '7']; // Safaricom and Airtel
                var firstDigit = phone.charAt(0);
                
                if (validPrefixes.includes(firstDigit)) {
                    isValid = true;
                }
            }

            // Update field styling
            if (isValid) {
                $field.removeClass('error').addClass('valid');
                $field.next('.error-message').remove();
            } else if ($field.val().length > 0) {
                $field.removeClass('valid').addClass('error');
                if (!$field.next('.error-message').length) {
                    $field.after('<span class="error-message" style="color: red; font-size: 12px;">Please enter a valid Kenyan phone number</span>');
                }
            }

            return isValid;
        },

        validateForm: function() {
            var isValid = true;
            var $phoneField = $('#tinypesa_phone');
            
            if ($phoneField.length && $phoneField.val().trim() === '') {
                $phoneField.focus();
                tinypesa_checkout.showError('Please enter your M-Pesa phone number');
                isValid = false;
            } else if ($phoneField.length && !tinypesa_checkout.validatePhoneNumber($phoneField)) {
                $phoneField.focus();
                tinypesa_checkout.showError('Please enter a valid M-Pesa phone number');
                isValid = false;
            }

            if (isValid) {
                // Show processing message
                tinypesa_checkout.showProcessing();
            }

            return isValid;
        },

        showError: function(message) {
            $('.woocommerce-notices-wrapper').html(
                '<div class="woocommerce-error" role="alert">' + message + '</div>'
            );
            $('html, body').animate({
                scrollTop: $('.woocommerce-notices-wrapper').offset().top - 100
            }, 500);
        },

        showProcessing: function() {
            var $form = $('form.checkout');
            var $submitButton = $form.find('#place_order');
            
            $submitButton.prop('disabled', true);
            $submitButton.text('Processing Payment...');
            
            // Show processing overlay
            if (!$('.tinypesa-processing-overlay').length) {
                $('body').append(
                    '<div class="tinypesa-processing-overlay" style="' +
                    'position: fixed; top: 0; left: 0; width: 100%; height: 100%; ' +
                    'background: rgba(0,0,0,0.7); z-index: 9999; display: flex; ' +
                    'align-items: center; justify-content: center; color: white; font-size: 18px;">' +
                    '<div style="text-align: center;">' +
                    '<div style="margin-bottom: 20px;">🔄</div>' +
                    '<div>Processing M-Pesa Payment...</div>' +
                    '<div style="font-size: 14px; margin-top: 10px;">Please check your phone for the payment prompt</div>' +
                    '</div></div>'
                );
            }
        },

        hideProcessing: function() {
            $('.tinypesa-processing-overlay').remove();
            var $submitButton = $('form.checkout').find('#place_order');
            $submitButton.prop('disabled', false);
            $submitButton.text('Place Order');
        }
    };

    // Initialize TinyPesa checkout
    tinypesa_checkout.init();

    // Handle payment method selection on page load
    if ($('input[name="payment_method"]:checked').val() === 'tinypesa') {
        tinypesa_checkout.showTinyPesaFields();
    }

    // Add custom styles
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .tinypesa-payment-fields {
                padding: 15px;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                margin-top: 10px;
            }
            
            .tinypesa-payment-fields label {
                font-weight: 600;
                margin-bottom: 5px;
                display: block;
            }
            
            .tinypesa-payment-fields input[type="tel"] {
                width: 100%;
                padding: 10px;
                border: 1px solid #ccc;
                border-radius: 4px;
                font-size: 16px;
            }
            
            .tinypesa-payment-fields input[type="tel"].valid {
                border-color: #28a745;
                background-color: #f8fff8;
            }
            
            .tinypesa-payment-fields input[type="tel"].error {
                border-color: #dc3545;
                background-color: #fff8f8;
            }
            
            .tinypesa-payment-fields small {
                color: #6c757d;
                font-size: 12px;
                margin-top: 5px;
                display: block;
            }
            
            .error-message {
                display: block;
                margin-top: 5px;
            }
        `)
        .appendTo('head');
});
