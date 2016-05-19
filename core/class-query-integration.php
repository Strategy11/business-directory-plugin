<?php
/**
 * @since next-release
 */
class WPBDP__Query_Integration {

    public function __construct() {
        add_action( 'parse_query', array( $this, 'set_query_flags' ), 50 );
        add_action( 'template_redirect', array( $this, 'set_404_flag' ), 0 );
    }

    public function set_query_flags( $query ) {
        if ( is_admin() || ! $query->is_main_query() )
            return;

        // Defaults.
        $query->wpbdp_view = '';
        $query->wpbdp_is_main_page = false;
        $query->wpbdp_is_listing = false;
        $query->wpbdp_is_category = false;
        $query->wpbdp_is_tag = false;
        $query->wpbdp_our_query = false;

        // Is this a listing query?
        $types = ( ! empty( $query->query_vars['post_type'] ) ? (array) $query->query_vars['post_type'] : array() );
        if ( in_array( WPBDP_POST_TYPE, $types ) && count( $types ) < 2 ) {
            $query->wpbdp_is_listing = true;
            $query->wpbdp_view = 'show_listing';
        }

        // Is this a category query?
        if ( ! empty( $query->query_vars[ WPBDP_CATEGORY_TAX ] ) ) {
            $query->wpbdp_is_category = true;
            $query->wpbdp_view = 'show_category';
        }

        // Is this the main page?
        if ( $query->get( 'page_id' ) == wpbdp_get_page_id() ) {
            $query->wpbdp_is_main_page = true;
        }

        if ( ! $query->wpbdp_view ) {
            if ( $query->get( 'wpbdp_view' ) )
                $query->wpbdp_view = $query->get( 'wpbdp_view' );
            else
                $query->wpbdp_view = 'main';
        }

        $query->wpbdp_our_query = ( $query->wpbdp_is_listing || $query->wpbdp_is_category || $query->wpbdp_is_tag );

        do_action_ref_array( 'wpbdp_query_flags', array( $query ) );
    }

    public function set_404_flag() {
        global $wp_query;

        if ( ! $wp_query->wpbdp_our_query )
            return;

        if ( ( 'show_listing' == $wp_query->wpbdp_view || $wp_query->wpbdp_is_category ) && empty( $wp_query->posts ) )
            $wp_query->is_404 = true;
    }

}
