<?php
/**
 * @since 4.0
 */
class WPBDP__Views__Manage_Listings extends WPBDP_NView {

    public function dispatch() {
        $current_user = is_user_logged_in() ? wp_get_current_user() : null;

        if ( ! $current_user )
            return wpbdp_render( 'parts/login-required' );

        $args = array(
            'post_type' => WPBDP_POST_TYPE,
            'post_status' => 'publish',
            'paged' => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
            'author' => $current_user->ID,
            'wpbdp_main_query' => true
        );
        $q = new WP_Query( $args );
        wpbdp_push_query( $q );

        $html = $this->_render_page( 'manage_listings', array( 'current_user' => $current_user,
                                                               'query' => $q ) );

        wpbdp_pop_query();

        return $html;
    }

}
