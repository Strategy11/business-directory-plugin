<?php
/**
 * Main Business Directory class.
 *
 * @package WPBDP
 * @SuppressWarnings(PHPMD)
 */

// phpcs:disable
final class WPBDP {

    public $_query_stack = array();


    public function __construct() {
        $this->setup_constants();
        $this->includes();
        $this->hooks();
    }

    private function setup_constants() {
        define( 'WPBDP_VERSION', '5.5.7' );

        define( 'WPBDP_PATH', wp_normalize_path( plugin_dir_path( WPBDP_PLUGIN_FILE ) ) );
        define( 'WPBDP_INC', trailingslashit( WPBDP_PATH . 'includes' ) );
        define( 'WPBDP_URL', trailingslashit( plugins_url( '/', WPBDP_PLUGIN_FILE ) ) );
        define( 'WPBDP_TEMPLATES_PATH', WPBDP_PATH . 'templates' );

        define( 'WPBDP_POST_TYPE', 'wpbdp_listing' );
        define( 'WPBDP_CATEGORY_TAX', 'wpbdp_category' );
        define( 'WPBDP_TAGS_TAX', 'wpbdp_tag' );
    }

    private function includes() {
        // Make DBO framework available to everyone.
        require_once( WPBDP_INC . 'db/class-db-model.php' );

        require_once( WPBDP_INC . 'class-view.php' );

        require_once( WPBDP_INC . 'class-modules.php' );
        require_once( WPBDP_INC . 'licensing.php' );

        require_once( WPBDP_INC . 'form-fields.php' );
        require_once( WPBDP_INC . 'payment.php' );
        require_once( WPBDP_PATH . 'includes/class-payment-gateways.php' );
        require_once( WPBDP_INC . 'installer.php' );

        require_once( WPBDP_INC . 'class-cron.php' );

        require_once( WPBDP_INC . 'admin/settings/class-settings.php' );

        require_once( WPBDP_INC . 'functions.php' );
        require_once( WPBDP_INC . 'utils.php' );

        require_once( WPBDP_INC . 'helpers/listing_flagging.php' );

        require_once( WPBDP_INC . 'class-cpt-integration.php' );
        require_once( WPBDP_INC . 'class-listing-expiration.php' );
        require_once( WPBDP_INC . 'class-listing-email-notification.php' );
        require_once( WPBDP_INC . 'class-abandoned-payment-notification.php' );

        require_once( WPBDP_INC . 'compatibility/class-compat.php' );
        require_once( WPBDP_INC . 'class-rewrite.php' );


        require_once( WPBDP_INC . 'class-assets.php' );
        require_once( WPBDP_INC . 'class-meta.php' );
        require_once( WPBDP_INC . 'widgets/class-widgets.php' );

        if ( wpbdp_is_request( 'frontend' ) ) {
            require_once( WPBDP_INC . 'templates-ui.php' );
            require_once( WPBDP_INC . 'template-sections.php' );
            require_once( WPBDP_INC . 'class-shortcodes.php' );
            require_once( WPBDP_INC . 'class-recaptcha.php' );
            require_once( WPBDP_INC . 'class-query-integration.php' );
            require_once( WPBDP_INC . 'class-dispatcher.php' );
            require_once( WPBDP_INC . 'class-wordpress-template-integration.php' );
            require_once( WPBDP_INC . 'seo.php' );
        }

        require_once( WPBDP_INC . 'themes.php' );

        if ( wpbdp_is_request( 'admin' ) ) {
            require_once( WPBDP_INC . 'admin/tracking.php' );
            require_once( WPBDP_INC . 'admin/class-admin.php' );
            require_once( WPBDP_INC . 'admin/class-personal-data-privacy.php' );

            require_once( WPBDP_INC . 'admin/class-listings-with-no-fee-plan-view.php' );
        }

        require_once( WPBDP_INC . 'helpers/class-access-keys-sender.php' );
    }

    // phpcs:enable

    /**
     * @since 5.2.1 Removed usage of create_function().
     */
    private function hooks() {
        register_activation_hook( WPBDP_PLUGIN_FILE, array( $this, 'plugin_activation' ) );
        register_deactivation_hook( WPBDP_PLUGIN_FILE, array( $this, 'plugin_deactivation' ) );

        add_action( 'init', array( $this, 'init' ), 0 );
        add_filter( 'plugin_action_links_' . plugin_basename( WPBDP_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );

        // Clear cache of page IDs when a page is saved.
        add_action( 'save_post_page', 'wpbdp_delete_page_ids_cache' );

        // AJAX actions.
        // TODO: Use Dispatcher AJAX support instead of hardcoding these actions here.
        add_action( 'wp_ajax_wpbdp-listing-submit-image-upload', array( &$this, 'ajax_listing_submit_image_upload' ) );
        add_action( 'wp_ajax_nopriv_wpbdp-listing-submit-image-upload', array( &$this, 'ajax_listing_submit_image_upload' ) );
        add_action( 'wp_ajax_wpbdp-listing-submit-image-delete', array( &$this, 'ajax_listing_submit_image_delete' ) );
        add_action( 'wp_ajax_nopriv_wpbdp-listing-submit-image-delete', array( &$this, 'ajax_listing_submit_image_delete' ) );

        add_action( 'plugins_loaded', array( $this, 'register_cache_groups' ) );
        add_action( 'switch_blog', array( $this, 'register_cache_groups' ) );
    }

    // phpcs:disable

    public function init() {
        $this->load_textdomain();

        $this->form_fields = WPBDP_FormFields::instance();
        $this->formfields = $this->form_fields; // Backwards compat.

        $this->settings = new WPBDP__Settings();
        $this->settings->bootstrap();

        $this->cpt_integration = new WPBDP__CPT_Integration();

        $this->licensing = new WPBDP_Licensing();
        $this->modules = new WPBDP__Modules();

        $this->themes = new WPBDP_Themes();

        $this->installer = new WPBDP_Installer();
        try {
            $this->installer->install();
        } catch ( Exception $e ) {
            $this->installer->show_installation_error( $e );
            return;
        }

        $this->fees = new WPBDP_Fees_API();

        if ( $manual_upgrade = get_option( 'wpbdp-manual-upgrade-pending', array() ) ) {
            if ( $this->installer->setup_manual_upgrade() ) {
                add_shortcode( 'businessdirectory', array( $this, 'frontend_manual_upgrade_msg' ) );
                add_shortcode( 'business-directory', array( $this, 'frontend_manual_upgrade_msg' ) );

                // XXX: Temporary fix to disable features until a pending Manual
                // Upgrades have been performed.
                //
                // Ideally, these hooks would be registered later, making the following
                // lines unnecessary.
                remove_action( 'wp_footer', array( $this->themes, 'fee_specific_coloring' ), 999 );
                remove_action( 'admin_notices', array( &$this->licensing, 'admin_notices' ) );

                return;
            }
        }

        $this->modules->load_i18n();
        $this->modules->init(); // Change to something we can fire in WPBDP__Modules to register modules.

        $this->payment_gateways = new WPBDP__Payment_Gateways();

        do_action('wpbdp_modules_loaded');

        do_action_ref_array( 'wpbdp_register_settings', array( &$this->settings ) );
        do_action('wpbdp_register_fields', $this->formfields);
        do_action('wpbdp_modules_init');

        $this->listings = new WPBDP_Listings_API();
        $this->payments = new WPBDP_PaymentsAPI();

        $this->cpt_integration->register_hooks();

        $this->cron = new WPBDP__Cron();

        $this->setup_email_notifications();

        $this->assets = new WPBDP__Assets();
        $this->widgets = new WPBDP__Widgets();

        // We need to ask for frontend requests first, because
        // wpbdp_is_request( 'admin' ) or is_admin() return true for ajax
        // requests made from the frontend.
        if ( wpbdp_is_request( 'frontend' ) ) {
            $this->query_integration = new WPBDP__Query_Integration();
            $this->dispatcher = new WPBDP__Dispatcher();
            $this->shortcodes = new WPBDP__Shortcodes();
            $this->template_integration = new WPBDP__WordPress_Template_Integration();

            $this->meta = new WPBDP__Meta();
            $this->recaptcha = new WPBDP_reCAPTCHA();
        }

        if ( wpbdp_is_request( 'admin' ) ) {
            $this->admin   = new WPBDP_Admin();
            $this->privacy = new WPBDP_Personal_Data_Privacy();
        }

        $this->compat = new WPBDP_Compat();
        $this->rewrite = new WPBDP__Rewrite();


        do_action( 'wpbdp_loaded' );
    }

    public function setup_email_notifications() {
        global $wpdb;

        $this->listing_expiration = new WPBDP__Listing_Expiration();
        $this->listing_email_notification = new WPBDP__Listing_Email_Notification();

        if ( $this->settings->get_option( 'payment-abandonment' ) ) {
            $abandoned_payment_notification = new WPBDP__Abandoned_Payment_Notification( $this->settings, $wpdb );
            add_action( 'wpbdp_hourly_events', array( $abandoned_payment_notification, 'send_abandoned_payment_notifications' ) );
        }
    }

    public function register_cache_groups() {
        if ( ! function_exists( 'wp_cache_add_non_persistent_groups' ) ) {
            return;
        }

        wp_cache_add_non_persistent_groups( array( 'wpbdp pages', 'wpbdp formfields', 'wpbdp fees', 'wpbdp submit state', 'wpbdp' ) );
    }

    private function load_textdomain() {
        //        $languages_dir = str_replace( trailingslashit( WP_PLUGIN_DIR ), '', WPBDP_PATH . 'languages' );

        $languages_dir = trailingslashit( basename( WPBDP_PATH ) ) . 'languages';
        load_plugin_textdomain( 'WPBDM', false, $languages_dir );
    }

    public function plugin_activation() {
        if ( function_exists( 'flush_rewrite_rules' ) ) {
            add_action( 'shutdown', 'flush_rewrite_rules' );
        }
        delete_transient( 'wpbdp-page-ids' );
    }

    public function plugin_deactivation() {
        wp_clear_scheduled_hook( 'wpbdp_hourly_events' );
        wp_clear_scheduled_hook( 'wpbdp_daily_events' );
    }

    public function plugin_action_links( $links ) {
        $links = array_merge(
            array( 'settings' => '<a href="' . admin_url( 'admin.php?page=wpbdp_settings' ) . '">' . _x( 'Settings', 'admin plugins', 'WPBDM' ) . '</a>' ),
            $links
        );

        return $links;
    }

    public function is_plugin_page() {
        if ( wpbdp_current_view() ) {
            return true;
        }

        global $wp_query;

        if ( ! empty( $wp_query->wpbdp_our_query ) || ! empty( $wp_query->wpbdp_view ) )
            return true;

        global $post;

        if ( $post && ( 'page' == $post->post_type || 'post' == $post->post_type ) ) {
            foreach ( array_keys( $this->shortcodes->get_shortcodes() ) as $shortcode ) {
                if ( wpbdp_has_shortcode( $post->post_content, $shortcode ) ) {
                    return true;
                    break;
                }
            }
        }

        if ( $post && WPBDP_POST_TYPE == $post->post_type )
            return true;

        return false;
    }

    public function get_post_type() {
        return WPBDP_POST_TYPE;
    }

    public function get_post_type_category() {
        return WPBDP_CATEGORY_TAX;
    }

    public function get_post_type_tags() {
        return WPBDP_TAGS_TAX;
    }

    /**
     * @deprecated since fees-revamp. Remove when found, kept for backwards compat.
     */
    public function is_debug_on() {
        return false;
    }

    // TODO: better validation.
    public function ajax_listing_submit_image_upload() {
        $res = new WPBDP_Ajax_Response();

        $listing_id = intval( $_REQUEST['listing_id'] );

        if ( ! $listing_id )
            return $res->send_error();

        $content_range = null;
        $size = null;

        if ( isset( $_SERVER['HTTP_CONTENT_RANGE'] ) ) {
            $content_range = preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']);
            $size =  $content_range ? $content_range[3] : null;
        }

        $attachments = array();
        $files = wpbdp_flatten_files_array( isset( $_FILES['images'] ) ? $_FILES['images'] : array() );
        $errors = array();

        $listing = WPBDP_Listing::get( $listing_id );
        $slots_available = 0;

        if ( $plan = $listing->get_fee_plan() ) {
            $slots_available = absint( $plan->fee_images ) - absint( $_POST['images_count'] );
        }

        if ( ! current_user_can( 'administrator' ) ) {
            if ( 0 >= $slots_available ) {
                return $res->send_error( _x( 'Can not upload any more images for this listing.', 'listing image upload', 'WPBDM' ) );
            } elseif ( $slots_available < count( $files ) ) {
                return $res->send_error(
                    sprintf(
                        _nx(
                            'You\'re trying to upload %d images, but only have %d slot available. Please adjust your selection.',
                            'You\'re trying to upload %d images, but only have %d slots available. Please adjust your selection.',
                            $slots_available,
                            'listing image upload',
                            'WPBDM'
                        ),
                        count( $files ),
                        $slots_available
                    )
                );
            }
        }

        foreach ( $files as $i => $file ) {
            $image_error = '';
            $attachment_id = wpbdp_media_upload( $file,
                                                 true,
                                                 true,
                                                 array( 'image' => true,
                                                        'min-size' => intval( wpbdp_get_option( 'image-min-filesize' ) ) * 1024,
                                                        'max-size' => intval( wpbdp_get_option( 'image-max-filesize' ) ) * 1024,
                                                        'min-width' => wpbdp_get_option( 'image-min-width' ),
                                                        'min-height' => wpbdp_get_option( 'image-min-height' )
                                                     ),
                                                 $image_error ); // TODO: handle errors.

            if ( $image_error )
                $errors[ $file['name'] ] = $image_error;
            else
                $attachments[] = $attachment_id;
        }


        $html = '';
        foreach ( $attachments as $attachment_id ) {
            $html .= wpbdp_render( 'submit-listing-images-single',
                                   array( 'image_id' => $attachment_id, 'listing_id' => $listing_id ),
                                   false );
        }

        $listing->set_images( $attachments, true );

        if ( $errors ) {
            $error_msg = '';

            foreach ( $errors as $fname => $error )
                $error_msg .= sprintf( '&#149; %s: %s', $fname, $error ) . '<br />';

            $res->add( 'uploadErrors', $error_msg );
        }

        $res->add( 'is_admin', current_user_can( 'administrator' ) );
        $res->add( 'slots_available', $slots_available );
        $res->add( 'attachmentIds', $attachments );
        $res->add( 'html', $html );
        $res->send();
    }

    public function ajax_listing_submit_image_delete() {
        $res = new WPBDP_Ajax_Response();

        $image_id = intval( $_REQUEST['image_id'] );
        $listing_id = intval( $_REQUEST['listing_id'] );
        $nonce = $_REQUEST['_wpnonce'];

        if ( ! $image_id || ! $listing_id || ! wp_verify_nonce( $nonce, 'delete-listing-' . $listing_id . '-image-' . $image_id ) )
            $res->send_error();

        $parent_id = (int) wp_get_post_parent_id( $image_id );
        if ( $parent_id != $listing_id )
            $res->send_error();

        $listing = wpbdp_get_listing( $listing_id );

        if ( ! $listing ) {
            $res->send_error();
        }

        $thumbnail_id = $listing->get_thumbnail_id();

        if ( false !== wp_delete_attachment( $image_id, true ) && $image_id == $thumbnail_id ) {
            $listing->set_thumbnail_id( 0 );
        }

        $res->add( 'imageId', $image_id );
        $res->send();
    }

    public function frontend_manual_upgrade_msg() {
        wp_enqueue_style( 'wpbdp-base-css' );

        if ( current_user_can( 'administrator' ) ) {
            return wpbdp_render_msg(
                str_replace(
                    '<a>',
                    '<a href="' . admin_url( 'admin.php?page=wpbdp-upgrade-page' ) . '">',
                    __( 'The directory features are disabled at this time because a <a>manual upgrade</a> is pending.', 'WPBDM' )
                ),
                'error'
            );
        }

        return wpbdp_render_msg(
            __( 'The directory is not available at this time. Please try again in a few minutes or contact the administrator if the problem persists.', 'WPBDM' ),
            'error'
        );
    }

}

// phpcs:enable
