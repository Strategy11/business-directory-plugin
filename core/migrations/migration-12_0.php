<?php

class WPBDP__Migrations__12_0 {

    public function migrate() {
        delete_transient( 'wpbdp-themes-updates' );
    }

}
