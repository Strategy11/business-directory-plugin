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
	 *
	 * @return bool
	 */
	public static function cancel_subscription( $sub_id ) {
		if ( current_user_can( 'manage_options' ) ) {
			$customer_id = false;
		} else {
			$user_id     = get_current_user_id();
			$meta_name   = WPBDPStrpAppHelper::customer_meta_name();
			$customer_id = get_user_meta( $user_id, $meta_name, true );

			if ( ! $customer_id || ! WPBDPStrpConnectHelper::validate_customer( $customer_id ) ) {
				return false;
			}
		}
		return WPBDPStrpConnectHelper::cancel_subscription( $sub_id, $customer_id );
	}
}
