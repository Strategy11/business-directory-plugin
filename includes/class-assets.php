<?php
/**
 * @since 5.0
 */
class WPBDP__Assets {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_common_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_common_scripts' ) );

        // Scripts & styles.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_css_override' ), 9999, 0 );

        $this->register_image_sizes();
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
            WPBDP_URL . 'vendors/jQuery-File-Upload-9.5.7/js/jquery.iframe-transport.min.js'
        );
        wp_register_script(
            'jquery-file-upload',
            WPBDP_URL . 'vendors/jQuery-File-Upload-9.5.7/js/jquery.fileupload.min.js',
            array( 'jquery', 'jquery-ui-widget', 'jquery-file-upload-iframe-transport' )
        );
        wp_register_script(
            'jquery-breakpoints',
            WPBDP_URL . 'vendors/jquery-breakpoints.min.js',
            array( 'jquery' ),
            null,
            true
        );

        // Views
        wp_register_script(
            'wpbdp-checkout',
            WPBDP_URL . 'assets/js/checkout.js',
            array( 'wpbdp-js' ),
            WPBDP_VERSION
        );

        // Drag & Drop.
        wp_register_style( 'wpbdp-dnd-upload', WPBDP_URL . 'assets/css/dnd-upload.min.css', array(), WPBDP_VERSION );
        wp_register_script( 'wpbdp-dnd-upload', WPBDP_URL . 'assets/js/dnd-upload.min.js', array( 'jquery-file-upload' ), WPBDP_VERSION );

        // Select2.
        wp_register_style( 'wpbdp-js-select2-css', WPBDP_URL . 'vendors/select2-4.0.3/css/select2.min.css' );
        wp_register_script( 'wpbdp-js-select2', WPBDP_URL . 'vendors/select2-4.0.3/js/select2.full.min.js', array( 'jquery' ) );
    }

    public function enqueue_scripts() {
        $only_in_plugin_pages = true;
        $enqueue_scripts_and_styles = apply_filters( 'wpbdp_should_enqueue_scripts_and_styles', wpbdp()->is_plugin_page() );

        wp_enqueue_style( 'wpbdp-widgets', WPBDP_URL . 'assets/css/widgets.min.css' );

        if ( $only_in_plugin_pages && ! $enqueue_scripts_and_styles )
            return;

        wp_register_style( 'wpbdp-base-css', WPBDP_URL . 'assets/css/wpbdp.min.css', array( 'wpbdp-js-select2-css' ), WPBDP_VERSION );

        wp_register_script(
            'wpbdp-js',
            WPBDP_URL . 'assets/js/wpbdp.min.js',
            array( 'jquery', 'jquery-breakpoints', 'wpbdp-js-select2', 'jquery-ui-sortable' ),
            WPBDP_VERSION
        );

        wp_localize_script( 'wpbdp-js', 'wpbdp_global', array(
            'ajaxurl' => wpbdp_ajaxurl()
        ) );

        wp_enqueue_style( 'wpbdp-dnd-upload' );
        wp_enqueue_script( 'wpbdp-dnd-upload' );

        if ( wpbdp_get_option( 'use-thickbox' ) ) {
            add_thickbox();
        }

        wp_enqueue_style( 'wpbdp-base-css' );
        wp_enqueue_script( 'wpbdp-js' );

        do_action( 'wpbdp_enqueue_scripts' );

        // enable legacy css (should be removed in a future release) XXX
        if (_wpbdp_template_mode('single') == 'template' || _wpbdp_template_mode('category') == 'template' )
            wp_enqueue_style('wpbdp-legacy-css', WPBDP_URL . 'assets/css/wpbdp-legacy.min.css');
    }

    /**
     * @since 3.5.3
     */
    public function enqueue_css_override() {
        $stylesheet_dir = trailingslashit( get_stylesheet_directory() );
        $stylesheet_dir_uri = trailingslashit( get_stylesheet_directory_uri() );
        $template_dir = trailingslashit( get_template_directory() );
        $template_dir_uri = trailingslashit( get_template_directory_uri() );

        $folders_uris = array(
            array( trailingslashit( WP_PLUGIN_DIR ), trailingslashit( WP_PLUGIN_URL ) ),
            array( $stylesheet_dir, $stylesheet_dir_uri ),
            array( $stylesheet_dir . 'css/', $stylesheet_dir_uri . 'css/' )
        );

        if ( $template_dir != $stylesheet_dir ) {
            $folders_uris[] = array( $template_dir, $template_dir_uri );
            $folders_uris[] = array( $template_dir . 'css/', $template_dir_uri . 'css/' );
        }

        $filenames = array( 'wpbdp.css',
                            'wpbusdirman.css',
                            'wpbdp_custom_style.css',
                            'wpbdp_custom_styles.css',
                            'wpbdm_custom_style.css',
                            'wpbdm_custom_styles.css' );

        $n = 0;
        foreach ( $folders_uris as $folder_uri ) {
            list( $dir, $uri ) = $folder_uri;

            foreach ( $filenames as $f ) {
                if ( file_exists( $dir . $f ) ) {
                    wp_enqueue_style( 'wpbdp-custom-' . $n, $uri . $f );
                    $n++;
                }
            }
        }
    }    

    public function register_image_sizes() {
        $thumbnail_width = absint( wpbdp_get_option( 'thumbnail-width' ) );
        $thumbnail_height = absint( wpbdp_get_option( 'thumbnail-height' ) );

        $max_width = absint( wpbdp_get_option('image-max-width') );
        $max_height = absint( wpbdp_get_option('image-max-height') );

        $crop = (bool) wpbdp_get_option( 'thumbnail-crop' );

        add_image_size( 'wpbdp-mini', 50, 50, true ); // Used for the submit process.
        add_image_size( 'wpbdp-thumb', $thumbnail_width, $crop ? $thumbnail_height : 9999, $crop ); // Thumbnail size.
        add_image_size( 'wpbdp-large', $max_width, $max_height, false ); // Large size.
    }    

}

