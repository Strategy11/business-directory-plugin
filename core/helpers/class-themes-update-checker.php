<?php
class WPBDP_Themes_Update_Checker {

    private $themes_api;


    public function __construct( &$themes_api ) {
        $this->themes_api = $themes_api;
        $this->data = get_transient( 'wpbdp-themes-update-info' );

        if ( ! $this->data )
            $this->check_for_updates();
    }

    private function check_for_updates() {
        $themes = $this->themes_api->get_installed_themes();
        $res = array();

        foreach ( $themes as $theme_id => $details ) {
            if ( in_array( $theme_id, array( 'default', 'no_theme' ), true ) )
                continue;

            $id = $details->id;
            $name = $details->edd_name;
            $version = $details->version;

            if ( ! $name )
                continue;

//            wpbdp_debug_e( $id, $name, $version );
        }

//        wpbdp_debug_e( $themes );
//        wpbdp_debug_e('running check...');
    }

}
