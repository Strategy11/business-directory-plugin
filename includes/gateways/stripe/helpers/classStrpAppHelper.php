<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class WPBDPStrpAppHelper {

	/**
	 * @var string
	 */
	public static $gateway_id = 'stripe';

	/**
	 * @return string
	 */
	public static function plugin_path() {
		return WPBDP_PATH . 'includes/gateways/stripe/';
	}

	/**
	 * @return string
	 *
	 * @psalm-return 'live'|'test'
	 */
	public static function active_mode() {
		return wpbdp_get_option( 'payments-test-mode' ) ? 'test' : 'live';
	}

	/**
	 * Add education about Stripe fees.
	 *
	 * @return void
	 */
	public static function fee_education( $medium = 'tip' ) {
		$license_type = self::get_license_type();

		if ( in_array( $license_type, array( 'elite', 'pro' ), true ) ) {
			return;
		}

		WPBDP_Admin_Education::show_tip_message(
			esc_html__( 'Pay as you go pricing: 3% fee per-transaction + Stripe fees.', 'business-directory-plugin' )
			. '<a href="' . esc_url( wpbdp_admin_upgrade_link( 'stripe-fees' ) ) . '" target="_blank" rel="noopener" style="margin-left: auto;">' . esc_html__( 'Upgrade to save on fees.', 'business-directory-plugin' ) . '</a>'
		);

		// Add some padding below the tip so it isn't right against the Stripe buttons.
		echo '<br>';
	}

	/**
	 * @since x.x
	 *
	 * @return string
	 */
	public static function get_license_type() {
		$free_license_type = 'lite';
		$stripe_product_id = 1934;

		include_once dirname( WPBDP_PLUGIN_FILE ) . '/includes/class-modules-api.php';

		$api    = new WPBDP_Modules_API();
		$addons = $api->get_api_info();

		if ( ! isset( $addons[ $stripe_product_id ] ) ) {
			return $free_license_type;
		}

		$addon = $addons[ $stripe_product_id ];
		if ( ! isset( $addon['type'] ) ) {
			return $free_license_type;
		}

		return isset( $addon['type'] ) ? strtolower( $addon['type'] ) : $free_license_type;
	}

	/**
	 * @since x.x
	 *
	 * @return string
	 */
	public static function customer_meta_name() {
		$test_mode = wpbdp_get_option( 'payments-test-mode' );
		return '_wpbdp_stripe_customer_id' . ( $test_mode ? '_test' : '' );
	}
}
