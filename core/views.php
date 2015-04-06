<?php
/*
 * General directory views
 */

if (!class_exists('WPBDP_DirectoryController')) {

class WPBDP_DirectoryController {

    public $action = null;

    public function __construct() {
        add_action( 'wp', array( $this, '_handle_action'), 10, 1 );
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
        if ( is_page() && in_array( get_the_ID(), wpbdp_get_page_id( 'main', false ) ) ) {
            $action = get_query_var('action') ? get_query_var('action') : ( isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '' );

            if (get_query_var('category_id') || get_query_var('category')) $action = 'browsecategory';
            if (get_query_var('tag')) $action = 'browsetag';
            if (get_query_var('id') || get_query_var('listing')) $action = 'showlisting';

            if (!$action) $action = 'main';

            $this->action = $action;
        } else {
            $this->action = null;
        }
    }

    public function get_current_action() {
        return $this->action;
    }

    public function dispatch() {
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

        $category_id = $category_id ? $category_id : intval(get_query_var('category_id'));
        $category_id = is_array( $category_id ) && 1 == count( $category_id ) ? $category_id[0] : $category_id;

        $listings_api = wpbdp_listings_api();

        query_posts(array(
            'post_type' => WPBDP_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC'),
            'tax_query' => array(
                array('taxonomy' => WPBDP_CATEGORY_TAX,
                      'field' => 'id',
                      'terms' => $category_id)
            )
        ));
        $q = $GLOBALS['wp_query'];
        wpbdp_push_query( $q );

        if ( is_array( $category_id ) ) {
            $title = '';
            $category = null;
        } else {
            $category = get_term( $category_id, WPBDP_CATEGORY_TAX );
            $title = esc_attr( $category->name );

            if ( $in_listings_shortcode )
                $title = '';
        }

        $html = wpbdp_render( 'category',
                             array(
                                'title' => $title,
                                'category' => $category,
                                'is_tag' => false,
                                'in_shortcode' => $in_listings_shortcode
                                ),
                             false );

        wp_reset_query();
        wpbdp_pop_query();

        return $html;
    }

    /* Display category. */
    public function browse_tag() {
        if (!$this->check_main_page($msg)) return $msg;

        $tag = get_term_by('slug', get_query_var('tag'), WPBDP_TAGS_TAX);
        $tag_id = $tag->term_id;

        $listings_api = wpbdp_listings_api();

        query_posts(array(
            'post_type' => WPBDP_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC'),
            'tax_query' => array(
                array('taxonomy' => WPBDP_TAGS_TAX,
                      'field' => 'id',
                      'terms' => $tag_id)
            )
        ));
        wpbdp_push_query( $GLOBALS['wp_query'] );

        $html = wpbdp_render( 'category',
                             array(
                                'title' => esc_attr( $tag->name ),
                                'category' => $tag,
                                'is_tag' => true
                                ),
                             false );

        wp_reset_query();
        wpbdp_pop_query();

        return $html;
    }    

    /* display listings */
    public function view_listings($include_buttons=false, $args_ = array()) {
        $paged = 1;

        if (get_query_var('page'))
            $paged = get_query_var('page');
        elseif (get_query_var('paged'))
            $paged = get_query_var('paged');

        $args = array(
            'post_type' => WPBDP_POST_TYPE,
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'post_status' => 'publish',
            'paged' => intval($paged),
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC'),
        );
        if ( isset( $args_['numberposts'] ) )
            $args['numberposts'] = $args_['numberposts'];

        // See if we need to call query_posts() directly in case the user is using the template without
        // the $query argument.
        $template = file_get_contents( wpbdp_locate_template( 'businessdirectory-listings' ) );
        $compat = ( false === stripos( $template, '$query->the_post' ) ) ? true : false;

        if ( $compat ) {
            query_posts( $args );
            $q = $GLOBALS['wp_query'];
        } else {
            $q = new WP_Query( $args );
        }

        wpbdp_push_query( $q );

        $html = wpbdp_capture_action( 'wpbdp_before_viewlistings_page' );
        $html .= wpbdp_render('businessdirectory-listings', array(
                'query' => $q,
                'excludebuttons' => !$include_buttons
            ), true);
        $html .= wpbdp_capture_action( 'wpbdp_after_viewlistings_page' );

        if ( ! $compat )
            wp_reset_postdata();

        wp_reset_query();
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

    public function submit_listing() {
        require_once( WPBDP_PATH . 'core/view-submit-listing.php' );
        $submit_page = new WPBDP_Submit_Listing_Page( isset( $_REQUEST['listing_id'] ) ? $_REQUEST['listing_id'] : 0 );
        return $submit_page->dispatch();
    }

    /*
     * Directory views/actions
     */
    public function main_page() {
        $html = '';

        if ( count(get_terms(WPBDP_CATEGORY_TAX, array('hide_empty' => 0))) == 0 ) {
            if (is_user_logged_in() && current_user_can('install_plugins')) {
                $html .= wpbdp_render_msg( _x('There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that there are currently no listings in the directory. Listings cannot be added until you assign categories to the business directory.', 'templates', 'WPBDM'), 'error' );
            } else {
                $html .= "<p>" . _x('There are currently no listings in the directory.', 'templates', 'WPBDM') . "</p>";
            }
        }

        if (current_user_can('administrator')) {
            if ($errors = wpbdp_payments_api()->check_config()) {
                foreach ($errors as $error) {
                    $html .= wpbdp_render_msg($error, 'error');
                }
            }
        }

        $listings = '';
        if (wpbdp_get_option('show-listings-under-categories'))
            $listings = $this->view_listings(false);

        if ( current_user_can( 'administrator' ) && wpbdp_get_option( 'hide-empty-categories' ) &&
             wp_count_terms( WPBDP_CATEGORY_TAX, 'hide_empty=0' ) > 0 && wp_count_terms( WPBDP_CATEGORY_TAX, 'hide_empty=1' ) == 0 ) {
            $msg = _x( 'You have "Hide Empty Categories" on and some categories that don\'t have listings in them. That means they won\'t show up on the front end of your site. If you didn\'t want that, click <a>here</a> to change the setting.',
                       'templates',
                       'WPBDM' );
            $msg = str_replace( '<a>',
                                '<a href="' . admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=listings#hide-empty-categories' ) . '">',
                                $msg );
            $html .= wpbdp_render_msg( $msg );
        }

        $html .= wpbdp_render(array('businessdirectory-main-page', 'wpbusdirman-index-categories'),
                               array(
                                'submit_listing_button' => wpbusdirman_post_menu_button_submitlisting(),
                                'view_listings_button' => wpbusdirman_post_menu_button_viewlistings(),
                                'action_links' => wpbusdirman_post_menu_button_submitlisting() . wpbusdirman_post_menu_button_viewlistings(),
                                'search_form' => wpbdp_get_option('show-search-listings') ? wpbdp_search_form() : '',
                                'listings' => $listings
                               ));

        return $html;
    }


    /*
     * Submit listing process.
     */

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

    /*
     * Search functionality
     */
    public function search() {
        $_REQUEST = stripslashes_deep( $_REQUEST );

        $search_args = array();
        $results = array();

        if ( isset( $_GET['dosrch'] ) ) {
            $search_args['q'] = wpbdp_getv($_GET, 'q', null);
            $search_args['fields'] = array(); // standard search fields
            $search_args['extra'] = array(); // search fields added by plugins

            foreach ( wpbdp_getv( $_GET, 'listingfields', array() ) as $field_id => $field_search )
                $search_args['fields'][] = array( 'field_id' => $field_id, 'q' => $field_search );

            foreach ( wpbdp_getv( $_GET, '_x', array() ) as $label => $field )
                $search_args['extra'][ $label ] = $field;

            $listings_api = wpbdp_listings_api();

            if ( $search_args['q'] && ! $search_args['fields'] && ! $search_args['extra'] )
                $results = $listings_api->quick_search( $search_args['q'] );
            else
                $results = $listings_api->search( $search_args );
        }

        $form_fields = wpbdp_get_form_fields( array( 'display_flags' => 'search', 'validators' => '-email' ) );
        $fields = '';
        foreach ( $form_fields as &$field ) {
            $field_value = isset( $_REQUEST['listingfields'] ) && isset( $_REQUEST['listingfields'][ $field->get_id() ] ) ? $field->convert_input( $_REQUEST['listingfields'][ $field->get_id() ] ) : $field->convert_input( null );
            $fields .= $field->render( $field_value, 'search' );
        }

        $args = array(
            'post_type' => WPBDP_POST_TYPE,
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'post__in' => $results ? $results : array(0),
            'orderby' => wpbdp_get_option( 'listings-order-by', 'date' ),
            'order' => wpbdp_get_option( 'listings-sort', 'ASC' )
        );
        $args = apply_filters( 'wpbdp_search_query_posts_args', $args, $search_args );
        query_posts( $args );
        wpbdp_push_query( $GLOBALS['wp_query'] );

        $html = wpbdp_render( 'search',
                               array( 
                                      'fields' => $fields,
                                      'searching' => isset( $_GET['dosrch'] ) ? true : false,
                                      'show_form' => !isset( $_GET['dosrch'] ) || wpbdp_get_option( 'show-search-form-in-results' )
                                    ),
                              false );
        wp_reset_query();
        wpbdp_pop_query();

        return $html;
    }

}

}
