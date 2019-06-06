<?php //phpcs:disable
/**
 * @since 5.0
 * @SuppressWarnings(PHPMD)
 */
class WPBDP__CPT_Integration {

    public function __construct() {
        $this->register_post_type();
    }

    private function register_post_type() {
        // Listing type.
        $args = array(
            'labels' => array(
                'name' => _x( 'Directory', 'post type general name', 'WPBDM' ),
                'singular_name' => _x( 'Listing', 'post type singular name', 'WPBDM' ),
                'add_new' => _x( 'Add New Listing', 'listing', 'WPBDM' ),
                'add_new_item' => _x( 'Add New Listing', 'post type', 'WPBDM' ),
                'edit_item' => __( 'Edit Listing', 'WPBDM' ),
                'new_item' => __( 'New Listing', 'WPBDM' ),
                'view_item' => __( 'View Listing', 'WPBDM' ),
                'search_items' => __( 'Search Listings', 'WPBDM' ),
                'not_found' =>  __( 'No listings found', 'WPBDM' ),
                'not_found_in_trash' => __( 'No listings found in trash', 'WPBDM' )
            ),
            'public' => true,
            'menu_icon' => WPBDP_URL . 'assets/images/menuico.png',
            'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields' ),
            'rewrite' => array(
                'slug' => wpbdp_get_option( 'permalinks-directory-slug', WPBDP_POST_TYPE ),
                'with_front' => true,
                'feeds' => true
            )
        );
        register_post_type( WPBDP_POST_TYPE, $args );

        // Category tax.
        $cat_args = array(
            'labels' => array(
                'name' => __( 'Directory Categories', 'WPBDM' ),
                'singular_name' => __( 'Directory Category', 'WPBDM' )
            ),
            'hierarchical' => true,
            'public' => true,
            'rewrite' => array( 'slug' => wpbdp_get_option( 'permalinks-category-slug', WPBDP_CATEGORY_TAX ) )
        );
        register_taxonomy( WPBDP_CATEGORY_TAX, WPBDP_POST_TYPE, $cat_args );

        // Tag tax.
        $tags_args = array(
            'labels' => array(
                'name' => __( 'Directory Tags', 'WPBDM' ),
                'singular_name' => __( 'Directory Tag', 'WPBDM' )
            ),
            'hierarchical' => false,
            'public' => true,
            'rewrite' => array( 'slug' => wpbdp_get_option( 'permalinks-tags-slug', WPBDP_TAGS_TAX ) )
        );

        $tags_slug = wpbdp_get_option( 'permalinks-tags-slug', WPBDP_TAGS_TAX );
        register_taxonomy( WPBDP_TAGS_TAX, WPBDP_POST_TYPE, $tags_args );
    }

    public function register_hooks() {
        add_filter( 'post_type_link', array( &$this, '_post_link' ), 10, 3 );
        add_filter( 'get_shortlink', array( &$this, '_short_link' ), 10, 4 );
        // add_filter('post_type_link', array($this, '_post_link_qtranslate'), 11, 2); // basic support for qTranslate
        add_filter('preview_post_link', array($this, '_preview_post_link'), 10, 2);

        add_filter('term_link', array($this, '_category_link'), 10, 3);
        add_filter('term_link', array($this, '_tag_link'), 10, 3);

        add_filter('comments_open', array($this, '_allow_comments'), 10, 2);

        add_action( 'before_delete_post', array( &$this, 'after_listing_delete' ) );
        add_action( 'delete_term', array( &$this, 'handle_delete_term' ), 10, 3 );

        add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
    }

    public function _category_link($link, $category, $taxonomy) {
        if ( WPBDP_CATEGORY_TAX != $taxonomy )
            return $link;

        if ( ! wpbdp_rewrite_on() ) {
            if ( wpbdp_get_option( 'disable-cpt' ) )
                return wpbdp_url( '/' ) . '&_' . wpbdp_get_option( 'permalinks-category-slug' ) . '=' . $category->slug;

            return $link;
        }

        $link  = wpbdp_url(
            sprintf(
                '/%s/%s%s',
                wpbdp_get_option( 'permalinks-category-slug' ),
                $category->slug,
                substr( $link, -1) === '/' ? '/' : ''
            )
        );

        return apply_filters( 'wpbdp_category_link', $link, $category );
    }

    public function _tag_link($link, $tag, $taxonomy) {
        if ( WPBDP_TAGS_TAX != $taxonomy )
            return $link;

        if ( ! wpbdp_rewrite_on() ) {
            if ( wpbdp_get_option( 'disable-cpt' ) )
                $link = wpbdp_url( '/' ) . '&_' . wpbdp_get_option( 'permalinks-tags-slug' ) . '=' . $tag->slug;

            return $link;
        }

        $link  = wpbdp_url(
            sprintf(
                '/%s/%s%s',
                wpbdp_get_option( 'permalinks-tags-slug' ),
                $tag->slug,
                substr( $link, -1) === '/' ? '/' : ''
            )
        );

        return $link;
    }

    public function _post_link( $link, $post = null, $leavename = false ) {
        if ( WPBDP_POST_TYPE != get_post_type( $post ) )
            return $link;

        if ( $querystring = parse_url( $link, PHP_URL_QUERY ) )
            $querystring = '?' . $querystring;
        else
            $querystring = '';

        $querystring = substr( $link, -1) === '/' || $querystring ? '/' : '' . $querystring;

        if ( ! wpbdp_rewrite_on() ) {
            if ( wpbdp_get_option( 'disable-cpt' ) ) {
                $link = wpbdp_url( '/' ) . '&' . '_' . wpbdp_get_option( 'permalinks-directory-slug' ) . '=' . $post->post_name;
            }
        } else {
            if ( $leavename )
                return wpbdp_url( '/' . '%' . WPBDP_POST_TYPE . '%' . $querystring );

            if ( wpbdp_get_option( 'permalinks-no-id' ) ) {
                if ( $post->post_name ) {
                    $link = wpbdp_url( '/' . $post->post_name );
                } else {
                    // Use default $link.
                    return $link;
                }
            } else {
                $link = wpbdp_url( '/' . $post->ID . '/' . $post->post_name );
            }

            $link .= $querystring;
        }

        return apply_filters( 'wpbdp_listing_link', $link, $post->ID );
    }

    public function _short_link( $shortlink, $id = 0, $context = 'post', $allow_slugs = true ) {
        if ( 'post' !== $context || WPBDP_POST_TYPE != get_post_type( $id ) )
            return $shortlink;

        $post = get_post( $id );
        return $this->_post_link( $shortlink, $post );
    }

    public function _post_link_qtranslate( $url, $post ) {
        if ( is_admin() || !function_exists( 'qtrans_convertURL' ) )
            return $url;

        global $q_config;

        $lang = isset( $_GET['lang'] ) ? $_GET['lang'] : $q_config['language'];
        $default_lang = $q_config['default_language'];

        if ( $lang != $default_lang )
            return add_query_arg( 'lang', $lang, $url );

        return $url;
    }

    public function _preview_post_link( $url, $post = null ) {
        if ( is_null( $post ) && isset( $GLOBALS['post'] ) )
            $post = $GLOBALS['post'];

        if ( WPBDP_POST_TYPE != get_post_type( $post ) )
            return $url ;

        if ( wpbdp_rewrite_on() ) {
            if ( ! wpbdp_get_option( 'permalinks-no-id' ) || ! empty( $post->post_name ) ) {
                $url = remove_query_arg( array( 'post_type', 'p' ), $url );
            }
        }

        return $url;
    }

    public function _allow_comments($open, $post_id) {
        // comments on directory pages
        if ($post_id == wpbdp_get_page_id('main'))
            return false;

        // comments on listings
        if ( get_post_type( $post_id ) == WPBDP_POST_TYPE ) {
            return in_array(
                wpbdp_get_option( 'allow-comments-in-listings' ),
                array( 'allow-comments', 'allow-comments-and-insert-template' )
            );
        }

        return $open;
    }

    /**
     * Handles cleanup after a listing is deleted.
     * @since 3.4
     */
    public function after_listing_delete( $post_id ) {
        if ( WPBDP_POST_TYPE != get_post_type( $post_id ) )
            return;

        $listing = wpbdp_get_listing( $post_id );
        $listing->after_delete( 'delete_post' );
    }

    /**
     * @since 5.0
     */
    public function save_post( $post_id, $post, $update ) {
        if ( WPBDP_POST_TYPE != $post->post_type )
            return;

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;

        if ( ! $update ) {
            wpbdp_insert_log( array( 'log_type' => 'listing.created', 'object_id' => $post_id ) );
        }

        if ( 'auto-draft' == $post->post_status )
            return;

        $listing = wpbdp_get_listing( $post_id );
        $listing->_after_save( $update ? 'save_post' : 'submit-new' );
    }

    public function handle_delete_term( $term_id, $tt_id, $taxonomy ) {
        global $wpdb;

        if ( WPBDP_CATEGORY_TAX != $taxonomy )
            return;
    }

}
