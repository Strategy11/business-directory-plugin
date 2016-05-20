<?php

class WPBDP__Views__Submit_Listing extends WPBDP_NView {

    public function dispatch() {
        // FIXME: move the actual view to this class.
        require_once ( WPBDP_PATH . 'core/view-submit-listing.php' );

        $submit_page = new WPBDP_Submit_Listing_Page( isset( $_REQUEST['listing_id'] ) ? $_REQUEST['listing_id'] : 0 );
        return $submit_page->dispatch();
    }

}
