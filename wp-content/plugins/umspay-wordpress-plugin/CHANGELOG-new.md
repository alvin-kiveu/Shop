# Changelog

All notable changes to the UmsPay WooCommerce Plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2025-01-03 - 🚀 MAJOR UPDATE: Block Checkout Support

### 🎉 NEW: Full WooCommerce Block Checkout Support
- **🧱 Complete Block Integration**: Now fully compatible with WooCommerce's modern block-based checkout
- **📱 Interactive Phone Field**: Real-time phone number validation with auto-formatting in block checkout
- **🎨 Modern Block UI**: Beautiful, responsive design optimized for WooCommerce blocks
- **🔄 Dual Checkout Support**: Works seamlessly with both classic and block checkout methods
- **⚡ Enhanced Performance**: React-based components for smooth user experience

### 🔧 Enhanced Features
- **📞 Smart Phone Formatting**: Auto-converts various formats (07XX, 7XX, +254) to 254XXXXXXXXX
- **🛡️ Real-time Validation**: Instant feedback on phone number validity with visual indicators
- **🎯 Improved STK Push**: Enhanced payment initiation with better error handling
- **📊 Better User Feedback**: Clear success/error messages for both checkout types
- **🔐 Enhanced Security**: Improved data sanitization for block checkout submissions

### 🚀 Block Checkout Features
- ✅ Custom phone number field with pattern validation
- ✅ Real-time format checking and auto-correction
- ✅ Visual validation feedback (green/red borders)
- ✅ Clear payment instructions within blocks
- ✅ Responsive design for all screen sizes
- ✅ Proper error handling and user guidance
- ✅ Seamless integration with WooCommerce block ecosystem

### 🔧 Technical Improvements
- **🏗️ Modern Architecture**: React-based block components using WooCommerce Blocks API
- **📦 Proper Asset Management**: Optimized script loading with correct dependencies
- **🔧 Block Registration**: Proper payment method registration for WooCommerce blocks
- **📝 Enhanced Code Quality**: Comprehensive documentation and clean code structure
- **🧪 Improved Testing**: Support for testing both classic and block checkout flows

### 🐛 Bug Fixes
- Fixed phone number data transmission in block checkout
- Resolved payment method visibility issues in blocks
- Corrected validation feedback timing
- Fixed mobile responsiveness in block interface
- Improved error message display in blocks

### 📋 Migration from Classic to Block Checkout
No action required! The plugin automatically:
- Detects checkout type (classic vs block)
- Renders appropriate interface
- Handles payment data correctly
- Maintains all existing functionality

### 🔍 Compatibility Matrix
| Feature | Classic Checkout | Block Checkout |
|---------|------------------|----------------|
| Phone Number Field | ✅ | ✅ |
| Real-time Validation | ✅ | ✅ |
| Auto-formatting | ✅ | ✅ |
| STK Push | ✅ | ✅ |
| Error Handling | ✅ | ✅ |
| Mobile Support | ✅ | ✅ |
| Visual Feedback | ✅ | ✅ |

### 📱 Supported Formats
The plugin now accepts and auto-converts:
- `0712345678` → `254712345678` ✅
- `712345678` → `254712345678` ✅
- `+254712345678` → `254712345678` ✅
- `254712345678` → `254712345678` ✅

---

## [2.1.0] - 2024-12-XX

### Added
- 🚀 Initial release with M-Pesa STK Push integration
- 💳 Basic payment processing functionality
- 🔧 Admin configuration interface
- 📄 Webhook support for payment notifications
- 🔐 SSL/TLS support for secure API communication

### Features
- ✅ WooCommerce classic checkout integration
- ✅ M-Pesa STK Push payment initiation
- ✅ Order status management
- ✅ Basic error handling
- ✅ Admin settings panel

---

## Support & Documentation

- **📧 Support**: support@umeskiasoftwares.com
- **🌐 Website**: [umspay.co.ke](https://umspay.co.ke)
- **📚 Documentation**: [GitHub Repository](https://github.com/UMESKIA-SOFTWARES/UmsPay-WooCommwece-Plugin)
- **🐛 Bug Reports**: [GitHub Issues](https://github.com/UMESKIA-SOFTWARES/UmsPay-WooCommwece-Plugin/issues)

---

*Made with ❤️ by [UMESKIA SOFTWARES](https://umeskiasoftwares.com)*
