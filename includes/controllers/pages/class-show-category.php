<?php
/**
 * @package WPBDP/Views/Show Category
 */

class WPBDP__Views__Show_Category extends WPBDP__View {

    public function dispatch() {
        global $wp_query;

        wpbdp_push_query( $wp_query );

        $term = get_queried_object();

        $searching = ( ! empty( $_GET ) && ! empty( $_GET['kw'] ) );

        $html = '';

        if ( is_object( $term ) ) {

			add_filter( 'the_title', array( &$this, 'set_tax_title' ) );
			add_filter( 'post_thumbnail_html', array( &$this, 'remove_tax_thumbnail' ) );

            $term->is_tag = false;

            $html = $this->_render(
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

        wpbdp_pop_query();

        return $html;
    }

    /**
     * Since the category page thinks it's a normal post, override the global post.
     * This would be better to change the category output, rather than using a "page".
     * See WPBDP__Dispatcher.
     *
     * @since x.x
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
	 * @since x.x
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
