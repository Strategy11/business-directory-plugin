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


    function __construct( $router = null ) {
        $this->router = $router;
    }

    function dispatch( $params = array() ) {
    }

    function http_404() {
        status_header( 404 );
        nocache_headers();

        if ( $template_404 = get_404_template() )
            include( $template_404 );

        exit;
    }

    function redirect( $redir ) {
        wp_redirect( $redir );
        exit;
    }

    function render( $template, $params = array() ) {
        return wpbdp_render( $template, $params );
    }

}
