<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * This file exists for backward compatibility with the Payments Submodule.
 * Without these functions the Authorize.Net add on will trigger fatal errors.
 */
class WPBDPStrpApiHelper {

	/**
	 * @return bool
	 */
	public static function initialize_api() {
		return WPBDPStrpConnectHelper::stripe_connect_is_setup();
	}

	/**
	 * The payments submodule calls this function.
	 * This function exists so subscriptions can be cancelled when Authorize.Net is active.
	 *
	 * @param string $sub_id
	 * @return bool
	 */
	public static function cancel_subscription( $sub_id ) {
		if ( current_user_can( 'administrator' ) ) {
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
	 * The payments submodule calls this function.
	 * This function exists so payments can be refunded when Authorize.Net is active.
	 *
	 * @param int $payment_id
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
	 * @param array $options
	 * @return object|string
	 */
	public static function get_customer( $options = array() ) {
		$user_id   = ! empty( $options['user_id'] ) ? $options['user_id'] : get_current_user_id();
		$meta_name = WPBDPStrpAppHelper::get_customer_id_meta_name();

		if ( $user_id ) {
			$customer_id = get_user_meta( $user_id, $meta_name, true );
			if ( ! isset( $options['email'] ) ) {
				$user_info = get_userdata( $user_id );
				if ( ! empty( $user_info->user_email ) ) {
					$options['email'] = $user_info->user_email;
				}
			}
			if ( $customer_id ) {
				$options['customer_id'] = $customer_id;
			}
		}

		if ( isset( $options['user_id'] ) ) {
			unset( $options['user_id'] );
		}

		$a_customer_id_value_was_previously_set = ! empty( $customer_id );
		$customer_id                            = WPBDPStrpConnectHelper::get_customer_id( $options );

		if ( $customer_id ) {
			$customer_id_is_actually_an_error_message = false === strpos( $customer_id, 'cus_' );
			if ( $customer_id_is_actually_an_error_message ) {
				$customer_id_error_message = $customer_id;
				$customer_id               = false;
			}
		}

		if ( ! $customer_id ) {
			if ( $a_customer_id_value_was_previously_set ) {
				delete_user_meta( $user_id, $meta_name );
			}

			if ( ! empty( $customer_id_error_message ) ) {
				return $customer_id_error_message;
			}

			return __( 'Unable to retrieve customer through Stripe Connect.', 'business-directory-plugin' );
		}

		if ( $user_id ) {
			update_user_meta( $user_id, $meta_name, $customer_id );
		}

		return self::create_decoy_customer( $customer_id );
	}

	/**
	 * @param string $customer_id
	 * @return object
	 */
	private static function create_decoy_customer( $customer_id ) {
		$decoy_object       = new stdClass();
		$decoy_object->id   = $customer_id;
		$decoy_object->type = 'customer';
		return $decoy_object;
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
	 * @since x.x
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
	 * @since x.x
	 *
	 * @param string $setup_id
	 * @return object|string|false
	 */
	public static function get_setup_intent( $setup_id ) {
		return WPBDPStrpConnectHelper::get_setup_intent( $setup_id );
	}
}
