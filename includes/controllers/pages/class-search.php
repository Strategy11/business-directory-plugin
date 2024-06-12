<?php
/**
 * @package WPBDP/Views/Search
 */

require_once WPBDP_PATH . 'includes/helpers/class-listing-search.php';

class WPBDP__Views__Search extends WPBDP__View {

	public function get_title() {
		return _x( 'Find A Listing', 'views', 'business-directory-plugin' );
	}

	public function dispatch() { // phpcs:ignore SlevomatCodingStandard
		$searching = ( ! empty( $_GET ) && ( ! empty( $_GET['kw'] ) || ! empty( $_GET['dosrch'] ) ) );

		$handler      = wpbdp_get_var( array( 'param' => 'handler' ), 'post' );
		$search_modal = 'search__get_search_content' === $handler;

		$searching = apply_filters( 'wpbdp_searching_request', $searching );
		$search    = null;
		$redirect  = ! $searching && isset( $_GET['kw'] ) && 'none' === wpbdp_get_option( 'search-form-in-results' );

		if ( $redirect ) {
			$this->_redirect( wpbdp_url( 'all_listings' ) );
		}

		$form_fields = wpbdp_get_form_fields(
			array(
				'display_flags' => 'search',
				'validators'    => '-email',
			)
		);

		if ( $searching ) {
			$url_vars = stripslashes_deep( $_GET );
			wpbdp_sanitize_value( 'sanitize_text_field', $url_vars );

			$validation_errors = array();
			if ( ! empty( $url_vars['dosrch'] ) ) {
				// Validate fields that are required.
				foreach ( $form_fields as $field ) {
					if ( $field->has_validator( 'required-in-search' ) ) {
						$value = $field->value_from_GET();

						if ( ! $value || $field->is_empty_value( $value ) ) {
							$validation_errors[] = sprintf( __( '%s is required.', 'business-directory-plugin' ), $field->get_label() );
						}
					}
				}
			}

			if ( ! $validation_errors ) {
				$search = WPBDP__Listing_Search::from_request( $url_vars );
				$search->execute();
			} else {
				$searching = false;
			}
		}

		if ( $search_modal ) {
			$search   = WPBDP__Listing_Search::from_request( $_POST );
			$fallback = false;
		} else {
			// Show search form on the page if not in a modal and not searching.
			$fallback = ! $searching;
		}
		$search_form = '';
		$fields      = '<div class="wpbdp-form-fields">';
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
		$fields .= '</div>';

		// Allow [businessdirectory-search] shortcode to render form only filling current search fields.
		if ( ! empty( $this->in_shortcode ) ) {
			$searching = $searching && empty( $this->form_only );
		}

		if ( $searching ) {
			$results = $search->get_results();
			$args    = array(
				'post_type'        => WPBDP_POST_TYPE,
				'posts_per_page'   => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
				'paged'            => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
				'post__in'         => $results ? $results : array( 0 ),
				'orderby'          => wpbdp_get_option( 'listings-order-by', 'title' ),
				'order'            => wpbdp_get_option( 'listings-sort', 'ASC' ),
				'wpbdp_main_query' => true,
			);
			$args    = apply_filters( 'wpbdp_search_query_posts_args', $args, $search );

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
				'listings',
				array(
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
				'search_form_position' => wpbdp_get_option( 'search-form-in-results' ), // Deprecated.
				'fields'               => $fields,
				'searching'            => $searching,
				'results'              => $results,
				'form_only'            => isset( $this->form_only ) ? $this->form_only : false,
				'count'                => $this->get_count(),
				'fallback'             => $fallback,
			)
		);

		if ( $searching ) {
			wp_reset_query();
			wpbdp_pop_query();
		}

		return $html;
	}

	/**
	 * @since 5.13.1
	 */
	private function get_count() {
		global $wp_query;
		return $wp_query->found_posts;
	}

	/**
	 * Get adnvaced search modal content.
	 *
	 * @since 6.4
	 */
	public function ajax_get_search_content() {
		$html = $this->dispatch();

		wp_send_json_success( $html );
	}
}
