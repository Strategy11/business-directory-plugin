<?php
class WPBDP__Themes_Compat {

    private $theme_name = '';
    private $theme_version = '';


    public function __construct() {
        if ( wpbdp_get_option( 'disable-cpt' ) )
            return;

        $current_theme = wp_get_theme();
        $this->theme_name = strtolower( $current_theme->get( 'Name' ) );
        $this->theme_version = strtolower( $current_theme->get( 'Version' ) );

        add_action( 'wpbdp_after_dispatch', array( $this, 'add_workarounds' ) );
    }

    public function add_workarounds() {
        if ( ! in_array( $this->theme_name, $this->get_themes_with_fixes(), true ) )
            return;

        if ( ! method_exists( $this, 'theme_' . $this->theme_name ) )
            return;

        call_user_func( array( $this, 'theme_' . $this->theme_name ) );
    }

    public function get_themes_with_fixes() {
        $themes_with_fixes = array( 'genesis' );
        return apply_filters( 'wpbdp_themes_with_fixes_list', $themes_with_fixes );
    }

    //
    // {{ Fixes for some themes.
    //

    public function theme_genesis() {
        $current_view = wpbdp_current_view();

        if ( ! $current_view )
            return;

        if ( ! in_array( $current_view, array( 'show_listing', 'show_category', 'show_tag' ), true ) )
            return;

        // Workaround taken from https://theeventscalendar.com/knowledgebase/genesis-theme-framework-integration/.
        remove_action( 'genesis_entry_content', 'genesis_do_post_image', 8 );
        remove_action( 'genesis_entry_content', 'genesis_do_post_content' );
        add_action( 'genesis_entry_content', 'the_content', 15 );
    }

    //
    // }}
    //
}

