<?php
/**
 * @since next-release
 */
class WPBDP__Shortcodes {

    private $shortcodes = array();


    public function __construct() {}

    /**
     * Returns shortcodes being handled by Business Directory.
     * @return array array of `shortcode => callback` items.
     */
    public function get_shortcodes() {
        return $this->shortcodes;
    }

    public function register() {
        if ( ! empty( $this->shortcodes ) )
            return;

        // TODO: change this to use the actual views or a "generic callback" that actually loads the view and returns
        // the output.
        global $wpbdp;

        $this->add( 'businessdirectory',
                    array( $wpbdp->controller, 'dispatch' ),
                    array( 'business-directory', 'WPBUSDIRMANUI' ) );
        $this->add( 'businessdirectory-submit-listing',
                    array( $wpbdp->controller, 'submit_listing' ),
                    array( 'businessdirectory-submitlisting', 'business-directory-submitlisting', 'business-directory-submit-listing', 'WPBUSDIRMANADDLISTING' ) );
        $this->add( 'businessdirectory-manage-listings',
                    array( $wpbdp->controller, 'manage_listings' ),
                    array( 'businessdirectory-managelistings', 'business-directory-manage-listings', 'businessdirectory-manage_listings' ) );
        $this->add( 'businessdirectory-listings',
                    array( $this, 'sc_listings' ),
                    array( 'WPBUSDIRMANVIEWLISTINGS', 'WPBUSDIRMANMVIEWLISTINGS', 'businessdirectory-view_listings', 'businessdirectory-viewlistings' ) );
        $this->add( 'businessdirectory-search',
                    array( $wpbdp->controller, 'search' ),
                    array( 'business-directory-search', 'businessdirectory_search', 'business-directory_search' ) );
        $this->add( 'businessdirectory-featuredlistings', array( $this, 'sc_featured_listings' ) );
        $this->add( 'businessdirectory-listing', array( $this, 'sc_single_listing' ) );
        $this->add( 'businessdirectory-categories', array( $this, 'sc_categories' ) );
        $this->add( 'businessdirectory-listing-count', array( $this, 'sc_count' ), array( 'bd-listing-count', 'business-directory-listing-count' ) );

        do_action_ref_array( 'wpbdp_shortcodes_register', array( &$this ) );

        $this->shortcodes = apply_filters( 'wpbdp_shortcodes', $this->shortcodes );

        foreach ( $this->shortcodes as $shortcode => &$handler )
            add_shortcode( $shortcode, $handler );
    }

    public function add( $shortcode, $callback, $aliases = array() ) {
        foreach ( array_merge( array( $shortcode ), $aliases ) as $alias )
            $this->shortcodes[ $alias ] = $callback;
    }

    //
    // {{ Built-in shortcodes.
    //

    public function sc_listings( $atts ) {
        global $wpbdp;

        $atts = shortcode_atts( array( 'tag' => '',
                                       'tags' => '',
                                       'category' => '',
                                       'categories' => '',
                                       'title' => '',
                                       'operator' => 'OR',
                                       'author' => '',
                                       'items_per_page' => '' ),

                                $atts );
        $atts = array_map( 'trim', $atts );
        if ( ! $atts['category'] && ! $atts['categories'] && ! $atts['tag'] && ! $atts['tags'] ) {
            $args = array();

            if ( ! empty( $atts['author'] ) ) {
                $u = false;

                if ( is_numeric( $atts['author'] ) )
                    $u = get_user_by( 'id', absint( $atts['author'] ) );
                else
                    $u = get_user_by( 'login', $atts['author'] );

                if ( $u )
                    $args['author'] = $u->ID;
            }

            return $wpbdp->controller->view_listings( true, $args );
        }

        if ( $atts['category'] || $atts['categories'] ) {
            $requested_categories = array();

            if ( $atts['category'] )
                $requested_categories = array_merge( $requested_categories, explode( ',', $atts['category'] ) );

            if ( $atts['categories'] )
                $requested_categories = array_merge( $requested_categories, explode( ',', $atts['categories'] ) );

            $categories = array();

            foreach ( $requested_categories as $cat ) {
                $term = null;
                if ( !is_numeric( $cat ) )
                    $term = get_term_by( 'slug', $cat, WPBDP_CATEGORY_TAX );

                if ( !$term && is_numeric( $cat ) )
                    $term = get_term_by( 'id', $cat, WPBDP_CATEGORY_TAX );

                if ( $term )
                    $categories[] = $term->term_id;
            }

            return $wpbdp->controller->browse_category( $categories, array('items_per_page'=> $atts['items_per_page']), true );
        } elseif ( $atts['tag'] || $atts['tags'] ) {
            $requested_tags = array();

            if ( $atts['tag'] )
                $requested_tags = array_merge( $requested_tags, explode( ',', $atts['tag'] ) );

            if ( $atts['tags'] )
                $requested_tags = array_merge( $requested_tags, explode( ',', $atts['tags'] ) );

            return $wpbdp->controller->browse_tag( array( 'tags' => $requested_tags, 'title' => $atts['title'], 'only_listings' => true ) );
        }

        return '';
    }

    public function sc_featured_listings( $atts ) {
        global $wpbdp;

        $atts = shortcode_atts( array( 'number_of_listings' => wpbdp_get_option( 'listings-per-page' ) ), $atts );
        $atts['number_of_listings'] = max( 0, intval( $atts['number_of_listings'] ) );

        return $wpbdp->controller->view_featured_listings( $atts );
    }

    /**
     * @since 3.6.10
     */
    function sc_single_listing( $atts ) {
        $atts = shortcode_atts( array( 'id' => null, 'slug' => null ), $atts );
        $listing_id = wpbdp_get_post_by_id_or_slug( $atts['id'] ? $atts['id'] : $atts['slug'], 'id', 'id' );

        if ( ! $listing_id )
            return '';

        return wpbdp_render_listing( $listing_id, 'single' );
    }

    /**
     * @since next-release
     */
    function sc_categories( $atts ) {
        return wpbdp_list_categories( $atts );
    }

    /**
     * @since next-release
     */
    public function listing_count_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'category' => false, 'region' => false ), $atts );
        extract( $atts );

        // All listings.
        if ( ! $category && ! $region ) {
            $count = wp_count_posts( WPBDP_POST_TYPE );
            return $count->publish;
        }

        if ( ! function_exists( 'wpbdp_regions_taxonomy' ) )
            $region = false;

        $term = false;
        $region_term = false;

        if ( $category ) {
            foreach ( array( 'id', 'name', 'slug' ) as $field ) {
                if ( $term = get_term_by( $field, $category, WPBDP_CATEGORY_TAX ) )
                    break;
            }
        }

        if ( $region ) {
            foreach ( array( 'id', 'name', 'slug' ) as $field ) {
                if ( $region_term = get_term_by( $field, $region, wpbdp_regions_taxonomy() ) )
                    break;
            }
        }

        if ( ( $region && ! $region_term ) || ( $category && ! $term ) )
            return '0';

        if ( $region ) {
            $regions_api = wpbdp_regions_api();
            return $regions_api->count_listings( (int) $region_term->term_id, $term ? (int) $term->term_id : 0 );
        } else {
            _wpbdp_padded_count( $term );
            return $term->count;
        }

        return '0';
    }

    //
    // }}
    //

}

