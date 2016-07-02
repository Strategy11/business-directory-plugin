<?php
/**
 * Renew listing view.
 */
class WPBDP__Views__Renew_Listing extends WPBDP_NView {

    private $category = null;
    private $listing = null;


    public function dispatch() {
        if ( ! wpbdp_get_option( 'listing-renewal' ) )
            return wpbdp_render_msg( _x( 'Listing renewal is disabled at this moment. Please try again later.', 'renewal', 'WPBDM' ), 'error' );

       if ( ! $this->obtain_renewal_info() )
            return wpbdp_render_msg( _x( 'Your renewal ID is invalid. Please use the URL you were given on the renewal e-mail message.', 'renewal', 'WPBDM' ), 'error' );

        if ( ! wpbdp_user_can( 'edit', $this->listing->get_id() ) ) {
            $html  = '';
//            $html .= wpbdp_render_msg( _x( 'You don\'t have permission to access this page. Please login.', 'renewal', 'WPBDM' ), 'error' );
            $html .= wpbdp_render( 'parts/login-required', array(), false );
            return $html;
        }

        if ( $this->category->recurring )
            return $this->recurring_management();

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
                                    array( 'fee_id' => $fee_id, 'fee_days' => $fee->days, 'fee_images' => $fee->images, 'is_renewal' => true ),
                                    $this->category->id,
                                    $fee_id );
                $payment->save();

                $this->category->payment_id = $payment->get_id();
                return $this->checkout();
            }
        }

        return wpbdp_render( 'renew-listing', array( 'listing' => $this->listing, 'category' => $this->category, 'fees' => $fees ) );
    }

    private function checkout() {
        $payment = WPBDP_Payment::get( $this->category->payment_id );

        if ( ! $payment )
            return wpbdp_render_msg( _x( 'Invalid renewal state.', 'renewal', 'WPBDM' ), 'error' );

        $checkout = wpbdp_load_view( 'checkout', $payment );
        return $checkout->dispatch();
    }

    private function recurring_management() {
        global $wpbdp;

        $html  = '';
        $html .=  '<div id="wpbdp-renewal-page" class="wpbdp-renewal-page businessdirectory-renewal businessdirectory wpbdp-page">';
        $html .= '<h2>' . _x('Recurring Fee Management', 'templates', 'WPBDM') . '</h2>';
        $html .= '<p>' . _x( 'Because you are on a recurring fee plan you don\'t have to renew your listing right now as this will be handled automatically when renewal comes.', 'renew', 'WPBDM' ) . '</p>';

        $html .= '<h4>' . _x( 'Current Fee Details', 'renewal', 'WPBDM' ) . '</h4>';
        $html .= '<dl class="recurring-fee-details">';
        $html .= '<dt>' . _x( 'Number of images:', 'renewal', 'WPBDM' ) . '</dt>';
        $html .= '<dd>' . $this->category->fee_images . '</dd>';
        $html .= '<dt>' . _x( 'Expiration date:', 'renewal', 'WPBDM' ) . '</dt>';
        $html .= '<dd>' . date_i18n( get_option( 'date_format' ), strtotime( $this->category->expires_on ) ) . '</dd>';
        $html .= '</dl>';

        $html .= '<p>' . _x( 'However, if you want to cancel your subscription you can do that on this page. When the renewal time comes you\'ll be able to change your settings again.', 'renew', 'WPBDM' ) . '</p>';
        $html .= $wpbdp->payments->render_unsubscribe_integration( $this->category, $this->listing );

        $html .= '</div>';

        return $html;
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

}
