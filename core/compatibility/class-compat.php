<?php
require_once ( WPBDP_PATH . 'core/compatibility/deprecated.php' );

class WPBDP_Compat {

    function __construct() {
        add_action( 'wpbdp_loaded', array( &$this, 'load_integrations' ) );
    }

    function load_integrations() {
        if ( isset( $GLOBALS['sitepress'] ) ) {
            require_once( WPBDP_PATH . 'core/compatibility/class-wpml-compat.php' );
            $wpml_integration = new WPBDP_WPML_Compat();
        }
    }

}
