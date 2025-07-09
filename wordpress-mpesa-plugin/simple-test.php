<?php
/**
 * Simple M-Pesa API Test (Standalone)
 * 
 * INSTRUCTIONS:
 * 1. Upload this file to your WordPress root directory
 * 2. Edit the credentials below
 * 3. Visit: https://yoursite.com/simple-test.php
 * 4. Delete this file after testing
 */

// ======================================
// EDIT YOUR CREDENTIALS HERE
// ======================================
$CONSUMER_KEY = 'YOUR_CONSUMER_KEY_HERE';          // Get from Safaricom Developer Portal
$CONSUMER_SECRET = 'YOUR_CONSUMER_SECRET_HERE';    // Get from Safaricom Developer Portal
$SANDBOX_MODE = true;                              // Set to false for production

// ======================================
// DO NOT EDIT BELOW THIS LINE
// ======================================

echo "<h1>M-Pesa API Connection Test</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Check if credentials are set
if ($CONSUMER_KEY === 'YOUR_CONSUMER_KEY_HERE' || $CONSUMER_SECRET === 'YOUR_CONSUMER_SECRET_HERE') {
    echo "<p class='error'><strong>ERROR:</strong> Please edit this file and add your actual API credentials.</p>";
    echo "<p class='info'>Get credentials from: <a href='https://developer.safaricom.co.ke/' target='_blank'>Safaricom Developer Portal</a></p>";
    exit;
}

// Set API URL based on mode
$api_url = $SANDBOX_MODE ? 
    'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' : 
    'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

echo "<h2>1. Test Configuration</h2>";
echo "<p><strong>Mode:</strong> " . ($SANDBOX_MODE ? 'Sandbox' : 'Production') . "</p>";
echo "<p><strong>API URL:</strong> " . $api_url . "</p>";
echo "<p><strong>Consumer Key:</strong> " . substr($CONSUMER_KEY, 0, 10) . "..." . "</p>";
echo "<p><strong>Consumer Secret:</strong> " . substr($CONSUMER_SECRET, 0, 10) . "..." . "</p>";

echo "<h2>2. PHP Environment Check</h2>";

// Check PHP extensions
$required_extensions = ['curl', 'json', 'openssl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ {$ext} extension loaded</p>";
    } else {
        echo "<p class='error'>✗ {$ext} extension missing</p>";
    }
}

// Check if allow_url_fopen is enabled
if (ini_get('allow_url_fopen')) {
    echo "<p class='success'>✓ allow_url_fopen enabled</p>";
} else {
    echo "<p class='info'>allow_url_fopen disabled (using cURL instead)</p>";
}

echo "<h2>3. Network Connectivity Test</h2>";

// Test basic connectivity to Safaricom
$test_url = $SANDBOX_MODE ? 'https://sandbox.safaricom.co.ke' : 'https://api.safaricom.co.ke';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo "<p class='error'>✗ Cannot reach Safaricom servers: " . $curl_error . "</p>";
    echo "<p class='info'>This indicates a network or server configuration issue.</p>";
} else {
    echo "<p class='success'>✓ Can reach Safaricom servers (HTTP {$http_code})</p>";
}

echo "<h2>4. API Authentication Test</h2>";

// Prepare credentials
$credentials = base64_encode($CONSUMER_KEY . ':' . $CONSUMER_SECRET);

// Test API call using cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Basic ' . $credentials,
    'Content-Type: application/json',
    'Cache-Control: no-cache'
));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
$total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
curl_close($ch);

echo "<p><strong>Request Time:</strong> " . round($total_time, 2) . " seconds</p>";

if ($curl_error) {
    echo "<p class='error'>✗ API Request Failed: " . $curl_error . "</p>";
} else {
    echo "<p><strong>HTTP Status Code:</strong> " . $http_code . "</p>";
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($data['access_token'])) {
            echo "<p class='success'>✓ <strong>SUCCESS!</strong> API connection working correctly</p>";
            echo "<p><strong>Access Token (preview):</strong> " . substr($data['access_token'], 0, 20) . "...</p>";
            echo "<p><strong>Expires In:</strong> " . $data['expires_in'] . " seconds</p>";
        } else {
            echo "<p class='error'>✗ Invalid response format</p>";
            echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";
        }
    } else {
        echo "<p class='error'>✗ API Error (HTTP {$http_code})</p>";
        echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";
        
        // Provide specific guidance based on error code
        switch ($http_code) {
            case 400:
                echo "<p class='info'><strong>Fix:</strong> Bad request - check your app configuration in Safaricom portal</p>";
                break;
            case 401:
                echo "<p class='info'><strong>Fix:</strong> Invalid credentials - check your Consumer Key and Secret</p>";
                break;
            case 403:
                echo "<p class='info'><strong>Fix:</strong> Access denied - your app may not be approved or active</p>";
                break;
            case 404:
                echo "<p class='info'><strong>Fix:</strong> Endpoint not found - check API URL</p>";
                break;
            case 500:
                echo "<p class='info'><strong>Fix:</strong> Server error - try again later or contact Safaricom support</p>";
                break;
            default:
                echo "<p class='info'><strong>Fix:</strong> Check Safaricom Developer Portal for app status</p>";
        }
    }
}

echo "<h2>5. Troubleshooting Guide</h2>";

if (isset($http_code) && $http_code != 200) {
    echo "<div style='background:#fff3cd;border:1px solid #ffeaa7;padding:15px;border-radius:4px;'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    
    if ($http_code == 401) {
        echo "<li><strong>Check your API credentials:</strong>";
        echo "<ul>";
        echo "<li>Log into <a href='https://developer.safaricom.co.ke/' target='_blank'>Safaricom Developer Portal</a></li>";
        echo "<li>Go to 'My Apps' and select your app</li>";
        echo "<li>Copy the Consumer Key and Consumer Secret exactly (no extra spaces)</li>";
        echo "<li>Make sure you're using the right credentials for the right environment</li>";
        echo "</ul>";
        echo "</li>";
    }
    
    if ($http_code == 403) {
        echo "<li><strong>Check your app status:</strong>";
        echo "<ul>";
        echo "<li>Ensure your app is approved and active</li>";
        echo "<li>Check if there are any IP restrictions</li>";
        echo "<li>Verify your app is configured for 'Lipa Na M-Pesa Online'</li>";
        echo "</ul>";
        echo "</li>";
    }
    
    echo "<li><strong>Common fixes:</strong>";
    echo "<ul>";
    echo "<li>Wait a few minutes and try again (rate limiting)</li>";
    echo "<li>Check if your hosting provider blocks outbound HTTPS requests</li>";
    echo "<li>Ensure your server has a stable internet connection</li>";
    echo "</ul>";
    echo "</li>";
    
    echo "</ol>";
    echo "</div>";
} else if (isset($data['access_token'])) {
    echo "<div style='background:#d4edda;border:1px solid #c3e6cb;padding:15px;border-radius:4px;'>";
    echo "<h3>✓ Connection Successful!</h3>";
    echo "<p>Your M-Pesa API credentials are working correctly. You can now:</p>";
    echo "<ol>";
    echo "<li>Delete this test file for security</li>";
    echo "<li>Configure these same credentials in your WordPress M-Pesa plugin</li>";
    echo "<li>Enable the M-Pesa payment gateway in WooCommerce</li>";
    echo "<li>Test a small transaction</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<h2>6. WordPress Integration</h2>";
echo "<p>If this test passes but WordPress still fails:</p>";
echo "<ul>";
echo "<li>Check if WordPress can make outbound HTTP requests</li>";
echo "<li>Look for plugin conflicts</li>";
echo "<li>Enable WordPress debug mode and check error logs</li>";
echo "<li>Ensure the M-Pesa plugin files are properly uploaded</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p style='color:red;'><strong>Security Note:</strong> Delete this file after testing!</p>";
?>