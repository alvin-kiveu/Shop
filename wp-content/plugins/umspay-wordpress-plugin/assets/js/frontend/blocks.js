const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement, useState, useEffect } = window.wp.element;
const { __ } = window.wp.i18n;
const { decodeEntities } = window.wp.htmlEntities;
const { getSetting } = window.wc.wcSettings;

const settings = getSetting('umspay_data', {});

/**
 * UmsPay payment method label component
 */
const UmsPayLabel = (props) => {
    return createElement('span', {
        style: {
            display: 'flex',
            alignItems: 'center',
            gap: '8px'
        }
    }, [
        settings.logo_url && createElement('img', {
            key: 'logo',
            src: settings.logo_url,
            alt: decodeEntities(settings.title || __('UmsPay', 'umspay-woocommerce')),
            style: {
                maxHeight: '24px',
                width: 'auto'
            }
        }),
        createElement('span', {
            key: 'title'
        }, decodeEntities(settings.title || __('UmsPay', 'umspay-woocommerce')))
    ]);
};

/**
 * UmsPay payment method content component
 */
const UmsPayContent = (props) => {
    const [phoneNumber, setPhoneNumber] = useState('');
    const [isValid, setIsValid] = useState(false);
    
    // Validate phone number
    const validatePhone = (phone) => {
        const cleaned = phone.replace(/[^0-9]/g, '');
        return /^254[0-9]{9}$/.test(cleaned);
    };
    
    // Handle phone number change
    const handlePhoneChange = (event) => {
        let value = event.target.value.replace(/[^0-9]/g, '');
        
        // Auto-format to 254 prefix
        if (value.length > 0 && !value.startsWith('254')) {
            if (value.startsWith('0')) {
                value = '254' + value.substring(1);
            } else if (value.startsWith('7') || value.startsWith('1')) {
                value = '254' + value;
            }
        }
        
        // Limit to 12 digits
        if (value.length > 12) {
            value = value.substring(0, 12);
        }
        
        setPhoneNumber(value);
        const valid = validatePhone(value);
        setIsValid(valid);
        
        // Update checkout data
        if (props.eventRegistration && props.eventRegistration.onPaymentSetup) {
            props.eventRegistration.onPaymentSetup(() => {
                return {
                    type: valid ? 'success' : 'error',
                    meta: {
                        paymentMethodData: {
                            umspay_phone: value
                        }
                    }
                };
            });
        }
    };
    
    return createElement('div', {
        className: 'umspay-payment-method-content'
    }, [
        createElement('p', {
            key: 'description',
            style: {
                margin: '0 0 16px 0',
                color: '#666',
                fontSize: '14px'
            }
        }, decodeEntities(settings.description || __('Pay securely using M-Pesa via UmsPay gateway.', 'umspay-woocommerce'))),
        
        // Phone number input field
        createElement('div', {
            key: 'phone-field',
            style: {
                marginBottom: '16px'
            }
        }, [
            createElement('label', {
                key: 'phone-label',
                htmlFor: 'umspay-phone-block',
                style: {
                    display: 'block',
                    marginBottom: '4px',
                    fontWeight: '600',
                    fontSize: '14px'
                }
            }, __('M-Pesa Phone Number', 'umspay-woocommerce') + ' *'),
            
            createElement('input', {
                key: 'phone-input',
                type: 'tel',
                id: 'umspay-phone-block',
                name: 'umspay_phone',
                value: phoneNumber,
                onChange: handlePhoneChange,
                placeholder: '254XXXXXXXXX',
                pattern: '254[0-9]{9}',
                required: true,
                style: {
                    width: '100%',
                    padding: '8px 12px',
                    border: `1px solid ${isValid && phoneNumber ? '#28a745' : (!isValid && phoneNumber ? '#dc3545' : '#ddd')}`,
                    borderRadius: '4px',
                    fontSize: '14px',
                    boxSizing: 'border-box'
                }
            }),
            
            createElement('small', {
                key: 'phone-help',
                style: {
                    display: 'block',
                    marginTop: '4px',
                    color: '#666',
                    fontSize: '12px'
                }
            }, __('Format: 254XXXXXXXXX', 'umspay-woocommerce'))
        ]),
        
        createElement('div', {
            key: 'payment-info',
            className: 'umspay-payment-info',
            style: {
                padding: '12px',
                backgroundColor: '#f8f9fa',
                border: '1px solid #e9ecef',
                borderRadius: '4px',
                fontSize: '13px'
            }
        }, [
            createElement('h4', {
                key: 'title',
                style: {
                    margin: '0 0 8px 0',
                    fontSize: '14px',
                    fontWeight: '600',
                    color: '#333'
                }
            }, __('M-Pesa Payment Instructions:', 'umspay-woocommerce')),
            
            createElement('ol', {
                key: 'instructions',
                style: {
                    margin: '0',
                    paddingLeft: '16px',
                    color: '#555'
                }
            }, [
                createElement('li', {
                    key: 'step1'
                }, __('Enter your M-Pesa phone number above', 'umspay-woocommerce')),
                createElement('li', {
                    key: 'step2'
                }, __('Complete your order by clicking "Place Order"', 'umspay-woocommerce')),
                createElement('li', {
                    key: 'step3'
                }, __('You will receive an M-Pesa STK Push on your phone', 'umspay-woocommerce')),
                createElement('li', {
                    key: 'step4'
                }, __('Enter your M-Pesa PIN to complete the payment', 'umspay-woocommerce'))
            ])
        ])
    ]);
};

/**
 * UmsPay payment method edit component (for editor)
 */
const UmsPayEdit = () => {
    return createElement('div', {
        style: {
            padding: '16px',
            border: '1px dashed #ccc',
            borderRadius: '4px',
            textAlign: 'center',
            color: '#666'
        }
    }, __('UmsPay payment method (customer will see payment instructions here)', 'umspay-woocommerce'));
};

/**
 * Register the UmsPay payment method
 */
const UmsPayPaymentMethod = {
    name: 'umspay',
    label: createElement(UmsPayLabel),
    content: createElement(UmsPayContent),
    edit: createElement(UmsPayEdit),
    canMakePayment: () => true,
    ariaLabel: decodeEntities(settings.title || __('UmsPay', 'umspay-woocommerce')),
    supports: {
        features: settings.supports || []
    }
};

registerPaymentMethod(UmsPayPaymentMethod);

/**
 * Add custom styles for the payment method
 */
const addUmsPayStyles = () => {
    const style = document.createElement('style');
    style.textContent = `
        .wc-block-components-payment-method-content .umspay-payment-method-content {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .wc-block-components-payment-method-content .umspay-payment-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-left: 4px solid #2196f3;
        }
        
        .wc-block-components-payment-method-content .umspay-payment-info h4 {
            color: #1976d2;
        }
        
        .wc-block-components-payment-method-content .umspay-payment-info ol li {
            margin-bottom: 4px;
            line-height: 1.4;
        }
        
        .wc-block-components-payment-method-content .umspay-payment-info ol li:last-child {
            margin-bottom: 0;
        }
        
        .wc-block-components-payment-method-content input[type="tel"] {
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .wc-block-components-payment-method-content input[type="tel"]:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.2);
        }
        
        @media (max-width: 768px) {
            .wc-block-components-payment-method-content .umspay-payment-info {
                font-size: 12px;
            }
        }
    `;
    document.head.appendChild(style);
};

// Add styles when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addUmsPayStyles);
} else {
    addUmsPayStyles();
}
