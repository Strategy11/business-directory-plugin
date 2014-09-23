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
