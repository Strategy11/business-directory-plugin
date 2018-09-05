<?php
/**
 * @package WPBDP/Compatibility
 */

// phpcs:disable Squiz,PEAR,Generic,WordPress,PSR2

/**
 * @SuppressWarnings(PHPMD)
 */
require_once WPBDP_PATH . 'includes/compatibility/deprecated.php';
class WPBDP_Compat {

    function __construct() {
        $this->workarounds_for_wp_bugs();
        $this->load_integrations();

        if ( wpbdp_get_option( 'disable-cpt' ) ) {
            add_action( 'wp', array( &$this, '_jetpack_compat' ), 11, 1 );

            require_once WPBDP_PATH . 'includes/compatibility/class-cpt-compat-mode.php';
            $nocpt = new WPBDP__CPT_Compat_Mode();
        } else {
            require_once WPBDP_PATH . 'includes/compatibility/class-themes-compat.php';
            new WPBDP__Themes_Compat();
        }

        add_action( 'wp_head', array( &$this, '_handle_broken_plugin_filters' ), 0 );
    }

    function load_integrations() {
        if ( isset( $GLOBALS['sitepress'] ) ) {
            require_once WPBDP_PATH . 'includes/compatibility/class-wpml-compat.php';
            $wpml_integration = new WPBDP_WPML_Compat();
        }

        if ( function_exists( 'bcn_display' ) ) {
            require_once WPBDP_PATH . 'includes/compatibility/class-navxt-integration.php';
            $navxt_integration = new WPBDP_NavXT_Integration();
        }

        if ( class_exists( 'Advanced_Excerpt' ) ) {
            require_once WPBDP_PATH . 'includes/compatibility/class-advanced-excerpt-integration.php';
            $advanced_excerpt_integration = new WPBDP_Advanced_Excerpt_Integration();
        }

        if ( defined( 'CUSTOM_PERMALINKS_PLUGIN_VERSION' ) ) {
            require_once WPBDP_PATH . 'includes/compatibility/class-custom-permalinks-integration.php';
            $custom_permalinks_integration = new WPBDP_Custom_Permalink_Integration();
        }

        if ( class_exists( 'acf' ) && 'Bold Move' === wp_get_theme()->Name ) {
            require_once WPBDP_PATH . 'includes/compatibility/class-acf-boldmove-compat.php';
            $advanced_custom_fields = new WPBDP_ACF_Compat();
        }
    }

    function cpt_compat_mode() {
        require_once WPBDP_PATH . 'includes/compatibility/class-cpt-compat-mode.php';
        $nocpt = new WPBDP__CPT_Compat_Mode();
    }

    // Work around WP bugs. {{{
    function workarounds_for_wp_bugs() {
        // #1466 (related to https://core.trac.wordpress.org/ticket/28081).
        add_filter( 'wpbdp_query_clauses', array( &$this, '_fix_pagination_issue' ), 10, 2 );
    }

    function _fix_pagination_issue( $clauses, $query ) {
        $posts_per_page = intval( $query->get( 'posts_per_page' ) );
        $paged          = intval( $query->get( 'paged' ) );

        if ( -1 != $posts_per_page || $paged <= 1 ) {
            return $clauses;
        }

        // Force no results for pages outside of the scope of the query.
        $clauses['where'] .= ' AND 1=0 ';

        return $clauses;
    }

    // }}}
    public function _handle_broken_plugin_filters() {
        // TODO: fix before themes-release
        $action = '';

        if ( ! $action ) {
            return;
        }

        // Relevanssi
        if ( in_array( $action, array( 'submitlisting', 'editlisting' ), true ) && function_exists( 'relevanssi_insert_edit' ) ) {
            remove_action( 'wp_insert_post', 'relevanssi_insert_edit', 99, 1 );
            remove_action( 'delete_attachment', 'relevanssi_delete' );
            remove_action( 'add_attachment', 'relevanssi_publish' );
            remove_action( 'edit_attachment', 'relevanssi_edit' );
        }

        $bad_filters = array(
			'get_the_excerpt' => array(),
			'the_excerpt'     => array(),
			'the_content'     => array(),
		);

        // AddThis Social Bookmarking Widget - http://www.addthis.com/
        if ( defined( 'ADDTHIS_PLUGIN_VERSION' ) ) {
            $bad_filters['get_the_excerpt'][] = array( 'addthis_display_social_widget_excerpt', 11 );
            $bad_filters['get_the_excerpt'][] = array( 'addthis_display_social_widget', 15 );
            $bad_filters['the_content'][]     = array( 'addthis_display_social_widget', 15 );
        }

        // Jamie Social Icons - http://wordpress.org/extend/plugins/jamie-social-icons/
        if ( function_exists( 'jamiesocial' ) ) {
            $bad_filters['the_content'][] = 'add_post_topbot_content';
            $bad_filters['the_content'][] = 'add_post_bot_content';
            $bad_filters['the_content'][] = 'add_page_topbot_content';
            $bad_filters['the_content'][] = 'add_page_top_content';
            $bad_filters['the_content'][] = 'add_page_bot_content';
        }

        // TF Social Share - http://www.searchtechword.com/2011/06/wordpress-plugin-add-twitter-facebook-google-plus-one-share
        if ( function_exists( 'kc_twitter_facebook_excerpt' ) ) {
            $bad_filters['the_excerpt'][] = 'kc_twitter_facebook_excerpt';
            $bad_filters['the_content'][] = 'kc_twitter_facebook_contents';
        }

        // Shareaholic - https://shareaholic.com/publishers/
        if ( defined( 'SHRSB_vNum' ) ) {
            $bad_filters['the_content'][] = 'shrsb_position_menu';
            $bad_filters['the_content'][] = 'shrsb_get_recommendations';
            $bad_filters['the_content'][] = 'shrsb_get_cb';
        }

        // Simple Facebook Connect (#481)
        if ( function_exists( 'sfc_version' ) ) {
            remove_action( 'wp_head', 'sfc_base_meta' );
        }

        // Quick AdSense - http://quicksense.net/
        global $QData;
        if ( isset( $QData ) ) {
            $bad_filters['the_content'][] = 'process_content';
        }

        foreach ( $bad_filters as $filter => &$callbacks ) {
            foreach ( $callbacks as &$callback_info ) {
                if ( has_filter( $filter, is_array( $callback_info ) ? $callback_info[0] : $callback_info ) ) {
                    remove_filter( $filter, is_array( $callback_info ) ? $callback_info[0] : $callback_info, is_array( $callback_info ) ? $callback_info[1] : 10 );
                }
            }
        }

    }

    /*
     * Fix issues with Jetpack.
     */
    public function _jetpack_compat( &$wp ) {
        static $incompatible_actions = array( 'submitlisting', 'editlisting' );

        // TODO: fix before themes-release
        $action = '';

        if ( ! $action ) {
            return;
        }

        if ( defined( 'JETPACK__VERSION' ) && in_array( $action, $incompatible_actions ) ) {
            add_filter( 'jetpack_enable_opengraph', '__return_false', 99 );
            remove_action( 'wp_head', 'jetpack_og_tags' );
        }
    }

}
