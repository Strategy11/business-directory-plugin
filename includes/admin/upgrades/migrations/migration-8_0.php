<?php

class WPBDP__Migrations__8_0 extends WPBDP__Migration {

    public function migrate() {
        if ( get_option( WPBDP_Settings::PREFIX . 'show-search-form-in-results', false ) )
            update_option( WPBDP_Settings::PREFIX . 'search-form-in-results', 'above' );
        delete_option( WPBDP_Settings::PREFIX . 'show-search-form-in-results' );
    }

}
