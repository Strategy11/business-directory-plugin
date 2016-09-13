<?php

class WPBDP__Migration {

    public function request_manual_upgrade( $callback ) {
        update_option( 'wpbdp-manual-upgrade-pending', array( get_class( $this ), $callback ) );
    }

}
