# M-Pesa API Connection Troubleshooting

## "Test Connection Failed" Error

If you're getting a "test connection failed" error, here are the steps to diagnose and fix the issue:

## 1. Check Your API Credentials

### For Sandbox Testing (Most Common)
The most common cause is incorrect or missing sandbox credentials.

**Required Sandbox Credentials:**
- **Consumer Key**: Get from Safaricom Developer Portal
- **Consumer Secret**: Get from Safaricom Developer Portal  
- **Business Short Code**: Usually `174379` for sandbox
- **Pass Key**: Usually `bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919`

### How to Get Sandbox Credentials:
1. Go to [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Create account or log in
3. Click **"My Apps"** → **"Add a new app"**
4. Select **"Lipa Na M-Pesa Online"**
5. Fill in app details and submit
6. Once created, you'll see your **Consumer Key** and **Consumer Secret**

### Common Credential Issues:
- ❌ Using production credentials in sandbox mode
- ❌ Using old/expired credentials
- ❌ Copying credentials with extra spaces
- ❌ App not approved/active in developer portal

## 2. Check Environment Settings

**In Plugin Settings:**
- ✅ Enable **"Sandbox Mode"** for testing
- ✅ Use **sandbox credentials** when sandbox is enabled
- ✅ Use **production credentials** only when sandbox is disabled

**Common Environment Mistakes:**
- Using production credentials with sandbox mode enabled
- Using sandbox credentials with sandbox mode disabled

## 3. Test with Curl (Advanced)

To test if your credentials work outside WordPress, use this curl command:

```bash
curl -X GET \
  'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' \
  -H 'Authorization: Basic [BASE64_ENCODED_CREDENTIALS]'
```

Replace `[BASE64_ENCODED_CREDENTIALS]` with base64 encoding of `consumer_key:consumer_secret`

**Expected Response:**
```json
{
  "access_token": "ABC123...",
  "expires_in": "3599"
}
```

## 4. Check Server Requirements

### Required PHP Extensions:
- ✅ **curl** - For API requests
- ✅ **json** - For JSON parsing
- ✅ **openssl** - For SSL connections

### Check Extensions:
```php
<?php
echo "CURL: " . (extension_loaded('curl') ? 'Yes' : 'No') . "\n";
echo "JSON: " . (extension_loaded('json') ? 'Yes' : 'No') . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'Yes' : 'No') . "\n";
?>
```

### Network Requirements:
- ✅ Server can make **outbound HTTPS requests**
- ✅ Port **443** is open for outbound connections
- ✅ No firewall blocking `safaricom.co.ke` domain

## 5. Check WordPress Configuration

### Enable Debug Mode:
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Error Logs:
- **WordPress Error Log**: `/wp-content/debug.log`
- **Server Error Log**: Ask hosting provider
- **M-Pesa Plugin Logs**: WooCommerce → Status → Logs

## 6. Common Error Messages

### "Missing consumer key or secret"
**Fix:** Add your API credentials in plugin settings

### "API returned status code: 400"
**Possible causes:**
- Invalid credentials format
- Wrong grant_type parameter
- Malformed request

### "API returned status code: 401"
**Possible causes:**
- Wrong consumer key/secret
- Expired credentials
- App not active in developer portal

### "API returned status code: 403"
**Possible causes:**
- App not approved
- IP address restrictions
- Invalid app configuration

### "Connection timeout"
**Possible causes:**
- Server can't reach Safaricom APIs
- Firewall blocking outbound requests
- Network connectivity issues

## 7. Step-by-Step Debugging

### Step 1: Verify Basic Setup
```bash
# Check if WordPress can make HTTP requests
wp eval "var_dump(wp_remote_get('https://httpbin.org/get'));"
```

### Step 2: Test Raw API Call
```php
<?php
// Add this to functions.php temporarily
function test_mpesa_raw() {
    $consumer_key = 'YOUR_CONSUMER_KEY';
    $consumer_secret = 'YOUR_CONSUMER_SECRET';
    $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
    
    $response = wp_remote_get('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials', array(
        'headers' => array(
            'Authorization' => 'Basic ' . $credentials,
            'Content-Type' => 'application/json'
        ),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        echo 'Error: ' . $response->get_error_message();
    } else {
        echo 'Response: ' . wp_remote_retrieve_body($response);
    }
}

// Call the function
test_mpesa_raw();
?>
```

### Step 3: Check Response Details
Look for specific error messages in the API response:
- `"Invalid authentication credentials"`
- `"Invalid grant_type"`
- `"App not found"`

## 8. Working Test Credentials

For **sandbox testing only**, you can use these test credentials to verify your setup:

```
Consumer Key: [Get from Safaricom Developer Portal]
Consumer Secret: [Get from Safaricom Developer Portal]
Business Short Code: 174379
Pass Key: bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
```

⚠️ **Important**: You must get your own Consumer Key and Consumer Secret from the developer portal.

## 9. Hosting Provider Issues

### Common Hosting Restrictions:
- **Shared hosting**: May block outbound HTTPS requests
- **Firewall rules**: May block specific domains
- **PHP settings**: `allow_url_fopen` disabled
- **Curl settings**: SSL verification issues

### Contact Hosting Support if:
- Other HTTPS requests work but M-Pesa doesn't
- You get "Connection refused" errors
- Timeouts occur consistently

## 10. Quick Fix Checklist

- [ ] API credentials are correct and complete
- [ ] Sandbox mode matches credential type
- [ ] WordPress can make outbound HTTPS requests
- [ ] Required PHP extensions are loaded
- [ ] No firewall blocking safaricom.co.ke
- [ ] Error logs checked for specific issues
- [ ] App is active in Safaricom Developer Portal

## Still Not Working?

### Check Debug Output:
1. Enable debug mode in WordPress
2. Try the test connection again
3. Check `/wp-content/debug.log` for detailed errors
4. Look for M-Pesa specific error messages

### Get Help:
- **Safaricom Developer Support**: [Portal Support](https://developer.safaricom.co.ke/)
- **Hosting Provider**: For server connectivity issues
- **WordPress Forums**: For WordPress-specific problems

### Test Environment:
Try the connection test from a different environment:
- Different hosting provider
- Local development setup
- Different server location

---

**Pro Tip**: Start with sandbox credentials first, get the connection working, then move to production credentials when ready to go live.