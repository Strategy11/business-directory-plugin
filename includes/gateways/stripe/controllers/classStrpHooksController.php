<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class WPBDPStrpHooksController {

	/**
	 * @return void
	 */
	public static function load_hooks() {
		// Actions.
		add_action( 'wpbdp_entry_form', 'WPBDPStrpAuth::add_hidden_token_field' );
		add_action( 'wpbdp_enqueue_form_scripts', 'WPBDPStrpActionsController::maybe_load_scripts' );
		add_action( 'init', 'WPBDPStrpConnectHelper::check_for_stripe_connect_webhooks' );

		// Filters.
		add_filter( 'wpbdp_saved_errors', 'WPBDPStrpAppController::maybe_add_payment_error', 10, 2 );
		add_filter( 'wpbdp_filter_final_form', 'WPBDPStrpAuth::maybe_show_message' );
		//add_filter( 'wpbdp_setup_edit_entry_vars', 'WPBDPStrpAppController::maybe_delete_pay_entry', 20, 2 );

		// This filter flags the Pro credit card field that Stripe is enabled.
		add_filter(
			'wpbdp_pro_show_card_callback',
			function() {
				return 'WPBDPStrpActionsController::show_card';
			}
		);

		// Stripe link.
		//add_filter( 'wpbdp_form_classes', 'WPBDPStrpLinkController::add_form_classes' );
	}

	/**
	 * @return void
	 */
	public static function load_admin_hooks() {
		// Actions.
		add_action( 'wpbdp_after_uninstall', 'WPBDPStrpAppController::uninstall' );

		// Filters.
		add_filter( 'wpbdp_pay_action_defaults', 'WPBDPStrpActionsController::add_action_defaults' );
		add_filter( 'wpbdp_pay_stripe_receipt', 'WPBDPStrpPaymentsController::get_receipt_link' );
		add_filter( 'wpbdp_sub_stripe_receipt', 'WPBDPStrpPaymentsController::get_receipt_link' );

		if ( defined( 'DOING_AJAX' ) ) {
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
		add_action( 'wp_ajax_nopriv_wpbdp_strp_amount', 'WPBDPStrpAuth::update_intent_ajax' );
		add_action( 'wp_ajax_wpbdp_strp_amount', 'WPBDPStrpAuth::update_intent_ajax' );

		// Stripe link.
		add_action( 'wp_ajax_nopriv_wpbdpstrplinkreturn', 'WPBDPStrpLinkController::handle_return_url' );
		add_action( 'wp_ajax_wpbdpstrplinkreturn', 'WPBDPStrpLinkController::handle_return_url' );

		// Stripe Lite
		add_action( 'wp_ajax_nopriv_wpbdp_strp_lite_verify', 'WPBDPStrpConnectHelper::verify' );
	}
}
