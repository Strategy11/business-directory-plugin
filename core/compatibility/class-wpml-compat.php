<?php

class WPBDP_WPML_Compat {

    private $wpml;

    function __construct() {
        $this->wpml = $GLOBALS['sitepress'];

        add_filter( 'wpbdp_get_page_id', array( &$this, 'page_id'), 10, 2 );
        add_filter( 'wpbdp_listing_link', array( &$this, 'add_lang_to_link' ) );
        add_filter( 'wpbdp_category_link', array( &$this, 'add_lang_to_link' ) );
    }

    function get_current_language() {
        return $this->wpml->get_current_language();
    }

    function page_id( $id, $page_name = '' ) {
        $lang = $this->get_current_language();

        if ( ! $lang )
            return $id;

        $trans_id = icl_object_id( $id, 'page', false, $lang );
        if ( ! $trans_id )
            return $id;

        return $trans_id;
    }
    
    function add_lang_to_link( $link ) {
        $lang = $this->get_current_language();

        if ( ! $lang )
            return $link;

        $link = add_query_arg( 'lang', $lang, $link );
        return $link;
    }

    function translate_link( $link ) {
        $lang = $this->get_current_language();

        if ( ! $lang )
            return $link;

        if ( wpbdp_rewrite_on() ) {
            $main_id = wpbdp_get_page_id( 'main' );
            $trans_id = icl_object_id( $main_id, 'page', false, $lang );

            if ( ! $trans_id )
                return $link;

            $link = str_replace( _get_page_link( $main_id ), _get_page_link( $trans_id ), $link );
            $link = add_query_arg( 'lang', $lang, $link );
        } else {
            $link = add_query_arg( 'lang', $lang, $link );
        }


        return $link;
    }

}
