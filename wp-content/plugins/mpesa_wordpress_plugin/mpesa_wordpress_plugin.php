<?php
/**
 * Plugin Name:       M-Pesa for WooCommerce
 * Plugin URI:        https://yourwebsite.com/mpesa-woocommerce/
 * Description:       Accept M-Pesa payments directly in your WooCommerce store. Supports STK Push for seamless mobile payments.
 * Version:           2.0.0
 * Requires at least: 5.0
 * Tested up to:      6.4
 * Requires PHP:      7.4
 * WC requires at least: 4.0
 * WC tested up to:   8.5
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mpesa-woocommerce
 * Domain Path:       /languages
 *
 * @package           MpesaWooCommerce
 * @author            Your Name
 * @copyright         2025 Your Company
 */

defined('ABSPATH') or exit;

// Add compatibility with WooCommerce Blocks
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

add_action('plugins_loaded', 'mpesa_wordpress_plugin_init');

// Register block support for WooCommerce Blocks
add_action('woocommerce_blocks_loaded', 'mpesa_wordpress_plugin_block_support');

function mpesa_wordpress_plugin_block_support() {
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-mpesa-blocks-support.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $payment_method_registry->register(new WC_Mpesa_Blocks_Support);
            }
        );
    }
}

function mpesa_wordpress_plugin_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Mpesa_Gateway extends WC_Payment_Gateway {
        public $consumer_key;
        public $consumer_secret;
        public $business_shortcode;
        public $passkey;
        public $environment;
        public $transaction_type;

        public function __construct() {
            $this->id = 'mpesa';
            $this->icon = apply_filters('woocommerce_mpesa_icon', plugin_dir_url(__FILE__) . 'assets/mpesa-logo.png');
            $this->has_fields = true;
            $this->method_title = __('M-Pesa', 'mpesa-woocommerce');
            $this->method_description = __('Accept M-Pesa payments via STK Push in your WooCommerce store.', 'mpesa-woocommerce');
            
            $this->supports = array(
                'products',
                'refunds'
            );

            $this->init_form_fields();
            $this->init_settings();

            // Get settings
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->consumer_key = sanitize_text_field($this->get_option('consumer_key'));
            $this->consumer_secret = sanitize_text_field($this->get_option('consumer_secret'));
            $this->business_shortcode = sanitize_text_field($this->get_option('business_shortcode'));
            $this->passkey = sanitize_text_field($this->get_option('passkey'));
            $this->environment = $this->get_option('environment');
            $this->transaction_type = $this->get_option('transaction_type', 'CustomerPayBillOnline');
            $this->instructions = $this->get_option('instructions');

            // Hooks
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            add_action('woocommerce_api_wc_mpesa_gateway', array($this, 'handle_mpesa_callback'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __('Enable/Disable', 'mpesa-woocommerce'),
                    'type'    => 'checkbox',
                    'label'   => __('Enable M-Pesa Payments', 'mpesa-woocommerce'),
                    'default' => 'no',
                ),
                'title' => array(
                    'title'       => __('Title', 'mpesa-woocommerce'),
                    'type'        => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'mpesa-woocommerce'),
                    'default'     => __('M-Pesa', 'mpesa-woocommerce'),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __('Description', 'mpesa-woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'mpesa-woocommerce'),
                    'default'     => __('Pay securely via M-Pesa STK Push. You will receive a prompt on your phone to complete payment.', 'mpesa-woocommerce'),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __('Instructions', 'mpesa-woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Instructions that will be added to the thank you page and emails.', 'mpesa-woocommerce'),
                    'default'     => __('Thank you for paying with M-Pesa. Your order will be processed once payment is confirmed.', 'mpesa-woocommerce'),
                    'desc_tip'    => true,
                ),
                'environment' => array(
                    'title'       => __('Environment', 'mpesa-woocommerce'),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __('Select whether to use the sandbox or production environment.', 'mpesa-woocommerce'),
                    'default'     => 'sandbox',
                    'desc_tip'    => true,
                    'options'     => array(
                        'sandbox'    => __('Sandbox', 'mpesa-woocommerce'),
                        'production' => __('Production', 'mpesa-woocommerce')
                    ),
                ),
                'consumer_key' => array(
                    'title'       => __('Consumer Key', 'mpesa-woocommerce'),
                    'type'        => 'text',
                    'description' => __('Your M-Pesa API Consumer Key.', 'mpesa-woocommerce'),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'consumer_secret' => array(
                    'title'       => __('Consumer Secret', 'mpesa-woocommerce'),
                    'type'        => 'password',
                    'description' => __('Your M-Pesa API Consumer Secret.', 'mpesa-woocommerce'),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'business_shortcode' => array(
                    'title'       => __('Business Shortcode', 'mpesa-woocommerce'),
                    'type'        => 'text',
                    'description' => __('Your M-Pesa Paybill or Buy Goods Till Number.', 'mpesa-woocommerce'),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'passkey' => array(
                    'title'       => __('Passkey', 'mpesa-woocommerce'),
                    'type'        => 'password',
                    'description' => __('Your M-Pesa API Passkey.', 'mpesa-woocommerce'),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'transaction_type' => array(
                    'title'       => __('Transaction Type', 'mpesa-woocommerce'),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __('Select the transaction type for M-Pesa payments.', 'mpesa-woocommerce'),
                    'default'     => 'CustomerPayBillOnline',
                    'desc_tip'    => true,
                    'options'     => array(
                        'CustomerPayBillOnline' => __('Paybill', 'mpesa-woocommerce'),
                        'CustomerBuyGoodsOnline' => __('Buy Goods', 'mpesa-woocommerce')
                    ),
                ),
                'callback_url' => array(
                    'title'       => __('Callback URL', 'mpesa-woocommerce'),
                    'type'        => 'text',
                    'description' => __('The URL where M-Pesa will send payment notifications.', 'mpesa-woocommerce'),
                    'default'     => esc_url(home_url('/wc-api/wc_mpesa_gateway/')),
                    'custom_attributes' => array(
                        'readonly' => 'readonly',
                    ),
                    'desc_tip'    => true,
                ),
            );
        }

        public function payment_fields() {
            if ($this->description) {
                echo wpautop(wptexturize($this->description));
            }
            
            echo '<div class="form-row form-row-wide">';
            echo '<label for="mpesa_phone">' . esc_html__('M-Pesa Phone Number', 'mpesa-woocommerce') . ' <span class="required">*</span></label>';
            echo '<input type="tel" class="input-text" name="mpesa_phone" id="mpesa_phone" placeholder="e.g. 254700000000" pattern="254[0-9]{9}" required />';
            echo '<small>' . esc_html__('Enter your M-Pesa registered phone number in format 254XXXXXXXXX', 'mpesa-woocommerce') . '</small>';
            echo '</div>';
            
            // Add payment instructions
            echo '<div class="mpesa-instructions">';
            echo '<h4>' . esc_html__('How to pay with M-Pesa:', 'mpesa-woocommerce') . '</h4>';
            echo '<ol>';
            echo '<li>' . esc_html__('Enter your M-Pesa phone number above', 'mpesa-woocommerce') . '</li>';
            echo '<li>' . esc_html__('Click "Place Order" button', 'mpesa-woocommerce') . '</li>';
            echo '<li>' . esc_html__('Check your phone for an M-Pesa STK Push prompt', 'mpesa-woocommerce') . '</li>';
            echo '<li>' . esc_html__('Enter your M-Pesa PIN to complete payment', 'mpesa-woocommerce') . '</li>';
            echo '</ol>';
            echo '</div>';
            
            // Add some CSS
            echo '<style>
                .mpesa-instructions {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                    margin-top: 15px;
                    border-left: 4px solid #007cba;
                }
                .mpesa-instructions h4 {
                    margin-top: 0;
                    color: #007cba;
                }
                .mpesa-instructions ol {
                    margin-bottom: 0;
                }
            </style>';
        }

        public function validate_fields() {
            if (empty($_POST['mpesa_phone'])) {
                wc_add_notice(__('Please enter your M-Pesa phone number', 'mpesa-woocommerce'), 'error');
                return false;
            }
            
            $phone = sanitize_text_field($_POST['mpesa_phone']);
            $formatted_phone = $this->format_phone_number($phone);
            
            if (!$this->validate_phone_number($formatted_phone)) {
                wc_add_notice(__('Please enter a valid M-Pesa phone number in format 254XXXXXXXXX', 'mpesa-woocommerce'), 'error');
                return false;
            }
            
            return true;
        }

        private function format_phone_number($phone) {
            // Remove all non-numeric characters
            $phone = preg_replace('/[^0-9]/', '', $phone);
            
            // Convert to 254 format if in local format
            if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
                return '254' . $phone;
            } elseif (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
                return '254' . substr($phone, 1);
            }
            
            return $phone;
        }

        private function validate_phone_number($phone) {
            return preg_match('/^254[0-9]{9}$/', $phone);
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                wc_add_notice(__('Order not found.', 'mpesa-woocommerce'), 'error');
                return false;
            }
            
            // Get and validate phone number
            $phone = sanitize_text_field($_POST['mpesa_phone']);
            $formatted_phone = $this->format_phone_number($phone);
            
            if (!$this->validate_phone_number($formatted_phone)) {
                wc_add_notice(__('Invalid M-Pesa phone number format. Please use format 254XXXXXXXXX', 'mpesa-woocommerce'), 'error');
                return false;
            }
            
            // Generate transaction reference
            $transaction_reference = 'WC' . $order_id . '_' . time();
            
            // Initiate STK Push
            $stk_push_response = $this->initiate_stk_push($order, $formatted_phone, $transaction_reference);
            
            if ($stk_push_response['success']) {
                // Mark as on-hold (we're awaiting the payment)
                $order->update_status('on-hold', __('Awaiting M-Pesa payment confirmation.', 'mpesa-woocommerce'));
                
                // Store transaction reference in order meta
                $order->update_meta_data('_mpesa_transaction_reference', $transaction_reference);
                $order->update_meta_data('_mpesa_phone_number', $formatted_phone);
                $order->save();
                
                // Reduce stock levels
                wc_reduce_stock_levels($order_id);
                
                // Remove cart
                WC()->cart->empty_cart();
                
                // Return thankyou redirect
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } else {
                wc_add_notice(__('Payment error: ', 'mpesa-woocommerce') . $stk_push_response['message'], 'error');
                return false;
            }
        }

        private function initiate_stk_push($order, $phone, $transaction_reference) {
            $api_url = ($this->environment === 'sandbox') ? 
                'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' : 
                'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            
            $timestamp = date('YmdHis');
            $password = base64_encode($this->business_shortcode . $this->passkey . $timestamp);
            
            $payload = array(
                'BusinessShortCode' => $this->business_shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => $this->transaction_type,
                'Amount' => (int) $order->get_total(),
                'PartyA' => $phone,
                'PartyB' => $this->business_shortcode,
                'PhoneNumber' => $phone,
                'CallBackURL' => $this->get_option('callback_url'),
                'AccountReference' => 'Order #' . $order->get_id(),
                'TransactionDesc' => 'Payment for Order #' . $order->get_id(),
            );
            
            // First get access token
            $access_token = $this->get_access_token();
            
            if (is_wp_error($access_token)) {
                return array(
                    'success' => false,
                    'message' => $access_token->get_error_message()
                );
            }
            
            $args = array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($payload),
                'timeout' => 30
            );
            
            $response = wp_remote_post($api_url, $args);
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => $response->get_error_message()
                );
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($body['errorCode'])) {
                return array(
                    'success' => false,
                    'message' => $body['errorMessage'] ?? __('M-Pesa API error occurred', 'mpesa-woocommerce')
                );
            }
            
            if (isset($body['ResponseCode']) && $body['ResponseCode'] == '0') {
                $order->add_order_note(sprintf(
                    __('M-Pesa STK Push initiated successfully. Checkout Request ID: %s', 'mpesa-woocommerce'),
                    $body['CheckoutRequestID']
                ));
                
                // Store checkout request ID
                $order->update_meta_data('_mpesa_checkout_request_id', $body['CheckoutRequestID']);
                $order->save();
                
                return array(
                    'success' => true,
                    'message' => __('STK Push sent successfully. Check your phone for the payment prompt.', 'mpesa-woocommerce')
                );
            }
            
            return array(
                'success' => false,
                'message' => __('Failed to initiate M-Pesa payment. Please try again.', 'mpesa-woocommerce')
            );
        }

        private function get_access_token() {
            $api_url = ($this->environment === 'sandbox') ? 
                'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' : 
                'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
            
            $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);
            
            $args = array(
                'headers' => array(
                    'Authorization' => 'Basic ' . $credentials
                ),
                'timeout' => 30
            );
            
            $response = wp_remote_get($api_url, $args);
            
            if (is_wp_error($response)) {
                return $response;
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($body['access_token'])) {
                return $body['access_token'];
            }
            
            return new WP_Error('mpesa_auth_failed', __('Failed to authenticate with M-Pesa API', 'mpesa-woocommerce'));
        }

        public function thankyou_page($order_id) {
            $order = wc_get_order($order_id);
            
            if ($order->get_payment_method() === $this->id) {
                echo '<div class="mpesa-thankyou">';
                echo '<h3>' . esc_html__('Your M-Pesa Payment', 'mpesa-woocommerce') . '</h3>';
                
                if ($order->has_status('on-hold')) {
                    echo '<p>' . esc_html__('We are waiting for confirmation of your M-Pesa payment. This may take a few moments.', 'mpesa-woocommerce') . '</p>';
                } elseif ($order->has_status('processing') || $order->has_status('completed')) {
                    echo '<p>' . esc_html__('Thank you! Your M-Pesa payment has been received.', 'mpesa-woocommerce') . '</p>';
                }
                
                echo '</div>';
            }
        }

        public function handle_mpesa_callback() {
            $callback_data = json_decode(file_get_contents('php://input'), true);
            
            // Log the callback for debugging
            $this->log_callback($callback_data);
            
            if (!isset($callback_data['Body']['stkCallback'])) {
                status_header(400);
                wp_send_json_error('Invalid callback data');
            }
            
            $callback = $callback_data['Body']['stkCallback'];
            $checkout_request_id = $callback['CheckoutRequestID'];
            $result_code = $callback['ResultCode'];
            
            // Find order by checkout request ID
            $order = $this->find_order_by_checkout_request_id($checkout_request_id);
            
            if (!$order) {
                status_header(404);
                wp_send_json_error('Order not found');
            }
            
            // Process based on result code
            if ($result_code == 0) {
                // Success
                $metadata = $callback['CallbackMetadata']['Item'];
                $amount = $metadata[0]['Value'];
                $mpesa_receipt_number = $metadata[1]['Value'];
                $transaction_date = $metadata[3]['Value'];
                $phone_number = $metadata[4]['Value'];
                
                // Update order status
                $order->payment_complete($mpesa_receipt_number);
                $order->update_status('processing', __('M-Pesa payment received.', 'mpesa-woocommerce'));
                
                // Add order notes
                $order->add_order_note(sprintf(
                    __('M-Pesa payment confirmed. Receipt Number: %s, Amount: %s, Phone: %s', 'mpesa-woocommerce'),
                    $mpesa_receipt_number,
                    $amount,
                    $phone_number
                ));
                
                // Store transaction details
                $order->update_meta_data('_mpesa_receipt_number', $mpesa_receipt_number);
                $order->update_meta_data('_mpesa_transaction_date', $transaction_date);
                $order->update_meta_data('_mpesa_amount_paid', $amount);
                $order->save();
                
                status_header(200);
                wp_send_json_success('Callback processed successfully');
            } else {
                // Failure
                $error_message = $callback['ResultDesc'] ?? __('M-Pesa payment failed', 'mpesa-woocommerce');
                $order->update_status('failed', $error_message);
                
                status_header(200);
                wp_send_json_success('Callback processed (payment failed)');
            }
        }

        private function find_order_by_checkout_request_id($checkout_request_id) {
            $orders = wc_get_orders(array(
                'limit' => 1,
                'meta_key' => '_mpesa_checkout_request_id',
                'meta_value' => $checkout_request_id,
                'return' => 'ids'
            ));
            
            if (!empty($orders)) {
                return wc_get_order($orders[0]);
            }
            
            return false;
        }

        private function log_callback($data) {
            $log_file = WP_CONTENT_DIR . '/mpesa-callback.log';
            $log_entry = '[' . date('Y-m-d H:i:s') . '] ' . json_encode($data) . PHP_EOL;
            file_put_contents($log_file, $log_entry, FILE_APPEND);
        }
    }

    // Register the payment gateway
    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'WC_Mpesa_Gateway';
        return $gateways;
    });
}