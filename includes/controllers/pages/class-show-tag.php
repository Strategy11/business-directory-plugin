<?php

class WPBDP__Views__Show_Tag extends WPBDP__View {

    public function dispatch() {
        global $wp_query;

        wpbdp_push_query( $wp_query );

        $term = get_queried_object();

        // TODO: figure out why get_queried_object would return
        // something other thana taxonomy term when this method
        // is executed.
        if ( is_object( $term ) ) {
            $term->is_tag = true;

            $html = $this->_render( 'tag',
                                    array( 'title' => $term->name,
                                           'term' => $term,
                                           'query' => $wp_query,
                                           'in_shortcode' => false ),
                                    'page' );
        } else {
            $html = '';
        }

        wpbdp_pop_query();

        return $html;
    }

}
