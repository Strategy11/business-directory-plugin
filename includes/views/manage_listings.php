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

    public function __construct( $args = null ) {
        parent::__construct( $args );
        add_filter( 'wpbdp_form_field_html_value', array( $this, 'remove_expired_listings_title_links' ), 10, 3 );
        add_filter( 'wpbdp_user_can_view', array( $this, 'maybe_remove_listing_buttons'), 20, 3 );
        add_filter( 'wpbdp_user_can_edit', array( $this, 'maybe_remove_listing_buttons'), 20, 3 );
        add_filter( 'wpbdp_user_can_flagging', array( $this, 'maybe_remove_listing_buttons'), 20, 3 );
    }

    public function dispatch() {
        $current_user = is_user_logged_in() ? wp_get_current_user() : null;

        if ( ! $current_user ) {
            $login_msg = _x( 'Please <a>login</a> to manage your listings.', 'view:manage-listings', 'WPBDM' );
            $login_msg = str_replace(
                '<a>',
                '<a href="' . esc_attr( add_query_arg( 'redirect_to', urlencode( apply_filters( 'the_permalink', get_permalink() ) ), wpbdp_url( 'login' ) ) ) . '">',
                $login_msg
            );
            return $login_msg;
        }

        $args = array(
            'post_type' => WPBDP_POST_TYPE,
            'post_status' => array( 'publish', 'pending', 'draft' ),
            'paged' => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
            'author' => $current_user->ID,
            'wpbdp_main_query' => true
        );
        $q = new WP_Query( $args );
        wpbdp_push_query( $q );

        $html = $this->_render_page( 'manage_listings', array( 'current_user' => $current_user,
                                                               'query' => $q,
                                                               '_bar' => ! empty( $this->show_search_bar ) ? $this->show_search_bar : 'false' ) );

        wpbdp_pop_query();

        return $html;
    }

    public function remove_expired_listings_title_links( $value, $listing_id, $field ) {
        if ( 'title' !== $field->get_association() || current_user_can( 'administrator' ) ) {
            return $value;
        }
        
        $listing         = wpbdp_get_listing( $listing_id );
        $listing_status  = $listing->get_status();

        if ( 'complete' === $listing_status ) {
            return $value;
        }

        return sprintf( '%s (%s)', $field->plain_value( $listing_id ), $listing_status );
    }

    public function maybe_remove_listing_buttons( $res, $listing_id, $user_id ) {
        if ( current_user_can( 'administrator' ) ) {
            return $res;
        }

        $listing         = wpbdp_get_listing( $listing_id );
        $listing_status  = $listing->get_status();

        if ( 'complete' === $listing_status ) {
            return $res;
        }

        return false;

    }
}
