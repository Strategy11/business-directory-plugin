<?php

class WPBDP__Views__Show_Tag extends WPBDP_NView {

    public function dispatch() {
        global $wp_query;

        wpbdp_push_query( $wp_query );

        $term = get_queried_object();
        $term->is_tag = true;

        $html = $this->_render( 'tag',
                                array( 'title' => $term->name,
                                       'term' => $term,
                                       'query' => $wp_query,
                                       'in_shortcode' => false ),
                                'page' );
        wpbdp_pop_query();

        return $html;
    }

}
