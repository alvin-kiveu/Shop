const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement, useState } = window.wp.element;
const { __ } = window.wp.i18n;
const { decodeEntities } = window.wp.htmlEntities;

const settings = window.wc.wcSettings.getSetting('mpesa_data', {});

/**
 * M-Pesa payment method content component
 */
const MpesaContent = () => {
    const [phoneNumber, setPhoneNumber] = useState('');
    const [phoneError, setPhoneError] = useState('');

    /**
     * Format phone number to 254xxxxxxxxx format
     */
    const formatPhoneNumber = (value) => {
        // Remove all non-digits
        let cleaned = value.replace(/\D/g, '');
        
        // Auto-format to 254 prefix
        if (cleaned.length > 0) {
            if (cleaned.startsWith('0')) {
                // Convert 07xxxxxxxx to 254xxxxxxxx
                cleaned = '254' + cleaned.substring(1);
            } else if (cleaned.startsWith('7') || cleaned.startsWith('1')) {
                // Convert 7xxxxxxxx to 2547xxxxxxxx
                cleaned = '254' + cleaned;
            } else if (!cleaned.startsWith('254')) {
                // If doesn't start with 254, add it
                cleaned = '254' + cleaned;
            }
        }
        
        // Limit to 12 digits (254 + 9 digits)
        if (cleaned.length > 12) {
            cleaned = cleaned.substring(0, 12);
        }
        
        return cleaned;
    };

    /**
     * Validate phone number
     */
    const validatePhone = (phone) => {
        const phoneRegex = /^254[0-9]{9}$/;
        return phoneRegex.test(phone);
    };

    /**
     * Handle phone number input change
     */
    const handlePhoneChange = (event) => {
        const formattedPhone = formatPhoneNumber(event.target.value);
        setPhoneNumber(formattedPhone);
        
        if (formattedPhone && !validatePhone(formattedPhone)) {
            setPhoneError(__('Please enter a valid M-Pesa phone number (254xxxxxxxxx)', 'mpesa-woocommerce'));
        } else {
            setPhoneError('');
        }
        
        // Update the checkout data
        wp.hooks.doAction('wc-blocks-checkout-set-payment-data', {
            'mpesa_phone': formattedPhone
        });
    };

    return createElement('div', {
        className: 'wc-block-mpesa-fields'
    }, [
        // Description
        settings.description && createElement('p', {
            key: 'description',
            dangerouslySetInnerHTML: {
                __html: decodeEntities(settings.description)
            }
        }),
        
        // Phone number field
        createElement('div', {
            key: 'phone-field',
            className: 'wc-block-mpesa-phone-field'
        }, [
            createElement('label', {
                key: 'phone-label',
                htmlFor: 'mpesa-phone',
                className: 'wc-block-mpesa-phone-label'
            }, [
                __('M-Pesa Phone Number:', 'mpesa-woocommerce'),
                createElement('span', {
                    key: 'required',
                    className: 'required'
                }, ' *')
            ]),
            createElement('input', {
                key: 'phone-input',
                type: 'tel',
                id: 'mpesa-phone',
                className: 'wc-block-mpesa-phone-input',
                placeholder: '254700000000',
                value: phoneNumber,
                onChange: handlePhoneChange,
                required: true
            }),
            createElement('small', {
                key: 'phone-help',
                className: 'wc-block-mpesa-phone-help'
            }, __('Enter your M-Pesa registered phone number (format: 254700000000)', 'mpesa-woocommerce')),
            phoneError && createElement('div', {
                key: 'phone-error',
                className: 'wc-block-mpesa-phone-error'
            }, phoneError)
        ]),
        
        // Payment instructions
        createElement('div', {
            key: 'instructions',
            className: 'wc-block-mpesa-instructions'
        }, [
            createElement('h4', {
                key: 'instructions-title'
            }, __('How to pay with M-Pesa:', 'mpesa-woocommerce')),
            createElement('ol', {
                key: 'instructions-list'
            }, [
                createElement('li', {
                    key: 'step1'
                }, __('Enter your M-Pesa phone number above', 'mpesa-woocommerce')),
                createElement('li', {
                    key: 'step2'
                }, __('Click "Place Order" button', 'mpesa-woocommerce')),
                createElement('li', {
                    key: 'step3'
                }, __('Check your phone for an M-Pesa STK Push prompt', 'mpesa-woocommerce')),
                createElement('li', {
                    key: 'step4'
                }, __('Enter your M-Pesa PIN to complete payment', 'mpesa-woocommerce'))
            ])
        ])
    ]);
};

/**
 * M-Pesa payment method label component
 */
const MpesaLabel = (props) => {
    const { PaymentMethodLabel } = props.components;
    return createElement(PaymentMethodLabel, {
        text: settings.title
    });
};

/**
 * M-Pesa payment method object
 */
const MpesaPaymentMethod = {
    name: 'mpesa',
    label: createElement(MpesaLabel),
    content: createElement(MpesaContent),
    edit: createElement(MpesaContent),
    canMakePayment: () => true,
    ariaLabel: settings.title,
    supports: {
        features: settings.supports,
    },
};

// Register the payment method
registerPaymentMethod(MpesaPaymentMethod);

// Add custom styles
const style = document.createElement('style');
style.textContent = `
.wc-block-mpesa-fields {
    margin: 20px 0;
}

.wc-block-mpesa-phone-field {
    margin-bottom: 20px;
}

.wc-block-mpesa-phone-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.wc-block-mpesa-phone-label .required {
    color: #d63638;
}

.wc-block-mpesa-phone-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.wc-block-mpesa-phone-input:focus {
    outline: none;
    border-color: #00a32a;
    box-shadow: 0 0 0 1px #00a32a;
}

.wc-block-mpesa-phone-help {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 14px;
    font-style: italic;
}

.wc-block-mpesa-phone-error {
    margin-top: 5px;
    padding: 8px 12px;
    background-color: #fcf2f2;
    border: 1px solid #d63638;
    border-radius: 4px;
    color: #d63638;
    font-size: 14px;
}

.wc-block-mpesa-instructions {
    margin-top: 20px;
    padding: 16px;
    background-color: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #00a32a;
}

.wc-block-mpesa-instructions h4 {
    margin: 0 0 12px 0;
    color: #00a32a;
    font-size: 16px;
}

.wc-block-mpesa-instructions ol {
    margin: 0;
    padding-left: 20px;
}

.wc-block-mpesa-instructions li {
    margin-bottom: 8px;
    color: #555;
    line-height: 1.5;
}

.wc-block-mpesa-instructions li:last-child {
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .wc-block-mpesa-phone-input {
        font-size: 16px; /* Prevent zoom on iOS */
    }
}
`;
document.head.appendChild(style);
