# UmsPay WooCommerce Plugin - Installation Guide

## Quick Start

### Prerequisites
- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.4+
- SSL Certificate (for production)
- Active UmsPay Account

### Installation Steps

1. **Download & Upload**
   - Download the plugin zip file
   - Go to WordPress Admin → Plugins → Add New → Upload Plugin
   - Select the zip file and click "Install Now"
   - Click "Activate"

2. **Configure Settings**
   - Go to WooCommerce → Settings → Payments
   - Find "UmsPay Gateway" and click "Set up"
   - Fill in your UmsPay credentials:
     - Owner Email: Your UmsPay account email
     - API Key: From your UmsPay dashboard
     - Account ID: From your UmsPay dashboard

3. **Test Configuration**
   - Enable "Test Mode"
   - Use test credentials
   - Place a test order
   - Verify payment flow works

4. **Go Live**
   - Disable "Test Mode"
   - Enter live credentials
   - Configure webhook URL in UmsPay dashboard
   - Test with small amount

### Webhook Setup

1. Copy webhook URL from plugin settings:
   ```
   https://yoursite.com/wc-api/wc_umspay_gateway/
   ```

2. In your UmsPay dashboard:
   - Go to Webhook Settings
   - Add the copied URL
   - Save configuration

### Security Checklist

- ✅ SSL certificate installed
- ✅ WordPress and plugins updated
- ✅ Strong admin passwords
- ✅ Limited login attempts enabled
- ✅ Regular backups configured

### Troubleshooting

#### Common Issues

**Payment not processing**
- Check API credentials
- Verify webhook URL
- Enable logging for details

**Phone number errors**
- Use format: 254XXXXXXXXX
- Remove special characters
- Try different number

**SSL/Security errors**
- Ensure SSL certificate is valid
- Check server configuration
- Verify firewall settings

#### Getting Help

- Enable plugin logging
- Check WooCommerce logs
- Review PHP error logs
- Contact support with details

### Support Contacts

- **Email**: support@umeskiasoftwares.com
- **GitHub**: [Issues Page](https://github.com/UMESKIA-SOFTWARES/UmsPay-WooCommwece-Plugin/issues)
- **Documentation**: [umeskiasoftwares.com](https://umeskiasoftwares.com/)
