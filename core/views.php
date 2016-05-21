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

        add_action( 'wpbdp_enqueue_scripts', array( &$this, '_enqueue_view_scripts' ) );
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

    function _enqueue_view_scripts() {
        switch ( $this->action ) {
            case 'submitlisting':
            case 'editlisting':
                wp_enqueue_script( 'wpbdp-submit-listing', WPBDP_URL . 'core/js/submit-listing.js', array( 'jquery-ui-sortable' ) );
                break;
            default:
                break;
        }
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
            case 'showlisting':
                return $this->show_listing();
                break;
            case 'browsecategory':
                return $this->browse_category();
                break;
            case 'browsetag':
                return $this->browse_tag();
                break;
            case 'editlisting':
            case 'submitlisting':
                require_once( WPBDP_PATH . 'core/view-submit-listing.php' );
                $submit_page = new WPBDP_Submit_Listing_Page( isset( $_REQUEST['listing_id'] ) ? $_REQUEST['listing_id'] : 0 );
                return $submit_page->dispatch();

                break;
            case 'sendcontactmessage':
                require_once( WPBDP_PATH . 'core/view-listing-contact.php' );
                $page = new WPBDP_Listing_Contact_View();
                return $page->dispatch();

                break;
            case 'deletelisting':
                require_once( WPBDP_PATH . 'core/view-delete-listing.php' );
                $v = new WPBDP_Delete_Listing_View();
                return $v->dispatch();

                break;
            case 'upgradetostickylisting':
                require_once( WPBDP_PATH . 'core/view-upgrade-listing.php' );
                $upgrade_page = new WPBDP_Upgrade_Listing_Page();
                return $upgrade_page->dispatch();

                break;
            case 'view-listings':
            case 'viewlistings':
                return $this->view_listings(true);
                break;
            case 'renewlisting':
                require_once( WPBDP_PATH . 'core/view-renew-listing.php' );
                $renew_page = new WPBDP_Renew_Listing_Page();
                return $renew_page->dispatch();

                break;
            case 'payment-process':
                return $this->process_payment();
                break;
            case 'search':
                return $this->search();
                break;
            case 'checkout':
                require_once( WPBDP_PATH . 'core/view-checkout.php' );
                $checkout_page = new WPBDP_Checkout_Page();
                return $checkout_page->dispatch();
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

    /* Show listing. */
    public function show_listing() {
        if ( ! $this->check_main_page( $msg ) )
            return $msg;

        $id_or_slug = '';
        if ( get_query_var( 'listing' ) || isset( $_GET['listing'] ) )
            $id_or_slug = get_query_var( 'listing' ) ? get_query_var( 'listing' ) : wpbdp_getv( $_GET, 'listing', 0 );
        else
            $id_or_slug = get_query_var( 'id' ) ? get_query_var( 'id' ) : wpbdp_getv( $_GET, 'id', 0 );

        $listing_id = wpbdp_get_post_by_id_or_slug( $id_or_slug, 'id', 'id' );
        $this->current_listing = $listing_id;
/*
        if (get_query_var('listing') || isset($_GET['listing'])) {
            if ($posts = get_posts(array('post_status' => 'any', 'numberposts' => 1, 'post_type' => WPBDP_POST_TYPE, 'name' => get_query_var('listing') ? get_query_var('listing') : wpbdp_getv($_GET, 'listing', null) ) )) {
                $listing_id = $posts[0]->ID;
            } else {
                $listing_id = null;
            }
        } else {
            $listing_id = get_query_var('id') ? get_query_var('id') : wpbdp_getv($_GET, 'id', null);
        }*/

        if ( !$listing_id )
            return;

        $html  = '';

        if ( 'publish' != get_post_status( $listing_id ) ) {
            if ( current_user_can( 'edit_posts' ) )
                $html .= wpbdp_render_msg( _x('This is just a preview. The listing has not been published yet.', 'preview', 'WPBDM') );
            else
                return;
        }

        // Handle ?v=viewname argument for alternative views (other than 'single').
        $view = '';
        if ( isset( $_GET['v'] ) )
            $view = wpbdp_capture_action_array( 'wpbdp_listing_view_' . trim( $_GET['v'] ), array( $listing_id ) );

        if ( ! $view )
            $html .= wpbdp_render_listing($listing_id, 'single', false, true);
        else
            $html .= $view;

        wp_reset_query(); // Just in case some shortcode messed this up.
        return $html;
    }

    /* Display category. */
    public function browse_category( $category_id=null, $args = array(), $in_listings_shortcode = false ) {
        if (!$this->check_main_page($msg)) return $msg;

        if (get_query_var('category')) {
            if ($term = get_term_by('slug', get_query_var('category'), WPBDP_CATEGORY_TAX)) {
                $category_id = $term->term_id;
            } else {
                $category_id = intval(get_query_var('category'));
            }
        }
        if( !empty( $args['items_per_page'] ) ){
            $items_per_page = $args['items_per_page'];
        }else{
           $items_per_page = wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1;
        }
        $category_id = $category_id ? $category_id : intval(get_query_var('category_id'));
        $category_id = is_array( $category_id ) && 1 == count( $category_id ) ? $category_id[0] : $category_id;

        $args = array(
                    'wpbdp_action' => 'browsecategory',
                    'post_type' => WPBDP_POST_TYPE,
                    'post_status' => 'publish',
                    'posts_per_page' => $items_per_page ,
                    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
                    'orderby' => wpbdp_get_option('listings-order-by', 'date'),
                    'order' => wpbdp_get_option('listings-sort', 'ASC'),
                    'tax_query' => array(
                        array('taxonomy' => WPBDP_CATEGORY_TAX,
                              'field' => 'id',
                              'terms' => $category_id)
                    )
        );

        if ( ! $in_listings_shortcode )
            $this->current_category = $category_id;

        $q = new WP_Query( $args );
        wpbdp_push_query( $q );

        $category = get_term( $category_id, WPBDP_CATEGORY_TAX );
        $category->is_tag = false;

        $html = wpbdp_x_render( 'category', array( '_id' => 'category',
                                                   '_full' => true,
                                                   'category' => $category,
                                                   'query' => $q ) );
        // TODO(themes-release). Review original category template and use of is_tag and in_shortcode.
        // if ( is_array( $category_id ) ) {
        //     $title = '';
        //     $category = null;
        // } else {
        //     $category = get_term( $category_id, WPBDP_CATEGORY_TAX );
        //     $title = esc_attr( $category->name );
        //
        //     if ( $in_listings_shortcode )
        //         $title = '';
        // }

        wp_reset_postdata();
        wpbdp_pop_query();


        wpbdp_pop_query();

        return $html;
    }

    /* Display category. */
    public function browse_tag( $args = array() ) {
        if ( ! $this->check_main_page( $msg ) )
            return $msg;

        $args = wp_parse_args( $args, array( 'tags' => array(), 'title' => '', 'only_listings' => false ) );

        $tags = array();
        $tag_list = '';

        if ( ! $args['tags'] ) {
            $tag = get_term_by( 'slug', get_query_var( 'tag' ), WPBDP_TAGS_TAX );
            $tags = array( $tag );
            $tag_list = $tag->name;
        } else {
            foreach ( $args['tags'] as $t ) {
                $tag = false;

                if ( ! is_numeric( $t ) )
                    $tag = get_term_by( 'name', $t, WPBDP_TAGS_TAX );

                if ( ! $tag && is_numeric( $t ) )
                    $tag = get_term_by( 'id', $t, WPBDP_TAGS_TAX );

                if ( $tag )
                    $tags[] = $tag;

                $tag_list = implode( ', ', wp_list_pluck( $tags, 'name' ) );
            }
        }

        $this->current_tag = ( 1 == count( $tags ) ) ? $tags[0]->term_id : 0;

        query_posts(array(
            'post_type' => WPBDP_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC'),
            'tax_query' => array(
                array( 'taxonomy' => WPBDP_TAGS_TAX,
                       'field' => 'id',
                       'terms' => wp_list_pluck( $tags, 'term_id' ) )
            )
        ));
        wpbdp_push_query( $GLOBALS['wp_query'] );

        $html = wpbdp_render( 'category',
                              array( 'title' => $args['title'] ? $args['title'] : sprintf( _x( 'Listings tagged: %s', 'templates', 'WPBDM' ), $tag_list ),
                                     'category' => $tag,
                                     'is_tag' => true,
                                     'tag_list' => $tag_list,
                                     'only_listings' => $args['only_listings'] ),
                              false );

        wp_reset_query();
        wpbdp_pop_query();

        return $html;
    }

    /* display listings */
    public function view_listings($include_buttons=false, $args_ = array()) {
        $paged = get_query_var( 'page' ) ? get_query_var( 'page' ) : ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 );

        $args = array(
            'post_type' => WPBDP_POST_TYPE,
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'post_status' => 'publish',
            'paged' => intval($paged),
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC')
        );
        if ( isset( $args_['numberposts'] ) )
            $args['numberposts'] = $args_['numberposts'];

        if ( ! empty( $args_['author'] ) )
            $args['author'] = $args_['author'];

        $q = new WP_Query( $args );
        wpbdp_push_query( $q );

        // TODO: review use of wpbdp_before_viewlistings_page, wpbdp_after_viewlistings_page.
        $html = wpbdp_x_render( 'listings', array( '_id' => 'listings',
                                                   '_wrapper' => $include_buttons ? 'page' : '',
                                                   '_bar' => $include_buttons ? true : false,
                                                   'query' => $q ) );
        wp_reset_postdata();
        wpbdp_pop_query( $q );

        return $html;
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


function wpbdp_current_category_id() {
    global $wpbdp;
    return $wpbdp->controller->current_category_id();
}

function wpbdp_current_tag_id() {
    global $wpbdp;
    return $wpbdp->controller->current_tag_id();
}

function wpbdp_current_action() {
    global $wpbdp;
    return $wpbdp->controller->get_current_action();
}

function wpbdp_current_listing_id() {
    global $wpbdp;
    return $wpbdp->controller->current_listing_id();
}



}


