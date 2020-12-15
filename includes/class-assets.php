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
    }

    /**
     * Registers scripts and styles that can be used either by frontend or backend code.
     * The scripts are just registered, not enqueued.
     *
     * @since 3.4
     */
    public function register_common_scripts() {

        wp_register_script(
            'jquery-file-upload-iframe-transport',
            WPBDP_URL . 'vendors/jQuery-File-Upload/js/jquery.iframe-transport.js',
            array(),
            '10.7.0'
        );

        wp_register_script(
            'jquery-file-upload',
            WPBDP_URL . 'vendors/jQuery-File-Upload/js/jquery.fileupload.js',
            array( 'jquery', 'jquery-ui-widget', 'jquery-file-upload-iframe-transport' ),
            '10.7.0'
        );

        $this->maybe_register_script(
            'breakpoints.js',
            WPBDP_URL . 'vendors/jquery-breakpoints.min.js',
            array( 'jquery' ),
            '0.0.11',
            true
        );

        // Views.
        wp_register_script(
            'wpbdp-checkout',
            WPBDP_URL . 'assets/js/checkout.js',
            array( 'wpbdp-js' ),
            WPBDP_VERSION
        );

        // Drag & Drop.
        wp_register_script(
            'wpbdp-dnd-upload',
            WPBDP_URL . 'assets/js/dnd-upload.min.js',
            array( 'jquery-file-upload' ),
            WPBDP_VERSION
        );

        $this->register_select2();

		wp_register_style( 'wpbdp-base-css', WPBDP_URL . 'assets/css/wpbdp.min.css', array(), WPBDP_VERSION );
    }

	private function register_select2() {
        // Use Select2 styles and scripts from selectWoo https://woocommerce.wordpress.com/2017/08/08/selectwoo-an-accessible-replacement-for-select2/.
        wp_register_style(
            'wpbdp-js-select2-css',
            WPBDP_URL . 'vendors/selectWoo/css/selectWoo.min.css',
            array(),
            '4.0.5'
        );

        wp_register_script(
            'wpbdp-js-select2',
            WPBDP_URL . 'vendors/selectWoo/js/selectWoo.full.min.js',
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
        $only_in_plugin_pages       = true;
        $enqueue_scripts_and_styles = apply_filters( 'wpbdp_should_enqueue_scripts_and_styles', wpbdp()->is_plugin_page() );

        wp_enqueue_style(
            'wpbdp-widgets',
            WPBDP_URL . 'assets/css/widgets.min.css',
            array(),
            WPBDP_VERSION
        );

        if ( $only_in_plugin_pages && ! $enqueue_scripts_and_styles ) {
            return;
        }

        // TODO: Is it possible (and worth it) to figure out if we need the
        // jquery-ui-datepicker script based on which fields are available?
        wp_register_script(
            'wpbdp-js',
            WPBDP_URL . 'assets/js/wpbdp.min.js',
            array(
                'jquery',
                'breakpoints.js',
                'jquery-ui-sortable',
                'jquery-ui-datepicker',
            ),
            WPBDP_VERSION
        );

        wp_localize_script(
            'wpbdp-js', 'wpbdp_global', array(
				'ajaxurl' => wpbdp_ajaxurl(),
            )
        );

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
                WPBDP_URL . 'assets/css/wpbdp-legacy.min.css',
                array(),
                WPBDP_VERSION
            );
        }
    }

    public function load_css() {
        $rootline_color = sanitize_hex_color( wpbdp_get_option( 'rootline-color' ) );

        if ( ! $rootline_color ) {
            $rootline_color = '#569AF6';
        }

        wp_add_inline_style(
            'wpbdp-base-css',
            'html{ --bd-main-color:' . $rootline_color . '; --bd-main-color-20:' . $rootline_color . '33;--bd-main-color-8:' . $rootline_color . '14;}'
        );
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
                    $n++;
                }
            }
        }
    }
}
