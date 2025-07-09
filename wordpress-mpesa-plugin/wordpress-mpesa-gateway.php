<?php
/**
 * Plugin Name: WordPress M-Pesa Payment Gateway
 * Plugin URI: https://github.com/your-username/wordpress-mpesa-gateway
 * Description: A comprehensive WordPress plugin that integrates M-Pesa payments with WooCommerce using Safaricom's Daraja API.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-mpesa-gateway
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_MPESA_GATEWAY_VERSION', '1.0.0');
define('WP_MPESA_GATEWAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_MPESA_GATEWAY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WP_MPESA_GATEWAY_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class WP_Mpesa_Gateway_Plugin {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load plugin text domain
        load_plugin_textdomain('wp-mpesa-gateway', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize payment gateway
        add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_class'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add AJAX handlers
        add_action('wp_ajax_mpesa_stk_push', array($this, 'handle_stk_push'));
        add_action('wp_ajax_nopriv_mpesa_stk_push', array($this, 'handle_stk_push'));
        
        // Handle M-Pesa callbacks
        add_action('wp_ajax_mpesa_callback', array($this, 'handle_callback'));
        add_action('wp_ajax_nopriv_mpesa_callback', array($this, 'handle_callback'));
        
        // Add admin AJAX handlers - will be handled by the admin class instance
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once WP_MPESA_GATEWAY_PLUGIN_PATH . 'includes/class-mpesa-api.php';
        require_once WP_MPESA_GATEWAY_PLUGIN_PATH . 'includes/class-mpesa-gateway.php';
        require_once WP_MPESA_GATEWAY_PLUGIN_PATH . 'includes/class-mpesa-admin.php';
        require_once WP_MPESA_GATEWAY_PLUGIN_PATH . 'includes/class-mpesa-logger.php';
        
        // Initialize admin class
        if (is_admin()) {
            new WP_Mpesa_Admin();
        }
    }
    
    /**
     * Add gateway class to WooCommerce
     */
    public function add_gateway_class($gateways) {
        $gateways[] = 'WC_Mpesa_Gateway';
        return $gateways;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('M-Pesa Gateway Settings', 'wp-mpesa-gateway'),
            __('M-Pesa Gateway', 'wp-mpesa-gateway'),
            'manage_options',
            'mpesa-gateway-settings',
            array('WP_Mpesa_Admin', 'settings_page')
        );
    }
    
    /**
     * Handle STK Push AJAX request
     */
    public function handle_stk_push() {
        check_ajax_referer('mpesa_stk_push', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone']);
        $amount = floatval($_POST['amount']);
        $order_id = intval($_POST['order_id']);
        
        $api = new WP_Mpesa_API();
        $result = $api->stk_push($phone, $amount, $order_id);
        
        wp_send_json($result);
    }
    
    /**
     * Handle M-Pesa callback
     */
    public function handle_callback() {
        $callback_data = file_get_contents('php://input');
        $callback = json_decode($callback_data, true);
        
        WP_Mpesa_Logger::log('Callback received: ' . $callback_data);
        
        $api = new WP_Mpesa_API();
        $api->process_callback($callback);
        
        http_response_code(200);
        echo json_encode(['status' => 'success']);
        wp_die();
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if (is_checkout() || is_cart()) {
            wp_enqueue_script(
                'mpesa-gateway',
                WP_MPESA_GATEWAY_PLUGIN_URL . 'assets/js/mpesa-gateway.js',
                array('jquery'),
                WP_MPESA_GATEWAY_VERSION,
                true
            );
            
            wp_localize_script('mpesa-gateway', 'mpesa_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mpesa_stk_push'),
                'processing_text' => __('Processing payment...', 'wp-mpesa-gateway'),
                'enter_phone_text' => __('Please enter your M-Pesa phone number', 'wp-mpesa-gateway')
            ));
            
            wp_enqueue_style(
                'mpesa-gateway',
                WP_MPESA_GATEWAY_PLUGIN_URL . 'assets/css/mpesa-gateway.css',
                array(),
                WP_MPESA_GATEWAY_VERSION
            );
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'settings_page_mpesa-gateway-settings') {
            wp_enqueue_script(
                'mpesa-admin',
                WP_MPESA_GATEWAY_PLUGIN_URL . 'assets/js/mpesa-admin.js',
                array('jquery'),
                WP_MPESA_GATEWAY_VERSION,
                true
            );
            
            wp_enqueue_style(
                'mpesa-admin',
                WP_MPESA_GATEWAY_PLUGIN_URL . 'assets/css/mpesa-admin.css',
                array(),
                WP_MPESA_GATEWAY_VERSION
            );
        }
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('WordPress M-Pesa Gateway requires WooCommerce to be installed and active.', 'wp-mpesa-gateway');
        echo '</p></div>';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('mpesa_check_payment_status');
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mpesa_transactions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            phone varchar(15) NOT NULL,
            amount decimal(10,2) NOT NULL,
            merchant_request_id varchar(255) NOT NULL,
            checkout_request_id varchar(255) NOT NULL,
            mpesa_receipt_number varchar(255) DEFAULT '',
            transaction_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'pending',
            callback_data text,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY checkout_request_id (checkout_request_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        if (!get_option('mpesa_gateway_sandbox_mode')) {
            update_option('mpesa_gateway_sandbox_mode', 'yes');
        }
    }
}

// Initialize plugin
WP_Mpesa_Gateway_Plugin::get_instance();