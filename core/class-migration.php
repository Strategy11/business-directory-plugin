<?php

class WPBDP__Migration {

    public function request_manual_upgrade( $callback ) {
        return $this->request_manual_upgrade_with_configuration( $callback, null );
    }

    public function request_manual_upgrade_with_configuration( $callback, $config_callback ) {
        update_option( 'wpbdp-manual-upgrade-pending', array( 'callback' => array( get_class( $this ), $callback ),
                                                              'config_callback' => $config_callback ? array( get_class( $this ), $config_callback ) : null ) );
    }

    public function manual_upgrade_configured() {
        $manual_upgrade = get_option( 'wpbdp-manual-upgrade-pending', false );

        if ( ! $manual_upgrade || ! is_array( $manual_upgrade ) )
            return;

        $manual_upgrade['configured'] = true;
        update_option( 'wpbdp-manual-upgrade-pending', $manual_upgrade );
    }

    public function set_manual_upgrade_config( $conf ) {
        $manual_upgrade = get_option( 'wpbdp-manual-upgrade-pending', false );
        $manual_upgrade = is_array( $manual_upgrade ) ? $manual_upgrade : array();

        $manual_upgrade['config'] = $conf;
        update_option( 'wpbdp-manual-upgrade-pending', $manual_upgrade );
    }

    public function get_config() {
        $manual_upgrade = get_option( 'wpbdp-manual-upgrade-pending', false );

        if ( ! $manual_upgrade || ! is_array( $manual_upgrade ) || empty( $manual_upgrade['config'] ) )
            return array();

        return $manual_upgrade['config'];
    }

}
