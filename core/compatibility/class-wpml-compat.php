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

            add_filter( 'icl_ls_languages', array( &$this, 'language_switcher' ) );
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


    function translate_link( $link, $lang = null ) {
        $lang = $lang ? $lang : $this->get_current_language();

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

    function language_switcher( $languages ) {
        global $wpbdp;

        $action = $wpbdp->controller->get_current_action();

        switch ( $action ) {
            case 'browsecategory':
                if (get_query_var('category')) {
                    if ($term = get_term_by('slug', get_query_var('category'), WPBDP_CATEGORY_TAX)) {
                        $category_id = $term->term_id;
                    } else {
                        $category_id = intval(get_query_var('category'));
                    }
                }

                $category_id = $category_id ? $category_id : intval(get_query_var('category_id'));
                $category_id = is_array( $category_id ) && 1 == count( $category_id ) ? $category_id[0] : $category_id;

                if ( ! $category_id )
                    return $languages;

                foreach ( $languages as $l_code => $l ) {
                    $trans_id = (int) icl_object_id( $category_id, WPBDP_CATEGORY_TAX, false, $languages[ $l_code ]['language_code'] );
                    $link = get_term_link( $trans_id, WPBDP_CATEGORY_TAX );

                    if ( ! $trans_id || is_wp_error( $link ) )
                        unset( $languages[ $l_code ] );

                    $languages[ $l_code ]['url'] = $this->translate_link( $link, $languages[ $l_code ]['language_code'] );
                }

                break;

            case 'showlisting':
                $id_or_slug = '';
                if ( get_query_var( 'listing' ) || isset( $_GET['listing'] ) )
                    $id_or_slug = get_query_var( 'listing' ) ? get_query_var( 'listing' ) : wpbdp_getv( $_GET, 'listing', 0 );
                else
                    $id_or_slug = get_query_var( 'id' ) ? get_query_var( 'id' ) : wpbdp_getv( $_GET, 'id', 0 );

                $listing_id = wpbdp_get_post_by_id_or_slug( $id_or_slug, 'id', 'id' );

                if ( ! $listing_id )
                    break;

                foreach ( $languages as $l_code => $l ) {
                    $trans_id = icl_object_id( $listing_id, WPBDP_POST_TYPE, true, $languages[ $l_code ]['language_code'] );

                    if ( ! $trans_id )
                        unset( $languages[ $l_code ] );

                    $languages[ $l_code ]['url'] = $this->translate_link( get_permalink( $trans_id ), $languages[ $l_code ]['language_code'] );
                }

                break;

            default:
                break;
        }

        return $languages;
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
        if ( ! is_object( $field ) || ! function_exists( 'icl_t' ) )
            return $label;

        return icl_t( 'Business Directory Plugin',
                      sprintf( 'Field #%d - label', $field->get_id() ),
                      $field->get_label() );
    }

    function translate_form_field_description( $description, $field ) {
        if ( ! is_object( $field ) || ! function_exists( 'icl_t' ) )
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
        if ( ! function_exists( 'icl_t' ) )
            return $label;

        return icl_t( 'Business Directory Plugin',
                      sprintf( 'Fee label (#%d)', $fee->id ),
                      $fee->label );
    }

    // }}}
}
