<?php
class WPBDP__Themes_Compat {

    private $theme = '';
    private $theme_version = '';
    private $parent_theme = '';
    private $parent_theme_version = '';


    public function __construct() {
        if ( wpbdp_get_option( 'disable-cpt' ) )
            return;

        $current_theme = wp_get_theme();

        $this->theme = $current_theme->get_stylesheet();
        $this->theme_version = $current_theme->get( 'Version' );

        if ( $parent = $current_theme->parent() ) {
            $this->parent_theme = $parent->get_stylesheet();
            $this->parent_theme_version = $parent->get( 'Version' );
        }

        add_action( 'wpbdp_after_dispatch', array( $this, 'add_workarounds' ) );
    }

    public function add_workarounds() {
        $current_view = wpbdp_current_view();

        if ( ! $current_view )
            return;


        $themes_with_fixes = $this->get_themes_with_fixes();
        $themes_to_try = array( $this->theme, $this->parent_theme );

        foreach ( $themes_to_try as $t ) {
            if ( ! $t )
                continue;

            if ( ! in_array( $t, $themes_with_fixes, true ) )
                continue;

            $t = WPBDP_Utils::normalize( $t );
            $t = str_replace( '-', '_', $t );

            if ( method_exists( $this, 'theme_' . $t ) )
                call_user_func( array( $this, 'theme_' . $t ) );
        }
    }

    public function get_themes_with_fixes() {
        $themes_with_fixes = array( 'atahualpa', 'genesis', 'hmtpro5', 'customizr', 'customizr-pro' );
        return apply_filters( 'wpbdp_themes_with_fixes_list', $themes_with_fixes );
    }

    //
    // {{ Fixes for some themes.
    //

    public function theme_genesis() {
        if ( ! in_array( wpbdp_current_view(), array( 'show_listing', 'show_category', 'show_tag' ), true ) )
            return;

        // Workaround taken from https://theeventscalendar.com/knowledgebase/genesis-theme-framework-integration/.
        remove_action( 'genesis_entry_content', 'genesis_do_post_image', 8 );
        remove_action( 'genesis_entry_content', 'genesis_do_post_content' );
        remove_action( 'genesis_after_entry', 'genesis_do_author_box_single', 8 );
        add_action( 'genesis_entry_content', 'the_content', 15 );
    }

    public function theme_hmtpro5() {
        if ( ! in_array( wpbdp_current_view(), array( 'show_category', 'show_tag' ), true ) )
            return;

        add_action( 'wp_head', array( $this, 'theme_hmtpro5_after_head' ), 999 );
    }

    public function theme_atahualpa() {
        global $wp_query;

        $wp_query->is_page = true;
    }

    public function theme_hmtpro5_after_head() {
        global $wp_query;

        $wp_query->is_page = true;
    }

    public function theme_customizr() {
        add_filter( 'tc_show_single_post_content', '__return_false', 999 );
        add_filter( 'tc_show_single_post_footer', '__return_false', 999 );

        if ( ! in_array( wpbdp_current_view(), array( 'show_category', 'show_tag' ), true ) )
            return;

        add_filter( 'tc_is_grid_enabled', '__return_false', 999 );
        add_filter( 'tc_show_excerpt', '__return_false', 999 );
        add_filter( 'tc_post_list_controller', '__return_true', 999 );
        add_filter( 'tc_show_tax_archive_title', '__return_false', 999 );
    }

    public function theme_customizr_pro() {
        return $this->theme_customizr();
    }

    //
    // }}
    //
}


