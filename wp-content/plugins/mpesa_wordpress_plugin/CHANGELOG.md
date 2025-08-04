# Changelog - M-Pesa for WooCommerce

## [2.1.0] - 2025-08-04

### 🎉 Major Update: WooCommerce Blocks Support

#### Added
- **WooCommerce Blocks Integration**: Full support for block-based checkout experience
- **React Payment Component**: Modern, interactive payment interface for blocks
- **Dual Checkout Support**: Seamlessly works with both classic and block checkout flows
- **Enhanced Phone Validation**: Real-time formatting and validation in blocks
- **Auto-formatting**: Converts various phone formats (07xx, 7xx) to 254xxxxxxxxx format
- **Modern UI Components**: Clean, responsive design with improved user experience
- **Payment Instructions**: Comprehensive step-by-step guidance for customers
- **Cross-checkout Compatibility**: Unified payment processing for both checkout types
- **Enhanced Error Handling**: Better validation and user feedback in blocks
- **Accessibility Features**: Screen reader friendly with proper ARIA labels

#### Enhanced
- **Payment Processing**: Improved handling of payment data from both checkout types
- **Phone Number Validation**: Enhanced regex validation with real-time feedback
- **Order Management**: Better order notes and transaction tracking
- **JavaScript Integration**: Auto-formatting for classic checkout phone fields
- **CSS Styling**: Improved visual design for payment fields
- **Code Structure**: Modernized codebase with better organization
- **Asset Management**: Proper dependency handling for block scripts
- **API Integration**: Enhanced STK Push implementation with better error handling

#### Technical Improvements
- **Block Registration**: Proper integration with WooCommerce Blocks API
- **React Components**: Full-featured payment component with hooks
- **Asset Dependencies**: Optimized script loading and dependencies
- **Payment Data Handling**: Support for both classic and block checkout data structures
- **Property Declarations**: Added missing class properties to fix PHP warnings
- **Version Management**: Updated to v2.1.0 with proper asset versioning

### Files Added
- `includes/class-mpesa-blocks-support.php` - WooCommerce Blocks integration class
- `assets/js/frontend/blocks.js` - React component for block checkout
- `assets/js/frontend/blocks.asset.php` - Asset dependencies configuration
- `BLOCKS-SUPPORT.md` - Comprehensive documentation for blocks support

### Files Modified
- `mpesa_wordpress_plugin.php` - Enhanced with blocks support and improved payment processing

### Migration Notes
- **Automatic Upgrade**: No manual migration steps required
- **Settings Preservation**: All existing plugin settings are maintained
- **Backward Compatibility**: Classic checkout continues to work exactly as before
- **New Features**: Block checkout support is automatically enabled when WooCommerce Blocks is active

---

## [2.0.0] - Previous Release

### Core Features
- STK Push integration with M-Pesa Daraja API
- Classic WooCommerce checkout support
- Payment callback handling
- Order status management
- Admin configuration panel
- Transaction logging and tracking

### API Integration
- OAuth token management
- STK Push request handling
- Callback URL processing
- Error handling and logging
- Environment switching (sandbox/production)

### Payment Flow
- Phone number validation
- Order processing
- Stock management
- Customer notifications
- Receipt number storage

---

## Testing Checklist

### Before Release
- [ ] Classic checkout payment flow
- [ ] Block checkout payment flow
- [ ] Phone number validation and auto-formatting
- [ ] STK Push generation and processing
- [ ] Callback handling and order completion
- [ ] Error handling for failed payments
- [ ] Admin settings configuration
- [ ] Multi-device compatibility testing
- [ ] Accessibility compliance

### API Testing
- [ ] Sandbox environment connectivity
- [ ] Production environment setup
- [ ] OAuth token generation
- [ ] STK Push API calls
- [ ] Callback URL processing
- [ ] Error response handling

---

## Compatibility Matrix

| Component | Version | Status |
|-----------|---------|--------|
| WordPress | 5.0+ | ✅ Supported |
| WooCommerce | 4.0+ | ✅ Supported |
| WooCommerce Blocks | 7.0+ | ✅ Supported |
| PHP | 7.4+ | ✅ Required |
| M-Pesa Daraja API | v1 | ✅ Integrated |

## Breaking Changes

### v2.0.0 to v2.1.0
- **None**: Fully backward compatible
- Classic checkout behavior unchanged
- All existing settings preserved
- No API changes

## Support Information

- **Plugin Support**: Full documentation and setup guides
- **WooCommerce Compatibility**: Tested with latest WooCommerce versions
- **M-Pesa Integration**: Complete Daraja API implementation
- **Block Checkout**: Native WooCommerce Blocks support

## Future Roadmap

### Planned Features
- [ ] Subscription payment support
- [ ] Refund processing via API
- [ ] Multi-currency support
- [ ] Advanced reporting dashboard
- [ ] Webhook security enhancements

### Performance Optimizations
- [ ] Cache implementation for access tokens
- [ ] Optimized callback processing
- [ ] Enhanced error logging
- [ ] Database query optimization

---

For technical support or feature requests, please refer to the plugin documentation or contact the development team.
