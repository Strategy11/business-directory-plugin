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

        $this->theme = strtolower( $current_theme->get_stylesheet() );
        $this->theme_version = $current_theme->get( 'Version' );

        if ( $parent = $current_theme->parent() ) {
            $this->parent_theme = strtolower( $parent->get_stylesheet() );
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
        $themes_with_fixes = array(
            'atahualpa', 'genesis', 'hmtpro5', 'customizr', 'customizr-pro', 'canvas', 'builder', 'Divi', 'longevity', 'x', 'u-design', 'thesis',
            'takeawaywp'
        );

        return apply_filters( 'wpbdp_themes_with_fixes_list', $themes_with_fixes );
    }

    //
    // {{ Fixes for some themes.
    //

    public function theme_genesis() {
        if ( ! in_array( wpbdp_current_view(), array( 'all_listings', 'show_listing', 'show_category', 'show_tag' ), true ) )
            return;

        // Workaround taken from https://theeventscalendar.com/knowledgebase/genesis-theme-framework-integration/.
        remove_action( 'genesis_entry_content', 'genesis_do_post_image', 8 );
        remove_action( 'genesis_entry_content', 'genesis_do_post_content' );
        remove_action( 'genesis_after_entry', 'genesis_do_author_box_single', 8 );
        remove_action( 'genesis_post_content', 'genesis_do_post_image', 10 );
        remove_action( 'genesis_post_content', 'genesis_do_post_content', 10 );
        remove_action( 'genesis_after_post', 'genesis_do_author_box_single', 10 );
        add_action( 'genesis_entry_content', 'the_content', 15 );
        add_action( 'genesis_post_content', 'the_content', 15 );
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

        $current_view = wpbdp_current_view();

        if ( $current_view == 'show_listing' ) {
            $this->theme_customizr_hide_post_thumb();
            $this->theme_customizr_disable_comments();
        }

        if ( ! in_array( $current_view, array( 'show_category', 'show_tag' ), true ) )
            return;

        add_filter( 'tc_is_grid_enabled', '__return_false', 999 );
        add_filter( 'tc_show_excerpt', '__return_false', 999 );
        add_filter( 'tc_post_list_controller', '__return_true', 999 );
        add_filter( 'tc_show_tax_archive_title', '__return_false', 999 );
        add_filter( 'tc_show_breadcrumb_in_context', array( $this, 'theme_customizr_show_breadcrumb_in_context' ), 999 );
    }

    /**
     * The code that setups the filter that this function attempts to remove,
     * is attached to 'wp' hook, so it gets executed before any of the workarounds
     * has a chance to do anything.
     *
     * Also, the filter is dynamically configured, so we need to duplicate the
     * configuartion logic here, in order to figure out what filter to remove.
     *
     * @since 4.0.5dev
     */
    private function theme_customizr_hide_post_thumb() {
        if ( ! class_exists( 'TC_post' ) ) {
            return;
        }

        $post_thumb_location = $this->theme_customizr_get_option( 'tc_single_post_thumb_location' );

        if ( $post_thumb_location == 'hide' ) {
            return;
        }

        $location_parts = explode( '|', $post_thumb_location );
        $hook = isset( $location_parts[0] ) ? $location_parts[0] : '__before_content';
        $priority = isset( $location_parts[1] ) ? $location_parts[1] : 200;

        remove_filter( $hook, array( TC_post::$instance, 'tc_single_post_prepare_thumb' ), $priority );
    }

    private function theme_customizr_get_option( $option_name ) {
        if ( ! class_exists( 'TC_utils' ) ) {
            return null;
        }

        if ( ! is_object( TC_utils::$inst ) || ! method_exists( TC_utils::$inst, 'tc_opt' ) ) {
            return null;
        }

        return TC_utils::$inst->tc_opt( $option_name );
    }

    private function theme_customizr_disable_comments() {
        if ( ! class_exists( 'TC_comments' ) ) {
            return;
        }

        remove_action( '__after_loop', array( TC_comments::$instance , 'tc_comments' ), 10 );
    }

    public function theme_customizr_show_breadcrumb_in_context() {
        return $this->theme_customizr_get_option( 'tc_show_breadcrumb_in_pages' );
    }

    public function theme_customizr_pro() {
        return $this->theme_customizr();
    }

    public function theme_canvas() {
        add_filter( 'woo_template_parts', array( $this, 'theme_canvas_set_template' ) );
        add_filter( 'the_excerpt', array( $this, 'theme_canvas_the_excerpt' ), 999 );
    }

    public function theme_canvas_set_template( $templates ) {
        $templates = array();
        $templates[] = 'content-page.php';

        return $templates;
    }

    public function theme_canvas_the_excerpt( $excerpt ) {
        remove_filter( 'the_excerpt', array( $this, 'theme_canvas_the_excerpt' ) );

        return wpbdp_current_view_output();
    }

    public function theme_divi() {
        if ( ! in_array( wpbdp_current_view(), array( 'show_category', 'show_tag' ) ) ) {
            return;
        }

        if ( 'et_full_width_page' != get_post_meta( wpbdp_get_page_id( 'main' ), '_et_pb_page_layout', true ) ) {
            return;
        }

        add_filter( 'body_class', array( $this, 'theme_divi_add_full_with_page_body_class' ) );
        add_filter( 'is_active_sidebar', array( $this, 'theme_divi_disable_sidebar' ), 999, 2 );
    }

    public function theme_divi_add_full_with_page_body_class( $classes ) {
        $classes[] = 'et_full_width_page';
        return $classes;
    }

    public function theme_divi_disable_sidebar( $is_active_sidebar, $index ) {
        return $index == 'sidebar-1' ? false : $is_active_sidebar;
    }

    public function theme_longevity() {
        if ( ! in_array( wpbdp_current_view(), array( 'show_category', 'show_tag' ) ) ) {
            return;
        }

        add_filter( 'theme_mod_excerpt_content', array( $this, 'theme_longevity_excerpt_content_mod' ), 999 );
    }

    public function theme_longevity_excerpt_content_mod( $value ) {
        return 'content';
    }

    public function theme_builder() {
    }

    public function theme_builder_change_layout( $layout_id = '' ) {
    }

    /**
     * @since 4.0.6
     */
    public function theme_x() {
        if ( ! in_array( wpbdp_current_view(), array( 'show_category', 'show_tag' ), true ) )
            return;

        add_action( 'x_before_view_global__content', array( $this, 'theme_x__toggle_singular' ) );
        add_action( 'x_after_view_global__content', array( $this, 'theme_x__toggle_singular' ) );
    }

    public function theme_x__toggle_singular() {
        global $wp_query;
        $wp_query->is_singular = ! $wp_query->is_singular;
    }

    // public function theme_thesis() {
    //     add_action( 'thesis_hook_before_post', array( $this, 'theme_thesis_before_post' ) );
    //     // wpbdp_debug_e( wpbdp_current_view_output() );
    // }
    //
    // public function theme_thesis_before_post() {
    //     add_filter( 'the_content', array( $this, 'theme_thesis_the_content' ), 999 );
    // }
    //
    // public function theme_thesis_the_content( $content = '' ) {
    //     global $post;
    //
    //     if ( 0 == $post->ID )
    //         return wpbdp_current_view_output();
    //
    //     return '';
    // }

    /**
     * @since 4.0.8
     */
    public function theme_u_design() {
        remove_filter( 'the_content', 'autoinsert_rel_prettyPhoto', 10 );
    }

    /**
     * @since 4.0.8
     */
    public function theme_takeawaywp() {
        $main_id = wpbdp_get_page_id( 'main' );

        if ( ! $main_id )
            return;

        if ( 'page-fullwidth.php' != get_page_template_slug( $main_id ) )
            return;

        // page-fullwidth.php has a bug. It doesn't call the_post(), so we do it for them :/.
        add_action( 'wp_head', 'the_post', 999 );
    }

    //
    // }}
    //
}


