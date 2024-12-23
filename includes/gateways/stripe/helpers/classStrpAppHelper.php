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
	 * Return the active mode to use for Stripe based on the global Payments settings.
	 *
	 * @since 6.4.9
	 *
	 * @return string Ether 'live' or 'test'.
	 *
	 * @psalm-return 'live'|'test'
	 */
	public static function active_mode() {
		return wpbdp_get_option( 'payments-test-mode' ) ? 'test' : 'live';
	}

	/**
	 * Add education about Stripe fees.
	 *
	 * @since 6.4.9
	 *
	 * @return void
	 */
	public static function fee_education() {
		if ( ! self::license_includes_stripe_fees() ) {
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
	 * Licenses without access to the Stripe module will have Stripe fees applied.
	 *
	 * @since 6.4.9
	 *
	 * @return bool True if the user does not have access to the Stripe module.
	 */
	public static function license_includes_stripe_fees() {
		include_once dirname( WPBDP_PLUGIN_FILE ) . '/includes/admin/helpers/class-modules-api.php';

		$api               = new WPBDP_Modules_API();
		$addons            = $api->get_api_info();
		$stripe_product_id = 1934;

		return ! isset( $addons[ $stripe_product_id ] ) || empty( $addons[ $stripe_product_id ]['package'] );
	}

	/**
	 * Get the key used for customer meta based on the current mode.
	 *
	 * @since 6.4.9
	 *
	 * @return string Either _wpbdp_stripe_customer_id or _wpbdp_stripe_customer_id_test.
	 */
	public static function customer_meta_name() {
		$test_mode = wpbdp_get_option( 'payments-test-mode' );
		return '_wpbdp_stripe_customer_id' . ( $test_mode ? '_test' : '' );
	}
}
