<?php
/*
 * Plugin Name: Business Directory Plugin
 * Plugin URI: http://www.businessdirectoryplugin.com
 * Description: Provides the ability to maintain a free or paid business directory on your WordPress powered site.
 * Version: 3.6.4
 * Author: D. Rodenbaugh
 * Author URI: http://businessdirectoryplugin.com
 * License: GPLv2 or any later version
 */

/*  Copyright 2009-2015, Skyline Consulting and D. Rodenbaugh

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2 or later, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    reCAPTCHA used with permission of Mike Crawford & Ben Maurer, http://recaptcha.net
*/

// Do not allow direct loading of this file.
if( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) )
    exit();

define( 'WPBDP_VERSION', '3.6.4' );

define( 'WPBDP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPBDP_URL', trailingslashit( plugins_url( '/', __FILE__ ) ) );
define( 'WPBDP_TEMPLATES_PATH', WPBDP_PATH . 'templates' );

define( 'WPBDP_POST_TYPE', 'wpbdp_listing' );
define( 'WPBDP_CATEGORY_TAX', 'wpbdp_category' );
define( 'WPBDP_TAGS_TAX', 'wpbdp_tag' );

require_once( WPBDP_PATH . 'core/api.php' );
require_once( WPBDP_PATH . 'core/compatibility/class-compat.php' );
require_once( WPBDP_PATH . 'core/utils.php' );
require_once( WPBDP_PATH . 'admin/tracking.php' );
require_once( WPBDP_PATH . 'admin/class-admin.php' );
require_once( WPBDP_PATH . 'core/class-settings.php' );
require_once( WPBDP_PATH . 'core/form-fields.php' );
require_once( WPBDP_PATH . 'core/payment.php' );
include_once( WPBDP_PATH . 'core/gateways-googlewallet.php' );
require_once( WPBDP_PATH . 'core/listings.php' );
require_once( WPBDP_PATH . 'core/templates-generic.php' );
require_once( WPBDP_PATH . 'core/templates-listings.php' );
require_once( WPBDP_PATH . 'core/templates-ui.php' );
require_once( WPBDP_PATH . 'core/installer.php' );
require_once( WPBDP_PATH . 'core/views.php' );
require_once( WPBDP_PATH . 'core/licensing.php' );
require_once( WPBDP_PATH . 'core/seo.php' );


global $wpbdp;


/**
 * The main plugin class.
 */
class WPBDP_Plugin {

    var $_query_stack = array();


    public function __construct() {
        register_activation_hook( __FILE__, array( &$this, 'plugin_activation' ) );
        register_deactivation_hook( __FILE__, array( &$this, 'plugin_deactivation' ) );

        // Enable debugging if needed.
        if ( defined( 'WPBDP_DEBUG' ) && true == WPBDP_DEBUG )
            $this->debug_on();

        // Load dummy objects in case plugins try to do something at an early stage.
        $noop = new WPBDP_NoopObject();
        $this->settings = $noop;
        $this->controller = $noop;
        $this->formfields = $noop;
        $this->admin = $noop;
        $this->fees = $noop;
        $this->payments = $noop;
        $this->listings = $noop;

        $this->licensing = new WPBDP_Licensing();

        add_action( 'plugins_loaded', array( &$this, 'load_i18n' ) );

        if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
            add_action( 'init', array( &$this, 'init' ), 9 );
        } else {
            add_action( 'init', array( &$this, 'init' ) );
        }

        add_action( 'widgets_init', array( &$this, '_register_widgets' ) );

        // For testing the expiration routine only.
        // add_action('init', create_function('', 'do_action("wpbdp_listings_expiration_check");'), 20);
    }

    function load_i18n() {
        $plugin_dir = basename( dirname( __FILE__ ) );
        $languages_dir = trailingslashit( $plugin_dir . '/languages' );
        load_plugin_textdomain( 'WPBDM', false, $languages_dir );
    }

    function init() {
        $this->compat = new WPBDP_Compat();

        // Register cache groups.
        wp_cache_add_non_persistent_groups( array( 'wpbdp pages', 'wpbdp formfields', 'wpbdp submit state', 'wpbdp' ) );

        // Register some basic JS resources.
        add_action( 'wp_enqueue_scripts', array( &$this, 'register_common_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( &$this, 'register_common_scripts' ) );

        // Initialize settings API.
        $this->settings = new WPBDP_Settings();
        $this->formfields = WPBDP_FormFields::instance();

        // Install plugin.
        $this->settings->register_settings();
        $this->_register_post_type();
        $this->install_or_update_plugin();

        if ( $manual_upgrade = get_option( 'wpbdp-manual-upgrade-pending', false ) ) {
            $installer = new WPBDP_Installer();
            $installer->setup_manual_upgrade();
            return;
        }

        // Display "Settings" link on Plugins page.
        $plugin_filename = plugin_basename( __FILE__ );
        add_filter( 'plugin_action_links_' . $plugin_filename, array( &$this, 'plugin_action_links' ) );

        // Initialize APIs.
        $this->admin = is_admin() ? new WPBDP_Admin() : null;
        $this->controller = new WPBDP_DirectoryController();
        $this->fees = new WPBDP_FeesAPI();
        $this->payments = new WPBDP_PaymentsAPI();
        $this->listings = new WPBDP_Listings_API();

        $this->_register_image_sizes();
        $this->handle_recaptcha();

        add_filter('posts_request', create_function('$x', 'wpbdp_debug($x); return $x;')); // used for debugging
        add_filter('rewrite_rules_array', array( &$this, '_rewrite_rules'));
        add_filter('query_vars', array( &$this, '_query_vars'));
        add_filter( 'redirect_canonical', array( &$this, '_redirect_canonical' ), 10, 2 );
        add_action('template_redirect', array( &$this, '_template_redirect'));
        add_action('wp_loaded', array( &$this, '_wp_loaded'));

        add_action('pre_get_posts', array( &$this, '_pre_get_posts'));
        add_filter( 'posts_clauses', array( &$this, '_posts_clauses' ), 10 );
        add_filter( 'posts_fields', array( &$this, '_posts_fields'), 10, 2);
        add_filter( 'posts_orderby', array( &$this, '_posts_orderby'), 10, 2);

        add_filter('comments_template', array( &$this, '_comments_template'));
        add_filter('taxonomy_template', array( &$this, '_category_template'));
        add_filter('single_template', array( &$this, '_single_template'));

        add_action( 'wp', array( &$this, '_meta_setup' ) );
        add_action( 'wp', array( &$this, '_jetpack_compat' ), 11, 1 );
        add_action( 'wp_head', array( &$this, '_handle_broken_plugin_filters' ), 0 );

        add_filter( 'wp_title', array( &$this, '_meta_title' ), 10, 3 );

        add_action( 'wp_head', array( &$this, '_rss_feed' ) );

        // Register shortcodes.
        $shortcodes = $this->get_shortcodes();

        foreach ( $shortcodes as $shortcode => &$handler )
            add_shortcode( $shortcode, $handler );

        do_action( 'wpbdp_loaded' );

        // Expiration hook.
        add_action( 'wpbdp_listings_expiration_check', array( &$this, '_notify_expiring_listings' ), 0 );

        // Scripts & styles.
        add_action('wp_enqueue_scripts', array($this, '_enqueue_scripts'));
        add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_css_override' ), 30, 0 );

        // Plugin modules initialization.
        $this->_init_modules();

        // AJAX actions.
        add_action( 'wp_ajax_wpbdp-file-field-upload', array( &$this, 'ajax_file_field_upload' ) );
        add_action( 'wp_ajax_nopriv_wpbdp-file-field-upload', array( &$this, 'ajax_file_field_upload' ) );
        add_action( 'wp_ajax_wpbdp-listing-submit-image-upload', array( &$this, 'ajax_listing_submit_image_upload' ) );
        add_action( 'wp_ajax_nopriv_wpbdp-listing-submit-image-upload', array( &$this, 'ajax_listing_submit_image_upload' ) );
        add_action( 'wp_ajax_wpbdp-listing-submit-image-delete', array( &$this, 'ajax_listing_submit_image_delete' ) );
        add_action( 'wp_ajax_nopriv_wpbdp-listing-submit-image-delete', array( &$this, 'ajax_listing_submit_image_delete' ) );

        // Core sorting options.
        add_filter( 'wpbdp_listing_sort_options', array( &$this, 'sortbar_sort_options' ) );
        add_filter( 'wpbdp_query_fields', array( &$this, 'sortbar_query_fields' ) );
        add_filter( 'wpbdp_query_orderby', array( &$this, 'sortbar_orderby' ) );
    }

    // {{{ Premium modules.

    /**
     * Return information about known premium modules.
     * @return array An array in the form $module_id => $module_information where $module_information contains the keys
     *               'installed' (True or False),
     *               'version' (if installed, NULL otherwise),
     *               'required' (required module version as known to current core version).
     * @since 3.4
     */
    public function get_premium_modules_data() {
        static $modules = array(
            '2checkout' => array( 'WPBDP_2Checkout_Module', '3.4' ),
            'attachments' => array( 'WPBDP_ListingAttachmentsModule', '3.4' ),
            'categories' => array( 'WPBDP_CategoriesModule', '3.4' ),
            'featured-levels' => array( 'WPBDP_FeaturedLevelsModule', '3.4' ),
            'googlemaps' => array( 'BusinessDirectory_GoogleMapsPlugin', '3.4' ),
            'payfast' => array( 'WPBDP_Gateways_PayFast', '3.4' ),
            'paypal' => array( 'WPBDP_PayPal_Module', '3.4' ),
            'ratings' => array( 'BusinessDirectory_RatingsModule', '3.4' ),
            'regions' => array( 'WPBDP_RegionsPlugin', '3.4' ),
            'stripe' => array( 'WPBDP_Stripe_Module', '1.0' ),
            'zipcodesearch' => array( 'WPBDP_ZIPCodeSearchModule', '3.4' )
        );

        static $data = null;

        if ( null !== $data )
            return $data;

        $data = array();

        foreach ( $modules as $module_id => $module_ ) {
            $module_class = $module_[0];
            $data[ $module_id ] = array( 'installed' => false,
                                         'version' => null,
                                         'required' => $module_[1] );

            if ( class_exists( $module_class ) ) {
                $data[ $module_id ]['installed'] = true;

                if ( defined( $module_class . '::VERSION' ) ) {
                    $data[ $module_id ]['version'] = constant( $module_class . '::VERSION' );
                }
            }
        }

        return $data;
    }

    // }}}

    public function _pre_get_posts(&$query) {
        global $wpdb;

        if (!$query->is_admin && $query->is_archive && $query->get(WPBDP_CATEGORY_TAX)) {
            // category page query
            $query->set('post_status', 'publish');
            $query->set('post_type', WPBDP_POST_TYPE);
            $query->set('posts_per_page', wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1);
            $query->set('orderby', wpbdp_get_option('listings-order-by', 'date'));
            $query->set('order', wpbdp_get_option('listings-sort', 'ASC'));
        }
    }

    function _posts_clauses( $pieces ) {
        global $wp_query;

        if ( is_admin() || ! isset( $wp_query->query_vars['post_type'] ) || WPBDP_POST_TYPE != $wp_query->query_vars['post_type'] )
            return $pieces;

        return apply_filters( 'wpbdp_query_clauses', $pieces );
    }

    public function _posts_fields($fields, $query) {
        global $wpdb;

        if (!is_admin() && isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == WPBDP_POST_TYPE) {
            $is_sticky_query = $wpdb->prepare("(SELECT 1 FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->postmeta}.meta_key = %s AND {$wpdb->postmeta}.meta_value = %s LIMIT 1 ) AS wpbdp_is_sticky",
                                               '_wpbdp[sticky]', 'sticky');

            if ( 'paid' == wpbdp_get_option( 'listings-order-by' ) ) {
                $is_paid_query = "(SELECT 1 FROM {$wpdb->prefix}wpbdp_payments pp WHERE pp.listing_id = {$wpdb->posts}.ID AND pp.amount > 0 LIMIT 1 ) AS wpbdp_is_paid";
                $fields = $fields . ', ' . $is_sticky_query . ', ' . $is_paid_query;
            } else {
                $fields = $fields . ', ' . $is_sticky_query;
            }

            $fields = apply_filters('wpbdp_query_fields', $fields);
        }

        return $fields;
    }

    public function _posts_orderby($orderby, $query) {
        if (!is_admin() && isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == WPBDP_POST_TYPE) {
            $wpbdp_orderby = apply_filters('wpbdp_query_orderby', '');

            if ( 'paid' == wpbdp_get_option( 'listings-order-by' ) ) {
                $orderby = 'wpbdp_is_sticky DESC, wpbdp_is_paid DESC' . $wpbdp_orderby . ', ' . $orderby;
            } else {
                $orderby = 'wpbdp_is_sticky DESC' . $wpbdp_orderby . ', ' . $orderby;
            }

            $orderby = apply_filters( 'wpbdp_query_full_orderby', $orderby );
        }

        return $orderby;
    }

    private function get_rewrite_rules() {
        global $wpdb;
        global $wp_rewrite;

        $rules = array();

        if ( $page_ids = wpbdp_get_page_id( 'main', false ) ) {
            foreach ( $page_ids as $page_id ) {
                $page_link = _get_page_link( $page_id );
                $rewrite_base = str_replace('index.php/', '', rtrim(str_replace(home_url() . '/', '', $page_link), '/'));

                $rules['(' . $rewrite_base . ')/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$'] = 'index.php?page_id=' . $page_id . '&paged=$matches[2]';
                $rules['(' . $rewrite_base . ')/' . wpbdp_get_option('permalinks-category-slug') . '/(.+?)/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$'] = 'index.php?page_id=' . $page_id . '&category=$matches[2]&paged=$matches[3]';
                $rules['(' . $rewrite_base . ')/' . wpbdp_get_option('permalinks-category-slug') . '/(.+?)/?$'] = 'index.php?page_id=' . $page_id . '&category=$matches[2]';
                $rules['(' . $rewrite_base . ')/' . wpbdp_get_option('permalinks-tags-slug') . '/(.+?)/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$'] = 'index.php?page_id=' . $page_id . '&tag=$matches[2]&paged=$matches[3]';
                $rules['(' . $rewrite_base . ')/' . wpbdp_get_option('permalinks-tags-slug') . '/(.+?)$'] = 'index.php?page_id=' . $page_id . '&tag=$matches[2]';

                if ( wpbdp_get_option( 'permalinks-no-id' ) ) {
                    //$rules['(' . $rewrite_base . ')/([0-9]{1,})/?$'] = 'index.php?page_id=' . $page_id . '&id=$matches[2]';
                    $rules['(' . $rewrite_base . ')/(.*)/?$'] = 'index.php?page_id=' . $page_id . '&listing=$matches[2]';
                } else {
                    $rules['(' . $rewrite_base . ')/([0-9]{1,})/?(.*)/?$'] = 'index.php?page_id=' . $page_id . '&id=$matches[2]';
                }
            }
        }

        return apply_filters( 'wpbdp_rewrite_rules', $rules );
    }

    public function _rewrite_rules($rules) {
        $newrules = $this->get_rewrite_rules();
        return $newrules + $rules;
    }

    public function _wp_loaded() {
        if ($rules = get_option( 'rewrite_rules' )) {
            foreach ($this->get_rewrite_rules() as $k => $v) {
                if (!isset($rules[$k]) || $rules[$k] != $v) {
                    global $wp_rewrite;
                    $wp_rewrite->flush_rules();
                    return;
                }
            }
        }
    }

    public function _query_vars($vars) {
        array_push($vars, 'id');
        array_push($vars, 'listing');
        array_push($vars, 'category_id'); // TODO: are we really using this var?
        array_push($vars, 'category');
        array_push($vars, 'action'); // TODO: are we really using this var?
        array_push( $vars, 'wpbdpx' );
        array_push( $vars, 'region' );

        return $vars;
    }

    /**
     * Workaround for issue WP bug #16373.
     * See http://wordpress.stackexchange.com/questions/51530/rewrite-rules-problem-when-rule-includes-homepage-slug.
     */
    public function _redirect_canonical( $redirect_url, $requested_url ) {
        global $wp_query;

        if ( $main_page_id = wpbdp_get_page_id( 'main' ) ) {
            if ( is_page() && !is_feed() && isset( $wp_query->queried_object ) &&
                 get_option( 'show_on_front' ) == 'page' &&
                 get_option( 'page_on_front' ) == $wp_query->queried_object->ID ) {
                return $requested_url;
            }
        }

        return $redirect_url;
    }

    public function _template_redirect() {
        global $wp_query;

        if ( $wp_query->get( 'wpbdpx' ) ) {
            // Handle some special wpbdpx actions.
            $wpbdpx = $wp_query->get( 'wpbdpx' );

            if ( isset( $this->{$wpbdpx} ) && method_exists( $this->{$wpbdpx}, 'process_request' ) ) {
                $this->{$wpbdpx}->process_request();
                exit();
            }
        }

        if ( is_feed() )
            return;

        // handle some deprecated stuff
        if ( is_search() && isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == WPBDP_POST_TYPE ) {
            $url = add_query_arg( array( 'action' => 'search',
                                         'dosrch' => 1,
                                         'q' => wpbdp_getv( $_REQUEST, 's', '' ) ), wpbdp_get_page_link( 'main' ) );
            wp_redirect( $url ); exit;
        }

        if ( (get_query_var('taxonomy') == WPBDP_CATEGORY_TAX) && (_wpbdp_template_mode('category') == 'page') ) {
            wp_redirect( add_query_arg('category', get_query_var('term'), wpbdp_get_page_link('main')) ); // XXX
            exit;
        }

        if ( (get_query_var('taxonomy') == WPBDP_TAGS_TAX) && (_wpbdp_template_mode('category') == 'page') ) {
            wp_redirect( add_query_arg('tag', get_query_var('term'), wpbdp_get_page_link('main')) ); // XXX
            exit;
        }

        if ( is_single() && (get_query_var('post_type') == WPBDP_POST_TYPE) && (_wpbdp_template_mode('single') == 'page') ) {
            $url = wpbdp_get_page_link( 'main' );

            if (get_query_var('name')) {
                wp_redirect( add_query_arg('listing', get_query_var('name'), $url) ); // XXX
            } else {
                wp_redirect( add_query_arg('id', get_query_var('p'), $url) ); // XXX
            }

            exit;
        }

        global $post;
        if ( $post && ($post->ID == wpbdp_get_page_id('main')) && (get_query_var('id') || get_query_var('listing')) ) {
            $id_or_slug = false;

            foreach ( array( 'id', 'preview_id', 'listing' ) as $x ) {
                if ( get_query_var( $x ) ) {
                    $id_or_slug = get_query_var( $x );
                    break;
                }
            }

            $listing = wpbdp_get_post_by_id_or_slug( $id_or_slug, wpbdp_get_option( 'permalinks-no-id' ) ? 'slug' : 'id' );

            if ( ! $listing || ( 'publish' != $listing->post_status && ! current_user_can( 'edit_posts' ) )  ) {
                $this->controller->action = null;
                status_header(404);
                nocache_headers();
                include( get_404_template() );
                exit();
            }
        }

    }

    public function plugin_activation() {
        add_action('init', array($this, 'flush_rules'), 11);
    }

    public function plugin_deactivation() {
        wp_clear_scheduled_hook('wpbdp_listings_expiration_check');
    }

    public function flush_rules() {
        if (function_exists('flush_rewrite_rules'))
            flush_rewrite_rules(false);
    }

    // TODO: better validation.
    public function ajax_listing_submit_image_upload() {
        $res = new WPBDP_Ajax_Response();

        $listing_id = 0;
        $state_id = 0;
        $state = null;

        if ( isset( $_REQUEST['state_id'] ) ) {
            require_once( WPBDP_PATH . 'core/view-submit-listing.php' );

            $state_id = trim( $_REQUEST['state_id'] );
            $state = WPBDP_Listing_Submit_State::get( $state_id );

            if ( ! $state )
                $res->send_error();
        } else {
            $listing_id = intval( $_REQUEST['listing_id'] );

            if ( ! $listing_id )
                $res->send_error();
        }

        $content_range = null;
        $size = null;

        if ( isset( $_SERVER['HTTP_CONTENT_RANGE'] ) ) {
            $content_range = preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']);
            $size =  $content_range ? $content_range[3] : null;
        }

        $attachments = array();
        $files = wpbdp_flatten_files_array( isset( $_FILES['images'] ) ? $_FILES['images'] : array() );
        $errors = array();

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
            if ( $state )
                $state->images[] = $attachment_id;

            $html .= wpbdp_render( 'submit-listing/images-single',
                                   array( 'image_id' => $attachment_id,
                                          'state_id' => $state ? $state->id : '' ),
                                   false );
        }

        if ( $listing_id ) {
            $listing = WPBDP_Listing::get( $listing_id );
            $listing->set_images( $attachments, true );
        } elseif ( $state ) {
            $state->save();
        }

        if ( $errors ) {
            $error_msg = '';

            foreach ( $errors as $fname => $error )
                $error_msg .= sprintf( '&#149; %s: %s', $fname, $error ) . '<br />';

            $res->add( 'uploadErrors', $error_msg );
        }

        $res->add( 'attachmentIds', $attachments );
        $res->add( 'html', $html );
        $res->send();
    }

    public function ajax_listing_submit_image_delete() {
        $res = new WPBDP_Ajax_Response();
        $image_id = intval( $_REQUEST['image_id'] );

        if ( ! $image_id )
            $res->send_error();

        $state_id = isset( $_REQUEST['state_id'] ) ? $_REQUEST['state_id'] : '';

        if ( $state_id ) {
            require_once( WPBDP_PATH . 'core/view-submit-listing.php' );

            if ( ! $state_id )
                $res->send_error();

            $state = WPBDP_Listing_Submit_State::get( $state_id );

            if ( ! $state || ! in_array( $image_id, $state->images ) )
                $res->send_error();

            wpbdp_array_remove_value( $state->images, $image_id );
            $state->save();
        }

        wp_delete_attachment( $image_id, true );

        $res->add( 'imageId', $image_id );
        $res->send();
    }

    private function get_shortcodes() {
        $shortcodes = array();
        $shortcodes += array_fill_keys( array( 'WPBUSDIRMANADDLISTING',
                                               'businessdirectory-submitlisting' ),
                                        array( &$this->controller, 'submit_listing' ) );
        $shortcodes += array_fill_keys( array( 'WPBUSDIRMANMANAGELISTING',
                                               'businessdirectory-managelistings',
                                               'businessdirectory-manage_listings' ),
                                        array( &$this->controller, 'manage_listings' ) );
        $shortcodes += array_fill_keys( array( 'WPBUSDIRMANVIEWLISTINGS',
                                               'WPBUSDIRMANMVIEWLISTINGS',
                                               'businessdirectory-view_listings',
                                               'businessdirectory-viewlistings',
                                               'businessdirectory-listings' ),
                                        array( &$this, '_listings_shortcode' ) );
        $shortcodes += array_fill_keys( array( 'WPBUSDIRMANUI',
                                               'businessdirectory',
                                               'business-directory' ),
                                        array( &$this->controller, 'dispatch' ) );
        $shortcodes += array_fill_keys( array( 'businessdirectory-search',
                                               'businessdirectory_search' ),
                                        array( &$this->controller, 'search' ) );
        $shortcodes['businessdirectory-featuredlistings'] = array( &$this, '_featured_listings_shortcode' );

        return apply_filters( 'wpbdp_shortcodes', $shortcodes );
    }

    public function _init_modules() {
        do_action('wpbdp_modules_loaded');
        do_action_ref_array( 'wpbdp_register_settings', array( &$this->settings ) );
        do_action('wpbdp_register_fields', $this->formfields);
        do_action('wpbdp_modules_init');

        if ( wpbdp_get_option( 'tracking-on', false ) ) {
            $this->site_tracking = new WPBDP_SiteTracking();
        }
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

    public function install_or_update_plugin() {
        $installer = new WPBDP_Installer();
        $installer->install();
    }

    public function plugin_action_links( $links ) {
        $links['settings'] = '<a href="' . admin_url( 'admin.php?page=wpbdp_admin_settings' ) . '">' . _x( 'Settings', 'admin plugins', 'WPBDM' ) . '</a>';
        return $links;
    }

    function _register_post_type() {
        $post_type_slug = $this->settings->get('permalinks-directory-slug', WPBDP_POST_TYPE);
        $category_slug = $this->settings->get('permalinks-category-slug', WPBDP_CATEGORY_TAX);
        $tags_slug = $this->settings->get('permalinks-tags-slug', WPBDP_TAGS_TAX);

        $labels = array(
            'name' => _x('Directory', 'post type general name', 'WPBDM'),
            'singular_name' => _x('Directory', 'post type singular name', 'WPBDM'),
            'add_new' => _x('Add New Listing', 'listing', 'WPBDM'),
            'add_new_item' => _x('Add New Listing', 'post type', 'WPBDM'),
            'edit_item' => __('Edit Listing', 'WPBDM'),
            'new_item' => __('New Listing', 'WPBDM'),
            'view_item' => __('View Listing', 'WPBDM'),
            'search_items' => __('Search Listings', 'WPBDM'),
            'not_found' =>  __('No listings found', 'WPBDM'),
            'not_found_in_trash' => __('No listings found in trash', 'WPBDM'),
            'parent_item_colon' => ''
            );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug'=> $post_type_slug, 'with_front' => false, 'feeds' => true),
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => WPBDP_URL . 'admin/resources/menuico.png',
            'supports' => array('title','editor','author','categories','tags','thumbnail','excerpt','comments','custom-fields','trackbacks')
        );

        register_post_type(WPBDP_POST_TYPE, $args);

        register_taxonomy( WPBDP_CATEGORY_TAX, WPBDP_POST_TYPE,
                           array( 'hierarchical' => true,
                                  'label' => __( 'Directory Categories', 'WPBDM'),
                                  'singular_name' => 'Directory Category',
                                  'show_in_nav_menus' => true,
                                  'query_var' => true,
                                  'rewrite' => array('slug' => $category_slug) ) );
        register_taxonomy(WPBDP_TAGS_TAX, WPBDP_POST_TYPE, array( 'hierarchical' => false, 'label' => 'Directory Tags', 'singular_name' => 'Directory Tag', 'show_in_nav_menus' => true, 'update_count_callback' => '_update_post_term_count', 'query_var' => true, 'rewrite' => array('slug' => $tags_slug) ) );
    }

    public function _register_image_sizes() {
        $thumbnail_width = absint( wpbdp_get_option( 'thumbnail-width' ) );
        $thumbnail_height = absint( wpbdp_get_option( 'thumbnail-height' ) );

        $max_width = absint( wpbdp_get_option('image-max-width') );
        $max_height = absint( wpbdp_get_option('image-max-height') );

        $crop = (bool) wpbdp_get_option( 'thumbnail-crop' );

        // thumbnail size
        add_image_size( 'wpbdp-thumb', $thumbnail_width, $crop ? $thumbnail_height : 9999, $crop );
        add_image_size( 'wpbdp-large', $max_width, $max_height, false );
    }

    public function handle_recaptcha() {
        if ( wpbdp_get_option( 'recaptcha-on' ) ) {
            // Only one reCAPTCHA is allowed per page, so we work around this limitation by sharing the one in the contact form.
            add_action( 'wp_footer', array( &$this, 'comment_recaptcha_workaround' ) );
        }

        // Comments reCAPTCHA.
        if ( wpbdp_get_option( 'recaptcha-for-comments' ) ) {
            add_filter( 'comment_form', array( &$this, 'recaptcha_in_comments' ) );
            add_action( 'preprocess_comment', array( &$this, 'check_comment_recaptcha' ), 0 );

            // add_action('wp_head', array(&$this, 'saved_comment'), 0);
            add_action( 'comment_post_redirect', array( &$this, 'comment_relative_redirect' ), 0, 2 );
        }
    }

    public function is_debug_on() {
        return WPBDP_Debugging::is_debug_on();
    }

    public function debug_on() {
        global $wpdb;

        // Set MySQL strict mode.
        //$wpdb->show_errors();
        //$wpdb->query( "SET @@sql_mode = 'TRADITIONAL'" );

        // Enable BD debugging.
        WPBDP_Debugging::debug_on();
    }

    public function debug_off() {
        WPBDP_Debugging::debug_off();
    }

    public function has_module($name) {
        switch (strtolower($name)) {
            default:
                break;
            case 'payfast':
            case 'payfast-payment-module':
                return class_exists( 'WPBDP_Gateways_PayFast' );
                break;
            case 'paypal':
            case 'paypal-gateway-module':
                return class_exists( 'WPBDP_Paypal_Module' );
                break;
            case '2checkout':
            case 'twocheckout':
            case '2checkout-gateway-module':
                return class_exists( 'WPBDP_2Checkout_Module' );
                break;
            case 'googlecheckout':
                return wpbdp_payments_api()->has_gateway('googlecheckout');
                break;
            case 'google-maps-module':
            case 'googlemaps':
                return class_exists('BusinessDirectory_GoogleMapsPlugin');
                break;
            case 'ratings-module':
            case 'ratings':
                return class_exists('BusinessDirectory_RatingsModule');
                break;
            case 'regions-module':
            case 'regions':
                return class_exists('WPBDP_RegionsPlugin');
                break;
            case 'file-attachments-module':
            case 'attachments':
                return class_exists( 'WPBDP_ListingAttachmentsModule' );
                break;
            case 'zip-search-module':
            case 'zipcodesearch':
                return class_exists( 'WPBDP_ZIPCodeSearchModule' );
                break;
            case 'featured-levels-module':
            case 'featuredlevels':
                return class_exists( 'WPBDP_FeaturedLevelsModule' );
                break;
            case 'stripe-payment-module':
            case 'stripe':
                return class_exists( 'WPBDP_Stripe_Module' );
                break;
            case 'categories':
                return class_exists( 'WPBDP_CategoriesModule' );
                break;
            case 'claim-listings-module':
                return class_exists( 'WPBDP_Claim_Listings_Module' );
                break;
        }

        return false;
    }

    public function _rss_feed() {
        $action = $this->controller->get_current_action();
        $main_page_id = wpbdp_get_page_id( 'main' );

        if ( !$action || !$main_page_id )
            return;

        echo "\n<!-- Business Directory RSS feed -->\n";
        echo sprintf( '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" /> ',
                      sprintf( _x( '%s Feed', 'rss feed', 'WPBDM'), get_the_title( $main_page_id ) ),
                      esc_url( add_query_arg( 'post_type', WPBDP_POST_TYPE,  get_bloginfo( 'rss2_url' ) ) )
                    );

        if ( $action == 'browsetag' || $action == 'browsecategory' ) {
            echo "\n";
            echo sprintf( '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" /> ',
                          sprintf( _x( '%s Feed', 'rss feed', 'WPBDM'), get_the_title( $main_page_id ) ),
                          esc_url( add_query_arg( array( 'post_type' => WPBDP_POST_TYPE, WPBDP_CATEGORY_TAX => get_query_var( 'category' ) ),  get_bloginfo( 'rss2_url' ) ) )
                        );
        }

        echo "\n";
    }

    public function _register_widgets() {
        include_once ( WPBDP_PATH . 'core/widget-featured-listings.php' );
        include_once ( WPBDP_PATH . 'core/widget-latest-listings.php' );
        include_once ( WPBDP_PATH . 'core/widget-random-listings.php' );
        include_once ( WPBDP_PATH . 'core/widget-search.php' );

        register_widget('WPBDP_FeaturedListingsWidget');
        register_widget('WPBDP_LatestListingsWidget');
        register_widget('WPBDP_RandomListingsWidget');
        register_widget('WPBDP_SearchWidget');
    }

    public function _listings_shortcode($atts) {
        if (!$this->controller->check_main_page($msg)) return $msg;

        $atts = shortcode_atts( array(
                                        'category' => null,
                                        'operator' => 'OR'
                                     ),
                                $atts
                              );

        if ( !$atts['category'] )
            return $this->controller->view_listings( true );

        $atts['category'] = explode( ',', $atts['category'] );
        $categories = array();

        foreach ( $atts['category'] as $cat ) {
            $term = null;
            if ( !is_numeric( $cat ) )
                $term = get_term_by( 'slug', $cat, WPBDP_CATEGORY_TAX );

            if ( !$term && is_numeric( $cat ) )
                $term = get_term_by( 'id', $cat, WPBDP_CATEGORY_TAX );

            if ( $term )
                $categories[] = $term->term_id;
        }

        return $this->controller->browse_category( $categories, array(), true );
    }

    public function _featured_listings_shortcode($atts) {
        if (!$this->controller->check_main_page($msg)) return $msg;

        $atts = shortcode_atts( array(
            'number_of_listings' => wpbdp_get_option( 'listings-per-page' )
            ),
            $atts
        );
        $atts['number_of_listings'] = max( 0, intval( $atts['number_of_listings'] ) );

        return $this->controller->view_featured_listings( $atts );
    }

    /* theme filters */
    public function _comments_template($template) {
        // disable comments in WPBDP pages or if comments are disabled for listings
        if ( (is_single() && get_post_type() == WPBDP_POST_TYPE && !$this->settings->get('show-comment-form')) ||
              (get_post_type() == 'page' && get_the_ID() == wpbdp_get_page_id('main') )  ) {
            return WPBDP_TEMPLATES_PATH . '/empty-template.php';
        }

        return $template;
    }

    public function _category_template($template) {
        if (get_query_var(WPBDP_CATEGORY_TAX) && taxonomy_exists(WPBDP_CATEGORY_TAX)) {
            return wpbdp_locate_template(array('businessdirectory-category', 'wpbusdirman-category'));
        }

        return $template;
    }

    public function _single_template($template) {
        if (is_single() && get_post_type() == WPBDP_POST_TYPE) {
            return wpbdp_locate_template(array('businessdirectory-single', 'wpbusdirman-single'));
        }

        return $template;
    }

    /* scripts & styles */

    /**
     * Registers scripts and styles that can be used either by frontend or backend code.
     * The scripts are just registered, not enqueued.
     *
     * @since 3.4
     */
    public function register_common_scripts() {
        // jQuery-FileUpload.
//        wp_register_script( 'jquery-fileupload-ui-widget',
//                            WPBDP_URL . 'vendors/jQuery-File-Upload-9.5.7/js/vendor/jquery.ui.widget' . ( ! $this->is_debug_on() ? '.min' : '' ) . '.js' );
        wp_register_script( 'jquery-fileupload-iframe-transport',
                            WPBDP_URL . 'vendors/jQuery-File-Upload-9.5.7/js/jquery.iframe-transport' . ( ! $this->is_debug_on() ? '.min' : '' ) . '.js' );
        wp_register_script( 'jquery-fileupload',
                            WPBDP_URL . 'vendors/jQuery-File-Upload-9.5.7/js/jquery.fileupload' . ( ! $this->is_debug_on() ? '.min' : '' ) . '.js',
                            array( 'jquery',
                                   'jquery-ui-widget',
                                   'jquery-fileupload-iframe-transport' ) );

        // Drag & Drop.
        wp_register_style( 'wpbdp-dnd-upload', WPBDP_URL . 'core/css/dnd-upload' . ( ! $this->is_debug_on() ? '.min' : '' ) . '.css' );
        wp_register_script( 'wpbdp-dnd-upload', WPBDP_URL . 'core/js/dnd-upload' . ( ! $this->is_debug_on() ? '.min' : '' ) . '.js',
                            array( 'jquery-fileupload' ) );
    }

    public function is_plugin_page() {
        global $post;

        if ( $post && 'page' == $post->post_type ) {
            foreach ( array_keys( $this->get_shortcodes() ) as $shortcode ) {
                if ( wpbdp_has_shortcode( $post->post_content, $shortcode ) ) {
                    return true;
                    break;
                }
            }
        }

        if ( $post && WPBDP_POST_TYPE == $post->post_type )
            return true;

        if ( 'template' == _wpbdp_template_mode ('single' ) || 'template' == _wpbdp_template_mode( 'category' ) )
            return true;
         // TODO: $is_plugin_page detection should take into account custom post type/tax templates.

        return false;
    }

    public function _enqueue_scripts() {
        $only_in_plugin_pages = true;

        wp_enqueue_style( 'wpbdp-widgets', WPBDP_URL . 'core/css/widgets.min.css' );

        if ( $only_in_plugin_pages && ! $this->is_plugin_page() )
            return;

        if ( $this->is_debug_on() ) {
            wp_register_style( 'wpbdp-base-css', WPBDP_URL . 'core/css/wpbdp.css' );
            wp_register_script( 'wpbdp-js', WPBDP_URL . 'core/js/wpbdp.js', array( 'jquery' ) );
        } else {
            wp_register_style( 'wpbdp-base-css', WPBDP_URL . 'core/css/wpbdp.min.css' );
            wp_register_script( 'wpbdp-js', WPBDP_URL . 'core/js/wpbdp.min.js', array( 'jquery' ) );
        }

        wp_enqueue_style( 'wpbdp-dnd-upload' );
        wp_enqueue_script( 'wpbdp-dnd-upload' );

        if ( wpbdp_get_option( 'use-thickbox' ) ) {
            add_thickbox();
        }

        wp_enqueue_style( 'wpbdp-base-css' );
        wp_enqueue_script( 'wpbdp-js' );

        if ( wpbdp_get_option( 'payments-on') && wpbdp_get_option( 'googlewallet' ) ) {
            wp_enqueue_script( 'wpbdp-googlewallet', WPBDP_URL . 'core/js/googlewallet' . ( ! $this->is_debug_on() ? '.min' : '' ) .  '.js', array( 'wpbdp-js' ) );
        }

        do_action( 'wpbdp_enqueue_scripts' );

        // enable legacy css (should be removed in a future release) XXX
        if (_wpbdp_template_mode('single') == 'template' || _wpbdp_template_mode('category') == 'template' )
            wp_enqueue_style('wpbdp-legacy-css', WPBDP_URL . 'core/css/wpbdp-legacy.min.css');
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

    /*
     * Page metadata
     */
    public function _meta_setup() {
        $action = $this->controller->get_current_action();

        if ( ! $action )
            return;

        require_once( WPBDP_PATH . 'core/class-page-meta.php' );
        $this->page_meta = new WPBDP_Page_Meta( $action );


        $this->_do_wpseo = defined( 'WPSEO_VERSION' ) ? true : false;

        if ( $this->_do_wpseo ) {
            $wpseo_front = null;

            if ( isset( $GLOBALS['wpseo_front'] ) )
                $wpseo_front = $GLOBALS['wpseo_front'];
            elseif ( class_exists( 'WPSEO_Frontend' ) && method_exists( 'WPSEO_Frontend', 'get_instance' ) )
                $wpseo_front = WPSEO_Frontend::get_instance();

            remove_filter( 'wp_title', array( $this, '_meta_title' ), 10, 3 );
            add_filter( 'wp_title', array( $this, '_meta_title' ), 16, 3 );

            if ( is_object( $wpseo_front ) ) {
                remove_filter( 'wp_title', array( &$wpseo_front, 'title' ), 15, 3 );
                remove_action( 'wp_head', array( &$wpseo_front, 'head' ), 1, 1 );
            }

            add_action( 'wp_head', array( $this, '_meta_keywords' ) );
        }

        remove_filter( 'wp_head', 'rel_canonical' );
        add_filter( 'wp_head', array( $this, '_meta_rel_canonical' ) );

        if ( 'showlisting' == $action && wpbdp_rewrite_on() )
            add_action( 'wp_head', array( &$this, 'listing_opentags' ) );
    }

    /*
     * Fix issues with Jetpack.
     */
    public function _jetpack_compat( &$wp ) {
        static $incompatible_actions = array( 'submitlisting', 'editlisting', 'upgradetostickylisting' );

        $action = $this->controller->get_current_action();

        if ( !$action )
            return;

        if ( defined( 'JETPACK__VERSION' ) && in_array( $action, $incompatible_actions ) ) {
            add_filter( 'jetpack_enable_opengraph', '__return_false', 99 );
            remove_action( 'wp_head', 'jetpack_og_tags' );
        }
    }

    public function _handle_broken_plugin_filters() {
        $action = $this->controller->get_current_action();

        if ( !$action )
            return;

        // Relevanssi
        if ( in_array( $action, array( 'submitlisting', 'editlisting' ), true ) && function_exists( 'relevanssi_insert_edit' ) ) {
            remove_action( 'wp_insert_post', 'relevanssi_insert_edit', 99, 1 );
            remove_action( 'delete_attachment', 'relevanssi_delete' );
            remove_action( 'add_attachment', 'relevanssi_publish' );
            remove_action( 'edit_attachment', 'relevanssi_edit' );
        }

        $bad_filters = array( 'get_the_excerpt' => array(), 'the_excerpt' => array(), 'the_content' => array() );

        // AddThis Social Bookmarking Widget - http://www.addthis.com/
        if ( defined( 'ADDTHIS_PLUGIN_VERSION' ) ) {
            $bad_filters['get_the_excerpt'][] = array( 'addthis_display_social_widget_excerpt', 11);
            $bad_filters['get_the_excerpt'][] = array( 'addthis_display_social_widget', 15 );
            $bad_filters['the_content'][] = array( 'addthis_display_social_widget', 15 );
        }

        // Jamie Social Icons - http://wordpress.org/extend/plugins/jamie-social-icons/
        if ( function_exists( 'jamiesocial' ) ) {
            $bad_filters['the_content'][] = 'add_post_topbot_content';
            $bad_filters['the_content'][] = 'add_post_bot_content';
            $bad_filters['the_content'][] = 'add_page_topbot_content';
            $bad_filters['the_content'][] = 'add_page_top_content';
            $bad_filters['the_content'][] = 'add_page_bot_content';
        }

        // TF Social Share - http://www.searchtechword.com/2011/06/wordpress-plugin-add-twitter-facebook-google-plus-one-share
        if ( function_exists( 'kc_twitter_facebook_excerpt' ) ) {
            $bad_filters['the_excerpt'][] = 'kc_twitter_facebook_excerpt';
            $bad_filters['the_content'][] = 'kc_twitter_facebook_contents';
        }

        // Shareaholic - https://shareaholic.com/publishers/
        if ( defined( 'SHRSB_vNum' ) ) {
            $bad_filters['the_content'][] = 'shrsb_position_menu';
            $bad_filters['the_content'][] = 'shrsb_get_recommendations';
            $bad_filters['the_content'][] = 'shrsb_get_cb';
        }

        // Simple Facebook Connect (#481)
        if ( function_exists( 'sfc_version' ) ) {
            remove_action( 'wp_head', 'sfc_base_meta' );
        }

        // Quick AdSense - http://quicksense.net/
        global $QData;
        if ( isset( $QData ) ) {
            $bad_filters['the_content'][] = 'process_content';
        }

        foreach ( $bad_filters as $filter => &$callbacks ) {
            foreach ( $callbacks as &$callback_info ) {
                if ( has_filter( $filter, is_array( $callback_info ) ? $callback_info[0] : $callback_info ) ) {
                    remove_filter( $filter, is_array( $callback_info ) ? $callback_info[0] : $callback_info, is_array( $callback_info ) ? $callback_info[1] : 10 );
                }
            }
        }

    }

    // TODO: it'd be nice to move workarounds outside this class.
    public function _meta_title( $title = '', $sep = '»', $seplocation = 'right' ) {
        $wpseo_front = null;

        if ( isset( $GLOBALS['wpseo_front'] ) )
            $wpseo_front = $GLOBALS['wpseo_front'];
        elseif ( class_exists( 'WPSEO_Frontend' ) && method_exists( 'WPSEO_Frontend', 'get_instance' ) )
            $wpseo_front = WPSEO_Frontend::get_instance();

        $action = $this->controller->get_current_action();

        switch ($action) {
            case 'submitlisting':
                if ( $this->_do_wpseo ) {
                    $title = esc_html( strip_tags( stripslashes( apply_filters( 'wpseo_title', $title ) ) ) );
                    return $title;
                }

                return  _x( 'Submit A Listing', 'title', 'WPBDM' ) . ' ' . $sep . ' ' . $title;

                break;

            case 'search':
                if ( $this->_do_wpseo ) {
                    $title = esc_html( strip_tags( stripslashes( apply_filters( 'wpseo_title', $title ) ) ) );
                    return $title;
                }

                return _x( 'Find a Listing', 'title', 'WPBDM' ) . ' ' . $sep . ' ' . $title;

                break;

            case 'viewlistings':
                if ( $this->_do_wpseo ) {
                    $title = esc_html( strip_tags( stripslashes( apply_filters( 'wpseo_title', $title ) ) ) );
                    return $title;
                }

                return _x( 'View All Listings', 'title', 'WPBDM' ) . ' ' . $sep . ' ' . $title;
                break;

            case 'browsetag':
                $term = get_term_by('slug', get_query_var('tag'), WPBDP_TAGS_TAX);

                if ( $this->_do_wpseo ) {
                    if ( method_exists( 'WPSEO_Taxonomy_Meta', 'get_term_meta' ) ) {
                        $title = WPSEO_Taxonomy_Meta::get_term_meta( $term, $term->taxonomy, 'title' );
                    } else {
                        $title = trim( wpseo_get_term_meta( $term, $term->taxonomy, 'title' ) );
                    }

                    if ( !empty( $title ) )
                        return wpseo_replace_vars( $title, (array) $term );

                    if ( is_object( $wpseo_front ) )
                        return $wpseo_front->get_title_from_options( 'title-tax-' . $term->taxonomy, $term );
                }

                return sprintf( _x( 'Listings tagged: %s', 'title', 'WPBDM' ), $term->name ) . ' ' . $sep . ' ' . $title;

                break;

            case 'browsecategory':
                $term = get_term_by('slug', get_query_var('category'), WPBDP_CATEGORY_TAX);
                if (!$term && get_query_var('category_id')) $term = get_term_by('id', get_query_var('category_id'), WPBDP_CATEGORY_TAX);

                if ( $this->_do_wpseo ) {
                    if ( method_exists( 'WPSEO_Taxonomy_Meta', 'get_term_meta' ) ) {
                        $title = WPSEO_Taxonomy_Meta::get_term_meta( $term, $term->taxonomy, 'title' );
                    } else {
                        $title = trim( wpseo_get_term_meta( $term, $term->taxonomy, 'title' ) );
                    }

                    if ( !empty( $title ) )
                        return wpseo_replace_vars( $title, (array) $term );

                    if ( is_object( $wpseo_front ) )
                        return $wpseo_front->get_title_from_options( 'title-tax-' . $term->taxonomy, $term );
                }

                return $term->name . ' ' . $sep . ' ' . $title;

                break;

            case 'showlisting':
                $listing_id = get_query_var('listing') ? wpbdp_get_post_by_slug(get_query_var('listing'))->ID : wpbdp_getv($_GET, 'id', get_query_var('id'));

                if ( $this->_do_wpseo ) {
                    $title = $wpseo_front->get_content_title( get_post( $listing_id ) );
                    $title = esc_html( strip_tags( stripslashes( apply_filters( 'wpseo_title', $title ) ) ) );

                    return $title;
                    break;
                } else {
                    $post_title = get_the_title($listing_id);
                }

                return $post_title . ' '.  $sep . ' ' . $title;
                break;

            case 'main':
                break;

            default:
                break;
        }

        return $title;
    }

    public function _meta_keywords() {
        $wpseo_front = null;

        if ( isset( $GLOBALS['wpseo_front'] ) )
            $wpseo_front = $GLOBALS['wpseo_front'];
        elseif ( class_exists( 'WPSEO_Frontend' ) && method_exists( 'WPSEO_Frontend', 'get_instance' ) )
            $wpseo_front = WPSEO_Frontend::get_instance();

        $current_action = $this->controller->get_current_action();

        switch ( $current_action ){
            case 'showlisting':
                global $post;

                $listing_id = get_query_var('listing') ? wpbdp_get_post_by_slug(get_query_var('listing'))->ID : wpbdp_getv($_GET, 'id', get_query_var('id'));

                $prev_post = $post;
                $post = get_post( $listing_id );

                if ( is_object( $wpseo_front ) ) {
                    $wpseo_front->metadesc();
                    $wpseo_front->metakeywords();
                }

                $post = $prev_post;

                break;
            case 'browsecategory':
            case 'browsetag':
                if ( $current_action == 'browsetag' ) {
                    $term = get_term_by('slug', get_query_var('tag'), WPBDP_TAGS_TAX);
                } else {
                    $term = get_term_by('slug', get_query_var('category'), WPBDP_CATEGORY_TAX);
                    if (!$term && get_query_var('category_id')) $term = get_term_by('id', get_query_var('category_id'), WPBDP_CATEGORY_TAX);
                }

                if ( $term ) {
                    $metadesc = method_exists( 'WPSEO_Taxonomy_Meta', 'get_term_meta' ) ?
                                WPSEO_Taxonomy_Meta::get_term_meta( $term, $term->taxonomy, 'desc' ) :
                                wpseo_get_term_meta( $term, $term->taxonomy, 'desc' );

                    if ( !$metadesc && is_object( $wpseo_front ) && isset( $wpseo_front->options['metadesc-tax-' . $term->taxonomy] ) )
                        $metadesc = wpseo_replace_vars( $wpseo_front->options['metadesc-tax-' . $term->taxonomy], (array) $term );

                    if ( $metadesc )
                        echo '<meta name="description" content="' . esc_attr( strip_tags( stripslashes( $metadesc ) ) ) . '"/>' . "\n";
                }

                break;

            case 'main':
                if ( is_object( $wpseo_front ) ) {
                    $wpseo_front->metadesc();
                    $wpseo_front->metakeywords();
                }

                break;

            default:
                break;
        }

    }

    public function _meta_rel_canonical() {
        $action = $this->controller->get_current_action();

        if ( !$action )
            return rel_canonical();

        if ( in_array( $action, array( 'editlisting', 'submitlisting', 'sendcontactmessage', 'deletelisting', 'upgradetostickylisting', 'renewlisting' ) ) )
            return;

        if ( $action == 'showlisting' ) {
            $listing_id = get_query_var('listing') ? wpbdp_get_post_by_slug(get_query_var('listing'))->ID : wpbdp_getv($_GET, 'id', get_query_var('id'));
            $link = get_permalink( $listing_id );
        } else {
            $link = $_SERVER['REQUEST_URI'];
        }

        echo sprintf( '<link rel="canonical" href="%s" />', esc_url( $link ) );
    }

    function listing_opentags() {
        $listing_id = get_query_var('listing') ? wpbdp_get_post_by_slug(get_query_var('listing'))->ID : wpbdp_getv($_GET, 'id', get_query_var('id'));
        $listing = WPBDP_Listing::get( $listing_id );

        if ( ! $listing )
            return;

        echo '<meta property="og:type" content="website" />';
        echo '<meta property="og:title" content="' . esc_attr( WPBDP_SEO::listing_title( $listing_id ) ) . '" />';
        echo '<meta property="og:url" content="' . esc_url( $listing->get_permalink() ) . '" />';
        echo '<meta property="og:description" content="' . esc_attr( WPBDP_SEO::listing_og_description( $listing_id ) ) . '" />';

        if ( $thumbnail_id = $listing->get_thumbnail_id() ) {
            if ( $img = wp_get_attachment_image_src( $thumbnail_id, 'wpbdp-large' ) )
                echo '<meta property="og:image" content="' . $img[0] . '" />';
        }
    }

    public function ajax_file_field_upload() {
        echo '<form action="" method="POST" enctype="multipart/form-data">';
        echo '<input type="file" name="file" class="file-upload" onchange="return window.parent.WPBDP.fileUpload.handleUpload(this);"/>';
        echo '</form>';

        if ( isset($_FILES['file']) && $_FILES['file']['error'] == 0 ) {
            // TODO: we support only images for now but we could use this for anything later
            if ( $media_id = wpbdp_media_upload( $_FILES['file'], true, true, array(), $errors ) ) {
                echo '<div class="preview" style="display: none;">';
                echo wp_get_attachment_image( $media_id, 'thumb', false );
                echo '</div>';

                echo '<script type="text/javascript">';
                echo sprintf( 'window.parent.WPBDP.fileUpload.finishUpload(%d, %d);', $_REQUEST['field_id'], $media_id );
                echo '</script>';
            } else {
                print $errors;
            }
        }

        echo sprintf( '<script type="text/javascript">window.parent.WPBDP.fileUpload.resizeIFrame(%d);</script>', $_REQUEST['field_id'] );

        exit;
    }

    /* Listing expiration. */
    public function _notify_expiring_listings() {
        if ( wpbdp_get_option( 'payment-abandonment' ) )
            $this->payments->notify_abandoned_payments();

        if ( ! wpbdp_get_option( 'listing-renewal' ) )
            return;

        wpbdp_log('Running expirations hook.');

        $now = current_time( 'timestamp' );

        $api = wpbdp_listings_api();
        $api->notify_expiring_listings( 0, $now ); //  notify already expired listings first
        $api->notify_expiring_listings( wpbdp_get_option( 'renewal-email-threshold', 5 ), $now ); // notify listings expiring soon

        if ( wpbdp_get_option( 'renewal-reminder' ) ) {
            $threshold = -max( 1, intval( wpbdp_get_option( 'renewal-reminder-threshold' ) ) );
            $api->notify_expiring_listings( $threshold, $now );
        }
    }


    /*
     *  Comments reCAPTCHA.
     */
    public function recaptcha_in_comments( $comment_field ) {
        $html  = '';
//        $html .= $comment_field;

        // If this is not a BD page, ignore reCAPTCHA.
        if ( ! $this->controller->get_current_action() )
            return $html;

        if ( wpbdp_get_option( 'recaptcha-on' ) ) {
            // Only one reCAPTCHA is allowed per page, so we work around this limitation by sharing the one in the contact form.
//            add_action( 'wp_footer', array( &$this, 'comment_recaptcha_workaround' ) );

            $html .= '<div id="wpbdp-comment-recaptcha">';
        } else {
            $html .= '<div id="wpbdp-comment-recaptcha">';
            $html .= wpbdp_recaptcha();
        }

        $error = '';
        if ( isset( $_GET['wpbdp-recaptcha-error'] ) && $_GET['wpbdp-recaptcha-error'] ) {
            $error = _x( "The reCAPTCHA wasn't entered correctly.", 'comment-form', 'WPBDM' );

            add_action( 'wp_footer', array( &$this, 'restore_comment_fields' ) );
        }

        $html .= '</div>';

        if ( $error )
            $html .= sprintf( '<p class="wpbdp-recaptcha-error">%s</p>', $error );

        echo $html;
    }


    public function check_comment_recaptcha( $comment_data ) {
        if ( ! wpbdp_get_option( 'recaptcha-for-comments' ) )
            return $comment_data;

        if ( ! wpbdp_recaptcha_check_answer( $this->_comment_recaptcha_error ) ) {
            add_filter( 'pre_comment_approved', create_function( '$a', 'return \'spam\';' ) );
        }

        return $comment_data;
    }

    public function comment_relative_redirect( $location, $comment ) {
        if ( !isset( $this->_comment_recaptcha_error ) || empty( $this->_comment_recaptcha_error ) )
            return $location;

        $location = substr( $location, 0, strpos( $location, '#' ) );
        $location = add_query_arg( 'wpbdp-recaptcha-error', urlencode( base64_encode( $comment->comment_ID . '/' . $this->_comment_recaptcha_error ) ), $location );
        $location .= '#commentform';

        return $location;
    }

    public function restore_comment_fields() {
        if ( !isset( $_GET['wpbdp-recaptcha-error'] ) || empty( $_GET['wpbdp-recaptcha-error'] ) )
            return;

        $error_data = explode('/', base64_decode( urldecode( $_GET['wpbdp-recaptcha-error'] ) ) );
        $comment_id = $error_data ? intval( $error_data[0] ) : 0;
        $comment = get_comment( $comment_id );

        if ( !$comment )
            return;

        echo <<<JS
        <script type="text/javascript">//<![CDATA[
            jQuery('#comment').val("{$comment->comment_content}");
        //]]></script>
JS;
    }

    public function comment_recaptcha_workaround() {
        $public_key = wpbdp_get_option( 'recaptcha-public-key' );

        echo <<<JS
        <script type="text/javascript">//<![CDATA[
        jQuery(function($) {
            var recaptchas_in_page = [];
            var active = '';

            if ( $( '#wpbdp-claim-listings-form' ).length > 0 )
                recaptchas_in_page.push( 'claim-listings' );

            if ( $( '#wpbdp-comment-recaptcha' ) )
                recaptchas_in_page.push( 'comment' );

            if ( $( '#wpbdp-contact-form-recaptcha' ) )
                recaptchas_in_page.push( 'contact' );

            if ( recaptchas_in_page.length <= 1 )
                return;

            var active = recaptchas_in_page[0];

            var move_recaptcha_to = function( dest ) {
                if ( active == dest )
                    return;

                Recaptcha.destroy();
                $( '#wpbdp-contact-form-recaptcha, #wpbdp-comment-recaptcha' ).attr( 'class', '' ).empty();
                $( '#wpbdp-claim-listings-form .field.recaptcha' ).empty();

                var recaptcha_area = '';

                if ( 'comment' == dest ) {
                    recaptcha_area = 'wpbdp-comment-recaptcha';
                } else if ( 'contact' == dest ) {
                    recaptcha_area = 'wpbdp-contact-form-recaptcha';
                } else if ( 'claim-listings' == dest ) {
                    $( '#wpbdp-claim-listings-form .field.recaptcha' ).html( '<div id="wpbdp-claim-listings-recaptcha"></div>' );
                    recaptcha_area = 'wpbdp-claim-listings-recaptcha';
                }

                if ( recaptcha_area )
                    Recaptcha.create( '{$public_key}', recaptcha_area );

                active = dest;
            };

            $( '#comment' ).focusin(function() {
                move_recaptcha_to( 'comment' );
            });
            $( '#wpbdp-contact-form-message' ).focusin(function() {
                    move_recaptcha_to( 'contact' );
            });
            $( '.wpbdp-claim-listings .claim-listing-link' ).click(function(e) {
                var open = $(this).parent( '.wpbdp-claim-listings' ).hasClass('open');

                if ( ! open )
                    return;

                move_recaptcha_to( 'claim-listings' );
            });

        });
        //]]></script>
JS;

        if ( isset( $_GET['wpbdp-recaptcha-error'] ) && !empty( $_GET['wpbdp-recaptcha-error'] ) ) {
            echo <<<JS
            <script type="text/javascript">//<![CDATA[
            jQuery(document).ready(function(){
                jQuery('#comment').focus();
            });
            //]]></script>
JS;
        }
    }

    // {{ Sorting options.
    public function sortbar_sort_options( $options ) {
        if ( ! wpbdp_get_option( 'listings-sortbar-enabled' ) )
            return $options;

        $sortbar_fields = $this->settings->sortbar_fields_cb();
        $sortbar = wpbdp_get_option( 'listings-sortbar-fields' );

        foreach ( $sortbar as $field_id ) {
            if ( ! array_key_exists( $field_id, $sortbar_fields ) )
                continue;
            $options[ 'field-' . $field_id ] = array( $sortbar_fields[ $field_id ], '', 'ASC' );
        }

        return $options;
    }

    public function sortbar_query_fields( $fields ) {
        global $wpdb;

        $sort = wpbdp_get_current_sort_option();

        if ( ! $sort || ! in_array( str_replace( 'field-', '', $sort->option ), wpbdp_get_option( 'listings-sortbar-fields' ) ) )
            return $fields;

        $sname = str_replace( 'field-', '', $sort->option );
        $q = '';

        switch ( $sname ) {
            case 'user_login':
                $q = "(SELECT user_login FROM {$wpdb->users} WHERE {$wpdb->users}.ID = {$wpdb->posts}.post_author) AS user_login";
                break;
            case 'user_registered':
                $q = "(SELECT user_registered FROM {$wpdb->users} WHERE {$wpdb->users}.ID = {$wpdb->posts}.post_author) AS user_registered";
                break;
            case 'date':
            case 'modified':
                break;
            default:
                $field = wpbdp_get_form_field( $sname );

                if ( ! $field || 'meta' != $field->get_association() )
                    break;

                $q = $wpdb->prepare( "(SELECT {$wpdb->postmeta}.meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->postmeta}.meta_key = %s) AS field_{$sname}", '_wpbdp[fields][' . $field->get_id() . ']' );
                break;
        }

        if ( $q )
            return $fields . ', ' . $q;
        else
            return $fields;
    }

    public function sortbar_orderby( $orderby ) {
        global $wpdb;

        $sort = wpbdp_get_current_sort_option();

        if ( ! $sort || ! in_array( str_replace( 'field-', '', $sort->option ), wpbdp_get_option( 'listings-sortbar-fields' ) ) )
            return $orderby;

        $sname = str_replace( 'field-', '', $sort->option );
        $qn = '';

        switch ( $sname ) {
            case 'user_login':
            case 'user_registered':
                $qn = $sname;
                break;
            case 'date':
            case 'modified':
                $qn = "{$wpdb->posts}.post_{$sname}";
                break;
            default:
                $field = wpbdp_get_form_field( $sname );

                if ( ! $field )
                    break;

                switch ( $field->get_association() ) {
                    case 'title':
                    case 'excerpt':
                    case 'content':
                        $qn = "{$wpdb->posts}.post_" . $field->get_association();
                        break;
                    case 'meta':
                        $qn = "field_{$sname}";
                        break;
                }

                break;
        }

        if ( $qn )
            return $orderby . ', ' . $qn . ' ' . $sort->order;
        else
            return $orderby;
    }
    // }}
}

$wpbdp = new WPBDP_Plugin();

