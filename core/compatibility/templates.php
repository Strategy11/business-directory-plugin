<?php
/**
 * @since themes-release
 */
class WPBDP_Theme_Compat_Layer {

    private $current_vars = array();
    private $templates_before = array();
    private $templates_after = array();


    function __construct() {
        add_action( 'wpbdp_page_listings_after', array( &$this, 'after_viewlistings_page' ) );

        add_filter( 'wpbdp_render_vars', array( &$this, 'render_vars' ), 9999, 2 );

        add_action( 'wpbdp_template_before_inner', array( &$this, 'prepare_before_inner' ), 0, 3 );
        add_action( 'wpbdp_template_before_inner', array( &$this, 'before_inner' ), 9999, 3 );

        add_action( 'wpbdp_template_after_inner', array( &$this, 'prepare_after_inner' ), 0, 3 );
        add_action( 'wpbdp_template_after_inner', array( &$this, 'after_inner' ), 9999, 3 );
    }

    function after_viewlistings_page() {
        do_action( 'wpbdp_after_viewlistings_page' );
    }

    function render_vars( $vars, $template ) {
        $this->current_vars[ $template ] = $vars;
        $this->templates_before[ $template ] = '';
        $this->templates_after[ $template ] = '';

        // Fake some old integrations.
        $old_vars = array(
            '__page__' => array( 'class' => array(),
                                 'content_class' => array(),
                                 'before_content' => '' )
        );
        $old_template_name = '';

        if ( 'main page' == $template )
            $old_template_name = 'businessdirectory-main-page';
        elseif ( 'listings' == $template )
            $old_template_name = 'businessdirectory-listings';
        else
            $old_template_name = $template;

        $old_vars = apply_filters( 'wpbdp_template_vars', array_merge( $vars, $old_vars ), $old_template_name );

        $page_var = $old_vars['__page__'];
        if ( empty( $page_var['class'] ) && empty( $page_var['content_class'] ) && empty( $page_var['before_content'] ) )
            return $vars;

        $vars['_class'] .= '  ' . implode( ' ', $page_var['class'] );
        $vars['_inner_class'] .= ' wpbdp-page-content ' . implode( ' ', $page_var['content_class'] );

        if ( 'main page' == $template ) {
            $vars['_class'] .= ' wpbdp-main-page';
        } elseif ( 'listings' == $template ) {
            $vars['_class'] .= ' wpbdp-view-listings-page';
        }

        $this->templates_before[ $template ] .= $page_var['before_content'];
        return $vars;
    }

    function prepare_before_inner( $id, $template, $vars ) {
        $html = '';

        if ( 'category' == $id ) {
            $html = wpbdp_capture_action( 'wpbdp_before_category_page', $this->current_vars[ $template ]['category'] );
        } elseif ( 'single' == $id ) {
            $html .= apply_filters(  'wpbdp_listing_view_before', '', $vars['listing_id'], 'single' );
            $html .= wpbdp_capture_action( 'wpbdp_before_single_view', $vars['listing_id'] );
        }

        if ( $html )
            $this->templates_before[ $template ] .= $html;
    }

    function before_inner( $id, $template, $vars ) {
        if ( empty( $this->templates_before[ $template ] ) )
            return;

        echo $this->templates_before[ $template ];
    }

    function prepare_after_inner( $id, $template, $vars ) {
        $html = '';

        switch ( $id ) {
            case 'category':
                $html .= wpbdp_capture_action( 'wpbdp_after_category_page', $this->current_vars[ $template ]['category'] );
                break;
            case 'single':
                $html .= apply_filters(  'wpbdp_listing_view_after', '', $vars['listing_id'], 'single' );
                $html .= wpbdp_capture_action( 'wpbdp_after_single_view', $vars['listing_id'] );
                break;
            default:
                break;
        }

        if ( $html )
            $this->templates_after[ $template ] .= $html;
    }

    function after_inner( $id, $template, $vars ) {
        if ( empty( $this->templates_after[ $template ] ) )
            return;

        echo $this->templates_after[ $template ];
    }

}
