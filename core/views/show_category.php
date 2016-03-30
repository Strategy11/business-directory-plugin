<?php

class WPBDP__Views__Show_Category extends WPBDP_NView {

    public function dispatch() {
        global $wp_query;

        $term = get_queried_object();
        wpbdp_push_query( $wp_query );

        $html = $this->_render( 'category',
                                array( 'title' => $term->name,
                                       'category' => $term,
                                       'query' => $wp_query,
                                       'in_shortcode' => false,
                                       'is_tag' => false ) );
        wpbdp_pop_query();


        return $html;
    }

}
