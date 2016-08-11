<?php

class WPBDP__Views__Show_Listing extends WPBDP_NView {

    public function dispatch() {
        if ( ! wpbdp_user_can( 'view', null ) )
            $this->_http_404();


        // if ( 'publish' != get_post_status( $listing_id ) ) {
        //     if ( current_user_can( 'edit_posts' ) )
        //         $html .= wpbdp_render_msg( _x('This is just a preview. The listing has not been published yet.', 'preview', 'WPBDM') );

/*        // Handle ?v=viewname argument for alternative views (other than 'single').
        $view = '';
        if ( isset( $_GET['v'] ) )
            $view = wpbdp_capture_action_array( 'wpbdp_listing_view_' . trim( $_GET['v'] ), array( $listing_id ) );*/

        $html = wpbdp_render_listing( null, 'single', false, true );

        return $html;
    }

}
