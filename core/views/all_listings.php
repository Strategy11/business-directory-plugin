<?php

class WPBDP__Views__All_Listings extends WPBDP_NView {

    public function dispatch() {
        $this->include_buttons = ! isset( $this->include_buttons ) ? true : $this->include_buttons;

        $paged = get_query_var( 'page' ) ? get_query_var( 'page' ) : ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 );
        $args = array(
            'post_type' => WPBDP_POST_TYPE,
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'post_status' => 'publish',
            'paged' => intval($paged),
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC'),
            'wpbdp_main_query' => true
        );
        if ( isset( $args_['numberposts'] ) )
            $args['numberposts'] = $args_['numberposts'];

        if ( ! empty( $args_['author'] ) )
            $args['author'] = $args_['author'];

        $q = new WP_Query( $args );
        wpbdp_push_query( $q );

        // TODO: review use of wpbdp_before_viewlistings_page, wpbdp_after_viewlistings_page.
        $html = wpbdp_x_render( 'listings', array( '_id' => $this->include_buttons ? 'all_listings' : 'listings',
                                                   '_wrapper' => $this->include_buttons ? 'page' : '',
                                                   '_bar' => $this->include_buttons ? true : false,
                                                   'query' => $q ) );
        wp_reset_postdata();
        wpbdp_pop_query( $q );

        return $html;
    }

}
