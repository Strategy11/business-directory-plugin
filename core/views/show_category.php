<?php

class WPBDP__Views__Show_Category extends WPBDP_NView {

    public function dispatch() {
        global $wp_query;

        wpbdp_push_query( $wp_query );

        $term = get_queried_object();
        $term->is_tag = false;

        $html  = '';
        $html .= $this->_render( 'category',
                                 array( 'title' => $term->name,
                                        'category' => $term,
                                        'query' => $wp_query,
                                        'in_shortcode' => false,
                                        'is_tag' => false ),
                                 'page' );
        wpbdp_pop_query();

        // if ( is_array( $category_id ) ) {
        //     $title = '';
        //     $category = null;
        // } else {
        //     $category = get_term( $category_id, WPBDP_CATEGORY_TAX );
        //     $title = esc_attr( $category->name );
        //
        //     if ( $in_listings_shortcode )
        //         $title = '';
        // }


        return $html;
    }

}
