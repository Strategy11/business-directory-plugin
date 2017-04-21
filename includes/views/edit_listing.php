<?php
require_once( WPBDP_PATH . 'includes/views/submit_listing.php' );


class WPBDP__Views__Edit_Listing extends WPBDP__Views__Submit_Listing {

    public function __construct( $args = null ) {
        parent::__construct();
        $this->editing = true;
    }

}

