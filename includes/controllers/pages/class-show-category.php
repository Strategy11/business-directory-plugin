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

        if ( is_object( $term ) ) {

			add_filter( 'the_title', array( &$this, 'set_tax_title' ) );
			add_filter( 'post_thumbnail_html', array( &$this, 'remove_tax_thumbnail' ) );

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

	/**
	 * Since the category page thinks it's a normal post, override the global post.
	 * This would be better to change the category output, rather than using a "page".
	 * See WPBDP__Dispatcher.
	 *
	 * @since 6.2.2
	 * @return string
	 */
	public function set_tax_title( $title ) {
		if ( in_the_loop() ) {
			return $title;
		}

		$term = get_queried_object();
		return is_object( $term ) ? $term->name : '';
	}

	/**
	 * Prevent a post thumbnail from showing on the page before the loop.
	 *
	 * @since 6.2.2
	 * @return string
	 */
	public function remove_tax_thumbnail( $thumbnail ) {
		if ( in_the_loop() ) {
			remove_filter( 'post_thumbnail_html', array( &$this, 'remove_tax_thumbnail' ) );

			return $thumbnail;
		}

		return '';
	}
}
