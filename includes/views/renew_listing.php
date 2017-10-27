<?php
require_once( WPBDP_PATH . 'includes/helpers/class-authenticated-listing-view.php' );

/**
 * Renew listing view.
 */
class WPBDP__Views__Renew_Listing extends WPBDP__Authenticated_Listing_View {

    private $plan = null;
    private $payment_id = 0;


    public function dispatch() {
        global $wpdb;

        if ( ! wpbdp_get_option( 'listing-renewal' ) )
            return wpbdp_render_msg( _x( 'Listing renewal is disabled at this moment. Please try again later.', 'renewal', 'WPBDM' ), 'error' );

        $renewal_id = ! empty( $_GET['renewal_id'] ) ? $_GET['renewal_id'] : 0;

        if ( ! ( $this->listing = WPBDP_Listing::get( $renewal_id ) ) )
            return wpbdp_render_msg( _x( 'Your renewal ID is invalid. Please use the URL you were given on the renewal e-mail message.', 'renewal', 'WPBDM' ), 'error' );

        $this->_auth_required();

        $this->plan = $this->listing->get_fee_plan();

        // wpbdp_debug_e( $this->listing->get_status() );
        // if ( ! in_array( $this->listing->get_status(), array( 'expired', 'pending_renewal' ) ) ) {
        //     $html  = '';
        //     $html .= wpbdp_render_msg( _x( 'You don\'t have permission to access this page.', 'renewal', 'WPBDM' ), 'error' );
        //     return $html;
        // }

        if ( 'pending_renewal' == $this->listing->get_status() ) {
            // Check to see if there's a pending payment for this renewal. If there is, move to checkout.
            if ( $payment = WPBDP_Payment::objects()->get( array( 'listing_id' => $this->listing->get_id(), 'payment_type' => 'renewal', 'status' => 'pending' ) ) ) {
                return $this->_redirect( $payment->get_checkout_url() );
            }
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
        // FIXME: consider categories here before fees-revamp.
        $plans = wpbdp_get_fee_plans();

        if ( isset( $_POST['listing_plan'] ) ) {
            if ( $fee = wpbdp_get_fee( absint( $_POST['listing_plan'] ) ) ) {
                $payment = new WPBDP_Payment( array( 'listing_id' => $this->listing->get_id(), 'payment_type' => 'renewal' ) );
                $payment->payment_items[] = array(
                    'type' => 'plan',
                    'description' => sprintf( _x( 'Fee "%s" renewal.', 'listings', 'WPBDM' ), $fee->label ),
                    'amount' => $fee->amount,
                    'fee_id' => $fee->id,
                    'fee_days' => $fee->days,
                    'fee_images' => $fee->images,
                    'is_renewal' => true
                );
                if ( $payment->save() ) {
                    $this->listing->set_status( 'pending_renewal' );
                }


                $this->payment_id = $payment->id;
                return $this->_redirect( $payment->get_checkout_url() );
            }
        }

        return wpbdp_render( 'renew-listing', array( 'listing' => $this->listing,
                                                     'current_plan' => $this->plan->fee_id,
                                                     'plans' => $plans ) );
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
