# TinyPesa for WooCommerce

A WordPress plugin that integrates TinyPesa payment gateway with WooCommerce, allowing customers to pay using M-Pesa STK Push.

## Features

- **STK Push Integration**: Seamless M-Pesa STK Push payments
- **Real-time Webhooks**: Automatic payment confirmation via webhooks
- **Phone Number Validation**: Automatic formatting and validation of Kenyan phone numbers
- **WooCommerce Integration**: Full integration with WooCommerce checkout process
- **Admin Dashboard**: Easy configuration through WooCommerce settings
- **Order Management**: Automatic order status updates based on payment status

## Installation

1. Download the plugin files
2. Upload to your WordPress plugins directory (`/wp-content/plugins/tinypesa-wordpress-plugin/`)
3. Activate the plugin through the WordPress admin
4. Configure the plugin settings in WooCommerce > Settings > Payments > TinyPesa

## Configuration

### Required Settings

1. **API Key**: Your TinyPesa API key
2. **Username**: Your TinyPesa username
3. **Base URL**: Your TinyPesa base URL (e.g., `https://your-tinypesa-url.com`)

### Webhook Setup

1. Copy the webhook URL from the plugin settings
2. Add this URL to your TinyPesa webhook configuration
3. Ensure your server can receive POST requests on this endpoint

## API Integration

The plugin integrates with the TinyPesa STK Push API:

### Endpoint
```
POST {base_url}/express/initialize/?username={username}
```

### Headers
```
Accept: application/json
Apikey: {your_api_key}
Content-Type: application/json
```

### Request Body
```json
{
    "amount": "100",
    "msisdn": "254712345678",
    "account_no": "ORDER-123"
}
```

### Webhook Response
The plugin handles webhook responses with the following fields:
- `TinyPesaID`: Request ID for reference
- `ExternalReference`: Order reference (ORDER-{order_id})
- `Amount`: Transaction amount
- `Msisdn`: Customer phone number
- `TransactionCode`: M-Pesa transaction code
- `Status`: Payment status (Success/Failed)

## Usage

1. Customer selects TinyPesa as payment method at checkout
2. Enters their M-Pesa registered phone number
3. Clicks "Place Order"
4. Receives STK Push prompt on their phone
5. Enters M-Pesa PIN to complete payment
6. Order status automatically updates upon payment confirmation

## Phone Number Formats

The plugin accepts and automatically formats these phone number formats:
- `0712345678` (Local format)
- `254712345678` (International format)
- `712345678` (Without country code)

## Order Statuses

- **Pending**: Order created, awaiting payment
- **Processing**: Payment received and confirmed
- **Failed**: Payment failed or cancelled

## Troubleshooting

### Common Issues

1. **Phone number validation errors**
   - Ensure the phone number is a valid Kenyan mobile number
   - Supported networks: Safaricom, Airtel

2. **Payment not processing**
   - Check API credentials in settings
   - Verify webhook URL is accessible
   - Check server logs for API errors

3. **Webhook not working**
   - Ensure webhook URL is publicly accessible
   - Check for SSL certificate issues
   - Verify TinyPesa webhook configuration

### Debug Mode

Enable WordPress debug mode to see detailed error logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs in `/wp-content/debug.log` for TinyPesa-related errors.

## Requirements

- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.4+
- SSL certificate (recommended for webhooks)

## Support

For support and bug reports, please contact the plugin developer or create an issue in the project repository.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- STK Push integration
- Webhook handling
- Phone number validation
- WooCommerce integration
