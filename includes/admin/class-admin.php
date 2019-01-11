<?php
/**
 * Class Admin
 *
 * @package BDP/Includes/Admin/Class Admin
 * @SuppressWarnings(PHPMD)
 */

// phpcs:disable

require_once( WPBDP_PATH . 'includes/admin/admin-pages.php' );
require_once( WPBDP_PATH . 'includes/admin/class-admin-listings.php' );
require_once( WPBDP_PATH . 'includes/admin/form-fields.php' );
require_once( WPBDP_PATH . 'includes/admin/csv-import.php' );
require_once( WPBDP_PATH . 'includes/admin/csv-export.php' );
require_once( WPBDP_PATH . 'includes/admin/class-listing-fields-metabox.php' );
require_once( WPBDP_PATH . 'includes/admin/page-debug.php' );
require_once( WPBDP_PATH . 'includes/admin/class-admin-controller.php' );

if ( ! class_exists( 'WPBDP_Admin' ) ) {

/**
 * Class WPBDP_Admin
 */
class WPBDP_Admin {

    private $menu = array();
    private $current_controller = null;
    private $current_controller_output = '';

    private $dropdown_users_args_stack = array();

    public $messages = array();


    public function __construct() {
        add_action('admin_init', array($this, 'handle_actions'));

        add_action('admin_init', array($this, 'check_for_required_pages'));

        add_action( 'admin_init', array( &$this, 'process_admin_action' ), 999 );
        add_action( 'admin_init', array( $this, 'register_listings_views' ) );

        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Adds admin menus.
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

        // Enables reordering of admin menus.
        add_filter( 'custom_menu_order', '__return_true' );

        // Puts the "Directory" and "Directory Admin" next to each other.
        add_filter( 'menu_order', array( &$this, 'admin_menu_reorder' ) );

        add_filter( 'wp_dropdown_users_args', array( $this, '_dropdown_users_args' ), 10, 2 );

        add_filter( 'manage_edit-' . WPBDP_CATEGORY_TAX . '_columns', array( &$this, 'add_custom_taxonomy_columns' ) );
        add_filter( 'manage_edit-' . WPBDP_TAGS_TAX . '_columns', array( &$this, 'tag_taxonomy_columns' ) );
        add_action( 'manage_' . WPBDP_CATEGORY_TAX . '_custom_column', array( &$this, 'custom_taxonomy_columns' ), 10, 3 );

        add_filter('wp_terms_checklist_args', array($this, '_checklist_args')); // fix issue #152

        add_action( 'wp_ajax_wpbdp-formfields-reorder', array( &$this, 'ajax_formfields_reorder' ) );

        add_action( 'wp_ajax_wpbdp-admin-fees-set-order', array( &$this, 'ajax_fees_set_order' ) );
        add_action( 'wp_ajax_wpbdp-admin-fees-reorder', array( &$this, 'ajax_fees_reorder' ) );

        add_action( 'wp_ajax_wpbdp-renderfieldsettings', array( 'WPBDP_FormFieldsAdmin', '_render_field_settings' ) );

        add_action( 'wp_ajax_wpbdp-create-main-page', array( &$this, 'ajax_create_main_page' ) );
        add_action( 'wp_ajax_wpbdp-drip_subscribe', array( &$this, 'ajax_drip_subscribe' ) );
        add_action( 'wp_ajax_wpbdp-set_site_tracking', 'WPBDP_SiteTracking::handle_ajax_response' );
        add_action( 'wp_ajax_wpbdp_dismiss_notification', array( &$this, 'ajax_dismiss_notification' ) );

        add_action( 'wpbdp_admin_ajax_dismiss_notification_server_requirements', array( $this, 'ajax_dismiss_notification_server_requirements' ) );

        add_action( 'current_screen', array( $this, 'admin_view_dispatch' ), 9999 );
        add_action( 'wp_ajax_wpbdp_admin_ajax', array( $this, 'admin_ajax_dispatch' ), 9999 );

        $this->listings = new WPBDP_Admin_Listings();
        $this->csv_import = new WPBDP_CSVImportAdmin();
        $this->csv_export = new WPBDP_Admin_CSVExport();
        $this->debug_page = new WPBDP_Admin_Debug_Page();

        // Post-install migrations.
        if ( get_option( 'wpbdp-migrate-18_0-featured-pending', false ) ) {
            require_once( WPBDP_PATH . 'includes/admin/upgrades/migrations/manual-upgrade-18_0-featured-levels.php' );
            $this->post_install_migration = new WPBDP__Manual_Upgrade__18_0__Featured_Levels();
        }

        require_once( WPBDP_INC . 'admin/settings/class-settings-admin.php' );
        $this->settings_admin = new WPBDP__Settings_Admin();

        if ( wpbdp_get_option( 'tracking-on' ) ) {
            $this->site_tracking = new WPBDP_SiteTracking();
        }
    }

    function enqueue_scripts() {
        global $wpbdp;
        global $pagenow;

        wp_enqueue_style(
            'wpbdp-admin',
            WPBDP_URL . 'assets/css/admin.min.css',
            array(),
            WPBDP_VERSION
        );

        wp_enqueue_style( 'thickbox' );

        wp_enqueue_style(
            'wpbdp-frontend-css',
            WPBDP_URL . 'assets/css/wpbdp.min.css',
            array(),
            WPBDP_VERSION
        );

        wp_enqueue_script(
            'wpbdp-frontend-js',
            WPBDP_URL . 'assets/js/wpbdp.min.js',
            array( 'jquery' ),
            WPBDP_VERSION
        );

        wp_enqueue_script(
            'wpbdp-admin-js',
            WPBDP_URL . 'assets/js/admin.min.js',
            array( 'jquery', 'thickbox', 'jquery-ui-sortable' ),
            WPBDP_VERSION
        );

        if ( 'post-new.php' == $pagenow || 'post.php' == $pagenow ) {
            wpbdp_enqueue_jquery_ui_style();

            wp_enqueue_style(
                'wpbdp-listing-admin-metabox',
                WPBDP_URL . 'assets/css/admin-listing-metabox.min.css',
                array(),
                WPBDP_VERSION
            );

            wp_enqueue_style(
                'wpbdp-listing-admin-timeline',
                WPBDP_URL . 'assets/css/admin-listing-timeline.min.css',
                array(),
                WPBDP_VERSION
            );

            wp_enqueue_style( 'wpbdp-dnd-upload' );

            wp_enqueue_script(
                'wpbdp-admin-listing',
                WPBDP_URL . 'assets/js/admin-listing.min.js',
                array( 'wpbdp-admin-js', 'wpbdp-dnd-upload', 'jquery-ui-tooltip' ),
                WPBDP_VERSION
            );

            wp_enqueue_script(
                'wpbdp-admin-listing-metabox',
                WPBDP_URL . 'assets/js/admin-listing-metabox.min.js',
                array( 'wpbdp-admin-js', 'jquery-ui-datepicker' ),
                WPBDP_VERSION
            );

            wp_localize_script( 'wpbdp-admin-listing-metabox', 'wpbdpListingMetaboxL10n', array(
                'planDisplayFormat' => sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=wpbdp-admin-fees&wpbdp_view=edit-fee&id={{plan_id}}' ), '{{plan_label}}' ),
                'noExpiration' => _x( 'Never', 'listing metabox', 'WPBDM' ),
                'yes' => _x( 'Yes', 'listing metabox', 'WPBDM' ),
                'no' => _x( 'No', 'listing metabox', 'WPBDM' )
            ) );

            wp_localize_script( 'wpbdp-admin-listing', 'WPBDP_admin_listings_config', array(
                'messages' => array(
                    'preview_button_tooltip' => __( "Preview is only available after you've saved the first draft. This is due
to how WordPress stores the data.", 'WPBDM' )
                )
            ) );
        }

        // Ask for site tracking if needed.
        if ( ! wpbdp_get_option( 'tracking-on', false ) && get_option( 'wpbdp-show-tracking-pointer', 0 ) && current_user_can( 'administrator' ) ) {
            wp_enqueue_style( 'wp-pointer' );
            wp_enqueue_script( 'wp-pointer' );
            add_action( 'admin_print_footer_scripts', 'WPBDP_SiteTracking::request_js' );
        }

        if ( current_user_can( 'administrator' ) && get_option( 'wpbdp-show-drip-pointer', 0 ) ) {
            wp_enqueue_style( 'wp-pointer' );
            wp_enqueue_script( 'wp-pointer' );
            add_action( 'admin_print_footer_scripts', array( $this, 'drip_pointer' ) );
        }
    }

    /**
     * @since 3.4.1
     */
    public function drip_pointer() {
        $current_user = wp_get_current_user();

        $js = '$.post( ajaxurl, { action: "wpbdp-drip_subscribe",
                                  email: $( "#wpbdp-drip-pointer-email" ).val(),
                                  nonce: "'. wp_create_nonce( 'drip pointer subscribe') .'",
                                  subscribe: "%d" } );';

        $content  = '';
        $content .= _x( 'Find out how to create a compelling, thriving business directory from scratch in this ridiculously actionable (and FREE) 5-part email course. Get a FREE premium module just for signing up.', 'drip pointer', 'WPBDM' ) . '<br /><br />';
        $content .= '<label>';
        $content .= '<b>' . _x( 'Email Address:', 'drip pointer', 'WPBDM' ) . '</b>';
        $content .= '<br />';
        $content .= '<input type="text" id="wpbdp-drip-pointer-email" value="' . esc_attr( $current_user->user_email ) . '" />';
        $content .= '</label>';

        wpbdp_admin_pointer( '#wpadminbar',
                             _x( 'Want to know the Secrets of Building an Awesome Business Directory?', 'drip pointer', 'WPBDM' ),
                             $content,
                             _x( 'Yes, please!', 'drip pointer', 'WPBDM' ),
                             sprintf( $js, 1 ),
                             _x( 'No, thanks', 'drip pointer', 'WPBDM' ),
                             sprintf( $js, 0 ) );
    }

    /**
     * @since 3.5.3
     */
    public function ajax_create_main_page() {
        $nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';

        if ( ! current_user_can( 'administrator' ) || ! $nonce || ! wp_verify_nonce( $nonce, 'create main page' ) )
            exit();

        if ( wpbdp_get_page_id( 'main' ) )
            exit();

        $page = array( 'post_status' => 'publish',
                       'post_title' => _x( 'Business Directory', 'admin', 'WPBDM' ),
                       'post_type' => 'page',
                       'post_content' => '[businessdirectory]' );
        $page_id = wp_insert_post( $page );

        if ( ! $page_id )
            exit();

        $res = new WPBDP_Ajax_Response();
        $res->set_message( str_replace( '<a>',
                                        '<a href="' . get_permalink( $page_id ) . '" target="_blank" rel="noopener">',
                                        _x( 'You\'re all set. Visit your new <a>Business Directory</a> page.', 'admin', 'WPBDM' ) ) );
        $res->send();
    }

    /**
     * @since 3.4.1
     */
    public function ajax_drip_subscribe() {
        $current_user = wp_get_current_user();

        $res = new WPBDP_Ajax_Response();
        $subscribe = ( '1' == $_POST['subscribe'] ) ? true : false;

        if ( ! get_option( 'wpbdp-show-drip-pointer', 0 ) || ! wp_verify_nonce( $_POST['nonce'], 'drip pointer subscribe' ) )
            $res->send_error();

        delete_option( 'wpbdp-show-drip-pointer' );

        if ( $subscribe ) {
            if ( ! filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL ) )
                return $res->send_error( _x( 'Invalid e-mail address.', 'drip pointer', 'WPBDM' ) );

            // Build fields for POSTing to Drip.
            $data = array();
            $data['name'] = '';

            foreach ( array(  'first_name', 'display_name', 'user_login', 'username' ) as $k ) {
                if ( empty ( $current_user->{$k} ) )
                    continue;

                $data['name'] = $current_user->{$k};
                break;
            }

            $data['email'] = $_POST['email'];
            $data['website'] = get_bloginfo( 'url' );
            $data['gmt_offset'] = get_option( 'gmt_offset' );

            $response = wp_remote_post( 'https://www.getdrip.com/forms/6877690/submissions', array(
                        'body' => array(
                            'fields[name]' => $data['name'],
                            'fields[email]' => $data['email'],
                            'fields[website]' => $data['website'],
                            'fields[gmt_offset]' => $data['gmt_offset'] )
            ) );
        }

        $res->send();
    }

    function admin_menu() {
        $badge_number = absint( apply_filters( 'wpbdp_admin_menu_badge_number', 0 ) );
        $count_html = $badge_number ? '<span class="update-plugins"><span class="plugin-count">' . $badge_number . '</span></span>' : '';

        add_menu_page( _x( 'Business Directory Admin', 'admin menu', "WPBDM" ),
                       $count_html ? _x( 'Dir. Admin', 'admin menu', 'WPBDM' ) . $count_html : _x( 'Directory Admin', 'admin menu', 'WPBDM' ),
                       'administrator',
                       'wpbdp_admin',
                       array( &$this, 'main_menu' ),
                       WPBDP_URL . 'assets/images/menuico.png' );

        $menu['wpbdp-admin-add-listing'] = array(
            'title' => _x('Add New Listing', 'admin menu', 'WPBDM'),
            'url' => admin_url( sprintf( 'post-new.php?post_type=%s', WPBDP_POST_TYPE ) )
        );
        // $menu['wpbdp_admin_settings'] = array(
        //     'title' => _x('Manage Options', 'admin menu', 'WPBDM'),
        //     'callback' => array( $this, 'admin_settings' )
        // );
        $menu['wpbdp-admin-fees'] = array(
            'title' => _x( 'Manage Fees', 'admin menu', 'WPBDM' )
        );
        $menu['wpbdp_all_listings'] = array(
            'title' => _x('Listings', 'admin menu', 'WPBDM'),
            'url' => admin_url( 'edit.php?post_type=' . WPBDP_POST_TYPE )
        );
        $menu['wpbdp_admin_formfields'] = array(
            'title' => _x('Manage Form Fields', 'admin menu', 'WPBDM'),
            'callback' => array('WPBDP_FormFieldsAdmin', 'admin_menu_cb')
        );
        $menu['wpbdp_admin_payments'] = array(
            'title' => _x( 'Payment History', 'admin menu', 'WPBDM' )
        );
        $menu['wpbdp_admin_csv'] = array(
            'title' => _x( 'CSV Import & Export', 'admin menu', 'WPBDM' )
        );
        // $menu['wpbdp-csv-import'] = array(
        //     'title' => _x( 'CSV Import', 'admin menu', 'WPBDM' ),
        //     'callback' => array( &$this->csv_import, 'dispatch' )
        // );
        // $menu['wpbdp-csv-export'] = array(
        //     'title' => _x( 'CSV Export', 'admin menu', 'WPBDM' ),
        //     'callback' => array( &$this->csv_export, 'dispatch' )
        // );
        $menu['wpbdp-debug-info'] = array(
            'title' => _x( 'Debug', 'admin menu', 'WPBDM' ),
            'callback' => array( &$this->debug_page, 'dispatch' )
        );

        // FIXME: before next-release
        // if (current_user_can('administrator')) {
        //     $submenu['wpbdp_admin'][0][0] = _x('Main Menu', 'admin menu', 'WPBDM');
        //     $submenu['wpbdp_admin'] = apply_filters( 'wpbdp_admin_menu_reorder', $submenu['wpbdp_admin'] );

        $this->menu = apply_filters( 'wpbdp_admin_menu_items', $menu );
        $this->prepare_menu( $this->menu );

        // Register menu items.
        foreach ( $this->menu as $item_slug => &$item_data ) {
            $item_data['hook'] = add_submenu_page( 'wpbdp_admin',
                                                   $item_data['title'],
                                                   $item_data['label'],
                                                   'administrator',
                                                   $item_slug,
                                                   array( $this, 'menu_dispatch' ) );
        }
        // $item_data = null;
        do_action('wpbdp_admin_menu', 'wpbdp_admin');

        if ( ! current_user_can( 'administrator' ) )
            return;

        add_submenu_page( 'wpbdp_admin',
                          __( 'Uninstall Business Directory Plugin', 'WPBDM' ),
                          __( 'Uninstall', 'WPBDM' ),
                          'administrator',
                          'wpbdp_uninstall',
                          array( $this, 'uninstall_plugin' ) );

        // Handle some special menu items.
        foreach ( $GLOBALS['submenu']['wpbdp_admin'] as &$menu_item ) {
            if ( ! isset( $this->menu[ $menu_item[2] ] ) )
                continue;

            $menu_item_data = $this->menu[ $menu_item[2] ];

            if ( ! empty( $menu_item_data['url'] ) )
                $menu_item[2] = $menu_item_data['url'];
        }

    }

    /**
     * @since 5.0
     */
    private function prepare_menu( &$menu ) {
        $n = 1;

        foreach ( $menu as &$item ) {
            if ( ! isset( $item['priority'] ) )
                $item['priority'] = $n++;

            if ( ! isset( $item['title'] ) )
                $item['title'] = _x( 'Untitled Menu', 'admin', 'WPBDM' );

            if ( ! isset( $item['label'] ) )
                $item['label'] = $item['title'];

            if ( ! isset( $item['file'] ) )
                $item['file'] = '';

            if ( ! isset( $item['callback'] ) )
                $item['callback'] = '';

            if ( ! isset( $item['url'] ) )
                $item['url'] = '';
        }

        WPBDP_Utils::sort_by_property( $menu, 'priority' );
    }

    /**
     * @since 5.0
     */
    function admin_view_dispatch() {
        global $plugin_page;

        if ( ! isset( $plugin_page ) || ! isset( $this->menu[ $plugin_page ] ) )
            return;

        $item = $this->menu[ $plugin_page ];
        $slug = $plugin_page;
        $callback = $item['callback'];


        // Simple callback view are not processed here.
        if ( $callback && is_callable( $callback ) )
            return;

        $id = str_replace( array( 'wpbdp-admin-', 'wpbdp_admin_' ), '', $slug );

        $candidates = array( $item['file'],
                             WPBDP_INC . 'admin/class-admin-' . $id . '.php',
                             WPBDP_INC . 'admin/' . $id . '.php' );
        foreach ( $candidates as $c ) {
            if ( $c && file_exists( $c ) )
                require_once( $c );
        }

        // Maybe loading one of the candidate files made the callback available.
        if ( $callback && is_callable( $callback ) ) {
            ob_start();
            call_user_func( $callback );
            $this->current_controller_output = ob_get_contents();
            ob_end_clean();
            return;
        }

        $classname = 'WPBDP__Admin__' . ucfirst( $id );

        if ( ! class_exists( $classname ) )
            return;

        $this->current_controller = new $classname;

        ob_start();
        $this->current_controller->_dispatch();
        $this->current_controller_output = ob_get_contents();
        ob_end_clean();

        add_action( 'admin_enqueue_scripts', array( $this->current_controller, '_enqueue_scripts' ) );
    }

    /**
     * @since 5.0
     */
    function admin_ajax_dispatch() {
        if ( empty( $_REQUEST['handler'] ) )
            return;

        $handler = trim( $_REQUEST['handler'] );
        $handler = WPBDP__Utils::normalize( $handler );

        $parts = explode( '__', $handler );
        $controller_id = $parts[0];
        $function = isset( $parts[1] ) ? $parts[1] : '';

        $candidates = array( WPBDP_INC . 'admin/class-admin-' . $controller_id . '.php',
                             WPBDP_INC . 'admin/' . $controller_id . '.php' );
        foreach ( $candidates as $c ) {
            if ( ! file_exists( $c ) )
                continue;

            require_once( $c );
            $classname = 'WPBDP__Admin__' . ucfirst( $controller_id );

            if ( ! class_exists( $classname ) )
                continue;

            $controller = new $classname;
            return $controller->_ajax_dispatch();
        }

        exit;
    }

    /**
     * @since 5.0
     */
    function menu_dispatch() {
        $output = $this->current_controller_output;

        if ( $output )
            return print( $output );

        global $plugin_page;
        if ( ! isset( $plugin_page ) || ! isset( $this->menu[ $plugin_page ] ) )
            return;

        $item = $this->menu[ $plugin_page ];
        $slug = $plugin_page;
        $callback = $item['callback'];

        if ( $callback ) {
            call_user_func( $callback );
        }
    }

    /**
     * Makes sure that both the "Directory" and "Directory Admin" menus are next to each other.
     */
    function admin_menu_reorder( $menu_order ) {
        $index1 = array_search( 'wpbdp_admin', $menu_order, true );
        $index2 = array_search( 'edit.php?post_type=' . WPBDP_POST_TYPE, $menu_order, true );

        if ( false === $index1 || false === $index2 )
            return $menu_order;

        $min = min( $index1, $index2 );
        $max = max( $index1, $index2 );

        return array_merge( array_slice( $menu_order, 0, $min ),
                            array( $menu_order[ $min ], $menu_order[ $max ] ),
                            array_slice( $menu_order, $min + 1, $max - $min - 1 ),
                            array_slice( $menu_order, $max + 1 ) );
    }

    public function _checklist_args($args) {
        $args['checked_ontop'] = false;
        return $args;
    }

    public function ajax_formfields_reorder() {
        $response = new WPBDP_Ajax_Response();

        if ( ! current_user_can( 'administrator' ) )
            $response->send_error();

        $order = array_map( 'intval', isset( $_REQUEST['order'] ) ? $_REQUEST['order'] : array() );

        if ( ! $order )
            $response->send_error();

        global $wpbdp;

        if ( ! $wpbdp->formfields->set_fields_order( $order ) )
            $response->send_error();

        $response->send();
    }

    public function ajax_fees_set_order() {
        $nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
        $order = isset( $_POST['fee_order'] ) ? $_POST['fee_order'] : false;

        if ( ! wp_verify_nonce( $nonce, 'change fees order' ) || ! $order )
            exit();

        $res = new WPBDP_Ajax_Response();
        wpbdp_set_option( 'fee-order', $order );
        $res->send();
    }

    public function ajax_fees_reorder() {
        global $wpdb;

        $response = new WPBDP_Ajax_Response();

        if ( ! current_user_can( 'administrator' ) )
            $response->send_error();

        $order = array_map( 'intval', isset( $_REQUEST['order'] ) ? $_REQUEST['order'] : array() );

        if ( ! $order )
            $response->send_error();

        $wpdb->query( "UPDATE {$wpdb->prefix}wpbdp_plans SET weight = 0" );

        $weight = count( $order ) - 1;
        foreach( $order as $fee_id ) {
            $wpdb->update( $wpdb->prefix . 'wpbdp_plans', array( 'weight' => $weight ), array( 'id' => $fee_id ) );
            $weight--;
        }

        $response->send();
    }

    /*
     * AJAX listing actions.
     */
    function ajax_dismiss_notification() {
        $id = isset( $_POST['id'] ) ? $_POST['id'] : '';
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        $user_id = get_current_user_id();

        $res = new WPBDP_Ajax_Response();

        if ( ! $id || ! $nonce || ! $user_id || ! wp_verify_nonce( $nonce, 'dismiss notice ' . $id ) ) {
            $res->send_error();
        }

        if ( has_action( 'wpbdp_admin_ajax_dismiss_notification_' . $id ) ) {
            do_action( 'wpbdp_admin_ajax_dismiss_notification_' . $id, $user_id );
            return;
        }

        update_user_meta( $user_id, 'wpbdp_notice_dismissed[' . $id . ']', true );
        $res->send();
    }

    /**
     * TODO: Use notice, notice-{type} and is-dismissible CSS classes. Those are
     * the current standard
     */
    function admin_notices() {
        if ( ! current_user_can( 'administrator' ) )
            return;

        if ( ! isset( $this->displayed_warnings ) )
            $this->displayed_warnings = array();

        $this->check_server_requirements();
        $this->check_setup();
        $this->check_ajax_compat_mode();
        $this->check_deprecation_warnings();

        do_action( 'wpbdp_admin_notices' );

        foreach ($this->messages as $msg) {
            $msg_sha1 = sha1( is_array( $msg ) ? $msg[0] : $msg );

            if ( in_array( $msg_sha1, $this->displayed_warnings, true ) )
                continue;

            $this->displayed_warnings[] = $msg_sha1;

            if ( is_array( $msg ) ) {
                $class = isset( $msg[1] ) ? $msg[1] : 'updated';
                $text = isset( $msg[0] ) ? $msg[0] : '';
                $extra = isset( $msg[2] ) && is_array( $msg[2] ) ? $msg[2] : array();
            } else {
                $class = 'updated';
                $text = $msg;
                $extra = array();
            }

            echo '<div class="wpbdp-notice ' . $class . '">';
            echo '<p>' . $text . '</p>';

            if ( ! empty ( $extra['dismissible-id'] ) ) {
                printf( '<button type="button" class="notice-dismiss" data-dismissible-id="%s" data-nonce="%s"><span class="screen-reader-text">%s</span></button>',
                        $extra['dismissible-id'],
                        wp_create_nonce( 'dismiss notice ' . $extra['dismissible-id'] ),
                        _x( 'Dismiss this notice.', 'admin', 'WPBDM' ) );
            }

            echo '</div>';
        }

        $this->messages = array();
    }

    function handle_actions() {
        if (!isset($_REQUEST['wpbdmaction']) || !isset($_REQUEST['post']))
            return;

        $action = $_REQUEST['wpbdmaction'];
        $posts = is_array($_REQUEST['post']) ? $_REQUEST['post'] : array($_REQUEST['post']);

        $listings_api = wpbdp_listings_api();

        if (!current_user_can('administrator'))
            exit;

        switch ($action) {
            case 'change-to-publish':
            case 'change-to-pending':
            case 'change-to-draft':
                $new_status = str_replace( 'change-to-', '', $action );

                foreach ($posts as $post_id) {
                    wp_update_post( array( 'ID' => $post_id, 'post_status' => $new_status ) );
                }

                $this->messages[] = _nx('The listing has been updated.', 'The listings have been updated.', count($posts), 'admin', 'WPBDM');
                break;

            case 'change-to-expired':
                foreach ( $posts as $post_id ) {
                    $listing = wpbdp_get_listing( $post_id );
                    $listing->update_plan( array( 'expiration_date' => current_time( 'mysql' ) ) );
                    $listing->set_status( 'expired' );
                }

                $this->messages[] = _nx('The listing has been updated.', 'The listings have been updated.', count($posts), 'admin', 'WPBDM');
                break;

            case 'change-to-complete':
            case 'approve-payments':
                foreach ( $posts as $post_id ) {
                    $pending_payments = WPBDP_Payment::objects()->filter( array( 'listing_id' => $post_id, 'status' => 'pending' ) );

                    foreach ( $pending_payments as $p ) {
                        $p->status = 'completed';
                        $p->save();
                    }
                }

                break;

            case 'assignfee':
                $listing = WPBDP_Listing::get( $posts[0] );
                $fee_id = (int) $_GET['fee_id'];
                $listing->set_fee_plan( $fee_id );

                $this->messages[] = _x('The fee was successfully assigned.', 'admin', 'WPBDM');

                break;

            case 'renewlisting':
                foreach ( $posts as $post_id ):
                    $listing = WPBDP_Listing::get( $post_id );
                    $listing->renew();
                endforeach;

                $this->messages[] = _nx( 'Listing was renewed.', 'Listings were renewed.', count( $posts ), 'admin', 'WPBDM' );
                break;

            case 'send-renewal-email':
                $listing_id = intval( $_GET['listing_id'] );
                $listing = WPBDP_Listing::get( $listing_id );

                if ( ! $listing )
                    break;

                wpbdp()->listing_email_notification->send_notices( 'expiration', '0 days', $listing_id, true );
                $this->messages[] = _x( 'Renewal email sent.', 'admin', 'WPBDM' );

                break;

            case 'delete-flagging':
                WPBDP__Listing_Flagging::remove_flagging( $_GET['listing_id'], $_GET['meta_pos'] );

                $this->messages[] = _nx( 'Listing report deleted.', 'Listing reports deleted.', $_GET['meta_pos'] == 'all' ? 2 : 1, 'admin', 'WPBDM' );
                break;

            case 'send-access-keys':
                $this->send_access_keys( $posts );
                break;

            default:
                do_action( 'wpbdp_admin_directory_handle_action', $action );
                break;
        }

        $_SERVER['REQUEST_URI'] = remove_query_arg( array('wpbdmaction', 'wpbdmfilter', 'transaction_id', 'category_id', 'fee_id', 'u', 'renewal_id', 'flagging_user' ), $_SERVER['REQUEST_URI'] );
    }

    private function send_access_keys( $posts ) {
        $listings_by_email_address = array();

        foreach ( $posts as $post_id ) {
            $listing = wpbdp_get_listing( $post_id );

            if ( ! $listing ) {
                continue;
            }

            $email_address = wpbusdirman_get_the_business_email( $post_id );

            if ( ! $email_address ) {
                continue;
            }

            $listings_by_email_address[ $email_address ][] = $listing;
        }

        $sender = $this->get_access_keys_sender();
        $message_sent = false;

        foreach ( $listings_by_email_address as $email_address => $listings ) {
            try {
                $message_sent = $message_sent || $sender->send_access_keys_for_listings( $listings, $email_address );
            } catch ( Exception $e ) {
                // pass
            }
        }

        // TODO: Add more descriptive messages to indicate how many listings were
        // processed successfully, how many failed and why.
        if ( $message_sent ) {
            $this->messages[] = _x( 'Access keys sent.', 'admin', 'WPBDM' );
        } else {
            $this->messages[] = _x( "The access keys couldn't be sent.", 'admin', 'WPBDM' );
        }

        // TODO: Redirect and show messages on page load.
        // if ( wp_redirect( remove_query_arg( array( 'action', 'post', 'wpbdmaction' ) ) ) ) {
        //     exit();
        // }
    }

    public function get_access_keys_sender() {
        return new WPBDP__Access_Keys_Sender();
    }

    public function _dropdown_users_args( $query_args, $r ) {
        global $post;

        if ( isset( $r['wpbdp_skip_dropdown_users_args'] ) ) {
            return $query_args;
        }

        if ( is_admin() && get_post_type( $post ) == WPBDP_POST_TYPE ) {
            add_filter( 'wp_dropdown_users', array( $this, '_dropdown_users' ) );
            array_push( $this->dropdown_users_args_stack, $r );
        }

        return $query_args;
    }

    public function _dropdown_users( $output ) {
        global $post;

        remove_filter( 'wp_dropdown_users', array( $this, '_dropdown_users' ) );

        if ( ! $this->dropdown_users_args_stack ) {
            return $output;
        }

        $args = array_pop( $this->dropdown_users_args_stack );

        if ( $args['show_option_none'] ) {
            $selected = $args['option_none_value'];
        } else {
            $selected = ! empty( $post->ID ) ? $post->post_author : wp_get_current_user()->ID;
        }

        return wp_dropdown_users( array_merge( $args, array(
            'echo' => false,
            'selected' => $selected,
            'include_selected' => true,
            'who' => 'all',
            'wpbdp_skip_dropdown_users_args' => true,
        ) ) );
    }

    public function add_custom_taxonomy_columns( $cols ) {
        $newcols = array_merge( array_slice( $cols, 0, 1 ),
                                array( 'id' => _x( 'ID', 'admin category id', 'WPBDM' ) ),
                                array_slice( $cols, 1, -1),
                                array( 'posts' => _x('Listing Count', 'admin', 'WPBDM') ) );
        return $newcols;
    }

    public function tag_taxonomy_columns( $cols ) {
        $newcols = array_merge( array_slice( $cols, 0, -1 ),
                                array( 'posts' => _x('Listing Count', 'admin', 'WPBDM') ) );
        return $newcols;
    }

    public function custom_taxonomy_columns( $value, $column_name, $id ) {
        if ( $column_name == 'id' )
            return $id;

        return $value;
    }

    /* Uninstall. */
    public function uninstall_plugin() {
        global $wpdb;

        $nonce = isset( $_POST['_wpnonce'] ) ? trim( $_POST['_wpnonce'] ) : '';

        if ( $nonce && wp_verify_nonce( $nonce, 'uninstall bd' ) ) {
            $installer = new WPBDP_Installer();

            // Delete listings.
            $post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = %s", WPBDP_POST_TYPE ) );

            foreach ( $post_ids as $post_id )
                wp_delete_post( $post_id, true );

            // Drop tables.
            $tables = array_keys( $installer->get_database_schema() );
            foreach ( $tables as &$table ) {
                $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpbdp_{$table}" );
            }

            // Delete options.
            delete_option( 'wpbdp-db-version' );
            delete_option( 'wpbusdirman_db_version' );
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'wpbdp%' ) );

            // Clear scheduled hooks.
            wp_clear_scheduled_hook('wpbdp_hourly_events');
            wp_clear_scheduled_hook('wpbdp_daily_events');

            $tracking = new WPBDP_SiteTracking();
            $tracking->track_uninstall( isset( $_POST['uninstall'] ) ? $_POST['uninstall'] : null );

            // Deactivate plugin.
            $real_path = WPBDP_PATH . 'business-directory-plugin.php';
            // if the plugin directory is a symlink, plugin_basename will return
            // the real path, which may not be the same path WP associated to
            // the plugin. Plugin paths must be of the form:
            // wp-content/plugins/plugin-directory/plugin-file.php
            $fixed_path = WP_CONTENT_DIR . '/plugins/' . basename(dirname($real_path)) . '/' . basename($real_path);
            deactivate_plugins($fixed_path, true);

            echo wpbdp_render_page(WPBDP_PATH . 'templates/admin/uninstall-complete.tpl.php');
        } else {
            echo wpbdp_render_page(WPBDP_PATH . 'templates/admin/uninstall-confirm.tpl.php');
        }
    }

    /* Required pages check. */
    public function check_for_required_pages() {
        if ( ! wpbdp_get_page_id( 'main' ) && current_user_can( 'administrator' ) ) {
            $message = _x('<b>Business Directory Plugin</b> requires a page with the <tt>[businessdirectory]</tt> shortcode to function properly.', 'admin', 'WPBDM');
            $message .= '<br />';
            $message .= _x('You can create this page by yourself or let Business Directory do this for you automatically.', 'admin', 'WPBDM');
            $message .= '<p>';
            $message .= sprintf( '<a href="#" class="button wpbdp-create-main-page-button" data-nonce="%s">%s</a>',
                                 wp_create_nonce( 'create main page' ),
                                 _x( 'Create required pages for me', 'admin', 'WPBDM' ) );
            $message .= '</p>';

            $this->messages[] = array($message, 'error');
        }
    }

    /**
     * @since 3.6.10
     */
    function process_admin_action() {
        if ( isset( $_REQUEST['wpbdp-action'] ) ) {
            do_action( 'wpbdp_action_' . $_REQUEST['wpbdp-action'] );
//            do_action( 'wpbdp_dispatch_' . $_REQUEST['wpbdp-action'] );
        }
    }

    private function check_server_requirements() {
        $php_version = explode( '.', phpversion() );
        $installed_version = $php_version[0] . '.' . $php_version[1];

        // PHP 5.6 is required.
        if ( version_compare( $installed_version, '5.6', '>=' ) ) {
            return;
        }

        $dismissed = get_transient( 'wpbdp_server_requirements_warning_dismissed' );
        if ( $dismissed ) {
            return;
        }

        $this->messages[] = array(
            sprintf(
                _x( '<strong>Business Directory Plugin</strong> requires <strong>PHP 5.6</strong> or later, but your server is running version <strong>%s</strong>. Please ask your provider to upgrade in order to prevent any issues with the plugin.', 'admin', 'WPBDM' ),
                $installed_version
            ),
            'error dismissible',
            array( 'dismissible-id' => 'server_requirements' )
        );
    }

    public function ajax_dismiss_notification_server_requirements() {
        set_transient( 'wpbdp_server_requirements_warning_dismissed', true, WEEK_IN_SECONDS );
    }

    public function check_setup() {
        global $pagenow;

        if ( 'admin.php' != $pagenow || ! isset( $_GET['page'] ) || 'wpbdp_settings' != $_GET['page'] )
            return;

        // Registration disabled message.
        if ( wpbdp_get_option( 'require-login')
             && ! get_option( 'users_can_register')
             && ! get_user_meta( get_current_user_id(), 'wpbdp_notice_dismissed[registration_disabled]', true ) ) {
                $this->messages[] = array(
                    str_replace( array( '[', ']' ), array( '<a href="' . admin_url( 'options-general.php' )  . '">', '</a>' ), _x( 'We noticed you want your Business Directory users to register before posting listings, but Registration for your site is currently disabled. Go [here] and check "Anyone can register" to make sure BD works properly.', 'admin', 'WPBDM' ) ),
                    'error dismissible',
                    array( 'dismissible-id' => 'registration_disabled' )
                );
        }
    }

    public function check_ajax_compat_mode() {
        global $pagenow;

        if ( 'admin.php' != $pagenow || ! isset( $_GET['page'] ) || 'wpbdp_settings' != $_GET['page'] )
            return;

        $notice = get_option( 'wpbdp-ajax-compat-mode-notice' );

        if ( ! $notice )
            return;

        $this->messages[] = $notice;
        delete_option( 'wpbdp-ajax-compat-mode-notice' );
    }

    private function check_deprecation_warnings() {
        global $wpbdp_deprecation_warnings;

        if ( ! empty( $wpbdp_deprecation_warnings ) ) {
            foreach ( $wpbdp_deprecation_warnings as $warning ) {
                $this->messages[] = $warning;
            }
        }
    }

    public function main_menu() {
        echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/home.tpl.php' );
    }

    public function register_listings_views() {
        $view = new WPBDP__ListingsWithNoFeePlanView();

        add_filter( 'wpbdp_admin_directory_views', array( $view, 'filter_views' ), 10, 2 );
        add_filter( 'wpbdp_admin_directory_filter', array( $view, 'filter_query_pieces' ), 10, 2 );
    }
}

function wpbdp_admin_message( $msg, $kind = '', $extra = array() ) {
    global $wpbdp;
    $wpbdp->admin->messages[] = ( $kind || $extra ) ? array( $msg, $kind, $extra ) : $msg;
}

}
