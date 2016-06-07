<?php

class WPBDP__Views__Search extends WPBDP_NView {

    public function get_title() {
        return _x( 'Find A Listing', 'views', 'WPBDM' );
    }

    public function dispatch() {
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
            'order' => wpbdp_get_option( 'listings-sort', 'ASC' ),
            'wpbdp_main_query' => true
        );
        $args = apply_filters( 'wpbdp_search_query_posts_args', $args, $search_args );
        query_posts( $args );
        wpbdp_push_query( $GLOBALS['wp_query'] );

        $searching = isset( $_GET['dosrch'] ) ? true : false;
        $search_form = '';

        if ( ( $searching && 'none' != wpbdp_get_option( 'search-form-in-results' ) ) || ! $searching )
            $search_form = wpbdp_render_page( WPBDP_PATH . 'templates/search-form.tpl.php', array( 'fields' => $fields ) );

        $results = false;

        if ( have_posts() ) {
            $results  = '';
            $results .= wpbdp_capture_action( 'wpbdp_before_search_results' );
            $results .= wpbdp_x_render( 'listings', array( '_parent' => 'search',
                                                           'query' => wpbdp_current_query() ) );
            $results .= wpbdp_capture_action( 'wpbdp_after_search_results' );
        }

        $html = wpbdp_x_render( 'search',
                                array( 'search_form' => $search_form,
                                       'search_form_position' => wpbdp_get_option( 'search-form-in-results' ),
                                       'fields' => $fields,
                                       'searching' => $searching,
                                       'results' => $results
                                   ) );

        wp_reset_query();
        wpbdp_pop_query();

        return $html;
    }

}
