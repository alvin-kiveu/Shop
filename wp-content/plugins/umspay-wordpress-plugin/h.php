<?php
defined('ABSPATH') or die('You cannot access this file directly.');

/*
Plugin Name: Ums Pay for WooCommerce
Plugin URI: https://umeskiasoftwares.com/
Description: Ums Pay for WooCommerce is a powerful payment gateway plugin that allows you to accept payments via M-Pesa STK Push directly to M-Pesa Till, Paybill, and Bank Accounts. Simplify transactions on your WooCommerce store with secure and seamless M-Pesa integration.
Author: Alvin Kiveu (UMESKIA SOFTWARES)
Author URI: https://github.com/alvin-kiveu/
Version: 2.1.0
Text Domain: umspay-woocommerce
Domain Path: /languages
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Tags: #WooCommerce #MPesa #PaymentGateway #MPesaIntegration #EcommercePayments #UMSPay #WordPressPlugin #MobilePayments #SecureTransactions #KenyaPayments #STKPush #MPesaTill #MPesaPaybill #BankPayments

Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.2
WC requires at least: 4.0
WC tested up to: 7.0

*/


add_action('before_woocommerce_init', function () {
  if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
      'cart_checkout_blocks',
      __FILE__,
      true
    );
  }
});


add_action('plugins_loaded', 'umspayplugin_init');



add_action('woocommerce_blocks_loaded', function () {
  if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
    return;
  }
  require_once('class.blocks-checkout.php');
  add_action(
    'woocommerce_blocks_payment_method_type_registration',
    function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
      $payment_method_registry->register(new WC_Umspay_Blocks);
    }
  );
});


function umspayplugin_init()
{
  if (!class_exists('WC_Payment_Gateway')) {
    return;
  }

  class WC_UMS_Pay_Gateway extends WC_Payment_Gateway
  {

    public function __construct()
    {
      session_start();
      $this->id = 'umspay';
      $this->icon = apply_filters('woocommerce_umspay_icon', plugin_dir_url(__FILE__) . 'umspay.png');
      $this->has_fields = true;
      $this->method_title = __('Umspay Gateway', 'umspay-woocommerce');
      $this->method_description = __('Umspay Recieve payment Using Buy goods and Paybill Number.', 'umspay-woocommerce');
      $this->init_form_fields();
      $this->init_settings();
      $this->title = $this->get_option('title');
      $this->description = $this->get_option('description');
      $this->enabled = $this->get_option('enabled');
      //SESSION DATA 
      $_SESSION['apikey'] = $this->get_option('apikey');
      $_SESSION['owneremail']   = $this->get_option('owneremail');
      //GET WEBSITE URL
      $_SESSION['webhook'] = get_site_url() . '/wc-api/wc_umspay_gateway/';
      //Finally, you need to add a save hook for your settings:
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
      add_action('woocommerce_receipt_umspay', array($this, 'receipt_page'));
      // You can also register a webhook here
      add_action('woocommerce_api_wc_umspay_gateway', array($this, 'webhook'));
      //CHECK PAYMENT STATUS
      add_action('woocommerce_receipt_thankyou', array($this, 'check_payment_status'));
    }

    public function init_form_fields()
    {

      $this->form_fields =  array(
        'enabled' => array(

          'title' => __('Enable/Disable', 'umspay-woocommerce'),

          'type' => 'checkbox',

          'label' => __('Enable or Disable umspay Payments', 'umspay-woocommerce'),

          'default' => 'no'
        ),
        'title' => array(
          'title' => __('Title', 'umspay-woocommerce'),

          'type' => 'text',

          'default'     => __('umspay', 'umspay-woocommerce'),

          'desc_tip' => true,

          'description' => __('This controls the title which the user sees during checkout.', 'umspay-woocommerce')
        ),
        'description' => array(
          'title' => __('Description', 'umspay-woocommerce'),

          'type' => 'textarea',

          'default' => __('Pay via umspay; peyment is processed securely via umspay.', 'umspay-woocommerce'),

          'desc_tip' => true,

          'description' => __('Payment method description that the customer will see on your checkout.', 'umspay-woocommerce')
        ),
        'api_key' => array(
          'title' => __('API Key', 'umspay-woocommerce'),

          'default'     => __('', 'umspay-woocommerce'),

          'type' => 'text',

          'desc_tip' => true,

          'description' => __('Get your API Key from your umspay Account.', 'umspay-woocommerce')
        ),
        'owneremail' => array(
          'title' => __('Owner Email', 'umspay-woocommerce'),

          'type' => 'text',

          'default'     => __('', 'umspay-woocommerce'),

          'desc_tip' => true,

          'description' => __('Get your Owner Email from your umspay Account.', 'umspay-woocommerce')
        ),
        'webhook' => array(
          'title' => __('Webhook', 'umspay-woocommerce'),

          'type' => 'text',

          'default'     => __($_SESSION['webhook'], 'umspay-woocommerce'),

          'desc_tip' => true,

          'description' => __('This is the url where you will update on your Ums Portal account', 'umspay-woocommerce'),

          'custom_attributes' => array(

            'onfocus' => 'this.select()',

            'readonly' => 'readonly',

            'data-copy' => true

          )
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
        $_SESSION['total'] = (int)$order->order_total;
        $tel = $order->billing_phone;
        $tel = str_replace("-", "", $tel);
        $tel = str_replace(array(' ', '<', '>', '&', '{', '}', '*', "+", '!', '@', '#', "$", '%', '^', '&'), "", $tel);
        $_SESSION['tel'] = "254" . substr($tel, -9);
        if (isset($_POST['makepayment'])) {
            $phonenumber = $_POST['tel'];
            $amount = $_POST['total'];
            $apikey = $_POST['apikey'];
            $order = $_POST['order'];
            $owneremail = $_POST['owneremail'];
            if ($apikey == '') {
                $_SESSION['response_status'] = "<div class='alert alert-danger' style='padding: 15px; margin-bottom: 20px; border: 1px solid #d9534f; border-radius: 4px; color: #a94442; background-color: #f2dede;'> Please fill the api key</div>";
                $_SESSION['response_status_expire'] = time() + 5;
            } else {
                if ($owneremail == '') {
                    $_SESSION['response_status'] = "<div class='alert alert-danger' style='padding: 15px; margin-bottom: 20px; border: 1px solid #d9534f; border-radius: 4px; color: #a94442; background-color: #f2dede;'> Please fill the owner email</div>";
                    $_SESSION['response_status_expire'] = time() + 5;
                } else {
                    if ($phonenumber == '') {
                        $_SESSION['response_status'] = "<div class='alert alert-danger' style='padding: 15px; margin-bottom: 20px; border: 1px solid #d9534f; border-radius: 4px; color: #a94442; background-color: #f2dede;'> Please fill the phone number</div>";
                        $_SESSION['response_status_expire'] = time() + 5;
                    } else {
                        if ($amount == '') {
                            $_SESSION['response_status'] = "<div class='alert alert-danger' style='padding: 15px; margin-bottom: 20px; border: 1px solid #d9534f; border-radius: 4px; color: #a94442; background-color: #f2dede;'> Please fill the amount</div>";
                            $_SESSION['response_status_expire'] = time() + 5;
                        } else {

                            $curl = curl_init();
                            curl_setopt_array($curl, array(
                                CURLOPT_URL => 'https://api.umeskiasoftwares.com/api/v1/intiatestk',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_POSTFIELDS => json_encode([
                                    'api_key' => $apikey,
                                    'email' => $owneremail,
                                    'amount' => $amount,
                                    'msisdn' => $phonenumber,
                                    'reference' => $order
                                ]),
                                CURLOPT_HTTPHEADER => array(
                                    'Content-Type: application/json'
                                ),
                            ));
                            $response = curl_exec($curl);
                            curl_close($curl);
                            $data = json_decode($response, true);
                            if (isset($data['success']) && $data['success'] == '200') {
                                //UPDATE ORDER STATUS TO PROCESSING
                                $order = wc_get_order($order);
                                $order->update_status('processing', __('Order processing by admin', 'woocommerce'));
                                $this->check_payment_status($order_id);
                                //TAKE TO CHECKOUT PAGE
                            } else {
                                $error = $data['errorMessage'];
                                $_SESSION['response_status'] = "<div class='alert alert-danger' style='padding: 15px; margin-bottom: 20px; border: 1px solid #d9534f; border-radius: 4px; color: #a94442; background-color: #f2dede;'>" . "Payment Failed. Please try again later. Error:  $error" . "</div>";
                                $_SESSION['response_status_expire'] = time() + 5;
                            }
                        }
                    }
                }
            }
        }
        echo "<h4>Umspay Payment Instructions</h4>";
        echo "
        <ol>
        <li>Click on the <b>Make Payment</b> button in order to initiate the M-PESA payment.</li>
        <li>Check your mobile phone for a prompt asking to enter M-PESA pin.</li>
        <li>Enter your <b>M-PESA PIN</b> and the amount specified on the notification will be deducted from your M-PESA account when you press send.</li>
        <li>When you enter the pin and click on send, you will receive an M-PESA payment confirmation message on your mobile phone.</li>
        <li>After receiving the M-PESA payment confirmation message please click on the <b>Check Order</b> button below to check the order and confirm the payment made.</li>
       </ol>
      ";
        echo "<br/>";
?>
        <style>
            .formpayment {
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 0 15px rgba(52, 152, 219, 0.5);
                font-size: 16px;
                line-height: 1.6;
            }

            .formpayment input[type="text"] {
                display: block;
                width: calc(100% - 20px);
                padding: 10px;
                margin-bottom: 15px;
                border-radius: 4px;
                box-sizing: border-box;
                font-size: 16px;
                line-height: 1.5;
                transition: border-color 0.3s ease;
            }


            .btn-data {
                display: flex;
                justify-content: space-between;
                width: 100%;
                margin-top: 20px;
            }

            .btn-data button {
                padding: 10px 15px;
                color: #fff;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }
        </style>
        <form method="POST" action="#" class="formpayment">

            <?php
            if (isset($_SESSION['response_status']) && isset($_SESSION['response_status_expire']) && time() < $_SESSION['response_status_expire']) {
                echo $_SESSION['response_status'];
            }
            ?>
            <input type='hidden' name="order" value="<?php echo $order_id; ?>" />
            <input type='text' name="tel" value="<?php echo $_SESSION['tel']; ?>" />
            <input type='hidden' name="total" value="<?php echo $_SESSION['total']; ?>" />
            <input type='hidden' name="apikey" value="<?php echo $_SESSION['apikey']; ?>" />
            <input type='hidden' name="owneremail" value="<?php echo $_SESSION['owneremail']; ?>" />
            <input type="hidden" value="" id="txid" />
            <div id="commonname"></div>
            <button type="submit" name="makepayment" id="pay_btn">Make Payment</button>
        </form>
<?php
        echo "<br/>";
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
        global $woocommerce;
        $order = new WC_Order($order_id);
        $order_status = $order->get_status();
        if ($order_status == "processing") {
            echo "<h4>Thank you for shopping with us</h4>";
            echo "<p>Your order has been received and is now being processed. Your order details are shown below for your reference:</p>";
            echo "<p>Payment Method: <b>TinyPesa</b></p>";
            echo "<p>Payment Status: " . $order_status . "</p>";
            echo "<p>Order ID: " . $order_id . "</p>";
            echo "<p>Order Total: " . $order->order_total . "</p>";
            echo "<p>Order Date: " . $order->order_date . "</p>";
            echo "<p>Order Telephone: " . $order->billing_phone . "</p>";
            //ADD A COMPLETE ORDER INSTUCTIONS
            echo "After Payment please click on the Check Order button below to complete the order.</b></p>";
            //TAKE TO ORDER PAGE WITH JS
            echo "<button onclick='window.location.href = \"" . $order->get_view_order_url() . "\";'>CHECK Order</button>";
        }
        echo "<br><br><br><br>";
    }


    //CALLBACK FUNCTION

    public function webhook()
    {
        global $woocommerce;
        header("Content-Type: application/json");
        $umspayStkCallbackResponse = file_get_contents('php://input');
        $logFile = "umspayMpesaStkResponse.json";
        $log = fopen($logFile, "a");
        fwrite($log, $umspayStkCallbackResponse);
        fclose($log);
        $callbackContent = json_decode($umspayStkCallbackResponse);
        $ResponseCode = $callbackContent->ResponseCode;
        if ($ResponseCode == 0) {
            $TransactionReceipt = $callbackContent->TransactionReceipt;
            $TransactionReference = $callbackContent->TransactionReference;
            //COMPLETE ORDER
            $order = wc_get_order($TransactionReference);
            // Set the order status to completed
            $order->update_status('completed', __('Order completed by admin', 'woocommerce'));
            // UPDATE TRANCTION ID
            $order->set_transaction_id($TransactionReceipt);
            $order->save();
        } else {
            $TransactionReference = $callbackContent->TransactionReference;
            //COMPLETE ORDER
            $order = wc_get_order($TransactionReference);
            // Set the order status to completed
            $order->update_status('failed', __('Order failed by admin', 'woocommerce'));
            $order->save();
        }
    }
  }


  add_filter('woocommerce_payment_gateways', 'add_umspay_gateway');
  function add_umspay_gateway($gateways)
  {
    $gateways[] = 'WC_UMS_Pay_Gateway';
    return $gateways;
  }
}
