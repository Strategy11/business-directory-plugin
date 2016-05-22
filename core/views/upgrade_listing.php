<?php
class WPBDP__Views__Upgrade_Listing extends WPBDP_NView {

    private $listing = null;
    private $upgrades_api = null;


    public function __construct() {
        $this->upgrades_api = wpbdp_listing_upgrades_api();
    }

    public function dispatch() {
        $this->listing = WPBDP_Listing::get( intval( $_GET['listing_id'] ? $_GET['listing_id'] : 0 ) );

        if ( ! $this->listing || ! wpbdp_user_can( 'upgrade-to-sticky', $this->listing->get_id() ) )
            return wpbdp_render_msg( _x( 'Invalid link followed.', 'listing upgrade', 'WPBDM' ), 'error' );

        $sticky_info = $this->upgrades_api->get_info( $this->listing->get_id() );

        if ( $sticky_info->pending ) {
            $html  = '';
            $html .= wpbdp_render_msg( _x( 'Your listing is already pending approval for "featured" status.', 'templates', 'WPBDM' ) );
            $html .= sprintf('<a href="%s">%s</a>', $this->listing->get_permalink(), _x( 'Return to listing.', 'templates', 'WPBDM' ) );
            return $html;
        }

        if ( isset( $_POST['do_upgrade'] ) )
            return $this->checkout();

        return $this->upgrade_selection();
    }

    private function upgrade_selection() {
        $sticky_info = $this->upgrades_api->get_info( $this->listing->get_id() );

        return wpbdp_render( 'listing-upgradetosticky', array( 'listing' => $this->listing,
                                                               'featured_level' => $sticky_info->upgrade ), false );
    }

    private function checkout() {
        $sticky_info = $this->upgrades_api->get_info( $this->listing->get_id() );

        if ( $sticky_info->pending || ! $sticky_info->upgradeable || ! wpbdp_payments_possible() )
            return;

        $payment = new WPBDP_Payment( array( 'listing_id' => $this->listing->get_id() ) );
        $payment->add_item( 'upgrade',
                            $sticky_info->upgrade->cost,
                            _x( 'Listing upgrade to featured', 'submit', 'WPBDM' ) );
        $payment->save();
        update_post_meta( $this->listing->get_id(), '_wpbdp[sticky]', 'pending' ); // FIXME: maybe this should be set automatically when saving the payment?

        $checkout = wpbdp_load_view( 'checkout', $payment );
        return $checkout->dispatch();
    }

}
