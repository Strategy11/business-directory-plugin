<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class WPBDPStrpAppController {

	/**
	 * Flag to delete the previous pay entry.
	 *
	 * @since x.x
	 *
	 * @var bool
	 */
	private static $delete_pay_entry = false;

	/**
	 * Handle Stripe Link redirect failures.
	 * When a payment fails, the entry is deleted, and the previous entry's values are loaded in the form.
	 *
	 * @since x.x
	 *
	 * @param array $errors
	 * @param int   $form_id
	 * @return array
	 */
	private static function maybe_add_payment_error_on_redirect( $errors, $form_id ) {
		$details = WPBDPStrpUrlParamHelper::get_details_for_form( $form_id );
		if ( ! is_array( $details ) ) {
			return $errors;
		}

		$entry   = $details['entry'];
		$intent  = $details['intent'];
		$payment = $details['payment'];

		// Only add the payment error if the intent is incomplete.
		if ( ! in_array( $intent->status, array( 'requires_source', 'requires_payment_method', 'canceled' ), true ) ) {
			return $errors;
		}

		$cc_field_id = ''; // TODO
		if ( ! $cc_field_id ) {
			return $errors;
		}

		$is_setup_intent = 0 === strpos( $intent->id, 'seti_' );
		if ( $is_setup_intent ) {
			$errors[ 'field' . $cc_field_id ] = $intent->last_setup_error->message;
		} else {
			$errors[ 'field' . $cc_field_id ] = $intent->last_payment_error->message;
		}

		global $wpbdp_vars;
		$wpbdp_vars['wpbdp_trans']['pay_entry'] = $entry;

		self::setup_form_after_payment_error( (int) $entry->form_id, (int) $entry->id, $errors );

		add_filter( 'wpbdp_setup_new_fields_vars', 'WPBDPStrpActionsController::fill_entry_from_previous', 20, 2 );

		return $errors;
	}

	/**
	 * Reset a form after a payment fails.
	 *
	 * @since x.x
	 *
	 * @param int                  $form_id
	 * @param int                  $entry_id
	 * @param array<string,string> $errors
	 * @return void
	 */
	private static function setup_form_after_payment_error( $form_id, $entry_id, $errors ) {
		global $wpbdp_vars;
		$wpbdp_vars['created_entries'][ $form_id ]['errors'] = $errors;

		self::$delete_pay_entry = true;
	}

	/**
	 * Maybe delete the previous pay entry when error occurs.
	 *
	 * @since x.x
	 *
	 * @param array  $values Entry edit values.
	 * @param object $field  Field object.
	 * @return array
	 */
	public static function maybe_delete_pay_entry( $values, $field ) {
		if ( self::$delete_pay_entry ) {
			self::$delete_pay_entry = false;
			return WPBDPStrpActionsController::fill_entry_from_previous( $values, $field );
		}
		return $values;
	}
}
