<?php
/**
 * @since 4.0
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
                    array( $this, 'sc_main' ),
                    array( 'business-directory', 'WPBUSDIRMANUI' ) );
        $this->add( 'businessdirectory-submit-listing',
                    array( $this, 'sc_submit_listing' ),
                    array( 'businessdirectory-submitlisting', 'business-directory-submitlisting', 'business-directory-submit-listing', 'WPBUSDIRMANADDLISTING' ) );
        $this->add( 'businessdirectory-manage-listings',
                    array( $this, 'sc_manage_listings' ),
                    array( 'businessdirectory-managelistings', 'business-directory-manage-listings', 'businessdirectory-manage_listings', 'WPBUSDIRMANMANAGELISTING' ) );
        $this->add( 'businessdirectory-listings',
                    array( $this, 'sc_listings' ),
                    array( 'WPBUSDIRMANVIEWLISTINGS', 'WPBUSDIRMANMVIEWLISTINGS', 'businessdirectory-view_listings', 'businessdirectory-viewlistings' ) );
        $this->add( 'businessdirectory-search',
                    array( $this, 'sc_search' ),
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

    public function sc_main( $atts ) {
        global $wp_query;

        // if ( empty( $wp_query->wpbdp_is_main_page ) )
        //     return '';

        return wpbdp_current_view_output();
    }

    public function sc_submit_listing() {
        $v = wpbdp_load_view( 'submit_listing' );
        return $v->dispatch();
    }

    public function sc_listings( $atts ) {
        global $wpbdp;
        require_once ( WPBDP_PATH . 'core/views/all_listings.php' );

        $atts = shortcode_atts( array( 'tag' => '',
                                       'tags' => '',
                                       'category' => '',
                                       'categories' => '',
                                       'title' => '',
                                       'operator' => 'OR',
                                       'author' => '',
                                       'menu' => null,
                                       'items_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1 ),
                                $atts );

        if ( ! is_null( $atts['menu'] ) )
            $atts['menu'] = ( 1 === $atts['menu'] || 'true' === $atts['menu'] ) ? true : false;

        $query_args = array();
        $query_args['items_per_page'] = $atts['items_per_page'];

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

            $query_args['tax_query'] = array( array( 'taxonomy' => WPBDP_CATEGORY_TAX,
                                                     'field' => 'id',
                                                     'terms' => $categories ) );
        } elseif ( $atts['tag'] || $atts['tags'] ) {
            $requested_tags = array();

            if ( $atts['tag'] )
                $requested_tags = array_merge( $requested_tags, explode( ',', $atts['tag'] ) );

            if ( $atts['tags'] )
                $requested_tags = array_merge( $requested_tags, explode( ',', $atts['tags'] ) );

            $query_args['tax_query'] = array( array( 'taxonomy' => WPBDP_TAGS_TAX,
                                                     'field' => 'slug',
                                                     'terms' => $requested_tags ) );
        }

        if ( ! empty( $atts['author'] ) ) {
            $u = false;
            $u = is_numeric( $atts['author'] ) ? get_user_by( 'id', absint( $atts['author'] ) ) : get_user_by( 'login', $atts['author'] );

            if ( $u )
                $query_args['author'] = $u->ID;
        }

        $v = new WPBDP__Views__All_Listings( array(  'menu' => $atts['menu'], 'query_args' => $query_args ) );
        return $v->dispatch();
    }

    public function sc_featured_listings( $atts ) {
        global $wpbdp;

        $atts = shortcode_atts( array( 'number_of_listings' => wpbdp_get_option( 'listings-per-page' ) ), $atts );
        $atts['number_of_listings'] = max( 0, intval( $atts['number_of_listings'] ) );

        $args = array(
            'post_type' => WPBDP_POST_TYPE,
            'post_status' => 'publish',
            'paged' => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
            'posts_per_page' => $atts['number_of_listings'],
            'meta_query' => array( array( 'key' => '_wpbdp[sticky]', 'value' => 'sticky' ) )
        );
        $q = new WP_Query( $args );
        wpbdp_push_query( $q );

        $html = wpbdp_x_render( 'listings', array( 'query' => $q ) );

        wpbdp_pop_query();

        return $html;
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
     * @since 4.0
     */
    function sc_categories( $atts ) {
        return wpbdp_list_categories( $atts );
    }

    /**
     * @since 4.0
     */
    public function sc_count( $atts ) {
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

    public function sc_manage_listings() {
        $v = wpbdp_load_view( 'manage_listings' );
        return $v->dispatch();
    }

    public function sc_search() {
        $v = wpbdp_load_view( 'search' );
        return $v->dispatch();
    }

    //
    // }}
    //

}

