<?php
/**
 * M-Pesa Blocks Integration for WooCommerce
 * 
 * @package MpesaWooCommerce
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * M-Pesa payment method integration
 */
final class WC_Mpesa_Blocks_Support extends Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {

    /**
     * The gateway instance.
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     */
    protected $name = 'mpesa';

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_mpesa_settings', []);
        $gateways       = WC()->payment_gateways->payment_gateways();
        $this->gateway  = $gateways[$this->name];
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     */
    public function is_active() {
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     */
    public function get_payment_method_script_handles() {
        $script_path       = '/assets/js/frontend/blocks.js';
        $script_asset_path = plugin_dir_path(__DIR__) . 'assets/js/frontend/blocks.asset.php';
        $script_asset      = file_exists($script_asset_path)
            ? require($script_asset_path)
            : array(
                'dependencies' => array(),
                'version'      => '2.1.0'
            );
        $script_url        = plugin_dir_url(__DIR__) . $script_path;

        wp_register_script(
            'wc-mpesa-payments-blocks',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wc-mpesa-payments-blocks', 'mpesa-woocommerce', plugin_dir_path(__DIR__) . 'languages');
        }

        return ['wc-mpesa-payments-blocks'];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     */
    public function get_payment_method_data() {
        return [
            'title'                => $this->gateway->title,
            'description'          => $this->gateway->description,
            'supports'             => array_filter($this->gateway->supports, [$this->gateway, 'supports']),
            'logo_url'             => $this->gateway->icon,
            'business_shortcode'   => $this->gateway->business_shortcode,
            'environment'          => $this->gateway->environment,
        ];
    }
}
