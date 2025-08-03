# UmsPay for WooCommerce

<p align="center">
  <img src="umspay.png" alt="UmsPay Logo" width="200"/>
</p>

A comprehensive WordPress plugin that integrates UmsPay payment gateway with WooCommerce, enabling M-Pesa STK Push payments with enhanced security and user experience.

## ✨ Features

- **🚀 M-Pesa STK Push Integration**: Seamless mobile payment experience
- **🔧 Test & Live Modes**: Switch between test and production environments
- **🔒 Enhanced Security**: Input validation, nonce verification, and secure API communication
- **📱 Responsive Design**: Mobile-optimized payment forms with real-time validation
- **📊 Detailed Logging**: Track payment transactions and debug issues
- **🔄 Webhook Support**: Automatic payment status updates
- **💰 Refund Framework**: Foundation for handling refunds (API dependent)
- **🌍 Multi-language Ready**: Translation-ready with text domain
- **⚡ Performance Optimized**: Efficient code with minimal resource usage

## 📋 Requirements

- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.4+
- SSL certificate (required for production)
- Active UmsPay account

## 🚀 Installation

### Method 1: WordPress Admin Dashboard

1. Download the plugin zip file from the [releases page](https://github.com/UMESKIA-SOFTWARES/UmsPay-WooCommwece-Plugin/releases)
2. In your WordPress admin dashboard, navigate to **Plugins → Add New**
3. Click **Upload Plugin**, select the downloaded zip file, and click **Install Now**
4. After installation, click **Activate**
5. Navigate to **WooCommerce → Settings → Payments → UmsPay Gateway**
6. Configure your UmsPay API credentials

### Method 2: Manual Installation

1. Upload the plugin files to `/wp-content/plugins/umspay-wordpress-plugin/`
2. Activate the plugin through the **Plugins** screen in WordPress
3. Configure the settings as described above

## ⚙️ Configuration

### Required Settings

| Setting | Description |
|---------|-------------|
| **Owner Email** | Your UmsPay account email address |
| **Live API Key** | Production API key from UmsPay dashboard |
| **Test API Key** | Development API key for testing |
| **Account ID** | Your unique UmsPay account identifier |

### Optional Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **Test Mode** | Enable for development/testing | Enabled |
| **Logging** | Enable detailed transaction logging | Disabled |
| **Title** | Payment method title shown to customers | "M-Pesa via UmsPay" |
| **Description** | Payment method description | "Pay securely using M-Pesa..." |

### Webhook Configuration

1. Copy the webhook URL from plugin settings:
   ```
   https://yoursite.com/wc-api/wc_umspay_gateway/
   ```
2. Add this URL to your UmsPay account webhook settings
3. Ensure your server can receive POST requests on this endpoint

## 📱 Phone Number Formats

The plugin accepts and automatically converts various phone number formats:

| Input Format | Converted To | Status |
|--------------|--------------|--------|
| `07XXXXXXXX` | `254XXXXXXX` | ✅ Valid |
| `7XXXXXXXX` | `254XXXXXXX` | ✅ Valid |
| `254XXXXXXX` | `254XXXXXXX` | ✅ Preferred |
| `+254XXXXXXX` | `254XXXXXXX` | ✅ Valid |

## 🔒 Security Features

- **Input Sanitization**: All user inputs are properly sanitized
- **Nonce Verification**: WordPress security tokens for form submissions
- **SSL Verification**: Secure API communication with certificate validation
- **Phone Validation**: Format validation before processing
- **Amount Verification**: Order total verification against payment amount
- **Rate Limiting**: Built-in protection against excessive requests

## 📊 Logging & Debugging

### Enable Logging

1. Go to **WooCommerce → Settings → Payments → UmsPay Gateway**
2. Check **Enable Logging**
3. Save settings

### View Logs

1. Navigate to **WooCommerce → Status → Logs**
2. Select **umspay** from the log source dropdown
3. Review detailed transaction logs

### Log Contents

- Payment initiation requests
- API responses and errors
- Webhook notifications
- Security validation results
- Performance metrics

## 🔄 Webhook Integration

### Expected Webhook Payload

```json
{
  "TransactionReference": "order_id",
  "ResponseCode": 0,
  "TransactionReceipt": "receipt_id",
  "Amount": 100.00,
  "ResultDesc": "Success message"
}
```

### Response Codes

| Code | Status | Action |
|------|--------|--------|
| `0` | Success | Payment completed |
| `1` | Failed | Payment failed |
| `2` | Pending | Payment processing |

## 🛠️ Developer Features

### Hooks and Filters

```php
// Customize gateway icon
add_filter('woocommerce_umspay_icon', 'custom_umspay_icon');

// Modify API request arguments
add_filter('umspay_api_request_args', 'custom_api_args');

// Custom payment form fields
add_action('umspay_payment_form_fields', 'custom_form_fields');

// Payment status updates
add_action('umspay_payment_complete', 'handle_payment_complete', 10, 2);
```

### Constants

```php
UMSPAY_WC_VERSION          // Plugin version
UMSPAY_WC_PLUGIN_DIR       // Plugin directory path
UMSPAY_WC_PLUGIN_URL       // Plugin URL
UMSPAY_WC_API_BASE_URL     // UmsPay API base URL
```

## 🐛 Troubleshooting

### Common Issues

#### Payment Not Processing

1. **Check API Credentials**
   - Verify API key and account ID are correct
   - Ensure you're using the right credentials for test/live mode

2. **Webhook Configuration**
   - Confirm webhook URL is configured in UmsPay dashboard
   - Test webhook endpoint accessibility

3. **Server Requirements**
   - Ensure SSL certificate is valid
   - Check PHP version compatibility

#### Invalid Phone Number Errors

1. **Format Validation**
   - Ensure phone number starts with 254
   - Remove any special characters or spaces
   - Use format: 254XXXXXXXXX

2. **Input Field Issues**
   - Clear browser cache and cookies
   - Try different phone number format
   - Check for JavaScript errors in browser console

#### Webhook Not Receiving Notifications

1. **Server Configuration**
   - Check firewall settings allow incoming requests
   - Verify server can handle POST requests
   - Ensure no security plugins are blocking webhooks

2. **UmsPay Dashboard**
   - Confirm webhook URL is correctly configured
   - Check webhook delivery logs in UmsPay dashboard
   - Verify account is active and in good standing

### Debug Mode

Enable comprehensive debugging:

1. **WordPress Debug Mode**
   ```php
   // Add to wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. **Plugin Logging**
   - Enable logging in plugin settings
   - Monitor logs for detailed error information

3. **Network Debugging**
   - Use browser developer tools to inspect network requests
   - Check for any blocked resources or CORS issues

## 📝 Changelog

### Version 2.2.0 (Current)
- 🔄 Complete code refactoring and modernization
- 🔒 Enhanced security with comprehensive input validation
- 📱 Improved responsive UI with real-time form validation
- 🛡️ Comprehensive error handling and logging system
- 🔄 Proper webhook handling with retry mechanism
- 📞 Phone number auto-formatting and validation
- ⚙️ Enhanced admin configuration interface
- 🏪 Added support for WooCommerce HPOS
- 📚 Improved code documentation and comments
- ⚡ Performance optimizations

### Version 2.1.0
- 🚀 Initial release with basic M-Pesa integration
- 💳 STK Push payment support
- 🔧 Basic admin configuration

## Usage

Once the UmsPay WooCommerce Plugin is installed and configured, it will appear as a payment option during the checkout process on your WooCommerce store. Customers can select the UmsPay payment method and complete their transactions securely.

## Contributing

Contributions to the UmsPay WooCommerce Plugin are welcome! Here's how you can contribute:

- Fork the repository.
- Create a new branch (`git checkout -b feature-branch`).
- Make your changes and commit them (`git commit -am 'Add new feature'`).
- Push to the branch (`git push origin feature-branch`).
- Create a new Pull Request.

Please ensure that your code follows the existing coding style and includes relevant tests.

## License

This project is licensed under the GNU General Public License v2.0 or later. See the [LICENSE](LICENSE) file for details.

## Support

For support, bug reports, and feature requests, please [open an issue](https://github.com/UMESKIA-SOFTWARES/UmsPay-WooCommwece-Plugin.git/issues) on GitHub.

## Acknowledgements

Special thanks to [Alvin Kiveu](https://github.com/alvin-kiveu/) and UMESKIA SOFTWARES for developing the UmsPay WooCommerce Plugin.

## About

This plugin is maintained by [UMESKIA SOFTWARES](https://github.com/UMESKIA-SOFTWARES/).
