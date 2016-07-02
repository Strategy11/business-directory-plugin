<?php
/**
 * Views (pages) API.
 */

abstract class WPBDP_View {

    public function get_page_name() {
        $clsname = get_class( $this );
        return ltrim( strtolower( str_replace( array( 'WPBDP', '_', '-Page' ), array( '', '-', '' ), $clsname ) ), '-' );
    }

    public function get_title() {
        return '';
    }

    public function dispatch() {
    	return '';
    }

}

class WPBDP_NView {

    private $router = null;


    public function __construct( $args = array() ) {
        foreach ( $args as $k => $v )
            $this->{$k} = $v;
    }

    public function get_title() {
        return '';
    }

    public function enqueue_resources() {
    }

    public function dispatch() {
        return '';
    }

    public final function _http_404() {
        status_header( 404 );
        nocache_headers();

        if ( $template_404 = get_404_template() )
            include( $template_404 );

        exit;
    }

    public final function _redirect( $url ) {
        wp_redirect( $redir );
        exit;
    }

    public final function _render() {
        $args = func_get_args();
        return call_user_func_array( 'wpbdp_x_render', $args );
    }

    public final function _render_page() {
        $args = func_get_args();
        return call_user_func_array( 'wpbdp_x_render_page', $args );
    }

}
