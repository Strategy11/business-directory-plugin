<?php
/**
 * @since 3.4.2
 */
class WPBDP_Licensing {

    function setup_module( $module, $version ) {
        // Current key.
        $license = trim( get_option( 'wpbdp_licenses[' . $module . ']' ) );

        wpbdp_debug_e( $module, $version, $license );
    }

}
