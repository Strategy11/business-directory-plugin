<?php
/**
 * @package WPBDP/Views/Show Category
 */

class WPBDP__Views__Show_Category extends WPBDP__View {

    public function dispatch() {
        global $wp_query;

        wpbdp_push_query( $wp_query );

        $term = get_queried_object();

        $html = '';

        if ( is_object( $term ) && ! empty( $term->term_id ) ) {
			$html = $this->get_taxonomy_html( $term );
        }

        wpbdp_pop_query();

        return $html;
    }

	/**
	 * @since 6.2.2
	 * @return string
	 */
	protected function get_taxonomy_html( $term ) {
		global $wp_query;

		$searching    = ( ! empty( $_GET ) && ! empty( $_GET['kw'] ) );
		$term->is_tag = false;

		return $this->_render(
			'category',
			array(
				'title'        => $term->name,
				'category'     => $term,
				'query'        => $wp_query,
				'in_shortcode' => false,
				'is_tag'       => false,
				'searching'    => $searching,
			),
			$searching ? '' : 'page'
		);
	}
}
