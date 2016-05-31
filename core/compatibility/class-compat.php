<?php
require_once ( WPBDP_PATH . 'core/compatibility/deprecated.php' );

class WPBDP_Compat {

    function __construct() {
        $this->workarounds_for_wp_bugs();
        $this->load_integrations();

        if ( wpbdp_get_option( 'disable-cpt' ) ) {
            require_once( WPBDP_PATH . 'core/compatibility/class-cpt-compat-mode.php' );
            $nocpt = new WPBDP__CPT_Compat_Mode();
        } else {
            require_once( WPBDP_PATH . 'core/compatibility/class-themes-compat.php' );
            new WPBDP__Themes_Compat();
        }
    }

    function load_integrations() {
        if ( isset( $GLOBALS['sitepress'] ) ) {
            require_once( WPBDP_PATH . 'core/compatibility/class-wpml-compat.php' );
            $wpml_integration = new WPBDP_WPML_Compat();
        }

        if ( function_exists( 'bcn_display' ) ) {
            require_once( WPBDP_PATH . 'core/compatibility/class-navxt-integration.php' );
            $navxt_integration = new WPBDP_NavXT_Integration();
        }
    }

    function cpt_compat_mode() {
        require_once( WPBDP_PATH . 'core/compatibility/class-cpt-compat-mode.php' );
        $nocpt = new WPBDP__CPT_Compat_Mode();
    }

    // Work around WP bugs. {{{

    function workarounds_for_wp_bugs() {
        // #1466 (related to https://core.trac.wordpress.org/ticket/28081).
        add_filter( 'wpbdp_query_clauses', array( &$this, '_fix_pagination_issue' ), 10, 2 );
    }

    function _fix_pagination_issue( $clauses, $query ) {
        $posts_per_page = intval( $query->get( 'posts_per_page' ) );
        $paged = intval( $query->get( 'paged' ) );

        if ( -1 != $posts_per_page || $paged <= 1 )
            return $clauses;

        // Force no results for pages outside of the scope of the query.
        $clauses['where'] .= ' AND 1=0 ';

        return $clauses;
    }

    // }}}

}
