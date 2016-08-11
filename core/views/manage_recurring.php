<?php
/**
 * @since 3.5.3
 */ 
class WPBDP__Views__Manage_Recurring extends WPBDP_NView {

    private $subscriptions = array();


    public function __construct() { }

    public function dispatch() {
        if ( ! is_user_logged_in() ) {
            return wpbdp_render( 'parts/login-required', array(), false );
        }

        $this->subscriptions = $this->get_subscription_info();

        if ( ! $this->subscriptions )
            return wpbdp_render_msg( _x( 'You are not on recurring payments for any of your listings.', 'manage subscriptions', 'WPBDM' ) );

        $_SERVER['REQUEST_URI'] = remove_query_arg( array( 'subscription' ) );

        if ( isset( $_GET['cancel'] ) && $_GET['cancel'] )
            return $this->cancel_subscription();
        else
            return $this->subscription_list();
    }

    private function get_subscription_info() {
        global $wpdb;
        $listings = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_type = %s",
                                                    get_current_user_id(),
                                                    WPBDP_POST_TYPE ) );
        $info = array();

        foreach ( $listings as $listing_id ) {
            $listing = WPBDP_Listing::get( $listing_id );

            if ( ! $listing )
                continue;

            $categories = $listing->get_categories( 'all' );

            foreach ( $categories as $cat ) {
                if ( ! $cat->recurring )
                    continue;

                if ( ! isset( $info[ $listing_id ] ) )
                    $info[ $listing_id ] = array( 'listing' => $listing, 'subscriptions' => array() );

                $info[ $listing_id ]['subscriptions'][] = $cat;
            }
        }

        return $info;
    }

    private function subscription_list() {
        return wpbdp_render( 'manage-recurring', array( 'subscriptions' => $this->subscriptions ), false );
    }

    private function decode_subscription_hash( $hash = '' ) {
        $hash = urldecode( trim( $hash ) );

        if ( ! $hash )
            return false;

        parse_str( base64_decode( $hash ), $hash_data );

        if ( ! $hash_data || ! isset( $hash_data['listing_id'] ) || ! isset( $hash_data['category_id'] ) )
            return false;

        $listing = WPBDP_Listing::get( intval( $hash_data['listing_id'] ) );
        $category_id = intval( $hash_data['category_id'] );

        if ( ! $listing || ! $category_id || $listing->get_author_meta( 'ID' ) != get_current_user_id() )
            return false;

        $category_info = $listing->get_category_info( $category_id );

        if ( ! $category_info || ! $category_info->recurring )
            return false;

        return compact( 'listing', 'category_info' );
    }

    private function cancel_subscription() {
        $data = $this->decode_subscription_hash( isset( $_GET['cancel'] ) ? $_GET['cancel'] : '' );

        if ( ! $data )
            return wpbdp_render_msg( _x( 'Invalid subscription.', 'manage subscriptions', 'WPBDM' ), 'error' );

        $payment = (! empty( $data['category_info']->payment_id ) ) ? WPBDP_Payment::get( $data['category_info']->payment_id ) : false;

        $html = '';
        $html .= '<p><a href="' . esc_url( wpbdp_get_page_link( 'deletelisting', $data['listing']->get_id() ) ) . '">' . _x( '← Return to "Delete Listing".', 'manage subscriptions', 'WPBDM' ) . '</a></p>';

        if ( ! $payment && ! $data['category_info']->recurring_id ) {
            // This is a 'false' positive (probably an incomplete payment that was manually approved).
            $data['listing']->make_category_non_recurring( $data['category_info']->term_id );

            $html .= wpbdp_render_msg( _x( 'Subscription canceled.', 'manage subscriptions', 'WPBDM' ) );

            $this->subscriptions = $this->get_subscription_info(); // Refresh subscription info.

            $html .= $this->subscription_list();

            return $html;
        }

        global $wpbdp;
        $unsubscribe_form = $wpbdp->payments->render_unsubscribe_integration( $data['category_info'],
                                                                              $data['listing'] );

        $html .= wpbdp_render( 'manage-recurring-cancel', array( 'listing' => $data['listing'],
                                                                 'subscription' => $data['category_info'],
                                                                 'unsubscribe_form' => $unsubscribe_form ) );
        return $html;
    }

}
