<?php
/**
 * Renew listing view.
 */
class WPBDP__Views__Renew_Listing extends WPBDP_NView {

    private $listing = null;
    private $plan = null;
    private $payment_id = 0;


    public function dispatch() {
        global $wpdb;

        if ( ! wpbdp_get_option( 'listing-renewal' ) )
            return wpbdp_render_msg( _x( 'Listing renewal is disabled at this moment. Please try again later.', 'renewal', 'WPBDM' ), 'error' );

        if ( ! ( $this->listing = WPBDP_Listing::get( $_GET['renewal_id'] ) ) )
            return wpbdp_render_msg( _x( 'Your renewal ID is invalid. Please use the URL you were given on the renewal e-mail message.', 'renewal', 'WPBDM' ), 'error' );

        $this->plan = $this->listing->get_fee_plan();

        if ( ! wpbdp_user_can( 'edit', $this->listing->get_id() ) || 'ok' != $this->plan->status ) {
            $html  = '';
            $html .= wpbdp_render_msg( _x( 'You don\'t have permission to access this page. Please login.', 'renewal', 'WPBDM' ), 'error' );
            $html .= wpbdp_render( 'parts/login-required', array(), false );
            return $html;
        }

        // Check if there's a pending payment for this renewal. If there is, move to checkout.
        $this->payment_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT pi.payment_id FROM {$wpdb->prefix}wpbdp_payments_items pi INNER JOIN {$wpdb->prefix}wpbdp_payments p ON p.id = pi.payment_id WHERE pi.item_type = %s AND p.status = %s AND p.listing_id = %d",
            'plan_renewal',
            'pending',
            $this->listing->get_id()
        ) );
        if ( $this->payment_id ) {
            $html  = '';
            $html .= wpbdp_render_msg( _x( 'There\'s a renewal already in process. Please continue with the checkout process.', 'renewal', 'WPBDM' ) );
            $html .= $this->checkout();
            return $html;
        }

        if ( $this->plan->is_recurring )
            return $this->recurring_management();

        // Handle removal.
        if ( isset( $_POST['cancel-renewal'] ) ) {
            if ( $this->listing->delete() )
                return wpbdp_render_msg( _x( 'Your listing has been removed from the directory.', 'renewal', 'WPBDM' ) );
            else
                return wpbdp_render_msg( _x( 'Could not remove listing from directory.', 'renewal', 'WPBDM' ), 'error' );
        }

        return $this->plan_selection();
    }

    private function plan_selection() {
        // FIXME: consider categories here before next-release.
        $plans = WPBDP_Fee_Plan::find( 'all' );

        if ( isset( $_POST['listing_plan'] ) ) {
            if ( $fee = wpbdp_get_fee( absint( $_POST['listing_plan'] ) ) ) {
                $payment = new WPBDP_Payment( array( 'listing_id' => $this->listing->get_id() ) );
                $payment->add_item( 'plan_renewal',
                                    $fee->amount,
                                    sprintf( _x( 'Fee "%s" renewal.', 'listings', 'WPBDM' ),
                                             $fee->label ),
                                    array( 'fee_id' => $fee_id, 'fee_days' => $fee->days, 'fee_images' => $fee->images, 'is_renewal' => true ),
                                    $fee->id );
                $payment->save();

                $this->payment_id = $payment->get_id();
                return $this->checkout();
            }
        }

        return wpbdp_render( 'renew-listing', array( 'listing' => $this->listing,
                                                     'current_plan' => $this->plan->fee,
                                                     'plans' => $plans ) );
    }

    private function checkout() {
        $payment = WPBDP_Payment::get( $this->payment_id );

        if ( ! $payment )
            return wpbdp_render_msg( _x( 'Invalid renewal state.', 'renewal', 'WPBDM' ), 'error' );

        $checkout = wpbdp_load_view( 'checkout', $payment );
        return $checkout->dispatch();
    }

    // FIXME: before next-release
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

}
