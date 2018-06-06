<?php
/**
 * @package WPBDP/Views/Search
 */

// phpcs:disable
require_once WPBDP_PATH . 'includes/helpers/class-listing-search.php';

/**
 * @SuppressWarnings(PHPMD)
 */
class WPBDP__Views__Search extends WPBDP__View {

    public function get_title() {
        return _x( 'Find A Listing', 'views', 'WPBDM' );
    }

    public function dispatch() {
        $searching = ( ! empty( $_GET ) && ( ! empty( $_GET['kw'] ) || ! empty( $_GET['dosrch'] ) ) );
        $searching = apply_filters( 'wpbdp_searching_request', $searching );
        $search    = null;

        $form_fields = wpbdp_get_form_fields(
            array(
				'display_flags' => 'search',
				'validators'    => '-email',
            )
        );

        if ( $searching ) {
            $_GET = stripslashes_deep( $_GET );

            $validation_errors = array();
            if ( ! empty( $_GET['dosrch'] ) ) {
                // Validate fields that are required.
                foreach ( $form_fields as $field ) {
                    if ( $field->has_validator( 'required-in-search' ) ) {
                        $value = $field->value_from_GET();

                        if ( ! $value || $field->is_empty_value( $value ) ) {
                            $validation_errors[] = sprintf( _x( '"%s" is required.', 'search', 'WPBDM' ), $field->get_label() );
                        }
                    }
                }
            }

            if ( ! $validation_errors ) {
                $search = WPBDP__Listing_Search::from_request( $_GET );
                $search->execute();
            } else {
                $searching = false;
            }
        }

        $search_form = '';
        $fields      = '';
        foreach ( $form_fields as &$field ) {
            $field_value = null;

            if ( $search ) {
                $terms = $search->get_original_search_terms_for_field( $field );

                if ( $terms ) {
                    $field_value = array_pop( $terms );
                }
            }

            $fields .= $field->render( $field->convert_input( $field_value ), 'search' );
        }

        if ( $searching ) {
            $args = array(
                'post_type'        => WPBDP_POST_TYPE,
                'posts_per_page'   => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
                'paged'            => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
                'post__in'         => $search->get_results() ? $search->get_results() : array( 0 ),
                'orderby'          => wpbdp_get_option( 'listings-order-by', 'date' ),
                'order'            => wpbdp_get_option( 'listings-sort', 'ASC' ),
                'wpbdp_main_query' => true,
            );
            $args = apply_filters( 'wpbdp_search_query_posts_args', $args, $search );

            query_posts( $args );
            wpbdp_push_query( $GLOBALS['wp_query'] );
        }

        if ( ( $searching && 'none' != wpbdp_get_option( 'search-form-in-results' ) ) || ! $searching ) {
            $search_form = wpbdp_render_page(
                WPBDP_PATH . 'templates/search-form.tpl.php',
                array(
                    'fields'            => $fields,
                    'validation_errors' => ! empty( $validation_errors ) ? $validation_errors : array(),
                    'return_url'        => ( ! empty( $this->return_url ) ? $this->return_url : '' ),
                )
            );
        }

        $results = '';

        if ( $searching && have_posts() ) {
            $results .= wpbdp_capture_action( 'wpbdp_before_search_results' );
            $results .= wpbdp_x_render(
                'listings', array(
					'_parent' => 'search',
					'query'   => wpbdp_current_query(),
                )
            );
            $results .= wpbdp_capture_action( 'wpbdp_after_search_results' );
        }

        $html = wpbdp_x_render(
            'search',
            array(
				'search_form'          => $search_form,
				'search_form_position' => wpbdp_get_option( 'search-form-in-results' ),
				'fields'               => $fields,
				'searching'            => $searching,
				'results'              => $results,
            )
        );

        if ( $searching ) {
            wp_reset_query();
            wpbdp_pop_query();
        }

        return $html;
    }

}
