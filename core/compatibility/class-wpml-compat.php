<?php

class WPBDP_WPML_Compat {

    private $wpml;

    function __construct() {
        $this->wpml = $GLOBALS['sitepress'];
        $this->switch_language_if_needed();

        add_filter( 'wpbdp_category_link', array( &$this, 'add_language_to_link' ) );
    }

    function get_current_language() {
        return $this->wpml->get_current_language();
    }

    function switch_language_if_needed() {
        $lang = $this->get_current_language();

        if ( ! $lang )
            return;

        $this->wpml->switch_lang('es');
    }

    function add_language_to_link( $link ) {
        $lang = $this->get_current_language();

        if ( ! $lang )
            return $link;

        $link = add_query_arg( 'lang', $lang, $link );
        return $link;
    }



}
