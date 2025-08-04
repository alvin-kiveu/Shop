# TinyPesa for WooCommerce - Installation Guide

## Quick Setup

### 1. Installation
1. Upload the plugin folder to `/wp-content/plugins/tinypesa-wordpress-plugin/`
2. Activate the plugin through WordPress admin
3. Navigate to WooCommerce > Settings > Payments
4. Find and click "Set up" next to "TinyPesa Gateway"

### 2. Configuration
Enter the following required information:

#### Required Fields:
- **API Key**: Your TinyPesa API key (e.g., `Dfw3sdfx234F`)
- **Username**: Your TinyPesa username (e.g., `100`)
- **Base URL**: Your TinyPesa base URL (e.g., `https://your-domain.com`)

#### Optional Fields:
- **Method Title**: Display name for customers (default: "M-Pesa Payment")
- **Method Description**: Description shown to customers

### 3. Webhook Configuration
1. Copy the webhook URL from the plugin settings
2. Add this URL to your TinyPesa dashboard webhook settings
3. Ensure your server can receive POST requests

### 4. Testing
1. Enable the payment method
2. Create a test order
3. Use a test phone number to verify the STK push works
4. Check that webhook updates the order status

## API Endpoint Details

### STK Push Request
```
POST {base_url}/express/initialize/?username={username}

Headers:
- Accept: application/json
- Apikey: {your_api_key}
- Content-Type: application/json

Body:
{
    "amount": "100",
    "msisdn": "254712345678",
    "account_no": "ORDER-123"
}
```

### Expected Response
```json
{
    "success": true,
    "request_id": "unique_request_id",
    "message": "STK push sent successfully"
}
```

### Webhook Payload
Your webhook will receive successful payments in this format:
```json
{
    "Body": {
        "stkCallback": {
            "MerchantRequestID": "26773-830618-1",
            "CheckoutRequestID": "ws_CO_21042021114416028704",
            "ResultCode": 0,
            "ResultDesc": "The service request is processed successfully.",
            "CallbackMetadata": {
                "Item": [
                    {
                        "Name": "Amount",
                        "Value": 1
                    },
                    {
                        "Name": "MpesaReceiptNumber",
                        "Value": "PDL72WRAVZ"
                    },
                    {
                        "Name": "TransactionDate",
                        "Value": 20210421114425
                    },
                    {
                        "Name": "PhoneNumber",
                        "Value": 254718942539
                    }
                ]
            },
            "TinyPesaID": "c002f860-a27d-11eb-a7f4-c141263d7c15",
            "ExternalReference": "ORDER-123",
            "Amount": 1,
            "Msisdn": "254718942539"
        }
    }
}
```

Failed/cancelled payments:
```json
{
    "Body": {
        "stkCallback": {
            "MerchantRequestID": "25395-1644131-1",
            "CheckoutRequestID": "ws_CO_26022021085632641774",
            "ResultCode": 1031,
            "ResultDesc": "Request cancelled by user",
            "TinyPesaID": "f8ac0d60-7a14-11eb-ba1d-e3a49273aa65",
            "ExternalReference": "ORDER-123",
            "Amount": 1,
            "Msisdn": "254718942539"
        }
    }
}
```

**Result Codes:**
- `0` = Success
- `1031` = Request cancelled by user
- Other codes = Various failure reasons

## Troubleshooting

### Common Issues

1. **Plugin not showing in payment methods**
   - Ensure WooCommerce is installed and active
   - Check if the plugin is activated
   - Verify PHP version (7.4+ required)

2. **STK push not working**
   - Verify API credentials are correct
   - Check if the base URL is accessible
   - Ensure phone number format is correct

3. **Webhook not updating orders**
   - Check webhook URL is publicly accessible
   - Verify SSL certificate is valid
   - Check server logs for incoming webhook requests

4. **Phone number validation errors**
   - Ensure number is a valid Kenyan mobile (starts with 07 or 01)
   - Check for typos in phone number entry

### Debug Mode
Enable WordPress debug logging:
```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for TinyPesa-related errors.

### Test Credentials
Use these for testing (if provided by TinyPesa):
- Test API Key: `test_api_key_here`
- Test Username: `test_username`
- Test Base URL: `https://test.tinypesa.com`

## Security Notes

1. **HTTPS**: Always use HTTPS for production
2. **API Keys**: Keep API keys secure and never expose in frontend code
3. **Webhooks**: Validate webhook authenticity
4. **Phone Numbers**: Always sanitize and validate user input

## Support

For technical support:
1. Check the troubleshooting section above
2. Review server error logs
3. Contact TinyPesa support for API-related issues
4. Contact plugin developer for WordPress-specific issues

## Required Permissions

The plugin needs these WordPress capabilities:
- Read/write to orders and order meta
- Handle payment processing
- Manage WooCommerce settings
- Receive webhook requests

## Performance Notes

- Webhook handling is optimized for quick responses
- Phone number validation runs client-side for better UX
- API requests include proper timeout handling
- Failed payments are logged for debugging
