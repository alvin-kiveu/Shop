# Changelog

All notable changes to the TinyPesa for WooCommerce plugin will be documented in this file.

## [1.0.0] - 2025-08-04

### Added
- Initial release of TinyPesa for WooCommerce plugin
- STK Push payment integration using TinyPesa API
- Real-time webhook payment confirmation
- Automatic phone number formatting and validation
- WooCommerce checkout integration
- Admin settings panel in WooCommerce
- Order status management based on payment status
- Support for Kenyan mobile numbers (Safaricom, Airtel)
- Error handling and logging
- Customer payment fields validation
- Responsive checkout interface
- Security features and data validation

### Features
- **Payment Processing**: Seamless M-Pesa STK Push payments
- **Webhook Integration**: Automatic payment status updates
- **Phone Validation**: Smart formatting for Kenyan phone numbers
- **Order Management**: Automatic order status transitions
- **Admin Interface**: Easy configuration through WooCommerce settings
- **Error Handling**: Comprehensive error logging and user feedback
- **Security**: Input validation and secure API communication

### API Integration
- POST endpoint: `{base_url}/express/initialize/`
- Required headers: Accept, Apikey
- Request parameters: amount, msisdn, account_no
- Webhook handling for payment confirmation
- Support for TinyPesa API specifications

### Supported Features
- Multiple phone number formats (local and international)
- Real-time payment status updates
- Order metadata storage for transaction details
- Customer notification system
- Admin order notes for payment tracking
- Debug logging for troubleshooting

### Requirements
- WordPress 5.0 or higher
- WooCommerce 4.0 or higher  
- PHP 7.4 or higher
- Valid TinyPesa API credentials
- SSL certificate (recommended)

### Known Issues
- None reported in initial release

### Future Enhancements
- Multi-currency support
- Payment retry functionality
- Enhanced reporting dashboard
- SMS notifications
- Refund processing
- Bulk payment processing
