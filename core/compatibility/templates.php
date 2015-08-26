<?php
/**
 * @since themes-release
 */
class WPBDP_Theme_Compat_Layer {

    private $current_vars = array();
    private $templates_before = array();
    private $templates_after = array();


    function __construct() {
        add_filter( 'wpbdp_template_variables', array( &$this, 'template_vars' ), 999, 2 );
    }

    function template_vars( $vars, $id_or_file ) {
        $before = array();
        $after = array();

        // Fake some old integrations.
        $old_vars = array(
            '__page__' => array( 'class' => array(),
                                 'content_class' => array(),
                                 'before_content' => '' )
        );

        if ( 'main_page' == $id_or_file )
            $old_template_name = 'businessdirectory-main-page';
        elseif ( 'listings' == $id_or_file )
            $old_template_name = 'businessdirectory-listings';
        else
            $old_template_name = $id_or_file;

        $old_vars = apply_filters( 'wpbdp_template_vars', array_merge( $vars, $old_vars ), $old_template_name );
        $vars['_class'] .= ' ' . implode( ' ', isset( $old_vars['__page__']['class'] ) ? $old_vars['__page__']['class'] : array() );
        $vars['_inner_class'] .= ' wpbdp-page-content ' . implode( ' ', isset( $old_vars['__page__']['content_class'] ) ? $old_vars['__page__']['content_class'] : array() );

        if ( ! empty( $old_vars['__page__']['before_content'] ) )
            $before[] = $old_vars['__page__']['before_content'];

        // Page-specific handling.
        switch ( $id_or_file ) {
            case 'main_page':
                $vars['_class'] .= ' wpbdp-main-page';

                break;
            case 'listings':
                $vars['_class'] .= ' wpbdp-view-listings-page';
                $before[] = wpbdp_capture_action( 'wpbdp_before_viewlistings_page' );
                $after[] = wpbdp_capture_action( 'wpbdp_after_viewlistings_page' );

                break;
            case 'category':
                $before[] = wpbdp_capture_action( 'wpbdp_before_category_page', $vars['category'] );
                $after[] = wpbdp_capture_action( 'wpbdp_after_category_page', $vars['category'] );

                break;
            case 'single':
                $before[] = apply_filters( 'wpbdp_listing_view_before', '', $vars['listing_id'], 'single' );
                $before[] = wpbdp_capture_action( 'wpbdp_before_single_view', $vars['listing_id'] );
                $after[] = apply_filters( 'wpbdp_listing_view_after', '', $vars['listing_id'], 'single' );
                $after[] = wpbdp_capture_action( 'wpbdp_after_single_view', $vars['listing_id'] );

                break;
            case 'excerpt':
                $before[] = wpbdp_capture_action( 'wpbdp_before_excerpt_view', $vars['listing_id'] );
                $after[] = wpbdp_capture_action( 'wpbdp_after_excerpt_view', $vars['listing_id'] );

                break;
        }

        foreach ( array( 'before', 'after' ) as $pos ) {
            foreach ( $$pos as $i => $content ) {
                if ( ! $content )
                    continue;

                $vars[ '#compat_' . $pos . '_' . $i ] = array( 'position' => $pos,
                                                               'value' => $content );
            }
        }

        return $vars;
    }

}
