# Installation Guide - WordPress M-Pesa Gateway

This guide will walk you through setting up the WordPress M-Pesa Payment Gateway plugin.

## Prerequisites

Before installing the plugin, ensure you have:

- [ ] WordPress 5.0+ installed
- [ ] WooCommerce 5.0+ plugin activated
- [ ] PHP 7.4+ on your server
- [ ] SSL certificate installed (required for production)
- [ ] M-Pesa Developer Account (for API credentials)

## Step 1: Download and Install Plugin

### Method A: Manual Upload
1. Download the plugin ZIP file
2. Log into your WordPress admin dashboard
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin**
5. Select the ZIP file and click **Install Now**
6. Click **Activate Plugin**

### Method B: FTP Upload
1. Extract the plugin ZIP file
2. Upload the `wordpress-mpesa-plugin` folder to `/wp-content/plugins/`
3. In WordPress admin, go to **Plugins > Installed Plugins**
4. Find "WordPress M-Pesa Payment Gateway" and click **Activate**

## Step 2: Get M-Pesa API Credentials

### Sandbox Credentials (For Testing)
1. Visit [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Create an account or log in
3. Navigate to **My Apps** and click **Add a new app**
4. Select **Lipa Na M-Pesa Online**
5. Fill in the required details:
   - App Name: Your app name
   - Description: Brief description
6. After creation, note down:
   - **Consumer Key**
   - **Consumer Secret**
   - **Test Credentials** (usually provided by Safaricom)

### Production Credentials
1. Complete the Go-Live process on Safaricom Developer Portal
2. Submit required documentation
3. Once approved, get your production credentials
4. Configure your shortcode and passkey

## Step 3: Configure Plugin Settings

1. In WordPress admin, go to **WooCommerce > Settings**
2. Click the **Payments** tab
3. Find **M-Pesa** in the payment methods list
4. Click **Set up** or **Manage**

### Basic Settings
- ✅ **Enable M-Pesa Payment**: Check this box
- **Title**: Enter "M-Pesa" (customers will see this)
- **Description**: Enter a customer-friendly description

### API Configuration

#### For Testing (Sandbox Mode)
- ✅ **Enable Sandbox Mode**: Check this box
- **Sandbox Consumer Key**: Enter your sandbox consumer key
- **Sandbox Consumer Secret**: Enter your sandbox consumer secret
- **Sandbox Business Short Code**: Usually `174379`
- **Sandbox Pass Key**: Usually provided by Safaricom

#### For Production (Live Mode)
- ❌ **Enable Sandbox Mode**: Uncheck this box
- **Consumer Key**: Enter your live consumer key
- **Consumer Secret**: Enter your live consumer secret
- **Business Short Code**: Your approved shortcode
- **Pass Key**: Your live pass key

## Step 4: Configure Webhook URL

1. In the plugin settings, copy the **Callback URL**:
   ```
   https://yoursite.com/wp-admin/admin-ajax.php?action=mpesa_callback
   ```

2. In your Safaricom Developer Portal:
   - Go to your app settings
   - Add the callback URL to the **Validation URL** and **Confirmation URL** fields

## Step 5: Test the Integration

### In Sandbox Mode
1. Create a test product in WooCommerce
2. Add it to cart and proceed to checkout
3. Select M-Pesa as payment method
4. Use test phone number: `254708374149`
5. Complete the order
6. You should receive an STK push simulation

### Verify Setup
1. Go to **WooCommerce > M-Pesa Transactions**
2. Check if test transactions are logged
3. In plugin settings, click **Test Connection** to verify API

## Step 6: Go Live

When ready for production:

1. **Disable Sandbox Mode** in plugin settings
2. **Add Production Credentials**
3. **Verify SSL Certificate** is active
4. **Update Webhook URLs** in Safaricom portal
5. **Test with Small Amount** first

## Troubleshooting

### Common Issues

**Plugin not appearing in payment methods**
- Ensure WooCommerce is active
- Check if plugin is activated
- Verify WooCommerce version compatibility

**API connection failing**
- Verify credentials are correct
- Check if sandbox/production mode matches credentials
- Ensure server can make outbound HTTPS requests

**STK Push not received**
- Verify phone number format (254XXXXXXXXX)
- Check if device supports STK Push
- Ensure sufficient account balance (for production)

**Callback not working**
- Verify callback URL is correct
- Check if server accepts incoming webhooks
- Ensure SSL certificate is valid

### Error Logs

Check error logs in:
- **WordPress Debug Log**: `/wp-content/debug.log`
- **M-Pesa Logs**: WooCommerce > Status > Logs
- **Server Error Logs**: Check with hosting provider

## Security Checklist

Before going live, ensure:

- [ ] SSL certificate is installed and active
- [ ] WordPress and plugins are updated
- [ ] Strong admin passwords are used
- [ ] Two-factor authentication is enabled
- [ ] Regular backups are configured
- [ ] Firewall protection is active

## Support

If you encounter issues:

1. **Check Documentation**: Review README.md
2. **Search Forums**: WooCommerce community forums
3. **Contact Host**: For server-related issues
4. **Safaricom Support**: For API-related problems

## Next Steps

After successful installation:

1. **Monitor Transactions**: Regularly check transaction logs
2. **Update Settings**: Adjust as needed for your business
3. **Train Staff**: Ensure team knows how to handle M-Pesa orders
4. **Customer Education**: Inform customers about M-Pesa payment option

---

**Need Help?** 
- Documentation: [GitHub Repository]
- Safaricom Support: [Developer Portal](https://developer.safaricom.co.ke/)
- WooCommerce Docs: [Official Documentation](https://docs.woocommerce.com/)

*Last updated: [Current Date]*