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
        add_menu_page(__("Business Directory Plugin","WPBDM"),
                      'WPBusDirMan',
                      'activate_plugins',
                      'wpbusdirman.php',
                      'wpbusdirman_home_screen',
                      WPBUSDIRMANMENUICO);
        add_submenu_page('wpbusdirman.php',
                         __('Manage Options'),
                         __('Manage Options'),
                         'activate_plugins',
                         'wpbdp_settings',
                         array($this, 'admin_settings'));
        add_submenu_page('wpbusdirman.php',
                         'Manage Options ',
                         'Manage Options',
                         'activate_plugins',
                         'wpbdman_c1',
                         'wpbusdirman_config_admin');
        add_submenu_page('wpbusdirman.php',
                         'Manage Fees',
                         'Manage Fees',
                         'activate_plugins', 
                         'wpbdman_c2',
                         'wpbusdirman_opsconfig_fees');
        add_submenu_page('wpbusdirman.php',
                         'Manage Fields',
                         'Manage Form Fields',
                         'activate_plugins',
                         'wpbdman_c3',
                         'wpbusdirman_buildform');
        // add_submenu_page('wpbusdirman.php',
        //                  'Manage Featured',
        //                  'Manage Featured',
        //                  'activate_plugins',
        //                  'wpbdman_c4',
        //                  'wpbusdirman_featured_pending');
        // add_submenu_page('wpbusdirman.php',
        //                  'Manage Payments',
        //                  'Manage Payments',
        //                  'activate_plugins',
        //                  'wpbdman_c5',
        //                  'wpbusdirman_manage_paid');
        add_submenu_page('wpbusdirman.php',
                         'Uninstall WPDB Manager',
                         'Uninstall',
                         'activate_plugins',
                         'wpbdman_m1',
                         'wpbusdirman_uninstall');
    }

    function add_listing_metabox() {
        add_meta_box('BusinessDirectory_listinginfo',
                     __('Listing Information', 'WPBDM'),
                     array($this, 'listing_metabox'),
                     WPBDP_Plugin::POST_TYPE,
                     'side',
                     'default'
                    );
    }

    function listing_metabox($post) {
        global $wpbusdirman_haspaypalmodule;

        // Payment information
        if ($payment_status = get_post_meta($post->ID, 'paymentstatus', true)) {
            echo '<div class="misc-pub-section">';
            echo '<strong>' . __('Payment Information', 'WPBDM') . '</strong>';
            echo '<dl>';
                echo '<dt>'. __('Status', 'WPBDM') . '</dt>';
                echo '<dd>' . $payment_status . '</dd>';

                echo '<dt>'. __('Gateway', 'WPBDM') . '</dt>';
                echo '<dd>' . get_post_meta($post->ID, 'paymentgateway', true) . '</dd>';

                if ($wpbusdirman_haspaypalmodule) {
                    echo '<dt>'. __('Flag', 'WPBDM') . '</dt>';
                    echo '<dd>' . (get_post_meta($post->ID, 'paymentflag', true) ? get_post_meta($post->ID, 'paymentflag', true) : '-') . '</dd>';
                }

                echo '<dt>'. __('Buyer', 'WPBDM') . '</dt>';
                $buyer = sprintf('%s %s', get_post_meta($post->ID, 'buyerfirstname', true), get_post_meta($post->ID, 'buyerlastname', true));
                echo '<dd>' . ($buyer != ' ' ? $buyer : '-') . '</dd>';

                echo '<dt>'. __('Payment Email', 'WPBDM') . '</dt>';
                echo '<dd>' . (get_post_meta($post->ID, 'payeremail', true) ? get_post_meta($post->ID, 'payeremail', true) : '-') . '</dd>';

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
        if ($status = get_post_meta($post->ID, 'sticky', true)) {
            if ($status == 'pending') {
                echo '<strong>' . __('Upgrade to Featured', 'WPBDM') . '</strong>';
                echo '<dl>';
                echo '<dt>' . __('Pending manual upgrade.', 'WPBDM') . '</dt>';
                echo '<dd>';
                echo sprintf('<a href="%s" class="button-primary">%s</a>',
                             add_query_arg('wpbdmaction', 'upgradefeatured'),
                             __('Upgrade', 'WPBDM')
                            );
                echo sprintf('<a href="%s" class="button">%s</a>',
                             add_query_arg('wpbdmaction', 'cancelfeatured'),
                             __('Downgrade', 'WPBDM')
                            );
                echo '</dd>';
                echo '</dl>';
            }
        }

    }

    function apply_query_filters($request) {
        global $current_screen;

        if (is_admin() && isset($_REQUEST['wpbdmfilter']) && $current_screen->id == 'edit-' . WPBDP_Plugin::POST_TYPE) {
            switch ($_REQUEST['wpbdmfilter']) {
                case 'pendingupgrade':
                    $request['meta_key'] = 'sticky';
                    $request['meta_value'] = 'pending';
                    break;
                case 'paid':
                    $request['meta_key'] = 'paymentstatus';
                    $request['meta_value'] = 'paid';
                    break;
                default:
                    $request['meta_key'] = 'paymentstatus';
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
                update_post_meta($post_id, 'paymentstatus', 'paid');

                $this->messages[] = __("The listing status has been set as paid.","WPBDM");
                break;
            
            case 'setasnotpaid':
                delete_post_meta($post_id, "paymentstatus", "pending");
                delete_post_meta($post_id, "paymentstatus", "refunded");
                delete_post_meta($post_id, "paymentstatus", "unknown");
                delete_post_meta($post_id, "paymentstatus", "cancelled");

                $this->messages[] = __("The listing status has been changed non-paying.","WPBDM");
                break;

            case 'upgradefeatured':
                update_post_meta($post_id, "sticky", "approved");
            
                $this->messages[] = __("The listing has been upgraded.","WPBDM");
                break;

            case 'cancelfeatured':
                delete_post_meta($post_id, "sticky", "pending");
                
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
                                                           'paymentstatus',
                                                           'paid') );
        $unpaid = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND ( (pm.meta_key = %s AND NOT pm.meta_value = %s) ) GROUP BY p.ID",WPBDP_Plugin::POST_TYPE,
                                                           'paymentstatus',
                                                           'paid') );
        $pending_upgrade = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND ( (pm.meta_key = %s AND pm.meta_value = %s) )",
                                                           WPBDP_Plugin::POST_TYPE,
                                                           'sticky',
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

    function add_custom_columns($columns) {
        $columns['bd_payment_status'] = __('Payment Status', 'WPBDM');
        $columns['bd_sticky_status'] = __('Sticky Status', 'WPBDM');
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

        if ($paid_status = get_post_meta($post->ID, 'paymentstatus', true)) {
            $buyer = sprintf('%s %s', get_post_meta($post->ID, 'buyerfirstname', true), get_post_meta($post->ID, 'buyerlastname', true));
            $email = get_post_meta($post->ID, 'payeremail', true);
            $flag = get_post_meta($post->ID, 'paymentflag', true);
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
                          __('Gateway', 'WPBDM'), get_post_meta($post->ID, 'paymentgateway', true),
                          __('Flag', 'WPBDM'), $flag ? $flag : 'None',
                          __('Buyer', 'WPBDM'), $buyer != ' ' ? $buyer : '--',
                          __('Payment Email', 'WPBDM'), $email ? '(' . $email . ')' : '',
                          __('Set as', 'WPBDM'),
                          $status_links);
            } else {
                echo sprintf('<span class="status %s">%s</span><div class="paymentdata"><b>%s</b>: <span class="gateway">%s</span> | <b>%s:</b> <span class="buyer">%s</span> <span class="email" title="%s">%s</span></div><div class="row-actions"><b>%s:</b> %s</div>',
                              $paid_status,
                              strtoupper($paid_status),
                              __('Gateway', 'WPBDM'), get_post_meta($post->ID, 'paymentgateway', true),
                              __('Buyer', 'WPBDM'), $buyer != ' ' ? $buyer : '--',
                              __('Payment Email', 'WPBDM'), $email ? '(' . $email . ')' : '',
                              __('Set as', 'WPBDM'),
                              $status_links);
            }
        
        } else {
            echo '(' . __('Non-paying', 'WPBDM') . ')';
        }
    }

    private function sticky_status_column() {
        global $post;

        if ($status = get_post_meta($post->ID, 'sticky', true)) {
            if ($status == 'pending') {
                echo sprintf('<b>!</b> %s<br /><div class="row-actions">
                                <span><a href="%s">%s</a></span> |
                                <span><a href="%s">%s</a></span>
                            </div>',
                             __('Pending Upgrade', 'WPBDM'),
                             add_query_arg(array('wpbdmaction' => 'upgradefeatured', 'post' => $post->ID)),
                             __('Upgrade', 'WPBDM'),
                             add_query_arg(array('wpbdmaction' => 'cancelfeatured', 'post' => $post->ID)),
                             __('Downgrade', 'WPBDM')
                            );
            } elseif ($status == 'approved') {
                echo __('Approved', 'WPBDM');
            }
        }
    }

    /* Settings page */
    public function register_settings() {
        global $wpbdp;
        $wpbdp->settings->register_in_admin();
    }

    public function admin_settings() {
        global $wpbdp;
        wpbdp_render_page(WPBDP_PATH . 'admin/templates/settings.tpl.php',
                          array('wpbdp_settings' => $wpbdp->settings),
                          true);
    }


}

}