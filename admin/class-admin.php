<?php
require_once( WPBDP_PATH . 'admin/admin-pages.php' );
require_once( WPBDP_PATH . 'admin/class-admin-listings.php' );
require_once( WPBDP_PATH . 'admin/form-fields.php' );
require_once( WPBDP_PATH . 'admin/csv-import.php' );
require_once( WPBDP_PATH . 'admin/csv-export.php' );
require_once( WPBDP_PATH . 'admin/class-listing-fields-metabox.php' );
require_once( WPBDP_PATH . 'admin/page-debug.php' );
require_once( WPBDP_PATH . 'admin/class-admin-controller.php' );

if ( ! class_exists( 'WPBDP_Admin' ) ) {

class WPBDP_Admin {

    private $menu = array();
    public $messages = array();

    function __construct() {
        add_action('admin_init', array($this, 'handle_actions'));

        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'check_for_required_fields'));
        add_action('admin_init', array($this, 'check_for_required_pages'));
        add_action('admin_init', array($this, 'check_payments_possible'));

        add_action( 'admin_init', array( &$this, 'process_admin_action' ), 999 );

        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Adds admin menus.
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) ); 

        // Enables reordering of admin menus.
        add_filter( 'custom_menu_order', '__return_true' );

        // Puts the "Directory" and "Directory Admin" next to each other.
        add_filter( 'menu_order', array( &$this, 'admin_menu_reorder' ) );

        add_filter('wp_dropdown_users', array($this, '_dropdown_users'));

        add_filter( 'manage_edit-' . WPBDP_CATEGORY_TAX . '_columns', array( &$this, 'add_custom_taxonomy_columns' ) );
        add_filter( 'manage_edit-' . WPBDP_TAGS_TAX . '_columns', array( &$this, 'tag_taxonomy_columns' ) );
        add_action( 'manage_' . WPBDP_CATEGORY_TAX . '_custom_column', array( &$this, 'custom_taxonomy_columns' ), 10, 3 );

        add_filter('wp_terms_checklist_args', array($this, '_checklist_args')); // fix issue #152

        add_action( 'wp_ajax_wpbdp-formfields-reorder', array( &$this, 'ajax_formfields_reorder' ) );

        add_action( 'wp_ajax_wpbdp-admin-fees-set-order', array( &$this, 'ajax_fees_set_order' ) );
        add_action( 'wp_ajax_wpbdp-admin-fees-reorder', array( &$this, 'ajax_fees_reorder' ) );

        add_action( 'wp_ajax_wpbdp-listing_set_expiration', array( &$this, 'ajax_listing_set_expiration' ) );
        add_action( 'wp_ajax_wpbdp-listing_change_fee', array( &$this, 'ajax_listing_change_fee' ) );

        add_action( 'wp_ajax_wpbdp-renderfieldsettings', array( 'WPBDP_FormFieldsAdmin', '_render_field_settings' ) );

        add_action( 'wp_ajax_wpbdp-create-main-page', array( &$this, 'ajax_create_main_page' ) );
        add_action( 'wp_ajax_wpbdp-drip_subscribe', array( &$this, 'ajax_drip_subscribe' ) );
        add_action( 'wp_ajax_wpbdp-set_site_tracking', 'WPBDP_SiteTracking::handle_ajax_response' );
        add_action( 'wp_ajax_wpbdp_dismiss_notification', array( &$this, 'ajax_dismiss_notification' ) );
        // Reset settings action.
        add_action( 'wpbdp_action_reset-default-settings', array( &$this, 'settings_reset_defaults' ) );

        $this->listings = new WPBDP_Admin_Listings();
        $this->csv_import = new WPBDP_CSVImportAdmin();
        $this->csv_export = new WPBDP_Admin_CSVExport();
        $this->debug_page = new WPBDP_Admin_Debug_Page();
    }

    function enqueue_scripts() {
        global $wpbdp;
        global $pagenow;

        $debug_on = $wpbdp->is_debug_on();

        wp_enqueue_style( 'wpbdp-admin',
                          WPBDP_URL . 'admin/resources/admin' . ( ! $debug_on ? '.min' : '' ) . '.css');
        wp_enqueue_style( 'thickbox' );

        wp_enqueue_script( 'wpbdp-frontend-js',
                           WPBDP_URL . 'core/js/wpbdp.min.js',
                           array( 'jquery' ) );
        wp_enqueue_script( 'wpbdp-admin-js',
                           WPBDP_URL . 'admin/resources/admin' . ( ! $debug_on ? '.min' : '' ) . '.js',
                           array( 'jquery', 'thickbox', 'jquery-ui-sortable' ) );

        if ( 'post-new.php' == $pagenow || 'post.php' == $pagenow ) {
            wp_enqueue_style( 'wpbdp-jquery-ui-css',
                              'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/redmond/jquery-ui.css' );
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_style( 'wpbdp-listing-admin-metabox', WPBDP_URL . 'admin/css/listing-metabox.css' );

            wp_enqueue_style( 'wpbdp-dnd-upload' );
            wp_enqueue_script( 'wpbdp-admin-listing', WPBDP_URL . 'admin/js/listing.js', array( 'wpbdp-admin-js', 'wpbdp-dnd-upload' ) );
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
        global $current_user;
        get_currentuserinfo();

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
                                        '<a href="' . get_permalink( $page_id ) . '" target="_blank">',
                                        _x( 'You\'re all set. Visit your new <a>Business Directory</a> page.', 'admin', 'WPBDM' ) ) );
        $res->send();
    }

    /**
     * @since 3.4.1
     */
    public function ajax_drip_subscribe() {
        global $current_user;
        get_currentuserinfo();

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
                       WPBDP_URL . 'admin/resources/menuico.png' );

        $menu['wpbdp-admin-add-listing'] = array(
            'title' => _x('Add New Listing', 'admin menu', 'WPBDM'),
            'url' => admin_url( sprintf( 'post-new.php?post_type=%s', WPBDP_POST_TYPE ) )
        );
        $menu['wpbdp_admin_settings'] = array(
            'title' => _x('Manage Options', 'admin menu', 'WPBDM'),
            'callback' => array( $this, 'admin_settings' )
        );
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
        $menu['wpbdp-csv-import'] = array(
            'title' => _x( 'CSV Import', 'admin menu', 'WPBDM' ),
            'callback' => array( &$this->csv_import, 'dispatch' )
        );
        $menu['wpbdp-csv-export'] = array(
            'title' => _x( 'CSV Export', 'admin menu', 'WPBDM' ),
            'callback' => array( &$this->csv_export, 'dispatch' )
        );
        $menu['wpbdp-debug-info'] = array(
            'title' => _x( 'Debug', 'admin menu', 'WPBDM' ),
            'callback' => array( &$this->debug_page, 'dispatch' )
        );
        $menu['wpbdp_uninstall'] = array(
            'title' => _x('Uninstall Business Directory Plugin', 'admin menu', 'WPBDM'),
            'label' => _x('Uninstall', 'admin menu', 'WPBDM'),
            'callback' => array($this, 'uninstall_plugin')
        );
        // FIXME: before next-release
        // if (current_user_can('administrator')) {
        //     $submenu['wpbdp_admin'][0][0] = _x('Main Menu', 'admin menu', 'WPBDM');
        //     $submenu['wpbdp_admin'] = apply_filters( 'wpbdp_admin_menu_reorder', $submenu['wpbdp_admin'] );

        $this->prepare_menu( $menu );
        $this->menu = apply_filters( 'wpbdp_admin_menu_items', $menu );

        // Register menu items.
        foreach ( $this->menu as $item_slug => &$item_data ) {
            $item_data['hook'] = add_submenu_page( 'wpbdp_admin',
                                                   $item_data['title'],
                                                   $item_data['label'],
                                                   'administrator',
                                                   $item_slug,
                                                   array( $this, 'menu_dispatch' ) );
        }
        $item_data = null;
        do_action('wpbdp_admin_menu', 'wpbdp_admin');

        if ( ! current_user_can( 'administrator' ) )
            return;

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
     * @since next-release
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
     * @since next-release
     */
    function menu_dispatch() {
        global $plugin_page;

        if ( ! isset( $this->menu[ $plugin_page ] ) )
            return;

        $item = $this->menu[ $plugin_page ];
        $slug = $plugin_page;
        $callback = $item['callback'];

        if ( $callback && is_callable( $callback ) )
            return call_user_func( $callback );

        $id = str_replace( array( 'wpbdp-admin-', 'wpbdp_admin_' ), '', $slug );

        $candidates = array( $item['file'],
                             WPBDP_PATH . 'admin/class-admin-' . $id . '.php',
                             WPBDP_PATH . 'admin/' . $id . '.php' );
        foreach ( $candidates as $c ) {
            if ( $c && file_exists( $c ) )
                require_once( $c );
        }

        // Maybe loading one of the candidate files made the callback available.
        if ( $callback && is_callable( $callback ) )
            return call_user_func( $callback );

        $classname = 'WPBDP__Admin__' . ucfirst( $id );

        if ( ! class_exists( $classname ) )
            return;

        $admin = new $classname;
        return $admin->_dispatch();
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

        $wpdb->update( $wpdb->prefix . 'wpbdp_fees', array( 'weight' => 0 ) );

        $weight = count( $order ) - 1;
        foreach( $order as $fee_id ) {
            $wpdb->update( $wpdb->prefix . 'wpbdp_fees', array( 'weight' => $weight ), array( 'id' => $fee_id ) );
            $weight--;
        }

        $response->send();
    }

    /*
     * AJAX listing actions.
     */
    public function ajax_listing_set_expiration() {
        $response = new WPBDP_Ajax_Response();

        $listing_id = intval( isset( $_POST['listing_id'] ) ? $_POST['listing_id'] : 0 );
        $expiration_time = isset( $_POST['expiration_date'] ) ? ( 'never' == $_POST['expiration_date'] ? 'never' : date( 'Y-m-d 00:00:00', strtotime( trim( $_POST['expiration_date'] ) ) ) ) : '';

        if ( ! $listing_id || ! $expiration_time || ! current_user_can( 'administrator' ) )
            $response->send_error();

        global $wpdb;

        if ( 'never' == $expiration_time ) {
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listings_plans SET expiration_date = NULL WHERE listing_id = %d", $listing_id ) );
        } else {
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listings_plans SET expiration_date = %s WHERE listing_id = %d", $expiration_time, $listing_id ) );
        }

        $response->add( 'formattedExpirationDate', 'never' == $expiration_time ? _x( 'never', 'admin infometabox', 'WPBDM' ) : date_i18n( get_option( 'date_format' ), strtotime( $expiration_time ) ) );
        $response->send();
    }

    public function ajax_listing_change_fee() {
        global $wpdb;

        $response = new WPBDP_Ajax_Response();

        if ( ! current_user_can( 'administrator' ) )
            $response->send_error();

        $listing = WPBDP_Listing::get( $_REQUEST['listing_id'] );

        if ( ! $listing )
            $response->send_error();


        $plans = WPBDP_Fee_Plan::find(); // FIXME: before next-release
        $response->add( 'html', wpbdp_render_page( WPBDP_PATH . 'admin/templates/listing-change-fee.tpl.php',
                                                   array( 'listing' => $listing,
                                                          'plans' => $plans ) ) );
        $response->send();
    }

    function ajax_dismiss_notification() {
        $id = isset( $_POST['id'] ) ? $_POST['id'] : '';
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        $user_id = get_current_user_id();

        $res = new WPBDP_Ajax_Response();

        if ( ! $id || ! $nonce || ! $user_id || ! wp_verify_nonce( $nonce, 'dismiss notice ' . $id ) )
            $res->send_error();

        update_user_meta( $user_id, 'wpbdp_notice_dismissed[' . $id . ']', true );
        $res->send();
    }

    function admin_notices() {
        if ( ! current_user_can( 'administrator' ) )
            return;

        if ( ! isset( $this->displayed_warnings ) )
            $this->displayed_warnings = array();

        $this->check_compatibility();
        $this->check_setup();
        $this->check_ajax_compat_mode();

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
        $upgrades_api = wpbdp_listing_upgrades_api();

        if (!current_user_can('administrator'))
            exit;

        switch ($action) {
            case 'publish':
                foreach ($posts as $post_id) {
                    wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
                }

                $this->messages[] = _nx('The listing has been published.',
                                        'The listings have been published.',
                                        count($posts),
                                        'admin',
                                        'WPBDM');
                break;

            case 'setaspaid':
                $ok = true;

                foreach ($posts as $post_id) {
                    $listing = WPBDP_Listing::get( $post_id );

                    if ( ! $listing->mark_as_paid() )
                        $ok = false;
                }

                if ( $ok ) {
                    $this->messages[] = _nx('The listing status has been set as paid.',
                                            'The listings status has been set as paid.',
                                            count($posts),
                                            'admin',
                                            'WPBDM');
                } else {
                    $msg = _nx( 'Only invoices containing non-recurring items were marked as paid. Please review the <a>Transactions</a> tab for the listing to manage recurring items or check the gateway\'s backend.',
                                'Only invoices containing non-recurring items were marked as paid. Recurring payments have to be managed through the gateway.',
                                count( $posts ),
                                'admin',
                                'WPBDM' );

                    if ( 1 == count( $posts ) )
                        $msg = str_replace( '<a>', '<a href="' . admin_url( 'post.php?post=' . $posts[0] . '&action=edit#listing-metabox-transactions' ) . '">', $msg );

                    $this->messages[] = array( $msg, 'error' );
                }

                break;

            case 'upgradefeatured':
                foreach ( $posts as $post_id ):
                    $upgrades_api->set_sticky( $post_id, 'sticky', true );
                endforeach;

                $this->messages[] = _nx('The listing has been upgraded.',
                                        'The listings have been upgraded.',
                                        count($posts),
                                        'admin',
                                        'WPBDM');
                break;

            case 'cancelfeatured':
                foreach ($posts as $post_id ):
                    $upgrades_api->set_sticky( $post_id, 'normal' );
                endforeach;

                $this->messages[] = _nx('The listing has been downgraded.',
                                        'The listings have been downgraded.',
                                        count($posts),
                                        'admin',
                                        'WPBDM');
                break;

            case 'assignfee':
                $listing = WPBDP_Listing::get( $posts[0] );
                $fee_id = (int) $_GET['fee_id'];
                $listing->set_fee_plan( $fee_id );

                $this->messages[] = _x('The fee was successfully assigned.', 'admin', 'WPBDM');

                break;

            // FIXME: before next-release
            case 'renewlisting':
                foreach ( $posts as $post_id ):
                    $listings_api->auto_renew( $post_id );
                endforeach;

                $this->messages[] = _nx( 'Listing was renewed.', 'Listings were renewed.', count( $posts ), 'admin', 'WPBDM' );
                break;

            case 'send-renewal-email':
                $listing_id = intval( $_GET['listing_id'] );
                $listing = WPBDP_Listing::get( $listing_id );

                if ( ! $listing )
                    break;

                $listing->send_renewal_notice( 'auto', true );
                $this->messages[] = _x( 'Renewal email sent.', 'admin', 'WPBDM' );

                break;

            default:
                do_action( 'wpbdp_admin_directory_handle_action', $action );
                break;
        }

        $_SERVER['REQUEST_URI'] = remove_query_arg( array('wpbdmaction', 'wpbdmfilter', 'transaction_id', 'category_id', 'fee_id', 'u', 'renewal_id'), $_SERVER['REQUEST_URI'] );
    }

    public function _dropdown_users($output) {
        global $post;

        if (is_admin() && get_post_type($post) == WPBDP_POST_TYPE) {
            remove_filter('wp_dropdown_users', array($this, '_dropdown_users'));
            $select = wp_dropdown_users(array(
                'echo' => false,
                'name' => 'post_author',
                'selected' => !empty($post->ID) ? $post->post_author : wp_get_current_user()->ID,
                'include_selected' => true,
                'who' => 'all'
            ));
            add_filter('wp_dropdown_users', array($this, '_dropdown_users'));
            return $select;

        }

        return $output;
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


    /* Settings page */
    public function register_settings() {
        global $wpbdp;
        $wpbdp->settings->register_in_admin();
    }

    public function admin_settings() {
        global $wpbdp;

        flush_rewrite_rules(false);

        $_SERVER['REQUEST_URI'] = remove_query_arg( 'deletedb', $_SERVER['REQUEST_URI'] );

        $reset_defaults = ( isset( $_GET['action'] ) && 'reset' == $_GET['action'] );
        if ( $reset_defaults ) {
            echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/settings-reset.tpl.php' );
            return;
        }

        $_SERVER['REQUEST_URI'] = remove_query_arg( 'deletedb', $_SERVER['REQUEST_URI'] );

        wpbdp_render_page(WPBDP_PATH . 'admin/templates/settings.tpl.php',
                          array('wpbdp_settings' => $wpbdp->settings),
                          true);
    }

    public function settings_reset_defaults() {
        $do_reset = ( ! empty ( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'reset defaults' ) );

        if ( $do_reset ) {
            global $wpbdp;
            $wpbdp->settings->reset_defaults();
        }

        wp_redirect( admin_url( 'admin.php?page=wpbdp_admin_settings&settings-updated=1&groupid=general' ) );
        exit();
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

            echo wpbdp_render_page(WPBDP_PATH . 'admin/templates/uninstall-complete.tpl.php');
        } else {
            echo wpbdp_render_page(WPBDP_PATH . 'admin/templates/uninstall-confirm.tpl.php');
        }
    }

    /* Required fields check. */
    public function check_for_required_fields() {
        global $wpbdp;

        if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'wpbdp_admin_formfields' &&
             isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'createrequired' ) {
            // do not display the warning inside the page creating the required fields
            return;
        }

        if ( $missing = $wpbdp->formfields->get_missing_required_fields() ) {
            if (count($missing) > 1) {
                $message = sprintf(_x('<b>Business Directory Plugin</b> requires fields with the following associations in order to work correctly: <b>%s</b>.', 'admin', 'WPBDM'), join(', ', $missing));
            } else {
                $message = sprintf(_x('<b>Business Directory Plugin</b> requires a field with a <b>%s</b> association in order to work correctly.', 'admin', 'WPBDM'), array_pop( $missing ) );
            }

            $message .= '<br />';
            $message .= _x('You can create these custom fields by yourself inside "Manage Form Fields" or let Business Directory do this for you automatically.', 'admin', 'WPBDM');
            $message .= '<br /><br />';
            $message .= sprintf('<a href="%s">%s</a> | ',
                                admin_url('admin.php?page=wpbdp_admin_formfields'),
                                _x('Go to "Manage Form Fields"', 'admin', 'WPBDM'));
            $message .= sprintf('<a href="%s">%s</a>',
                                admin_url('admin.php?page=wpbdp_admin_formfields&action=createrequired'),
                                _x('Create these required fields for me', 'admin', 'WPBDM'));

            $this->messages[] = array($message, 'error');
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

    /* Check if payments are enabled but no gateway available. */
    public function check_payments_possible() {
        // show messages only in directory admin pages
        if ( (isset($_GET['post_type']) && $_GET['post_type'] == WPBDP_POST_TYPE) ||
             (isset($_GET['page']) && stripos($_GET['page'], 'wpbdp_') !== FALSE) ) {

            if ($errors = wpbdp_payments_api()->check_config()) {
                foreach ($errors as $error) $this->messages[] = array($error, 'error');
            }
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

    private function check_compatibility() {
        global $wpbdp;

        $modules_msg = '';
        $modules = $wpbdp->get_premium_modules_data();

        foreach ( $modules as $module_id => &$module_info ) {
            if ( $module_info['installed'] && version_compare( $module_info['version'], $module_info['required'], '<' ) ) {
                $modules_msg .= '<li class="module-info">';
                $modules_msg .= 'business-directory-<b>' . $module_id . '</b><br />';
                $modules_msg .= '<span class="module-version">';
                $modules_msg .= sprintf( _x( 'Installed: %s', 'admin compat', 'WPBDM' ), '<b>' . ( null === $module_info['version'] ? _x( 'N/A', 'admin compat', 'WPBDM' ) : $module_info['version'] ) . '</b>' );
                $modules_msg .= '</span> -- ';
                $modules_msg .= '<span class="module-required">';
                $modules_msg .= sprintf( _x( 'Required: %s', 'admin compat', 'WPBDM' ), '<b>' . $module_info['required'] . '</b>' );
                $modules_msg .= '</span>';
                $modules_msg .= '</li>';

/*                $modules_msg .= sprintf( _x( '&#149; %s (installed: %s, required: %s).', 'admin compat', 'WPBDM' ),
                                         '<span class="module-name">business-directory-<b>' . $module_id . '</b></span>',
                                         '<span class="module-version">' . ( null === $module_info['version'] ? _x( 'N/A', 'admin compat', 'WPBDM' ) : $module_info['version'] ) .  $module_info['required'] . '</span>' );*/
            }
        }

        if ( $modules_msg ) {
            $message  = '';
            $message .= _x( 'Business Directory has detected some incompatible premium module versions installed.', 'admin compat', 'WPBDM' );
            $message .= '<br />';
            $message .= _x( 'Please upgrade to the required versions indicated below to make sure everything functions properly.', 'admin compat', 'WPBDM' );
            $message .= '<ul class="wpbdp-module-compat-check">';
            $message .= $modules_msg;
            $message .= '</ul>';

            $this->messages[] = array( $message, 'error' );
        }
    }

    public function check_setup() {
        global $pagenow;

        if ( 'admin.php' != $pagenow || ! isset( $_GET['page'] ) || 'wpbdp_admin_settings' != $_GET['page'] )
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

        if ( 'admin.php' != $pagenow || ! isset( $_GET['page'] ) || 'wpbdp_admin_settings' != $_GET['page'] )
            return;

        $notice = get_option( 'wpbdp-ajax-compat-mode-notice' );

        if ( ! $notice )
            return;

        $this->messages[] = $notice;
        delete_option( 'wpbdp-ajax-compat-mode-notice' );
    }

    public function main_menu() {
        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/home.tpl.php' );
    }

}

function wpbdp_admin_message( $msg, $kind = '', $extra = array() ) {
    global $wpbdp;
    $wpbdp->admin->messages[] = ( $kind || $extra ) ? array( $msg, $kind, $extra ) : $msg;
}

}
