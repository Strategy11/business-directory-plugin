<?php
require_once(WPBDP_PATH . 'admin/admin-pages.php');
require_once(WPBDP_PATH . 'admin/fees.php');
require_once(WPBDP_PATH . 'admin/form-manager.php');
require_once(WPBDP_PATH . 'admin/uninstall.php');

if (!class_exists('WPBDP_Admin')) {

class WPBDP_Admin {

    private $messages = array();

    function __construct() {

        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'add_listing_metabox'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'admin_styles'));

        add_filter(sprintf('manage_edit-%s_columns', WPBDP_Plugin::POST_TYPE),
                   array($this, 'add_custom_columns'));
        add_action(sprintf('manage_posts_custom_column'), array($this, 'custom_columns'));
        add_filter('views_edit-' . WPBDP_Plugin::POST_TYPE, array($this, 'add_custom_views'));
        add_filter('request', array($this, 'apply_query_filters'));
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
                         '_wpbdp_admin_add_listing');
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
                         'wpbusdirman_opsconfig_fees');
        add_submenu_page('wpbusdirman.php',
                         _x('Manage Fields', 'admin menu', 'WPBDM'),
                         _x('Manage Form Fields', 'admin menu', 'WPBDM'),
                         'activate_plugins',
                         'wpbdman_c3',
                         'wpbusdirman_buildform');
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
        global $submenu;
        $submenu['wpbusdirman.php'][0][0] = _x('Main Menu', 'admin menu', 'WPBDM');
        $submenu['wpbusdirman.php'][1][2] = admin_url('admin.php?page=wpbdman_c3&action=addnewlisting');
        $submenu['wpbusdirman.php'][5][2] = admin_url(sprintf('edit.php?post_type=%s&wpbdmfilter=%s', wpbdp()->get_post_type(), 'pendingupgrade'));
        $submenu['wpbusdirman.php'][6][2] = admin_url(sprintf('edit.php?post_type=%s&wpbdmfilter=%s', wpbdp()->get_post_type(), 'unpaid'));
    }

    function add_listing_metabox() {
        add_meta_box('BusinessDirectory_listinginfo',
                     __('Listing Information', 'WPBDM'),
                     array($this, 'listing_metabox'),
                     WPBDP_Plugin::POST_TYPE,
                     'side',
                     'core'
                    );
    }

    function listing_metabox($post) {
        global $wpbusdirman_haspaypalmodule;

        // Payment information
        if ($payment_status = get_post_meta($post->ID, '_wpbdp_paymentstatus', true)) {
            echo '<div class="misc-pub-section">';
            echo '<strong>' . __('Payment Information', 'WPBDM') . '</strong>';
            echo '<dl>';
                echo '<dt>'. __('Status', 'WPBDM') . '</dt>';
                echo '<dd>' . $payment_status . '</dd>';

                echo '<dt>'. __('Gateway', 'WPBDM') . '</dt>';
                echo '<dd>' . get_post_meta($post->ID, '_wpbdp_paymentgateway', true) . '</dd>';

                if ($wpbusdirman_haspaypalmodule) {
                    echo '<dt>'. __('Flag', 'WPBDM') . '</dt>';
                    echo '<dd>' . (get_post_meta($post->ID, '_wpbdp_paymentflag', true) ? get_post_meta($post->ID, '_wpbdp_paymentflag', true) : '-') . '</dd>';
                }

                echo '<dt>'. __('Buyer', 'WPBDM') . '</dt>';
                $buyer = sprintf('%s %s', get_post_meta($post->ID, '_wpbdp_buyerfirstname', true), get_post_meta($post->ID, '_wpbdp_buyerlastname', true));
                echo '<dd>' . ($buyer != ' ' ? $buyer : '-') . '</dd>';

                echo '<dt>'. __('Payment Email', 'WPBDM') . '</dt>';
                echo '<dd>' . (get_post_meta($post->ID, '_wpbdp_payeremail', true) ? get_post_meta($post->ID, '_wpbdp_payeremail', true) : '-') . '</dd>';

                $status_links = '';

                if ($payment_status != 'paid')
                    echo sprintf('<a href="%s" class="button-primary">%s</a> ',
                             add_query_arg('wpbdmaction', 'setaspaid'),
                             __('Set as Paid', 'WPBDM'));
                echo sprintf('<a href="%s" class="button">%s</a>',
                             add_query_arg('wpbdmaction', 'setasnotpaid'),
                             __('Set as Not paid', 'WPBDM'));

            echo '</dl>';
            echo '</div>';
        }

        // Sticky information
        if ($status = get_post_meta($post->ID, '_wpbdp_sticky', true)) {
            $status_string = '';

            if ($status == 'not paid') $status_string = __('Not Paid', 'WPBDM');
            if ($status == 'pending') $status_string = __('Pending Upgrade', 'WPBDM');
            if ($status == 'approved') $status_string = __('Featured', 'WPBDM');
        } else {
            $status = 'notsticky';
            $status_string = _x('Normal', 'admin list', 'WPBDM');
        }

        echo '<div class="misc-pub-section">';
        echo '<label>' .  _x('Sticky Status', 'admin metabox', 'WPBDM') . ': </label>';
        echo '<span><b>' . $status_string . '</b> </span>';
        
        if ($status == 'approved') {
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

    }

    function apply_query_filters($request) {
        global $current_screen;

        if (is_admin() && isset($_REQUEST['wpbdmfilter']) && $current_screen->id == 'edit-' . WPBDP_Plugin::POST_TYPE) {
            switch ($_REQUEST['wpbdmfilter']) {
                case 'pendingupgrade':
                    $request['meta_key'] = '_wpbdp_sticky';
                    $request['meta_value'] = 'pending';
                    break;
                case 'paid':
                    $request['meta_key'] = '_wpbdp_paymentstatus';
                    $request['meta_value'] = 'paid';
                    break;
                default:
                    $request['meta_key'] = '_wpbdp_paymentstatus';
                    $request['meta_value'] = 'paid';
                    $request['meta_compare'] = '!=';
                    break;
            }

        }

        return $request;
    }

    function admin_notices() {
        foreach ($this->messages as $msg) {
            echo sprintf('<div class="updated">%s</div>', $msg);
        }
    }

    function handle_actions() {
        if (!isset($_REQUEST['wpbdmaction']) || !isset($_REQUEST['post']))
            return;

        $action = $_REQUEST['wpbdmaction'];
        $post_id = intval($_REQUEST['post']);

        switch ($action) {
            case 'setaspaid':
                update_post_meta($post_id, '_wpbdp_paymentstatus', 'paid');

                $this->messages[] = __("The listing status has been set as paid.","WPBDM");
                break;
            
            case 'setasnotpaid':
                delete_post_meta($post_id, "_wpbdp_paymentstatus", "pending");
                delete_post_meta($post_id, "_wpbdp_paymentstatus", "refunded");
                delete_post_meta($post_id, "_wpbdp_paymentstatus", "unknown");
                delete_post_meta($post_id, "_wpbdp_paymentstatus", "cancelled");

                $this->messages[] = __("The listing status has been changed unpaid.","WPBDM");
                break;

            case 'upgradefeatured':
                update_post_meta($post_id, "_wpbdp_sticky", "approved");
            
                $this->messages[] = __("The listing has been upgraded.","WPBDM");
                break;

            case 'cancelfeatured':
                delete_post_meta($post_id, "_wpbdp_sticky", "pending");
                
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
                                                           '_wpbdp_paymentstatus',
                                                           'paid') );
        $unpaid = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND ( (pm.meta_key = %s AND NOT pm.meta_value = %s) ) GROUP BY p.ID",WPBDP_Plugin::POST_TYPE,
                                                           '_wpbdp_paymentstatus',
                                                           'paid') );
        $pending_upgrade = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND ( (pm.meta_key = %s AND pm.meta_value = %s) )",
                                                           WPBDP_Plugin::POST_TYPE,
                                                           '_wpbdp_sticky',
                                                           'pending') );

        $views['paid'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                                 add_query_arg('wpbdmfilter', 'paid'),
                                 $_REQUEST['wpbdmfilter'] == 'paid' ? 'current' : '',
                                 __('Paid', 'WPBDM'),
                                 number_format_i18n($paid));
        $views['unpaid'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                                   add_query_arg('wpbdmfilter', 'unpaid'),
                                   $_REQUEST['wpbdmfilter'] == 'unpaid' ? 'current' : '',
                                   __('Unpaid', 'WPBDM'),
                                   number_format_i18n($unpaid));
        $views['featured'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                                   add_query_arg('wpbdmfilter', 'pendingupgrade'),
                                   $_REQUEST['wpbdmfilter'] == 'pendingupgrade' ? 'current' : '',
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
        global $wpbusdirman_haspaypalmodule;

        if ($paid_status = get_post_meta($post->ID, '_wpbdp_paymentstatus', true)) {
            $buyer = sprintf('%s %s', get_post_meta($post->ID, '_wpbdp_buyerfirstname', true), get_post_meta($post->ID, '_wpbdp_buyerlastname', true));
            $email = get_post_meta($post->ID, '_wpbdp_payeremail', true);
            $flag = get_post_meta($post->ID, '_wpbdp_paymentflag', true);
            $status_links = '';

            if ($paid_status != 'paid')
                $status_links .= sprintf('<span><a href="%s">%s</a> | </span>',
                                        add_query_arg(array('wpbdmaction' => 'setaspaid', 'post' => $post->ID)),
                                        __('Paid', 'WPBDM'));
            $status_links .= sprintf('<span><a href="%s">%s</a></span>',
                                      add_query_arg(array('wpbdmaction' => 'setasnotpaid', 'post' => $post->ID)),
                                      __('Not paid', 'WPBDM'));

            if ($wpbusdirman_haspaypalmodule) {
                echo sprintf('<span class="status %s">%s</span><div class="paymentdata"><b>%s</b>: <span class="gateway">%s</span> | <b>%s:</b> <span class="flag">%s</span> | <b>%s:</b> <span class="buyer">%s</span> <span class="email" title="%s">%s</span></div>
                    <div class="row-actions"><b>%s:</b> %s</div>',
                          $paid_status,
                          strtoupper($paid_status),
                          __('Gateway', 'WPBDM'), get_post_meta($post->ID, '_wpbdp_paymentgateway', true),
                          __('Flag', 'WPBDM'), $flag ? $flag : 'None',
                          __('Buyer', 'WPBDM'), $buyer != ' ' ? $buyer : '--',
                          __('Payment Email', 'WPBDM'), $email ? '(' . $email . ')' : '',
                          __('Set as', 'WPBDM'),
                          $status_links);
            } else {
                echo sprintf('<span class="status %s">%s</span><div class="paymentdata"><b>%s</b>: <span class="gateway">%s</span> | <b>%s:</b> <span class="buyer">%s</span> <span class="email" title="%s">%s</span></div><div class="row-actions"><b>%s:</b> %s</div>',
                              $paid_status,
                              strtoupper($paid_status),
                              __('Gateway', 'WPBDM'), get_post_meta($post->ID, '_wpbdp_paymentgateway', true),
                              __('Buyer', 'WPBDM'), $buyer != ' ' ? $buyer : '--',
                              __('Payment Email', 'WPBDM'), $email ? '(' . $email . ')' : '',
                              __('Set as', 'WPBDM'),
                              $status_links);
            }
        
        } else {
            echo '(' . __('Unpaid', 'WPBDM') . ')';
        }
    }

    private function sticky_status_column() {
        global $post;

        if ($status = get_post_meta($post->ID, '_wpbdp_sticky', true)) {
            $status_string = '';

            if ($status == 'not paid') $status_string = __('Not Paid', 'WPBDM');
            if ($status == 'pending') $status_string = __('Pending Upgrade', 'WPBDM');
            if ($status == 'approved') $status_string = __('Featured', 'WPBDM');
        } else {
            $status = 'notsticky';
            $status_string = _x('Normal', 'admin list', 'WPBDM');
        }
        
        echo sprintf('<span class="status %s">%s</span><br />',
                    str_replace(' ', '', $status),
                    $status_string);

        echo '<div class="row-actions">';

        if ($status == 'approved') {
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