<?php

class WPBDP__Migrations__18_1 extends WPBDP__Migration {

    public function migrate() {
        global $wpdb;

        // wpbdp_payments: move from 'created_on' column to 'created_at'.
        $wpdb->query( "UPDATE {$wpdb->prefix}wpbdp_payments SET created_at = FROM_UNIXTIME(UNIX_TIMESTAMP(created_on))" );
    }

}
