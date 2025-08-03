<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Umspay_Blocks extends AbstractPaymentMethodType {

	private $gateway;
	protected $name = 'umspay';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_umspay_settings', [] );
		$this->gateway = new WC_UMS_Pay_Gateway();
	}

	public function is_active() {
		return $this->get_setting( 'enabled' ) === 'yes';
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-umspay-blocks-integration',
			plugins_url( 'checkout.js', __FILE__ ),
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			false,
			true
		);
		if( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-umspay-blocks-integration');
		}
		return [ 'wc-umspay-blocks-integration' ];
	}

	public function get_payment_method_data() {
		return [
			'title' => $this->gateway->title,
			'description' => $this->gateway->description,
		];
	}

}