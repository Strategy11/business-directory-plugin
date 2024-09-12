<?php
/**
 * Assets to be used by Business Directory Plugin
 *
 * @package BDP/Includes/
 */

/**
 * Class WPBDP__Assets
 *
 * @since 5.0
 */
class WPBDP__Assets {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_common_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_common_scripts' ) );

		// Scripts & styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_css_override' ), 9999, 0 );

		// Admin
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Registers scripts and styles that can be used either by frontend or backend code.
	 * The scripts are just registered, not enqueued.
	 *
	 * @since 3.4
	 */
	public function register_common_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script(
			'jquery-file-upload-iframe-transport',
			WPBDP_ASSETS_URL . 'vendor/jQuery-File-Upload/js/jquery.iframe-transport.js',
			array(),
			'10.32.0',
			true
		);

		wp_register_script(
			'jquery-file-upload',
			WPBDP_ASSETS_URL . 'vendor/jQuery-File-Upload/js/jquery.fileupload.js',
			array( 'jquery', 'jquery-ui-widget', 'jquery-file-upload-iframe-transport' ),
			'10.32.0',
			true
		);

		$this->maybe_register_script(
			'breakpoints.js',
			WPBDP_ASSETS_URL . 'vendor/jquery-breakpoints/jquery-breakpoints' . $min . '.js',
			array( 'jquery' ),
			'0.0.11',
			true
		);

		// Views.
		wp_register_script(
			'wpbdp-checkout',
			WPBDP_ASSETS_URL . 'js/checkout.js',
			array( 'wpbdp-js' ),
			WPBDP_VERSION,
			true
		);

		// Drag & Drop.
		wp_register_script(
			'wpbdp-dnd-upload',
			WPBDP_ASSETS_URL . 'js/dnd-upload' . $min . '.js',
			array( 'jquery-file-upload' ),
			WPBDP_VERSION,
			true
		);

		$this->register_select2();

		wp_register_style( 'wpbdp-base-css', WPBDP_ASSETS_URL . 'css/wpbdp.min.css', array(), WPBDP_VERSION );
	}

	private function register_select2() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Use Select2 styles and scripts from selectWoo https://woocommerce.wordpress.com/2017/08/08/selectwoo-an-accessible-replacement-for-select2/.
		wp_register_style(
			'wpbdp-js-select2-css',
			WPBDP_ASSETS_URL . 'vendor/selectWoo/css/selectWoo.min.css',
			array(),
			'4.0.5'
		);

		wp_register_script(
			'wpbdp-js-select2',
			WPBDP_ASSETS_URL . 'vendor/selectWoo/js/selectWoo.full' . $min . '.js',
			array( 'jquery' ),
			'4.0.5',
			true
		);
	}

	/**
	 * @since 5.8.2
	 */
	public function enqueue_select2() {
		$this->register_select2();
		wp_enqueue_script( 'wpbdp-js-select2' );
		wp_enqueue_style( 'wpbdp-js-select2-css' );
	}

	private function maybe_register_script( $handle, $src, $deps, $ver, $in_footer = false ) {
		$scripts = wp_scripts();

		if ( isset( $scripts->registered[ $handle ] ) ) {
			$registered_script = $scripts->registered[ $handle ];
		} else {
			$registered_script = null;
		}

		if ( $registered_script && version_compare( $registered_script->ver, $ver, '>=' ) ) {
			return;
		}

		if ( $registered_script ) {
			wp_deregister_script( $handle );
		}

		wp_register_script( $handle, $src, $deps, $ver, $in_footer );
	}

	public function enqueue_scripts() {
		$enqueue_scripts_and_styles = apply_filters( 'wpbdp_should_enqueue_scripts_and_styles', wpbdp()->is_plugin_page() );
		$min                        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$this->maybe_enqueue_widget_css( $enqueue_scripts_and_styles );

		if ( ! $enqueue_scripts_and_styles ) {
			return;
		}

		$this->load_theme_css();

		wp_register_script(
			'wpbdp-js',
			WPBDP_ASSETS_URL . 'js/wpbdp' . $min . '.js',
			array(
				'jquery',
				'breakpoints.js',
				'jquery-ui-sortable',
			),
			WPBDP_VERSION,
			true
		);

		$this->global_localize( 'wpbdp-js' );

		wp_enqueue_script( 'wpbdp-dnd-upload' );

		if ( wpbdp_get_option( 'use-thickbox' ) ) {
			add_thickbox();
		}

		wp_enqueue_style( 'wpbdp-base-css' );
		wp_enqueue_script( 'wpbdp-js' );

		$this->load_css();

		do_action( 'wpbdp_enqueue_scripts' );

		// enable legacy css (should be removed in a future release) XXX
		if ( _wpbdp_template_mode( 'single' ) == 'template' || _wpbdp_template_mode( 'category' ) == 'template' ) {
			wp_enqueue_style(
				'wpbdp-legacy-css',
				WPBDP_ASSETS_URL . 'css/wpbdp-legacy.min.css',
				array(),
				WPBDP_VERSION
			);
		}

		// Enable `grunt-contrib-watch` livereload.
		// Live reload server will be started with the watch task per target.
		$ip = wpbdp_get_server_value( 'REMOTE_ADDR' );
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && in_array( $ip, array( '127.0.0.1', '::1' ) ) ) {
			wp_enqueue_script( 'livereload', 'http://localhost:35729/livereload.js?snipver=1', array(), WPBDP_VERSION, true );
		}
	}

	/**
	 * Only load the widget CSS if a widget is active.
	 *
	 * @since 6.3.5
	 *
	 * @param bool $is_bd_page Whether the current page is a BD page.
	 *
	 * @return void
	 */
	private function maybe_enqueue_widget_css( $is_bd_page ) {
		wp_register_style(
			'wpbdp-widgets',
			WPBDP_ASSETS_URL . 'css/widgets.min.css',
			array(),
			WPBDP_VERSION
		);

		if ( $is_bd_page ) {
			wp_enqueue_style( 'wpbdp-widgets' );
			return;
		}

		// Workaround for widgets missing enqueue 'wpbdp-widgets' or to include css in header.
		$check_widgets = array(
			'WPBDP_Region_Search_Widget',
			'WPBDP_ZIPSearchWidget',
			'WPBDP_FeaturedListingsWidget',
			'WPBDP_LatestListingsWidget',
			'WPBDP_RandomListingsWidget',
			'WPBDP_SearchWidget',
		);

		foreach ( $check_widgets as $widget ) {
			if ( is_active_widget( false, false, strtolower( $widget ) ) ) {
				$this->load_theme_css();
				wp_enqueue_style( 'wpbdp-widgets' );
				break;
			}
		}
	}

	/**
	 * Load the theme CSS if we're on a BD page or a widget is included.
	 *
	 * @since 6.3.5
	 *
	 * @return void
	 */
	private function load_theme_css() {
		global $wpbdp;
		add_action( 'wp_enqueue_scripts', array( &$wpbdp->themes, 'enqueue_theme_scripts' ), 999 );
	}

	/**
	 * @since 5.9.2
	 */
	public function global_localize( $script = 'wpbdp-js' ) {
		$global = array(
			'ajaxurl' => wpbdp_ajaxurl(),
			'nonce'   => wp_create_nonce( 'wpbdp_ajax' ),
		);
		if ( $script === 'wpbdp-admin-js' ) {
			$global['assets']   = WPBDP_ASSETS_URL;
			$global['cancel']   = __( 'Cancel', 'business-directory-plugin' );
			$global['continue'] = __( 'Continue', 'business-directory-plugin' );
			$global['confirm']  = __( 'Are you sure?', 'business-directory-plugin' );
		}
		wp_localize_script( $script, 'wpbdp_global', $global );
	}

	public function load_css() {
		$rootline_color   = sanitize_hex_color( wpbdp_get_option( 'rootline-color' ) );
		$thumbnail_width  = wpbdp_get_option( 'thumbnail-width' );
		$thumbnail_height = wpbdp_get_option( 'thumbnail-height' );

		if ( ! $rootline_color ) {
			$rootline_color = '#569AF6';
		}

		$css_vars = array(
			'--bd-main-color'       => $rootline_color,
			'--bd-main-color-20'    => $rootline_color . '33',
			'--bd-main-color-8'     => $rootline_color . '14',
			'--bd-thumbnail-width'  => $thumbnail_width . 'px',
			'--bd-thumbnail-height' => $thumbnail_height . 'px',
		);

		$this->add_default_theme_css( $css_vars );

		$css = 'html,body{';
		foreach ( $css_vars as $var => $value ) {
			$css .= esc_attr( $var ) . ':' . esc_attr( $value ) . ';';
		}
		$css .= '}';

		if ( isset( $css_vars['--bd-button-padding-left'] ) ) {
			// Workaround to only add the padding when defined to avoid overriding the theme padding.
			$css .= '.wpbdp-with-button-styles .wpbdp-checkout-submit input[type="submit"],
			.wpbdp-with-button-styles .wpbdp-ratings-reviews input[type="submit"],
			.wpbdp-with-button-styles .comment-form input[type="submit"],
			.wpbdp-with-button-styles .wpbdp-main-box input[type="submit"],
			.wpbdp-with-button-styles .listing-actions a.wpbdp-button,
			.wpbdp-with-button-styles .wpbdp-button-secondary,
			.wpbdp-with-button-styles .wpbdp-button{
				padding-left: ' . esc_attr( $css_vars['--bd-button-padding-left'] ) . ';
				padding-right: ' . esc_attr( $css_vars['--bd-button-padding-left'] ) . ';
			}';
		}

		if ( isset( $css_vars['--bd-button-font-size'] ) ) {
			$css .= 'a.wpbdp-button, .wpbdp-button{
				font-size: ' . esc_attr( $css_vars['--bd-button-font-size'] ) . ';
			}';
		}

		wp_add_inline_style( 'wpbdp-base-css', WPBDP_App_Helper::minimize_code( $css ) );
	}

	/**
	 * Get settings from the theme.json file and add them to the CSS variables.
	 *
	 * @since 6.4
	 *
	 * @param array $css_vars The CSS variables.
	 *
	 * @return void
	 */
	private function add_default_theme_css( &$css_vars ) {
		$settings = wp_get_global_styles();

		if ( isset( $settings['color']['text'] ) ) {
			$css_vars['--bd-text-color'] = $settings['color']['text'];
		}

		if ( isset( $settings['color']['background'] ) ) {
			$css_vars['--bd-bg-color'] = $settings['color']['background'];
		}

		if ( empty( $settings['elements']['button'] ) ) {
			return;
		}
		$button = $settings['elements']['button'];

		if ( isset( $button['color']['text'] ) ) {
			$css_vars['--bd-button-text-color'] = $button['color']['text'];
		}

		if ( isset( $button['color']['background'] ) ) {
			$css_vars['--bd-button-bg-color'] = $button['color']['background'];
			if ( $css_vars['--bd-main-color'] === '#569AF6' ) {
				// If default color, use theme button color.
				$css_vars['--bd-main-color'] = $css_vars['--bd-button-bg-color'];
			} else {
				// If the color is set, use it as the button background.
				$css_vars['--bd-button-bg-color']   = $css_vars['--bd-main-color'];
				$css_vars['--bd-button-text-color'] = '#fff';
			}
		}

		if ( isset( $button['typeography']['fontSize'] ) ) {
			$css_vars['--bd-button-font-size'] = $button['typeography']['fontSize'];
		}

		if ( isset( $button['spacing']['padding'] ) ) {
			$padding = $button['spacing']['padding'];
			if ( isset( $padding['left'] ) ) {
				$css_vars['--bd-button-padding-left'] = $padding['left'];
			}
			if ( isset( $padding['top'] ) ) {
				$css_vars['--bd-button-padding-top'] = $padding['top'];
			}
		}
	}

	/**
	 * @since 3.5.3
	 */
	public function enqueue_css_override() {
		$stylesheet_dir     = trailingslashit( get_stylesheet_directory() );
		$stylesheet_dir_uri = trailingslashit( get_stylesheet_directory_uri() );
		$template_dir       = trailingslashit( get_template_directory() );
		$template_dir_uri   = trailingslashit( get_template_directory_uri() );

		$folders_uris = array(
			array( trailingslashit( WP_PLUGIN_DIR ), trailingslashit( WP_PLUGIN_URL ) ),
			array( $stylesheet_dir, $stylesheet_dir_uri ),
			array( $stylesheet_dir . 'css/', $stylesheet_dir_uri . 'css/' ),
		);

		if ( $template_dir != $stylesheet_dir ) {
			$folders_uris[] = array( $template_dir, $template_dir_uri );
			$folders_uris[] = array( $template_dir . 'css/', $template_dir_uri . 'css/' );
		}

		$filenames = array(
			'wpbdp.css',
			'wpbusdirman.css',
			'wpbdp_custom_style.css',
			'wpbdp_custom_styles.css',
			'wpbdm_custom_style.css',
			'wpbdm_custom_styles.css',
		);

		$n = 0;
		foreach ( $folders_uris as $folder_uri ) {
			list( $dir, $uri ) = $folder_uri;

			foreach ( $filenames as $f ) {
				if ( file_exists( $dir . $f ) ) {
					wp_enqueue_style(
						'wpbdp-custom-' . $n,
						$uri . $f,
						array(),
						WPBDP_VERSION
					);
					++$n;
				}
			}
		}
	}

	/**
	 * Load resources on admin page
	 *
	 * @since 5.18 Deprecate the $force parameter to not load on non BD pages.
	 *
	 * @param bool $force Force reloading the resources.
	 */
	public function enqueue_admin_scripts( $force = false ) {
		if ( $force === true ) {
			_deprecated_argument( __FUNCTION__, '5.17.2', 'Loading admin scripts can no longer be forced. Use the wpbdp_is_bd_page hook instead.' );
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( ! WPBDP_App_Helper::is_bd_page() ) {
			wp_enqueue_script( 'wpbdp-wp-admin-js', WPBDP_ASSETS_URL . 'js/wp-admin' . $min . '.js', array( 'jquery' ), WPBDP_VERSION, true );

			return;
		}

		// Add admin body class for parent page class to avoid css conflicts.
		add_filter( 'admin_body_class', array( &$this, 'add_body_class' ) );

		wp_enqueue_style( 'wpbdp-admin', WPBDP_ASSETS_URL . 'css/admin.min.css', array(), WPBDP_VERSION );

		wp_enqueue_style( 'thickbox' );

		wp_enqueue_style( 'wpbdp-base-css' );

		wp_enqueue_script( 'wpbdp-frontend-js', WPBDP_ASSETS_URL . 'js/wpbdp' . $min . '.js', array( 'jquery' ), WPBDP_VERSION, true );

		wp_enqueue_script( 'wpbdp-admin-js', WPBDP_ASSETS_URL . 'js/admin' . $min . '.js', array( 'jquery', 'thickbox', 'jquery-ui-sortable', 'jquery-ui-dialog', 'jquery-ui-tooltip' ), WPBDP_VERSION, true );
		$this->global_localize( 'wpbdp-admin-js' );

		// Enqueue Floating Links.
		self::enqueue_floating_links( WPBDP_ASSETS_URL, WPBDP_VERSION );

		wp_enqueue_script( 'wpbdp-user-selector-js', WPBDP_ASSETS_URL . 'js/user-selector' . $min . '.js', array( 'jquery', 'wpbdp-js-select2' ), WPBDP_VERSION, true );

		wp_enqueue_style( 'wpbdp-js-select2-css' );

		/**
		 * Load additional scripts or styles used only in BD plugin pages.
		 * This hook can be used to load scripts and resources using `wp_enqueue_script` or `wp_enqueue_style` WordPress hooks.
		 *
		 * @since 5.18
		 */
		do_action( 'wpbdp_enqueue_admin_scripts' );

		if ( ! WPBDP_App_Helper::is_bd_post_page() ) {
			return;
		}

		$this->load_css();

		self::load_datepicker();

		wp_enqueue_style(
			'wpbdp-listing-admin-metabox',
			WPBDP_ASSETS_URL . 'css/admin-listing-metabox.min.css',
			array(),
			WPBDP_VERSION
		);

		wp_enqueue_script(
			'wpbdp-admin-listing',
			WPBDP_ASSETS_URL . 'js/admin-listing.min.js',
			array( 'wpbdp-admin-js', 'wpbdp-dnd-upload' ),
			WPBDP_VERSION,
			true
		);

		wp_enqueue_script(
			'wpbdp-admin-listing-metabox',
			WPBDP_ASSETS_URL . 'js/admin-listing-metabox' . $min . '.js',
			array( 'wpbdp-admin-js', 'jquery-ui-datepicker' ),
			WPBDP_VERSION,
			true
		);

		wp_localize_script(
			'wpbdp-admin-listing-metabox',
			'wpbdpListingMetaboxL10n',
			array(
				'planDisplayFormat' => sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'admin.php?page=wpbdp-admin-fees&wpbdp_view=edit-fee&id={{plan_id}}' ) ),
					'{{plan_label}}'
				),
			)
		);

		wp_localize_script(
			'wpbdp-admin-listing',
			'WPBDP_admin_listings_config',
			array(
				'messages' => array(
					'preview_button_tooltip' => __(
						"Preview is only available after you've saved the first draft. This is due to how WordPress stores the data.",
						'business-directory-plugin'
					),
				),
			)
		);
	}

	/**
	 * Add admin body class.
	 * This will be used a wrapper for admin css classes to prevent conflicts with other page styles.
	 *
	 * @since 5.14.3
	 *
	 * @param string $admin_body_classes The current admin body classes.
	 *
	 * @return string $admin_body_classes The body class with the added plugin class.
	 */
	public function add_body_class( $admin_body_classes ) {
		if ( WPBDP_App_Helper::is_bd_page() ) {
			$admin_body_classes .= ' wpbdp-admin-page';

			// Append 'wpbdp-no-renewal' class if listing renewals are turned off.
			if ( ! wpbdp_get_option( 'listing-renewal' ) ) {
				$admin_body_classes .= ' wpbdp-no-renewal';
			}
		}

		return $admin_body_classes;
	}

	/**
	 * Register resources required in installation only.
	 *
	 * @since 5.18
	 */
	public function register_installation_resources() {
		wp_enqueue_script( 'wpbdp-admin-install-js', WPBDP_ASSETS_URL . 'js/admin-install.min.js', array( 'jquery' ), WPBDP_VERSION, true );
	}

	/**
	 * Load Jquery UI Style.
	 *
	 * @since 6.0
	 */
	public static function load_datepicker() {
		wp_enqueue_script( 'jquery-ui-datepicker' );

		if ( self::is_jquery_ui_css_loaded() ) {
			return;
		}

		wp_enqueue_style(
			'jquery-theme',
			WPBDP_ASSETS_URL . 'css/jquery-ui.css',
			array(),
			WPBDP_VERSION
		);
	}

	/**
	 * Check if Jquery UI CSS is loaded.
	 *
	 * @since 6.0
	 *
	 * @return bool
	 */
	private static function is_jquery_ui_css_loaded() {
		$possible_styles = array( 'jquery-ui', 'jquery-ui-css', 'jquery-theme' );
		foreach ( $possible_styles as $style ) {
			if ( wp_style_is( $style ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Handles Floating Links' scripts and styles enqueueing.
	 *
	 * @since 6.3.8
	 *
	 * @param string $plugin_url URL of the plugin.
	 * @param string $version Current version of the plugin.
	 *
	 * @return void
	 */
	private static function enqueue_floating_links( $plugin_url, $version ) {
		if ( ! $plugin_url || ! $version ) {
			// If any required parameters are missing, exit early.
			return;
		}
		// Enqueue the Floating Links scripts.
		wp_enqueue_script( 's11-floating-links-notifications', $plugin_url . '/js/packages/floating-links/s11-floating-links-notifications.js', array(), $version, true );
		wp_enqueue_script( 's11-floating-links', $plugin_url . '/js/packages/floating-links/s11-floating-links.js', array(), $version, true );

		// Enqueue the config script.
		wp_enqueue_script( 's11-floating-links-config', $plugin_url . '/js/packages/floating-links/config.js', array( 'wp-i18n', 'wpbdp-admin-js' ), $version, true );
		wp_set_script_translations( 's11-floating-links-config', 's11-' );
		$floating_links_data = array(
			'navLinks'       => array(
				'freeVersion' => array(
					'upgrade'       => wpbdp_admin_upgrade_link( 'floating-links' ),
					'support'       => 'https://wordpress.org/support/plugin/business-directory-plugin/',
					'documentation' => wpbdp_admin_upgrade_link( 'floating-links', 'get-help/' ),
				),
				'proVersion'  => array(
					'support_and_docs' => wpbdp_admin_upgrade_link( 'floating-links', 'get-help/' ),
				),
			),
			'proIsInstalled' => WPBDP_Admin_Education::is_installed( 'premium' ),
		);
		wp_localize_script( 's11-floating-links-config', 's11FloatingLinksData', $floating_links_data );
	}
}
