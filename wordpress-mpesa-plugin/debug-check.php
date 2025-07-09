<?php
/**
 * M-Pesa Plugin Debug Check
 * 
 * INSTRUCTIONS:
 * 1. Upload this file to your WordPress root directory
 * 2. Visit: https://yoursite.com/debug-check.php
 * 3. Review the output to identify issues
 * 4. Delete this file after debugging
 */

// Prevent direct access without WordPress
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once('wp-config.php');
    require_once('wp-load.php');
}

// Start output
echo "<h1>WordPress M-Pesa Plugin Debug Check</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} .info{color:blue;}</style>";

// Check WordPress
echo "<h2>1. WordPress Environment</h2>";
echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Site URL:</strong> " . home_url() . "</p>";

// Check WooCommerce
echo "<h2>2. WooCommerce Status</h2>";
if (class_exists('WooCommerce')) {
    echo "<p class='success'>✓ WooCommerce is active</p>";
    echo "<p><strong>WooCommerce Version:</strong> " . WC()->version . "</p>";
    
    // Check currency
    $currency = get_woocommerce_currency();
    if ($currency === 'KES') {
        echo "<p class='success'>✓ Currency is set to KES (Kenyan Shillings)</p>";
    } else {
        echo "<p class='error'>✗ Currency is {$currency} - M-Pesa requires KES</p>";
        echo "<p class='info'>Fix: Go to WooCommerce → Settings → General → Currency</p>";
    }
} else {
    echo "<p class='error'>✗ WooCommerce is not active</p>";
    echo "<p class='info'>Fix: Install and activate WooCommerce plugin</p>";
}

// Check M-Pesa Plugin
echo "<h2>3. M-Pesa Plugin Status</h2>";
if (is_plugin_active('wordpress-mpesa-plugin/wordpress-mpesa-gateway.php')) {
    echo "<p class='success'>✓ M-Pesa plugin is active</p>";
} else {
    echo "<p class='error'>✗ M-Pesa plugin is not active</p>";
    echo "<p class='info'>Fix: Go to Plugins → Installed Plugins → Activate the M-Pesa plugin</p>";
}

// Check plugin files
$plugin_path = WP_PLUGIN_DIR . '/wordpress-mpesa-plugin/';
if (file_exists($plugin_path . 'wordpress-mpesa-gateway.php')) {
    echo "<p class='success'>✓ Main plugin file exists</p>";
} else {
    echo "<p class='error'>✗ Main plugin file not found</p>";
    echo "<p class='info'>Expected location: {$plugin_path}wordpress-mpesa-gateway.php</p>";
}

// Check gateway class
if (class_exists('WC_Mpesa_Gateway')) {
    echo "<p class='success'>✓ M-Pesa gateway class loaded</p>";
} else {
    echo "<p class='error'>✗ M-Pesa gateway class not found</p>";
    echo "<p class='info'>This indicates a code loading issue</p>";
}

// Check payment gateways
echo "<h2>4. Payment Gateway Registration</h2>";
if (function_exists('WC')) {
    $gateways = WC()->payment_gateways()->payment_gateways();
    if (isset($gateways['mpesa'])) {
        echo "<p class='success'>✓ M-Pesa gateway is registered with WooCommerce</p>";
        
        $gateway = $gateways['mpesa'];
        echo "<p><strong>Gateway Title:</strong> " . $gateway->title . "</p>";
        echo "<p><strong>Gateway Enabled:</strong> " . ($gateway->enabled === 'yes' ? 'Yes' : 'No') . "</p>";
        
        if ($gateway->enabled !== 'yes') {
            echo "<p class='warning'>⚠ Gateway is disabled</p>";
            echo "<p class='info'>Fix: Go to WooCommerce → Settings → Payments → M-Pesa → Enable</p>";
        }
        
        // Check API credentials
        echo "<h3>API Configuration</h3>";
        $sandbox_mode = $gateway->get_option('sandbox_mode');
        echo "<p><strong>Sandbox Mode:</strong> " . ($sandbox_mode === 'yes' ? 'Enabled' : 'Disabled') . "</p>";
        
        if ($sandbox_mode === 'yes') {
            $consumer_key = $gateway->get_option('sandbox_consumer_key');
            $consumer_secret = $gateway->get_option('sandbox_consumer_secret');
            echo "<p><strong>Sandbox Consumer Key:</strong> " . (empty($consumer_key) ? 'Not set' : 'Set') . "</p>";
            echo "<p><strong>Sandbox Consumer Secret:</strong> " . (empty($consumer_secret) ? 'Not set' : 'Set') . "</p>";
        } else {
            $consumer_key = $gateway->get_option('consumer_key');
            $consumer_secret = $gateway->get_option('consumer_secret');
            echo "<p><strong>Production Consumer Key:</strong> " . (empty($consumer_key) ? 'Not set' : 'Set') . "</p>";
            echo "<p><strong>Production Consumer Secret:</strong> " . (empty($consumer_secret) ? 'Not set' : 'Set') . "</p>";
        }
        
        if (empty($consumer_key) || empty($consumer_secret)) {
            echo "<p class='warning'>⚠ API credentials not configured</p>";
            echo "<p class='info'>Fix: Add your M-Pesa API credentials in gateway settings</p>";
        } else {
            echo "<p class='success'>✓ API credentials are configured</p>";
        }
        
    } else {
        echo "<p class='error'>✗ M-Pesa gateway not found in registered gateways</p>";
        echo "<p class='info'>Available gateways: " . implode(', ', array_keys($gateways)) . "</p>";
    }
} else {
    echo "<p class='error'>✗ WooCommerce not properly loaded</p>";
}

// Check database tables
echo "<h2>5. Database Tables</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'mpesa_transactions';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

if ($table_exists) {
    echo "<p class='success'>✓ M-Pesa transactions table exists</p>";
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    echo "<p><strong>Transaction Records:</strong> {$count}</p>";
} else {
    echo "<p class='error'>✗ M-Pesa transactions table not found</p>";
    echo "<p class='info'>Fix: Deactivate and reactivate the plugin to create tables</p>";
}

// Check SSL
echo "<h2>6. SSL Certificate</h2>";
if (is_ssl()) {
    echo "<p class='success'>✓ SSL is enabled</p>";
} else {
    echo "<p class='warning'>⚠ SSL is not enabled</p>";
    echo "<p class='info'>SSL is required for production M-Pesa payments</p>";
}

// Check PHP extensions
echo "<h2>7. PHP Extensions</h2>";
$required_extensions = ['curl', 'json', 'openssl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ {$ext} extension loaded</p>";
    } else {
        echo "<p class='error'>✗ {$ext} extension missing</p>";
    }
}

// Quick fix suggestions
echo "<h2>8. Quick Fix Recommendations</h2>";
echo "<div style='background:#f0f0f0;padding:15px;border-radius:5px;'>";

if (!class_exists('WooCommerce')) {
    echo "<p><strong>1.</strong> Install and activate WooCommerce</p>";
}

if (!is_plugin_active('wordpress-mpesa-plugin/wordpress-mpesa-gateway.php')) {
    echo "<p><strong>2.</strong> Activate the M-Pesa plugin</p>";
}

if (get_woocommerce_currency() !== 'KES') {
    echo "<p><strong>3.</strong> Set WooCommerce currency to KES</p>";
}

if (class_exists('WC_Mpesa_Gateway')) {
    $gateways = WC()->payment_gateways()->payment_gateways();
    if (isset($gateways['mpesa']) && $gateways['mpesa']->enabled !== 'yes') {
        echo "<p><strong>4.</strong> Enable M-Pesa in WooCommerce → Settings → Payments</p>";
    }
}

echo "</div>";

// Test API Connection if credentials are available
if (class_exists('WC_Mpesa_Gateway')) {
    echo "<h2>9. API Connection Test</h2>";
    $gateways = WC()->payment_gateways()->payment_gateways();
    if (isset($gateways['mpesa'])) {
        $gateway = $gateways['mpesa'];
        $sandbox_mode = $gateway->get_option('sandbox_mode');
        
        if ($sandbox_mode === 'yes') {
            $consumer_key = $gateway->get_option('sandbox_consumer_key');
            $consumer_secret = $gateway->get_option('sandbox_consumer_secret');
            $api_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        } else {
            $consumer_key = $gateway->get_option('consumer_key');
            $consumer_secret = $gateway->get_option('consumer_secret');
            $api_url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        }
        
        if (!empty($consumer_key) && !empty($consumer_secret)) {
            echo "<p>Testing API connection...</p>";
            
            $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
            
            $response = wp_remote_get($api_url, array(
                'headers' => array(
                    'Authorization' => 'Basic ' . $credentials,
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                echo "<p class='error'>✗ API Connection Failed: " . $response->get_error_message() . "</p>";
                echo "<p class='info'>This indicates a network or server configuration issue.</p>";
            } else {
                $response_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                
                if ($response_code === 200) {
                    $data = json_decode($body, true);
                    if (isset($data['access_token'])) {
                        echo "<p class='success'>✓ API Connection Successful</p>";
                        echo "<p><strong>Token Preview:</strong> " . substr($data['access_token'], 0, 20) . "...</p>";
                    } else {
                        echo "<p class='error'>✗ Invalid API Response</p>";
                        echo "<p><strong>Response:</strong> " . htmlspecialchars($body) . "</p>";
                    }
                } else {
                    echo "<p class='error'>✗ API Error (Status: {$response_code})</p>";
                    echo "<p><strong>Response:</strong> " . htmlspecialchars($body) . "</p>";
                    
                    // Provide specific error guidance
                    if ($response_code === 401) {
                        echo "<p class='info'>Status 401 means invalid credentials. Check your Consumer Key and Secret.</p>";
                    } elseif ($response_code === 403) {
                        echo "<p class='info'>Status 403 means access denied. Your app might not be approved.</p>";
                    } elseif ($response_code === 400) {
                        echo "<p class='info'>Status 400 means bad request. Check your app configuration.</p>";
                    }
                }
            }
        } else {
            echo "<p class='warning'>⚠ Cannot test API - credentials not configured</p>";
        }
    }
}

echo "<h2>10. Next Steps</h2>";
echo "<ol>";
echo "<li>Fix any issues marked with ✗ or ⚠</li>";
echo "<li>Configure your M-Pesa API credentials</li>";
echo "<li>Test API connection until successful</li>";
echo "<li>Test with a small transaction</li>";
echo "<li>Delete this debug file for security</li>";
echo "</ol>";

echo "<p><strong>Last checked:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p style='color:red;'><strong>Security Note:</strong> Delete this file after debugging!</p>";
?>