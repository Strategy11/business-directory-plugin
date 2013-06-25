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

}
/*
    public function renew_listing() {
        $listings_api = wpbdp_listings_api();
        $fees_api = wpbdp_fees_api();
        $payments_api = wpbdp_payments_api();

        $available_fees = $fees_api->get_fees_for_category( $fee_info->category_id );

        if ( isset( $_POST['fee_id'] ) ) {
            $fee = $fees_api->get_fee_by_id( $_POST['fee_id'] );

            if ( !$fee )
                return;

            if ( $transaction_id = $listings_api->renew_listing( $_GET['renewal_id'], $fee ) ) {
                return $payments_api->render_payment_page( array(
                    'title' => _x('Renew Listing', 'templates', 'WPBDM'),
                    'item_text' => _x('Pay %1$s renewal fee via %2$s.', 'templates', 'WPBDM'),
                    'transaction_id' => $transaction_id,
                ) );
            }
        }

        return wpbdp_render( 'renewlisting-fees', array(
            'fee_options' => $available_fees,
            'category' => get_term( $fee_info->category_id, WPBDP_CATEGORY_TAX ),
            'listing' => $post
        ), false );
    }*/