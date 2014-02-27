<?php
require_once( WPBDP_PATH . 'core/class-view.php' );

/**
 * Renew listing view.
 */
class WPBDP_Renew_Listing_Page extends WPBDP_View {

    private $category = null;
    private $listing = null;

    public function get_page_name() {
        return 'renewlisting';
    }

    public function dispatch() {
        if ( ! wpbdp_get_option( 'listing-renewal' ) )
            return wpbdp_render_msg( _x( 'Listing renewal is disabled at this moment. Please try again later.', 'renewal', 'WPBDM' ), 'error' );

       if ( ! $this->obtain_renewal_info() )
            return wpbdp_render_msg( _x( 'Your renewal ID is invalid. Please use the URL you were given on the renewal e-mail message.', 'renewal', 'WPBDM' ), 'error' );

        if ( ! wpbdp_user_can( 'edit', $this->listing->get_id() ) )
            return wpbdp_render_msg( _x( 'You don\'t have permission to access this page.', 'renewal', 'WPBDM' ), 'error' );

        if ( $this->category->recurring )
            die('Handle recurring!');

        if ( $this->category->payment_id )
            return $this->checkout();

        return $this->fee_selection();
    }

    private function fee_selection() {
        // Cancel renewal?
        if ( isset( $_POST['cancel-renewal'] ) ) {
            $this->listing->remove_category( $this->category->id, true );
            
            if ( ! $this->listing->get_categories( 'all' ) )
                $this->listing->delete();

            return wpbdp_render_msg( _x( 'Your renewal was successfully cancelled.', 'renewal', 'WPBDM' ) );
        }

        $fees = wpbdp_get_fees_for_category( $this->category->id );

        if ( isset( $_POST['fees'] ) && isset( $_POST['fees'][ $this->category->id ] ) ) {
            $fee_id = intval( $_POST['fees'][ $this->category->id ] );

            if ( $fee = wpbdp_get_fee( $fee_id ) ) {
                $payment = new WPBDP_Payment( array( 'listing_id' => $this->listing->get_id() ) );
                $payment->add_item( 'fee',
                                    $fee->amount,
                                    sprintf( _x( 'Fee "%s" renewal for category "%s"', 'listings', 'WPBDM' ),
                                             $fee->label,
                                             wpbdp_get_term_name( $this->category->id ) ),
                                    array( 'fee_id' => $fee_id, 'fee_days' => $fee->days, 'fee_images' => $fee->images ),
                                    $this->category->id,
                                    $fee_id );
                $payment->save();
                return $this->dispatch();
            }
        }

        return wpbdp_render( 'renew-listing', array( 'listing' => $this->listing, 'category' => $this->category, 'fees' => $fees ) );
    }

    private function checkout() {
        $payment = WPBDP_Payment::get( $this->category->payment_id );

        if ( ! $payment )
            return wpbdp_render_msg( _x( 'Invalid renewal state.', 'renewal', 'WPBDM' ), 'error' );

        return wpbdp_render( 'renew-listing', array( 'listing' => $this->listing,
                                                     'category' => $this->category,
                                                     'payment' => $payment ) );
    }

    private function obtain_renewal_info() {
        $renewal_id = urldecode( trim( $_GET['renewal_id'] ) );

        if ( ! $renewal_id )
            return false;

        parse_str( base64_decode( $renewal_id ), $renewal_data );

        if ( ! $renewal_data || ! isset( $renewal_data['listing_id'] ) || ! isset( $renewal_data['category_id'] ) )
            return false;

        $listing = WPBDP_Listing::get( intval( $renewal_data['listing_id'] ) );
        $category_id = intval( $renewal_data['category_id'] );
        
        if ( ! $listing )
            return false;

        $category_info = $listing->get_category_info( $category_id );

        if ( ! $category_info )
            return false;

        $this->category = $category_info;
        $this->listing = $listing;

        return true;
    }
        //     if ( isset( $_POST['fees'] ) && isset( $_POST['fees'][ $this->feeinfo->category_id ] ) ) {
    //         if ( $fee = wpbdp_get_fee( $_POST['fees'][ $this->feeinfo->category_id ] ) ) {
    //             // TODO: check fee works with category_id

    //             $listings_api = wpbdp_listings_api();
    //             $payments_api = wpbdp_payments_api();

    //             if ( $transaction_id = $listings_api->renew_listing( $this->feeinfo->id, $fee ) ) {
    //                 return $payments_api->render_payment_page( array(
    //                     'title' => _x('Renew Listing', 'templates', 'WPBDM'),
    //                     'item_text' => _x('Pay %1$s renewal fee via %2$s.', 'templates', 'WPBDM'),
    //                     'transaction_id' => $transaction_id,
    //                 ) );
    //             }
    //         }
    //     }
    // }

}