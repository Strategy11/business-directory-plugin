<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class WPBDPStrpConnectHelper {

	/**
	 * Track the latest error when calling stripe connect.
	 *
	 * @since 6.4.9
	 *
	 * @var string|null
	 */
	public static $latest_error_from_stripe_connect;

	/**
	 * @return void
	 */
	public static function check_for_stripe_connect_webhooks() {
		if ( wp_doing_ajax() ) {
			self::check_for_stripe_connect_ajax_actions();
		} elseif ( self::user_landed_on_the_oauth_return_url() && wpbdp_user_can_access_backend() ) {
			self::redirect_oauth();
		}
	}

	/**
	 * @return void
	 */
	private static function check_for_stripe_connect_ajax_actions() {
		$action = wpbdp_get_var( array( 'param' => 'action' ), 'post' );
		$prefix = 'wpbdp_stripe_connect_';

		if ( ! $action || 0 !== strpos( $action, $prefix ) ) {
			if ( 'wpbdp_strp_connect_get_settings_button' === $action ) {
				WPBDP_App_Helper::permission_check( wpbdp_backend_minimum_role() );
				self::render_settings();
			}
			return;
		}

		WPBDP_App_Helper::permission_check( wpbdp_backend_minimum_role() );

		$action   = str_replace( $prefix, '', $action );
		$function = 'handle_' . $action;

		if ( ! is_callable( self::class . '::' . $function ) || ! check_admin_referer( 'wpbdp_ajax', 'nonce' ) ) {
			wp_send_json_error();
		}

		self::$function();
	}

	/**
	 * @return bool
	 */
	private static function user_landed_on_the_oauth_return_url() {
		return isset( $_GET['wpbdp_stripe_connect_return_oauth'] );
	}

	/**
	 * Generate a new client password for authenticating with Connect Service and save it locally as an option.
	 *
	 * @param string $mode 'live' or 'test'.
	 *
	 * @return string the client password.
	 */
	private static function generate_client_password( $mode ) {
		$client_password = wp_generate_password();
		update_option( self::get_client_side_token_option_name( $mode ), $client_password, false );
		return $client_password;
	}

	/**
	 * @param string $action
	 * @param array  $additional_body
	 *
	 * @return array|string
	 * @return object|string
	 */
	private static function post_to_connect_server( $action, $additional_body = array() ) {
		$body    = array(
			'wpbdp_strp_connect_action' => $action,
			'wpbdp_strp_connect_mode'   => WPBDPStrpAppHelper::active_mode(),
		);
		$body    = array_merge( $body, $additional_body );
		$url     = self::get_url_to_connect_server();
		$headers = self::build_headers_for_post();

		if ( ! $headers ) {
			return 'Unable to build headers for post. Is your pro license configured properly?';
		}

		$timeout = 45; // (seconds) default timeout is 5. we want a bit more time to work with.
		self::try_to_extend_server_timeout( $timeout );

		$args     = compact( 'body', 'headers', 'timeout' );
		$response = wp_remote_post( $url, $args );

		if ( ! self::validate_response( $response ) ) {
			return 'Response from server is invalid';
		}

		$body = self::pull_response_body( $response );
		if ( empty( $body->success ) ) {
			if ( ! empty( $body->data ) && is_string( $body->data ) ) {
				return $body->data;
			}
			return 'Response from server was not successful';
		}

		return isset( $body->data ) ? $body->data : array();
	}

	/**
	 * Try to make sure the server time limit exceeds the request time limit.
	 *
	 * @param int $timeout seconds.
	 *
	 * @return void
	 */
	private static function try_to_extend_server_timeout( $timeout ) {
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( $timeout + 10 );
		}
	}

	/**
	 * @param string $mode either 'auto', 'live', or 'test'.
	 *
	 * @return string either _test or _live.
	 */
	private static function get_active_mode_option_name_suffix( $mode = 'auto' ) {
		if ( 'auto' !== $mode ) {
			return '_' . $mode;
		}
		return '_' . WPBDPStrpAppHelper::active_mode();
	}

	/**
	 * @param string $key 'account_id', 'client_password', 'server_password', 'details_submitted'.
	 * @param string $mode either 'auto', 'live', or 'test'.
	 *
	 * @return string
	 */
	private static function get_strp_connect_option_name( $key, $mode = 'auto' ) {
		return 'wpbdp_strp_' . $key . self::get_active_mode_option_name_suffix( $mode );
	}

	/**
	 * @param string $mode either 'auto', 'live', or 'test'.
	 *
	 * @return string
	 */
	private static function get_account_id_option_name( $mode = 'auto' ) {
		return self::get_strp_connect_option_name( 'account_id', $mode );
	}

	/**
	 * @param string $mode either 'auto', 'live', or 'test'.
	 *
	 * @return string
	 */
	private static function get_client_side_token_option_name( $mode = 'auto' ) {
		return self::get_strp_connect_option_name( 'client_password', $mode );
	}

	/**
	 * @param string $mode either 'auto', 'live', or 'test'.
	 *
	 * @return string
	 */
	private static function get_server_side_token_option_name( $mode = 'auto' ) {
		return self::get_strp_connect_option_name( 'server_password', $mode );
	}

	/**
	 * @param string $mode either 'auto', 'live', or 'test'.
	 *
	 * @return string
	 */
	private static function get_stripe_details_submitted_option_name( $mode = 'auto' ) {
		return self::get_strp_connect_option_name( 'details_submitted', $mode );
	}

	/**
	 * @return string
	 */
	private static function get_url_to_connect_server() {
		return 'https://api.businessdirectoryplugin.com/';
	}

	/**
	 * @return void
	 */
	private static function handle_disconnect() {
		self::disconnect();
		self::reset_stripe_connect_integration();
		wp_send_json_success();
	}

	/**
	 * Delete every Stripe connect option, calling when disconnecting.
	 *
	 * @return void
	 */
	public static function reset_stripe_connect_integration() {
		$mode = self::get_mode_value_from_post();
		delete_option( self::get_account_id_option_name( $mode ) );
		delete_option( self::get_server_side_token_option_name( $mode ) );
		delete_option( self::get_client_side_token_option_name( $mode ) );
		delete_option( self::get_stripe_details_submitted_option_name( $mode ) );
	}

	/**
	 * @return false|object
	 */
	private static function disconnect() {
		$additional_body = array(
			'wpbdp_strp_connect_mode' => self::get_mode_value_from_post(),
		);
		return self::post_with_authenticated_body( 'disconnect', $additional_body );
	}

	/**
	 * @since 6.4.9
	 *
	 * @return bool
	 */
	public static function at_least_one_mode_is_setup() {
		return self::stripe_connect_is_setup( 'test' ) || self::stripe_connect_is_setup( 'live' );
	}

	/**
	 * @return void
	 */
	private static function handle_reauth() {
		$additional_body = array(
			'wpbdp_strp_connect_mode' => self::get_mode_value_from_post(),
		);
		$data            = self::post_with_authenticated_body( 'reauth', $additional_body );

		if ( false === $data ) {
			// check account status
			if ( self::check_server_for_connected_account_status() ) {
				wp_send_json_success();
			}
			wp_send_json_error();
		}

		$response_data = array(
			'connect_url' => $data->connect_url,
		);
		wp_send_json_success( $response_data );
	}

	/**
	 * @return array
	 */
	private static function get_standard_authenticated_body() {
		$mode = self::get_mode_value_from_post();
		return array(
			'account_id'      => get_option( self::get_account_id_option_name( $mode ) ),
			'server_password' => get_option( self::get_server_side_token_option_name( $mode ) ),
			'client_password' => get_option( self::get_client_side_token_option_name( $mode ) ),
		);
	}

	private static function redirect_oauth() {
		$connected = self::check_server_for_oauth_account_id();
		header( 'Location: ' . self::get_url_for_stripe_settings( $connected ), true, 302 );
		exit;
	}

	/**
	 * @return bool
	 */
	private static function check_server_for_oauth_account_id() {
		$mode = wpbdp_get_var( array( 'param' => 'mode' ) );
		if ( 'live' !== $mode ) {
			$mode = 'test';
		}

		if ( self::get_account_id( $mode ) ) {
			// Do not allow for initialize if there is already a configured account id.
			return false;
		}

		$body = array(
			'server_password'         => get_option( self::get_server_side_token_option_name( $mode ) ),
			'client_password'         => get_option( self::get_client_side_token_option_name( $mode ) ),
			'wpbdp_strp_connect_mode' => $mode,
		);
		$data = self::post_to_connect_server( 'oauth_account_status', $body );

		if ( is_object( $data ) && ! empty( $data->account_id ) ) {
			update_option( self::get_account_id_option_name( $mode ), $data->account_id, false );

			if ( ! empty( $data->details_submitted ) ) {
				self::set_stripe_details_as_submitted( $mode );
			}

			// Enable Stripe when we successfully connect if it is not already enabled.
			// This is required because we redirect after connecting, and the toggle is
			// never saved.
			$wpbdp_settings = new WPBDP__Settings();
			if ( ! $wpbdp_settings->get_option( 'stripe' ) ) {
				$wpbdp_settings->set_option( 'stripe', '1' );
			}

			return true;
		}

		return false;
	}

	/**
	 * On a successful account status check, set details_submitted option.
	 *
	 * @param string $mode 'live' or 'test'.
	 *
	 * @return void
	 */
	private static function set_stripe_details_as_submitted( $mode ) {
		update_option( self::get_stripe_details_submitted_option_name( $mode ), true, false );
	}

	/**
	 * @return void
	 */
	private static function handle_oauth() {
		$response_data = array(
			'redirect_url' => self::get_oauth_redirect_url(),
		);
		wp_send_json_success( $response_data );
	}

	/**
	 * @return string|void
	 */
	private static function get_oauth_redirect_url() {
		$mode = self::get_mode_value_from_post();

		if ( self::get_account_id( $mode ) ) {
			// Do not allow for initialize if there is already a configured account id.
			wp_send_json_error( array( 'message' => 'Cannot initialize another account' ) );
		}

		$additional_body = array(
			'password'                => self::generate_client_password( $mode ),
			'user_id'                 => get_current_user_id(),
			'wpbdp_strp_connect_mode' => $mode,
		);

		// Clear the transient so it doesn't fail.
		delete_option( 'wpbdp_stripe_lite_last_verify_attempt' );
		$data = self::post_to_connect_server( 'oauth_request', $additional_body );

		if ( is_string( $data ) ) {
			wp_send_json_error( array( 'message' => wp_strip_all_tags( $data ) ) );
		}

		if ( ! empty( $data->password ) ) {
			update_option( self::get_server_side_token_option_name( $mode ), $data->password, false );
		}

		if ( ! is_object( $data ) || empty( $data->redirect_url ) ) {
			$error = 'No redirect url in response.';
			if ( is_string( $data ) ) {
				$error .= ' ' . wp_strip_all_tags( $data );
			}
			wp_send_json_error( array( 'message' => $error ) );
		}

		return $data->redirect_url;
	}

	/**
	 * @return bool true if our account is onboarded
	 */
	public static function check_server_for_connected_account_status() {
		$mode = wpbdp_get_var( array( 'param' => 'mode' ) );
		if ( 'live' !== $mode ) {
			$mode = 'test';
		}
		$additional_body = array(
			'wpbdp_strp_connect_mode' => $mode,
		);
		$data            = self::post_with_authenticated_body( 'account_status', $additional_body );
		$success         = false !== $data && ! empty( $data->details_submitted );
		if ( $success ) {
			self::set_stripe_details_as_submitted( $mode );
		}
		return $success;
	}

	/**
	 * @param string $mode
	 *
	 * @return bool
	 */
	public static function stripe_connect_is_setup( $mode = 'auto' ) {
		return get_option( self::get_stripe_details_submitted_option_name( $mode ) );
	}

	/**
	 * @param mixed $response
	 *
	 * @return bool
	 */
	private static function validate_response( $response ) {
		return ! is_wp_error( $response ) && is_array( $response ) && isset( $response['http_response'] );
	}

	private static function pull_response_body( $response ) {
		$http_response   = $response['http_response'];
		$response_object = $http_response->get_response_object();
		return json_decode( $response_object->body );
	}

	/**
	 * @return array
	 */
	private static function build_headers_for_post() {
		$password = self::maybe_get_pro_license();
		if ( false === $password ) {
			$password = 'lite_' . self::get_uuid();
		}

		$site_url = home_url();
		$site_url = self::maybe_fix_wpml_url( $site_url );
		$site_url = preg_replace( '#^https?://#', '', $site_url ); // remove protocol from url (our url cannot include the colon).
		$site_url = preg_replace( '/:[0-9]+/', '', $site_url );    // remove port from url (mostly helpful in development)
		$site_url = self::strip_lang_from_url( $site_url );

		// $password is either a Pro license or a uuid (See WPBDP_SiteTracking::uuid).
		return array(
			'Authorization' => 'Basic ' . base64_encode( $site_url . ':' . $password ),
		);
	}

	/**
	 * WPML alters the output of home_url.
	 * If it is active, use the WPML "absolute home" URL which is not modified.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	private static function maybe_fix_wpml_url( $url ) {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && ! ICL_PLUGIN_INACTIVE && class_exists( 'SitePress' ) ) {
			global $wpml_url_converter;
			$url = $wpml_url_converter->get_abs_home();
		}
		return $url;
	}

	/**
	 * WPML might add a language to the url. Don't send that to the server.
	 */
	private static function strip_lang_from_url( $url ) {
		$split_on_language = explode( '/?lang=', $url );
		if ( 2 === count( $split_on_language ) ) {
			$url = $split_on_language[0];
		}
		return $url;
	}

	/**
	 * Get a Pro license when Pro is active.
	 * Otherwise we'll use a uuid to support Lite.
	 *
	 * @return false|string
	 */
	private static function maybe_get_pro_license() {
		$license = wpbdp_get_option( 'license-key-module-business-directory-premium' );
		return is_string( $license ) ? $license : false;
	}

	/**
	 * Get a unique ID to use for connecting Lite users.
	 *
	 * @return string
	 */
	private static function get_uuid() {
		$usage = new WPBDP_SiteTracking();
		$hash  = $usage->site_hash();

		// Make sure that the hash cannot be guessed if it was in the legacy format.
		if ( $hash === sha1( site_url() ) ) {
			delete_option( 'wpbdp-site_tracking_hash' );
			$hash = $usage->site_hash();
		}

		return $hash;
	}

	/**
	 * @param bool $connected
	 *
	 * @return string
	 */
	private static function get_url_for_stripe_settings( $connected ) {
		return admin_url( 'admin.php?page=wpbdp_settings&tab=payment&subtab=gateway_stripe&connected=' . intval( $connected ) );
	}

	/**
	 * @return void
	 */
	public static function render_stripe_connect_settings_container() {
		self::register_settings_scripts();
		WPBDP_App_Helper::include_svg();
		?>
		<div id="wpbdp_strp_settings_container"></div>
		<div id="wpbdp_strp_connect_error" class="wpbdp-hidden"></div>
		<?php
	}

	/**
	 * @return void
	 */
	private static function render_settings() {
		$modes = array( 'test', 'live' );
		$html  = '';
		foreach ( $modes as $mode ) {
			$account_id = self::get_account_id( $mode );
			$connected  = self::stripe_connect_is_setup( $mode );
			$test       = 'test' === $mode;
			$title      = $test ? __( 'TEST', 'business-directory-plugin' ) : __( 'LIVE', 'business-directory-plugin' );

			ob_start();
			require WPBDP_PATH . 'includes/gateways/stripe/views/connect-settings.php';
			$html .= ob_get_contents();
			ob_end_clean();
		}

		$response_data = array(
			'html' => $html,
		);
		wp_send_json_success( $response_data );
	}

	/**
	 * @return void
	 */
	public static function stripe_icon() {
		?>
		<svg height="16" aria-hidden="true" style="vertical-align:text-bottom" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M155.3 154.6c0-22.3 18.6-30.9 48.4-30.9a320 320 0 01141.9 36.7V26.1A376.2 376.2 0 00203.8 0C88.1 0 11 60.4 11 161.4c0 157.9 216.8 132.3 216.8 200.4 0 26.4-22.9 34.9-54.7 34.9-47.2 0-108.2-19.5-156.1-45.5v128.5a396 396 0 00156 32.4c118.6 0 200.3-51 200.3-153.6 0-170.2-218-139.7-218-203.9z"/></svg><?php // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong ?>
		<?php
	}

	/**
	 * Check $_POST for live or test mode value as it can be updated in real time from Stripe Settings and can be configured before the update is saved.
	 *
	 * @return string 'test' or 'live'
	 */
	private static function get_mode_value_from_post() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST ) || ! array_key_exists( 'testMode', $_POST ) ) {
			return WPBDPStrpAppHelper::active_mode();
		}
		$test_mode = wpbdp_get_var( array( 'param' => 'testMode', 'sanitize' => 'absint' ), 'post' );
		return $test_mode ? 'test' : 'live';
	}

	/**
	 * @return void
	 */
	private static function register_settings_scripts() {
		wp_register_script( 'wpbdp_stripe_settings', WPBDP_App_Helper::plugin_url() . 'includes/gateways/stripe/js/connect-settings.js', array( 'wpbdp-admin-js' ), WPBDP_VERSION, true );
		wp_enqueue_script( 'wpbdp_stripe_settings' );
	}

	/**
	 * @param string $mode
	 *
	 * @return bool|string
	 */
	public static function get_account_id( $mode = 'auto' ) {
		return get_option( self::get_account_id_option_name( $mode ) );
	}

	/**
	 * @param array $options
	 *
	 * @return false|string
	 */
	public static function get_customer_id( $options ) {
		$data    = self::post_with_authenticated_body( 'get_customer', compact( 'options' ) );
		$success = false !== $data;
		if ( ! $success ) {
			return self::latest_error_message();
		}
		if ( empty( $data->customer_id ) ) {
			return false;
		}
		return $data->customer_id;
	}

	/**
	 * @param string $customer_id
	 *
	 * @return bool
	 */
	public static function validate_customer( $customer_id ) {
		$data = self::post_with_authenticated_body( 'validate_customer', compact( 'customer_id' ) );
		return is_object( $data ) && ! empty( $data->valid );
	}

	/**
	 * @param string $action
	 * @param array  $additional_body
	 *
	 * @return false|object
	 */
	private static function post_with_authenticated_body( $action, $additional_body = array() ) {
		$body     = array_merge( self::get_standard_authenticated_body(), $additional_body );
		$response = self::post_to_connect_server( $action, $body );
		if ( is_object( $response ) ) {
			return $response;
		}
		if ( is_array( $response ) ) {
			// reformat empty arrays as empty objects
			// if the response is an array, it's because it's empty. Everything with data is already an object.
			return new stdClass();
		}
		if ( is_string( $response ) ) {
			self::$latest_error_from_stripe_connect = $response;
			wpbdp_insert_log(
				array(
					'log_type' => 'stripe.connect',
					'message'  => 'Stripe Connect Error',
					'data'     => array( 'response' => wp_strip_all_tags( $response ) ),
				)
			);
		} else {
			self::$latest_error_from_stripe_connect = '';
		}
		return false;
	}

	/**
	 * @return false|string
	 */
	private static function latest_error_message() {
		return self::$latest_error_from_stripe_connect ? self::$latest_error_from_stripe_connect : false;
	}

	/**
	 * @param string $charge_id
	 *
	 * @return mixed
	 */
	public static function get_charge( $charge_id ) {
		$data = self::post_with_authenticated_body( 'get_charge', compact( 'charge_id' ) );
		if ( ! $data ) {
			return false;
		}
		return $data;
	}

	/**
	 * @param array $new_charge
	 *
	 * @return mixed
	 */
	public static function create_subscription( $new_charge ) {
		$data = self::post_with_authenticated_body( 'create_subscription', compact( 'new_charge' ) );
		if ( is_object( $data ) ) {
			return $data;
		}

		$error = self::latest_error_message();
		if ( 0 === strpos( $error, 'No such plan: ' ) ) {
			return $error;
		}

		return false;
	}

	/**
	 * @param string       $sub_id
	 * @param false|string $customer_id if specified, this will enforce a customer id match (bypassed for users with administrator permission).
	 *
	 * @return bool
	 */
	public static function cancel_subscription( $sub_id, $customer_id = false ) {
		$data     = self::post_with_authenticated_body( 'cancel_subscription', compact( 'sub_id', 'customer_id' ) );
		$canceled = false !== $data;
		return $canceled;
	}

	/**
	 * @param string $event_id
	 *
	 * @return false|object|string
	 */
	public static function get_event( $event_id ) {
		$event = wp_cache_get( $event_id, 'wpbdp_strp' );
		if ( is_object( $event ) ) {
			return $event;
		}

		$event = self::post_with_authenticated_body( 'get_event', compact( 'event_id' ) );
		if ( ! $event || empty( $event->event ) ) {
			return self::latest_error_message();
		}

		wp_cache_set( $event_id, $event->event, 'wpbdp_strp' );
		return $event->event;
	}

	/**
	 * @return array|false
	 */
	public static function get_events( $data ) {
		$result = self::post_with_authenticated_body( 'get_events', compact( 'data' ) );
		if ( ! is_object( $result ) || empty( $result->events ) ) {
			return false;
		}
		return $result->events;
	}

	/**
	 * @param string $event_id
	 *
	 * @return mixed
	 */
	public static function process_event( $event_id ) {
		return self::post_with_authenticated_body( 'process_event', compact( 'event_id' ) );
	}

	/**
	 * @param string $plan_id
	 *
	 * @return false|object
	 */
	public static function get_plan( $plan_id ) {
		$plan = self::post_with_authenticated_body( 'get_plan', compact( 'plan_id' ) );
		return is_object( $plan ) ? $plan : false;
	}

	/**
	 * @param array $plan
	 *
	 * @return mixed
	 */
	public static function create_plan( $plan ) {
		return self::post_with_authenticated_body( 'create_plan', compact( 'plan' ) );
	}

	/**
	 * @return array
	 */
	public static function get_unprocessed_event_ids() {
		$data = self::post_with_authenticated_body( 'get_unprocessed_event_ids' );
		if ( false === $data || empty( $data->event_ids ) ) {
			return array();
		}
		return $data->event_ids;
	}

	/**
	 * Create a session for a Stripe checkout and get the page url.
	 *
	 * @param string $session New session data.
	 *
	 * @return false|object
	 */
	public static function create_checkout_session( $session ) {
		$data = self::post_with_authenticated_body( 'create_checkout_session', compact( 'session' ) );
		if ( false === $data || ! is_object( $data ) ) {
			return false;
		}
		return $data;
	}

	/**
	 * @param array $data
	 *
	 * @return false|object
	 */
	public static function create_coupon( $data ) {
		$result = self::post_with_authenticated_body( 'create_coupon', compact( 'data' ) );
		return is_object( $result ) ? $result : false;
	}

	/**
	 * Verify a site identifier is a match.
	 */
	public static function verify() {
		$option_name  = 'wpbdp_stripe_lite_last_verify_attempt';
		$last_request = get_option( $option_name );

		if ( $last_request && $last_request > strtotime( '-1 day' ) ) {
			wp_send_json_error( 'Too many requests' );
		}

		$site_identifier = wpbdp_get_var( array( 'param' => 'site_identifier' ), 'post' );
		$usage           = new WPBDP_SiteTracking();
		$uuid            = $usage->site_hash();

		update_option( $option_name, time(), false );

		if ( $site_identifier === $uuid ) {
			wp_send_json_success();
		}
		wp_send_json_error();
	}
}
