<?php
require_once( WPBDP_PATH . 'includes/class-module.php' );

// FIXME: replace all module support with branch issue/2708 before release.
class WPBDP__Modules {

    private $modules = array();


    public function __construct() {
        do_action( 'wpbdp_load_modules', $this );
        add_action( 'init', array( $this, 'load_i18n' ), 999 );
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

    public function is_loaded( $module_id ) {
        return array_key_exists( $module_id, $this->modules );
    }

    public function load_i18n() {
        foreach ( $this->modules as $mod ) {
            load_plugin_textdomain( $mod->text_domain, false, basename( dirname( $mod->file ) ) . $mod->text_domain_path );
        }
    }

}
