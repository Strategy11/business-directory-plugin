<?php

class WPBDP_WPML_Compat {

    private $wpml;

    function __construct() {
        $this->wpml = $GLOBALS['sitepress'];

        if ( ! is_admin() ) {
            add_filter( 'wpbdp_get_page_id', array( &$this, 'page_id'), 10, 2 );
            add_filter( 'wpbdp_listing_link', array( &$this, 'add_lang_to_link' ) );
            add_filter( 'wpbdp_category_link', array( &$this, 'add_lang_to_link' ) );
            add_filter( 'wpbdp_get_page_link', array( &$this, 'correct_page_link' ), 10, 3 );

            add_filter( 'wpbdp_render_field_label', array( &$this, 'translate_form_field_label' ), 10, 2 );
            add_filter( 'wpbdp_render_field_description', array( &$this, 'translate_form_field_description' ), 10, 2 );
            add_filter( 'wpbdp_display_field_label', array( &$this, 'translate_form_field_label' ), 10, 2 );

            add_filter( 'wpbdp_category_fee_selection_label', array( &$this, 'translate_fee_label' ), 10, 2 );
        }

        add_action( 'admin_footer-directory-admin_page_wpbdp_admin_formfields', array( &$this, 'register_form_fields_strings' ) );
        add_action( 'admin_footer-directory-admin_page_wpbdp_admin_fees', array( &$this, 'register_fees_strings' ) );
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

    function correct_page_link( $link, $name = '', $arg0 = '' ) {
        $lang = $this->get_current_language();

        if ( ! $lang )
            return $link;

        switch ( $name ) {
            case 'editlisting':
            case 'upgradetostickylisting':
            case 'deletelisting':
                $link = add_query_arg( 'lang', $lang, $link );
                break;

            default:
                break;
        }

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

    // {{{ Form Fields integration.

    function register_form_fields_strings() {
        if ( isset( $_GET['action'] ) || ! function_exists( 'icl_register_string' ) )
            return;

        $fields = wpbdp_get_form_fields();

        foreach ( $fields as &$f ) {
            icl_register_string( 'Business Directory Plugin',
                                 sprintf( 'Field #%d - label', $f->get_id() ),
                                 $f->get_label() );

            if ( $f->get_description() )
                icl_register_string( 'Business Directory Plugin',
                                     sprintf( 'Field #%d - description', $f->get_id() ),
                                     $f->get_description() );
        }
    }

    function translate_form_field_label( $label, $field ) {
        if ( ! is_object( $field ) )
            return $label;

        return icl_t( 'Business Directory Plugin',
                      sprintf( 'Field #%d - label', $field->get_id() ),
                      $field->get_label() );
    }

    function translate_form_field_description( $description, $field ) {
        if ( ! is_object( $field ) )
            return $description;

        return icl_t( 'Business Directory Plugin',
                      sprintf( 'Field #%d - description', $field->get_id() ),
                      $field->get_description() );
    }

    // }}}

    // {{{ Fees API integration.

    function register_fees_strings() {
        if ( isset( $_GET['action'] ) || ! function_exists( 'icl_register_string' ) )
            return;

        $fees = wpbdp_fees_api()->get_fees();

        foreach ( $fees as &$f ) {
            icl_register_string( 'Business Directory Plugin',
                                 sprintf( 'Fee label (#%d)', $f->id ),
                                 $f->label );
        }
    }

    function translate_fee_label( $label, $fee ) {
        return icl_t( 'Business Directory Plugin',
                      sprintf( 'Fee label (#%d)', $fee->id ),
                      $fee->label );
    }

    // }}}
}
