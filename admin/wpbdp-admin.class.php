<?php
require_once(WPBDP_PATH . 'admin/admin-pages.php');
require_once(WPBDP_PATH . 'admin/fees.php');
require_once(WPBDP_PATH . 'admin/form-fields.php');
require_once( WPBDP_PATH . 'admin/transactions.php' );
require_once(WPBDP_PATH . 'admin/csv-import.php');
require_once( WPBDP_PATH . 'admin/csv-export.php' );

if (!class_exists('WPBDP_Admin')) {

class WPBDP_Admin {

    public $messages = array();

    function __construct() {
        add_action('admin_init', array($this, '_handle_downloads'));
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'add_metaboxes'));
        add_action('admin_init', array($this, 'check_for_required_fields'));
        add_action('admin_init', array($this, 'check_for_required_pages'));
        add_action('admin_init', array($this, 'check_payments_possible'));
        add_action('before_delete_post', array($this, '_delete_post_metadata'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        add_filter('wp_dropdown_users', array($this, '_dropdown_users'));

        add_filter(sprintf('manage_edit-%s_columns', WPBDP_POST_TYPE), array( $this, 'add_custom_columns'));
        add_filter('post_row_actions', array($this, '_row_actions'), 10, 2);
        
        add_filter( 'manage_edit-' . WPBDP_CATEGORY_TAX . '_columns', array( &$this, 'add_custom_taxonomy_columns' ) );
        add_filter( 'manage_edit-' . WPBDP_TAGS_TAX . '_columns', array( &$this, 'tag_taxonomy_columns' ) );
        add_action( 'manage_' . WPBDP_CATEGORY_TAX . '_custom_column', array( &$this, 'custom_taxonomy_columns' ), 10, 3 );

        add_action(sprintf('manage_posts_custom_column'), array($this, 'custom_columns'));
        add_filter('views_edit-' . WPBDP_POST_TYPE, array($this, 'add_custom_views'));
        add_filter('request', array($this, 'apply_query_filters'));

        add_action('save_post', array($this, '_save_post'));

        add_filter('wp_terms_checklist_args', array($this, '_checklist_args')); // fix issue #152

        add_action('wp_ajax_wpbdp-uploadimage', array($this, '_upload_image'));
        add_action('wp_ajax_wpbdp-deleteimage', array($this, '_delete_image'));
        add_action('wp_ajax_wpbdp-listingimages', array($this, '_listing_images'));

        add_action( 'wp_ajax_wpbdp-renderfieldsettings', array( 'WPBDP_FormFieldsAdmin', '_render_field_settings' ) );

        add_action( 'wp_ajax_wpbdp-set_site_tracking', 'WPBDP_SiteTracking::handle_ajax_response' );

        add_action('admin_footer', array($this, '_add_bulk_actions'));
        add_action('admin_footer', array($this, '_fix_new_links'));
        
        // CSV export page.
        $this->csv_export = new WPBDP_Admin_CSVExport();
    }

    function enqueue_scripts() {
        global $pagenow;

        wp_enqueue_style('wpbdp-admin', WPBDP_URL . 'admin/resources/admin.css');
        wp_enqueue_style('thickbox');

        wp_enqueue_script('wpbdp-frontend-js', WPBDP_URL . 'resources/js/wpbdp.js', array('jquery'));
        wp_enqueue_script('wpbdp-admin-js', WPBDP_URL . 'admin/resources/admin.js', array('jquery', 'thickbox'));

        if ( 'post.php' == $pagenow ) {
            wp_enqueue_style( 'wpbdp-jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/redmond/jquery-ui.css' );
            wp_enqueue_script( 'jquery-ui-datepicker' );
        }

        // Ask for site tracking if needed.
        if ( !wpbdp_get_option( 'tracking-on', false ) && !get_option( 'wpbdp-tracking-dismissed', false ) && current_user_can( 'administrator' ) ) {
            wp_enqueue_style( 'wp-pointer' );
            wp_enqueue_script( 'wp-pointer' );
            add_action( 'admin_print_footer_scripts', 'WPBDP_SiteTracking::request_js' );
        }
    }

    function admin_menu() {
        add_menu_page(_x("Business Directory Admin", 'admin menu', "WPBDM"),
                      _x('Directory Admin', 'admin menu', 'WPBDM'),
                      'activate_plugins',
                      'wpbdp_admin',
                      'wpbusdirman_home_screen',
                      WPBDP_URL . 'resources/images/menuico.png');
        add_submenu_page('wpbdp_admin',
                         _x('Add New Listing', 'admin menu', 'WPBDM'),
                         _x('Add New Listing', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp_add_listing',
                         '__return_null');
        add_submenu_page('wpbdp_admin',
                         _x('Manage Options', 'admin menu', 'WPBDM'),
                         _x('Manage Options', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp_admin_settings',
                         array($this, 'admin_settings'));
        add_submenu_page('wpbdp_admin',
                         _x('Manage Fees', 'admin menu', 'WPBDM'),
                         _x('Manage Fees', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp_admin_fees',
                         array('WPBDP_FeesAdmin', 'admin_menu_cb'));
        add_submenu_page('wpbdp_admin',
                         _x('Manage Form Fields', 'admin menu', 'WPBDM'),
                         _x('Manage Form Fields', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp_admin_formfields',
                         array('WPBDP_FormFieldsAdmin', 'admin_menu_cb'));
        add_submenu_page('wpbdp_admin',
                         _x('All Listings', 'admin menu', 'WPBDM'),
                         _x('All Listings', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp_all_listings',
                         '__return_false');        
        add_submenu_page('wpbdp_admin',
                         _x('Pending Upgrade', 'admin menu', 'WPBDM'),
                         _x('Pending Upgrade', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp_manage_featured',
                         '__return_false');
        add_submenu_page('wpbdp_admin',
                         _x('Pending Payment', 'admin menu', 'WPBDM'),
                         _x('Pending Payment', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp_manage_payments',
                         '__return_false');

        if ( wpbdp_payments_api()->payments_possible() ) {
            add_submenu_page( 'wpbdp_admin',
                              _x( 'Transactions', 'admin menu', 'WPBDM' ),
                              _x( 'Transactions', 'admin menu', 'WPBDM' ),
                              'activate_plugins',
                              'wpbdp_manage_transactions',
                              array( 'WPBDP_TransactionsAdmin', 'admin_menu_cb' )
                            );
        }
        add_submenu_page('wpbdp_admin',
                         _x('CSV Import', 'admin menu', 'WPBDM'),
                         _x('CSV Import', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp-csv-import',
                         array('WPBDP_CSVImportAdmin', 'admin_menu_cb'));
        add_submenu_page( 'wpbdp_admin',
                          _x( 'CSV Export', 'admin menu', 'WPBDM' ),
                          _x( 'CSV Export', 'admin menu', 'WPBDM' ),
                          'activate_plugins',
                          'wpbdp-csv-export',
                          array( &$this->csv_export, 'dispatch' ) );
        add_submenu_page( 'wpbdp_admin',
                          _x( 'Debug', 'admin menu', 'WPBDM' ),
                          _x( 'Debug', 'admin menu', 'WPBDM' ),
                          'activate_plugins',
                          'wpbdp-debug-info',
                          array( $this, '_debug_info_page' ) );        

        // XXX: just a little hack
        global $submenu;
        
        if (current_user_can('administrator')) {
            $submenu['wpbdp_admin'][1][2] = admin_url(sprintf('post-new.php?post_type=%s', WPBDP_POST_TYPE));
            $submenu['wpbdp_admin'][0][0] = _x('Main Menu', 'admin menu', 'WPBDM');
            $submenu['wpbdp_admin'][5][2] = admin_url( 'edit.php?post_type=' . WPBDP_POST_TYPE );
            $submenu['wpbdp_admin'][6][2] = admin_url(sprintf('edit.php?post_type=%s&wpbdmfilter=%s', WPBDP_POST_TYPE, 'pendingupgrade'));
            $submenu['wpbdp_admin'][7][2] = admin_url(sprintf('edit.php?post_type=%s&wpbdmfilter=%s', WPBDP_POST_TYPE, 'unpaid'));
        } elseif (current_user_can('contributor')) {
            $m = $submenu['edit.php?post_type=' . WPBDP_POST_TYPE];
            $keys = array_keys($m);
            $m[$keys[1]][2] = wpbdp_get_page_link('add-listing');
        }

        do_action('wpbdp_admin_menu', 'wpbdp_admin');

        add_submenu_page('wpbdp_admin',
                         _x('Uninstall WPDB Manager', 'admin menu', 'WPBDM'),
                         _x('Uninstall', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp_uninstall',
                         array($this, 'uninstall_plugin'));        
    }

    public function _delete_post_metadata($post_id) {
        global $wpdb;

        if ( current_user_can('delete_posts') && get_post_type($post_id) == WPBDP_POST_TYPE ) {
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d", $post_id));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d", $post_id));

            $attachments = get_posts(array(
                'numberposts' => -1,
                'post_type' => 'attachment',
                'post_parent' => $post_id
            ));

            foreach ($attachments as $attachment) {
                wp_delete_attachment($attachment->ID, true);
            }
        }
    }

    function add_metaboxes() {
        add_meta_box('BusinessDirectory_listinginfo',
                     __('Listing Information', 'WPBDM'),
                     array($this, 'listing_metabox'),
                     WPBDP_POST_TYPE,
                     'side',
                     'core'
                    );

        add_meta_box('wpbdp-listing-fields',
                    _x('Listing Fields / Images', 'admin', 'WPBDM'),
                    array($this, '_listing_fields_metabox'),
                    WPBDP_POST_TYPE,
                    'normal',
                    'core');
    }

    public function _listing_fields_metabox($post) {
        $formfields_api = wpbdp_formfields_api();

        $post_values = wpbdp_getv( $_POST, 'listingfields', array() );

        echo wp_nonce_field( plugin_basename( __FILE__ ), 'wpbdp-listing-fields-nonce' );

        echo '<div style="border-bottom: solid 1px #dedede; padding-bottom: 10px;">';
        echo sprintf( '<strong>%s</strong>', _x( 'Listing Fields', 'admin', 'WPBDM' ) );
        echo '<div style="padding-left: 10px;">';
        foreach ($formfields_api->find_fields( array( 'association' => 'meta' ) ) as $field ) {
            $value = isset( $post_values[ $field->get_id() ] ) ? $field->convert_input( $post_values[ $field->get_id() ] ) : $field->value( $post->ID );
            echo $field->render( $value, 'admin-submit' );
        }
        echo '</div>';
        echo '</div>';
        echo '<div class="clear"></div>';      

        // listing images
        if ( current_user_can('edit_posts') ) {
            echo sprintf('<div id="wpbdp-listing-images" class="wpbdp-ajax-placeholder"
                               data-action="wpbdp-listingimages"
                               data-post_id="%s"
                               data-baseurl="%s"></div>',
                        $post->ID,
                        remove_query_arg(array('message', 'wpbdmaction')));
        }
    }

    public function _checklist_args($args) {
        $args['checked_ontop'] = false;
        return $args;
    }

    /*
     * Listing image handling
     */

    public function _listing_images() {
        $post_id = intval($_POST['post_id']);

        if (wpbdp_get_option('allow-images')) {
            $listings_api = wpbdp_listings_api();
            $thumbnail_id = $listings_api->get_thumbnail_id($post_id);
            $images = $listings_api->get_images($post_id);

            echo '<div style="margin-top: 10px;">';
            echo sprintf('<strong>%s</strong>', _x('Listing Images', 'admin', 'WPBDM'));
            echo '<div class="listing-images" style="padding-left: 10px;">';

            foreach ($images as $image) {
                echo '<div class="image">';
                echo sprintf('<img src="%s" /><br />', wp_get_attachment_thumb_url($image->ID));
                echo sprintf('<label><input type="radio" name="thumbnail_id" value="%d" %s/> %s</label><br /><br />',
                             $image->ID,
                             $thumbnail_id == $image->ID ? 'checked="checked"' : '',
                             _x('Listing thumbnail', 'admin', 'WPBDM'));
                echo sprintf('<a href="%s" class="button delete-image-button">%s</a>',
                            add_query_arg(array('action' => 'wpbdp-deleteimage',
                                                'image_id' => $image->ID),
                                          admin_url('admin-ajax.php')),
                            _x('Delete Image', 'admin', 'WPBDM'));
                echo '</div>';
            }

            echo '</div>';

            echo '<p style="clear: both; margin-top: 10px;">';
            echo sprintf('<a id="upload-listing-image" href="%s" class="thickbox button-primary" title="%s">%s</a>',
                         add_query_arg(array('action' => 'wpbdp-uploadimage',
                                             'post_id' => $post_id,
                                             'width' => '600',
                                             'TB_iframe' => 1),
                                        admin_url('admin-ajax.php')),
                         _x('Upload Image', 'admin', 'WPBDM'),
                         _x('Upload Image', 'admin', 'WPBDM'));
            echo '</p>';

            echo '</div>';
        }

        exit;
    }

    public function _upload_image() {
        echo '<script type="text/javascript">';
        echo 'parent.jQuery("#TB_window, #TB_iframeContent").width(350).height(150)';
        echo '</script>';

        if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] == 0) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');            

            $wp_image_ = wp_handle_upload($_FILES['image_upload'], array('test_form' => FALSE));

            if (!isset($wp_image_['error'])) {
                if ($attachment_id = wp_insert_attachment(array(
                                                'post_mime_type' => $wp_image_['type'],
                                                'post_title' => preg_replace('/\.[^.]+$/', '', basename($wp_image_['file'])),
                                                'post_content' => '',
                                                'post_status' => 'inherit',
                                                'post_parent' => $_REQUEST['post_id']
                                                ), $wp_image_['file'])) {

                    $attach_data = wp_generate_attachment_metadata($attachment_id, $wp_image_['file']);
                    wp_update_attachment_metadata($attachment_id, $attach_data);

                    if (!wp_attachment_is_image($attachment_id)) {
                        wp_delete_attachment($attachment_id, true);
                    }
                }
            }

            echo '<script type="text/javascript">';
            echo 'parent.jQuery("#TB_closeWindowButton").click();';
            echo 'parent.wpbdp_load_placeholder(parent.jQuery("#wpbdp-listing-images"))';
            echo '</script>';
            exit;
        }

        echo '<div class="wrap">';
        echo '<form action="" method="POST" enctype="multipart/form-data">';
        echo '<strong>' . _x('Upload Image', 'admin', 'WPBDM') . '</strong><br />';
        echo '<input type="file" name="image_upload" />';
        echo sprintf('<input type="submit" value="%s" class="button" />', _x('Upload', 'admin', 'WPBDM'));
        echo '</form>';
        echo '</div>';
        exit;
    }

    public function _delete_image() {
        wp_delete_attachment($_GET['image_id'], true);
        delete_post_meta($post_id, '_wpbdp[thumbnail_id]', $_GET['image_id']);
        exit;
    }

    public function _save_post($post_id) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
            return;

        if (is_admin() && isset($_POST['post_type']) && $_POST['post_type'] == WPBDP_POST_TYPE) {
            // Fix listings added through admin site
            wpbdp_listings_api()->set_default_listing_settings( $post_id );

            // Save custom fields
            if (isset($_POST['wpbdp-listing-fields-nonce']) && wp_verify_nonce( $_POST['wpbdp-listing-fields-nonce'], plugin_basename( __FILE__ ) ) ) {
                // save custom fields
                $formfields_api = wpbdp_formfields_api();
                $listingfields = wpbdp_getv($_POST, 'listingfields', array());
                
                foreach ( $formfields_api->find_fields( array('association' => 'meta' ) ) as $field ) {
                    if ( isset( $listingfields[ $field->get_id() ] ) ) {
                        $value = $field->convert_input( $listingfields[ $field->get_id() ] );
                        $field->store_value( $post_id, $value );
                    } else {
                        $field->store_value( $post_id, $field->convert_input( null ) );
                    }
                }

                if ( isset( $_POST['thumbnail_id'] ) )
                    update_post_meta( $post_id, '_wpbdp[thumbnail_id]', $_POST['thumbnail_id'] );
            }
        }
    }

    public function listing_metabox($post) {
        $listings_api = wpbdp_listings_api();
        $upgrades_api = wpbdp_listing_upgrades_api();

        // Payment status
        $payment_status = $listings_api->get_payment_status($post->ID);

        // Determine selected tab.
        $selected_tab = 'generalinfo';
        if ( isset( $_GET['wpbdmaction'] ) && in_array( $_GET['wpbdmaction'], array( 'removecategory', 'assignfee', 'change_expiration' ), true ) )
            $selected_tab = 'fees';

        // Some general info.
        $expired_categories_ids = $listings_api->get_expired_categories( $post->ID );
        $current_categories = wp_get_post_terms( $post->ID, WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) );
        $post_categories = array_unique( array_merge( $current_categories, $expired_categories_ids ) );
        $categories = get_terms( WPBDP_CATEGORY_TAX, array( 'hide_empty' => false, 'hierarchical' => false, 'include' => $post_categories ? $post_categories : array( -1 ) ) );        

        echo '<div class="misc-pub-section">';

        echo '<ul class="listing-metabox-tabs">';
        echo '<li class="tabs ' . ( $selected_tab == 'generalinfo' ? 'selected' : '' ) . '"><a href="#listing-metabox-generalinfo">' . _x('General', 'admin', 'WPBDM') . '</a></li>';
        echo '<li class="tabs ' . ( $selected_tab == 'fees' ? 'selected' : '' ) . '"><a href="#listing-metabox-fees">' . _x('Fee Details', 'admin', 'WPBDM') . '</a></li>';
        echo '<li class="tabs ' . ( $selected_tab == 'transactions' ? 'selected' : '' ) . '"><a href="#listing-metabox-transactions">' . _x('Transactions', 'admin', 'WPBDM') . '</a></li>';
        echo '</ul>';

        echo '<div id="listing-metabox-generalinfo">';
        echo '<strong>' . _x('General Info', 'admin infometabox', 'WPBDM') . '</strong>';        
        echo '<dl>';
            echo '<dt>'. _x('Total Listing Cost', 'admin infometabox', 'WPBDM') . '</dt>';
            echo '<dd>' . wpbdp_get_option('currency-symbol') .$listings_api->cost_of_listing($post->ID) . '</dd>';
            echo '<dt>'. _x('Payment Status', 'admin infometabox', 'WPBDM') . '</dt>';
            echo '<dd>';
                echo sprintf('<span class="tag paymentstatus %1$s">%1$s</span>', $payment_status);
            echo '</dd>';
            echo '<dt>' . _x('Featured (Sticky) Status', 'admin infometabox', 'WPBDM') . '</dt>';
            echo '<dd>';

                // sticky information
                $sticky_info = $upgrades_api->get_info( $post->ID );

                echo '<span><b>';
                if ($sticky_info->pending) {
                    echo _x('Pending Upgrade', 'admin metabox', 'WPBDM');
                } else {
                    echo esc_attr( $sticky_info->level->name );
                }
                echo '</b> </span><br />';

                if (current_user_can('administrator')) {
                    if ( $sticky_info->upgradeable ) {
                        echo sprintf('<span><a href="%s">%s</a></span>',
                                     add_query_arg(array('wpbdmaction' => 'changesticky', 'u' => $sticky_info->upgrade->id, 'post' => $post->ID)),
                                     '<b>↑</b> ' . sprintf(__('Upgrade to %s', 'WPBDM'), esc_attr($sticky_info->upgrade->name)) );
                    }

                    if ( $sticky_info->downgradeable ) {
                        echo '<br />';
                        echo sprintf('<span><a href="%s">%s</a></span>',
                                     add_query_arg(array('wpbdmaction' => 'changesticky', 'u' => $sticky_info->downgrade->id, 'post' => $post->ID)),
                                     '<b>↓</b> ' . sprintf(__('Downgrade to %s', 'WPBDM'), esc_attr($sticky_info->downgrade->name)) );                
                    }
                }

            echo '</dd>';
        echo '</dl>';

        if (current_user_can('administrator')) {
            if ($payment_status != 'paid')
                echo sprintf('<a href="%s" class="button-primary">%s</a> ',
                         add_query_arg('wpbdmaction', 'setaspaid'),
                         _x('Mark listing as Paid', 'admin infometabox', 'WPBDM'));
            else
                echo sprintf('<a href="%s" class="button">%s</a>',
                             add_query_arg('wpbdmaction', 'setasnotpaid'),
                             _x('Mark listing as Not paid', 'admin infometabox', 'WPBDM'));

            echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/infometabox-general-feesummary.tpl.php', array(
                'post_categories' => $categories,
                'expired_categories' => $expired_categories_ids,
                'post_id' => $post->ID,                           
            ) );

        }

        echo '</div>';

        // Fees
        echo wpbdp_render_page(WPBDP_PATH . 'admin/templates/infometabox-fees.tpl.php', array(
                                'post_categories' => $categories,
                                'expired_categories' => $expired_categories_ids,
                                'post_id' => $post->ID,
                                'image_count' => count($listings_api->get_images($post->ID))
                                ));

        // Transactions
        echo wpbdp_render_page(WPBDP_PATH . 'admin/templates/infometabox-transactions.tpl.php', array(
                                'transactions' => wpbdp_payments_api()->get_transactions($post->ID)
                               ));

        echo '</div>';

        echo '<div class="clear"></div>';

    }

    function apply_query_filters($request) {
        global $current_screen;
        global $wpdb;

        if (is_admin() && isset($_REQUEST['wpbdmfilter']) && $current_screen->id == 'edit-' . WPBDP_POST_TYPE) {
            switch ($_REQUEST['wpbdmfilter']) {
                case 'pendingupgrade':
                    $request['meta_key'] = '_wpbdp[sticky]';
                    $request['meta_value'] = 'pending';
                    break;
                case 'paid':
                    $request['meta_key'] = '_wpbdp[payment_status]';
                    $request['meta_value'] = 'paid';
                    break;
                case 'expired':
                    $expired_post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT listing_id FROM {$wpdb->prefix}wpbdp_listing_fees WHERE expires_on < %s", current_time( 'mysql' ) ) );
                    $expired_post_ids = $expired_post_ids ? $expired_post_ids : array( 0 );
                    $request['post__in'] = $expired_post_ids;
                    break;
                default:
                    $request['meta_key'] = '_wpbdp[payment_status]';
                    $request['meta_value'] = 'paid';
                    $request['meta_compare'] = '!=';
                    break;
            }

        }

        return $request;
    }

    function admin_notices() {
        $this->check_setup();

        foreach ($this->messages as $msg) {
            if (is_array($msg)) {
                echo sprintf('<div class="%s"><p>%s</p></div>', $msg[1], $msg[0]);
            } else {
                echo sprintf('<div class="updated"><p>%s</p></div>', $msg);
            }
        }

        $this->messages = array();
    }

    public function _add_bulk_actions() {
        if (!current_user_can('administrator'))
            return;
        
        if ($screen = get_current_screen()) {
            if ($screen->id == 'edit-' . WPBDP_POST_TYPE) {
                if (isset($_GET['post_type']) && $_GET['post_type'] == WPBDP_POST_TYPE) {
                    $bulk_actions = array('sep0' => '--',
                                          'publish' => _x('Publish Listing', 'admin actions', 'WPBDM'),
                                          'sep1' => '--',
                                          'upgradefeatured' => _x('Upgrade to Featured', 'admin actions', 'WPBDM'),
                                          'cancelfeatured' => _x('Downgrade to Normal', 'admin actions', 'WPBDM'),
                                          'sep2' => '--',
                                          'setaspaid' => _x('Set Paid', 'admin actions', 'WPBDM'),
                                          'setasnotpaid' => _x('Set Not Paid', 'admin actions', 'WPBDM'),
                                          'sep3' => '--',
                                          'renewlisting' => _x( 'Renew Listing', 'admin actions', 'WPBDM' )
                                         );


                    // the 'bulk_actions' filter doesn't really work for this until this bug is fixed: http://core.trac.wordpress.org/ticket/16031
                    echo '<script type="text/javascript">';

                    foreach ($bulk_actions as $action => $text) {
                        echo sprintf('jQuery(\'select[name="%s"]\').append(\'<option value="%s" data-uri="%s">%s</option>\');',
                                    'action', 'listing-' . $action, add_query_arg('wpbdmaction', $action), $text);
                        echo sprintf('jQuery(\'select[name="%s"]\').append(\'<option value="%s" data-uri="%s">%s</option>\');',
                                    'action2', 'listing-' . $action, '', $text);          
                    }

                    echo '</script>';
                }
            }
        }
    }

    public function _fix_new_links() {
        // 'contributors' should still use the frontend to add listings (editors, authors and admins are allowed to add things directly)
        // XXX: this is kind of hacky but is the best we can do atm, there aren't hooks to change add links
        if (current_user_can('contributor') && isset($_GET['post_type']) && $_GET['post_type'] == WPBDP_POST_TYPE) {
            echo '<script type="text/javascript">';
            echo sprintf('jQuery(\'a.add-new-h2\').attr(\'href\', \'%s\');', wpbdp_get_page_link('add-listing'));
            echo '</script>';
        }
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
                    $listings_api->set_payment_status($post_id, 'paid');
                }

                $this->messages[] = _nx('The listing status has been set as paid.',
                                        'The listings status has been set as paid.',
                                        count($posts),
                                        'admin',
                                        'WPBDM');
                break;
            
            case 'setasnotpaid':
                foreach ($posts as $post_id) {
                    $listings_api->set_payment_status($post_id, 'not-paid');
                }

                $this->messages[] = _nx('The listing status has been set as "not paid".',
                                        'The listings status has been set as "not paid".',
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
                $trans = wpbdp_payments_api()->get_transaction($_GET['transaction_id']);
                $trans->processed_on = current_time('mysql');
                $trans->processed_by = 'admin';
                $trans->status = 'approved';
                wpbdp_payments_api()->save_transaction($trans);

                $this->messages[] = _x('The transaction has been approved.', 'admin', 'WPBDM');
                break;

            case 'rejecttransaction':
                $trans = wpbdp_payments_api()->get_transaction($_GET['transaction_id']);
                $trans->processed_on = current_time('mysql');
                $trans->processed_by = 'admin';
                $trans->status = 'rejected';
                wpbdp_payments_api()->save_transaction($trans);

                $this->messages[] = _x('The transaction has been rejected.', 'admin', 'WPBDM');
                break;

            case 'change_expiration':
                global $wpdb;

                $expiration_time = isset( $_GET['expiration_date'] ) ? date( 'Y-m-d 00:00:00', strtotime( trim( $_GET['expiration_date'] ) ) ) : null;

                if ( $expiration_time ) {
                    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listing_fees SET expires_on = %s, email_sent = %d WHERE id = %d", $expiration_time, 0, intval( $_GET['listing_fee_id'] ) ) );
                }
                
                $this->messages[] = _x( 'The expiration date has been changed.', 'admin', 'WPBDM' );

                break;

            case 'assignfee':
                if ($listings_api->assign_fee($posts[0], $_GET['category_id'], $_GET['fee_id']))
                    $this->messages[] = _x('The fee was successfully assigned.', 'admin', 'WPBDM');
                break;

            case 'removecategory':
                if ( $listings_api->remove_category_info( $posts[0], $_GET['category_id'] ) )
                    $this->messages[] = _x( 'Category information was updated.', 'admin', 'WPBDM' );
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

    function add_custom_views($views) {
        global $wpdb;

        if (current_user_can('administrator')) {
            $post_statuses = '\'' . join('\',\'', isset($_GET['post_status']) ? array($_GET['post_status']) : array('publish', 'draft', 'pending')) . '\'';

            $paid_query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                               WHERE p.post_type = %s AND p.post_status IN ({$post_statuses}) AND ( (pm.meta_key = %s AND pm.meta_value = %s) )",
                                                               WPBDP_POST_TYPE,
                                                               '_wpbdp[payment_status]',
                                                               'paid');

            $paid = $wpdb->get_var( $paid_query);

            $unpaid = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                               WHERE p.post_type = %s AND p.post_status IN ({$post_statuses}) AND ( (pm.meta_key = %s AND NOT pm.meta_value = %s) ) GROUP BY p.ID",
                                                               WPBDP_POST_TYPE,
                                                               '_wpbdp[payment_status]',
                                                               'paid') );
            $pending_upgrade = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                               WHERE p.post_type = %s AND p.post_status IN ({$post_statuses}) AND ( (pm.meta_key = %s AND pm.meta_value = %s) )",
                                                               WPBDP_POST_TYPE,
                                                               '_wpbdp[sticky]',
                                                               'pending') );
            $expired = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT COUNT(p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->prefix}wpbdp_listing_fees lf ON lf.listing_id = p.ID WHERE lf.expires_on < %s",
                                                       current_time( 'mysql' ) ) );

            $views['paid'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                     add_query_arg('wpbdmfilter', 'paid', remove_query_arg('post')),
                                     wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'paid' ? 'current' : '',
                                     __('Paid', 'WPBDM'),
                                     number_format_i18n($paid));
            $views['unpaid'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                       add_query_arg('wpbdmfilter', 'unpaid', remove_query_arg('post')),
                                       wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'unpaid' ? 'current' : '',
                                       __('Unpaid', 'WPBDM'),
                                       number_format_i18n($unpaid));
            $views['featured'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                       add_query_arg('wpbdmfilter', 'pendingupgrade', remove_query_arg('post')),
                                       wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'pendingupgrade' ? 'current' : '',
                                       __('Pending Upgrade', 'WPBDM'),
                                       number_format_i18n($pending_upgrade));
            $views['expired'] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                         add_query_arg( 'wpbdmfilter', 'expired', remove_query_arg( 'post' ) ),
                                         wpbdp_getv( $_REQUEST, 'wpbdmfilter' ) == 'expired' ? 'current' : '' ,
                                         _x( 'Expired', 'admin', 'WPBDM' ),
                                         number_format_i18n( $expired )
                                        );
        } elseif (current_user_can('contributor')) {
            if (isset($views['mine']))
                return array($views['mine']);
            else
                return array();
        }

        return $views;

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

    function add_custom_columns($columns_) {
        $columns = array();

        foreach (array_keys($columns_) as $key) {
            $columns[$key] = $columns_[$key];

            if ($key == 'title') {
                // add custom columns *after* the title column
                $columns['bd_category'] = _x('Categories', 'admin', 'WPBDM');
                $columns['bd_payment_status'] = __('Payment Status', 'WPBDM');
                $columns['bd_sticky_status'] = __('Featured (Sticky) Status', 'WPBDM');
            }
        }

        return $columns;
    }

    public function _row_actions($actions, $post) {
        if ($post->post_type == WPBDP_POST_TYPE && current_user_can('contributor')) {
            if (wpbdp_user_can('edit', $post->ID))
                $actions['edit'] = sprintf('<a href="%s">%s</a>',
                                            wpbdp_get_page_link('editlisting', $post->ID),
                                            _x('Edit Listing', 'admin actions', 'WPBDM'));

            if (wpbdp_user_can('delete', $listing_id))
                $actions['delete'] = sprintf('<a href="%s">%s</a>', wpbdp_get_page_link('deletelisting', $listing_id), _x('Delete Listing', 'admin actions', 'WPBDM'));
        }

        return $actions;
    }

    function custom_columns($column) {
        switch ($column) {
            case 'bd_category':
                $this->category_column();
                break;

            case 'bd_payment_status':
                $this->payment_status_column();
                break;

            case 'bd_sticky_status':
                $this->sticky_status_column();
                break;

            default:
                break;
        }
    }

    private function category_column() {
        global $wpdb;
        global $post;

        $expired_categories = wpbdp_listings_api()->get_expired_categories( $post->ID );
        $current_categories = wp_get_post_terms( $post->ID, WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) );
        $categories = array_unique( array_merge( $current_categories, $expired_categories ) );

        foreach ( $categories as $i => $category_id ) {
            if ( $term = get_term( $category_id, WPBDP_CATEGORY_TAX, OBJECT, 'display' ) ) {
                $expired = in_array( $category_id, $expired_categories, true );

                print $expired ? '<s>' : '';
                printf( '<a href="%s" title="%s">%s</a>',
                        get_term_link( $term ),
                        $expired ? _x( '(Listing expired in this category)', 'admin', 'WPBDM' ) : '',
                        $term->name );
                print $expired ? '</s>' : '';
                print ( ( $i + 1 ) != count( $categories ) ? ', ' : '' );                
            }
        }

    }

    private function payment_status_column() {
        global $post;

        $listings_api = wpbdp_listings_api();

        $paid_status = $listings_api->get_payment_status($post->ID);
        $status_links = '';

        if ($paid_status != 'paid')
            $status_links .= sprintf('<span><a href="%s">%s</a></span>',
                                    add_query_arg(array('wpbdmaction' => 'setaspaid', 'post' => $post->ID)),
                                    __('Paid', 'WPBDM'));
        else
            $status_links .= sprintf('<span><a href="%s">%s</a></span>',
                                  add_query_arg(array('wpbdmaction' => 'setasnotpaid', 'post' => $post->ID)),
                                  __('Not paid', 'WPBDM'));

        echo sprintf('<span class="status %s">%s</span>', $paid_status, strtoupper($paid_status));

        if (current_user_can('administrator')) {
            echo sprintf('<div class="row-actions"><b>%s:</b> %s</div>', __('Set as', 'WPBDM'), $status_links);
        }
    }

    private function sticky_status_column() {
        global $post;

        $upgrades_api = wpbdp_listing_upgrades_api();
        $sticky_info = $upgrades_api->get_info( $post->ID );

        echo sprintf('<span class="status %s">%s</span><br />',
                    str_replace(' ', '', $sticky_info->status),
                    $sticky_info->pending ? __('Pending Upgrade', 'WPBDM') : esc_attr($sticky_info->level->name) );

        echo '<div class="row-actions">';

        if ( current_user_can('administrator') ) {
            if ( $sticky_info->upgradeable ) {
                echo sprintf('<span><a href="%s">%s</a></span>',
                             add_query_arg(array('wpbdmaction' => 'changesticky', 'u' => $sticky_info->upgrade->id, 'post' => $post->ID)),
                             '<b>↑</b> ' . sprintf(__('Upgrade to %s', 'WPBDM'), esc_attr($sticky_info->upgrade->name)) );
                echo '<br />';
            }

            if ( $sticky_info->downgradeable ) {
                echo sprintf('<span><a href="%s">%s</a></span>',
                             add_query_arg(array('wpbdmaction' => 'changesticky', 'u' => $sticky_info->downgrade->id, 'post' => $post->ID)),
                             '<b>↓</b> ' . sprintf(__('Downgrade to %s', 'WPBDM'), esc_attr($sticky_info->downgrade->name)) );                
            }
        } elseif ( current_user_can('contributor') && wpbdp_user_can( 'upgrade-to-sticky', $post->ID ) ) {
                echo sprintf('<span><a href="%s"><b>↑</b> %s</a></span>', wpbdp_get_page_link('upgradetostickylisting', $post->ID), _x('Upgrade to Featured', 'admin actions', 'WPBDM'));            
        }

        echo '</div>';

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

        if (isset($_POST['doit']) && $_POST['doit'] == 1) {
            $post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = %s", WPBDP_POST_TYPE ) );

            foreach ($post_ids as $post_id) {
                wp_delete_post($post_id, true);
            }

            $tables = array( 'wpbdp_form_fields', 'wpbdp_fees', 'wpbdp_payments', 'wpbdp_listing_fees' );
            foreach ( $tables as &$table ) {
                $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
            }

            delete_option( 'wpbdp-db-version' );
            delete_option( 'wpbusdirman_db_version' );

            // clear scheduled hooks
            wp_clear_scheduled_hook('wpbdp_listings_expiration_check');

            // deactivate plugin
            $real_path = WPBDP_PATH . 'wpbusdirman.php';
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

    /* Debug info page. */
    public function _handle_downloads() {
        global $pagenow;

        if ( !current_user_can( 'administrator' ) || $pagenow != 'admin.php' || !isset( $_GET['page'] ) )
            return;

        switch ( $_GET['page'] ) {
            case 'wpbdp-debug-info':
                if ( isset( $_GET['download'] ) && $_GET['download'] == 1 ) {
                    header( 'Content-Description: File Transfer' );
                    header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ), true );
                    header( 'Content-Disposition: attachment; filename=' . 'wpbdp-debug-info.txt' );
                    header( 'Pragma: no-cache' );
                    $this->_debug_info_page( true );
                    exit;
                }

                break;

            // case 'wpbdp-csv-export':
            //     if ( isset( $_POST['action'] ) && $_POST['action'] == 'do-export' ) {
            //         WPBDP_Admin_CSVExport::download();
            //     }
            // 
            //     break;

            default:
                break;
        }

    }

    public function _debug_info_page( $plain=false ) {
        global $wpdb;

        $debug_info = array();

        // basic BD setup info & tests
        $debug_info['basic']['_title'] = _x( 'BD Info', 'debug-info', 'WPBDM' );
        $debug_info['basic']['BD version'] = WPBDP_VERSION;
        $debug_info['basic']['BD database revision (current)'] = WPBDP_Installer::DB_VERSION;
        $debug_info['basic']['BD database revision (installed)'] = get_option( 'wpbdp-db-version' );

        $tables = apply_filters( 'wpbdp_debug_info_tables_check', array( 'wpbdp_form_fields', 'wpbdp_fees', 'wpbdp_payments', 'wpbdp_listing_fees' ) );
        $missing_tables = array();
        foreach ( $tables as &$t ) {
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . $t) ) == '' )
                $missing_tables[] = $t;
        }
        $debug_info['basic']['Table check'] = $missing_tables
                                              ? sprintf( _( 'Missing tables: %s', 'debug-info', 'WPBDM' ), implode(',', $missing_tables) )
                                              : _x( 'OK', 'debug-info', 'WPBDM' );

        $debug_info['basic']['Main Page'] = sprintf( '%d (%s)', wpbdp_get_page_id( 'main' ), get_post_status( wpbdp_get_page_id( 'main' ) ) );
        $debug_info['basic'] = apply_filters( 'wpbdp_debug_info_section', $debug_info['basic'], 'basic' );        


        // BD options
        $blacklisted = array( 'googlecheckout-merchant', 'paypal-business-email', 'wpbdp-2checkout-seller', 'recaptcha-public-key', 'recaptcha-private-key' );
        $debug_info['options']['_title'] = _x( 'BD Options', 'debug-info', 'WPBDM' );

        $settings_api = wpbdp_settings_api();
        foreach ( $settings_api->settings as &$s  ) {
            if ( $s->type == 'core' || in_array( $s->name, $blacklisted ) )
                continue;

            $debug_info['options'][ $s->name ] = wpbdp_get_option( $s->name );
        }
        $debug_info['options'] = apply_filters( 'wpbdp_debug_info_section', $debug_info['options'], 'options' );

        // environment info
        $debug_info['environment']['_title'] = _x( 'Environment', 'debug-info', 'WPBDM' );
        $debug_info['environment']['WordPress version'] = get_bloginfo( 'version', 'raw' );
        $debug_info['environment']['OS'] = php_uname( 's' ) . ' ' . php_uname( 'r' ) . ' ' . php_uname( 'm' );
        
        if ( function_exists( 'apache_get_version' ) ) {
            $apache_version = apache_get_version();
            $debug_info['environment']['Apache version'] = $apache_version;
        }

        $debug_info['environment']['PHP version'] = phpversion();

        $mysql_version = $wpdb->get_var( 'SELECT @@version' );
        if ( $sql_mode = $wpdb->get_var( 'SELECT @@sql_mode' ) )
            $mysql_version .= ' ( ' . $sql_mode . ' )';
        $debug_info['environment']['MySQL version'] = $mysql_version ? $mysql_version : 'N/A';

        $sqlite_version = class_exists('SQLite3') ? wpbdp_getv( SQLite3::version(), 'versionString', '' ): ( function_exists( 'sqlite_libversion' ) ? sqlite_libversion() : null );
        $debug_info['environment']['SQLite version'] = $sqlite_version ? $sqlite_version : 'N/A';

        $debug_info['environment']['cURL version'] = function_exists( 'curl_init' ) ? wpbdp_getv( curl_version(), 'version' ) : 'N/A';

        $debug_info['environment'] = apply_filters( 'wpbdp_debug_info_section', $debug_info['environment'], 'environment' );

        $debug_info = apply_filters( 'wpbdp_debug_info', $debug_info );

        if ( $plain ) {
            foreach ( $debug_info as &$section ) {
                foreach ( $section as $k => $v ) {
                    if ( $k == '_title' ) {
                        printf( '== %s ==', $v );
                        print PHP_EOL;
                        continue;
                    }

                    printf( "%-33s = %s", $k, $v );
                    print PHP_EOL;
                }

                print str_repeat( PHP_EOL, 2 );
            }
            return;
        }

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/debug-info.tpl.php', array( 'debug_info' => $debug_info ) );
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
        if (!wpbdp_get_page_id('main')) {
            if (isset($_GET['action']) && $_GET['action'] == 'createmainpage') // do not show message in the page creating the main page
                return;

            $message = _x('<b>Business Directory Plugin</b> requires a page with the <tt>[businessdirectory]</tt> shortcode to function properly.', 'admin', 'WPBDM');
            $message .= '<br />';
            $message .= _x('You can create this page by yourself or let Business Directory do this for you automatically.', 'admin', 'WPBDM');
            $message .= '<p>';
            $message .= sprintf('<a href="%s" class="button">%s</a>',
                                admin_url('admin.php?page=wpbdp_admin&action=createmainpage'),
                                _x('Create required pages for me', 'admin', 'WPBDM'));
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

}

function wpbdp_admin_message( $msg, $kind = '' ) {
    global $wpbdp;
    $wpbdp->admin->messages[] = $kind ? array( $msg, $kind ) : $msg;
}

}