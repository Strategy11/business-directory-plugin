<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Check, verify, and store URL param details.
 * This is used for 3D Secure and Stripe Link.
 *
 * @since x.x
 */
class WPBDPStrpUrlParamHelper {

	/**
	 * Each set of details includes an entry object, a payment object, and an intent object.
	 *
	 * @var array
	 */
	private static $details_by_form_id = array();

	/**
	 * Get some associated payment objects based on the URL param data.
	 * This includes the intent, the entry, and the payments table model instance.
	 *
	 * @param string|int $form_id
	 * @return array|false
	 */
	public static function get_details_for_form( $form_id ) {
		if ( ! isset( self::$details_by_form_id[ $form_id ] ) ) {
			self::set_details_for_form( (int) $form_id );
		}
		return isset( self::$details_by_form_id[ $form_id ] ) ? self::$details_by_form_id[ $form_id ] : false;
	}

	/**
	 * Check the URL params for Stripe intent details.
	 * These params are used in 3D secure as well as Stripe Link.
	 *
	 * The params include:
	 * - The ID of the payment intent or setup intent.
	 * - The ID of the entry.
	 * - The client secret which is used to verify the intent.
	 * - The charge ID (if applicable)
	 *
	 * @since x.x
	 *
	 * @param string|int $form_id
	 * @return void
	 */
	private static function set_details_for_form( $form_id ) {
		$intent_id       = wpbdp_get_var( array( 'param' => 'payment_intent' ) );
		$is_setup_intent = false;

		if ( ! $intent_id ) {
			$intent_id       = wpbdp_get_var( array( 'param' => 'setup_intent' ) );
			$is_setup_intent = true;

			if ( ! $intent_id ) {
				return;
			}
		}

		$intent_function_name = $is_setup_intent ? 'get_setup_intent' : 'get_intent';
		$intent               = WPBDPStrpAppHelper::call_stripe_helper_class( $intent_function_name, $intent_id );

		if ( ! $intent || ! self::verify_client_secret( $intent, $is_setup_intent ) ) {
			return;
		}

		$charge_id   = wpbdp_get_var( array( 'param' => 'charge' ) );
		$has_charge  = (bool) $charge_id;
		$wpbdp_payment = new WPBDPStrpPayment();

		if ( $has_charge ) {
			// Stripe link payments use charge id.
			$payment = $wpbdp_payment->get_one_by( $charge_id, 'receipt_id' );
		}

		if ( ! isset( $payment ) || ! is_object( $payment ) ) {
			// 3D secure payments use intent id.
			$payment = $wpbdp_payment->get_one_by( $intent_id, 'receipt_id' );
		}

		if ( ! is_object( $payment ) ) {
			return;
		}

		$entry = ''; // TODO Entry::getOne( $payment->item_id, true );
		if ( ! is_object( $entry ) || (int) $entry->form_id !== $form_id ) {
			return;
		}

		self::$details_by_form_id[ $form_id ] = array(
			'entry'   => $entry,
			'intent'  => $intent,
			'payment' => $payment,
		);
	}

	/**
	 * Check the client secret in the URL, verify it matches the Stripe object and isn't being manipulated.
	 *
	 * @since x.x
	 *
	 * @param object $intent
	 * @param bool   $is_setup_intent
	 * @return bool True if the client secret is set and valid.
	 */
	private static function verify_client_secret( $intent, $is_setup_intent ) {
		$client_secret_param = $is_setup_intent ? 'setup_intent_client_secret' : 'payment_intent_client_secret';
		$client_secret       = wpbdp_get_var( array( 'param' => $client_secret_param ) );
		return $client_secret && $client_secret === $intent->client_secret;
	}
}
