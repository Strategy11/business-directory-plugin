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
 * @since x.x
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
	 * Option name to store usage data.
	 *
	 * @var string
	 */
	const USAGE_DATA_OPTION = 'wpbdp_onboarding_usage_data';

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
	private static $view_path = '';

	/**
	 * Upgrade URL.
	 *
	 * @var string
	 */
	private static $upgrade_link = '';

	/**
	 * Initialize hooks for template page only.
	 *
	 * @since x.x
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
	 * When activating multiple plugins, the redirect is delayed until a Formidable page is loaded.
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
			// For multi-activations we want to only redirect when a user loads a Formidable page.
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
	 * @since x.x
	 *
	 * @return void
	 */
	public function maybe_load_page() {
		if ( $this->is_onboarding_wizard_page() ) {
			add_action( 'admin_menu', array( $this, 'menu' ), 99 );
			add_action( 'admin_init', array( $this, 'assign_properties' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 15 );
			add_action( 'admin_head', array( $this, 'remove_menu' ) );

			add_filter( 'admin_body_class', array( $this, 'add_admin_body_classes' ), 999 );
		}
	}

	/**
	 * Initializes class properties with essential values for operation.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	public function assign_properties() {
		self::$view_path = WPBDP_PATH . 'includes/admin/views/onboarding-wizard/';
	}

	/**
	 * Add Onboarding Wizard menu item to sidebar and define index page.
	 *
	 * @since x.x
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
	 * @since x.x
	 *
	 * @return void
	 */
	public function render() {
		if ( $this->has_onboarding_been_skipped() ) {
			delete_option( self::ONBOARDING_SKIPPED_OPTION );
			$this->has_already_redirected();
		}

		// Include SVG images for icons.
		FrmAppHelper::include_svg();

		$view_path        = self::get_view_path();
		$available_addons = self::get_available_addons();
		$upgrade_link     = self::get_upgrade_link();
		$addons_count     = FrmAddonsController::get_addons_count();
		$license_key      = base64_decode( rawurldecode( FrmAppHelper::get_param( 'key', '', 'request', 'sanitize_text_field' ) ) );
		$pro_is_installed = FrmAppHelper::pro_is_installed();

		// Note: Add step parts in order.
		$step_parts = array(
			'consent-tracking' => 'steps/consent-tracking-step.php',
			'install-addons'   => 'steps/install-addons-step.php',
			'success'          => 'steps/success-step.php',
			'unsuccessful'     => 'steps/unsuccessful-step.php',
		);

		include $view_path . 'index.php';
	}

	/**
	 * Handle AJAX request to setup the "Never miss an important update" step.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	public static function ajax_consent_tracking() {
		// Check permission and nonce.
		FrmAppHelper::permission_check( wpbdp_backend_minimim_role() );
		check_ajax_referer( 'frm_ajax', 'nonce' );

		// Update Settings.
		$frm_settings = FrmAppHelper::get_settings();
		$frm_settings->update_setting( 'tracking', true, 'rest_sanitize_boolean' );

		// Remove the 'FrmProSettingsController::store' action to avoid PHP errors during AJAX call.
		remove_action( 'frm_store_settings', 'FrmProSettingsController::store' );
		$frm_settings->store();

		self::subscribe_to_active_campaign();

		// Send response.
		wp_send_json_success();
	}

	/**
	 * When the user consents to receiving news of updates, subscribe their email to ActiveCampaign.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	private static function subscribe_to_active_campaign() {
		$user = wp_get_current_user();
		if ( empty( $user->user_email ) ) {
			return;
		}

		wp_remote_post(
			'https://sandbox.formidableforms.com/api/wp-admin/admin-ajax.php?action=frm_forms_preview&form=subscribe-onboarding',
			array(
				'body' => http_build_query(
					array(
						'form_key'      => 'subscribe-onboarding',
						'frm_action'    => 'create',
						'form_id'       => 5,
						'item_key'      => '',
						'item_meta[0]'  => '',
						'item_meta[15]' => $user->user_email,
					)
				),
			)
		);
	}

	/**
	 * Handle AJAX request to set up usage data for the Onboarding Wizard.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	public static function setup_usage_data() {
		// Check permission and nonce.
		FrmAppHelper::permission_check( wpbdp_backend_minimim_role() );
		check_ajax_referer( 'frm_ajax', 'nonce' );

		// Retrieve the current usage data.
		$usage_data = self::get_usage_data();

		$fields_to_update = array(
			'allows_tracking'  => 'rest_sanitize_boolean',
			'installed_addons' => 'sanitize_text_field',
			'processed_steps'  => 'sanitize_text_field',
			'completed_steps'  => 'rest_sanitize_boolean',
		);

		foreach ( $fields_to_update as $field => $sanitize_callback ) {
			if ( isset( $_POST[ $field ] ) ) {
				$usage_data[ $field ] = FrmAppHelper::get_post_param( $field, '', $sanitize_callback );
			}
		}

		update_option( self::USAGE_DATA_OPTION, $usage_data );
		wp_send_json_success();
	}

	/**
	 * Enqueues the Onboarding Wizard page scripts and styles.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$plugin_url      = FrmAppHelper::plugin_url();
		$version         = FrmAppHelper::plugin_version();
		$js_dependencies = array(
			'wp-i18n',
			// This prevents a console error "wp.hooks is undefined" in WP versions older than 5.7.
			'wp-hooks',
			'formidable_dom',
		);

		// Enqueue styles that needed.
		wp_enqueue_style( 'formidable-admin' );
		wp_enqueue_style( 'formidable-grids' );

		// Register and enqueue Onboarding Wizard style.
		wp_register_style( self::PAGE_SLUG, $plugin_url . '/css/admin/onboarding-wizard.css', array(), $version );
		wp_enqueue_style( self::PAGE_SLUG );

		// Register and enqueue Onboarding Wizard script.
		wp_register_script( self::PAGE_SLUG, $plugin_url . '/js/onboarding-wizard.js', $js_dependencies, $version, true );
		wp_localize_script( self::PAGE_SLUG, 'frmOnboardingWizardVars', self::get_js_variables() );
		wp_enqueue_script( self::PAGE_SLUG );

		/**
		 * Fires after the Onboarding Wizard enqueue assets.
		 *
		 * @since x.x
		 */
		do_action( 'frm_onboarding_wizard_enqueue_assets' );

		FrmAppHelper::dequeue_extra_global_scripts();
	}

	/**
	 * Get the Onboarding Wizard JS variables as an array.
	 *
	 * @since x.x
	 *
	 * @return array
	 */
	private static function get_js_variables() {
		return array(
			'INITIAL_STEP' => self::INITIAL_STEP,
		);
	}

	/**
	 * Remove the Onboarding Wizard submenu page from the formidable parent menu
	 * since it is not necessary to show that link there.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	public function remove_menu() {
		remove_submenu_page( 'formidable', self::PAGE_SLUG );
	}

	/**
	 * Adds custom classes to the existing string of admin body classes.
	 *
	 * The function appends a custom class to the existing admin body classes, enabling full-screen mode for the admin interface.
	 *
	 * @since x.x
	 *
	 * @param string $classes Existing body classes.
	 * @return string Updated list of body classes, including the newly added classes.
	 */
	public function add_admin_body_classes( $classes ) {
		return $classes . ' frm-admin-full-screen';
	}

	/**
	 * Checks if the Onboarding Wizard was skipped during the plugin's installation.
	 *
	 * @since x.x
	 * @return bool True if the Onboarding Wizard was skipped, false otherwise.
	 */
	public function has_onboarding_been_skipped() {
		return get_option( self::ONBOARDING_SKIPPED_OPTION, false );
	}

	/**
	 * Marks the Onboarding Wizard as skipped to prevent automatic redirects to the wizard.
	 *
	 * @since x.x
	 * @return void
	 */
	public function mark_onboarding_as_skipped() {
		update_option( self::ONBOARDING_SKIPPED_OPTION, true, 'no' );
	}

	/**
	 * Check if the current page is the Onboarding Wizard page.
	 *
	 * @since x.x
	 *
	 * @return bool True if the current page is the Onboarding Wizard page, false otherwise.
	 */
	public function is_onboarding_wizard_page() {
		return FrmAppHelper::is_admin_page( self::PAGE_SLUG );
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

		update_option( self::REDIRECT_STATUS_OPTION, FrmAppHelper::plugin_version(), 'no' );
		return false;
	}

	/**
	 * Get the path to the Onboarding Wizard views.
	 *
	 * @since x.x
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
	 * Get the list of add-ons available for installation.
	 *
	 * @since x.x
	 *
	 * @return array A list of add-ons.
	 */
	public static function get_available_addons() {
		return self::$available_addons;
	}

	/**
	 * Set the list of add-ons available for installation.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	private static function set_available_addons() {
	}

	/**
	 * Get the path to the Onboarding Wizard views.
	 *
	 * @since x.x
	 *
	 * @return string Path to views.
	 */
	public static function get_view_path() {
		return self::$view_path;
	}

	/**
	 * Get the upgrade link.
	 *
	 * @since x.x
	 *
	 * @return string URL for upgrading accounts.
	 */
	public static function get_upgrade_link() {
		return self::$upgrade_link;
	}

	/**
	 * Retrieves the current Onboarding Wizard usage data, returning an empty array if none exists.
	 *
	 * @since x.x
	 *
	 * @return array Current usage data.
	 */
	public static function get_usage_data() {
		return get_option( self::USAGE_DATA_OPTION, array() );
	}

	/**
	 * Validates if the Onboarding Wizard page is being displayed.
	 *
	 * @since x.x
	 * @deprecated x.x
	 *
	 * @return bool True if the Onboarding Wizard page is displayed, false otherwise.
	 */
	public static function is_onboarding_wizard_displayed() {
		_deprecated_function( __METHOD__, 'x.x' );
		return get_transient( self::TRANSIENT_NAME ) === self::TRANSIENT_VALUE;
	}
}
