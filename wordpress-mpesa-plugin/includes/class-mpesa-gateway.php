<?php
/**
 * WooCommerce M-Pesa Payment Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Mpesa_Gateway extends WC_Payment_Gateway {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'mpesa';
        $this->icon = WP_MPESA_GATEWAY_PLUGIN_URL . 'assets/images/mpesa-logo.png';
        $this->has_fields = true;
        $this->method_title = __('M-Pesa', 'wp-mpesa-gateway');
        $this->method_description = __('Allow customers to pay with M-Pesa mobile money service.', 'wp-mpesa-gateway');
        $this->supports = array(
            'products',
            'refunds'
        );
        
        // Load settings
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->sandbox_mode = $this->get_option('sandbox_mode');
        
        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        
        // Customer emails
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
    }
    
    /**
     * Initialize form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'wp-mpesa-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable M-Pesa Payment', 'wp-mpesa-gateway'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'wp-mpesa-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wp-mpesa-gateway'),
                'default' => __('M-Pesa', 'wp-mpesa-gateway'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'wp-mpesa-gateway'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'wp-mpesa-gateway'),
                'default' => __('Pay securely using your M-Pesa mobile money account.', 'wp-mpesa-gateway'),
                'desc_tip' => true,
            ),
            'sandbox_mode' => array(
                'title' => __('Sandbox Mode', 'wp-mpesa-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Sandbox Mode', 'wp-mpesa-gateway'),
                'default' => 'yes',
                'description' => __('Use sandbox environment for testing. Disable for live payments.', 'wp-mpesa-gateway'),
                'desc_tip' => true,
            ),
            'production_settings' => array(
                'title' => __('Production Settings', 'wp-mpesa-gateway'),
                'type' => 'title',
                'description' => __('Configure your production M-Pesa API credentials.', 'wp-mpesa-gateway'),
            ),
            'consumer_key' => array(
                'title' => __('Consumer Key', 'wp-mpesa-gateway'),
                'type' => 'text',
                'description' => __('Enter your M-Pesa consumer key for production.', 'wp-mpesa-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'consumer_secret' => array(
                'title' => __('Consumer Secret', 'wp-mpesa-gateway'),
                'type' => 'password',
                'description' => __('Enter your M-Pesa consumer secret for production.', 'wp-mpesa-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'shortcode' => array(
                'title' => __('Business Short Code', 'wp-mpesa-gateway'),
                'type' => 'text',
                'description' => __('Enter your M-Pesa business short code for production.', 'wp-mpesa-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'passkey' => array(
                'title' => __('Pass Key', 'wp-mpesa-gateway'),
                'type' => 'password',
                'description' => __('Enter your M-Pesa pass key for production.', 'wp-mpesa-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'sandbox_settings' => array(
                'title' => __('Sandbox Settings', 'wp-mpesa-gateway'),
                'type' => 'title',
                'description' => __('Configure your sandbox M-Pesa API credentials for testing.', 'wp-mpesa-gateway'),
            ),
            'sandbox_consumer_key' => array(
                'title' => __('Sandbox Consumer Key', 'wp-mpesa-gateway'),
                'type' => 'text',
                'description' => __('Enter your M-Pesa consumer key for sandbox testing.', 'wp-mpesa-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'sandbox_consumer_secret' => array(
                'title' => __('Sandbox Consumer Secret', 'wp-mpesa-gateway'),
                'type' => 'password',
                'description' => __('Enter your M-Pesa consumer secret for sandbox testing.', 'wp-mpesa-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'sandbox_shortcode' => array(
                'title' => __('Sandbox Business Short Code', 'wp-mpesa-gateway'),
                'type' => 'text',
                'description' => __('Enter your M-Pesa business short code for sandbox testing.', 'wp-mpesa-gateway'),
                'default' => '174379',
                'desc_tip' => true,
            ),
            'sandbox_passkey' => array(
                'title' => __('Sandbox Pass Key', 'wp-mpesa-gateway'),
                'type' => 'password',
                'description' => __('Enter your M-Pesa pass key for sandbox testing.', 'wp-mpesa-gateway'),
                'default' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
                'desc_tip' => true,
            ),
        );
    }
    
    /**
     * Payment form on checkout page
     */
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }
        
        echo '<fieldset id="wc-' . esc_attr($this->id) . '-form" class="wc-mpesa-form">';
        
        echo '<div class="form-row form-row-wide">';
        echo '<label for="mpesa_phone_number">' . __('M-Pesa Phone Number', 'wp-mpesa-gateway') . ' <span class="required">*</span></label>';
        echo '<input id="mpesa_phone_number" name="mpesa_phone_number" type="tel" placeholder="' . __('e.g. 0722123456', 'wp-mpesa-gateway') . '" class="input-text" />';
        echo '<small>' . __('Enter the phone number registered with M-Pesa', 'wp-mpesa-gateway') . '</small>';
        echo '</div>';
        
        echo '<div id="mpesa-payment-status" style="display: none;"></div>';
        
        echo '</fieldset>';
    }
    
    /**
     * Validate payment fields
     */
    public function validate_fields() {
        if (empty($_POST['mpesa_phone_number'])) {
            wc_add_notice(__('M-Pesa phone number is required.', 'wp-mpesa-gateway'), 'error');
            return false;
        }
        
        $phone = sanitize_text_field($_POST['mpesa_phone_number']);
        
        // Basic phone number validation
        if (!preg_match('/^(\+?254|0)[7][0-9]{8}$/', $phone)) {
            wc_add_notice(__('Please enter a valid Kenyan phone number.', 'wp-mpesa-gateway'), 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Process payment
     */
    public function process_payment($order_id) {
        // Check if API keys are configured before processing
        if (!$this->are_keys_set()) {
            wc_add_notice(__('M-Pesa payment method is not properly configured. Please contact the store administrator.', 'wp-mpesa-gateway'), 'error');
            return array(
                'result' => 'failure',
                'redirect' => ''
            );
        }
        
        $order = wc_get_order($order_id);
        $phone = sanitize_text_field($_POST['mpesa_phone_number']);
        $amount = $order->get_total();
        
        // Mark order as pending
        $order->update_status('pending', __('Awaiting M-Pesa payment', 'wp-mpesa-gateway'));
        
        // Reduce stock levels
        wc_reduce_stock_levels($order_id);
        
        // Remove cart
        WC()->cart->empty_cart();
        
        // Store phone number in order meta
        $order->update_meta_data('_mpesa_phone_number', $phone);
        $order->save();
        
        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }
    
    /**
     * Thank you page
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        
        if ($order->get_payment_method() !== $this->id) {
            return;
        }
        
        $phone = $order->get_meta('_mpesa_phone_number');
        $amount = $order->get_total();
        
        echo '<div id="mpesa-payment-section" class="woocommerce-order-received">';
        echo '<h3>' . __('Complete Your M-Pesa Payment', 'wp-mpesa-gateway') . '</h3>';
        echo '<p>' . sprintf(__('Please complete your payment of %s to the phone number %s.', 'wp-mpesa-gateway'), 
            wc_price($amount), 
            esc_html($phone)
        ) . '</p>';
        
        echo '<div id="mpesa-payment-form">';
        echo '<button id="initiate-mpesa-payment" class="button alt" data-order-id="' . $order_id . '" data-phone="' . esc_attr($phone) . '" data-amount="' . $amount . '">';
        echo __('Pay with M-Pesa', 'wp-mpesa-gateway');
        echo '</button>';
        echo '</div>';
        
        echo '<div id="mpesa-payment-status"></div>';
        echo '</div>';
        
        if ($this->sandbox_mode === 'yes') {
            echo '<div class="woocommerce-message">';
            echo '<strong>' . __('Sandbox Mode:', 'wp-mpesa-gateway') . '</strong> ';
            echo __('Use test phone number 254708374149 for sandbox payments.', 'wp-mpesa-gateway');
            echo '</div>';
        }
    }
    
    /**
     * Email instructions
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false) {
        if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status('on-hold')) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
    }
    
    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        
        if (!$order || $order->get_payment_method() !== $this->id) {
            return false;
        }
        
        // M-Pesa doesn't support automatic refunds through API
        // This would need to be handled manually
        $order->add_order_note(
            sprintf(__('Refund of %s requested. Reason: %s. Please process manually through M-Pesa.', 'wp-mpesa-gateway'), 
                wc_price($amount), 
                $reason
            )
        );
        
        return new WP_Error('mpesa_refund_error', __('M-Pesa refunds must be processed manually.', 'wp-mpesa-gateway'));
    }
    
    /**
     * Payment scripts
     */
    public function payment_scripts() {
        // We only need to enqueue scripts on cart/checkout pages
        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
            return;
        }
        
        // If our payment gateway is disabled, we do not have to enqueue JS too
        if ('no' === $this->enabled) {
            return;
        }
        
        // No reason to enqueue JavaScript if API keys are not set
        if (empty($this->get_option('consumer_key')) && empty($this->get_option('sandbox_consumer_key'))) {
            return;
        }
        
        wp_enqueue_script('woocommerce_mpesa', plugins_url('assets/js/mpesa-gateway.js', WP_MPESA_GATEWAY_PLUGIN_FILE), array('jquery'), WP_MPESA_GATEWAY_VERSION, true);
        
        wp_localize_script('woocommerce_mpesa', 'wc_mpesa_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mpesa_stk_push'),
            'processing_text' => __('Processing payment...', 'wp-mpesa-gateway'),
            'success_text' => __('Payment request sent successfully!', 'wp-mpesa-gateway'),
            'error_text' => __('Payment failed. Please try again.', 'wp-mpesa-gateway'),
            'enter_phone_text' => __('Please enter your M-Pesa phone number', 'wp-mpesa-gateway'),
            'invalid_phone_text' => __('Please enter a valid phone number', 'wp-mpesa-gateway')
        ));
    }
    
    /**
     * Check if gateway is available
     */
    public function is_available() {
        if ('yes' === $this->enabled) {
            // Allow gateway to be available even without keys for initial setup
            return true;
        }
        return false;
    }
    
    /**
     * Check if API keys are set
     */
    private function are_keys_set() {
        if ($this->sandbox_mode === 'yes') {
            return !empty($this->get_option('sandbox_consumer_key')) && !empty($this->get_option('sandbox_consumer_secret'));
        } else {
            return !empty($this->get_option('consumer_key')) && !empty($this->get_option('consumer_secret'));
        }
    }
    
    /**
     * Admin options
     */
    public function admin_options() {
        echo '<h2>' . esc_html($this->get_method_title());
        wc_back_link(__('Return to payments', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout'));
        echo '</h2>';
        
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
        
        // Display webhook URL
        echo '<div class="mpesa-webhook-info">';
        echo '<h3>' . __('Webhook Configuration', 'wp-mpesa-gateway') . '</h3>';
        echo '<p>' . __('Use the following URL as your M-Pesa callback URL:', 'wp-mpesa-gateway') . '</p>';
        echo '<code>' . home_url('/wp-admin/admin-ajax.php?action=mpesa_callback') . '</code>';
        echo '<p><small>' . __('This URL should be configured in your M-Pesa application settings.', 'wp-mpesa-gateway') . '</small></p>';
        echo '</div>';
    }
}