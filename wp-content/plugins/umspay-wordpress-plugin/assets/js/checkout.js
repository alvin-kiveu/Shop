/**
 * UmsPay Checkout JavaScript
 */

(function($) {
    'use strict';

    var UmsPayCheckout = {
        
        init: function() {
            this.bindEvents();
            this.setupPhoneValidation();
        },

        bindEvents: function() {
            $(document).on('click', '.umspay-pay-button', this.handlePaymentSubmit);
            $(document).on('input', '#umspay_phone', this.validatePhoneInput);
            $(document).on('submit', '.umspay-payment-form', this.handleFormSubmit);
        },

        setupPhoneValidation: function() {
            var $phoneInput = $('#umspay_phone');
            if ($phoneInput.length) {
                // Auto-format phone number as user types
                $phoneInput.on('input', function() {
                    var value = $(this).val().replace(/\D/g, '');
                    
                    // Auto-add 254 prefix if not present
                    if (value.length > 0 && !value.startsWith('254')) {
                        if (value.startsWith('0')) {
                            value = '254' + value.substring(1);
                        } else if (value.startsWith('7') || value.startsWith('1')) {
                            value = '254' + value;
                        }
                    }
                    
                    // Limit to 12 digits (254XXXXXXXXX)
                    if (value.length > 12) {
                        value = value.substring(0, 12);
                    }
                    
                    $(this).val(value);
                    UmsPayCheckout.validatePhoneInput.call(this);
                });
            }
        },

        validatePhoneInput: function() {
            var $input = $(this);
            var value = $input.val();
            var isValid = /^254[0-9]{9}$/.test(value);
            
            $input.toggleClass('valid', isValid && value.length === 12);
            $input.toggleClass('invalid', !isValid && value.length > 0);
            
            // Update submit button state
            var $button = $('.umspay-pay-button');
            $button.prop('disabled', !isValid);
            
            return isValid;
        },

        handleFormSubmit: function(e) {
            var $form = $(this);
            var $button = $form.find('.umspay-pay-button');
            var $phoneInput = $form.find('#umspay_phone');
            
            // Validate phone number
            if (!UmsPayCheckout.validatePhoneInput.call($phoneInput[0])) {
                e.preventDefault();
                UmsPayCheckout.showMessage('error', 'Please enter a valid M-Pesa phone number (254XXXXXXXXX)');
                return false;
            }
            
            // Show loading state
            $button.addClass('loading').prop('disabled', true);
            $button.text('Processing...');
            
            return true;
        },

        handlePaymentSubmit: function(e) {
            var $button = $(this);
            var $form = $button.closest('form');
            
            if ($button.hasClass('loading') || $button.prop('disabled')) {
                e.preventDefault();
                return false;
            }
            
            // Final validation before submit
            var $phoneInput = $form.find('#umspay_phone');
            if (!UmsPayCheckout.validatePhoneInput.call($phoneInput[0])) {
                e.preventDefault();
                UmsPayCheckout.showMessage('error', 'Please enter a valid M-Pesa phone number');
                return false;
            }
        },

        showMessage: function(type, message) {
            var $container = $('.umspay-payment-container');
            var $existingMessage = $container.find('.umspay-message');
            
            // Remove existing message
            $existingMessage.remove();
            
            // Create new message
            var $message = $('<div class="umspay-message umspay-' + type + '">')
                .html('<p>' + message + '</p>')
                .hide()
                .prependTo($container)
                .slideDown();
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $message.slideUp(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },

        checkPaymentStatus: function(orderId) {
            var data = {
                action: 'umspay_check_status',
                order_id: orderId,
                nonce: umspay_params.nonce
            };
            
            $.post(umspay_params.ajax_url, data, function(response) {
                if (response.success) {
                    if (response.data.status === 'completed') {
                        UmsPayCheckout.showMessage('success', 'Payment completed successfully!');
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 2000);
                    } else if (response.data.status === 'failed') {
                        UmsPayCheckout.showMessage('error', 'Payment failed. Please try again.');
                        $('.umspay-pay-button').removeClass('loading').prop('disabled', false).text('Pay Now');
                    } else {
                        // Still processing, check again
                        setTimeout(function() {
                            UmsPayCheckout.checkPaymentStatus(orderId);
                        }, 3000);
                    }
                }
            }).fail(function() {
                UmsPayCheckout.showMessage('error', 'Unable to check payment status. Please refresh the page.');
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        UmsPayCheckout.init();
    });

    // Expose to global scope if needed
    window.UmsPayCheckout = UmsPayCheckout;

})(jQuery);

// CSS for dynamic messages
var dynamicCSS = `
.umspay-message {
    margin-bottom: 15px;
    padding: 12px;
    border-radius: 4px;
    border-left: 4px solid;
}

.umspay-message.umspay-success {
    background-color: #d4edda;
    color: #155724;
    border-left-color: #28a745;
}

.umspay-message.umspay-error {
    background-color: #f8d7da;
    color: #721c24;
    border-left-color: #dc3545;
}

.umspay-message.umspay-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-left-color: #17a2b8;
}

.umspay-message p {
    margin: 0;
}

#umspay_phone.valid {
    border-color: #28a745;
    box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.25);
}

#umspay_phone.invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25);
}
`;

// Inject dynamic CSS
if (document.head) {
    var style = document.createElement('style');
    style.textContent = dynamicCSS;
    document.head.appendChild(style);
}
