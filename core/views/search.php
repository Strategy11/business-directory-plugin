<?php
require_once( WPBDP_PATH . 'core/helpers/class-listing-search.php' );


class WPBDP__Views__Search extends WPBDP_NView {

    public function get_title() {
        return _x( 'Find A Listing', 'views', 'WPBDM' );
    }

    public function dispatch() {
        $searching = ( ! empty( $_GET ) && ( isset( $_GET['kw'] ) || ! empty( $_GET['dosrch'] ) ) );
        $search = null;

        if ( $searching ) {
            $_GET = stripslashes_deep( $_GET );

            $search = WPBDP__Listing_Search::from_request( $_GET );
            $search->execute();
        }

        $search_form = '';
        $form_fields = wpbdp_get_form_fields( array( 'display_flags' => 'search', 'validators' => '-email' ) );
        $fields = '';
        foreach ( $form_fields as &$field ) {
            $field_value = null;

            if ( $search ) {
                $terms = $search->terms_for_field( $field );

                if ( $terms )
                    $field_value = array_pop( $terms );
            }

            $fields .= $field->render( $field->convert_input( $field_value ), 'search' );
        }

        if ( $searching ) {
            $args = array(
                'post_type' => WPBDP_POST_TYPE,
                'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
                'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
                'post__in' => $search->get_results() ? $search->get_results() : array( 0 ),
                'orderby' => wpbdp_get_option( 'listings-order-by', 'date' ),
                'order' => wpbdp_get_option( 'listings-sort', 'ASC' ),
                'wpbdp_main_query' => true
            );
            $args = apply_filters( 'wpbdp_search_query_posts_args', $args, $search );

            query_posts( $args );
            wpbdp_push_query( $GLOBALS['wp_query'] );
        }

        if ( ( $searching && 'none' != wpbdp_get_option( 'search-form-in-results' ) ) || ! $searching )
            $search_form = wpbdp_render_page( WPBDP_PATH . 'templates/search-form.tpl.php', array( 'fields' => $fields ) );

        if ( $searching && have_posts() ) {
            $results  = '';
            $results .= wpbdp_capture_action( 'wpbdp_before_search_results' );
            $results .= wpbdp_x_render( 'listings', array( '_parent' => 'search',
                                                           'query' => wpbdp_current_query() ) );
            $results .= wpbdp_capture_action( 'wpbdp_after_search_results' );
        } else {
            $results = '';
        }

        $html = wpbdp_x_render( 'search',
                                array( 'search_form' => $search_form,
                                       'search_form_position' => wpbdp_get_option( 'search-form-in-results' ),
                                       'fields' => $fields,
                                       'searching' => $searching,
                                       'results' => $results
                                   ) );

        if ( $searching ) {
            wp_reset_query();
            wpbdp_pop_query();
        }

        return $html;
    }

}

