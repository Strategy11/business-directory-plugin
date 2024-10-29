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
}
