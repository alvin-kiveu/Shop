<?php
defined('ABSPATH') or die('You cannot access this file directly.');

/*
Plugin Name: TinyPesa for WooCommerce
Plugin URI: https://tinypesa.com/
Description: TinyPesa for WooCommerce is a powerful payment gateway plugin that allows you to accept payments via M-Pesa STK Push using TinyPesa API.
Author: Alvin Kiveu
Author URI: https://github.com/alvin-kiveu/
Version: 1.0.0
Text Domain: tinypesa-woocommerce
Domain Path: /languages
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
WC requires at least: 4.0
WC tested up to: 8.5
*/

// Declare compatibility with WooCommerce Cart and Checkout Blocks
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Initialize the plugin
add_action('plugins_loaded', 'tinypesa_plugin_init');

function tinypesa_plugin_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_TinyPesa_Gateway extends WC_Payment_Gateway
    {
        public $api_key;
        public $username;
        public $base_url;
        public $webhook_url;

        public function __construct()
        {
            $this->id = 'tinypesa';
            $this->icon = apply_filters('woocommerce_tinypesa_icon', plugin_dir_url(__FILE__) . 'assets/tinypesa_logo.svg');
            $this->has_fields = true;
            $this->method_title = __('TinyPesa Gateway', 'tinypesa-woocommerce');
            $this->method_description = __('Accept M-Pesa payments using TinyPesa STK Push API.', 'tinypesa-woocommerce');

            // Initialize settings
            $this->init_form_fields();
            $this->init_settings();

            // Get settings
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->api_key = $this->get_option('api_key');
            $this->username = $this->get_option('username');
            $this->base_url = $this->get_option('base_url');
            $this->webhook_url = $this->get_option('webhook_url');

            // Add admin options hook
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Add webhook handler
            add_action('woocommerce_api_tinypesa_webhook', array($this, 'handle_webhook'));

            // Add scripts
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        }

        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'tinypesa-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable TinyPesa Payment Gateway', 'tinypesa-woocommerce'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Method Title', 'tinypesa-woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'tinypesa-woocommerce'),
                    'default' => __('M-Pesa Payment', 'tinypesa-woocommerce'),
                    'desc_tip' => true
                ),
                'description' => array(
                    'title' => __('Method Description', 'tinypesa-woocommerce'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'tinypesa-woocommerce'),
                    'default' => __('Pay securely using M-Pesa STK Push.', 'tinypesa-woocommerce'),
                    'desc_tip' => true
                ),
                'api_key' => array(
                    'title' => __('API Key', 'tinypesa-woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your TinyPesa API Key.', 'tinypesa-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'username' => array(
                    'title' => __('Username', 'tinypesa-woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your TinyPesa username.', 'tinypesa-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'base_url' => array(
                    'title' => __('Base URL', 'tinypesa-woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your TinyPesa base URL.', 'tinypesa-woocommerce'),
                    'default' => 'https://your-tinypesa-url.com',
                    'desc_tip' => true
                ),
                'webhook_url' => array(
                    'title' => __('Webhook URL', 'tinypesa-woocommerce'),
                    'type' => 'text',
                    'description' => __('Copy this URL to your TinyPesa webhook settings.', 'tinypesa-woocommerce'),
                    'default' => home_url('/wc-api/tinypesa_webhook'),
                    'custom_attributes' => array('readonly' => 'readonly'),
                    'desc_tip' => true
                )
            );
        }

        /**
         * Process the payment and return the result
         */
        public function process_payment($order_id)
        {
            global $woocommerce;

            $order = wc_get_order($order_id);

            // Get customer phone number from the custom field
            $phone = isset($_POST['tinypesa_phone']) ? sanitize_text_field($_POST['tinypesa_phone']) : $order->get_billing_phone();
            $amount = $order->get_total();

            // Clean phone number
            $phone = $this->clean_phone_number($phone);

            if (!$phone) {
                wc_add_notice(__('Please provide a valid phone number.', 'tinypesa-woocommerce'), 'error');
                return array(
                    'result' => 'failure',
                    'redirect' => ''
                );
            }

            // Make STK Push request
            $response = $this->initiate_stk_push($phone, $amount, $order_id);

            if ($response && isset($response['success']) && $response['success']) {
                // Mark order as pending payment
                $order->update_status('pending', __('Awaiting M-Pesa payment confirmation.', 'tinypesa-woocommerce'));

                // Store TinyPesa request ID
                if (isset($response['request_id'])) {
                    $order->add_meta_data('_tinypesa_request_id', $response['request_id']);
                    $order->save();
                }

                // Reduce stock levels
                wc_reduce_stock_levels($order_id);

                // Remove cart
                $woocommerce->cart->empty_cart();

                // Return thank you redirect
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } else {
                $error_message = isset($response['message']) ? $response['message'] : __('Payment failed. Please try again.', 'tinypesa-woocommerce');
                wc_add_notice($error_message, 'error');
                return array(
                    'result' => 'failure',
                    'redirect' => ''
                );
            }
        }

        /**
         * Initiate STK Push
         */
        private function initiate_stk_push($phone, $amount, $order_id)
        {
            $endpoint = rtrim($this->base_url, '/') . '/express/initialize/';

            $headers = array(
                'Accept' => 'application/json',
                'Apikey' => $this->api_key,
                'Content-Type' => 'application/json'
            );

            $body = array(
                'amount' => (string)$amount,
                'msisdn' => $phone,
                'account_no' => 'ORDER-' . $order_id
            );

            $args = array(
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode($body),
                'timeout' => 30,
                'sslverify' => false
            );

            // Add username as GET parameter
            $endpoint .= '?username=' . urlencode($this->username);

            $response = wp_remote_post($endpoint, $args);

            if (is_wp_error($response)) {
                error_log('TinyPesa API Error: ' . $response->get_error_message());
                return false;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            // Log the response for debugging
            error_log('TinyPesa Response: ' . $body);

            return $data;
        }

        /**
         * Clean phone number
         */
        private function clean_phone_number($phone)
        {
            // Remove all non-numeric characters
            $phone = preg_replace('/[^0-9]/', '', $phone);

            // Convert to international format
            if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
                $phone = '254' . substr($phone, 1);
            } elseif (strlen($phone) == 9) {
                $phone = '254' . $phone;
            }

            // Validate phone number
            if (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
                return $phone;
            }

            return false;
        }

        /**
         * Handle webhook callback
         */
        public function handle_webhook()
        {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            error_log('TinyPesa Webhook Data: ' . $json);

            if (!$data) {
                http_response_code(400);
                exit('Invalid JSON');
            }

            // Extract required fields
            $request_id = isset($data['TinyPesaID']) ? $data['TinyPesaID'] : '';
            $external_reference = isset($data['ExternalReference']) ? $data['ExternalReference'] : '';
            $amount = isset($data['Amount']) ? $data['Amount'] : '';
            $phone = isset($data['Msisdn']) ? $data['Msisdn'] : '';
            $transaction_code = isset($data['TransactionCode']) ? $data['TransactionCode'] : '';
            $status = isset($data['Status']) ? $data['Status'] : '';

            // Extract order ID from external reference
            if (strpos($external_reference, 'ORDER-') === 0) {
                $order_id = (int)str_replace('ORDER-', '', $external_reference);
                $order = wc_get_order($order_id);

                if ($order) {
                    if ($status === 'Success' || $status === 'success') {
                        // Payment successful
                        $order->payment_complete($transaction_code);
                        $order->add_order_note(sprintf(
                            __('M-Pesa payment completed. Transaction Code: %s, Phone: %s', 'tinypesa-woocommerce'),
                            $transaction_code,
                            $phone
                        ));
                    } else {
                        // Payment failed
                        $order->update_status('failed', sprintf(
                            __('M-Pesa payment failed. Status: %s', 'tinypesa-woocommerce'),
                            $status
                        ));
                    }

                    // Store webhook data
                    $order->add_meta_data('_tinypesa_transaction_code', $transaction_code);
                    $order->add_meta_data('_tinypesa_phone', $phone);
                    $order->add_meta_data('_tinypesa_status', $status);
                    $order->save();
                }
            }

            http_response_code(200);
            exit('OK');
        }

        /**
         * Enqueue scripts
         */
        public function enqueue_scripts()
        {
            if (is_checkout() && $this->enabled === 'yes') {
                wp_enqueue_style('tinypesa-styles', plugin_dir_url(__FILE__) . 'assets/styles.css', array(), '1.0.0');
                wp_enqueue_script('tinypesa-checkout', plugin_dir_url(__FILE__) . 'assets/checkout.js', array('jquery'), '1.0.0', true);
                wp_localize_script('tinypesa-checkout', 'tinypesa_params', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('tinypesa_nonce')
                ));
            }
        }

        /**
         * Payment fields
         */
        public function payment_fields()
        {
            if ($this->description) {
                echo wpautop(wptexturize($this->description));
            }
            ?>
            <div class="tinypesa-payment-fields">
                <p class="form-row form-row-wide">
                    <label for="tinypesa_phone"><?php _e('M-Pesa Phone Number', 'tinypesa-woocommerce'); ?> <span class="required">*</span></label>
                    <input id="tinypesa_phone" name="tinypesa_phone" type="tel" placeholder="0712345678" />
                    <small><?php _e('Enter your M-Pesa registered phone number', 'tinypesa-woocommerce'); ?></small>
                </p>
            </div>
            <?php
        }

        /**
         * Validate payment fields
         */
        public function validate_fields()
        {
            if (empty($_POST['tinypesa_phone'])) {
                wc_add_notice(__('M-Pesa phone number is required.', 'tinypesa-woocommerce'), 'error');
                return false;
            }

            $phone = $this->clean_phone_number($_POST['tinypesa_phone']);
            if (!$phone) {
                wc_add_notice(__('Please enter a valid M-Pesa phone number.', 'tinypesa-woocommerce'), 'error');
                return false;
            }

            return true;
        }
    }

    /**
     * Add the Gateway to WooCommerce
     */
    function woocommerce_add_tinypesa_gateway($methods)
    {
        $methods[] = 'WC_TinyPesa_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_tinypesa_gateway');
}

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'tinypesa_settings_link');

function tinypesa_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=tinypesa">' . __('Settings', 'tinypesa-woocommerce') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Add admin notices for missing WooCommerce
add_action('admin_notices', 'tinypesa_admin_notice_missing_woocommerce');

function tinypesa_admin_notice_missing_woocommerce()
{
    if (!class_exists('WooCommerce')) {
        $class = 'notice notice-error';
        $message = __('TinyPesa for WooCommerce requires WooCommerce to be installed and active.', 'tinypesa-woocommerce');
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }
}
