<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class WPBDPStrpHooksController {

	/**
	 * @return void
	 */
	public static function load_hooks() {
		add_action( 'init', 'WPBDPStrpConnectHelper::check_for_stripe_connect_webhooks' );

		if ( is_admin() && defined( 'DOING_AJAX' ) ) {
			self::load_ajax_hooks();
		}
	}

	/**
	 * @return void
	 */
	private static function load_ajax_hooks() {
		$wpbdp_strp_events_controller = new WPBDPStrpEventsController();
		add_action( 'wp_ajax_nopriv_wpbdp_strp_process_events', array( &$wpbdp_strp_events_controller, 'process_connect_events' ) );
		add_action( 'wp_ajax_wpbdp_strp_process_events', array( &$wpbdp_strp_events_controller, 'process_connect_events' ) );

		// Stripe Lite
		add_action( 'wp_ajax_nopriv_wpbdp_strp_lite_verify', 'WPBDPStrpConnectHelper::verify' );
	}
}
