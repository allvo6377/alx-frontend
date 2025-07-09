# WordPress M-Pesa Payment Gateway

A comprehensive WordPress plugin that integrates M-Pesa payments with WooCommerce using Safaricom's Daraja API.

## Features

- **STK Push Integration**: Seamless mobile payment experience with M-Pesa STK Push
- **Real-time Payment Processing**: Instant payment confirmation via callbacks
- **Sandbox & Production Support**: Test payments in sandbox mode before going live
- **Transaction Management**: Complete transaction logging and monitoring
- **WooCommerce Integration**: Native WooCommerce payment gateway
- **Admin Dashboard**: Comprehensive admin interface with transaction reports
- **Security**: Built with WordPress security best practices
- **Responsive Design**: Mobile-friendly payment forms and admin interface

## Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- SSL Certificate (required for production)
- M-Pesa Developer Account

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `wordpress-mpesa-plugin` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to WooCommerce > Settings > Payments
5. Enable and configure the M-Pesa payment method

### Via WordPress Admin

1. Go to Plugins > Add New
2. Upload the plugin ZIP file
3. Activate the plugin
4. Configure the settings

## Configuration

### 1. Get M-Pesa API Credentials

1. Visit the [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Create an account and log in
3. Create a new app and select "Lipa Na M-Pesa Online"
4. Note down your:
   - Consumer Key
   - Consumer Secret
   - Business Short Code
   - Pass Key

### 2. Configure Plugin Settings

1. Go to WooCommerce > Settings > Payments
2. Click on "M-Pesa" to configure
3. Enable the payment method
4. Add your API credentials:
   - For testing: Use sandbox credentials
   - For production: Use live credentials
5. Save the settings

### 3. Set Up Webhooks

Configure the callback URL in your M-Pesa app settings:
```
https://yoursite.com/wp-admin/admin-ajax.php?action=mpesa_callback
```

## Usage

### For Customers

1. Add products to cart
2. Proceed to checkout
3. Select "M-Pesa" as payment method
4. Enter M-Pesa phone number
5. Complete the order
6. Authorize payment on mobile device when prompted

### For Administrators

#### View Transactions
- Go to WooCommerce > M-Pesa Transactions
- View all payment transactions with status
- Export transaction data

#### Monitor Status
- Access M-Pesa Gateway settings page
- Check system status and API connectivity
- View setup guide and documentation

## API Integration

The plugin uses Safaricom's Daraja API v1.0:

- **Authentication**: OAuth 2.0
- **STK Push**: Lipa Na M-Pesa Online API
- **Callbacks**: Real-time payment notifications
- **Query**: Transaction status checking

## Security Features

- Nonce verification for all AJAX requests
- Input sanitization and validation
- Secure credential storage
- SSL enforcement for production
- Database prepared statements
- Access control and capability checks

## Logging & Debugging

The plugin includes comprehensive logging:

- API requests and responses
- Payment attempts and results
- Error tracking and debugging
- Transaction audit trail

### Enable Debug Mode

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Troubleshooting

### Common Issues

**Payment not completing**
- Check callback URL configuration
- Verify API credentials
- Ensure SSL is enabled
- Check server connectivity

**Invalid phone number error**
- Ensure format: 254XXXXXXXXX
- Remove spaces and special characters
- Use Kenyan mobile numbers only

**API authentication failed**
- Verify Consumer Key and Secret
- Check if credentials are for correct environment
- Ensure app is active on Daraja portal

### Error Codes

| Code | Description | Solution |
|------|-------------|----------|
| 0 | Success | Payment completed |
| 1 | Insufficient Funds | Customer needs to add money |
| 1032 | Request cancelled | Customer cancelled payment |
| 1037 | DS timeout | Network timeout, retry |

## Development

### File Structure
```
wordpress-mpesa-plugin/
├── wordpress-mpesa-gateway.php     # Main plugin file
├── includes/
│   ├── class-mpesa-api.php         # API integration
│   ├── class-mpesa-gateway.php     # WooCommerce gateway
│   ├── class-mpesa-admin.php       # Admin interface
│   └── class-mpesa-logger.php      # Logging system
├── assets/
│   ├── js/
│   │   ├── mpesa-gateway.js        # Frontend JS
│   │   └── mpesa-admin.js          # Admin JS
│   └── css/
│       ├── mpesa-gateway.css       # Frontend CSS
│       └── mpesa-admin.css         # Admin CSS
└── README.md
```

### Hooks and Filters

**Actions:**
- `mpesa_payment_complete` - Fired when payment completes
- `mpesa_payment_failed` - Fired when payment fails
- `mpesa_stk_push_sent` - Fired when STK push is sent

**Filters:**
- `mpesa_callback_url` - Modify callback URL
- `mpesa_transaction_description` - Customize transaction description
- `mpesa_phone_validation` - Custom phone validation

### Extending the Plugin

```php
// Add custom validation
add_filter('mpesa_phone_validation', function($is_valid, $phone) {
    // Custom validation logic
    return $is_valid;
}, 10, 2);

// Custom transaction processing
add_action('mpesa_payment_complete', function($order_id, $transaction_data) {
    // Custom processing after payment
}, 10, 2);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Changelog

### Version 1.0.0
- Initial release
- STK Push integration
- WooCommerce payment gateway
- Admin interface
- Transaction logging
- Sandbox support

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and documentation:
- [Plugin Documentation](https://github.com/your-username/wordpress-mpesa-gateway)
- [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
- [WooCommerce Documentation](https://docs.woocommerce.com/)

## Credits

Developed for WordPress and WooCommerce integration with Safaricom's M-Pesa payment service.

---

**Note**: This plugin is not officially affiliated with Safaricom. M-Pesa is a trademark of Safaricom Ltd.