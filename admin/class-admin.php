<?php
require_once( WPBDP_PATH . 'admin/admin-pages.php' );
require_once( WPBDP_PATH . 'admin/class-admin-listings.php' );
require_once( WPBDP_PATH . 'admin/fees.php' );
require_once( WPBDP_PATH . 'admin/form-fields.php' );
require_once( WPBDP_PATH . 'admin/payments.php' );
// require_once( WPBDP_PATH . 'admin/transactions.php' );
require_once( WPBDP_PATH . 'admin/csv-import.php' );
require_once( WPBDP_PATH . 'admin/csv-export.php' );
require_once( WPBDP_PATH . 'admin/listing-metabox.php' );
require_once( WPBDP_PATH . 'admin/class-listing-fields-metabox.php' );
require_once( WPBDP_PATH . 'admin/page-debug.php' );

if ( ! class_exists( 'WPBDP_Admin' ) ) {

class WPBDP_Admin {

    public $messages = array();

    function __construct() {
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_init', array($this, 'register_settings'));

        add_action('admin_init', array($this, 'check_for_required_fields'));
        add_action('admin_init', array($this, 'check_for_required_pages'));
        add_action('admin_init', array($this, 'check_payments_possible'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Admin menu.
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
        add_filter( 'custom_menu_order', '__return_true' );
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
        add_action( 'wp_ajax_wpbdp-listing_remove_category', array( &$this, 'ajax_listing_remove_category' ) );
        add_action( 'wp_ajax_wpbdp-listing_change_fee', array( &$this, 'ajax_listing_change_fee' ) );

        add_action( 'wp_ajax_wpbdp-renderfieldsettings', array( 'WPBDP_FormFieldsAdmin', '_render_field_settings' ) );

        add_action( 'wp_ajax_wpbdp-create-main-page', array( &$this, 'ajax_create_main_page' ) );
        add_action( 'wp_ajax_wpbdp-drip_subscribe', array( &$this, 'ajax_drip_subscribe' ) );
        add_action( 'wp_ajax_wpbdp-set_site_tracking', 'WPBDP_SiteTracking::handle_ajax_response' );

        $this->listings = new WPBDP_Admin_Listings();
        $this->csv_import = new WPBDP_CSVImportAdmin();
        $this->csv_export = new WPBDP_Admin_CSVExport();
        $this->payments = new WPBDP_Admin_Payments();
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
                           WPBDP_URL . 'core/js/wpbdp' . ( ! $debug_on ? '.min' : '' ) . '.js',
                           array( 'jquery' ) );
        wp_enqueue_script( 'wpbdp-admin-js',
                           WPBDP_URL . 'admin/resources/admin' . ( ! $debug_on ? '.min' : '' ) . '.js',
                           array( 'jquery', 'thickbox', 'jquery-ui-sortable' ) );

        if ( 'post-new.php' == $pagenow || 'post.php' == $pagenow ) {
            wp_enqueue_style( 'wpbdp-jquery-ui-css',
                              'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/redmond/jquery-ui.css' );
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_style( 'wpbdp-listing-admin-metabox', WPBDP_URL . 'admin/css/listing-metabox.min.css' );

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
    private function get_drip_api_info( $key = '' ) {
        $info = array(
            'url' => 'https://api.getdrip.com/v1',
            'api_key' => 'usskquea6f3yipbcitys',
            'account_id' => '4037583',
            'campaign' => '4494091'
        );

        if ( array_key_exists( $key, $info ) )
            return $info[ $key ];

        return '';
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
                $res->send_error();

            // Make request to Drip.
            $payload = array( 'status' => 'active',
                              'subscribers' => array( array(
                                      'email' => $_POST['email'],
                                      'utc_offset' => 660,
                                      'double_optin' => false,
                                      'starting_email_index' => 0,
                                      'reactivate_if_subscribed' => true,
                                      'custom_fields' => array( 'name' => $current_user->display_name,
                                                                'url' => get_bloginfo( 'url' ) )
                              ) )
                            );
            $url = sprintf( '%s/%s/campaigns/%s/subscribers',
                            $this->get_drip_api_info( 'url' ),
                            $this->get_drip_api_info( 'account_id' ),
                            $this->get_drip_api_info( 'campaign' ) );

            if ( function_exists( 'curl_init' ) ) {
                $ch = curl_init();
                curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
                curl_setopt( $ch, CURLOPT_USERPWD, $this->get_drip_api_info( 'api_key' ) . ':' );
                curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)" );
                curl_setopt( $ch, CURLOPT_HEADER, false );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
                curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $payload ) );
                curl_setopt( $ch, CURLOPT_URL, $url );
                $result = curl_exec( $ch );
                curl_close( $ch );
            }
        }

        $res->send();
    }

    function admin_menu() {
        add_menu_page( _x( 'Business Directory Admin', 'admin menu', "WPBDM" ),
                       _x( 'Directory Admin', 'admin menu', 'WPBDM' ),
                       'administrator',
                       'wpbdp_admin',
                       array( &$this, 'main_menu' ),
                       WPBDP_URL . 'admin/resources/menuico.png' );
        add_submenu_page('wpbdp_admin',
                         _x('Add New Listing', 'admin menu', 'WPBDM'),
                         _x('Add New Listing', 'admin menu', 'WPBDM'),
                         'administrator',
                         'wpbdp_add_listing',
                         '__return_null');
        add_submenu_page('wpbdp_admin',
                         _x('Manage Options', 'admin menu', 'WPBDM'),
                         _x('Manage Options', 'admin menu', 'WPBDM'),
                         'administrator',
                         'wpbdp_admin_settings',
                         array($this, 'admin_settings'));
        add_submenu_page('wpbdp_admin',
                         _x('Manage Fees', 'admin menu', 'WPBDM'),
                         _x('Manage Fees', 'admin menu', 'WPBDM'),
                         'administrator',
                         'wpbdp_admin_fees',
                         array('WPBDP_FeesAdmin', 'admin_menu_cb'));
        add_submenu_page('wpbdp_admin',
                         _x('Manage Form Fields', 'admin menu', 'WPBDM'),
                         _x('Manage Form Fields', 'admin menu', 'WPBDM'),
                         'administrator',
                         'wpbdp_admin_formfields',
                         array('WPBDP_FormFieldsAdmin', 'admin_menu_cb'));
        add_submenu_page('wpbdp_admin',
                         _x('Listings', 'admin menu', 'WPBDM'),
                         _x('Listings', 'admin menu', 'WPBDM'),
                         'administrator',
                         'wpbdp_all_listings',
                         '__return_false');
        // if ( wpbdp_payments_api()->payments_possible() ) {
        //     add_submenu_page( 'wpbdp_admin',
        //                       _x( 'Transactions', 'admin menu', 'WPBDM' ),
        //                       _x( 'Transactions', 'admin menu', 'WPBDM' ),
        //                       'administrator',
        //                       'wpbdp_manage_transactions',
        //                       array( 'WPBDP_TransactionsAdmin', 'admin_menu_cb' )
        //                     );
        // }
        add_submenu_page( 'wpbdp_admin',
                          _x( 'CSV Import', 'admin menu', 'WPBDM' ),
                          _x( 'CSV Import', 'admin menu', 'WPBDM' ),
                          'administrator',
                          'wpbdp-csv-import',
                          array( &$this->csv_import, 'dispatch' ) );
        add_submenu_page( 'wpbdp_admin',
                          _x( 'CSV Export', 'admin menu', 'WPBDM' ),
                          _x( 'CSV Export', 'admin menu', 'WPBDM' ),
                          'administrator',
                          'wpbdp-csv-export',
                          array( &$this->csv_export, 'dispatch' ) );
        add_submenu_page( 'wpbdp_admin',
                          _x( 'Debug', 'admin menu', 'WPBDM' ),
                          _x( 'Debug', 'admin menu', 'WPBDM' ),
                          'administrator',
                          'wpbdp-debug-info',
                          array( &$this->debug_page, 'dispatch' ) );

        global $submenu;

        if (current_user_can('administrator')) {
            $submenu['wpbdp_admin'][1][2] = admin_url(sprintf('post-new.php?post_type=%s', WPBDP_POST_TYPE));
            $submenu['wpbdp_admin'][0][0] = _x('Main Menu', 'admin menu', 'WPBDM');
            $submenu['wpbdp_admin'][5][2] = admin_url( 'edit.php?post_type=' . WPBDP_POST_TYPE );
        } elseif (current_user_can('contributor')) {
            $m = $submenu['edit.php?post_type=' . WPBDP_POST_TYPE];
            $keys = array_keys($m);
            $m[$keys[1]][2] = wpbdp_get_page_link('add-listing');
        }

        do_action('wpbdp_admin_menu', 'wpbdp_admin');

        add_submenu_page('wpbdp_admin',
                         _x('Uninstall Business Directory Plugin', 'admin menu', 'WPBDM'),
                         _x('Uninstall', 'admin menu', 'WPBDM'),
                         'administrator',
                         'wpbdp_uninstall',
                         array($this, 'uninstall_plugin'));
    }

    function admin_menu_reorder( $menu_order ) {
        $admin_index = array_search( 'wpbdp_admin', $menu_order, true );
        $dir_index = array_search( 'edit.php?post_type=' . WPBDP_POST_TYPE, $menu_order, true );

        if ( false === $admin_index || false === $dir_index )
            return $menu_order;

        $min_key = min( $admin_index, $dir_index );

        $res = array();
        foreach ( $menu_order as $i => $v ) {
            if ( $i == $min_key ) {
                $res[] = $menu_order[ $dir_index ];
                $res[] = $menu_order[ $admin_index ];
                continue;
            } elseif ( $admin_index == $i || $dir_index == $i ) {
                continue;
            }

            $res[] = $v;
        }

        return $res;
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

        $renewal_id = intval( isset( $_POST['renewal_id'] ) ? $_POST['renewal_id'] : 0 );
        $expiration_time = isset( $_POST['expiration_date'] ) ? ( 'never' == $_POST['expiration_date'] ? 'never' : date( 'Y-m-d 00:00:00', strtotime( trim( $_POST['expiration_date'] ) ) ) ) : '';

        if ( ! $renewal_id || ! $expiration_time || ! current_user_can( 'administrator' ) )
            $response->send_error();

        global $wpdb;

        if ( 'never' == $expiration_time ) {
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listing_fees SET expires_on = NULL, email_sent = %d WHERE id = %d", 0, $renewal_id ) );
        } else {
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listing_fees SET expires_on = %s, email_sent = %d WHERE id = %d", $expiration_time, 0, $renewal_id ) );
        }

        $response->add( 'formattedExpirationDate', 'never' == $expiration_time ? _x( 'never', 'admin infometabox', 'WPBDM' ) : date_i18n( get_option( 'date_format' ), strtotime( $expiration_time ) ) );
        $response->send();
    }

    public function ajax_listing_remove_category() {
        $response = new WPBDP_Ajax_Response();

        $listing = WPBDP_Listing::get( intval( isset( $_POST['listing'] ) ? $_POST['listing'] : 0 ) );
        $category = intval( isset( $_POST['category'] ) ? $_POST['category'] : 0 );
        if ( ! $listing || ! $category )
            $response->send_error();

        $listing->remove_category( $category );
        $response->send();
    }

    public function ajax_listing_change_fee() {
        global $wpdb;

        $response = new WPBDP_Ajax_Response();

        if ( ! current_user_can( 'administrator' ) )
            $response->send_error();

        $fee_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d",  isset( $_POST['renewal'] ) ? $_POST['renewal'] : 0 ) );

        if ( ! $fee_info )
            $response->send_error();

        $listing = WPBDP_Listing::get( $fee_info->listing_id );
        $category = $listing->get_category_info( $fee_info->category_id );

        if ( ! $listing || ! $category || 'pending' == $category->status )
            $response->send_error();

        $response->add( 'html', wpbdp_render_page( WPBDP_PATH . 'admin/templates/listing-change-fee.tpl.php',
                                                   array( 'category' => $category,
                                                          'listing' => $listing,
                                                          'fees' => wpbdp_get_fees_for_category( $fee_info->category_id ) ) ) );
        $response->send();
    }

    function admin_notices() {
        if ( ! current_user_can( 'administrator' ) )
            return;

        $this->check_compatibility();
        $this->check_setup();
        $this->check_ajax_compat_mode();

        foreach ($this->messages as $msg) {
            if (is_array($msg)) {
                echo sprintf('<div class="%s"><p>%s</p></div>', $msg[1], $msg[0]);
            } else {
                echo sprintf('<div class="updated"><p>%s</p></div>', $msg);
            }
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
                foreach ($posts as $post_id) {
                    $listing = WPBDP_Listing::get( $post_id );
                    $listing->mark_as_paid();
                }

                $this->messages[] = _nx('The listing status has been set as paid.',
                                        'The listings status has been set as paid.',
                                        count($posts),
                                        'admin',
                                        'WPBDM');
                break;

            case 'changesticky':
                foreach ( $posts as $post_id ):
                    $upgrades_api->set_sticky( $post_id, wpbdp_getv($_GET, 'u') );
                endforeach;

                $this->messages[] = _nx('The listing has been modified.',
                                        'The listings have been modified.',
                                        count($posts),
                                        'admin',
                                        'WPBDM');

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

            case 'approvetransaction':
                $transaction = WPBDP_Payment::get( $_GET['transaction_id'] );
                $transaction->set_status( WPBDP_Payment::STATUS_COMPLETED, 'admin' );
                $transaction->save();

                $this->messages[] = _x( 'The transaction has been approved.', 'admin', 'WPBDM' );
                break;

            case 'rejecttransaction':
                $transaction = WPBDP_Payment::get( $_GET['transaction_id'] );
                $transaction->set_status( WPBDP_Payment::STATUS_REJECTED, 'admin' );
                $transaction->save();

                $this->messages[] = _x( 'The transaction has been rejected.', 'admin', 'WPBDM' );
                break;

            case 'assignfee':
                $listing = WPBDP_Listing::get( $posts[0] );
                $listing->add_category( $_GET['category_id'], $_GET['fee_id'] );
                $this->messages[] = _x('The fee was successfully assigned.', 'admin', 'WPBDM');

                break;

            case 'renewlisting':
                foreach ( $posts as $post_id ):
                    $listings_api->auto_renew( $post_id );
                endforeach;

                $this->messages[] = _nx( 'Listing was renewed.', 'Listings were renewed.', count( $posts ), 'admin', 'WPBDM' );
                break;

            case 'send-renewal-email':
                $renewal_id = intval( $_GET['renewal_id'] );

                if ( $listings_api->send_renewal_email( $renewal_id ) )
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

        if (isset($_REQUEST['resetdefaults']) && intval($_REQUEST['resetdefaults']) == 1) {
            $wpbdp->settings->reset_defaults();
            $_REQUEST['settings-updated'] = true;
            $_REQUEST['groupid'] = 'general';
            unset($_REQUEST['resetdefaults']);
        }

        $_SERVER['REQUEST_URI'] = remove_query_arg( 'deletedb', $_SERVER['REQUEST_URI'] );

        wpbdp_render_page(WPBDP_PATH . 'admin/templates/settings.tpl.php',
                          array('wpbdp_settings' => $wpbdp->settings),
                          true);
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
            wp_clear_scheduled_hook('wpbdp_listings_expiration_check');

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
                                         '<span class="module-version">' . ( null === $module_info['version'] ? _x( 'N/A', 'admin compat', 'WPBDM' ) : $module_info['version'] ) . '</span>',
                                         '<span class="module-required">' . $module_info['required'] . '</span>' );*/
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

        if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] == 'wpbdp_admin_settings' ) {
            if ( wpbdp_get_option( 'require-login' ) && !get_option( 'users_can_register' ) ) {
                $this->messages[] = array(
                    str_replace( array( '[', ']' ), array( '<a href="' . admin_url( 'options-general.php' )  . '">', '</a>' ), _x( 'We noticed you want your Business Directory users to register before posting listings, but Registration for your site is currently disabled. Go [here] and check "Anyone can register" to make sure BD works properly.', 'admin', 'WPBDM' ) ),
                    'error' );
            }
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

function wpbdp_admin_message( $msg, $kind = '' ) {
    global $wpbdp;
    $wpbdp->admin->messages[] = $kind ? array( $msg, $kind ) : $msg;
}

}
