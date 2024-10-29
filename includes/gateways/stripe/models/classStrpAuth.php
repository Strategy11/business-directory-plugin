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
	 * Check POST data for payment intents.
	 *
	 * @since x.x
	 *
	 * @param string $name
	 * @return mixed
	 */
	public static function get_payment_intents( $name ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ $name ] ) ) {
			return array();
		}
		$intents = $_POST[ $name ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
		wpbdp_sanitize_value( 'sanitize_text_field', $intents );
		return $intents;
	}

	/**
	 * Create an entry object with posted values.
	 *
	 * @since x.x
	 * @return stdClass
	 */
	private static function generate_false_entry() {
		$entry          = new stdClass();
		$entry->post_id = 0;
		$entry->id      = 0;
		$entry->metas   = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		foreach ( $_POST as $k => $v ) {
			$k = sanitize_text_field( stripslashes( $k ) );
			$v = wp_unslash( $v );

			if ( $k === 'item_meta' ) {
				foreach ( $v as $f => $value ) {
					wpbdp_sanitize_value( 'wp_kses_post', $value );
					$entry->metas[ absint( $f ) ] = $value;
				}
			} else {
				wpbdp_sanitize_value( 'wp_kses_post', $v );
				$entry->{$k} = $v;
			}
		}

		return $entry;
	}

	/**
	 * Reformat the form data in name => value array.
	 *
	 * @since x.x
	 *
	 * @param array $form
	 * @return void
	 */
	private static function format_form_data( &$form ) {
		$formatted = array();

		foreach ( $form as $input ) {
			$key = $input['name'];
			if ( isset( $formatted[ $key ] ) ) {
				if ( is_array( $formatted[ $key ] ) ) {
					$formatted[ $key ][] = $input['value'];
				} else {
					$formatted[ $key ] = array( $formatted[ $key ], $input['value'] );
				}
			} else {
				$formatted[ $key ] = $input['value'];
			}
		}

		parse_str( http_build_query( $formatted ), $form );
	}

	/**
	 * Create intents on form load when required.
	 * This only happens in two cases: For stripe link, and when processing a one-time payment before the entry is created.
	 *
	 * @since x.x
	 *
	 * @param string|int $form_id
	 * @return array
	 */
	private static function maybe_create_intents( $form_id ) {
		$intents = array();

		$details = self::check_request_params( $form_id );
		if ( is_array( $details ) && ! self::intent_has_failed_status( $details['intent'] ) ) {
			// Exit early if the request params are set.
			// This way an extra payment intent isn't created for Stripe Link.
			return $intents;
		}

		if ( ! WPBDPStrpApiHelper::initialize_api() ) {
			// Stripe is not configured, so don't create intents.
			return $intents;
		}

		/*
		$actions = WPBDPStrpActionsController::get_actions_before_submit( $form_id );
		//self::add_amount_to_actions( $form_id, $actions );

		foreach ( $actions as $action ) {
			if ( is_array( $details ) && self::intent_has_failed_status( $details['intent'] ) ) {
				$intents[] = array(
					'id'     => $details['intent']->client_secret,
					'action' => $action->ID,
				);
				continue;
			}

			$intent = self::create_intent( $action );
			if ( ! is_object( $intent ) ) {
				// A non-object is a string error message.
				// The error gets logged to results.log so we can just skip it.
				// Reasons it could fail is because a payment method type was specified that will not work.
				// A payment method type may not work because of a currency conflict, or because it isn't enabled.
				// Or the payment method type could be an incorrect value.
				// When using Stripe Connect, the error will just say "Unable to create intent".
				// In this case, you can find the full error message in the Stripe dashboard.
				continue;
			}

			$intents[] = array(
				'id'     => $intent->client_secret,
				'action' => $action->ID,
			);
		}
		*/

		return $intents;
	}

	/**
	 * Create a payment intent for Stripe link or when processing a payment before the entry is created.
	 *
	 * @since x.x
	 *
	 * @param WP_Post $action
	 * @return mixed
	 */
	private static function create_intent( $action ) {
		$amount = $action->post_content['amount'];
		if ( $amount == '000' ) {
			$amount = 100; // Create the intent when the form loads.
		}

		$payment_method_types = array( 'card' );

		if ( 'recurring' === $action->post_content['type'] ) {
			return self::create_setup_intent( $payment_method_types );
		}

		$new_charge = array(
			'amount'   => $amount,
			'currency' => $action->post_content['currency'],
			'metadata' => array( 'action' => $action->ID ),
			'payment_method_types' => $payment_method_types,
		);

		return WPBDPStrpApiHelper::create_intent( $new_charge );
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
