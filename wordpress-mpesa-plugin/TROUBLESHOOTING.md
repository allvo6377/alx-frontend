# WordPress M-Pesa Plugin - Troubleshooting Guide

## "No Payment Methods Available" Error

This error occurs when WooCommerce cannot find any valid payment methods at checkout. Here's how to fix it:

### Step 1: Verify Plugin Activation

1. **Check Plugin Status**
   - Go to **WordPress Admin > Plugins > Installed Plugins**
   - Ensure "WordPress M-Pesa Payment Gateway" is **Active**
   - If not active, click **Activate**

2. **Check for PHP Errors**
   - Go to **WordPress Admin > Tools > Site Health > Info > Server**
   - Look for any PHP errors related to the M-Pesa plugin
   - Check your error logs: `/wp-content/debug.log`

### Step 2: Verify WooCommerce Integration

1. **Check WooCommerce Status**
   - Ensure WooCommerce is active and up-to-date
   - Go to **WooCommerce > Status** and check for any issues

2. **Check Payment Methods**
   - Go to **WooCommerce > Settings > Payments**
   - Look for "M-Pesa" in the payment methods list
   - If missing, the plugin integration has failed

### Step 3: Enable M-Pesa Gateway

1. **Enable the Gateway**
   - Go to **WooCommerce > Settings > Payments**
   - Find "M-Pesa" and click **Set up** or **Manage**
   - Check the box for **"Enable M-Pesa Payment"**
   - Click **Save changes**

### Step 4: Configure API Credentials

The gateway won't be available without proper API credentials:

1. **Add Sandbox Credentials (for testing)**
   - Consumer Key: Your sandbox consumer key
   - Consumer Secret: Your sandbox consumer secret
   - Business Short Code: `174379` (default sandbox)
   - Pass Key: Your sandbox pass key

2. **Test Configuration**
   - In M-Pesa settings, click **Test Connection**
   - Should show "Connection successful!"

### Step 5: Check Gateway Availability

The M-Pesa gateway might be disabled due to:

1. **Missing API Credentials**
   - Gateway is hidden if credentials are empty
   - Ensure all required fields are filled

2. **Currency Issues**
   - M-Pesa only works with KES (Kenyan Shillings)
   - Go to **WooCommerce > Settings > General**
   - Set **Currency** to "Kenyan shilling (KSh)"

3. **Country Restrictions**
   - Some gateways are country-specific
   - Set **Base Location** to Kenya

## Advanced Troubleshooting

### Enable Debug Mode

Add to your `wp-config.php` file:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Error Logs

1. **WordPress Error Log**
   - Location: `/wp-content/debug.log`
   - Look for M-Pesa related errors

2. **M-Pesa Plugin Logs**
   - Go to **WooCommerce > Status > Logs**
   - Select "mpesa-gateway" from dropdown

3. **Server Error Logs**
   - Check with your hosting provider
   - Look for PHP fatal errors

### Common PHP Errors and Fixes

#### Error: "Class 'WC_Payment_Gateway' not found"
**Cause:** WooCommerce not active or loaded
**Solution:**
```php
// Add this check to your code
if (!class_exists('WC_Payment_Gateway')) {
    return;
}
```

#### Error: "Plugin activated but no payment method appears"
**Cause:** Gateway class not properly registered
**Solution:** Check the `add_filter` hook in main plugin file:
```php
add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_class'));
```

### Manual Integration Check

If the plugin still doesn't work, manually verify the integration:

1. **Check Hook Registration**
   ```php
   // In wordpress-mpesa-gateway.php, verify this exists:
   add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_class'));
   ```

2. **Verify Class Loading**
   ```php
   // Check if the gateway class loads properly
   if (class_exists('WC_Mpesa_Gateway')) {
       echo "Gateway class loaded successfully";
   } else {
       echo "Gateway class not found";
   }
   ```

### Plugin Conflicts

1. **Deactivate Other Plugins**
   - Temporarily deactivate all plugins except WooCommerce and M-Pesa
   - If it works, reactivate plugins one by one to find the conflict

2. **Theme Conflicts**
   - Switch to a default theme (Twenty Twenty-Three)
   - Check if the payment method appears

### Database Issues

1. **Check if Tables Were Created**
   ```sql
   SHOW TABLES LIKE '%mpesa_transactions%';
   ```

2. **Recreate Tables**
   - Deactivate and reactivate the plugin
   - This will recreate the database tables

## Specific Error Messages

### "Invalid API Credentials"
**Solution:**
1. Verify credentials in Safaricom Developer Portal
2. Ensure you're using sandbox credentials for testing
3. Check if the app is active in the portal

### "SSL Certificate Required"
**Solution:**
1. Install SSL certificate on your website
2. Force HTTPS in WordPress settings
3. Update site URLs to use HTTPS

### "Callback URL Not Accessible"
**Solution:**
1. Ensure the callback URL is publicly accessible:
   `https://yoursite.com/wp-admin/admin-ajax.php?action=mpesa_callback`
2. Check firewall settings
3. Verify .htaccess doesn't block the URL

## Testing Checklist

### Pre-Launch Checklist
- [ ] Plugin activated successfully
- [ ] WooCommerce active and configured
- [ ] M-Pesa gateway appears in payment methods
- [ ] API credentials configured
- [ ] Test connection successful
- [ ] Currency set to KES
- [ ] SSL certificate installed
- [ ] Test transaction completed successfully

### Test Transaction Steps
1. Create a test product (KES 1.00)
2. Add to cart and proceed to checkout
3. Select M-Pesa payment method
4. Enter test phone number: `254708374149`
5. Complete order
6. Check if STK push is received
7. Verify transaction in admin panel

## Getting Help

### Log Information to Collect
When seeking help, provide:
1. WordPress version
2. WooCommerce version
3. PHP version
4. Error messages from logs
5. Plugin settings screenshots
6. Steps to reproduce the issue

### Support Resources
- **Plugin Documentation:** README.md
- **Safaricom Developer Portal:** https://developer.safaricom.co.ke/
- **WooCommerce Documentation:** https://docs.woocommerce.com/
- **WordPress Support:** https://wordpress.org/support/

## Quick Fixes Summary

| Issue | Quick Fix |
|-------|-----------|
| No payment methods | Enable M-Pesa in WooCommerce settings |
| API connection failed | Check credentials and test connection |
| STK push not received | Verify phone number format (254XXXXXXXXX) |
| SSL errors | Install SSL certificate |
| Currency issues | Set currency to KES |
| Plugin not loading | Check for PHP errors in debug.log |

---

**Still having issues?** 
1. Enable debug mode
2. Check error logs
3. Test with default theme and minimal plugins
4. Contact your hosting provider for server-level issues