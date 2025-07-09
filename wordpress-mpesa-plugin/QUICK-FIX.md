# QUICK FIX: "Test Connection Failed" Error

## Step 1: Use the Simple Test (MOST IMPORTANT)

1. **Upload `simple-test.php`** to your WordPress root directory
2. **Edit the file** and add your actual API credentials:
   ```php
   $CONSUMER_KEY = 'your_actual_consumer_key';
   $CONSUMER_SECRET = 'your_actual_consumer_secret';
   $SANDBOX_MODE = true; // Keep true for testing
   ```
3. **Visit**: `https://yoursite.com/simple-test.php`
4. **This will tell you EXACTLY what's wrong**

## Step 2: Get Safaricom API Credentials (If You Don't Have Them)

**Most common cause: Missing or wrong credentials**

### To Get Sandbox Credentials:
1. Go to [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Create account or log in
3. Click **"Create App"**
4. Choose **"Lipa Na M-Pesa Online"**
5. Fill in details:
   - **App Name**: Your app name
   - **Description**: Brief description
6. After creation, copy:
   - **Consumer Key** (looks like: `ABC123xyz...`)
   - **Consumer Secret** (looks like: `XYZ789abc...`)

## Step 3: Common Quick Fixes

### Fix 1: Clear Plugin Settings
```
1. Go to WooCommerce → Settings → Payments → M-Pesa
2. Delete any existing credentials
3. Add fresh credentials from Safaricom portal
4. Save settings
5. Try test connection again
```

### Fix 2: Check WordPress HTTP Functions
Add this to your `functions.php` temporarily:
```php
add_action('wp_footer', function() {
    if (current_user_can('administrator')) {
        $test = wp_remote_get('https://httpbin.org/get');
        if (is_wp_error($test)) {
            echo '<div style="position:fixed;top:0;left:0;background:red;color:white;padding:10px;z-index:9999;">WordPress HTTP Error: ' . $test->get_error_message() . '</div>';
        } else {
            echo '<div style="position:fixed;top:0;left:0;background:green;color:white;padding:10px;z-index:9999;">WordPress HTTP Working</div>';
        }
    }
});
```

### Fix 3: Enable Debug Mode
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Fix 4: Check Server Requirements
Ask your hosting provider:
- Can the server make outbound HTTPS requests?
- Is cURL enabled and working?
- Are there any firewall restrictions for `safaricom.co.ke`?

## Step 4: Most Likely Issues & Solutions

| Problem | Solution |
|---------|----------|
| **No credentials** | Get them from Safaricom Developer Portal |
| **Wrong credentials** | Double-check Consumer Key/Secret |
| **Server blocks outbound requests** | Contact hosting provider |
| **WordPress HTTP issues** | Install "WP HTTP Test" plugin |
| **Plugin not updated** | Re-upload all plugin files |
| **Cache issues** | Clear all caches |

## Step 5: Emergency Fallback

If nothing works, try this **minimal test** in WordPress:

1. Go to **Appearance → Theme Editor**
2. Edit `functions.php`
3. Add this code at the end:

```php
add_action('wp_ajax_test_mpesa_direct', function() {
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
        echo 'ERROR: ' . $response->get_error_message();
    } else {
        echo 'SUCCESS: ' . wp_remote_retrieve_body($response);
    }
    
    wp_die();
});
```

4. Visit: `https://yoursite.com/wp-admin/admin-ajax.php?action=test_mpesa_direct`
5. Remove the code after testing

## Step 6: Check These Common Issues

### Issue: "Connection timeout"
**Cause**: Server can't reach Safaricom
**Fix**: Contact hosting provider about outbound HTTPS

### Issue: "Invalid credentials"
**Cause**: Wrong Consumer Key/Secret
**Fix**: Get fresh credentials from Safaricom portal

### Issue: "Access denied"
**Cause**: App not approved in Safaricom portal
**Fix**: Check app status in developer portal

### Issue: "cURL error"
**Cause**: Server configuration
**Fix**: Contact hosting provider

## Step 7: Get Help

If still not working, provide this info:
1. **Simple test results** from `simple-test.php`
2. **WordPress version** and **hosting provider**
3. **Any error messages** from `/wp-content/debug.log`
4. **Screenshot** of plugin settings page

---

## TL;DR - Quick Checklist

- [ ] Got Consumer Key & Secret from Safaricom?
- [ ] Ran `simple-test.php` to check credentials?
- [ ] Enabled debug mode in WordPress?
- [ ] Checked if server allows outbound HTTPS?
- [ ] Tried the emergency fallback test?

**90% of issues are solved by getting proper credentials from Safaricom Developer Portal.**