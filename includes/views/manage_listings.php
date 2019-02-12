<?php
/**
 * Manage Listings View allows users to see, edit and delete their listings.
 *
 * @package BDP/Includes/Views
 */

// phpcs:disable

/**
 * @since 4.0
 * @SuppressWarnings(PHPMD)
 */
class WPBDP__Views__Manage_Listings extends WPBDP__View {

    public function dispatch() {
        $current_user = is_user_logged_in() ? wp_get_current_user() : null;

        if ( ! $current_user ) {
            $login_msg = _x( 'Please <a>login</a> to manage your listings.', 'view:manage-listings', 'WPBDM' );
            $login_msg = str_replace( '<a>', '<a href="' . esc_url( add_query_arg( 'redirect_to', urlencode( apply_filters( 'the_permalink', get_permalink() ) ), wpbdp_url( 'login' ) ) ) . '">', $login_msg );
            return $login_msg;
        }

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
                                                               'query' => $q,
                                                               '_bar' => $this->show_search_bar ) );

        wpbdp_pop_query();

        return $html;
    }

}
