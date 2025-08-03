<?php

/**
 * Plugin Name:       Ums Pay for WooCommerce
 * Plugin URI:        https://umspay.co.ke/
 * Description:       🚀 Ums Pay for WooCommerce enables fast and secure payment collection via M-Pesa STK Push directly to your M-Pesa Till, Paybill, or Bank Account. Ideal for Kenyan merchants using WooCommerce.
 * Version:           2.2.0
 * Requires at least: 5.0
 * Tested up to:      6.4
 * Requires PHP:      7.4
 * WC requires at least: 4.0
 * WC tested up to:   8.5
 * Author:            Alvin Kiveu (UMESKIA SOFTWARES)
 * Author URI:        https://github.com/alvin-kiveu/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       umspay-woocommerce
 * Domain Path:       /languages
 *
 * @package           UmsPayWooCommerce
 * @author            Alvin Kiveu
 * @copyright         2025 UMESKIA SOFTWARES
 */

// Exit if accessed directly.
defined('ABSPATH') or die('You cannot access this file directly.');

/**
 * Main UmsPay WooCommerce Gateway class
 *
 * Load plugin files, register hooks, and initialize the payment gateway.
 */

add_action('before_woocommerce_init', function () {
  if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
  }
});

add_action('plugins_loaded', 'umspayplugin_init');

function umspayplugin_init()
{
  if (!class_exists('WC_Payment_Gateway')) {
    return;
  }

  class WC_UMS_Pay_Gateway extends WC_Payment_Gateway
  {
    public $api_key;
    public $owner_email;
    public $webhook;
    public $account_id;

    public function __construct()
    {
      $this->id = 'umspay';
      $this->icon = apply_filters('woocommerce_umspay_icon', plugin_dir_url(__FILE__) . 'umspay_logo.png');
      $this->has_fields = true;
      $this->method_title = __('Umspay Gateway', 'umspay-woocommerce');
      $this->method_description = __('Umspay Receive payment Using Buy goods and Paybill Number.', 'umspay-woocommerce');

      // Initialize settings
      $this->init_form_fields();
      $this->init_settings();

      // Get settings
      $this->title = $this->get_option('title');
      $this->description = $this->get_option('description');
      $this->enabled = $this->get_option('enabled');
      $this->api_key = sanitize_text_field($this->get_option('api_key'));
      $this->owner_email = sanitize_email($this->get_option('owneremail'));
      $this->account_id = sanitize_text_field($this->get_option('account_id'));
      // Register hooks
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
      add_action('woocommerce_receipt_umspay', array($this, 'receipt_page'));
      add_action('woocommerce_api_wc_umspay_gateway', array($this, 'webhook'));
    }

    public function init_form_fields()
    {
      $this->form_fields = array(
        'enabled' => array(
          'title' => __('Enable/Disable', 'umspay-woocommerce'),
          'type' => 'checkbox',
          'label' => __('Enable Umspay Payments', 'umspay-woocommerce'),
          'default' => 'no',
        ),
        'title' => array(
          'title' => __('Title', 'umspay-woocommerce'),
          'type' => 'text',
          'default' => __('Umspay', 'umspay-woocommerce'),
          'desc_tip' => true,
        ),
        'description' => array(
          'title' => __('Description', 'umspay-woocommerce'),
          'type' => 'textarea',
          'default' => __('Pay via Umspay; payment is processed securely.', 'umspay-woocommerce'),
          'desc_tip' => true,
        ),
        'owneremail' => array(
          'title' => __('Owner Email', 'umspay-woocommerce'),
          'type' => 'email',
          'desc_tip' => true,
          'description' => __('Enter your Umspay account email.', 'umspay-woocommerce'),
        ),
        'api_key' => array(
          'title' => __('API Key', 'umspay-woocommerce'),
          'type' => 'text',
          'desc_tip' => true,
          'description' => __('Get your API Key from your Umspay Account.', 'umspay-woocommerce'),
        ),
        'account_id' => array(
          'title' => __('Account ID', 'umspay-woocommerce'),
          'type' => 'text',
          'desc_tip' => true,
          'description' => __('Get your Account ID from your Umspay Account.', 'umspay-woocommerce'),
        ),
        'webhook' => array(
          'title' => __('Webhook', 'umspay-woocommerce'),
          'type' => 'text',
          'default' => esc_url(get_site_url() . '/wc-api/wc_umspay_gateway/'),
          'desc_tip' => true,
          'custom_attributes' => array(
            'readonly' => 'readonly',
            'onfocus' => 'this.select()',
          ),
        ),
      );
    }

    public function receipt_page($order_id)
    {
      echo $this->umspay_generate_iframe($order_id);
    }




    public function umspay_generate_iframe($order_id)
    {
      global $woocommerce;
      $order = new WC_Order($order_id);

      // Sanitize and prepare data
      $order_total = (int)$order->get_total();
      $phone = preg_replace("/[^0-9]/", "", $order->get_billing_phone());
      $tel = "254" . substr($phone, -9);

      // Store order details in WooCommerce session
      WC()->session->set('umspay_order_total', $order_total);
      WC()->session->set('umspay_tel', $tel);

      if (isset($_POST['makepayment']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'umspay_payment_nonce')) {
        $required = ['apikey', 'owneremail', 'tel', 'total'];
        foreach ($required as $field) {
          if (empty($_POST[$field])) {
            wc_add_notice(__('Please fill all required fields.', 'umspay-woocommerce'), 'error');
            return;
          }
        }

        // Make API request
        $response = wp_remote_post('https://api.umspay.co.ke/api/v1/intiatestk', [
          'body'    => wp_json_encode([
            'api_key'   => sanitize_text_field($_POST['apikey']),
            'email'     => sanitize_email($_POST['owneremail']),
            'account_id' => sanitize_text_field($_POST['accountid']),
            'amount'    => intval($_POST['total']),
            'msisdn'    => sanitize_text_field($_POST['tel']),
            'reference' => sanitize_text_field($_POST['order'])
          ]),
          'headers' => ['Content-Type' => 'application/json'],
        ]);

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['success']) && $data['success'] == '200') {
          wc_get_order($order_id)->update_status('processing', __('Payment processing.', 'umspay-woocommerce'));
          $this->check_payment_status($order_id);
        } else {
          wc_add_notice(__('Payment Failed: ' . esc_html($data['errorMessage']), 'umspay-woocommerce'), 'error');
        }
      }

      // Payment form
      echo "<h4>Umspay Payment Instructions</h4>
        <ol>
            <li>Click <b>Make Payment</b> to initiate M-PESA.</li>
            <li>Enter your <b>M-PESA PIN</b> when prompted.</li>
            <li>Check for the confirmation message.</li>
            <li>Click <b>Check Order</b> to confirm payment.</li>
        </ol>";

      // Inline style optimization
      echo "<style>
            .formpayment { padding: 15px; border-radius: 8px; box-shadow: 0 0 15px rgba(52, 152, 219, 0.5); }
            .formpayment input, .formpayment button { width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
            .formpayment button { background-color: #3498db; color: #fff; border: none; cursor: pointer; }
        </style>";

      // Secure form with nonce
      echo "<form method='POST' action='#' class='formpayment'>";
      wp_nonce_field('umspay_payment_nonce');
      echo "
            <input type='hidden' name='order' value='" . esc_attr($order_id) . "' />
            <input type='text' name='tel' value='" . esc_attr($tel) . "' placeholder='Phone Number' required />
            <input type='hidden' name='total' value='" . esc_attr($order_total) . "' />
            <input type='hidden' name='apikey' value='" . esc_attr($this->api_key) . "' />
            <input type='hidden' name='owneremail' value='" . esc_attr($this->owner_email) . "' />
            <input type='hidden' name='accountid' value='" . esc_attr($this->account_id) . "' />
            <button type='submit' name='makepayment'>Make Payment</button>
        </form><br/>";
    }


    public function process_payment($order_id)
    {
      global $woocommerce;
      $order = new WC_Order($order_id);
      return array(
        'result' => 'success',
        'redirect' => $order->get_checkout_payment_url(true, true)
      );
    }

    public function check_payment_status($order_id)
    {
      $order = wc_get_order($order_id);
      if ($order->get_status() === "processing") {
        echo "<div style='text-align: left; padding: 20px; border: 1px solid #13b261; border-radius: 8px;'>
                    <h4 style='color: #13b261;'>Thank you for shopping with us!</h4>
                    <p>Your order is being processed. Here are your details:</p>
                    <ul style='list-style: none; padding: 0;'>
                        <li><b>Payment Method:</b> Ums Pay</li>
                        <li><b>Status:</b> Processing</li>
                        <li><b>Order ID:</b> #{$order_id}</li>
                        <li><b>Total:</b> {$order->get_formatted_order_total()}</li>
                        <li><b>Date:</b> {$order->get_date_created()->date('Y-m-d H:i')}</li>
                        <li><b>Phone:</b> {$order->get_billing_phone()}</li>
                    </ul>
                    <p>Please click the button below to check your order status:</p>
                    <button onclick='window.location.href=\"{$order->get_view_order_url()}\"' 
                        style='background-color: #13b261; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>
                        Check Order
                    </button>
                </div>";
      }
    }


    public function webhook()
    {
      global $woocommerce;
      header("Content-Type: application/json");
      $callback = json_decode(file_get_contents('php://input'));
      file_put_contents("umspayMpesaStkResponse.json", json_encode($callback) . PHP_EOL, FILE_APPEND);
      $order = wc_get_order($callback->TransactionReference);
      if ($callback->ResponseCode == 0) {
        $order->update_status('completed', 'Order completed');
        $order->set_transaction_id($callback->TransactionReceipt);
      } else {
        $order->update_status('failed', 'Order failed');
      }
      $order->save();
    }
  }

  // Register the payment gateway
  add_filter('woocommerce_payment_gateways', function ($gateways) {
    $gateways[] = 'WC_UMS_Pay_Gateway';
    return $gateways;
  });
}
