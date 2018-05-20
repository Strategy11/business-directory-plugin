<?php
/**
 * @package WPBDP/Views/Show Category
 */

// phpcs:disable
class WPBDP__Views__Show_Category extends WPBDP__View {

    public function dispatch() {
        global $wp_query;

        wpbdp_push_query( $wp_query );

        $term = get_queried_object();

        $searching = ( ! empty( $_GET ) && ! empty( $_GET['kw'] ) );

        $html = '';

        if ( is_object( $term ) ) {
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

        // if ( is_array( $category_id ) ) {
        // $title = '';
        // $category = null;
        // } else {
        // $category = get_term( $category_id, WPBDP_CATEGORY_TAX );
        // $title = esc_attr( $category->name );
        //
        // if ( $in_listings_shortcode )
        // $title = '';
        // }
        return $html;
    }

}
