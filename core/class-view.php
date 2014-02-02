<?php
/**
 * Views (pages) API.
 */

abstract class WPBDP_View {

    abstract public function get_page_name();
    
    public function get_title() {
        return '';
    }

    public function dispatch() {
    	return '';
    }

}