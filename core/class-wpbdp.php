<?php
require_once( WPBDP_PATH . 'core/class-query-integration.php' );
require_once( WPBDP_PATH . 'core/class-dispatcher.php' );
require_once( WPBDP_PATH . 'core/class-wordpress-template-integration.php' );


final class WPBDP {

    // FIXME: only to allow the global object to access it (for now).
    public $dispatcher = null;
    public $query_integration = null;
    public $template_integration = null;


    public function __construct() {
    }

    public function init() {
        $this->register_post_type();

        $this->query_integration = new WPBDP__Query_Integration();
        $this->dispatcher = new WPBDP__Dispatcher();
        $this->template_integration = new WPBDP__WordPress_Template_Integration();
    }

    public function register_post_type() {
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
            'menu_icon' => WPBDP_URL . 'admin/resources/menuico.png',
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

}

?>
