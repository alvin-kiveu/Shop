<?php

/**
 * @package Mpesa for WooCommerce
 * @author AlvinKiveu [ UMESKIA SOFTWARES ]
 * @version 1.0.0
 *
 * Plugin Name: Mpesa for WooCommerce
 * Plugin URI: https://alvinkiveu.com/scripts/mpesa-for-woocommerce/
 * Description: Mpesa for WooCommerce is a powerful payment gateway plugin that allows you to accept Mpesa payments on your WooCommerce store.
 * Author: Alvin Kiveu
 * Author URI: https://alvinkiveu.com/
 * Version: 1.1.0
 * Text Domain: mpesa-woocommerce
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// require_once plugin_dir_path(__FILE__) . 'class-wc-gateway-mpesa-blocks.php';

// add_action('woocommerce_blocks_loaded', 'register_mpesa_gateway_for_blocks');

// function register_mpesa_gateway_for_blocks() {
//     if (!class_exists('\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry')) {
//        echo 'Mpesa for WooCommerce requires WooCommerce Blocks to be installed and active.';
//        return;
//     }

//     // Fetch the WooCommerce Blocks PaymentMethodRegistry
//     $registry = \Automattic\WooCommerce\Blocks\Package::container()->get(
//         \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry::class
//     );

//     if ($registry) {
//         $registry->register(new WC_Gateway_Mpesa_Blocks());
//     }
// }



define('WCM_VER', '2.3.6');
if (!defined('WCM_PLUGIN_FILE')) {
    define('WCM_PLUGIN_FILE', __FILE__);
}

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

register_activation_hook(__FILE__, function () {
    set_transient('wc-mpesa-activation-notice', true, 5);
});

add_action('admin_notices', function () {
    /* Check transient, if available display notice */
    if (get_transient('wc-mpesa-activation-notice')) {
        echo '<div class="updated notice is-dismissible">
            <p>Thank you for installing the M-Pesa for WooCommerce plugin! <strong>You are awesome</strong>.</p>
            <p>
            <a class="button" href="'.admin_url('admin.php?page=wc_mpesa_about').'">About M-Pesa for WooCommerce</a>
            <a class="button button-primary" href="'.admin_url('admin.php?page=wc_mpesa_go_live').'">How to Go Live</a>
            </p>
        </div>';
        /* Delete transient, only display this notice once. */
        delete_transient('wc-mpesa-activation-notice');
    }
});

/**
 * Initialize all plugin features and utilities
 */
new Osen\Woocommerce\Initialize;
new Osen\Woocommerce\Utilities;

/**
 * Initialize metaboxes for C2B API
 */
new Osen\Woocommerce\Post\Metaboxes\C2B;

/**
 * Initialize our admin menus (submenus under WooCommerce)
 */
new Osen\Woocommerce\Admin\Menu;

