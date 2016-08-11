<?php
/**
 * @since 4.0
 */
class WPBDP__WordPress_Template_Integration {

    private $wp_head_done = false;
    private $displayed = false;
    private $original_post_title = '';


    public function __construct() {
        if ( wpbdp_get_option( 'disable-cpt' ) )
            return;

        add_filter( 'template_include', array( $this, 'template_include' ), 20 );
        add_action( 'wp_head', array( $this, 'maybe_spoof_post' ), 100 );
        add_action( 'wp_head', array( $this, 'wp_head_done' ), 999 );
        add_filter( 'body_class', array( &$this, 'body_class' ), 10 );
    }

    public function template_include( $template ) {
        global $wp_query;

        if ( ! $wp_query->wpbdp_our_query )
            return $template;

        if ( is_404() )
            return get_404_template();

        global $post;
        if ( empty( $wp_query->wpbdp_view ) && ( ! isset( $post ) || ! $post instanceof WP_Post ) )
            return $template;

        add_filter( 'document_title_parts', array( $this, 'modify_global_post_title' ), 1000 );
        add_filter( 'wp_title', array( $this, 'modify_global_post_title' ), 1000 );
        add_action( 'loop_start', array( $this, 'setup_post_hooks' ) );

        if ( $page_template = locate_template( $this->get_template_alternatives() ) )
            $template = $page_template;

        return $template;
    }

    private function get_template_alternatives() {
        $templates = array( 'page.php', 'single.php', 'singular.php' );

        $main_page_id = wpbdp_get_page_id( 'main' );

        if ( ! $main_page_id ) {
            return $templates;
        }

        $main_page_template = get_page_template_slug( $main_page_id );

        if ( $main_page_template ) {
            array_unshift( $templates, $main_page_template );
        }

        return $templates;
    }

    public function setup_post_hooks( $query ) {
        if ( ! $this->wp_head_done )
            return;

        if ( ! $query->is_main_query() )
            return;

        add_action( 'the_post', array( $this, 'spoof_post' ) );
        remove_filter( 'the_content', 'wpautop' );
        // TODO: we should probably be more clever here to avoid conflicts. Run last so other hooks don't break our
        // output.
        add_filter( 'the_content', array( $this, 'display_view_in_content' ), 5 );
        remove_action( 'loop_start', array( $this, 'setup_post_hooks' ) );
    }

    public function spoof_post() {
        $GLOBALS['post'] = $this->spoofed_post();
        remove_action( 'the_post', array( $this, 'spoof_post' ) );
    }

    public function display_view_in_content( $content = '' ) {
        if ( $this->displayed ) {
            remove_filter( 'the_content', array( $this, 'display_view_in_content' ), 5 );
            return '';
        }

        remove_filter( 'the_content', array( $this, 'display_view_in_content' ), 5 );
        // add_filter( 'the_content', 'wpautop' );
        $this->restore_things();

        $html = wpbdp_current_view_output();

        if ( ! is_404() )
            $this->end_query();

        $this->displayed = true;

        return $html;
    }

    public function modify_global_post_title( $title = '' ) {
        global $post;

        if ( ! $post )
            return $title;

        // Set the title to an empty string (but record the original)
        $this->original_post_title = $post->post_title;
        $post->post_title = '';

        return $title;
    }

    private function spoofed_post() {
        $spoofed_post = array(
            'ID'                    => 0,
            'post_status'           => 'draft',
            'post_author'           => 0,
            'post_parent'           => 0,
            'post_type'             => 'page',
            'post_date'             => 0,
            'post_date_gmt'         => 0,
            'post_modified'         => 0,
            'post_modified_gmt'     => 0,
            'post_content'          => '',
            'post_title'            => '',
            'post_excerpt'          => '',
            'post_content_filtered' => '',
            'post_mime_type'        => '',
            'post_password'         => '',
            'post_name'             => '',
            'guid'                  => '',
            'menu_order'            => 0,
            'pinged'                => '',
            'to_ping'               => '',
            'ping_status'           => '',
            'comment_status'        => 'closed',
            'comment_count'         => 0,
            'is_404'                => false,
            'is_page'               => false,
            'is_single'             => false,
            'is_archive'            => false,
            'is_tax'                => false,
        );

        return (object) $spoofed_post;
    }

    public function maybe_spoof_post() {
        // if ( is_single() && post_password_required() || is_feed() ) {
        // return;

        global $wp_query;

        if ( ! $wp_query->is_main_query() || ! $wp_query->wpbdp_our_query )
            return;

        $spoofed_post = $this->spoofed_post();

        $GLOBALS['post']      = $spoofed_post;
        $wp_query->posts[]    = $spoofed_post;
        $wp_query->post_count = count( $wp_query->posts );

        $wp_query->wpbdp_spoofed = true;
        $wp_query->rewind_posts();
    }

    public function wp_head_done() {
        $this->wp_head_done = true;
    }

    public function body_class( $classes = array() ) {
        global $wp_query;
        global $wpbdp;

        // FIXME: we need a better way to handle this, since it might be that a shortcode is being used and not something
        // really dispatched through BD.
        $view = wpbdp_current_view();

        if ( ! $view )
            return $classes;

        $classes[] = 'business-directory';
        $classes[] = 'wpbdp-view-' . $view;

        if ( $theme = wp_get_theme() ) {
            $classes[] = 'wpbdp-wp-theme-' . $theme->get_stylesheet();
            $classes[] = 'wpbdp-wp-theme-' . $theme->get_template();
        }

        $classes[] = 'wpbdp-theme-' . $wpbdp->themes->get_active_theme();

        return $classes;
    }

    private function restore_things() {
        global $wp_query, $post;

        if ( ! isset( $wp_query->wpbdp_spoofed ) || ! $wp_query->wpbdp_spoofed )
            return;

        // Remove the spoof post and fix the post count
        array_pop( $wp_query->posts );
        $wp_query->post_count = count( $wp_query->posts );

        // If we have other posts besides the spoof, rewind and reset
        if ( $wp_query->post_count > 0 ) {
            $wp_query->rewind_posts();
            wp_reset_postdata();
        }
        // If there are no other posts, unset the $post property
        elseif ( 0 === $wp_query->post_count ) {
            $wp_query->current_post = -1;
            unset( $wp_query->post );
        }

        // Don't do this again
        unset( $wp_query->wpbdp_spoofed );

        // Restore title.
        $post->post_title = $this->original_post_title;
    }

    private function end_query() {
        global $wp_query;

        $wp_query->current_post = -1;
        $wp_query->post_count   = 0;
    }

}

