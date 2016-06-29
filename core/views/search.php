<?php

class WPBDP__Views__Search extends WPBDP_NView {

    public function dispatch() {
        $listings_api = wpbdp_listings_api();
        $quick_search = false;
        $results = array();

        if ( isset ( $_POST['q'] ) )
            $quick_search = true;

        if ( $quick_search ) {
            $keywords = trim( $_POST['q'] );
            $location = ! empty( $_POST['location'] ) ? $_POST['location'] : '';

            $results = $listings_api->quick_search( $keywords, $location );
        } elseif ( ! empty( $_POST ) ) {
            // Advanced search.
            $results = $listings_api->search_2( $_POST['listingfields'] );
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
            'post__in' => $results ? $results : array( 0 ),
            'orderby' => wpbdp_get_option( 'listings-order-by', 'date' ),
            'order' => wpbdp_get_option( 'listings-sort', 'ASC' )
        );
        // $args = apply_filters( 'wpbdp_search_query_posts_args', $args );
        query_posts( $args );
        wpbdp_push_query( $GLOBALS['wp_query'] );

        $searching = $quick_search || ! empty( $_POST );
        $search_form = '';

        if ( ( $searching && 'none' != wpbdp_get_option( 'search-form-in-results' ) ) || ! $searching )
            $search_form = wpbdp_render_page( WPBDP_PATH . 'templates/search-form.tpl.php', array( 'fields' => $fields ) );

        if ( wpbdp_experimental( 'themes' ) ) {
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

        } else {
            $html = wpbdp_render( 'search',
                                  array( 'search_form' => $search_form,
                                         'search_form_position' => wpbdp_get_option( 'search-form-in-results' ),
                                         'fields' => $fields,
                                         'searching' => $searching
                                       ),
                                  false );
        }

        wp_reset_query();
        wpbdp_pop_query();

        return $html;
    }

}

