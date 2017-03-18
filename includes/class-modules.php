<?php
require_once( WPBDP_PATH . 'includes/class-module.php' );


class WPBDP__Modules {

    private $modules = array();


    public function __construct() {
        do_action( 'wpbdp_load_modules', $this );
    }

    public function load( $module ) {
        try {
            if ( is_string( $module ) && class_exists( $module ) )
                $module = new $module;

            $wrapper = new WPBDP__Module( $module );
            $this->modules[ $wrapper->id ] = $wrapper;
        } catch ( Exception $e ) {
        }
    }

    public function init() {
        foreach ( $this->modules as $mod ) {
            if ( ! $mod->is_premium_module ) {
                $mod->init();
                continue;
            }

            if ( ! wpbdp_licensing_register_module( $mod->title, $mod->file, $mod->version ) )
                continue;

            $mod->init();
        }
    }

}
