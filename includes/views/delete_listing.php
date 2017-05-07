<?php
/**
 * @since 4.0
 */
class WPBDP__Views__Delete_Listing extends WPBDP_NView {

    function dispatch() {
        $listing_id = intval( $_REQUEST['listing_id'] );

        if ( ! wpbdp_user_can( 'delete', $listing_id ) ) {
            $html .= wpbdp_render_msg( _x( 'Please log in to delete the listing.', 'delete listing', 'WPBDM' ) );
            $html .= wpbdp_render( 'parts/login-required', array( 'show_message' => false ) );
            return $html;
        }

        $listing = WPBDP_Listing::get( $listing_id );
        $nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';

        if ( ! $listing )
            die();

        if ( $nonce && wp_verify_nonce( $nonce, 'delete listing ' . $listing->get_id() ) ) {
            $listing->delete();
            return wpbdp_render_msg( _x( 'Your listing has been deleted.', 'delete listing', 'WPBDM' ) );
        }

        return wpbdp_render( 'delete-listing-confirm', array( 'listing' => $listing,
                                                              'has_recurring' => $this->has_recurring_fee( $listing ) ) );
    }

    private function has_recurring_fee( &$listing ) {
        global $wpdb;

        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT 1 AS x FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND recurring = %d",
            $listing->get_id(),
            1 ) );
    }

}
