<?php

class WPBDP__Authenticated_Listing_View extends WPBDP__View {

    protected function authenticate() {
        if ( ! $this->listing )
            die();

        if ( current_user_can( 'administrator' ) )
            return true;

        $user_id = intval( get_current_user_id() );
        $post = get_post( $this->listing->get_id() );

        if ( 'WPBDP__Views__Submit_Listing' == get_class( $this ) && empty( $this->editing ) && ! wpbdp_get_option( 'require-login' ) )
            return true;

        //if ( is_user_logged_in() && ( $this->listing->get_auth ) )

        $key_hash = ! empty( $_REQUEST['access_key_hash'] ) ? $_REQUEST['access_key_hash'] : '';

        if ( wpbdp_get_option( 'enable-key-access' ) && $key_hash )
            return $this->listing->validate_access_key_hash( $key_hash );

        return false;
    }


}
