<?php
/**
 * @since 4.0
 */
class WPBDP__Query_Integration {

    public function __construct() {
        add_action( 'parse_query', array( $this, 'set_query_flags' ), 50 );
        add_action( 'template_redirect', array( $this, 'set_404_flag' ), 0 );

        add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10, 1 );
        add_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 10, 2 );
    }

    public function set_query_flags( $query ) {
        if ( is_admin() )
            return;

        $main_query = ( ! $query->is_main_query() && isset( $query->query_vars['wpbdp_main_query'] ) && $query->query_vars['wpbdp_main_query'] )
                      || $query->is_main_query();

        if ( ! $main_query )
            return;

        // Defaults.
        $query->wpbdp_view = '';
        $query->wpbdp_is_main_page = false;
        $query->wpbdp_is_listing = false;
        $query->wpbdp_is_category = false;
        $query->wpbdp_is_tag = false;
        $query->wpbdp_our_query = false;

        // Is this a listing query?
        // FIXME: this results in false positives frequently
        $types = ( ! empty( $query->query_vars['post_type'] ) ? (array) $query->query_vars['post_type'] : array() );
        if ( $query->is_single && in_array( WPBDP_POST_TYPE, $types ) && count( $types ) < 2 ) {
            $query->wpbdp_is_listing = true;
            $query->wpbdp_view = 'show_listing';
        }

        // Is this a category query?
        $category_slug = wpbdp_get_option( 'permalinks-category-slug' );
        if ( ! empty( $query->query_vars[ WPBDP_CATEGORY_TAX ] ) ) {
            $query->wpbdp_is_category = true;
            $query->wpbdp_view = 'show_category';
        }

        $tags_slug = wpbdp_get_option( 'permalinks-tags-slug' );
        if ( ! empty( $query->query_vars[ WPBDP_TAGS_TAX ] ) ) {
            $query->wpbdp_is_tag = true;
            $query->wpbdp_view = 'show_tag';
        }

        // Is this the main page?
        // FIXME: make this more robust.
        if ( $query->is_page
             && ( in_array( (int) $query->get_queried_object_id(), array_map( 'absint', wpbdp_get_page_ids() ), true )
                  || in_array( (int) $query->get( 'page_id' ), array_map( 'absint', wpbdp_get_page_ids() ), true ) ) ) {
            $query->wpbdp_is_main_page = true;
        }

        if ( ! $query->wpbdp_view ) {
            if ( $query->get( 'wpbdp_view' ) )
                $query->wpbdp_view = $query->get( 'wpbdp_view' );
            elseif ( $query->wpbdp_is_main_page )
                $query->wpbdp_view = 'main';
        }

        $query->wpbdp_our_query = ( $query->wpbdp_is_listing || $query->wpbdp_is_category || $query->wpbdp_is_tag );

        if ( ! empty( $query->query_vars['wpbdp_main_query'] ) )
            $query->wpbdp_our_query = true;

        // Normalize view name.
        if ( ! empty( $query->wpbdp_view ) )
            $query->wpbdp_view = WPBDP_Utils::normalize( $query->wpbdp_view );

        do_action_ref_array( 'wpbdp_query_flags', array( $query ) );
    }

    public function set_404_flag() {
        global $wp_query;

        if ( ! $wp_query->wpbdp_our_query )
            return;

        if ( 'show_listing' == $wp_query->wpbdp_view && empty( $wp_query->posts ) )
            $wp_query->is_404 = true;
    }

    public function pre_get_posts( &$query ) {
        if ( is_admin() || ! isset( $query->wpbdp_our_query ) || ! $query->wpbdp_our_query )
            return;

        if ( ! $query->get( 'posts_per_page' ) )
            $query->set( 'posts_per_page', wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1 );

        if ( ! $query->get( 'orderby' ) )
            $query->set( 'orderby', wpbdp_get_option('listings-order-by', 'date' ) );

        if ( ! $query->get( 'order' ) )
            $query->set( 'order', wpbdp_get_option('listings-sort', 'ASC' ) );
    }

    public function posts_clauses( $pieces, $query ) {
        global $wpdb;

        if ( is_admin() || ! isset( $query->wpbdp_our_query ) || ! $query->wpbdp_our_query )
            return $pieces;

        $pieces = apply_filters( 'wpbdp_query_clauses', $pieces, $query );

        // Sticky listings.
        $is_sticky_query = $wpdb->prepare( "(SELECT 1 FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->postmeta}.meta_key = %s AND {$wpdb->postmeta}.meta_value = %s LIMIT 1 ) AS wpbdp_is_sticky",
                                           '_wpbdp[sticky]', 'sticky' );
        $pieces['fields'] .= ', ' . $is_sticky_query . ' ';

        // Sticky listings (per fee).
        if ( ! empty( $query->wpbdp_is_category ) ) {
            $category = $query->get_queried_object();
            $pieces['fields'] .= ', ' . $wpdb->prepare( "(SELECT 1 FROM {$wpdb->prefix}wpbdp_listing_fees lf WHERE lf.listing_id = {$wpdb->posts}.ID AND lf.sticky = %d AND lf.category_id = %d LIMIT 1 ) AS wpbdp_cat_sticky",
                                                        1,
                                                        $category->term_id );
        } else {
            $pieces['fields'] .= ', (SELECT 0) AS wpbdp_cat_sticky';
        }

        // Paid first query order.
        if ( in_array( $query->get( 'orderby' ), array( 'paid', 'paid-title' ), true ) ) {
            $is_paid_query = "(SELECT 1 FROM {$wpdb->prefix}wpbdp_payments pp WHERE pp.listing_id = {$wpdb->posts}.ID AND pp.amount > 0 LIMIT 1 ) AS wpbdp_is_paid";
            $pieces['fields'] .= ', ' . $is_paid_query;
        } else {
            $pieces['fields'] .= ', (SELECT 0) AS wpbdp_is_paid';
        }

        if ( 'paid-title' == $query->get( 'orderby' ) )
            $pieces['orderby'] = "{$wpdb->posts}.post_title ASC, " . $pieces['orderby'];

        $pieces['orderby'] = 'wpbdp_is_sticky DESC, wpbdp_cat_sticky DESC, wpbdp_is_paid DESC ' . apply_filters( 'wpbdp_query_orderby', '' ) . ', ' . $pieces['orderby'];
        $pieces['fields'] = apply_filters('wpbdp_query_fields', $pieces['fields'] );

        return $pieces;
    }

}

