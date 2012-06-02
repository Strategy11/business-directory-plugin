<?php
require_once(WPBDP_PATH . 'admin/admin-pages.php');
require_once(WPBDP_PATH . 'admin/fees.php');
require_once(WPBDP_PATH . 'admin/form-fields.php');
require_once(WPBDP_PATH . 'admin/uninstall.php');

if (!class_exists('WPBDP_Admin')) {

class WPBDP_Admin {

    public $messages = array();

    function __construct() {
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'add_metaboxes'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'admin_javascript'));
        add_action('admin_enqueue_scripts', array($this, 'admin_styles'));

        add_filter(sprintf('manage_edit-%s_columns', WPBDP_Plugin::POST_TYPE),
                   array($this, 'add_custom_columns'));
        add_action(sprintf('manage_posts_custom_column'), array($this, 'custom_columns'));
        add_filter('views_edit-' . WPBDP_Plugin::POST_TYPE, array($this, 'add_custom_views'));
        add_filter('request', array($this, 'apply_query_filters'));

        add_action('save_post', array($this, '_save_post'));
    }

    function admin_javascript() {
        wp_enqueue_script('wpbdp-admin-js', plugins_url('/resources/admin.js', __FILE__), array('jquery'));
    }

    function admin_styles() {
        wp_enqueue_style('wpbdp-admin', plugins_url('/resources/admin.css', __FILE__));
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
                         'wpbdp_settings',
                         array($this, 'admin_settings'));
        add_submenu_page('wpbusdirman.php',
                         _x('Manage Fees', 'admin menu', 'WPBDM'),
                         _x('Manage Fees', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdman_c2',
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
                         _x('Uninstall WPDB Manager', 'admin menu', 'WPBDM'),
                         _x('Uninstall', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdman_m1',
                         'wpbusdirman_uninstall');

        // just a little hack
        if (current_user_can('activate_plugins')) {
            global $submenu;
            $submenu['wpbusdirman.php'][0][0] = _x('Main Menu', 'admin menu', 'WPBDM');
            $submenu['wpbusdirman.php'][5][2] = admin_url(sprintf('edit.php?post_type=%s&wpbdmfilter=%s', wpbdp()->get_post_type(), 'pendingupgrade'));
            $submenu['wpbusdirman.php'][6][2] = admin_url(sprintf('edit.php?post_type=%s&wpbdmfilter=%s', wpbdp()->get_post_type(), 'unpaid'));
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

        echo wp_nonce_field( plugin_basename( __FILE__ ), 'wpbdp-listing-fields-nonce');

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
/*        $listings_api = wpbdp_listings_api();

        echo '<div style="margin-top: 10px;">';
        echo sprintf('<b>%s</b>', _x('Listing Images', 'admin', 'WPBDM'));
        echo '<div style="padding-left: 10px;">';
        if ($images = $listings_api->get_images($post->ID)) {

        }
        echo '</div>';
        echo '</div>';*/
    }

    public function _save_post($post_id) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
            return;

        if (!wp_verify_nonce( $_POST['wpbdp-listing-fields-nonce'], plugin_basename( __FILE__ ) ) )
            return;

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
                }
            }
        }
    }

    function listing_metabox($post) {
        $listings_api = wpbdp_listings_api();

        // Payment status
        $payment_status = $listings_api->get_payment_status($post->ID);
        $last_transaction = $listings_api->get_last_transaction($post->ID);

        echo '<div class="misc-pub-section">';

        echo '<ul class="listing-metabox-tabs">';
        echo '<li class="tabs"><a href="">' . _x('Payment Information', 'admin', 'WPBDM') . '</a></li>';
        echo '<li class="tabs"><a href="">' . _x('Fee Information', 'admin', 'WPBDM') . '</a></li>';
        echo '<li class="tabs"><a href="">' . _x('Transaction History', 'admin', 'WPBDM') . '</a></li>';
        echo '</ul>';

        echo '<strong>' . __('Payment Information', 'WPBDM') . '</strong>';        
        echo '<dl>';
            echo '<dt><u>'. __('Listing Cost') . '</u></dt>';            
            echo '<dd>' . wpbdp_get_option('currency-symbol') .$listings_api->cost_of_listing($post->ID) . '</dd>';
            echo '<dt><u>'. __('Status', 'WPBDM') . '</u></dt>';
            echo '<dd>' . $payment_status . '</dd>';
            echo '<dt><u>' . _x('Last Transaction', 'admin', 'WPBDM') . '</u></dt>';
            echo '<dd>';
                if ($last_transaction) {
                    echo sprintf('<span>%s:</span> <strong>%s</strong><br />',
                                 _x('Payment Type', 'admin', 'WPBDM'),
                                 $last_transaction->payment_type ? $last_transaction->payment_type : '?');                    
                    echo sprintf('<span>%s:</span> <strong>%s</strong><br />',
                                 _x('Gateway', 'admin', 'WPBDM'),
                                 $last_transaction->gateway ? $last_transaction->gateway : '?');
                    echo sprintf('<span>%s: <strong>%s</strong></span><br />',
                                 _x('Amount', 'admin', 'WPBDM'),
                                 wpbdp_get_option('currency-symbol') . $last_transaction->amount);
                    echo sprintf('<span>%s: <strong>%s</strong></span><br />',
                                 _x('Status', 'admin', 'WPBDM'),
                                 $last_transaction->status);
                    echo sprintf('<span>%s: <strong>%s</strong></span><br />',
                                 _x('Date', 'admin', 'WPBDM'),
                                 $last_transaction->created_on);
                    echo sprintf('<span>%s: </span><strong>%s</strong><br />',
                                 _x('Payer Info', 'admin', 'WPBDM'),
                                 $last_transaction->payerinfo['name'] ? $last_transaction->payerinfo['name'] : _x('Unknown', 'admin', 'WPBDM'));
                    if ($last_transaction->payerinfo['email'])
                        echo sprintf('<a href="mailto:%s">%s</a>', $last_transaction->payerinfo['email'], $last_transaction->payerinfo['email']);

                    if ($last_transaction->processed_on)
                        echo sprintf('<span>%s:</span> <strong>%s</strong> by <strong>%s</strong>',
                                     _x('Processed on', 'admin', 'WPBDM'),
                                     $last_transaction->processed_on,
                                     $last_transaction->processed_by);
                } else {
                    echo '--';
                }
            echo '</dd>';
        echo '</dl>';

        if ($payment_status != 'paid')
            echo sprintf('<a href="%s" class="button-primary">%s</a> ',
                     add_query_arg('wpbdmaction', 'setaspaid'),
                     __('Set listing as Paid', 'WPBDM'));
        else
            echo sprintf('<a href="%s" class="button">%s</a>',
                         add_query_arg('wpbdmaction', 'setasnotpaid'),
                         __('Set listing as Not paid', 'WPBDM'));
        echo '</div>';

        echo '<div class="misc-pub-section">';
        echo '<strong>' . _x('Transaction History', 'admin', 'WPBDM') . '</strong>';
        echo '<ul>';
        
        foreach (wpbdp_payments_api()->get_transactions($post->ID) as $transaction) {
            echo '<li>';
            echo '<dl>';
            echo '<dt>' . _x('Date', 'admin', 'WPBDM') . '</dt>';
            echo '<dd>' . $transaction->created_on . '</dd>';
            echo '<dt>' . _x('Payment Type', 'admin', 'WPBDM') . '</dt>';
            echo '<dd>' . $transaction->payment_type . '</dd>';
            echo '<dt>' . _x('Amount', 'admin', 'WPBDM') . '</dt>';
            echo '<dd>' . $transaction->amount . '</dd>';            
            echo '<dt>' . _x('Gateway', 'admin', 'WPBDM') . '</dt>';
            echo '<dd>' . ($transaction->gateway ? $transaction->gateway : '--') . '</dd>';
            echo '<dt>' . _x('Status', 'admin', 'WPBDM') . '</dt>';
            echo '<dd>' . $transaction->status . '</dd>';

            if ($transaction->processed_on) {
                echo '<dt></dt>';
                echo '<dd>' . sprintf(_x('Processed on <b>%s</b> by <b>%s</b>', 'admin', 'WPBDM'),
                                     $transaction->processed_on,
                                     $transaction->processed_by) . '</dd>';
            }
            echo '</dl>';
            echo '</li>';
        }        

        echo '</ul>';
        echo '</div>';

        echo '<div class="misc-pub-section">';
        echo '<strong>' . _x('Fee Information', 'admin', 'WPBDM') . '</strong>';

        echo '<dl>';

        $image_count = count($listings_api->get_images($post->ID));

        foreach (wp_get_post_terms($post->ID, wpbdp_categories_taxonomy()) as $post_term) {
            echo '<dt>' . $post_term->name . '</dt>';
            echo '<dd>';

            if ($fee_info = $listings_api->get_listing_fee_for_category($post->ID, $post_term->term_id)) {
                echo '<span>' . _x('# images', 'admin', 'WPBDM') . ':</span> ';
                echo sprintf('%d / %d', min($image_count, $fee_info->images), $fee_info->images);
                echo '<br /><span>' . _x('term length', 'admin', 'WPBDM') . ':</span> ';
                echo $fee_info->days;
                echo '<br /><span>' . _x('expires on', 'admin', 'WPBDM') . ':</span> ';
                if ($fee_info->expires_on) {
                    echo date_i18n(get_option('date_format'), strtotime($fee_info->expires_on));
                } else {
                    echo _x('never', 'admin', 'WPBDM');
                }
            } else {
                echo '--';
            }

            echo '</dd>';
        }

        echo '</dl>';
        echo '</div>';

        /*
         * Sticky information.
         */
        $sticky_status = $listings_api->get_sticky_status($post->ID);
        $status_string = '';

        if ($sticky_status == 'sticky')
            $status_string = _x('Featured', 'admin metabox', 'WPBDM');
        elseif ($sticky_status == 'pending')
            $status_string = _x('Pending Upgrade', 'admin metabox', 'WPBDM');
        else
            $status_string = _x('Normal', 'admin metabox', 'WPBDM');

        echo '<div class="misc-pub-section">';
        echo '<label>' .  _x('Sticky Status', 'admin metabox', 'WPBDM') . ': </label>';
        echo '<span><b>' . $status_string . '</b> </span>';
        
        if ($sticky_status == 'sticky') {
            echo sprintf('<a href="%s">%s</a>',
                         add_query_arg('wpbdmaction', 'cancelfeatured'),
                         _x('Downgrade', 'admin metabox', 'WPBDM'));
        } else {
            echo sprintf('<a href="%s">%s</a>',
                         add_query_arg('wpbdmaction', 'upgradefeatured'),
                         __('Upgrade'));
        }

        echo '</div>';
        echo '<div class="clear"></div>';

        /* Fee info. 
        echo '<div class="misc-pub-section">';
        echo '<strong>' . _x('Fee Information', 'admin', 'WPBDM') . '</strong>';

        foreach ()

        echo '</div>';
        echo '<div class="clear"></div>';        */

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

    function handle_actions() {
        if (!isset($_REQUEST['wpbdmaction']) || !isset($_REQUEST['post']))
            return;

        $action = $_REQUEST['wpbdmaction'];
        $post_id = intval($_REQUEST['post']);

        $listings_api = wpbdp_listings_api();

        switch ($action) {
            case 'setaspaid':
                $last_transaction = $listings_api->get_last_transaction($post_id);
                $last_transaction->status = 'approved';
                $last_transaction->processed_on = date('Y-m-d H:i:s', time());
                $last_transaction->processed_by = 'admin';
                wpbdp_payments_api()->save_transaction($last_transaction);

                if ($last_transaction->payment_type == 'upgrade-to-sticky')
                    update_post_meta($post_id, '_wpbdp[sticky]', 'sticky');
                
                update_post_meta($post_id, '_wpbdp[payment_status]', 'paid');

                $this->messages[] = __("The listing status has been set as paid.","WPBDM");
                break;
            
            case 'setasnotpaid':
                $last_transaction = $listings_api->get_last_transaction($post_id);
                $last_transaction->status = 'rejected';
                $last_transaction->processed_on = date('Y-m-d H:i:s', time());
                $last_transaction->processed_by = 'admin';
                wpbdp_payments_api()->save_transaction($last_transaction);

                update_post_meta($post_id, '_wpbdp[payment_status]', 'not-paid');
                

                $this->messages[] = __("The listing status has been changed to 'not paid'.","WPBDM");
                break;

            case 'upgradefeatured':
                update_post_meta($post_id, '_wpbdp[sticky]', 'sticky');
            
                $this->messages[] = __("The listing has been upgraded.","WPBDM");
                break;

            case 'cancelfeatured':
                delete_post_meta($post_id, "_wpbdp[sticky]");
                
                $this->messages[] = __("The listing has been downgraded.","WPBDM");
                break;

            default:
                break;
        }

        $_SERVER['REQUEST_URI'] = remove_query_arg( array('wpbdmaction', 'wpbdmfilter'), $_SERVER['REQUEST_URI'] );
    }

    function add_custom_views($views) {
        global $wpdb;

        $paid = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND ( (pm.meta_key = %s AND pm.meta_value = %s) )",
                                                           WPBDP_Plugin::POST_TYPE,
                                                           '_wpbdp[payment_status]',
                                                           'paid') );
        $unpaid = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND ( (pm.meta_key = %s AND NOT pm.meta_value = %s) ) GROUP BY p.ID",
                                                           WPBDP_Plugin::POST_TYPE,
                                                           '_wpbdp[payment_status]',
                                                           'paid') );
        $pending_upgrade = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND ( (pm.meta_key = %s AND pm.meta_value = %s) )",
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
            $status_links .= sprintf('<span><a href="%s">%s</a> | </span>',
                                    add_query_arg(array('wpbdmaction' => 'setaspaid', 'post' => $post->ID)),
                                    __('Paid', 'WPBDM'));
        $status_links .= sprintf('<span><a href="%s">%s</a></span>',
                                  add_query_arg(array('wpbdmaction' => 'setasnotpaid', 'post' => $post->ID)),
                                  __('Not paid', 'WPBDM'));

        if ($paid_status == 'paid') {
            if ($last_transaction = $listings_api->get_last_transaction($post->ID)) {
                echo sprintf('<span class="status %s">%s</span>', $paid_status, strtoupper($paid_status));
                // echo '<div class="paymentdata">';
                // echo sprintf('<span><b>%s:</b></span>', _x('Last Payment', 'admin', 'WPBDM'));
                echo sprintf('<div class="row-actions"><b>%s:</b> %s</div>', __('Set as', 'WPBDM'), $status_links);
                echo '</div>';
            //     echo sprintf('<div class="row-actions"><b>%s:</b> %s</div>', __('Set as', 'WPBDM'), $status_links);
            //     // echo sprintf('<span class="status %s">%s</span><div class="paymentdata"><b>%s</b>: <span class="gateway">%s</span> | <b>%s:</b> <span class="buyer">%s</span> <span class="email" title="%s">%s</span></div><div class="row-actions"><b>%s:</b> %s</div>',
            //     //               $paid_status,
            //     //               strtoupper($paid_status),
            //     //               __('Gateway', 'WPBDM'), get_post_meta($post->ID, '_wpbdp_paymentgateway', true),
            //     //               __('Buyer', 'WPBDM'), $buyer != ' ' ? $buyer : '--',
            //     //               __('Payment Email', 'WPBDM'), $email ? '(' . $email . ')' : '',
            //     //               __('Set as', 'WPBDM'),
            //     //               $status_links);
            } else {
                if ($listings_api->is_free_listing($post->ID)) {
                    echo _x('(Free Listing)', 'admin', 'WPBDM');
                } 
            }
        } else {
            echo sprintf('<span class="status %s">%s</span>', $paid_status, strtoupper($paid_status));
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

        if ($status == 'sticky') {
            echo sprintf('<span><a href="%s">%s</a></span>',
                         add_query_arg(array('wpbdmaction' => 'cancelfeatured', 'post' => $post->ID)),
                         '<b>↓</b> ' . __('Downgrade to Normal', 'WPBDM'));
        } else {
            echo sprintf('<span><a href="%s">%s</a></span>',
                         add_query_arg(array('wpbdmaction' => 'upgradefeatured', 'post' => $post->ID)),
                         '<b>↑</b> ' . __('Upgrade to Featured', 'WPBDM'));
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

}

}