<?php

class WPBDP__Migrations__4_0 extends WPBDP__Migration {

    public function migrate() {
        $o = (bool) get_option( WPBDP_Settings::PREFIX . 'send-email-confirmation', false );

        if ( ! $o ) {
            update_option( WPBDP_Settings::PREFIX . 'user-notifications', array( 'listing-published' ) );
        }
        delete_option( WPBDP_Settings::PREFIX . 'send-email-confirmation' );
    }
}

