<?php

class WPBDP__Views__All_Listings extends WPBDP_NView {

    public function get_title() {
        return _x( 'View All Listings', 'views', 'WPBDM' );
    }

    public function dispatch() {
        $this->include_buttons = ! isset( $this->include_buttons ) ? true : $this->include_buttons;
        $args_ = isset( $this->query_args ) ? $this->query_args : array();

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
            $args['posts_per_page'] = $args_['numberposts'];

        if ( isset( $args_['items_per_page'] ) )
            $args['posts_per_page'] = $args_['items_per_page'];

        if ( ! empty( $args_['author'] ) )
            $args['author'] = $args_['author'];

        $args = array_merge( $args, $args_ );

        $q = new WP_Query( $args );
        wpbdp_push_query( $q );

        $template_args = array( '_id' => $this->include_buttons ? 'all_listings' : 'listings',
                                '_wrapper' => $this->include_buttons ? 'page' : '',
                                '_bar' => ( ! empty ( $args['tax_query'] ) ? false : ( $this->include_buttons ? true : false ) ),
                                'query' => $q );

        $html = wpbdp_x_render( 'listings', $template_args );
        wp_reset_postdata();
        wpbdp_pop_query( $q );

        return $html;
    }

}
