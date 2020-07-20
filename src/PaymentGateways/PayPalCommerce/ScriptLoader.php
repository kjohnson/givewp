<?php

namespace Give\PaymentGateways\PayPalCommerce;

use Give_Admin_Settings;
use PayPalCheckoutSdk\Core\AccessTokenRequest;

/**
 * Class ScriptLoader
 * @package Give\PaymentGateways\PayPalCommerce
 *
 * @since 2.8.0
 */
class ScriptLoader {
	/**
	 * Setup hooks
	 *
	 * @since 2.8.0
	 */
	public function boot() {
		add_action( 'admin_enqueue_scripts', [ $this, 'loadAdminScripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'loadPublicScripts' ] );
	}

	/**
	 * Load admin scripts
	 *
	 * @since 2.8.0
	 */
	public function loadAdminScripts() {
		if ( Give_Admin_Settings::is_setting_page( 'gateway', 'paypal' ) ) {
			wp_enqueue_script(
				'give-paypal-partner-js',
				$this->getPartnerJsUrl(),
				[],
				null,
				true
			);

			wp_localize_script(
				'give-paypal-partner-js',
				'givePayPalCommerce',
				[
					'translations' => [
						'confirmPaypalAccountDisconnection' => esc_html__( 'Confirm PayPal account disconnection', 'give' ),
						'disconnectPayPalAccount' => esc_html__( 'Do you want to disconnect PayPal account?', 'give' ),
					],
				]
			);

			$script = <<<EOT
				function givePayPalOnBoardedCallback(authCode, sharedId) {
					const query = '&authCode=' + authCode + '&sharedId=' + sharedId;
					fetch( ajaxurl + '?action=give_paypal_commerce_user_on_boarded' + query )
						.then(function(res){ return res.json() })
						.then(function(res) {
							if ( true !== res.success ) {
								alert("Something went wrong!");
								}
							}
						);
				}
EOT;

			wp_add_inline_script(
				'give-paypal-partner-js',
				$script
			);
		}
	}

	/**
	 * Load public scripts.
	 *
	 * @since 2.8.0
	 */
	public function loadPublicScripts() {
		/* @var MerchantDetail $merchant */
		$merchant = give( MerchantDetail::class );

		wp_enqueue_script(
			'give-paypal-sdk',
			sprintf(
				'https://www.paypal.com/sdk/js?components=%1$s&client-id=%2$s&merchant-id=%3$s&currency=%4$s&intent=capture',
				'hosted-fields',
				$merchant->clientId,
				$merchant->merchantIdInPayPal,
				give_get_currency()
			),
			[ 'give' ],
			null,
			false
		);

		add_filter( 'script_loader_tag', [ $this, 'addAttributesToPayPalSdkScript' ], 10, 2 );
	}

	/**
	 * Add attributes to PayPal sdk.
	 *
	 * @param $tag
	 * @param $handle
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function addAttributesToPayPalSdkScript( $tag, $handle ) {
		if ( 'give-paypal-sdk' !== $handle ) {
			return $tag;
		}

		/* @var MerchantDetail $merchant */
		$merchant = give( MerchantDetail::class );

		$tag = str_replace(
			'src=',
			sprintf(
				'data-partner-attribution-id="%1$s" data-client-token="%2$s" src=',
				$merchant->merchantIdInPayPal,
				give( PayPalClient::class )->getToken()
			),
			$tag
		);

		return $tag;
	}

	/**
	 * Get PayPal partner js url.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	private function getPartnerJsUrl() {
		return sprintf(
			'https://www.%1$spaypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js',
			$this->getPayPalScriptFileUrlPrefix()
		);
	}

	/**
	 * Get PayPal script file url prefix.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	private function getPayPalScriptFileUrlPrefix() {
		return 'sandbox' === give( PayPalClient::class )->mode ? 'sandbox.' : '';
	}
}
