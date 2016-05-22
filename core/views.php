<?php
/*
 * General directory views
 */

if (!class_exists('WPBDP_DirectoryController')) {

class WPBDP_DirectoryController {

    public $action = null;

    private $current_category = 0;
    private $current_tag = 0;
    private $current_listing = 0;

    private $router = null;
    private $output = null;


    public function __construct() {
        if ( wpbdp_experimental( 'routing' ) ) {
            require_once ( WPBDP_PATH . 'core/class-router.php' );

            $this->router = new WPBDP_Router();
            $this->router->add_view_path( WPBDP_PATH . 'core/views/' );
            $this->setup_routes();
        }

        $this->urlconf[] = array( '/request_access_keys', 'WPBDP_Views__Request_Access_Keys', 'name' => 'request_access_keys' );

        add_action( 'wp', array( $this, '_handle_action'), 10, 1 );
        add_action( 'template_redirect', array( &$this, 'handle_login_redirect' ), 20 );

        $this->_extra_sections = array();
    }

    public function check_main_page(&$msg) {
        $msg = '';

        $wpbdp = wpbdp();
        if ( ! wpbdp_get_page_id( 'main' ) ) {
            if (current_user_can('administrator') || current_user_can('activate_plugins'))
                $msg = __('You need to create a page with the [businessdirectory] shortcode for the Business Directory plugin to work correctly.', 'WPBDM');
            else
                $msg = __('The directory is temporarily disabled.', 'WPBDM');
            return false;
        }

        return true;
    }

    public function _handle_action(&$wp) {
        global $wpbdp;

        if ( is_page() && in_array( get_the_ID(), wpbdp_get_page_ids( 'main' ) ) ) {
            $action = get_query_var('action') ? get_query_var('action') : ( isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '' );

            if (get_query_var('category_id') || get_query_var('category')) $action = 'browsecategory';
            if (get_query_var('tag')) $action = 'browsetag';
            if (get_query_var('id') || get_query_var('listing')) $action = 'showlisting';

            if (!$action) $action = 'main';

            $this->action = $action;
        } else {
            $this->action = null;

            if ( $wpbdp->is_plugin_page() ) {
                global $post;

                if ( wpbdp_has_shortcode( $post->post_content, 'businessdirectory-submitlisting' ) ||
                     wpbdp_has_shortcode( $post->post_content, 'WPBUSDIRMANADDLISTING' ) ) {
                     $this->action = 'submitlisting';
                } elseif ( wpbdp_has_shortcode( $post->post_content, 'businessdirectory-search' ) ||
                           wpbdp_has_shortcode( $post->post_content, 'businessdirectory_search' ) ) {
                    $this->action = 'search';
                }
            }
        }

        if ( wpbdp_experimental( 'routing' ) )
            return $this->process_view();
    }

    private function setup_routes() {
//        $this->router->add( '/?v=request_access_keys', 'WPBDP_Request_Access_Keys_View', null, 'request_access_keys' );
        $this->router->add( '/request_access_keys', 'WPBDP_Request_Access_Keys_View', null, 'request_access_keys' );

        do_action_ref_array( 'wpbdp-add-routes', array( $this->router ) );
    }

    private function process_view() {
        $view = $this->router->route();

        if ( ! $view )
            return;

        $response = $view->dispatch();

        if ( is_string( $response ) )
            $this->output = $response;
    }

    /**
     * @deprecated since themes-release
     */
    public function get_current_action() {
        global $wp_query;

        if ( ! empty ( $wp_query->wpbdp_view ) )
            return $wp_query->wpbdp_view;

        if ( ! empty ( $_REQUEST['wpbdp_view'] ) )
            return $_REQUEST['wpbdp_view'];
    }

    public function current_category_id() {
        return $this->current_category;
    }

    public function current_tag_id() {
        return $this->current_tag;
    }

    public function current_listing_id() {
        return $this->current_listing;
    }

    function handle_login_redirect() {
        $action = $this->get_current_action();
        $login_url = trim( wpbdp_get_option( 'login-url' ) );

        if ( ! $login_url || is_user_logged_in() || ! wpbdp_get_option( 'require-login' ) )
            return;

        if ( ! in_array( $action, array( 'editlisting', 'submitlisting', 'deletelisting', 'renewlisting' ), true ) )
            return;

        $url = add_query_arg( 'redirect_to', urlencode( home_url( $_SERVER['REQUEST_URI'] ) ), $login_url );
        wp_redirect( esc_url_raw( $url ) );
        exit();
    }

    public function dispatch() {
        if ( wpbdp_experimental( 'routing' ) && ! empty( $this->output ) ) {
            return $this->output;
        }

        switch ($this->action) {
            case 'renewlisting':
                require_once( WPBDP_PATH . 'core/view-renew-listing.php' );
                $renew_page = new WPBDP_Renew_Listing_Page();
                return $renew_page->dispatch();

                break;
            case 'payment-process':
                return $this->process_payment();
                break;
            case 'manage-recurring':
                require_once( WPBDP_PATH . 'core/view-manage-recurring.php' );
                $page = new WPBDP_Manage_Subscriptions_View();
                return $page->dispatch();
                break;
            default:
                // Handle custom actions.
                $page = wpbdp_capture_action_array( 'wpbdp_action_page_' . $this->action );
                if ( $page )
                    return $page;

                return $this->main_page();
                break;
        }
    }

    /**
     * @deprecated since themes-release
     */
    public function view_listings( $include_buttons=false, $args_ = array() ) {
        require_once ( WPBDP_PATH . 'core/views/all_listings.php' );

        $v = new WPBDP__Views__All_Listings( compact( 'include_buttons' ) );
        return $v->dispatch();
    }

    /* display featured listings */
    public function view_featured_listings($args) {
        $no_listings = isset( $args['number_of_listings'] ) ? intval( $args['number_of_listings'] ) : 0;

        if ( ! $no_listings )
            $no_listings = wpbdp_get_option( 'listings-per-page' );

        $html  = '';

        global $wp_query;
        $old_query = $wp_query;

        query_posts( array(
            'post_type' => WPBDP_POST_TYPE,
            'post_status' => 'publish',
            'paged' => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
            'posts_per_page' => $no_listings,
            'meta_query' => array(
                array( 'key' => '_wpbdp[sticky]', 'value' => 'sticky' )
            )
        ) );
        wpbdp_push_query( $GLOBALS['wp_query'] );

        $html  = '';
        $html .= wpbdp_render( 'businessdirectory-listings' );

        $wp_query = $old_query;
        wp_reset_query();
        wpbdp_pop_query();

        return $html;
    }

    /**
     * @deprecated since next-release
     */
    public function submit_listing() {
        require_once( WPBDP_PATH . 'core/view-submit-listing.php' );
        $submit_page = new WPBDP_Submit_Listing_Page( isset( $_REQUEST['listing_id'] ) ? $_REQUEST['listing_id'] : 0 );
        return $submit_page->dispatch();
    }


    /**
     * @deprecated since next-release.
     */
    public function main_page() {
        require_once( WPBDP_PATH . 'core/views/main.php' );

        $v = new WPBDP__Views__Main();
        return $v->dispatch();
    }

    /* Manage Listings */
    public function manage_listings() {
        if (!$this->check_main_page($msg)) return $msg;

        $current_user = is_user_logged_in() ? wp_get_current_user() : null;
        $listings = array();

        if ($current_user) {
            query_posts(array(
                'author' => $current_user->ID,
                'post_type' => WPBDP_POST_TYPE,
                'post_status' => 'publish',
                'paged' => get_query_var('paged') ? get_query_var('paged') : 1
            ));
            wpbdp_push_query( $GLOBALS['wp_query'] );
        }

        $html = wpbdp_render('manage-listings', array(
            'current_user' => $current_user
            ), false);

        if ($current_user) {
            wp_reset_query();
            wpbdp_pop_query();
        }

        return $html;
    }

}

}

