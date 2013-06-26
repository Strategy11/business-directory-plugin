<?php
/*
 * Renew listing view
 */

require_once( WPBDP_PATH . 'api/views.php' );


class WPBDP_RenewListingPage extends WPBDP_View {

    public function get_page_name() {
        return 'renewlisting';
    }

    public function dispatch() {
        if ( !wpbdp_get_option( 'listing-renewal' ) )
            return wpbdp_render_msg( _x( 'Listing renewal is disabled at this moment. Please try again later.', 'renewal', 'WPBDM' ),
                                    'error' );

        global $wpdb;

        $feeinfo = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d AND expires_on IS NOT NULL AND email_sent <> 0",
                                                   intval( wpbdp_getv( $_REQUEST, 'renewal_id', 0 ) ) ) );
        if ( !$feeinfo)
            return wpbdp_render_msg( _x( 'Your renewal ID is invalid. Please use the URL you were given on the renewal e-mail message.', 'renewal', 'WPBDM' ), 'error' );

        $this->feeinfo = $feeinfo;
        $this->listing = get_post( $feeinfo->listing_id );

        if ( !$this->listing || $this->listing->post_type != WPBDP_POST_TYPE )
            return;

        return $this->renew_listing();
    }

    public function renew_listing() {
        global $wpdb;

        // Check there are no currently pending transactions for this same renewal
        if ( $tid = $this->check_pending_transactions() )
            return wpbdp_render_msg( sprintf( _x( 'There is a transaction (#%d) awaiting approval for this renewal in our system. Please contact the site administrator.', 'renewal', 'WPBDM' ),
                                                 $tid ), 'error' );

        if ( isset( $_POST['cancel-renewal'] ) ) {
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d",
                                          $this->feeinfo->id ) );

            // delete all pending transactions relating this renewal or category
            $transactions = wpbdp_payments_api()->get_transactions( $this->listing->ID );
            foreach ( $transactions as &$t ) {
                if ( $t->payment_type != 'renewal' )
                    continue;

                $extra_data = is_array( $t->extra_data ) ? $t->extra_data : array();
                if ( isset( $extra_data['renewal_id'] ) && $extra_data['renewal_id'] == $this->feeinfo->id )
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE id = %d", $t->id ) );
            }

            return wpbdp_render_msg( _x( 'Your renewal was successfully cancelled.', 'renewal', 'WPBDM' ) );

        }

        $available_fees = wpbdp_get_fees_for_category( $this->feeinfo->category_id ) or die( '' );

        if ( isset( $_POST['fees'] ) && isset( $_POST['fees'][ $this->feeinfo->category_id ] ) ) {
            if ( $fee = wpbdp_get_fee( $_POST['fees'][ $this->feeinfo->category_id ] ) ) {
                // TODO: check fee works with category_id

                $listings_api = wpbdp_listings_api();
                $payments_api = wpbdp_payments_api();

                if ( $transaction_id = $listings_api->renew_listing( $this->feeinfo->id, $fee ) ) {
                    return $payments_api->render_payment_page( array(
                        'title' => _x('Renew Listing', 'templates', 'WPBDM'),
                        'item_text' => _x('Pay %1$s renewal fee via %2$s.', 'templates', 'WPBDM'),
                        'transaction_id' => $transaction_id,
                    ) );
                }
            }
        }

        return wpbdp_render( 'renew-listing',
                             array( 'listing' => $this->listing,
                                    'category' => get_term( $this->feeinfo->category_id, WPBDP_CATEGORY_TAX ),
                                    'fees' => $available_fees
                                  ) );
    }

    private function check_pending_transactions() {
        $payments_api = wpbdp_payments_api();
        $transactions = $payments_api->get_transactions( $this->listing->ID );

        foreach ( $transactions as &$t ) {
            if ( $t->payment_type != 'renewal' )
                continue;

            $extra_data = $t->extra_data;

            if ( isset( $extra_data['renewal_id'] ) && $extra_data['renewal_id'] == $this->feeinfo->id && $t->status == 'pending' )
                return intval( $t->id );
        }

        return 0;
    }

}