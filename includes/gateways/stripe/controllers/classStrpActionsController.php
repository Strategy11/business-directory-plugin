<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class WPBDPStrpActionsController {

	/**
	 * @var object|string|null A memoized value for the current Stripe customer object.
	 */
	private static $customer;

	/**
	 * Override the credit card field HTML if there is a Stripe action.
	 *
	 * @since x.x
	 *
	 * @param array  $field
	 * @param string $field_name
	 * @param array  $atts
	 * @return void
	 */
	public static function show_card( $field, $field_name, $atts ) {
		$actions = self::get_actions_before_submit( $field['form_id'] );
		$html_id = $atts['html_id'];
		include WPBDP_PATH . 'includes/gateways/stripe/views/card-field.php';
	}

	/**
	 * Get all published payment actions with the Stripe gateway that have an amount set.
	 *
	 * @since x.x
	 *
	 * @param string|int $form_id
	 * @return array
	 */
	public static function get_actions_before_submit( $form_id ) {
		$payment_actions = self::get_actions_for_form( $form_id );
		foreach ( $payment_actions as $k => $payment_action ) {
			$gateway   = $payment_action->post_content['gateway'];
			$is_stripe = $gateway === 'stripe' || ( is_array( $gateway ) && in_array( 'stripe', $gateway, true ) );
			if ( ! $is_stripe || empty( $payment_action->post_content['amount'] ) ) {
				unset( $payment_actions[ $k ] );
			}
		}
		return $payment_actions;
	}

	/**
	 * Check the Stripe link action for a form. There should only be one.
	 * We want to ignore $action->post_content['stripe_link'].
	 * This way any Stripe action will fall back to Stripe link when the Stripe add on is unavailable.
	 *
	 * @since x.x
	 *
	 * @param string|int $form_id
	 * @return WP_Post|false
	 */
	public static function get_stripe_link_action( $form_id ) {
		$actions = self::get_actions_before_submit( $form_id );
		return reset( $actions );
	}

	/**
	 * Trigger a Stripe payment after a form is submitted.
	 * This is called for both one time and recurring payments.
	 * It is also called when Stripe link is active but the Stripe payment is triggered instead with JavaScript.
	 *
	 * @param WP_Post  $action
	 * @param stdClass $entry
	 * @param stdClass $form
	 * @return array
	 */
	public static function trigger_gateway( $action, $entry, $form ) {
		$response = array(
			'success'      => false,
			'run_triggers' => false,
			'show_errors'  => true,
		);
		$atts     = compact( 'action', 'entry', 'form' );

		$amount = self::prepare_amount( $action->post_content['amount'], $atts );
		if ( empty( $amount ) || $amount == 000 ) {
			$response['error'] = __( 'Please specify an amount for the payment', 'business-directory-plugin' );
			return $response;
		}

		if ( ! self::stripe_is_configured() ) {
			$response['error'] = __( 'There was a problem communicating with Stripe. Please try again.', 'business-directory-plugin' );
			return $response;
		}

		$customer = self::set_customer_with_token( $atts );
		if ( ! is_object( $customer ) ) {
			$response['error'] = $customer;
			return $response;
		}

		$one_time_payment_args = compact( 'customer', 'form', 'entry', 'action', 'amount' );

		WPBDPStrpLinkController::create_pending_stripe_link_payment( $one_time_payment_args );
		$response['show_errors'] = false;
		return $response;
	}

	/**
	 * Check if either Stripe integration is enabled.
	 *
	 * @return bool true if Stripe Connect is set up.
	 */
	private static function stripe_is_configured() {
		return WPBDPStrpAppHelper::call_stripe_helper_class( 'initialize_api' );
	}

	/**
	 * Set a customer object to $_POST['customer'] to use later.
	 *
	 * @param array $atts
	 * @return object|string
	 */
	private static function set_customer_with_token( $atts ) {
		if ( isset( self::$customer ) ) {
			// It's an object if this isn't the first Stripe action running.
			return self::$customer;
		}

		$payment_info = array(
			'user_id' => WPBDPStrpAppHelper::get_user_id_for_current_payment(),
		);

		if ( ! empty( $atts['action']->post_content['email'] ) ) {
			$payment_info['email'] = apply_filters( 'wpbdp_content', $atts['action']->post_content['email'], $atts['form'], $atts['entry'] );
			$payment_info['email'] = self::replace_email_shortcode( $payment_info['email'] );
		}

		self::add_customer_name( $atts, $payment_info );

		$customer       = WPBDPStrpAppHelper::call_stripe_helper_class( 'get_customer', $payment_info );
		self::$customer = $customer; // Set for later use.

		return $customer;
	}

	/**
	 * Replace an [email] shortcode with the current user email.
	 *
	 * @param string $email
	 * @return string
	 */
	private static function replace_email_shortcode( $email ) {
		if ( false === strpos( $email, '[email]' ) ) {
			return $email;
		}

		global $current_user;
		return str_replace(
			'[email]',
			! empty( $current_user->user_email ) ? $current_user->user_email : '',
			$email
		);
	}

	/**
	 * Set the customer name based on the mapped first and last name fields in the Stripe action.
	 *
	 * @since x.x
	 *
	 * @param array $atts
	 * @param array $payment_info
	 * @return void
	 */
	private static function add_customer_name( $atts, &$payment_info ) {
		if ( empty( $atts['action']->post_content['billing_first_name'] ) ) {
			return;
		}

		$name = '[' . $atts['action']->post_content['billing_first_name'] . ' show="first"]';
		if ( ! empty( $atts['action']->post_content['billing_last_name'] ) ) {
			$name .= ' [' . $atts['action']->post_content['billing_last_name'] . ' show="last"]';
		}

		$payment_info['name'] = apply_filters( 'wpbdp_content', $name, $atts['form'], $atts['entry'] );
	}

	/**
	 * Get the trial period from the settings or from the connected entry.
	 *
	 * @since x.x
	 *
	 * @param array $atts Includes 'customer', 'entry', 'action', 'amount'.
	 * @return int The timestamp when the trial should end. 0 for no trial
	 */
	public static function get_trial_end_time( $atts ) {
		$settings = $atts['action']->post_content;
		if ( empty( $settings['trial_interval_count'] ) ) {
			return 0;
		}

		$trial = $settings['trial_interval_count'];
		if ( ! is_numeric( $trial ) ) {
			$trial = WPBDPStrpAppHelper::process_shortcodes(
				array(
					'value' => $trial,
					'entry' => $atts['entry'],
				)
			);
		}

		if ( ! $trial ) {
			return 0;
		}

		return strtotime( '+' . absint( $trial ) . ' days' );
	}

	/**
	 * Convert the amount from 10.00 to 1000.
	 *
	 * @param mixed $amount
	 * @param array $atts
	 * @return string
	 */
	public static function prepare_amount( $amount, $atts = array() ) {
		$amount   = parent::prepare_amount( $amount, $atts );
		$currency = self::get_currency_for_action( $atts );
		return number_format( $amount, $currency['decimals'], '', '' );
	}

	/**
	 * Add defaults for additional Stripe action options.
	 *
	 * @param array $defaults
	 * @return array
	 */
	public static function add_action_defaults( $defaults ) {
		$defaults['plan_id']     = '';
		$defaults['capture']     = '';
		$defaults['stripe_link'] = '';
		return $defaults;
	}

	/**
	 * Create any required Stripe plans, used for subscriptions.
	 *
	 * @param array $settings
	 * @return array
	 */
	public static function create_plans( $settings ) {
		if ( $settings['type'] !== 'recurring' || strpos( $settings['amount'], ']' ) ) {
			// Don't create a plan for one time payments, or for actions with shortcodes in the value.
			$settings['plan_id'] = '';
			return $settings;
		}

		$plan_opts = WPBDPStrpSubscriptionHelper::prepare_plan_options( $settings );
		if ( $plan_opts['id'] != $settings['plan_id'] ) {
			$settings['plan_id'] = WPBDPStrpSubscriptionHelper::maybe_create_plan( $plan_opts );
		}

		return $settings;
	}

	/**
	 * Include settings in the plan description in order to make sure the correct plan is used.
	 *
	 * @param array $settings
	 * @return string
	 */
	public static function create_plan_id( $settings ) {
		$amount = self::prepare_amount( $settings['amount'], $settings );
		$id     = sanitize_title_with_dashes( $settings['description'] ) . '_' . $amount . '_' . $settings['interval_count'] . $settings['interval'] . '_' . $settings['currency'];
		return $id;
	}

	/**
	 * If this form submits with ajax, load the scripts on the first page.
	 *
	 * @param array $params
	 * @return void
	 */
	public static function maybe_load_scripts( $params ) {
		if ( $params['form_id'] == $params['posted_form_id'] ) {
			// This form has already been posted, so we aren't on the first page.
			return;
		}

		//$form = Form::getOne( $params['form_id'] );
		if ( ! $form ) {
			return;
		}

		self::load_scripts( (int) $form->id );
	}

	/**
	 * Load front end JavaScript for a Stripe form.
	 *
	 * @param int $form_id
	 * @return void
	 */
	public static function load_scripts( $form_id ) {
		if ( WPBDP_App_Helper::is_admin_page( 'formidable-entries' ) ) {
			return;
		}

		if ( wp_script_is( 'wpbdp-stripe', 'enqueued' ) ) {
			return;
		}

		$stripe_connect_is_setup = WPBDPStrpConnectHelper::stripe_connect_is_setup();
		if ( ! $stripe_connect_is_setup ) {
			return;
		}

		if ( ! $form_id || ! is_int( $form_id ) ) {
			_doing_it_wrong( __METHOD__, '$form_id parameter must be a non-zero integer', '6.5' );
			return;
		}

		$settings    = WPBDPStrpAppHelper::get_settings();
		$publishable = $settings->get_active_publishable_key();

		wp_register_script(
			'stripe',
			'https://js.stripe.com/v3/',
			array(),
			'3.0',
			false
		);

		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$dependencies = array( 'stripe' );
		$path         = WPBDP_PATH . 'includes/gateways/stripe';

		if ( '.min' === $suffix && is_readable( $path . '/js/wpbdpstrp.min.js' ) ) {
			// Use the combined file if it is available.
			$script_url = WPBDP_App_Helper::plugin_url() . 'includes/gateways/stripe/js/wpbdpstrp.min.js';
		} else {
			if ( ! $suffix && ! is_readable( $path . 'js/wpbdpstrp.js' ) ) {
				// The unminified file is not included in releases so force the minified script.
				$suffix = '.min';
			}
			$script_url = $path . 'js/wpbdpstrp' . $suffix . '.js';
		}

		wp_enqueue_script(
			'wpbdp-stripe',
			$script_url,
			$dependencies,
			WPBDP_VERSION,
			false
		);

		$action_settings = self::prepare_settings_for_js( $form_id );
		$style_settings  = self::get_style_settings_for_form( $form_id );
		$stripe_vars     = array(
			'publishable_key' => $publishable,
			'form_id'         => $form_id,
			'nonce'           => wp_create_nonce( 'wpbdp_strp_ajax' ),
			'ajax'            => esc_url_raw( wpbdp_ajax_url() ),
			'settings'        => $action_settings,
			'locale'          => self::get_locale(),
			'baseFontSize'    => $style_settings['field_font_size'],
			'appearanceRules' => self::get_appearance_rules( $style_settings ),
			'account_id'      => WPBDPStrpConnectHelper::get_account_id(),
		);

		wp_localize_script( 'wpbdp-stripe', 'wpbdp_stripe_vars', $stripe_vars );
	}

	/**
	 * Get the style rules for Stripe link Authentication and Payment elements.
	 * These settings get set to wpbdp_stripe_vars.appearanceRules.
	 * They're in the format that Stripe accepts.
	 * Documentation for Appearance rules can be found at https://stripe.com/docs/elements/appearance-api?platform=web#rules
	 *
	 * @since x.x
	 *
	 * @param array $settings
	 * @return array
	 */
	private static function get_appearance_rules( $settings ) {
		return array(
			'.Input' => array(
				'color'           => $settings['text_color'],
				'backgroundColor' => $settings['bg_color'],
				'padding'         => $settings['field_pad'],
				'lineHeight'      => 1.3,
				'borderColor'     => $settings['border_color'],
				'borderWidth'     => $settings['field_border_width'],
				'borderStyle'     => $settings['field_border_style'],
			),
			'.Input::placeholder' => array(
				'color' => $settings['text_color_disabled'],
			),
			'.Input:focus' => array(
				'backgroundColor' => $settings['bg_color_active'],
			),
			'.Label' => array(
				'color'      => $settings['label_color'],
				'fontSize'   => $settings['font_size'],
				'fontWeight' => $settings['weight'],
			),
			'.Error' => array(
				'color' => $settings['border_color_error'],
			),
		);
	}

	/**
	 * Get the language to use for Stripe elements.
	 *
	 * @since x.x
	 * @return string
	 */
	private static function get_locale() {
		$allowed = array( 'ar', 'da', 'de', 'en', 'es', 'fi', 'fr', 'he', 'it', 'ja', 'nl', 'no', 'pl', 'ru', 'sv', 'zh' );
		$current = get_locale();
		$parts   = explode( '_', $current );
		$part    = strtolower( $parts[0] );
		return in_array( $part, $allowed, true ) ? $part : 'auto';
	}


	// Start TransLite

		/**
	 * Track the entry IDs we're destroying so we don't attempt to delete an entry more than once.
	 * Set in self::destroy_entry_later.
	 *
	 * @var array
	 */
	private static $entry_ids_to_destroy_later = array();

	/**
	 * @since x.x
	 *
	 * @param object $sub
	 * @return void
	 */
	public static function trigger_subscription_status_change( $sub ) {
		$wpbdp_payment = new WPBDPStrpPayment();
		$payment     = $wpbdp_payment->get_one_by( $sub->id, 'sub_id' );

		if ( $payment && $payment->action_id ) {
			self::trigger_payment_status_change(
				array(
					'status'  => $sub->status,
					'payment' => $payment,
				)
			);
		}
	}

	/**
	 * @param array $atts
	 * @return void
	 */
	public static function trigger_payment_status_change( $atts ) {
		$action = isset( $atts['action'] ) ? $atts['action'] : $atts['payment']->action_id;
		$entry_id = isset( $atts['entry'] ) ? $atts['entry']->id : $atts['payment']->item_id;
		$atts = array(
			'trigger'  => $atts['status'],
			'entry_id' => $entry_id,
		);

		if ( ! isset( $atts['payment'] ) ) {
			$wpbdp_payment     = new WPBDPStrpPayment();
			$atts['payment'] = $wpbdp_payment->get_one_by( $entry_id, 'item_id' );
		}

		if ( ! isset( $atts['trigger'] ) ) {
			$atts['trigger'] = $atts['status'];
		}

		// Set future-cancel as trigger when applicable.
		$atts['trigger'] = str_replace( '_', '-', $atts['trigger'] );

		if ( $atts['payment'] ) {
			self::trigger_actions_after_payment( $atts['payment'], $atts );
		}
	}

	/**
	 * These settings are included in wpbdp_stripe_vars.settings global JavaScript object on Stripe forms.
	 *
	 * @param int $form_id
	 * @return array
	 */
	public static function prepare_settings_for_js( $form_id ) {
		$payment_actions = self::get_actions_for_form( $form_id );
		$action_settings = array();
		foreach ( $payment_actions as $payment_action ) {
			$settings_for_action = array(
				'id'         => $payment_action->ID,
				'first_name' => $payment_action->post_content['billing_first_name'],
				'last_name'  => $payment_action->post_content['billing_last_name'],
				'gateways'   => $payment_action->post_content['gateway'],
				'fields'     => self::get_fields_for_price( $payment_action ),
				'one'        => $payment_action->post_content['type'],
				'email'      => $payment_action->post_content['email'],
			);

			/**
			 * @param array   $settings_for_action
			 * @param WP_Post $payment_action
			 */
			$settings_for_action = apply_filters( 'wpbdp_trans_settings_for_js', $settings_for_action, $payment_action );
			$action_settings[] = $settings_for_action;
		}

		return $action_settings;
	}
}
