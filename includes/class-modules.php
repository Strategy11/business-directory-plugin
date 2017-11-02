<?php
require_once( WPBDP_PATH . 'includes/class-module.php' );

// FIXME: replace all module support with branch issue/2708 before release.
class WPBDP__Modules {

    private $modules = array();
    private $valid   = array();


    public function __construct() {
        $this->register_modules();
    }

    private function register_modules() {
        // Allow modules to register themselves with this class.
        do_action( 'wpbdp_load_modules', $this );

        // Register modules with the Licensing API.
        foreach ( $this->modules as $mod ) {
            $valid = false;

            if ( ! $mod->is_premium_module ) {
                $valid = true;
            } else {
                $valid = wpbdp()->licensing->add_item_and_check_license( array( 'item_type' => 'module', 'name' => $mod->title, 'file' => $mod->file, 'version' => $mod->version ) );
            }

            if ( $valid ) {
                $this->valid[] = $mod->id;
            }
        }
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
        foreach ( $this->valid as $module_id ) {
            $mod = $this->modules[ $module_id ];
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
