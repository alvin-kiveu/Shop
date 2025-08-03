<?php
class WC_Gateway_Mpesa_Blocks extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType
{

    protected $name = 'mpesa';

    public function initialize()
    {
        // Set up required parameters
    }

    public function get_payment_method_script_handles()
    {
        return ['wc-mpesa-blocks'];
    }

    public function is_active()
    {
        return true; // Enable the gateway in blocks
    }

    public function enqueue_payment_scripts()
    {
        if (is_checkout()) {
            wp_enqueue_script(
                'wc-mpesa-blocks',
                plugins_url('assets/js/mpesa-blocks.js', __FILE__),
                ['wc-blocks-registry', 'wc-settings'],
                time(),
                true
            );
        }
    }
}

// Fix: Call `enqueue_payment_scripts()` on the correct class
add_action('wp_enqueue_scripts', function () {
    $mpesa_gateway = new WC_Gateway_Mpesa_Blocks();
    $mpesa_gateway->enqueue_payment_scripts();
});
