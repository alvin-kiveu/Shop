<?php
/**
 * UmsPay Security and Performance Utilities
 * 
 * @package UmsPayWooCommerce
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * UmsPay Security Helper Class
 */
class UmsPay_Security {
    
    /**
     * Validate and sanitize phone number
     * 
     * @param string $phone Raw phone number input
     * @return string|false Formatted phone number or false if invalid
     */
    public static function validate_phone_number($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle different formats
        if (strlen($phone) === 9) {
            $phone = '254' . $phone;
        } elseif (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        }
        
        // Validate final format
        if (preg_match('/^254[0-9]{9}$/', $phone)) {
            return $phone;
        }
        
        return false;
    }
    
    /**
     * Sanitize API response data
     * 
     * @param array $data Raw API response
     * @return array Sanitized data
     */
    public static function sanitize_api_response($data) {
        if (!is_array($data)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_string($value)) {
                $sanitized[$key] = sanitize_text_field($value);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = floatval($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitize_api_response($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Verify webhook signature (if implemented by UmsPay)
     * 
     * @param string $payload Webhook payload
     * @param string $signature Provided signature
     * @param string $secret Webhook secret
     * @return bool True if signature is valid
     */
    public static function verify_webhook_signature($payload, $signature, $secret) {
        if (empty($payload) || empty($signature) || empty($secret)) {
            return false;
        }
        
        $expected_signature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Rate limiting for API requests
     * 
     * @param string $key Unique identifier for rate limiting
     * @param int $limit Maximum requests allowed
     * @param int $window Time window in seconds
     * @return bool True if request is allowed
     */
    public static function check_rate_limit($key, $limit = 60, $window = 3600) {
        $cache_key = 'umspay_rate_limit_' . md5($key);
        $requests = get_transient($cache_key);
        
        if ($requests === false) {
            $requests = array();
        }
        
        $now = time();
        $requests = array_filter($requests, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        if (count($requests) >= $limit) {
            return false;
        }
        
        $requests[] = $now;
        set_transient($cache_key, $requests, $window);
        
        return true;
    }
}

/**
 * UmsPay Performance Helper Class
 */
class UmsPay_Performance {
    
    /**
     * Cache API responses
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $expiry Cache expiry in seconds
     */
    public static function cache_set($key, $data, $expiry = 300) {
        $cache_key = 'umspay_cache_' . md5($key);
        set_transient($cache_key, $data, $expiry);
    }
    
    /**
     * Get cached data
     * 
     * @param string $key Cache key
     * @return mixed|false Cached data or false if not found
     */
    public static function cache_get($key) {
        $cache_key = 'umspay_cache_' . md5($key);
        return get_transient($cache_key);
    }
    
    /**
     * Clear cache for specific key or all UmsPay cache
     * 
     * @param string|null $key Specific key to clear or null for all
     */
    public static function cache_clear($key = null) {
        if ($key !== null) {
            $cache_key = 'umspay_cache_' . md5($key);
            delete_transient($cache_key);
        } else {
            // Clear all UmsPay cache entries
            global $wpdb;
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_umspay_cache_%'
                )
            );
        }
    }
    
    /**
     * Optimize database queries for order lookups
     * 
     * @param int $order_id Order ID
     * @return WC_Order|false Order object or false if not found
     */
    public static function get_order_optimized($order_id) {
        static $order_cache = array();
        
        if (isset($order_cache[$order_id])) {
            return $order_cache[$order_id];
        }
        
        $order = wc_get_order($order_id);
        
        if ($order) {
            $order_cache[$order_id] = $order;
            
            // Limit cache size
            if (count($order_cache) > 50) {
                $order_cache = array_slice($order_cache, -25, null, true);
            }
        }
        
        return $order;
    }
    
    /**
     * Minify and compress log data
     * 
     * @param array $log_data Log data to compress
     * @return string Compressed log data
     */
    public static function compress_log_data($log_data) {
        $json = wp_json_encode($log_data);
        
        if (function_exists('gzcompress')) {
            return base64_encode(gzcompress($json, 6));
        }
        
        return $json;
    }
    
    /**
     * Decompress log data
     * 
     * @param string $compressed_data Compressed log data
     * @return array|false Decompressed data or false on failure
     */
    public static function decompress_log_data($compressed_data) {
        if (function_exists('gzuncompress')) {
            $json = @gzuncompress(base64_decode($compressed_data));
            if ($json !== false) {
                return json_decode($json, true);
            }
        }
        
        return json_decode($compressed_data, true);
    }
}

/**
 * UmsPay Utility Functions
 */
class UmsPay_Utils {
    
    /**
     * Format currency for display
     * 
     * @param float $amount Amount to format
     * @param string $currency Currency code
     * @return string Formatted amount
     */
    public static function format_currency($amount, $currency = 'KES') {
        return number_format($amount, 2) . ' ' . $currency;
    }
    
    /**
     * Generate unique transaction reference
     * 
     * @param int $order_id Order ID
     * @return string Unique reference
     */
    public static function generate_transaction_reference($order_id) {
        return 'UMSPAY_' . $order_id . '_' . time() . '_' . wp_rand(1000, 9999);
    }
    
    /**
     * Validate order amount
     * 
     * @param float $amount Amount to validate
     * @return bool True if amount is valid
     */
    public static function validate_amount($amount) {
        return is_numeric($amount) && $amount > 0 && $amount <= 999999;
    }
    
    /**
     * Get plugin version
     * 
     * @return string Plugin version
     */
    public static function get_plugin_version() {
        return defined('UMSPAY_WC_VERSION') ? UMSPAY_WC_VERSION : '2.2.0';
    }
    
    /**
     * Check if WooCommerce is active and compatible
     * 
     * @return bool True if compatible
     */
    public static function is_woocommerce_compatible() {
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        $wc_version = WC()->version;
        $min_version = '4.0.0';
        
        return version_compare($wc_version, $min_version, '>=');
    }
    
    /**
     * Get system information for debugging
     * 
     * @return array System information
     */
    public static function get_system_info() {
        global $wp_version;
        
        return array(
            'wordpress_version' => $wp_version,
            'woocommerce_version' => class_exists('WooCommerce') ? WC()->version : 'Not installed',
            'php_version' => PHP_VERSION,
            'plugin_version' => self::get_plugin_version(),
            'ssl_enabled' => is_ssl(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        );
    }
}

/**
 * Initialize security and performance features
 */
function umspay_init_security_performance() {
    // Add security headers
    add_action('send_headers', function() {
        if (is_admin() || (isset($_GET['wc-api']) && $_GET['wc-api'] === 'wc_umspay_gateway')) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
        }
    });
    
    // Clear expired cache periodically
    add_action('wp_scheduled_delete', function() {
        UmsPay_Performance::cache_clear();
    });
}

// Initialize on plugin load
add_action('plugins_loaded', 'umspay_init_security_performance', 5);
