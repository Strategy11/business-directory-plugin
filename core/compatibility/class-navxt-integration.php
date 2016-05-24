<?php
/**
 * @since 3.6.5
 */
class WPBDP_NavXT_Integration {

    private $state = array();
    private $doing = '';


    function __construct() {
        add_action( 'bcn_before_fill', array( &$this, 'prepare_state' ) );
        add_action( 'bcn_after_fill', array( &$this, 'restore_state' ) );
    }

    function prepare_state( $trail ) {
        if ( $this->doing )
            return;

        global $wpbdp;
        $action = wpbdp_current_view();

        switch ( $action ) {
            case 'show_listing':
                $this->doing = 'listing';
                break;

            case 'show_category':
                $this->doing = 'category';
                break;

            case 'show_tag':
                $this->doing = 'tag';
                break;

            case 'edit_listing':
                $this->doing = 'edit';
                break;

            case 'submit_listing':
                $this->doing = 'submit';
                break;

            case 'search':
                $this->doing = 'search';
                break;

            default:
                $this->doing = '';
        }

        if ( ! $this->doing )
            return;

        if ( method_exists( $this, 'before_' . $this->doing ) )
            call_user_func( array( $this, 'before_' . $this->doing ), $trail );
    }

    function restore_state( $trail ) {
        if ( ! $this->doing )
            return;

        if ( method_exists( $this, 'after_' . $this->doing ) )
            call_user_func( array( $this, 'after_' . $this->doing ), $trail );

        $this->doing = '';
    }

    function main_page_breadcrumb( $trail ) {
        $last = $trail->trail[ count( $trail->trail ) - 1 ];
        if ( in_array( 'home', $last->type, true ) )
            array_pop( $trail->trail );

        $trail->add( new bcn_breadcrumb( get_the_title( wpbdp_get_page_id() ),
                                         '',
                                         array(),
                                         wpbdp_get_page_link(),
                                         wpbdp_get_page_id() ) );
    }

    // {{ Handlers.

    function before_listing( $trail ) {
        // XXX: Taken from core/views.php:show_listing(). Probably a good idea to move this to an utility function.
        $id_or_slug = '';
        if ( get_query_var( 'listing' ) || isset( $_GET['listing'] ) )
            $id_or_slug = get_query_var( 'listing' ) ? get_query_var( 'listing' ) : wpbdp_getv( $_GET, 'listing', 0 );
        else
            $id_or_slug = get_query_var( 'id' ) ? get_query_var( 'id' ) : wpbdp_getv( $_GET, 'id', 0 );

        $listing_id = wpbdp_get_post_by_id_or_slug( $id_or_slug, 'id', 'id' );

        if ( ! $listing_id )
            return;

        $this->state['post'] = $GLOBALS['post'];
        $GLOBALS['post'] = get_post( $listing_id );
    }

    function after_listing( $trail ) {
        $GLOBALS['post'] = $this->state['post'];
        unset( $this->state['post'] );

        $this->main_page_breadcrumb( $trail );
    }

    function before_category( $trail ) {
        // XXX: Taken from core/views.php:browse_category(). Probably a good idea to move this to an utility function.
        if (get_query_var('category')) {
            if ($term = get_term_by('slug', get_query_var('category'), WPBDP_CATEGORY_TAX)) {
                $category_id = $term->term_id;
            } else {
                $category_id = intval(get_query_var('category'));
            }
        }

        $category_id = $category_id ? $category_id : intval(get_query_var('category_id'));

        if ( ! $category_id )
            return;

        global $wp_query;
        $term = get_term( $category_id, WPBDP_CATEGORY_TAX );
        $this->state['queried'] = $wp_query->get_queried_object();

        $wp_query->is_singular = false;
        $wp_query->queried_object = $term;
    }

    function after_category( $trail ) {
        global $wp_query;

        $wp_query->queried_object = $this->state['queried'];
        $wp_query->is_singular = true;
        unset( $this->state['queried'] );

        $this->main_page_breadcrumb( $trail );
    }

    function before_tag( $trail ) {
        $tag = get_term_by( 'slug', get_query_var( 'tag' ), WPBDP_TAGS_TAX );

        if ( ! $tag )
            return;

        global $wp_query;
        $term = get_term( $category_id, WPBDP_CATEGORY_TAX );
        $this->state['queried'] = $wp_query->get_queried_object();

        $wp_query->is_singular = false;
        $wp_query->queried_object = $tag;
    }

    function after_tag( $trail ) {
        $this->after_category( $trail );
    }

    function before_submit( $trail ) {
        $trail->add( new bcn_breadcrumb( _x( 'Submit Listing', 'navxt', 'WPBDM' ) ) );
    }

    function before_edit( $trail ) {
        $trail->add( new bcn_breadcrumb( _x( 'Edit Listing', 'navxt', 'WPBDM' ) ) );
    }

    function before_search( $trail ) {
        $trail->add( new bcn_breadcrumb( _x( 'Search', 'navxt', 'WPBDM' ) ) );
    }

    // }}

}
