<?php

class WPBDP__CPT_Compat_Mode {

    private $current_view = '';
    private $data = array();


    public function __construct() {
        add_filter( 'wpbdp_current_view', array( $this, 'maybe_change_current_view' ) );
        add_action( 'wpbdp_before_dispatch', array( $this, 'before_dispatch' ) );
        add_action( 'wpbdp_after_dispatch', array( $this, 'after_dispatch' ) );
    }

    public function maybe_change_current_view( $viewname ) {
        global $wp_query;

        $slug_dir = wpbdp_get_option( 'permalinks-directory-slug' );
        $slug_cat = wpbdp_get_option( 'permalinks-category-slug' );
        $slug_tag = wpbdp_get_option( 'permalinks-tags-slug' );

        if ( get_query_var( '_' . $slug_dir ) ) {
            $listing_id = $this->get_listing_id_from_query_var();

            if ( $listing_id ) {
                $this->data['listing_id'] = $listing_id;
                $this->current_view = 'show_listing';
            } else {
                $wp_query->set_404();
                $wp_query->set( 'page_id', null );
                $wp_query->set( 'p', null );
                return null;
            }
        } elseif ( get_query_var( '_' . $slug_cat ) ) {
            $this->current_view = 'show_category';
        } elseif ( get_query_var( '_' . $slug_tag ) ) {
            $this->current_view = 'show_tag';
        }

        if ( $this->current_view )
            return $this->current_view;

        return $viewname;
    }

    private function get_listing_id_from_query_var() {
        $id_or_slug = get_query_var( '_' . wpbdp_get_option( 'permalinks-directory-slug' ) );
        return wpbdp_get_post_by_id_or_slug( $id_or_slug, 'id', 'id' );
    }

    public function before_dispatch() {
        global $wp_query;

        $this->current_view = wpbdp_current_view();

        if ( ! $this->current_view )
            return;

        switch ( $this->current_view ) {
            case 'show_listing':
                $this->data['wp_query'] = $wp_query;

                if ( isset( $this->data['listing_id'] ) ) {
                    $listing_id = $this->data['listing_id'];
                } else {
                    $listing_id = $this->get_listing_id_from_query_var();
                }

                $args = array( 'post_type' => WPBDP_POST_TYPE,
                               'p' => $listing_id );
                $wp_query = new WP_Query( $args );
                $wp_query->the_post();

                break;

            case 'show_category':
                $this->data['wp_query'] = $wp_query;

                $args = array( WPBDP_CATEGORY_TAX => get_query_var( '_' . wpbdp_get_option( 'permalinks-category-slug' ) ) );
                $wp_query = $this->get_archive_query( $args );

                break;

            case 'show_tag':
                $this->data['wp_query'] = $wp_query;

                $args = array( WPBDP_TAGS_TAX => get_query_var( '_' . wpbdp_get_option( 'permalinks-tags-slug' ) ) );
                $wp_query = $this->get_archive_query( $args );

                break;
        }

        // wpbdp_debug_e( $wp_query, $this->current_view );
    }

    private function get_archive_query( $args ) {
        $args['wpbdp_main_query'] = true;
        $args['paged'] = get_query_var( 'paged' );
        $args['post_type'] = WPBDP_POST_TYPE;

        // $args = wp_parse_args( $args, array(
        //     'wpbdp_main_query' => true,
        //     'paged' => get_query_var( 'paged' ),
        //     'posts_per_page' => get_query_var( 'posts_per_page' ),
        //     'order' => get_query_var( 'order' ),
        //     'orderby' => get_query_var( 'orderby' ),
        // ) );

        return new WP_Query( $args );
    }

    public function after_dispatch() {
        global $wp_query;

        $this->current_view = wpbdp_current_view();

        switch ( $this->current_view ) {
            case 'show_listing':
            case 'show_category':
            case 'show_tag':
                $wp_query = $this->data['wp_query'];
                wp_reset_postdata();
                break;
        }
    }


}
