<?php
require_once( WPBDP_PATH . 'includes/helpers/class-authenticated-listing-view.php' );

/**
 * @since 4.0
 */
class WPBDP__Views__Delete_Listing extends WPBDP__Authenticated_Listing_View {

    public function dispatch() {
        $this->listing = WPBDP_Listing::get( intval( $_REQUEST['listing_id'] ) );
        $this->_auth_required();

        $nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';

        if ( $nonce && wp_verify_nonce( $nonce, 'delete listing ' . $this->listing->get_id() ) ) {
            $this->listing->delete();
            return wpbdp_render_msg( _x( 'Your listing has been deleted.', 'delete listing', 'WPBDM' ) );
        }

        return wpbdp_render( 'delete-listing-confirm', array( 'listing' => $this->listing,
                                                              'has_recurring' => $this->has_recurring_fee() ) );
    }

    private function has_recurring_fee() {
        global $wpdb;

        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT 1 AS x FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d AND is_recurring = %d",
            $this->listing->get_id(),
            1 ) );
    }

}
