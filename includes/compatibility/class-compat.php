<?php
/**
 * @package WPBDP/Compatibility
 */

require_once WPBDP_PATH . 'includes/compatibility/deprecated.php';
class WPBDP_Compat {

	public function __construct() {
		$this->workarounds_for_wp_bugs();
		$this->load_integrations();
        $this->priority_adjustment();

		if ( wpbdp_get_option( 'disable-cpt' ) ) {
			self::cpt_compat_mode();
		} else {
			require_once WPBDP_PATH . 'includes/compatibility/class-themes-compat.php';
			new WPBDP__Themes_Compat();
		}
	}

	public function load_integrations() {
		if ( isset( $GLOBALS['sitepress'] ) ) {
			require_once WPBDP_PATH . 'includes/compatibility/class-wpml-compat.php';
			new WPBDP_WPML_Compat();
		}

		if ( function_exists( 'bcn_display' ) ) {
			require_once WPBDP_PATH . 'includes/compatibility/class-navxt-integration.php';
			new WPBDP_NavXT_Integration();
		}

		if ( class_exists( 'Advanced_Excerpt' ) ) {
			require_once WPBDP_PATH . 'includes/compatibility/class-advanced-excerpt-integration.php';
			new WPBDP_Advanced_Excerpt_Integration();
		}

		if ( defined( 'CUSTOM_PERMALINKS_PLUGIN_VERSION' ) ) {
			require_once WPBDP_PATH . 'includes/compatibility/class-custom-permalinks-integration.php';
			new WPBDP_Custom_Permalink_Integration();
		}

		if ( class_exists( 'acf' ) && 'Bold Move' === wp_get_theme()->get( 'Name' ) ) {
			require_once WPBDP_PATH . 'includes/compatibility/class-acf-boldmove-compat.php';
			new WPBDP_ACF_Compat();
		}

		if ( class_exists( 'Cornerstone_Plugin' ) ) {
			require_once WPBDP_PATH . 'includes/compatibility/class-cornerstone-compat.php';
			new WPBDP_Cornerstone_Compat();
		}

		if ( class_exists( 'FLTheme' ) ) {
			require_once WPBDP_PATH . 'includes/compatibility/class-beaver-themer-compat.php';
			new WPBDP_Beaver_Themer_Compat();
		}

		// Yoast SEO.
		if ( defined( 'WPSEO_VERSION' ) ) {
			add_action( 'wp_head', array( &$this, 'yoast_maybe_force_taxonomy' ), 0 );
		}
		// WP Fusion and WooCommerce Invalid nonce.
		if ( defined( 'WP_FUSION_VERSION' ) && class_exists( 'WooCommerce' ) ) {
			add_filter(
                'wpf_skip_auto_login', array( $this, 'wp_fusion_skip_auto_login' ), 20 );
		}

		// Delete Elementor element caching for shortcodes.
		if ( class_exists( 'Elementor\Core\Base\Document' ) ) {
			add_action( 'template_redirect', array( $this, 'prevent_shortcodes_elementor_element_cache' ) );
		}
	}

	public function cpt_compat_mode() {
		require_once WPBDP_PATH . 'includes/compatibility/class-cpt-compat-mode.php';
		$nocpt = new WPBDP__CPT_Compat_Mode();
	}

	/**
	 * If the category page is using a page template for the current theme,
	 * remove the singular flag momentarily.
	 *
	 * @since 6.2.8
	 */
	public function yoast_maybe_force_taxonomy() {
		global $wp_query;
		if ( wpbdp_is_taxonomy() && $wp_query->is_singular ) {
			$wp_query->is_singular = false;
			add_action( 'wpseo_head', array( &$this, 'yoast_force_page' ), 9999 );
		}
	}

	/**
	 * Switch back to singular, since the current theme needs it.
	 *
	 * @since 6.2.8
	 */
	public function yoast_force_page() {
		global $wp_query;
		$wp_query->is_singular = true;
	}

	// Work around WP bugs. {{{
	public function workarounds_for_wp_bugs() {
		// #1466 (related to https://core.trac.wordpress.org/ticket/28081).
		add_filter( 'wpbdp_query_clauses', array( &$this, '_fix_pagination_issue' ), 10, 2 );
	}

	public function _fix_pagination_issue( $clauses, $query ) {
		$posts_per_page = intval( $query->get( 'posts_per_page' ) );
		$paged          = intval( $query->get( 'paged' ) );

		if ( -1 != $posts_per_page || $paged <= 1 ) {
			return $clauses;
		}

		// Force no results for pages outside of the scope of the query.
		$clauses['where'] .= ' AND 1=0 ';

		return $clauses;
	}

	/**
	 * Skip WP Fusion auto login .
	 *
	 * @param bool $skip_auto_login skip auto login.
	 *
	 * @return bool
	 */
	public function wp_fusion_skip_auto_login( $skip_auto_login ) {
		if ( $skip_auto_login || ( 'wpbdp_ajax' === wpbdp_get_var( array( 'param' => 'action' ) ) && 'checkout__load_gateway' === wpbdp_get_var( array( 'param' => 'handler' ) ) ) ) {
			return true;
		}
		return $skip_auto_login;
	}

	/**
	 * Checks if the current post content has a BD shortcode and deletes the Elementor cache.
	 * 
	 * @since 6.4.10
	 *
	 * @return void
	 */
	public function prevent_shortcodes_elementor_element_cache() {
		global $post, $wpbdp;

		if ( ! $post ) {
			return;
		}

		$cache_key  = Elementor\Core\Base\Document::CACHE_META_KEY;
		$shortcodes = get_shortcode_regex( array_keys( $wpbdp->shortcodes->get_shortcodes() ) );

		if ( preg_match( "/$shortcodes/", $post->post_content ) ) {
		    delete_post_meta( $post->ID, $cache_key );
		}
	}

    /**
     * Adjust the priority of the addtoany_content_priority filter.
     * 
     * @return void
     */
    private function priority_adjustment() {
        // AddToAny Social Share
        add_filter(
            'addtoany_content_priority', function () {
            return 1100;
        }, 100);
    }
}
