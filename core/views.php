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
        if ( is_page() && get_the_ID() == wpbdp_get_page_id( 'main' ) ) {
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
                return $this->submit_listing();
                break;
            case 'sendcontactmessage':
                require_once( WPBDP_PATH . 'core/view-listing-contact.php' );
                $page = new WPBDP_Listing_Contact_Page();
                return $page->dispatch();
                break;
            case 'deletelisting':
                return $this->delete_listing();
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
            default:
                return $this->main_page();
                break;
        }
    }

    /* Show listing. */
    public function show_listing() {
        if (!$this->check_main_page($msg)) return $msg;

        if (get_query_var('listing') || isset($_GET['listing'])) {
            if ($posts = get_posts(array('post_status' => 'publish', 'numberposts' => 1, 'post_type' => WPBDP_POST_TYPE, 'name' => get_query_var('listing') ? get_query_var('listing') : wpbdp_getv($_GET, 'listing', null) ) )) {
                $listing_id = $posts[0]->ID;
            } else {
                $listing_id = null;
            }
        } else {
            $listing_id = get_query_var('id') ? get_query_var('id') : wpbdp_getv($_GET, 'id', null);
        }

        if ( !$listing_id )
            return;

        $html  = '';

        if ( isset($_GET['preview']) )
            $html .= wpbdp_render_msg( _x('This is just a preview. The listing has not been published yet.', 'preview', 'WPBDM') );

        // Handle ?v=viewname argument for alternative views (other than 'single').
        $view = '';
        if ( isset( $_GET['v'] ) )
            $view = wpbdp_capture_action_array( 'wpbdp_listing_view_' . trim( $_GET['v'] ), array( $listing_id ) );

        if ( ! $view )
            $html .= wpbdp_render_listing($listing_id, 'single', false, true);
        else
            $html .= $view;

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

        if ( is_array( $category_id ) || $in_listings_shortcode ) {
            $title = '';
            $category = null;
        } else {
            $category = get_term( $category_id, WPBDP_CATEGORY_TAX );
            $title = esc_attr( $category->name );
        }

        $html = wpbdp_render( 'category',
                             array(
                                'title' => $title,
                                'category' => $category,
                                'is_tag' => false
                                ),
                             false );

        wp_reset_query();

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

        $html = wpbdp_render( 'category',
                             array(
                                'category' => get_term( $tag_id, WPBDP_TAGS_TAX ),
                                'is_tag' => true
                                ),
                             false );        

        wp_reset_query();

        return $html;
    }    

    /* display listings */
    public function view_listings($include_buttons=false) {
        $paged = 1;

        if (get_query_var('page'))
            $paged = get_query_var('page');
        elseif (get_query_var('paged'))
            $paged = get_query_var('paged');

        query_posts(array(
            'post_type' => WPBDP_POST_TYPE,
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'post_status' => 'publish',
            'paged' => intval($paged),
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC')
        ));

        $html = wpbdp_capture_action( 'wpbdp_before_viewlistings_page' );
        $html .= wpbdp_render('businessdirectory-listings', array(
                'excludebuttons' => !$include_buttons
            ), true);
        $html .= wpbdp_capture_action( 'wpbdp_after_viewlistings_page' );

        wp_reset_query();

        return $html;
    }

    /* display featured listings */
    public function view_featured_listings($args) {
        extract($args);

        $html = "";

        $posts = get_posts(array(
            'post_type' => WPBDP_POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => $args['number_of_listings'],
            'orderby' => 'date',
            'meta_query' => array(
                array('key' => '_wpbdp[sticky]', 'value' => 'sticky')
            )
        ));

        foreach ($posts as $post) {
            $html .= wpbdp_render_listing($post->ID, 'excerpt');
        }

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
        }

        $html = wpbdp_render('manage-listings', array(
            'current_user' => $current_user
            ), false);

        if ($current_user)
            wp_reset_query();

        return $html;
    }

    public function delete_listing() {
        if ($listing_id = wpbdp_getv($_REQUEST, 'listing_id')) {
            if ( (wp_get_current_user()->ID == get_post($listing_id)->post_author) || (current_user_can('administrator')) ) {
                $post_update = array('ID' => $listing_id,
                                     'post_type' => WPBDP_POST_TYPE,
                                     'post_status' => wpbdp_get_option('deleted-status'));
                
                wp_update_post($post_update);

                return wpbdp_render_msg(_x('The listing has been deleted.', 'templates', 'WPBDM'))
                      . $this->main_page();
            }
        }
    }

    /*
     * Search functionality
     */
    public function search() {
        $results = array();

        if ( isset( $_GET['dosrch'] ) ) {
            $search_args = array();
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

        query_posts( array(
            'post_type' => WPBDP_POST_TYPE,
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'post__in' => $results ? $results : array(0),
            'orderby' => wpbdp_get_option( 'listings-order-by', 'date' ),
            'order' => wpbdp_get_option( 'listings-sort', 'ASC' )
        ) );

        $html = wpbdp_render( 'search',
                               array( 
                                      'fields' => $fields,
                                      'searching' => isset( $_GET['dosrch'] ) ? true : false,
                                      'show_form' => !isset( $_GET['dosrch'] ) || wpbdp_get_option( 'show-search-form-in-results' )
                                    ),
                              false );
        wp_reset_query();

        return $html;
    }

}

}
