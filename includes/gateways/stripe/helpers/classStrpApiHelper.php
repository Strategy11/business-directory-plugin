<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * The majority of Stripe Connect logic lies in WPBDPStrpConnectHelper
 * The purpose of this Adapter is to mirror WPBDPStrpAppHelper's interface
 */
class WPBDPStrpApiHelper {

	/**
	 * @param string $sub_id
	 * @return bool
	 */
	public static function cancel_subscription( $sub_id ) {
		if ( current_user_can( 'manage_options' ) ) {
			$customer_id = false;
		} else {
			$user_id  = get_current_user_id();
			$customer = self::get_customer_by_id( $user_id );
			if ( ! is_object( $customer ) ) {
				return false;
			}
			$customer_id = $customer->id;
		}
		return WPBDPStrpConnectHelper::cancel_subscription( $sub_id, $customer_id );
	}

	/**
	 * @param string $payment_id
	 * @return bool
	 */
	public static function refund_payment( $payment_id ) {
		return WPBDPStrpConnectHelper::refund_payment( $payment_id );
	}

	/**
	 * Get the payment intent from Stripe
	 *
	 * @param string $payment_id
	 * @return mixed
	 */
	public static function get_intent( $payment_id ) {
		return WPBDPStrpConnectHelper::get_intent( $payment_id );
	}

	/**
	 * @return array
	 */
	public static function get_customer_subscriptions() {
		return WPBDPStrpConnectHelper::get_customer_subscriptions();
	}

	/**
	 * @param int $user_id
	 * @return mixed
	 */
	public static function get_customer_by_id( $user_id ) {
		$meta_name   = WPBDPStrpAppHelper::get_customer_id_meta_name();
		$customer_id = get_user_meta( $user_id, $meta_name, true );

		if ( ! $customer_id ) {
			return false;
		}

		if ( ! WPBDPStrpConnectHelper::validate_customer( $customer_id ) ) {
			delete_user_meta( $user_id, $meta_name );
			return false;
		}

		return self::create_decoy_customer( $customer_id );
	}

	/**
	 * @param string $event_id
	 * @return mixed
	 */
	public static function get_event( $event_id ) {
		return WPBDPStrpConnectHelper::get_event( $event_id );
	}

	/**
	 * @param array $plan
	 */
	public static function maybe_create_plan( $plan ) {
		return WPBDPStrpConnectHelper::maybe_create_plan( $plan );
	}

	/**
	 * @param array $new_charge
	 * @return object|string|false
	 */
	public static function create_subscription( $new_charge ) {
		return WPBDPStrpConnectHelper::create_subscription( $new_charge );
	}

	/**
	 * @param array $new_charge
	 * @return mixed
	 */
	public static function create_intent( $new_charge ) {
		return WPBDPStrpConnectHelper::create_intent( $new_charge );
	}

	/**
	 * @param string $intent_id
	 * @param array  $data
	 * @return mixed
	 */
	public static function update_intent( $intent_id, $data ) {
		return WPBDPStrpConnectHelper::update_intent( $intent_id, $data );
	}

	/**
	 * Create a setup intent for a Stripe link recurring payment.
	 * This is called when a form is loaded.
	 *
	 * @since 6.5, introduced in v3.0 of the Stripe add on.
	 *
	 * @param string      $customer_id Customer ID beginning with cus_.
	 * @param array|false $payment_method_types If false the types will defaults to array( 'card', 'link' ).
	 * @return object|string|false
	 */
	public static function create_setup_intent( $customer_id, $payment_method_types = false ) {
		return WPBDPStrpConnectHelper::create_setup_intent( $customer_id, $payment_method_types );
	}

	/**
	 * Get a setup intent (used for Stripe link recurring payments).
	 *
	 * @since 6.5, introduced in v3.0 of the Stripe add on.
	 *
	 * @param string $setup_id
	 * @return object|string|false
	 */
	public static function get_setup_intent( $setup_id ) {
		return WPBDPStrpConnectHelper::get_setup_intent( $setup_id );
	}
}
