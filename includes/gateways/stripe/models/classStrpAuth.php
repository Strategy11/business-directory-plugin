<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class WPBDPStrpAuth {

	/**
	 * All of the form IDs with payment details in the URL params will be included in this array.
	 *
	 * @var array
	 */
	private static $form_ids = array();

	/**
	 * @param string|int $form_id
	 * @return array|false
	 */
	private static function check_request_params( $form_id ) {
		if ( ! WPBDPStrpApiHelper::initialize_api() ) {
			return false;
		}

		$details = WPBDPStrpUrlParamHelper::get_details_for_form( $form_id );
		if ( ! is_array( $details ) ) {
			return false;
		}

		self::$form_ids[] = $form_id;

		return $details;
	}

	/**
	 * The wpbdp_filter_final_form filter only passes form HTML as a string.
	 * To determine which form is being filtered, this function checks for the
	 * hidden form_id input. If there is a match, it returns the matching form id.
	 *
	 * @since x.x
	 *
	 * @param string $html
	 * @return int|false Matching form id or false if there is no match.
	 */
	private static function check_html_for_form_id_match( $html ) {
		foreach ( self::$form_ids as $form_id ) {
			$substring = '<input type="hidden" name="form_id" value="' . $form_id . '"';
			if ( strpos( $html, $substring ) ) {
				return $form_id;
			}
		}

		return false;
	}

	/**
	 * Add the parameters the receiving functions are expecting.
	 *
	 * @since x.x
	 *
	 * @param array $atts
	 * @return void
	 */
	private static function prepare_success_atts( &$atts ) {
		$atts['form']        = ''; // TODO Form::getOne( $atts['entry']->form_id );
		$atts['entry_id']    = $atts['entry']->id;
		$opt                 = 'success_action';
		$atts['conf_method'] = ! empty( $atts['form']->options[ $opt ] ) ? $atts['form']->options[ $opt ] : 'message';
	}

	/**
	 * Create a customer and an associated setup intent for a recurring Stripe link payment.
	 *
	 * @since x.x
	 *
	 * @param array $payment_method_types
	 * @return object|false
	 */
	private static function create_setup_intent( $payment_method_types ) {
		$payment_info = array(
			'user_id' => WPBDPStrpAppHelper::get_user_id_for_current_payment(),
		);

		// We need to add a customer to support subscriptions with link.
		$customer = WPBDPStrpApiHelper::get_customer( $payment_info );
		if ( ! is_object( $customer ) ) {
			return false;
		}

		return WPBDPStrpApiHelper::create_setup_intent( $customer->id, $payment_method_types );
	}

	/**
	 * Get the URL to return to after a payment is complete.
	 * This may either use the success URL on redirect, or the message on success.
	 * It shouldn't be confused for the Stripe link return URL. It isn't used for that. That uses the wpbdpstrplinkreturn AJAX action instead.
	 *
	 * @since x.x
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function return_url( $atts ) {
		$atts = array(
			'entry' => $atts['entry'],
		);
		self::prepare_success_atts( $atts );

		if ( $atts['conf_method'] === 'redirect' ) {
			$redirect = self::get_redirect_url( $atts );
		} else {
			$redirect = self::get_message_url( $atts );
		}

		return $redirect;
	}

	/**
	 * If the form should redirect, get the url to redirect to.
	 *
	 * @since x.x
	 *
	 * @param array $atts {
	 *     @type stdClass $form
	 *     @type stdClass $entry
	 * }
	 * @return string
	 */
	private static function get_redirect_url( $atts ) {
		$success_url = $atts['form']->options['success_url'];

		$success_url = trim( $atts['form']->options['success_url'] );
		$success_url = apply_filters( 'wpbdp_content', $success_url, $atts['form'], $atts['entry'] );
		$success_url = do_shortcode( $success_url );
		$atts['id']  = $atts['entry']->id;

		return $success_url;
	}

	/**
	 * If the form should should a message, apend it to the success url.
	 *
	 * @since x.x
	 *
	 * @param array $atts
	 */
	private static function get_message_url( $atts ) {
		$url = wpbdp_get_server_value( 'HTTP_REFERER' );
		return add_query_arg( array( 'wpbdpstrp' => $atts['entry_id'] ), $url );
	}

	/**
	 * Delete the referer meta as we'll no longer need it.
	 *
	 * @param int $row_id
	 * @return void
	 */
	private static function delete_temporary_referer_meta( $row_id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'wpbdp_item_metas', array( 'id' => $row_id ) );
	}

	/**
	 * Check if a payment or setup intent has failed.
	 *
	 * @since x.x
	 *
	 * @param object $intent
	 * @return bool
	 */
	private static function intent_has_failed_status( $intent ) {
		return in_array( $intent->status, array( 'requires_source', 'requires_payment_method', 'canceled' ), true );
	}
}
