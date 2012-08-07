<?php
require_once(WPBDP_PATH . 'admin/admin-pages.php');
require_once(WPBDP_PATH . 'admin/fees.php');
require_once(WPBDP_PATH . 'admin/form-fields.php');
require_once(WPBDP_PATH . 'admin/csv-import.php');

if (!class_exists('WPBDP_Admin')) {

class WPBDP_Admin {

    public $messages = array();

    function __construct() {
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'add_metaboxes'));
        add_action('admin_init', array($this, 'check_for_required_fields'));
        add_action('before_delete_post', array($this, '_delete_post_metadata'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'admin_javascript'));
        add_action('admin_enqueue_scripts', array($this, 'admin_styles'));

        add_filter(sprintf('manage_edit-%s_columns', WPBDP_Plugin::POST_TYPE),
                   array($this, 'add_custom_columns'));
        add_filter('manage_edit-' . WPBDP_Plugin::POST_TYPE_CATEGORY . '_columns', array($this, '_custom_taxonomy_columns'));
        add_filter('manage_edit-' . WPBDP_Plugin::POST_TYPE_TAGS . '_columns', array($this, '_custom_taxonomy_columns'));
        add_action(sprintf('manage_posts_custom_column'), array($this, 'custom_columns'));
        add_filter('views_edit-' . WPBDP_Plugin::POST_TYPE, array($this, 'add_custom_views'));
        add_filter('request', array($this, 'apply_query_filters'));

        add_action('save_post', array($this, '_save_post'));

        add_filter('wp_terms_checklist_args', array($this, '_checklist_args')); // fix issue #152

        add_action('wp_ajax_wpbdp-uploadimage', array($this, '_upload_image'));
        add_action('wp_ajax_wpbdp-deleteimage', array($this, '_delete_image'));
        add_action('wp_ajax_wpbdp-listingimages', array($this, '_listing_images'));

        add_action('admin_footer', array($this, '_add_bulk_actions'));
    }

    function admin_javascript() {
        wp_enqueue_script('wpbdp-admin-js', plugins_url('/resources/admin.js', __FILE__), array('jquery', 'thickbox'));
    }

    function admin_styles() {
        wp_enqueue_style('wpbdp-admin', plugins_url('/resources/admin.css', __FILE__));
        wp_enqueue_style('thickbox');
  }

    function admin_menu() {
        add_menu_page(_x("Business Directory Admin", 'admin menu', "WPBDM"),
                      _x('Directory Admin', 'admin menu', 'WPBDM'),
                      'activate_plugins',
                      'wpbusdirman.php',
                      'wpbusdirman_home_screen',
                      WPBDP_URL . 'resources/images/menuico.png');
        add_submenu_page('wpbusdirman.php',
                         _x('Add New Listing', 'admin menu', 'WPBDM'),
                         _x('Add New Listing', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdman_c3a',
                         'wpbdp_admin_add_listing');
        add_submenu_page('wpbusdirman.php',
                         _x('Manage Options', 'admin menu', 'WPBDM'),
                         _x('Manage Options', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp_admin_settings',
                         array($this, 'admin_settings'));
        add_submenu_page('wpbusdirman.php',
                         _x('Manage Fees', 'admin menu', 'WPBDM'),
                         _x('Manage Fees', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp_admin_fees',
                         array('WPBDP_FeesAdmin', 'admin_menu_cb'));
        add_submenu_page('wpbusdirman.php',
                         _x('Manage Form Fields', 'admin menu', 'WPBDM'),
                         _x('Manage Form Fields', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp_admin_formfields',
                         array('WPBDP_FormFieldsAdmin', 'admin_menu_cb'));
        add_submenu_page('wpbusdirman.php',
                         _x('Manage Featured', 'admin menu', 'WPBDM'),
                         _x('Manage Featured', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdman_c4',
                         '_placeholder_');
        add_submenu_page('wpbusdirman.php',
                         _x('Manage Payments', 'admin menu', 'WPBDM'),
                         _x('Manage Payments', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdman_c5',
                         '_placeholder_');
        add_submenu_page('wpbusdirman.php',
                         _x('CSV Import', 'admin menu', 'WPBDM'),
                         _x('CSV Import', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdp-csv-import',
                         array('WPBDP_CSVImportAdmin', 'admin_menu_cb'));
        add_submenu_page('wpbusdirman.php',
                         _x('Uninstall WPDB Manager', 'admin menu', 'WPBDM'),
                         _x('Uninstall', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdman_m1',
                         array($this, 'uninstall_plugin'));

        // just a little hack
        if (current_user_can('activate_plugins')) {
            global $submenu;
            $submenu['wpbusdirman.php'][1][2] = admin_url(sprintf('post-new.php?post_type=%s', wpbdp_post_type()));
            $submenu['wpbusdirman.php'][0][0] = _x('Main Menu', 'admin menu', 'WPBDM');
            $submenu['wpbusdirman.php'][5][2] = admin_url(sprintf('edit.php?post_type=%s&wpbdmfilter=%s', wpbdp()->get_post_type(), 'pendingupgrade'));
            $submenu['wpbusdirman.php'][6][2] = admin_url(sprintf('edit.php?post_type=%s&wpbdmfilter=%s', wpbdp()->get_post_type(), 'unpaid'));
        }
    }

    public function _delete_post_metadata($post_id) {
        global $wpdb;

        if ( current_user_can('delete_posts') && get_post_type($post_id) == wpbdp_post_type() ) {
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
                     WPBDP_Plugin::POST_TYPE,
                     'side',
                     'core'
                    );

        add_meta_box('wpbdp-listing-fields',
                    _x('Listing Fields / Images', 'admin', 'WPBDM'),
                    array($this, '_listing_fields_metabox'),
                    wpbdp_post_type(),
                    'normal',
                    'core');
    }

    public function _listing_fields_metabox($post) {
        $formfields_api = wpbdp_formfields_api();

        $post_values = wpbdp_getv($_POST, 'listingfields', array());

        echo wp_nonce_field(plugin_basename( __FILE__ ), 'wpbdp-listing-fields-nonce');

        echo '<div style="border-bottom: solid 1px #dedede; padding-bottom: 10px;">';
        echo sprintf('<strong>%s</strong>', _x('Listing Fields', 'admin', 'WPBDM'));
        echo '<div style="padding-left: 10px;">';
        foreach ($formfields_api->getFieldsByAssociation('meta') as $field) {
            $value = wpbdp_getv($post_values, $field->id, wpbdp_get_listing_field_value($post->ID, $field));

            echo $formfields_api->render($field, $value);
        }
        echo '</div>';
        echo '</div>';
        echo '<div class="clear"></div>';

        // listing images
        echo sprintf('<div id="wpbdp-listing-images" class="wpbdp-ajax-placeholder"
                           data-action="wpbdp-listingimages"
                           data-post_id="%s"
                           data-baseurl="%s"></div>',
                    $post->ID,
                    remove_query_arg(array('message', 'wpbdmaction')));
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

        if (is_admin() && isset($_POST['post_type']) && $_POST['post_type'] == wpbdp_post_type()) {
            // Fix listings added through admin site
            wpbdp_listings_api()->set_default_listing_settings($post_id);

            // Save custom fields
            if (isset($_POST['wpbdp-listing-fields-nonce']) && wp_verify_nonce( $_POST['wpbdp-listing-fields-nonce'], plugin_basename( __FILE__ ) ) ) {
                // save custom fields
                $formfields_api = wpbdp_formfields_api();
                $listingfields = wpbdp_getv($_POST, 'listingfields', array());
                
                foreach ($formfields_api->getFieldsByAssociation('meta') as $field) {
                    if (isset($listingfields[$field->id])) {
                        if ($value = $formfields_api->extract($listingfields, $field)) {
                            if (in_array($field->type, array('multiselect', 'checkbox'))) {
                                $value = implode("\t", $value);
                            }

                            update_post_meta($post_id, '_wpbdp[fields][' . $field->id . ']', $value);
                        } else {
                            update_post_meta($post_id, '_wpbdp[fields][' . $field->id . ']', null);
                        }
                    }
                }

                if (isset($_POST['thumbnail_id']))
                    update_post_meta($post_id, '_wpbdp[thumbnail_id]', $_POST['thumbnail_id']);
            }
        }
    }

    function listing_metabox($post) {
        $listings_api = wpbdp_listings_api();

        // Payment status
        $payment_status = $listings_api->get_payment_status($post->ID);

        echo '<div class="misc-pub-section">';

        echo '<ul class="listing-metabox-tabs">';
        echo '<li class="tabs selected"><a href="#listing-metabox-generalinfo">' . _x('General Info', 'admin', 'WPBDM') . '</a></li>';
        echo '<li class="tabs"><a href="#listing-metabox-fees">' . _x('Fee Details', 'admin', 'WPBDM') . '</a></li>';
        echo '<li class="tabs"><a href="#listing-metabox-transactions">' . _x('Transactions', 'admin', 'WPBDM') . '</a></li>';
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
                $sticky_status = $listings_api->get_sticky_status($post->ID);
                $status_string = '';

                if ($sticky_status == 'sticky')
                    $status_string = _x('Featured', 'admin metabox', 'WPBDM');
                elseif ($sticky_status == 'pending')
                    $status_string = _x('Pending Upgrade', 'admin metabox', 'WPBDM');
                else
                    $status_string = _x('Normal', 'admin metabox', 'WPBDM');

                echo '<span><b>' . $status_string . '</b> </span>';
                
                if ($sticky_status == 'sticky') {
                    echo sprintf('<a href="%s">%s</a>',
                                 add_query_arg('wpbdmaction', 'cancelfeatured'),
                                 _x('Downgrade', 'admin metabox', 'WPBDM'));
                } else {
                    if (current_user_can('activate_plugins')) {
                        echo sprintf('<a href="%s">%s</a>',
                                     add_query_arg('wpbdmaction', 'upgradefeatured'),
                                     __('Upgrade'));
                    }
                }
            echo '</dd>';
        echo '</dl>';
        if ($payment_status != 'paid')
            echo sprintf('<a href="%s" class="button-primary">%s</a> ',
                     add_query_arg('wpbdmaction', 'setaspaid'),
                     _x('Mark listing as Paid', 'admin infometabox', 'WPBDM'));
        else
            echo sprintf('<a href="%s" class="button">%s</a>',
                         add_query_arg('wpbdmaction', 'setasnotpaid'),
                         _x('Mark listing as Not paid', 'admin infometabox', 'WPBDM'));
        echo '</div>';

        // Transactions
        echo wpbdp_render_page(WPBDP_PATH . 'admin/templates/infometabox-transactions.tpl.php', array(
                                'transactions' => wpbdp_payments_api()->get_transactions($post->ID)
                               ));

        // Fees
        echo wpbdp_render_page(WPBDP_PATH . 'admin/templates/infometabox-fees.tpl.php', array(
                                'post_categories' => wp_get_post_terms($post->ID, wpbdp_categories_taxonomy()),
                                'post_id' => $post->ID,
                                'image_count' => count($listings_api->get_images($post->ID))
                                ));
        echo '</div>';

        echo '<div class="clear"></div>';

    }

    function apply_query_filters($request) {
        global $current_screen;

        if (is_admin() && isset($_REQUEST['wpbdmfilter']) && $current_screen->id == 'edit-' . WPBDP_Plugin::POST_TYPE) {
            switch ($_REQUEST['wpbdmfilter']) {
                case 'pendingupgrade':
                    $request['meta_key'] = '_wpbdp[sticky]';
                    $request['meta_value'] = 'pending';
                    break;
                case 'paid':
                    $request['meta_key'] = '_wpbdp[payment_status]';
                    $request['meta_value'] = 'paid';
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
        foreach ($this->messages as $msg) {
            if (is_array($msg)) {
                echo sprintf('<div class="%s">%s</div>', $msg[1], $msg[0]);
            } else {
                echo sprintf('<div class="updated">%s</div>', $msg);
            }
        }

        $this->messages = array();
    }

    public function _add_bulk_actions() {
        if ($screen = get_current_screen()) {
            if ($screen->id == 'edit-' . wpbdp_post_type()) {
                if (isset($_GET['post_type']) && $_GET['post_type'] == WPBDP_Plugin::POST_TYPE) {
                    $bulk_actions = array('sep0' => '--',
                                          'publish' => _x('Publish Listing', 'admin actions', 'WPBDM'),
                                          'sep1' => '--',
                                          'upgradefeatured' => _x('Upgrade to Featured', 'admin actions', 'WPBDM'),
                                          'cancelfeatured' => _x('Downgrade to Normal', 'admin actions', 'WPBDM'),
                                          'sep2' => '--',
                                          'setaspaid' => _x('Set Paid', 'admin actions', 'WPBDM'),
                                          'setasnotpaid' => _x('Set Not Paid', 'admin actions', 'WPBDM')
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

    function handle_actions() {
        if (!isset($_REQUEST['wpbdmaction']) || !isset($_REQUEST['post']))
            return;

        $action = $_REQUEST['wpbdmaction'];
        $posts = is_array($_REQUEST['post']) ? $_REQUEST['post'] : array($_REQUEST['post']);

        $listings_api = wpbdp_listings_api();

        if (!current_user_can('activate_plugins'))
            exit;

        switch ($action) {
            case 'publish':
                foreach ($posts as $post_id) {
                    wp_publish_post($post_id);
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

            case 'upgradefeatured':
                foreach ($posts as $post_id) {
                    update_post_meta($post_id, '_wpbdp[sticky]', 'sticky');
                }
            
                $this->messages[] = _nx('The listing has been upgraded.',
                                        'The listings have been upgraded.',
                                        count($posts),
                                        'admin',
                                        'WPBDM');
                break;

            case 'cancelfeatured':
                foreach ($posts as $post_id) {
                    delete_post_meta($post_id, "_wpbdp[sticky]");
                }
                
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

            case 'assignfee':
                if ($listings_api->assign_fee($posts[0], $_GET['category_id'], $_GET['fee_id']))
                    $this->messages[] = _x('The fee was sucessfully assigned.', 'admin', 'WBPDM');
                break;

            default:
                break;
        }

        $_SERVER['REQUEST_URI'] = remove_query_arg( array('wpbdmaction', 'wpbdmfilter', 'transaction_id', 'category_id', 'fee_id', 'post'), $_SERVER['REQUEST_URI'] );
    }

    function add_custom_views($views) {
        global $wpdb;

        $post_statuses = '\'' . join('\',\'', isset($_GET['post_status']) ? array($_GET['post_status']) : array('publish', 'draft')) . '\'';

        $paid_query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND p.post_status IN ({$post_statuses}) AND ( (pm.meta_key = %s AND pm.meta_value = %s) )",
                                                           WPBDP_Plugin::POST_TYPE,
                                                           '_wpbdp[payment_status]',
                                                           'paid');
        $paid = $wpdb->get_var( $paid_query);

        $unpaid = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND p.post_status IN ({$post_statuses}) AND ( (pm.meta_key = %s AND NOT pm.meta_value = %s) ) GROUP BY p.ID",
                                                           WPBDP_Plugin::POST_TYPE,
                                                           '_wpbdp[payment_status]',
                                                           'paid') );
        $pending_upgrade = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND p.post_status IN ({$post_statuses}) AND ( (pm.meta_key = %s AND pm.meta_value = %s) )",
                                                           WPBDP_Plugin::POST_TYPE,
                                                           '_wpbdp[sticky]',
                                                           'pending') );

        $views['paid'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                                 add_query_arg('wpbdmfilter', 'paid'),
                                 wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'paid' ? 'current' : '',
                                 __('Paid', 'WPBDM'),
                                 number_format_i18n($paid));
        $views['unpaid'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                                   add_query_arg('wpbdmfilter', 'unpaid'),
                                   wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'unpaid' ? 'current' : '',
                                   __('Unpaid', 'WPBDM'),
                                   number_format_i18n($unpaid));
        $views['featured'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                                   add_query_arg('wpbdmfilter', 'pendingupgrade'),
                                   wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'pendingupgrade' ? 'current' : '',
                                   __('Pending Upgrade', 'WPBDM'),
                                   number_format_i18n($pending_upgrade));
        return $views;

    }

    public function _custom_taxonomy_columns($cols) {
        $cols['posts'] = _x('Listing Count', 'admin', 'WPBDM');
        return $cols;
    }

    function add_custom_columns($columns_) {
        $columns = array();

        foreach (array_keys($columns_) as $key) {
            $columns[$key] = $columns_[$key];

            if ($key == 'title') {
                // add custom columns *after* the title column
                $columns['bd_payment_status'] = __('Payment Status', 'WPBDM');
                $columns['bd_sticky_status'] = __('Featured (Sticky) Status', 'WPBDM');                
            }
        }

        return $columns;
    }

    function custom_columns($column) {
        switch ($column) {
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

        if (current_user_can('activate_plugins')) {
            echo sprintf('<div class="row-actions"><b>%s:</b> %s</div>', __('Set as', 'WPBDM'), $status_links);
        }
    }

    private function sticky_status_column() {
        global $post;

        $listings_api = wpbdp_listings_api();

        $status = $listings_api->get_sticky_status($post->ID);

        $status_string = '';
        if ($status == 'sticky')
            $status_string = __('Featured', 'WPBDM');
        elseif ($status == 'pending')
            $status_string = __('Pending Upgrade', 'WPBDM');
        else
            $status_string = _x('Normal', 'admin list', 'WPBDM');
        
        echo sprintf('<span class="status %s">%s</span><br />',
                    str_replace(' ', '', $status),
                    $status_string);

        echo '<div class="row-actions">';

        if (current_user_can('activate_plugins')) {
            if ($status == 'sticky') {
                echo sprintf('<span><a href="%s">%s</a></span>',
                             add_query_arg(array('wpbdmaction' => 'cancelfeatured', 'post' => $post->ID)),
                             '<b>↓</b> ' . __('Downgrade to Normal', 'WPBDM'));
            } else {
                echo sprintf('<span><a href="%s">%s</a></span>',
                             add_query_arg(array('wpbdmaction' => 'upgradefeatured', 'post' => $post->ID)),
                             '<b>↑</b> ' . __('Upgrade to Featured', 'WPBDM'));
            }
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

        wpbdp_render_page(WPBDP_PATH . 'admin/templates/settings.tpl.php',
                          array('wpbdp_settings' => $wpbdp->settings),
                          true);
    }

    /* Uninstall. */
    public function uninstall_plugin() {
        if (isset($_POST['doit']) && $_POST['doit'] == 1) {
            $new_status = wpbdp_get_option('status-on-uninstall');
            $posts = get_posts(array('post_type' => wpbdp_post_type()));

            foreach ($posts as $post) {
                $post_array = array('ID' => $post->ID,
                                    'post_type' => wpbdp_post_type(),
                                    'post_status' => $new_status);
                wp_update_post($post_array);
            }

            // delete options
            $settings_api = wpbdp_settings_api();
            $settings_api->reset_defaults();

            // clear scheduled hooks
            wp_clear_scheduled_hook('wpbdp_listings_expiration_check');

            // deactivate plugin
            deactivate_plugins(plugin_basename(WPBDP_PATH . 'wpbusdirman.php'), true);
            
            echo wpbdp_render_page(WPBDP_PATH . 'admin/templates/uninstall-complete.tpl.php');
        } else {
            echo wpbdp_render_page(WPBDP_PATH . 'admin/templates/uninstall-confirm.tpl.php');
        }
    }

    /* Required fields check. */
    public function check_for_required_fields() {
        $formfields_api = wpbdp_formfields_api();

        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'wpbdp_admin_formfields' &&
            isset($_REQUEST['action']) && $_REQUEST['action'] == 'createrequired') {
            // do not display the warning inside the page creating the required fields
            return;
        }

        if ($missing = $formfields_api->check_for_required_fields()) {
            if (count($missing) > 1) {
                $message = sprintf(_x('<b>Business Directory Plugin</b> requires fields with the following associations in order to work correctly: <b>%s</b>.', 'admin', 'WPBDM'), join(', ', $missing));
            } else {
                $message = sprintf(_x('<b>Business Directory Plugin</b> requires a field with a <b>%s</b> association in order to work correctly.', 'admin', 'WPBDM'), $missing[0]);
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

}

}