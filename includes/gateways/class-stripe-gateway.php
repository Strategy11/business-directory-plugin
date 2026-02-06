<?php
/**
 * Stripe Gateway
 *
 * @package Stripe/Includes/Stripe Gateway
 */

/**
 * Class WPBDPStripeGateway
 */
class WPBDPStripeGateway extends WPBDP__Payment_Gateway {

	public function __construct() {
		// Filters.
		add_filter( 'wpbdp_setting_type_strp_connect', array( &$this, 'connect_setting' ), 20, 2 );
	}

	public function get_id() {
		return WPBDPStrpAppHelper::$gateway_id;
	}

	public function get_title() {
		return __( 'Stripe', 'business-directory-plugin' );
	}

	public function get_logo() {
		return wpbdp_render( 'payment/stripe-credit-cards-logo' );
	}

	/**
	 * @param string $currency Currency code.
	 */
	public function supports_currency( $currency ) {
		// List taken from https://stripe.com/docs/currencies#charge-currencies.
		return in_array(
			$currency,
			array(
				'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN',
				'BAM', 'BBD', 'BDT', 'BGN', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD',
				'BWP', 'BYN', 'BZD',
				'CAD', 'CDF', 'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CVE', 'CZK',
				'DJF', 'DKK', 'DOP', 'DZD',
				'EGP', 'ETB', 'EUR',
				'FJD', 'FKP',
				'GBP', 'GEL', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD',
				'HKD', 'HNL', 'HRK', 'HTG', 'HUF',
				'IDR', 'ILS', 'INR', 'ISK',
				'JMD', 'JPY',
				'KES', 'KGS', 'KHR', 'KMF', 'KRW', 'KYD', 'KZT',
				'LAK', 'LBP', 'LRD', 'LSL',
				'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MUR', 'MVR',
				'MWK', 'MXN', 'MYR', 'MZN',
				'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD',
				'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG',
				'QAR',
				'RON', 'RSD', 'RUB', 'RWF',
				'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'STD', 'SZL',
				'THB', 'TJS', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS',
				'UAH', 'UGX', 'USD', 'UYU', 'UZS',
				'VND', 'VUV',
				'WST',
				'XAF', 'XCD', 'XOF', 'XPF',
				'YER',
				'ZAR', 'ZMW',
			),
			true
		);
	}

	/**
	 * @since 6.4.9
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'stripe', 'https://js.stripe.com/v3/', array(), '3', false );
		wp_enqueue_script( 'wpbdp-stripe-checkout' );
	}

	/**
	 * @return string
	 */
	private function get_publishable_key() {
		$test = 'pk_test_O28pD8iFrurLqBaayUQAOXch';
		$live = 'pk_live_sV5eqcAg9SUC6E0s79X9uYKc';
		return $this->in_test_mode() ? $test : $live;
	}

	/**
	 * @return string
	 */
	public function get_integration_method() {
		return 'direct';
	}

	/**
	 * Override this in the individual gateway class.
	 *
	 * @since 6.4.9
	 *
	 * @param WPBDP_Payment $payment Payment object.
	 *
	 * @return string
	 */
	public function get_payment_link( $payment ) {
		$url = 'https://dashboard.stripe.com/';
		if ( isset( $payment->mode ) && $payment->mode === 'test' ) {
			$url .= 'test/';
		}
		return $url . 'payments/' . $payment->gateway_tx_id;
	}

	/**
	 * @return array
	 */
	public function get_settings() {
		return array(
			array(
				'id'   => 'connect',
				'name' => '',
				'type' => 'strp_connect',
			),
			array(
				'id'      => 'checkout-title',
				'name'    => __( 'Checkout Window Title', 'business-directory-plugin' ),
				'type'    => 'text',
				'default' => '',
			),
			array(
				'id'      => 'billing-address-check',
				'name'    => __( 'Verify billing address during checkout?', 'business-directory-plugin' ),
				'type'    => 'checkbox',
				'default' => false,
			),
		);
	}

	/**
	 * @since 6.4.9
	 *
	 * @return string
	 */
	public function connect_setting( $setting, $value ) {
		ob_start();
		WPBDPStrpAppHelper::fee_education();

		WPBDPStrpConnectHelper::render_stripe_connect_settings_container();
		$html = ob_get_clean();

		return is_string( $html ) ? $html : '';
	}

	/**
	 * Render required HTML to support Stripe payments.
	 *
	 * @param WPBDP_Payment $payment Payment object.
	 * @param array         $errors  Errors.
	 *
	 * @return string
	 */
	public function render_form( $payment, $errors = array() ) {
		$stripe = $this->configure_stripe( $payment );

		$content = '<div class="wpbdp-msg wpbdp-error stripe-errors" style="display:none;">';
		if ( ! $stripe['sessionId'] ) {
			$content .= $stripe['sessionError'] ? $stripe['sessionError'] : __( 'There was an error while configuring Stripe gateway', 'business-directory-plugin' );
		}

		$content .= '</div>';

		$custom_script = '<script id="wpbdp-stripe-checkout-configuration" data-configuration="%s"></script>';
		$content      .= sprintf( $custom_script, esc_attr( (string) wp_json_encode( $stripe ) ) );

		return $content;
	}

	/**
	 * Get Stripe variables for front end JS.
	 *
	 * @param WPBDP_Payment $payment Payment object.
	 *
	 * @return array
	 */
	private function configure_stripe( $payment ) {
		$stripe = array(
			'key'       => $this->get_publishable_key(),
			'accountId' => WPBDPStrpConnectHelper::get_account_id(),
		);

		$session = $this->create_stripe_session( $payment );

		if ( is_wp_error( $session ) ) {
			$stripe['sessionId']    = false;
			$stripe['sessionError'] = $session->get_error_message();

			return $stripe;
		}

		$stripe['sessionId'] = $session->id;

		return $stripe;
	}

	/**
	 * Return a json error and die.
	 *
	 * @param string $error Error message.
	 *
	 * @return void
	 */
	private function handle_exception( $error ) {
		echo wp_json_encode(
			array(
				'error' => $error,
			)
		);
		wp_die();
	}

	/**
	 * @since 6.4.9
	 *
	 * @throws Exception If the response is not valid.
	 */
	private function get_posted_json() {
		$input = file_get_contents( 'php://input' );
		if ( false === $input ) {
			throw new Exception( 'Invalid input' );
		}
		return json_decode( $input );
	}

	private function get_stripe_customer( $payment, $create = true ) {
		$customer = null;

		$user_ids              = $this->get_possible_user_ids( $payment );
		$possible_customer_ids = $user_ids['possible_customer_ids'];
		$user_ids              = $user_ids['user_ids'];
		$this_user             = 0;

		foreach ( $possible_customer_ids as $uid => $sid ) {
			$customer_id = WPBDPStrpConnectHelper::get_customer_id( array( 'customer_id' => $sid ) );
			if ( false !== $customer_id ) {
				$customer     = new stdClass();
				$customer->id = $customer_id;
			} else {
				$customer = null;
			}

			if ( $customer ) {
				$this_user = $uid;
				break;
			}

			if ( $uid ) {
				// Remove the user meta if the customer doesn't exist.
				delete_user_meta( $uid, $this->customer_meta_name() );
			}
		}

		if ( $customer ) {
			return $customer;
		}

		if ( ! $create ) {
			return $customer;
		}

		$customer_id = WPBDPStrpConnectHelper::get_customer_id( $this->new_customer_data( $payment ) );
		if ( false === $customer_id ) {
			return null;
		}

		$customer     = new stdClass();
		$customer->id = $customer_id;

		$this->set_listing_stripe_customer( $payment->listing_id, $customer->id );

		if ( ! $this_user ) {
			$this_user = reset( $user_ids );
		}

		if ( $this_user ) {
			update_user_meta( $this_user, $this->customer_meta_name(), $customer->id );
		}

		return $customer;
	}

	/**
	 * Return the user ids in order of priority.
	 * The customer ID will include the last used payment method, so use carefully.
	 *
	 * @since 6.4.9
	 *
	 * @param WPBDP_Payment $payment Payment object.
	 *
	 * @return array
	 */
	private function get_possible_user_ids( $payment ) {
		$user_ids = array();
		if ( is_user_logged_in() ) {
			// The default user is only allowed here.
			$user_ids[] = get_current_user_id();
		}

		$default_author = (int) wpbdp_get_option( 'default-listing-author' );
		$post           = get_post( $payment->listing_id );
		if ( empty( $user_ids ) && $post->post_author && $default_author && $default_author !== (int) $post->post_author ) {
			$user_ids[] = $post->post_author;
		}

		$possible_customer_ids   = array();
		$possible_customer_ids[] = get_post_meta( $payment->listing_id, $this->customer_meta_name(), true );

		$user_ids = array_filter( array_unique( $user_ids ) );
		foreach ( $user_ids as $user_id ) {
			$possible_customer_ids[ $user_id ] = get_user_meta( $user_id, $this->customer_meta_name(), true );
		}
		$possible_customer_ids = array_filter( array_unique( $possible_customer_ids ) );

		return compact( 'user_ids', 'possible_customer_ids' );
	}

	/**
	 * The name of the post or user meta, depending on test or live mode.
	 *
	 * @since 6.4.9
	 *
	 * @return string
	 */
	private function customer_meta_name() {
		return WPBDPStrpAppHelper::customer_meta_name();
	}

	/**
	 * @since 6.4.9
	 *
	 * @param WPBDP_Payment $payment Payment object.
	 *
	 * @return array
	 */
	private function new_customer_data( $payment ) {
		$details = $payment->get_payer_details();
		if ( is_user_logged_in() ) {
			// Use the account email instead of the email on the listing if logged in.
			$user             = wp_get_current_user();
			$details['email'] = $user->user_email;
			if ( empty( $details['first_name'] ) ) {
				$details['first_name'] = $user->user_firstname;
			}
			if ( empty( $details['last_name'] ) ) {
				$details['last_name'] = $user->user_lastname;
			}
			if ( empty( $details['first_name'] ) ) {
				$details['first_name'] = $user->display_name;
			}
		}

		$new_customer = array(
			'email'   => $details['email'],
			'address' => array(),
			'name'    => trim( $details['first_name'] . ' ' . $details['last_name'] ),
		);

		$fill = array( 'city', 'state', 'country', 'postal_code' => 'zip' );
		foreach ( $fill as $k => $f ) {
			if ( is_numeric( $k ) ) {
				// Set the key to the Stripe naming.
				$k = $f;
			}
			if ( ! empty( $details[ $f ] ) ) {
				$new_customer['address'][ $k ] = $details[ $f ];
			}
		}

		return $new_customer;
	}

	private function set_listing_stripe_customer( $listing_id, $customer_id ) {
		if ( $listing_id && ! empty( $customer_id ) ) {
			update_post_meta( $listing_id, $this->customer_meta_name(), $customer_id );
		}
	}

	/**
	 * @param WPBDP_Payment $payment Payment object.
	 *
	 * @return array
	 */
	private function get_session_parameters( $payment ) {
		$parameters = array(
			'billing_address_collection' => $this->get_option( 'billing-address-check' ) ? 'required' : 'auto',
			'payment_method_types'       => array( 'card' ),
			'client_reference_id'        => $payment->id,
			'success_url'                => $payment->get_return_url(),
			'cancel_url'                 => $payment->get_cancel_url(),
		);

		$parameters['customer'] = $this->get_stripe_customer( $payment )->id;

		if ( $payment->has_item_type( 'recurring_plan' ) ) {
			$plan = $this->get_stripe_plan( $payment );

			$parameters['subscription_data'] = array(
				'items'    => array(
					array(
						'plan' => $plan->id,
					),
				),
				'metadata' => array(
					'wpbdp_payment_id' => $payment->id,
				),
			);
			$parameters['discounts']         = $this->get_discounts( $payment );
		} else {
			$checkout_title           = $this->get_option( 'checkout-title' );
			$parameters['line_items'] = array(
				array(
					'name'        => esc_attr( $checkout_title ? $checkout_title : get_bloginfo( 'name' ) ),
					'description' => $payment->summary,
					'amount'      => $this->formated_amount( $payment->amount ),
					'currency'    => strtolower( $payment->currency_code ),
					'quantity'    => 1,
				),
			);

			// Include payment ID in payment intent metadata for retry handling.
			$parameters['payment_intent_data'] = array(
				'metadata' => array(
					'wpbdp_payment_id' => $payment->id,
				),
			);
		}

		return $parameters;
	}

	/**
	 * @since 6.4.9
	 *
	 * @param WPBDP_Payment $payment Payment object.
	 *
	 * @return array
	 */
	private function get_discounts( $payment ) {
		if ( ! $payment->has_item_type( 'discount_code' ) ) {
			return array();
		}

		$discount = $payment->find_item( 'discount_code' );
		if ( ! $discount ) {
			return array();
		}

		$coupon = WPBDPStrpConnectHelper::create_coupon(
			array(
				'amount_off' => abs( (int) $this->formated_amount( $discount['amount'] ) ),
				'currency'   => $payment->currency_code,
				'duration'   => 'once',
			)
		);
		if ( ! is_object( $coupon ) ) {
			return array();
		}

		return array(
			array(
				'coupon' => $coupon->id,
			),
		);
	}

	/**
	 * @param WPBDP_Payment $payment Payment object.
	 *
	 * @return object
	 */
	private function get_stripe_plan( $payment ) {
		$recurring = $payment->find_item( 'recurring_plan' );

		$recurring_plan_fingerprint = $this->get_recurring_plan_fingerprint( $recurring, $payment );

		$previous_id = 'bd-fee-id' . $recurring['fee_id'] . '-d' . $recurring['fee_days'];
		$plan_id     = 'bd-fee-id-' . $recurring['fee_id'] . '-' . $recurring_plan_fingerprint;

		foreach ( array( $previous_id, $plan_id ) as $id ) {
			$plan = $this->try_to_get_stripe_plan_with_id( $id );
			if ( ! is_object( $plan ) ) {
				continue;
			}

			$stripe_plan_fingerprint = $this->get_stripe_plan_fingerprint( $plan );

			if ( $stripe_plan_fingerprint === $recurring_plan_fingerprint ) {
				return $plan;
			}
		}

		return $this->create_stripe_plan( $plan_id, $recurring, $payment );
	}

	/**
	 * @param array         $recurring Recurring plan data.
	 * @param WPBDP_Payment $payment   Payment object.
	 *
	 * @return string
	 */
	private function get_recurring_plan_fingerprint( $recurring, $payment ) {
		$params = array(
			'amount'         => $this->formated_amount( $recurring['amount'] ),
			'currency'       => strtolower( $payment->currency_code ),
			'interval'       => 'day',
			'interval_count' => intval( $recurring['fee_days'] ),
		);

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		return hash( 'crc32b', serialize( $params ) );
	}

	/**
	 * @param string $id
	 *
	 * @return false|object
	 */
	private function try_to_get_stripe_plan_with_id( $id ) {
		return WPBDPStrpConnectHelper::get_plan( $id );
	}

	/**
	 * @param stdClass $plan
	 *
	 * @return string
	 */
	private function get_stripe_plan_fingerprint( $plan ) {
		$params = array(
			'amount'         => floatval( $plan->amount ),
			'currency'       => $plan->currency,
			'interval'       => 'day',
			'interval_count' => intval( $plan->interval_count ),
		);

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		return hash( 'crc32b', serialize( $params ) );
	}

	/**
	 * @return mixed
	 */
	private function create_stripe_plan( $id, $recurring, $payment ) {
		$plan = array(
			'amount'         => $this->formated_amount( $recurring['amount'] ),
			'currency'       => strtolower( $payment->currency_code ),
			'interval'       => 'day',
			'interval_count' => $recurring['fee_days'],
			'product'        => array(
				'name' => $recurring['description'],
			),
			'id'             => $id,
		);
		return WPBDPStrpConnectHelper::create_plan( $plan );
	}

	/**
	 * @since 6.4.9
	 *
	 * @param WPBDP_Listing               $listing      Listing object.
	 * @param WPBDP__Listing_Subscription $subscription Subscription object.
	 */
	public function cancel_subscription( $listing, $subscription ) {
		$cancel = WPBDPStrpApiHelper::cancel_subscription( $subscription->get_subscription_id() );
		if ( $cancel ) {
			// Mark as canceled in BD.
			$subscription->cancel();
		}
	}

	/**
	 * @param WPBDP_Payment $payment Payment object.
	 *
	 * @return object|WP_Error
	 */
	private function create_stripe_session( $payment ) {
		$payment->gateway = $this->get_id();

		$session = WPBDPStrpConnectHelper::create_checkout_session( $this->get_session_parameters( $payment ) );

		if ( false === $session ) {
			return new WP_Error( 'stripe_no_session', 'Failed to create checkout session' );
		}

		return $session;
	}

	/**
	 * @since 6.4.9
	 *
	 * @param float $amount Amount to be formatted.
	 *
	 * @return float
	 */
	private function formated_amount( $amount ) {
		return round( $amount * 100, 0 );
	}

	/**
	 * Confirm settings are valid.
	 *
	 * @since 6.4.9
	 *
	 * @return array<string> Error messages.
	 */
	public function validate_settings() {
		$mode = WPBDPStrpAppHelper::active_mode();

		if ( WPBDPStrpConnectHelper::stripe_connect_is_setup( $mode ) ) {
			return array();
		}

		return array(
			_x( 'Stripe payments are not connected.', 'stripe', 'business-directory-plugin' ),
		);
	}
}
