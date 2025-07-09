<?php
/**
 * M-Pesa Logger Class
 * Handles logging for debugging and monitoring
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Mpesa_Logger {
    
    /**
     * Log levels
     */
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    
    /**
     * Logger instance
     */
    private static $logger = null;
    
    /**
     * Get WooCommerce logger instance
     */
    private static function get_logger() {
        if (null === self::$logger) {
            if (class_exists('WC_Logger')) {
                self::$logger = wc_get_logger();
            }
        }
        return self::$logger;
    }
    
    /**
     * Log a message
     */
    public static function log($message, $level = self::INFO, $context = array()) {
        $logger = self::get_logger();
        
        if (!$logger) {
            // Fallback to error_log if WooCommerce logger is not available
            error_log('M-Pesa Gateway: ' . $message);
            return;
        }
        
        // Add timestamp and source to context
        $context = array_merge($context, array(
            'source' => 'mpesa-gateway',
            'timestamp' => current_time('mysql')
        ));
        
        // Format message
        $formatted_message = self::format_message($message, $context);
        
        // Log the message
        $logger->log($level, $formatted_message, $context);
        
        // Also log to custom log file if debug mode is enabled
        if (self::is_debug_mode()) {
            self::log_to_file($formatted_message, $level);
        }
    }
    
    /**
     * Log debug message
     */
    public static function debug($message, $context = array()) {
        self::log($message, self::DEBUG, $context);
    }
    
    /**
     * Log info message
     */
    public static function info($message, $context = array()) {
        self::log($message, self::INFO, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning($message, $context = array()) {
        self::log($message, self::WARNING, $context);
    }
    
    /**
     * Log error message
     */
    public static function error($message, $context = array()) {
        self::log($message, self::ERROR, $context);
    }
    
    /**
     * Log API request
     */
    public static function log_api_request($url, $data, $response = null) {
        $message = "API Request to: {$url}";
        
        $context = array(
            'url' => $url,
            'request_data' => is_array($data) ? json_encode($data) : $data,
            'type' => 'api_request'
        );
        
        if ($response !== null) {
            $context['response'] = is_array($response) ? json_encode($response) : $response;
        }
        
        self::info($message, $context);
    }
    
    /**
     * Log API response
     */
    public static function log_api_response($url, $response, $status_code = null) {
        $message = "API Response from: {$url}";
        
        $context = array(
            'url' => $url,
            'response' => is_array($response) ? json_encode($response) : $response,
            'type' => 'api_response'
        );
        
        if ($status_code !== null) {
            $context['status_code'] = $status_code;
        }
        
        self::info($message, $context);
    }
    
    /**
     * Log STK push request
     */
    public static function log_stk_push($phone, $amount, $order_id, $checkout_request_id = null) {
        $message = "STK Push initiated for Order #{$order_id}";
        
        $context = array(
            'phone' => $phone,
            'amount' => $amount,
            'order_id' => $order_id,
            'type' => 'stk_push'
        );
        
        if ($checkout_request_id) {
            $context['checkout_request_id'] = $checkout_request_id;
        }
        
        self::info($message, $context);
    }
    
    /**
     * Log callback received
     */
    public static function log_callback($callback_data) {
        $message = "M-Pesa callback received";
        
        $context = array(
            'callback_data' => is_array($callback_data) ? json_encode($callback_data) : $callback_data,
            'type' => 'callback'
        );
        
        self::info($message, $context);
    }
    
    /**
     * Log payment completion
     */
    public static function log_payment_completion($order_id, $mpesa_receipt, $amount) {
        $message = "Payment completed for Order #{$order_id} - Receipt: {$mpesa_receipt}";
        
        $context = array(
            'order_id' => $order_id,
            'mpesa_receipt' => $mpesa_receipt,
            'amount' => $amount,
            'type' => 'payment_completion'
        );
        
        self::info($message, $context);
    }
    
    /**
     * Log payment failure
     */
    public static function log_payment_failure($order_id, $reason) {
        $message = "Payment failed for Order #{$order_id}: {$reason}";
        
        $context = array(
            'order_id' => $order_id,
            'failure_reason' => $reason,
            'type' => 'payment_failure'
        );
        
        self::error($message, $context);
    }
    
    /**
     * Format log message
     */
    private static function format_message($message, $context = array()) {
        $timestamp = isset($context['timestamp']) ? $context['timestamp'] : current_time('mysql');
        $type = isset($context['type']) ? strtoupper($context['type']) : 'GENERAL';
        
        return "[{$timestamp}] [{$type}] {$message}";
    }
    
    /**
     * Check if debug mode is enabled
     */
    private static function is_debug_mode() {
        $settings = get_option('woocommerce_mpesa_settings', array());
        return isset($settings['debug_mode']) && $settings['debug_mode'] === 'yes';
    }
    
    /**
     * Log to custom file
     */
    private static function log_to_file($message, $level = self::INFO) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/mpesa-logs';
        
        // Create log directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Add .htaccess to protect log files
            $htaccess_content = "Order Deny,Allow\nDeny from all";
            file_put_contents($log_dir . '/.htaccess', $htaccess_content);
        }
        
        $log_file = $log_dir . '/mpesa-' . date('Y-m-d') . '.log';
        $log_entry = "[" . date('Y-m-d H:i:s') . "] [" . strtoupper($level) . "] " . $message . PHP_EOL;
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Rotate logs (keep only last 30 days)
        self::rotate_logs($log_dir);
    }
    
    /**
     * Rotate log files
     */
    private static function rotate_logs($log_dir) {
        $files = glob($log_dir . '/mpesa-*.log');
        $cutoff_date = strtotime('-30 days');
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_date) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get log entries
     */
    public static function get_logs($date = null, $limit = 100) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/mpesa-logs';
        
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $log_file = $log_dir . '/mpesa-' . $date . '.log';
        
        if (!file_exists($log_file)) {
            return array();
        }
        
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Get last $limit lines
        if (count($lines) > $limit) {
            $lines = array_slice($lines, -$limit);
        }
        
        return array_reverse($lines);
    }
    
    /**
     * Clear logs
     */
    public static function clear_logs($date = null) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/mpesa-logs';
        
        if ($date) {
            $log_file = $log_dir . '/mpesa-' . $date . '.log';
            if (file_exists($log_file)) {
                unlink($log_file);
                return true;
            }
        } else {
            // Clear all logs
            $files = glob($log_dir . '/mpesa-*.log');
            foreach ($files as $file) {
                unlink($file);
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Get log file size
     */
    public static function get_log_size($date = null) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/mpesa-logs';
        
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $log_file = $log_dir . '/mpesa-' . $date . '.log';
        
        if (file_exists($log_file)) {
            return filesize($log_file);
        }
        
        return 0;
    }
    
    /**
     * Get available log dates
     */
    public static function get_log_dates() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/mpesa-logs';
        
        $files = glob($log_dir . '/mpesa-*.log');
        $dates = array();
        
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/mpesa-(\d{4}-\d{2}-\d{2})\.log/', $filename, $matches)) {
                $dates[] = $matches[1];
            }
        }
        
        rsort($dates); // Sort newest first
        return $dates;
    }
    
    /**
     * Export logs as CSV
     */
    public static function export_logs_csv($date = null) {
        $logs = self::get_logs($date, 0); // Get all logs
        
        if (empty($logs)) {
            return false;
        }
        
        $csv_data = array();
        $csv_data[] = array('Timestamp', 'Level', 'Message'); // Header
        
        foreach ($logs as $log) {
            // Parse log entry
            if (preg_match('/\[([^\]]+)\] \[([^\]]+)\] (.+)/', $log, $matches)) {
                $csv_data[] = array(
                    $matches[1], // Timestamp
                    $matches[2], // Level
                    $matches[3]  // Message
                );
            }
        }
        
        // Generate CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        
        return $csv_content;
    }
}