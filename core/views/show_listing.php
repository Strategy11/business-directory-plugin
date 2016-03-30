<?php

class WPBDP__Views__Show_Listing extends WPBDP_NView {

    public function dispatch() {
/*        // Handle ?v=viewname argument for alternative views (other than 'single').
        $view = '';
        if ( isset( $_GET['v'] ) )
            $view = wpbdp_capture_action_array( 'wpbdp_listing_view_' . trim( $_GET['v'] ), array( $listing_id ) );*/

        $html = wpbdp_render_listing( null, 'single', false, true );

        return $html;
//        return 'HI THERE';
    }

}
