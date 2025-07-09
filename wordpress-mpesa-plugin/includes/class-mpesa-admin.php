<?php
/**
 * M-Pesa Admin Class
 * Handles admin functionality and settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Mpesa_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_mpesa_test_connection', array($this, 'test_connection'));
    }
    
    /**
     * Admin menu
     */
    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('M-Pesa Transactions', 'wp-mpesa-gateway'),
            __('M-Pesa Transactions', 'wp-mpesa-gateway'),
            'manage_woocommerce',
            'mpesa-transactions',
            array($this, 'transactions_page')
        );
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Register settings
        register_setting('mpesa_gateway_settings', 'mpesa_gateway_settings');
    }
    
    /**
     * Settings page
     */
    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="mpesa-admin-content">
                <div class="mpesa-main-settings">
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('M-Pesa Gateway Status', 'wp-mpesa-gateway'); ?></h2>
                        <div class="inside">
                            <?php self::display_status(); ?>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Quick Setup Guide', 'wp-mpesa-gateway'); ?></h2>
                        <div class="inside">
                            <?php self::display_setup_guide(); ?>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Test API Connection', 'wp-mpesa-gateway'); ?></h2>
                        <div class="inside">
                            <p><?php _e('Test your M-Pesa API credentials to ensure they are working correctly.', 'wp-mpesa-gateway'); ?></p>
                            <button id="test-mpesa-connection" class="button button-secondary">
                                <?php _e('Test Connection', 'wp-mpesa-gateway'); ?>
                            </button>
                            <div id="test-connection-result"></div>
                        </div>
                    </div>
                </div>
                
                <div class="mpesa-sidebar">
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Documentation', 'wp-mpesa-gateway'); ?></h2>
                        <div class="inside">
                            <ul>
                                <li><a href="https://developer.safaricom.co.ke/" target="_blank"><?php _e('Safaricom Developer Portal', 'wp-mpesa-gateway'); ?></a></li>
                                <li><a href="https://developer.safaricom.co.ke/APIs/MpesaExpressSimulate" target="_blank"><?php _e('M-Pesa Express API Documentation', 'wp-mpesa-gateway'); ?></a></li>
                                <li><a href="#" target="_blank"><?php _e('Plugin Documentation', 'wp-mpesa-gateway'); ?></a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Support', 'wp-mpesa-gateway'); ?></h2>
                        <div class="inside">
                            <p><?php _e('Need help with the plugin? Contact our support team.', 'wp-mpesa-gateway'); ?></p>
                            <a href="#" class="button button-primary"><?php _e('Get Support', 'wp-mpesa-gateway'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .mpesa-admin-content {
            display: flex;
            gap: 20px;
        }
        .mpesa-main-settings {
            flex: 2;
        }
        .mpesa-sidebar {
            flex: 1;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-badge.success {
            background: #46b450;
            color: white;
        }
        .status-badge.error {
            background: #dc3232;
            color: white;
        }
        .status-badge.warning {
            background: #ffb900;
            color: white;
        }
        .setup-step {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .setup-step h4 {
            margin: 0 0 10px 0;
        }
        .setup-step.completed {
            background: #f0f8f0;
            border-color: #46b450;
        }
        </style>
        <?php
    }
    
    /**
     * Display status
     */
    private static function display_status() {
        $gateway = new WC_Mpesa_Gateway();
        $api = new WP_Mpesa_API();
        
        echo '<div class="status-items">';
        
        // Plugin status
        echo '<div class="status-item">';
        echo '<span>' . __('Plugin Status', 'wp-mpesa-gateway') . '</span>';
        if ($gateway->enabled === 'yes') {
            echo '<span class="status-badge success">' . __('Enabled', 'wp-mpesa-gateway') . '</span>';
        } else {
            echo '<span class="status-badge error">' . __('Disabled', 'wp-mpesa-gateway') . '</span>';
        }
        echo '</div>';
        
        // WooCommerce status
        echo '<div class="status-item">';
        echo '<span>' . __('WooCommerce', 'wp-mpesa-gateway') . '</span>';
        if (class_exists('WooCommerce')) {
            echo '<span class="status-badge success">' . __('Active', 'wp-mpesa-gateway') . '</span>';
        } else {
            echo '<span class="status-badge error">' . __('Not Found', 'wp-mpesa-gateway') . '</span>';
        }
        echo '</div>';
        
        // API credentials status
        echo '<div class="status-item">';
        echo '<span>' . __('API Credentials', 'wp-mpesa-gateway') . '</span>';
        if ($gateway->sandbox_mode === 'yes') {
            $consumer_key = $gateway->get_option('sandbox_consumer_key');
            $consumer_secret = $gateway->get_option('sandbox_consumer_secret');
        } else {
            $consumer_key = $gateway->get_option('consumer_key');
            $consumer_secret = $gateway->get_option('consumer_secret');
        }
        
        if (!empty($consumer_key) && !empty($consumer_secret)) {
            echo '<span class="status-badge success">' . __('Configured', 'wp-mpesa-gateway') . '</span>';
        } else {
            echo '<span class="status-badge error">' . __('Missing', 'wp-mpesa-gateway') . '</span>';
        }
        echo '</div>';
        
        // Mode
        echo '<div class="status-item">';
        echo '<span>' . __('Environment', 'wp-mpesa-gateway') . '</span>';
        if ($gateway->sandbox_mode === 'yes') {
            echo '<span class="status-badge warning">' . __('Sandbox', 'wp-mpesa-gateway') . '</span>';
        } else {
            echo '<span class="status-badge success">' . __('Production', 'wp-mpesa-gateway') . '</span>';
        }
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Display setup guide
     */
    private static function display_setup_guide() {
        $gateway = new WC_Mpesa_Gateway();
        
        $steps = array(
            array(
                'title' => __('Create M-Pesa Developer Account', 'wp-mpesa-gateway'),
                'description' => __('Register at the Safaricom Developer Portal and create a new app.', 'wp-mpesa-gateway'),
                'completed' => false
            ),
            array(
                'title' => __('Configure API Credentials', 'wp-mpesa-gateway'),
                'description' => __('Enter your Consumer Key and Consumer Secret in the payment gateway settings.', 'wp-mpesa-gateway'),
                'completed' => !empty($gateway->get_option('sandbox_consumer_key'))
            ),
            array(
                'title' => __('Enable the Gateway', 'wp-mpesa-gateway'),
                'description' => __('Go to WooCommerce > Settings > Payments and enable the M-Pesa gateway.', 'wp-mpesa-gateway'),
                'completed' => $gateway->enabled === 'yes'
            ),
            array(
                'title' => __('Test the Integration', 'wp-mpesa-gateway'),
                'description' => __('Make a test purchase to ensure everything is working correctly.', 'wp-mpesa-gateway'),
                'completed' => false
            )
        );
        
        foreach ($steps as $step) {
            $class = $step['completed'] ? 'setup-step completed' : 'setup-step';
            echo '<div class="' . $class . '">';
            echo '<h4>' . $step['title'];
            if ($step['completed']) {
                echo ' ✓';
            }
            echo '</h4>';
            echo '<p>' . $step['description'] . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Transactions page
     */
    public function transactions_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mpesa_transactions';
        
        // Handle pagination
        $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Get transactions
        $transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY transaction_date DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        $total_transactions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_transactions / $per_page);
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="mpesa-transactions-summary">
                <?php $this->display_transaction_summary(); ?>
            </div>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="status_filter" id="status_filter">
                        <option value=""><?php _e('All statuses', 'wp-mpesa-gateway'); ?></option>
                        <option value="pending"><?php _e('Pending', 'wp-mpesa-gateway'); ?></option>
                        <option value="completed"><?php _e('Completed', 'wp-mpesa-gateway'); ?></option>
                        <option value="failed"><?php _e('Failed', 'wp-mpesa-gateway'); ?></option>
                    </select>
                    <input type="submit" class="button" value="<?php _e('Filter', 'wp-mpesa-gateway'); ?>">
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="alignright">
                    <?php
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $page,
                        'total' => $total_pages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;'
                    );
                    echo paginate_links($pagination_args);
                    ?>
                </div>
                <?php endif; ?>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php _e('ID', 'wp-mpesa-gateway'); ?></th>
                        <th scope="col"><?php _e('Order', 'wp-mpesa-gateway'); ?></th>
                        <th scope="col"><?php _e('Phone', 'wp-mpesa-gateway'); ?></th>
                        <th scope="col"><?php _e('Amount', 'wp-mpesa-gateway'); ?></th>
                        <th scope="col"><?php _e('Receipt', 'wp-mpesa-gateway'); ?></th>
                        <th scope="col"><?php _e('Status', 'wp-mpesa-gateway'); ?></th>
                        <th scope="col"><?php _e('Date', 'wp-mpesa-gateway'); ?></th>
                        <th scope="col"><?php _e('Actions', 'wp-mpesa-gateway'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions): ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo esc_html($transaction->id); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('post.php?post=' . $transaction->order_id . '&action=edit'); ?>">
                                        #<?php echo esc_html($transaction->order_id); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($transaction->phone); ?></td>
                                <td><?php echo wc_price($transaction->amount); ?></td>
                                <td><?php echo esc_html($transaction->mpesa_receipt_number); ?></td>
                                <td>
                                    <span class="status-badge <?php echo esc_attr($transaction->status); ?>">
                                        <?php echo esc_html(ucfirst($transaction->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($transaction->transaction_date))); ?></td>
                                <td>
                                    <a href="#" class="button button-small view-details" data-id="<?php echo esc_attr($transaction->id); ?>">
                                        <?php _e('View Details', 'wp-mpesa-gateway'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8"><?php _e('No transactions found.', 'wp-mpesa-gateway'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .mpesa-transactions-summary {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            flex: 1;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .summary-card p {
            margin: 0;
            color: #666;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-badge.pending {
            background: #ffb900;
            color: white;
        }
        .status-badge.completed {
            background: #46b450;
            color: white;
        }
        .status-badge.failed {
            background: #dc3232;
            color: white;
        }
        </style>
        <?php
    }
    
    /**
     * Display transaction summary
     */
    private function display_transaction_summary() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mpesa_transactions';
        
        $total_transactions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_amount = $wpdb->get_var("SELECT SUM(amount) FROM $table_name WHERE status = 'completed'");
        $pending_transactions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        $failed_transactions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'failed'");
        
        ?>
        <div class="summary-card">
            <h3><?php echo number_format($total_transactions); ?></h3>
            <p><?php _e('Total Transactions', 'wp-mpesa-gateway'); ?></p>
        </div>
        <div class="summary-card">
            <h3><?php echo wc_price($total_amount ?: 0); ?></h3>
            <p><?php _e('Total Revenue', 'wp-mpesa-gateway'); ?></p>
        </div>
        <div class="summary-card">
            <h3><?php echo number_format($pending_transactions); ?></h3>
            <p><?php _e('Pending Payments', 'wp-mpesa-gateway'); ?></p>
        </div>
        <div class="summary-card">
            <h3><?php echo number_format($failed_transactions); ?></h3>
            <p><?php _e('Failed Payments', 'wp-mpesa-gateway'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        check_ajax_referer('mpesa_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Access denied', 'wp-mpesa-gateway'));
        }
        
        $api = new WP_Mpesa_API();
        $token = $api->get_access_token();
        
        if ($token) {
            wp_send_json_success(array(
                'message' => __('Connection successful! API credentials are working correctly.', 'wp-mpesa-gateway')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Connection failed. Please check your API credentials.', 'wp-mpesa-gateway')
            ));
        }
    }
}