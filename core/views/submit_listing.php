<?php

class WPBDP__Views__Submit_Listing extends WPBDP_NView {

    public function enqueue_resources() {
        wp_enqueue_script( 'wpbdp-submit-listing', WPBDP_URL . 'assets/js/submit-listing.min.js', array( 'jquery-ui-sortable' ) );
    }

    public function get_title() {
        return _x( 'Submit A Listing', 'views', 'WPBDM' );
    }

    public function dispatch() {
        if ( 'submit_listing' == wpbdp_current_view() && wpbdp_get_option( 'disable-submit-listing' ) ) {
            if ( current_user_can( 'administrator' ) )
                $msg = _x( '<b>View not available</b>. Do you have the "Disable Frontend Listing Submission?" setting checked?', 'templates', 'WPBDM' );
            else
                $msg = _x( 'View not available.', 'templates', 'WPBDM' );

            return wpbdp_render_msg( $msg, 'error');
        }

        // FIXME: move the actual view to this class.
        require_once ( WPBDP_PATH . 'core/view-submit-listing.php' );

        $submit_page = new WPBDP_Submit_Listing_Page( isset( $_REQUEST['listing_id'] ) ? $_REQUEST['listing_id'] : 0 );
        return $submit_page->dispatch();
    }

}
