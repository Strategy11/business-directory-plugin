<?php

/**
 * Onboarding Wizard Controller class.
 *
 * @package Business Directory Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Handles the Onboarding Wizard page in the admin area.
 *
 * @since 6.4.8
 */
final class WPBDP_Onboarding_Wizard {

	/**
	 * The slug of the Onboarding Wizard page.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'wpbdp-onboarding-wizard';

	/**
	 * Transient name used for managing redirection to the Onboarding Wizard page.
	 *
	 * @var string
	 */
	const TRANSIENT_NAME = 'wpbdp_activation_redirect';

	/**
	 * Transient value associated with the redirection to the Onboarding Wizard page.
	 * Used when activating a single plugin.
	 *
	 * @var string
	 */
	const TRANSIENT_VALUE = 'wpbdp-welcome';

	/**
	 * Transient value associated with the redirection to the Onboarding Wizard page.
	 * Used when activating multiple plugins at once.
	 *
	 * @var string
	 */
	const TRANSIENT_MULTI_VALUE = 'wpbdp-welcome-multi';

	/**
	 * Option name for storing the redirect status for the Onboarding Wizard page.
	 *
	 * @var string
	 */
	const REDIRECT_STATUS_OPTION = 'wpbdp_welcome_redirect';

	/**
	 * Option name for tracking if the onboarding wizard was skipped.
	 *
	 * @var string
	 */
	const ONBOARDING_SKIPPED_OPTION = 'wpbdp_onboarding_skipped';

	/**
	 * Defines the initial step for redirection within the application flow.
	 *
	 * @var string
	 */
	const INITIAL_STEP = 'consent-tracking';

	/**
	 * Holds the URL to access the Onboarding Wizard's page.
	 *
	 * @var string
	 */
	private static $page_url = '';

	/**
	 * Path to views.
	 *
	 * @var string
	 */
	private $view_path = '';

	/**
	 * Initialize hooks for template page only.
	 *
	 * @since 6.4.8
	 */
	public function load_admin_hooks() {
		$this->set_page_url();

		add_action( 'admin_init', array( $this, 'do_admin_redirects' ) );

		// Load page if admin page is Onboarding Wizard.
		$this->maybe_load_page();
	}

	/**
	 * Performs a safe redirect to the welcome screen when the plugin is activated.
	 * On single activation, we will redirect immediately.
	 * When activating multiple plugins, the redirect is delayed until a BD page is loaded.
	 *
	 * @return void
	 */
	public function do_admin_redirects() {
		$current_page = wpbdp_get_var(
			array(
				'param'    => 'page',
				'sanitize' => 'sanitize_title',
			)
		);

		// Prevent endless loop.
		if ( $current_page === self::PAGE_SLUG ) {
			return;
		}

		// Only do this for single site installs.
		if ( is_network_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->mark_onboarding_as_skipped();
			return;
		}

		if ( $this->has_onboarding_been_skipped() || WPBDP_Admin_Education::is_installed( 'premium' ) ) {
			return;
		}

		$transient_value = get_transient( self::TRANSIENT_NAME );
		if ( ! in_array( $transient_value, array( self::TRANSIENT_VALUE, self::TRANSIENT_MULTI_VALUE ), true ) ) {
			return;
		}

		if ( isset( $_GET['activate-multi'] ) ) {
			/**
			 * $_GET['activate-multi'] is set after activating multiple plugins.
			 * In this case, change the transient value so we know for future checks.
			 */
			set_transient( self::TRANSIENT_NAME, self::TRANSIENT_MULTI_VALUE, 60 );
			return;
		}

		if ( self::TRANSIENT_MULTI_VALUE === $transient_value && ! WPBDP_App_Helper::is_bd_page() ) {
			// For multi-activations we want to only redirect when a user loads a BD page.
			return;
		}

		set_transient( self::TRANSIENT_NAME, 'no', 60 );

		// Prevent redirect with every activation.
		if ( $this->has_already_redirected() ) {
			return;
		}

		// Redirect to the onboarding wizard's initial step.
		$page_url = add_query_arg( 'step', self::INITIAL_STEP, self::$page_url );
		if ( wp_safe_redirect( esc_url_raw( $page_url ) ) ) {
			exit;
		}
	}

	/**
	 * Initializes the Onboarding Wizard setup if on its designated admin page.
	 *
	 * @since 6.4.8
	 *
	 * @return void
	 */
	public function maybe_load_page() {
		add_action( 'wp_ajax_wpbdp_onboarding_consent_tracking', array( $this, 'ajax_consent_tracking' ) );

		if ( $this->is_onboarding_wizard_page() ) {
			$this->view_path = WPBDP_PATH . 'includes/admin/views/onboarding-wizard/';

			add_action( 'admin_menu', array( $this, 'menu' ), 99 );
			add_action( 'wpbdp_enqueue_admin_scripts', array( $this, 'enqueue_assets' ), 15 );
			add_action( 'admin_head', array( $this, 'remove_menu' ) );

			add_filter( 'admin_body_class', array( $this, 'add_admin_body_classes' ), 999 );
			add_filter( 'wpbdp_enqueue_floating_links', '__return_false' );
		}
	}

	/**
	 * Add Onboarding Wizard menu item to sidebar and define index page.
	 *
	 * @since 6.4.8
	 *
	 * @return void
	 */
	public function menu() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$label = __( 'Onboarding Wizard', 'business-directory-plugin' );

		add_submenu_page(
			'wpbdp_admin',
			__( 'Business Directory', 'business-directory-plugin' ) . ' | ' . $label,
			$label,
			wpbdp_backend_minimim_role(),
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Renders the Onboarding Wizard page in the WordPress admin area.
	 *
	 * @since 6.4.8
	 *
	 * @return void
	 */
	public function render() {
		if ( $this->has_onboarding_been_skipped() ) {
			delete_option( self::ONBOARDING_SKIPPED_OPTION );
			$this->has_already_redirected();
		}

		// Include SVG images for icons.
		WPBDP_App_Helper::include_svg();

		$view_path = $this->get_view_path();

		// Note: Add step parts in order.
		$step_parts = array(
			'consent-tracking' => 'steps/consent-tracking-step.php',
			'success'          => 'steps/success-step.php',
		);

		include $view_path . 'index.php';
	}

	/**
	 * Handle AJAX request to setup the "Never miss an important update" step.
	 *
	 * @since 6.4.8
	 *
	 * @return void
	 */
	public function ajax_consent_tracking() {
		// Check permission and nonce.
		WPBDP_App_Helper::permission_check();
		check_ajax_referer( 'wpbdp_ajax', 'nonce' );

		update_option( 'wpbdp-show-tracking-pointer', 0, 'no' );
		wpbdp_set_option( 'tracking-on', true );

		$this->subscribe_to_active_campaign();

		// Send response.
		wp_send_json_success();
	}

	/**
	 * When the user consents to receiving news of updates, subscribe their email to ActiveCampaign.
	 *
	 * @since 6.4.8
	 *
	 * @return void
	 */
	private function subscribe_to_active_campaign() {
		$user = wp_get_current_user();

		if ( empty( $user->user_email ) ) {
			return;
		}

		wp_remote_post(
			'https://feedback.strategy11.com/wp-json/frm/v2/entries',
			array(
				'body' => array(
					'bd-firstname1' => $user->first_name,
					'bd-email-1'    => $user->user_email,
					'form_id'       => 'bd-plugin-course',
				),
			)
		);
	}

	/**
	 * Enqueues the Onboarding Wizard page scripts and styles.
	 *
	 * @since 6.4.8
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style( self::PAGE_SLUG, WPBDP_ASSETS_URL . '/css/onboarding-wizard.min.css', array(), WPBDP_VERSION );

		// Register and enqueue Onboarding Wizard script.
		wp_register_script( self::PAGE_SLUG, WPBDP_ASSETS_URL . '/js/onboarding-wizard.min.js', array( 'wp-i18n' ), WPBDP_VERSION, true );
		wp_localize_script( self::PAGE_SLUG, 'wpbdpOnboardingWizardVars', $this->get_js_variables() );
		wp_enqueue_script( self::PAGE_SLUG );

		/**
		 * Fires after the Onboarding Wizard enqueue assets.
		 *
		 * @since 6.4.8
		 */
		do_action( 'wpbdp_onboarding_wizard_enqueue_assets' );
	}

	/**
	 * Get the Onboarding Wizard JS variables as an array.
	 *
	 * @since 6.4.8
	 *
	 * @return array
	 */
	private function get_js_variables() {
		return array(
			'INITIAL_STEP' => self::INITIAL_STEP,
		);
	}

	/**
	 * Remove the Onboarding Wizard submenu page from the bD parent menu
	 * since it is not necessary to show that link there.
	 *
	 * @since 6.4.8
	 *
	 * @return void
	 */
	public function remove_menu() {
		remove_submenu_page( 'wpbdp_admin', self::PAGE_SLUG );
	}

	/**
	 * Adds custom classes to the existing string of admin body classes.
	 *
	 * The function appends a custom class to the existing admin body classes, enabling full-screen mode for the admin interface.
	 *
	 * @since 6.4.8
	 *
	 * @param string $classes Existing body classes.
	 *
	 * @return string Updated list of body classes, including the newly added classes.
	 */
	public function add_admin_body_classes( $classes ) {
		return $classes . ' wpbdp-admin-full-screen';
	}

	/**
	 * Checks if the Onboarding Wizard was skipped during the plugin's installation.
	 *
	 * @since 6.4.8
	 *
	 * @return bool True if the Onboarding Wizard was skipped, false otherwise.
	 */
	public function has_onboarding_been_skipped() {
		return get_option( self::ONBOARDING_SKIPPED_OPTION, false );
	}

	/**
	 * Marks the Onboarding Wizard as skipped to prevent automatic redirects to the wizard.
	 *
	 * @since 6.4.8
	 *
	 * @return void
	 */
	public function mark_onboarding_as_skipped() {
		update_option( self::ONBOARDING_SKIPPED_OPTION, true, 'no' );
	}

	/**
	 * Check if the current page is the Onboarding Wizard page.
	 *
	 * @since 6.4.8
	 *
	 * @return bool True if the current page is the Onboarding Wizard page, false otherwise.
	 */
	public function is_onboarding_wizard_page() {
		return WPBDP_App_Helper::is_admin_page( self::PAGE_SLUG );
	}

	/**
	 * Checks if the plugin has already performed a redirect to avoid repeated redirections.
	 *
	 * @return bool Returns true if already redirected, otherwise false.
	 */
	private function has_already_redirected() {
		if ( get_option( self::REDIRECT_STATUS_OPTION ) ) {
			return true;
		}

		update_option( self::REDIRECT_STATUS_OPTION, WPBDP_VERSION, 'no' );
		return false;
	}

	/**
	 * Get the path to the Onboarding Wizard views.
	 *
	 * @since 6.4.8
	 *
	 * @return string Path to views.
	 */
	public static function get_page_url() {
		return self::$page_url;
	}

	/**
	 * Set the URL to access the Onboarding Wizard's page.
	 *
	 * @return void
	 */
	private function set_page_url() {
		self::$page_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * Get the path to the Onboarding Wizard views.
	 *
	 * @since 6.4.8
	 *
	 * @return string Path to views.
	 */
	public function get_view_path() {
		return $this->view_path;
	}
}
