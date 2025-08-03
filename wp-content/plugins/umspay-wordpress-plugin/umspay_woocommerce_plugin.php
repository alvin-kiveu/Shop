<?php
/**
 * UmsPay WooCommerce Payment Gateway Plugin
 *
 * @package     UmsPayWooCommerce
 * @author      Alvin Kiveu (UMESKIA SOFTWARES)
 * @copyright   2025 UMESKIA SOFTWARES
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Ums Pay for WooCommerce
 * Plugin URI: https://umeskiasoftwares.com/
 * Description: Ums Pay for WooCommerce is a powerful payment gateway plugin that allows you to accept payments via M-Pesa STK Push directly to M-Pesa Till, Paybill, and Bank Accounts.
 * Author: Alvin Kiveu (UMESKIA SOFTWARES)
 * Author URI: https://github.com/alvin-kiveu/
 * Version: 2.2.0
 * Text Domain: umspay-woocommerce
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Define plugin constants
define('UMSPAY_WC_VERSION', '2.2.0');
define('UMSPAY_WC_PLUGIN_FILE', __FILE__);
define('UMSPAY_WC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UMSPAY_WC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UMSPAY_WC_API_BASE_URL', 'https://api.umspay.co.ke/api/v1/');

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'umspay_wc_missing_woocommerce_notice');
    return;
}

/**
 * Admin notice for missing WooCommerce
 */
function umspay_wc_missing_woocommerce_notice() {
    echo '<div class="notice notice-error"><p>' . 
         esc_html__('UmsPay for WooCommerce requires WooCommerce to be installed and active.', 'umspay-woocommerce') . 
         '</p></div>';
}

// Declare compatibility with WooCommerce features
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Initialize the plugin
add_action('plugins_loaded', 'umspay_wc_init', 11);

/**
 * Initialize the UmsPay WooCommerce Gateway
 */
function umspay_wc_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Include required files
    require_once UMSPAY_WC_PLUGIN_DIR . 'includes/class-umspay-security.php';
    
    // Load textdomain for translations
    load_plugin_textdomain('umspay-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    
    // Register the gateway with WooCommerce
    add_filter('woocommerce_payment_gateways', 'umspay_wc_add_gateway');
    
    // Add settings link on plugin page
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'umspay_wc_plugin_action_links');
    
    // Add admin notices for system checks
    add_action('admin_notices', 'umspay_wc_system_checks');
}

/**
 * Add the gateway to WooCommerce
 */
function umspay_wc_add_gateway($gateways) {
    $gateways[] = 'WC_UmsPay_Gateway';
    return $gateways;
}

/**
 * Add settings link to plugin actions
 */
function umspay_wc_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=umspay') . '">' . 
                    esc_html__('Settings', 'umspay-woocommerce') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * System compatibility checks
 */
function umspay_wc_system_checks() {
    if (!UmsPay_Utils::is_woocommerce_compatible()) {
        echo '<div class="notice notice-error"><p>' . 
             esc_html__('UmsPay requires WooCommerce 4.0 or higher.', 'umspay-woocommerce') . 
             '</p></div>';
    }
    
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        echo '<div class="notice notice-error"><p>' . 
             esc_html__('UmsPay requires PHP 7.4 or higher. Current version: ', 'umspay-woocommerce') . PHP_VERSION .
             '</p></div>';
    }
    
    if (!is_ssl() && !defined('WP_DEBUG') || !WP_DEBUG) {
        echo '<div class="notice notice-warning"><p>' . 
             esc_html__('UmsPay requires SSL/HTTPS for production use.', 'umspay-woocommerce') . 
             '</p></div>';
    }
}

// Create includes directory structure if it doesn't exist
if (!file_exists(UMSPAY_WC_PLUGIN_DIR . 'includes/')) {
    wp_mkdir_p(UMSPAY_WC_PLUGIN_DIR . 'includes/');
}

/**
 * Main UmsPay Gateway Class
 */
if (!class_exists('WC_UmsPay_Gateway')) {
    class WC_UmsPay_Gateway extends WC_Payment_Gateway {
        
        /**
         * Gateway configuration
         */
        public $api_key;
        public $owner_email;
        public $account_id;
        public $test_mode;
        public $logging;

        /**
         * Constructor
         */
        public function __construct() {
            $this->id                 = 'umspay';
            $this->icon               = apply_filters('woocommerce_umspay_icon', UMSPAY_WC_PLUGIN_URL . 'assets/images/umspay_logo.png');
            $this->has_fields         = true;
            $this->method_title       = __('UmsPay Gateway', 'umspay-woocommerce');
            $this->method_description = __('Accept payments via M-Pesa STK Push using UmsPay gateway.', 'umspay-woocommerce');
            $this->supports           = array(
                'products',
                'refunds'
            );

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title         = $this->get_option('title');
            $this->description   = $this->get_option('description');
            $this->enabled       = $this->get_option('enabled');
            $this->test_mode     = 'yes' === $this->get_option('test_mode');
            $this->api_key       = $this->test_mode ? $this->get_option('test_api_key') : $this->get_option('api_key');
            $this->owner_email   = $this->get_option('owner_email');
            $this->account_id    = $this->get_option('account_id');
            $this->logging       = 'yes' === $this->get_option('logging');

            // Register hooks
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_api_wc_umspay_gateway', array($this, 'webhook_handler'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            
            // Admin hooks
            add_action('admin_notices', array($this, 'admin_notices'));
        }

        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __('Enable/Disable', 'umspay-woocommerce'),
                    'type'    => 'checkbox',
                    'label'   => __('Enable UmsPay Payments', 'umspay-woocommerce'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title'       => __('Title', 'umspay-woocommerce'),
                    'type'        => 'text',
                    'description' => __('This controls the title displayed during checkout.', 'umspay-woocommerce'),
                    'default'     => __('M-Pesa via UmsPay', 'umspay-woocommerce'),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __('Description', 'umspay-woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Payment method description that the customer will see during checkout.', 'umspay-woocommerce'),
                    'default'     => __('Pay securely using M-Pesa via UmsPay gateway.', 'umspay-woocommerce'),
                    'desc_tip'    => true,
                ),
                'test_mode' => array(
                    'title'       => __('Test Mode', 'umspay-woocommerce'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable Test Mode', 'umspay-woocommerce'),
                    'default'     => 'yes',
                    'description' => __('Place the payment gateway in test mode using test API credentials.', 'umspay-woocommerce'),
                ),
                'owner_email' => array(
                    'title'       => __('Owner Email', 'umspay-woocommerce'),
                    'type'        => 'email',
                    'description' => __('Enter your UmsPay account email address.', 'umspay-woocommerce'),
                    'desc_tip'    => true,
                ),
                'api_key' => array(
                    'title'       => __('Live API Key', 'umspay-woocommerce'),
                    'type'        => 'password',
                    'description' => __('Get your Live API Key from your UmsPay account dashboard.', 'umspay-woocommerce'),
                    'desc_tip'    => true,
                ),
                'test_api_key' => array(
                    'title'       => __('Test API Key', 'umspay-woocommerce'),
                    'type'        => 'password',
                    'description' => __('Get your Test API Key from your UmsPay account dashboard.', 'umspay-woocommerce'),
                    'desc_tip'    => true,
                ),
                'account_id' => array(
                    'title'       => __('Account ID', 'umspay-woocommerce'),
                    'type'        => 'text',
                    'description' => __('Get your Account ID from your UmsPay account dashboard.', 'umspay-woocommerce'),
                    'desc_tip'    => true,
                ),
                'webhook_url' => array(
                    'title'       => __('Webhook URL', 'umspay-woocommerce'),
                    'type'        => 'text',
                    'description' => __('Copy this URL to your UmsPay account webhook settings.', 'umspay-woocommerce'),
                    'default'     => WC()->api_request_url('wc_umspay_gateway'),
                    'desc_tip'    => true,
                    'custom_attributes' => array(
                        'readonly' => 'readonly',
                    ),
                ),
                'logging' => array(
                    'title'       => __('Logging', 'umspay-woocommerce'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable Logging', 'umspay-woocommerce'),
                    'default'     => 'no',
                    'description' => __('Log UmsPay events for debugging purposes.', 'umspay-woocommerce'),
                ),
            );
        }

        /**
         * Admin notices for configuration
         */
        public function admin_notices() {
            if ($this->enabled === 'no') {
                return;
            }

            $required_fields = array('owner_email', 'api_key', 'account_id');
            $missing_fields = array();

            foreach ($required_fields as $field) {
                if (empty($this->get_option($field))) {
                    $missing_fields[] = $this->form_fields[$field]['title'];
                }
            }

            if (!empty($missing_fields)) {
                echo '<div class="notice notice-error"><p>' .
                     sprintf(
                         esc_html__('UmsPay is enabled but not configured. Please configure: %s', 'umspay-woocommerce'),
                         implode(', ', $missing_fields)
                     ) . '</p></div>';
            }
        }

        /**
         * Enqueue frontend scripts
         */
        public function enqueue_scripts() {
            if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
                return;
            }

            wp_enqueue_style(
                'umspay-checkout-style',
                UMSPAY_WC_PLUGIN_URL . 'assets/css/checkout.css',
                array(),
                UMSPAY_WC_VERSION
            );

            wp_enqueue_script(
                'umspay-checkout-script',
                UMSPAY_WC_PLUGIN_URL . 'assets/js/checkout.js',
                array('jquery'),
                UMSPAY_WC_VERSION,
                true
            );

            wp_localize_script('umspay-checkout-script', 'umspay_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('umspay_checkout_nonce'),
            ));
        }

        /**
         * Check if gateway is available for use
         */
        public function is_available() {
            if ($this->enabled === 'no') {
                return false;
            }

            if (!$this->api_key || !$this->owner_email || !$this->account_id) {
                return false;
            }

            return parent::is_available();
        }

        /**
         * Process the payment and return the result
         */
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                wc_add_notice(__('Order not found.', 'umspay-woocommerce'), 'error');
                return array('result' => 'fail');
            }

            // Mark as pending payment
            $order->update_status('pending', __('Awaiting UmsPay payment', 'umspay-woocommerce'));

            // Reduce stock levels
            wc_reduce_stock_levels($order_id);

            // Remove cart
            WC()->cart->empty_cart();

            // Return success and redirect to receipt page
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }

        /**
         * Receipt page
         */
        public function receipt_page($order_id) {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                wc_add_notice(__('Order not found.', 'umspay-woocommerce'), 'error');
                return;
            }

            echo '<p>' . esc_html__('Thank you for your order. Please complete the payment using M-Pesa.', 'umspay-woocommerce') . '</p>';
            echo $this->generate_payment_form($order);
        }

        /**
         * Generate payment form
         */
        private function generate_payment_form($order) {
            $order_id = $order->get_id();
            $order_total = $order->get_total();
            $phone = $this->format_phone_number($order->get_billing_phone());

            // Handle form submission
            if (isset($_POST['umspay_pay_now']) && wp_verify_nonce($_POST['umspay_nonce'], 'umspay_payment_' . $order_id)) {
                return $this->initiate_payment($order, $_POST);
            }

            // Display payment form
            ob_start();
            ?>
            <div class="umspay-payment-container">
                <div class="umspay-instructions">
                    <h4><?php esc_html_e('M-Pesa Payment Instructions', 'umspay-woocommerce'); ?></h4>
                    <ol>
                        <li><?php esc_html_e('Click "Pay Now" to initiate M-Pesa STK Push', 'umspay-woocommerce'); ?></li>
                        <li><?php esc_html_e('Enter your M-Pesa PIN when prompted on your phone', 'umspay-woocommerce'); ?></li>
                        <li><?php esc_html_e('Complete the transaction', 'umspay-woocommerce'); ?></li>
                        <li><?php esc_html_e('Wait for confirmation', 'umspay-woocommerce'); ?></li>
                    </ol>
                </div>

                <form method="post" class="umspay-payment-form">
                    <?php wp_nonce_field('umspay_payment_' . $order_id, 'umspay_nonce'); ?>
                    
                    <div class="form-row">
                        <label for="umspay_phone"><?php esc_html_e('M-Pesa Phone Number', 'umspay-woocommerce'); ?></label>
                        <input type="tel" 
                               id="umspay_phone" 
                               name="umspay_phone" 
                               value="<?php echo esc_attr($phone); ?>" 
                               placeholder="254XXXXXXXXX" 
                               pattern="254[0-9]{9}" 
                               required />
                        <small><?php esc_html_e('Format: 254XXXXXXXXX', 'umspay-woocommerce'); ?></small>
                    </div>

                    <div class="form-row">
                        <strong><?php esc_html_e('Amount:', 'umspay-woocommerce'); ?> <?php echo wc_price($order_total); ?></strong>
                    </div>

                    <div class="form-row">
                        <button type="submit" 
                                name="umspay_pay_now" 
                                class="button alt umspay-pay-button" 
                                id="umspay-pay-button">
                            <?php esc_html_e('Pay Now', 'umspay-woocommerce'); ?>
                        </button>
                    </div>
                </form>

                <div id="umspay-status" class="umspay-status" style="display: none;"></div>
            </div>
            
            <style>
                .umspay-payment-container {
                    max-width: 500px;
                    margin: 20px 0;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    background: #f9f9f9;
                }
                .umspay-instructions {
                    margin-bottom: 20px;
                    padding: 15px;
                    background: #e3f2fd;
                    border-radius: 5px;
                }
                .umspay-payment-form .form-row {
                    margin-bottom: 15px;
                }
                .umspay-payment-form label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                }
                .umspay-payment-form input[type="tel"] {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    font-size: 16px;
                }
                .umspay-pay-button {
                    width: 100%;
                    padding: 15px;
                    font-size: 16px;
                    background-color: #2196F3;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                }
                .umspay-pay-button:hover {
                    background-color: #1976D2;
                }
                .umspay-pay-button:disabled {
                    background-color: #ccc;
                    cursor: not-allowed;
                }
                .umspay-status {
                    margin-top: 20px;
                    padding: 15px;
                    border-radius: 5px;
                }
                .umspay-status.success {
                    background-color: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }
                .umspay-status.error {
                    background-color: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
                .umspay-status.processing {
                    background-color: #fff3cd;
                    color: #856404;
                    border: 1px solid #ffeaa7;
                }
            </style>
            <?php
            return ob_get_clean();
        }

        /**
         * Initiate payment with UmsPay API
         */
        private function initiate_payment($order, $form_data) {
            $phone = $this->format_phone_number(sanitize_text_field($form_data['umspay_phone']));
            
            if (!$this->validate_phone_number($phone)) {
                wc_add_notice(__('Invalid phone number format. Please use format 254XXXXXXXXX', 'umspay-woocommerce'), 'error');
                return $this->generate_payment_form($order);
            }

            $order_id = $order->get_id();
            $amount = intval($order->get_total());

            $payload = array(
                'api_key'   => $this->api_key,
                'email'     => $this->owner_email,
                'account_id' => $this->account_id,
                'amount'    => $amount,
                'msisdn'    => $phone,
                'reference' => (string) $order_id
            );

            $this->log('Initiating payment for order ' . $order_id . ' with payload: ' . wp_json_encode($payload));

            $response = $this->make_api_request('intiatestk', $payload);

            if (is_wp_error($response)) {
                $this->log('API request failed: ' . $response->get_error_message());
                wc_add_notice(__('Payment initiation failed. Please try again.', 'umspay-woocommerce'), 'error');
                return $this->generate_payment_form($order);
            }

            $response_data = json_decode(wp_remote_retrieve_body($response), true);
            $this->log('API response: ' . wp_json_encode($response_data));

            if (isset($response_data['success']) && $response_data['success'] == '200') {
                $order->add_order_note(__('UmsPay STK Push initiated successfully.', 'umspay-woocommerce'));
                $order->update_status('on-hold', __('Awaiting UmsPay payment confirmation.', 'umspay-woocommerce'));
                
                return $this->show_payment_status($order, 'processing', __('Payment request sent to your phone. Please complete the transaction.', 'umspay-woocommerce'));
            } else {
                $error_message = isset($response_data['errorMessage']) ? $response_data['errorMessage'] : __('Unknown error occurred', 'umspay-woocommerce');
                $this->log('Payment initiation failed: ' . $error_message);
                wc_add_notice(__('Payment failed: ', 'umspay-woocommerce') . esc_html($error_message), 'error');
                return $this->generate_payment_form($order);
            }
        }

        /**
         * Show payment status
         */
        private function show_payment_status($order, $status, $message) {
            $status_class = $status === 'success' ? 'success' : ($status === 'error' ? 'error' : 'processing');
            
            ob_start();
            ?>
            <div class="umspay-status <?php echo esc_attr($status_class); ?>">
                <h4><?php echo esc_html($message); ?></h4>
                <?php if ($status === 'processing'): ?>
                    <p><?php esc_html_e('Please check your phone and complete the M-Pesa transaction.', 'umspay-woocommerce'); ?></p>
                    <button onclick="window.location.reload();" class="button">
                        <?php esc_html_e('Check Payment Status', 'umspay-woocommerce'); ?>
                    </button>
                <?php elseif ($status === 'success'): ?>
                    <p><?php esc_html_e('Payment completed successfully!', 'umspay-woocommerce'); ?></p>
                    <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="button">
                        <?php esc_html_e('View Order', 'umspay-woocommerce'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php
            return ob_get_clean();
        }

        /**
         * Handle webhook notifications from UmsPay
         */
        public function webhook_handler() {
            try {
                // Rate limiting
                $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                if (!UmsPay_Security::check_rate_limit('webhook_' . $client_ip, 100, 3600)) {
                    $this->log('Webhook rate limit exceeded for IP: ' . $client_ip);
                    http_response_code(429);
                    exit('Rate limit exceeded');
                }

                $raw_body = file_get_contents('php://input');
                $this->log('Webhook received: ' . $raw_body);

                if (empty($raw_body)) {
                    $this->log('Empty webhook body received');
                    http_response_code(400);
                    exit('Bad Request');
                }

                $callback_data = json_decode($raw_body, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->log('Invalid JSON in webhook: ' . json_last_error_msg());
                    http_response_code(400);
                    exit('Invalid JSON');
                }

                // Sanitize the callback data
                $callback_data = UmsPay_Security::sanitize_api_response($callback_data);

                // Log the callback for debugging
                $this->log_webhook_response($callback_data);

                // Validate required fields
                if (!isset($callback_data['TransactionReference']) || !isset($callback_data['ResponseCode'])) {
                    $this->log('Missing required fields in webhook data');
                    http_response_code(400);
                    exit('Missing required fields');
                }

                $order_id = sanitize_text_field($callback_data['TransactionReference']);
                $order = UmsPay_Performance::get_order_optimized($order_id);

                if (!$order) {
                    $this->log('Order not found for ID: ' . $order_id);
                    http_response_code(404);
                    exit('Order not found');
                }

                // Process the payment result
                if (intval($callback_data['ResponseCode']) === 0) {
                    $this->process_successful_payment($order, $callback_data);
                } else {
                    $this->process_failed_payment($order, $callback_data);
                }

                http_response_code(200);
                exit('OK');

            } catch (Exception $e) {
                $this->log('Webhook error: ' . $e->getMessage());
                http_response_code(500);
                exit('Internal Server Error');
            }
        }

        /**
         * Process successful payment
         */
        private function process_successful_payment($order, $callback_data) {
            $transaction_id = isset($callback_data['TransactionReceipt']) ? 
                             sanitize_text_field($callback_data['TransactionReceipt']) : '';
            $amount = isset($callback_data['Amount']) ? 
                     floatval($callback_data['Amount']) : 0;

            // Check if payment was already processed
            if ($order->get_status() === 'completed') {
                $this->log('Payment already processed for order: ' . $order->get_id());
                return;
            }

            // Verify amount matches order total
            if ($amount > 0 && abs($amount - $order->get_total()) > 0.01) {
                $order->add_order_note(
                    sprintf(
                        __('UmsPay payment amount mismatch. Expected: %s, Received: %s', 'umspay-woocommerce'),
                        wc_price($order->get_total()),
                        wc_price($amount)
                    )
                );
                $this->log('Amount mismatch for order ' . $order->get_id() . '. Expected: ' . $order->get_total() . ', Received: ' . $amount);
            }

            // Set transaction ID
            if ($transaction_id) {
                $order->set_transaction_id($transaction_id);
            }

            // Complete the payment
            $order->payment_complete($transaction_id);
            
            $order->add_order_note(
                sprintf(
                    __('UmsPay payment completed. Transaction ID: %s', 'umspay-woocommerce'),
                    $transaction_id
                )
            );

            $this->log('Payment completed for order: ' . $order->get_id() . ', Transaction ID: ' . $transaction_id);
        }

        /**
         * Process failed payment
         */
        private function process_failed_payment($order, $callback_data) {
            $error_message = isset($callback_data['ResultDesc']) ? 
                           sanitize_text_field($callback_data['ResultDesc']) : 
                           __('Payment failed', 'umspay-woocommerce');

            $order->update_status('failed', sprintf(__('UmsPay payment failed: %s', 'umspay-woocommerce'), $error_message));
            
            $this->log('Payment failed for order: ' . $order->get_id() . ', Reason: ' . $error_message);
        }

        /**
         * Log webhook responses for debugging
         */
        private function log_webhook_response($data) {
            if (!$this->logging) {
                return;
            }

            $log_file = UMSPAY_WC_PLUGIN_DIR . 'logs/webhook-responses.json';
            $log_dir = dirname($log_file);

            if (!file_exists($log_dir)) {
                wp_mkdir_p($log_dir);
            }

            $log_entry = array(
                'timestamp' => current_time('mysql'),
                'data' => $data
            );

            file_put_contents($log_file, wp_json_encode($log_entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        /**
         * Make API request to UmsPay
         */
        private function make_api_request($endpoint, $payload) {
            $url = UMSPAY_WC_API_BASE_URL . $endpoint;
            
            $args = array(
                'body'        => wp_json_encode($payload),
                'headers'     => array(
                    'Content-Type' => 'application/json',
                    'User-Agent'   => 'UmsPay-WooCommerce/' . UMSPAY_WC_VERSION
                ),
                'timeout'     => 30,
                'redirection' => 5,
                'blocking'    => true,
                'httpversion' => '1.1',
                'sslverify'   => true,
            );

            return wp_remote_post($url, $args);
        }

        /**
         * Format phone number to international format
         */
        private function format_phone_number($phone) {
            return UmsPay_Security::validate_phone_number($phone);
        }

        /**
         * Validate phone number format
         */
        private function validate_phone_number($phone) {
            return UmsPay_Security::validate_phone_number($phone) !== false;
        }

        /**
         * Process refund
         */
        public function process_refund($order_id, $amount = null, $reason = '') {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                return new WP_Error('invalid_order', __('Order not found.', 'umspay-woocommerce'));
            }

            $transaction_id = $order->get_transaction_id();
            
            if (!$transaction_id) {
                return new WP_Error('no_transaction_id', __('No transaction ID found for this order.', 'umspay-woocommerce'));
            }

            // Log refund attempt
            $this->log("Refund requested for order {$order_id}, amount: {$amount}, reason: {$reason}");
            
            // Add note to order
            $order->add_order_note(
                sprintf(
                    __('Refund of %s requested via UmsPay. Reason: %s', 'umspay-woocommerce'),
                    wc_price($amount),
                    $reason
                )
            );

            // Note: Actual refund implementation would depend on UmsPay API capabilities
            return new WP_Error('not_implemented', __('Automatic refunds are not yet supported. Please process refund manually through UmsPay dashboard.', 'umspay-woocommerce'));
        }

        /**
         * Log messages for debugging
         */
        private function log($message) {
            if (!$this->logging) {
                return;
            }

            if (function_exists('wc_get_logger')) {
                $logger = wc_get_logger();
                $logger->info($message, array('source' => 'umspay'));
            } else {
                error_log('UmsPay: ' . $message);
            }
        }

        /**
         * Get icon for payment method
         */
        public function get_icon() {
            $icon_html = '';
            $icon = $this->get_option('icon');
            
            if ($icon) {
                $icon_html .= '<img src="' . esc_url($icon) . '" alt="' . esc_attr($this->get_title()) . '" style="max-height: 32px;" />';
            }
            
            return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
        }
    }
}
