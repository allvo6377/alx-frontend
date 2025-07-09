<?php
/**
 * M-Pesa API Class
 * Handles communication with Safaricom's Daraja API
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Mpesa_API {
    
    /**
     * API URLs
     */
    private $sandbox_base_url = 'https://sandbox.safaricom.co.ke';
    private $production_base_url = 'https://api.safaricom.co.ke';
    
    /**
     * Settings
     */
    private $settings;
    private $is_sandbox;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('woocommerce_mpesa_settings', array());
        $this->is_sandbox = isset($this->settings['sandbox_mode']) && $this->settings['sandbox_mode'] === 'yes';
    }
    
    /**
     * Get base URL
     */
    private function get_base_url() {
        return $this->is_sandbox ? $this->sandbox_base_url : $this->production_base_url;
    }
    
    /**
     * Get access token
     */
    public function get_access_token() {
        $consumer_key = $this->is_sandbox ? $this->settings['sandbox_consumer_key'] : $this->settings['consumer_key'];
        $consumer_secret = $this->is_sandbox ? $this->settings['sandbox_consumer_secret'] : $this->settings['consumer_secret'];
        
        if (empty($consumer_key) || empty($consumer_secret)) {
            WP_Mpesa_Logger::log('Missing consumer key or secret');
            return false;
        }
        
        $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
        
        $url = $this->get_base_url() . '/oauth/v1/generate?grant_type=client_credentials';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            WP_Mpesa_Logger::log('Token request failed: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['access_token'])) {
            // Cache token for 55 minutes (tokens expire in 1 hour)
            set_transient('mpesa_access_token', $data['access_token'], 55 * MINUTE_IN_SECONDS);
            return $data['access_token'];
        }
        
        WP_Mpesa_Logger::log('Failed to get access token: ' . $body);
        return false;
    }
    
    /**
     * Get cached or fresh access token
     */
    private function get_token() {
        $token = get_transient('mpesa_access_token');
        if (!$token) {
            $token = $this->get_access_token();
        }
        return $token;
    }
    
    /**
     * Generate password for STK push
     */
    private function generate_password($timestamp) {
        $business_short_code = $this->is_sandbox ? $this->settings['sandbox_shortcode'] : $this->settings['shortcode'];
        $passkey = $this->is_sandbox ? $this->settings['sandbox_passkey'] : $this->settings['passkey'];
        
        return base64_encode($business_short_code . $passkey . $timestamp);
    }
    
    /**
     * Initiate STK Push
     */
    public function stk_push($phone, $amount, $order_id) {
        $token = $this->get_token();
        if (!$token) {
            return array(
                'success' => false,
                'message' => __('Failed to authenticate with M-Pesa', 'wp-mpesa-gateway')
            );
        }
        
        // Format phone number
        $phone = $this->format_phone_number($phone);
        if (!$phone) {
            return array(
                'success' => false,
                'message' => __('Invalid phone number format', 'wp-mpesa-gateway')
            );
        }
        
        $business_short_code = $this->is_sandbox ? $this->settings['sandbox_shortcode'] : $this->settings['shortcode'];
        $timestamp = date('YmdHis');
        $password = $this->generate_password($timestamp);
        
        $callback_url = home_url('/wp-admin/admin-ajax.php?action=mpesa_callback');
        
        $curl_post_data = array(
            'BusinessShortCode' => $business_short_code,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => round($amount),
            'PartyA' => $phone,
            'PartyB' => $business_short_code,
            'PhoneNumber' => $phone,
            'CallBackURL' => $callback_url,
            'AccountReference' => 'Order-' . $order_id,
            'TransactionDesc' => get_bloginfo('name') . ' - Order #' . $order_id
        );
        
        $url = $this->get_base_url() . '/mpesa/stkpush/v1/processrequest';
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($curl_post_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            WP_Mpesa_Logger::log('STK Push request failed: ' . $response->get_error_message());
            return array(
                'success' => false,
                'message' => __('Payment request failed. Please try again.', 'wp-mpesa-gateway')
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        WP_Mpesa_Logger::log('STK Push response: ' . $body);
        
        if (isset($data['ResponseCode']) && $data['ResponseCode'] == '0') {
            // Save transaction to database
            $this->save_transaction($order_id, $phone, $amount, $data['MerchantRequestID'], $data['CheckoutRequestID']);
            
            return array(
                'success' => true,
                'message' => __('Payment request sent. Please check your phone to complete the payment.', 'wp-mpesa-gateway'),
                'checkout_request_id' => $data['CheckoutRequestID']
            );
        } else {
            $error_message = isset($data['errorMessage']) ? $data['errorMessage'] : __('Payment request failed', 'wp-mpesa-gateway');
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
    }
    
    /**
     * Format phone number
     */
    private function format_phone_number($phone) {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle Kenyan phone numbers
        if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            // Convert 0722123456 to 254722123456
            return '254' . substr($phone, 1);
        } elseif (strlen($phone) == 9) {
            // Convert 722123456 to 254722123456
            return '254' . $phone;
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            // Already in correct format
            return $phone;
        }
        
        return false;
    }
    
    /**
     * Save transaction to database
     */
    private function save_transaction($order_id, $phone, $amount, $merchant_request_id, $checkout_request_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mpesa_transactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'phone' => $phone,
                'amount' => $amount,
                'merchant_request_id' => $merchant_request_id,
                'checkout_request_id' => $checkout_request_id,
                'status' => 'pending'
            ),
            array('%d', '%s', '%f', '%s', '%s', '%s')
        );
    }
    
    /**
     * Process callback from M-Pesa
     */
    public function process_callback($callback) {
        global $wpdb;
        
        if (!isset($callback['Body']['stkCallback'])) {
            WP_Mpesa_Logger::log('Invalid callback format');
            return;
        }
        
        $stk_callback = $callback['Body']['stkCallback'];
        $checkout_request_id = $stk_callback['CheckoutRequestID'];
        $result_code = $stk_callback['ResultCode'];
        
        // Find transaction in database
        $table_name = $wpdb->prefix . 'mpesa_transactions';
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE checkout_request_id = %s",
            $checkout_request_id
        ));
        
        if (!$transaction) {
            WP_Mpesa_Logger::log('Transaction not found: ' . $checkout_request_id);
            return;
        }
        
        $order = wc_get_order($transaction->order_id);
        if (!$order) {
            WP_Mpesa_Logger::log('Order not found: ' . $transaction->order_id);
            return;
        }
        
        if ($result_code == 0) {
            // Payment successful
            $mpesa_receipt_number = '';
            $amount = 0;
            
            if (isset($stk_callback['CallbackMetadata']['Item'])) {
                foreach ($stk_callback['CallbackMetadata']['Item'] as $item) {
                    if ($item['Name'] == 'MpesaReceiptNumber') {
                        $mpesa_receipt_number = $item['Value'];
                    }
                    if ($item['Name'] == 'Amount') {
                        $amount = $item['Value'];
                    }
                }
            }
            
            // Update transaction
            $wpdb->update(
                $table_name,
                array(
                    'status' => 'completed',
                    'mpesa_receipt_number' => $mpesa_receipt_number,
                    'callback_data' => json_encode($callback)
                ),
                array('checkout_request_id' => $checkout_request_id),
                array('%s', '%s', '%s'),
                array('%s')
            );
            
            // Update order
            $order->payment_complete($mpesa_receipt_number);
            $order->add_order_note(sprintf(
                __('M-Pesa payment completed. Receipt Number: %s', 'wp-mpesa-gateway'),
                $mpesa_receipt_number
            ));
            
            WP_Mpesa_Logger::log('Payment completed for order: ' . $transaction->order_id);
            
        } else {
            // Payment failed
            $result_desc = isset($stk_callback['ResultDesc']) ? $stk_callback['ResultDesc'] : 'Payment failed';
            
            // Update transaction
            $wpdb->update(
                $table_name,
                array(
                    'status' => 'failed',
                    'callback_data' => json_encode($callback)
                ),
                array('checkout_request_id' => $checkout_request_id),
                array('%s', '%s'),
                array('%s')
            );
            
            // Update order
            $order->update_status('failed', sprintf(
                __('M-Pesa payment failed: %s', 'wp-mpesa-gateway'),
                $result_desc
            ));
            
            WP_Mpesa_Logger::log('Payment failed for order: ' . $transaction->order_id . ' - ' . $result_desc);
        }
    }
    
    /**
     * Query transaction status
     */
    public function query_transaction_status($checkout_request_id) {
        $token = $this->get_token();
        if (!$token) {
            return false;
        }
        
        $business_short_code = $this->is_sandbox ? $this->settings['sandbox_shortcode'] : $this->settings['shortcode'];
        $timestamp = date('YmdHis');
        $password = $this->generate_password($timestamp);
        
        $curl_post_data = array(
            'BusinessShortCode' => $business_short_code,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkout_request_id
        );
        
        $url = $this->get_base_url() . '/mpesa/stkpushquery/v1/query';
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($curl_post_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}