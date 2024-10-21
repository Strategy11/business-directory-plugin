<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since x.x
 */
class WPBDPStrpPaymentTypeHandler {

	/**
	 * @var array
	 */
	private static $types_by_action_id = array();

	/**
	 * @since x.x
	 *
	 * @param WP_Post $action
	 * @return string[]
	 */
	private static function get_filtered_payment_method_types( $action ) {
		/**
		 * Allow users to filter payment method types to add possible other options like "us_bank_account".
		 * An empty array is treated as automatic.
		 *
		 * @since x.x
		 *
		 * @param array<string> $payment_method_types
		 * @param array $args {
		 *     @type WP_Post $action
		 * }
		 */
		$payment_method_types = apply_filters(
			'wpbdp_stripe_payment_method_types',
			array(),
			array(
				'action'  => $action,
				'form_id' => $action->menu_order,
			)
		);

		if ( ! is_array( $payment_method_types ) ) {
			_doing_it_wrong( __FUNCTION__, 'Payment method types should be an array. All other values are invalid.', '3.1' );
			$payment_method_types = array(); // Fallback to automatic when an invalid value is used.
		}

		return $payment_method_types;
	}
}
