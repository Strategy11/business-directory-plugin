<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class WPBDPStrpAppHelper {

	/**
	 * @var WPBDPStrpSettings|null
	 */
	private static $settings;

	/**
	 * @return string
	 */
	public static function plugin_path() {
		return WPBDP_PATH . 'includes/gateways/stripe/';
	}

	/**
	 * @param string $function
	 * @param array  ...$params
	 * @return mixed
	 */
	public static function call_stripe_helper_class( $function, ...$params ) {
		if ( self::should_use_stripe_connect() ) {
			if ( is_callable( "WPBDPStrpConnectApiHelper::$function" ) ) {
				return WPBDPStrpConnectApiHelper::$function( ...$params );
			}
		}
		return false;
	}

	/**
	 * @return bool true if we're using connect (versus the legacy integration).
	 */
	public static function should_use_stripe_connect() {
		return WPBDPStrpConnectHelper::stripe_connect_is_setup();
	}

	/**
	 * @return bool true if either connect or the legacy integration is set up.
	 */
	public static function stripe_is_configured() {
		return self::call_stripe_helper_class( 'initialize_api' );
	}

	/**
	 * If test mode is running, save the id somewhere else
	 *
	 * @return string
	 */
	public static function get_customer_id_meta_name() {
		$meta_name = '_wpbdp_stripe_customer_id';
		if ( 'test' === self::active_mode() ) {
			$meta_name .= '_test';
		}
		return $meta_name;
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
		$license_type = ''; // TODO
		if ( in_array( $license_type, array( 'elite', 'business' ), true ) ) {
			return;
		}

		/*
		show_tip(
			array(
				'link'  => array(
					'content' => 'stripe-fee',
					'medium'  => $medium,
				),
				'tip'   => 'Pay as you go pricing: 3% fee per-transaction + Stripe fees.',
				'call'  => __( 'Upgrade to save on fees.', 'business-directory-plugin' ),
				'class' => 'wpbdp-light-tip',
			),
			'p'
		);
		*/
	}

	/**
	 * Set a user id for current payment if a user is logged in.
	 *
	 * @return int
	 */
	public static function get_user_id_for_current_payment() {
		$user_id = 0;
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}
		return $user_id;
	}
}
