<?php
class WPBDP_WPML_Compat {

    private $wpml;

    function __construct() {
        $this->wpml = $GLOBALS['sitepress'];

        if ( ! is_admin() ) {
            add_filter( 'wpbdp_get_page_id', array( &$this, 'page_id'), 10, 2 );

            add_filter( 'wpbdp_listing_link', array( &$this, 'add_lang_to_link' ) );
            add_filter( 'wpbdp_category_link', array( &$this, 'add_lang_to_link' ) );
            add_filter( 'wpbdp__get_page_link', array( &$this, 'fix_get_page_link' ), 10, 2 );
            add_filter( 'wpbdp_get_page_link', array( &$this, 'correct_page_link' ), 10, 3 );

            add_filter( 'wpbdp_render_field_label', array( &$this, 'translate_form_field_label' ), 10, 2 );
            add_filter( 'wpbdp_render_field_description', array( &$this, 'translate_form_field_description' ), 10, 2 );
            add_filter( 'wpbdp_display_field_label', array( &$this, 'translate_form_field_label' ), 10, 2 );

            add_filter( 'wpbdp_category_fee_selection_label', array( &$this, 'translate_fee_label' ), 10, 2 );

            add_filter( 'icl_ls_languages', array( &$this, 'language_switcher' ) );

            // Regions.
            add_filter( 'wpbdp_region_link', array( &$this, 'add_lang_to_link' ) );
        }

        add_action( 'admin_footer-directory-admin_page_wpbdp_admin_formfields', array( &$this, 'register_form_fields_strings' ) );
        add_action( 'admin_footer-directory-admin_page_wpbdp_admin_fees', array( &$this, 'register_fees_strings' ) );

        // Regions.
        add_filter( 'wpbdp_regions__get_hierarchy_option', array( &$this, 'use_cache_per_lang' ) );
        add_action( 'wpbdp_regions_clean_cache', array( &$this, 'clean_cache_per_lang' ) );
    }

    function get_current_language() {
        return $this->wpml->get_current_language();
    }

    function fix_get_page_link( $link, $post_id ) {
        if ( ! wpbdp_rewrite_on() )
            return $link;

        $page_ids = wpbdp_get_page_ids( 'main' );
        if ( ! in_array( $post_id, $page_ids ) )
            return $link;

        $link = preg_replace( '/\?.*/', '', $link );
        return $link;
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
        global $sitepress;

        $lang = '';

        if ( false !== ($index = strpos( $link, '?' ) ) ) {
            // We honor the ?lang argument from the link itself (if present).
            $data = array();
            wp_parse_str( substr( $link, $index + 1 ), $data );

            if ( !empty( $data['lang'] ) )
                $lang = $data['lang'];
        } else {
           $lang = $this->get_current_language();
        }

        if ( ! $lang )
            return $link;

        $nego_type = absint( $sitepress->get_setting( 'language_negotiation_type' ) );
        if ( 1 == $nego_type ) {
            if ( $trans_id = icl_object_id( wpbdp_get_page_id(), 'page', false, $lang ) ) {
                $real_link = get_permalink( $trans_id );
                $used_link = _get_page_link( $trans_id );

                $link = str_replace( $used_link, $real_link, $link );

                return $link;
            }
        }

        $link = add_query_arg( 'lang', $lang, $link );
        return $link;
    }

    function correct_page_link( $link, $name = '', $arg0 = '' ) {
        global $sitepress;
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
        global $sitepress;

        $lang = $lang ? $lang : $this->get_current_language();

        if ( ! $lang )
            return $link;

        if ( wpbdp_rewrite_on() ) {
            $main_id = wpbdp_get_page_id( 'main' );
            $trans_id = icl_object_id( $main_id, 'page', false, $lang );

            if ( ! $trans_id )
                return $link;

            $main_link = $this->fix_get_page_link( get_page_link( $main_id ), $main_id );
            $main_trans_link = $this->fix_get_page_link( get_page_link( $trans_id ), $trans_id );

            $link = str_replace( $main_link, $main_trans_link, $link );

            $nego_type = absint( $sitepress->get_setting( 'language_negotiation_type' ) );

            if ( 3 == $nego_type ) {
                $link = add_query_arg( 'lang', $lang, $link );
            }
        } else {
            $link = add_query_arg( 'lang', $lang, $link );
        }

        return $link;
    }

    function language_switcher( $languages ) {
        global $wpbdp;

        $action = wpbdp_current_view();
        $this->workaround_autoids();

        switch ( $action ) {
            case 'show_category':
                $category_id = wpbdp_current_category_id();

                if ( ! $category_id )
                    return $languages;

                foreach ( $languages as $l_code => $l ) {
                    $trans_id = (int) icl_object_id( $category_id, WPBDP_CATEGORY_TAX, false, $languages[ $l_code ]['language_code'] );
                    $link = get_term_link( $trans_id, WPBDP_CATEGORY_TAX );

                    if ( ! $trans_id || is_wp_error( $link ) ) {
                        unset( $languages[ $l_code ] );
                        continue;
                    }

                    $languages[ $l_code ]['url'] = $this->translate_link( $link, $languages[ $l_code ]['language_code'] );
                }

                break;

            case 'show_listing':
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

                    if ( ! $trans_id ) {
                        unset( $languages[ $l_code ] );
                        continue;
                    }

                    $languages[ $l_code ]['url'] = $this->translate_link( get_permalink( $trans_id ), $languages[ $l_code ]['language_code'] );
                }

                break;

            default:
                break;
        }

        $this->workaround_autoids();

        return $languages;
    }

    function workaround_autoids() {
        global $sitepress_settings;

        if ( ! $this->wpml->get_setting( 'auto_adjust_ids' ) || ! isset( $sitepress_settings ) )
            return;

        if ( ! isset( $this->workaround ) ) {
            $this->workaround = true;
        } else {
            $this->workaround = ! $this->workaround;
        }

        if ( $this->workaround ) {
            // Magic here.
            $sitepress_settings['auto_adjust_ids'] = 0;
        } else {
            // Undo magic.
            $sitepress_settings['auto_adjust_ids'] = 1;
        }
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

        $fees = WPBDP_Fee_Plan::find();

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

    // Regions. {{{
    function use_cache_per_lang( $option ) {
        $lang = $this->get_current_language();

        if ( ! $lang )
            return $option;

        return $option . '-' . $lang;
    }

    function clean_cache_per_lang( $opt ) {
        $langs = icl_get_languages( 'skip_missing=0' );

        if ( ! $langs )
            return;

        foreach ( $langs as $l ) {
            $code = $l['language_code'];

            delete_option( $opt . '-' . $code );
        }
    }

    // }}}
}
