<?php
/**
 * @since 5.0
 */
class WPBDP__Meta {

    public function __construct() {
        add_action( 'wp_head', array( $this, '_rss_feed' ), 2 );
        add_filter( 'feed_links_show_posts_feed', array( $this, 'should_show_posts_feed_links' ) );
        add_filter( 'feed_links_show_comments_feed', array( $this, 'should_show_posts_feed_links' ) );

        if ( wpbdp_get_option( 'disable-cpt' ) ) {
            add_action( 'wp', array( &$this, '_meta_setup' ) );
            add_filter( 'wp_title', array( &$this, '_meta_title' ), 10, 3 );
            add_filter( 'pre_get_document_title', array( &$this, '_meta_title' ), 10, 3 );
        } else {
            add_filter( 'document_title_parts', array( &$this, 'set_view_title' ), 10 );
        }
    }

    public function _rss_feed() {
        $current_view = wpbdp_current_view();

        if ( ! $current_view ) {
            return;
        }

        if ( ! in_array( $current_view, array( 'main', 'show_category', 'all_listings' ), true ) ) {
            return;
        }

        $main_page_title = get_the_title( wpbdp_get_page_id() );
        $link_template = '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />' . PHP_EOL;
        $feed_links = array();

        if ( 'main' === $current_view || 'all_listings' === $current_view ) {
            $feed_title = sprintf( _x( '%s Feed', 'rss feed', 'WPBDM'), $main_page_title );
            $feed_url = esc_url( add_query_arg( 'post_type', WPBDP_POST_TYPE,  get_bloginfo( 'rss2_url' ) ) );

            $feed_links[] = sprintf( $link_template, $feed_title, $feed_url );
        }

        if ( 'show_category' === $current_view ) {
            $term = _wpbpd_current_category();

            if ( $term ) {
                $taxonomy = get_taxonomy( $term->taxonomy );
                $feed_title = sprintf( '%s &raquo; %s %s Feed', $main_page_title, $term->name, $taxonomy->labels->singular_name );
                $query_args = array( 'post_type' => WPBDP_POST_TYPE, WPBDP_CATEGORY_TAX => $term->slug );
                $feed_url = esc_url( add_query_arg( $query_args, get_bloginfo( 'rss2_url' ) ) );

                $feed_links[] = sprintf( $link_template, $feed_title, $feed_url );

                // Add dummy action to prevent https://core.trac.wordpress.org/ticket/40906
                add_action( 'wp_head', '__return_null', 3 );
                // Avoid two RSS URLs in Category pages.
                remove_action( 'wp_head', 'feed_links_extra', 3 );
            }
        }

        if ( $feed_links ) {
            echo '<!-- Business Directory RSS feed -->' . PHP_EOL;
            echo implode( '', $feed_links );
            echo '<!-- /Business Directory RSS feed -->' . PHP_EOL;
        }
    }

    public function should_show_posts_feed_links( $should ) {
        $current_view = wpbdp_current_view();

        if ( ! $current_view ) {
            return $should;
        }

        if ( ! in_array( $current_view, array( 'main', 'show_category', 'all_listings' ), true ) ) {
            return $should;
        }

        return false;
    }

    public function _meta_setup() {
        $action = wpbdp_current_view();

        $plugin_views_with_meta = array(
            'show_listing', 'show_category', 'show_tag',
            'all_listings', 'submit_listing', 'search'
        );

        if ( ! in_array( $action, $plugin_views_with_meta ) ) {
            return;
        }

        require_once( WPBDP_PATH . 'includes/class-page-meta.php' );
        $this->page_meta = new WPBDP_Page_Meta( $action );

        $this->_do_wpseo = defined( 'WPSEO_VERSION' ) ? true : false;

        if ( $this->_do_wpseo ) {
            $wpseo_front = $this->_get_wpseo_frontend();

            remove_filter( 'wp_title', array( $this, '_meta_title' ), 10, 3 );
            add_filter( 'wp_title', array( $this, '_meta_title' ), 16, 3 );
            add_filter( 'pre_get_document_title', array( $this, '_meta_title' ), 16 );

            if ( is_object( $wpseo_front ) ) {
                remove_filter( 'pre_get_document_title', array( &$wpseo_front, 'title' ), 15 );
                remove_filter( 'wp_title', array( &$wpseo_front, 'title' ), 15, 3 );
                remove_action( 'wp_head', array( &$wpseo_front, 'head' ), 1, 1 );
            }

            add_action( 'wp_head', array( $this, '_meta_keywords' ) );
        }

        remove_filter( 'wp_head', 'rel_canonical' );
        add_filter( 'wp_head', array( $this, '_meta_rel_canonical' ) );

        if ( 'show_listing' == $action && wpbdp_rewrite_on() ) {
            add_action( 'wp_head', array( &$this, 'listing_opentags' ) );
        }
    }

    private function _get_wpseo_frontend() {
        if ( isset( $GLOBALS['wpseo_front'] ) ) {
            return $GLOBALS['wpseo_front'];
        } elseif ( class_exists( 'WPSEO_Frontend' ) && method_exists( 'WPSEO_Frontend', 'get_instance' ) ) {
            return WPSEO_Frontend::get_instance();
        }
    }    

    public function set_view_title( $title ) {
        global $wp_query;

        if ( empty( $wp_query->wpbdp_view ) || ! is_array( $title ) )
            return $title;

        $current_view = wpbdp()->dispatcher->current_view_object();

        if ( ! $current_view )
            return $title;

        if ( $view_title = $current_view->get_title() )
            $title['title'] = $view_title;

        return $title;
    }

    public function _meta_title( $title = '', $sep = '»', $seplocation = 'right' ) {
        $wpseo_front = $this->_get_wpseo_frontend();

        $current_view = wpbdp_current_view();

        switch ( $current_view ) {
            case 'submit_listing':
                $view_title =  _x( 'Submit A Listing', 'views', 'WPBDM' );
                return $this->_maybe_do_wpseo_title( $view_title, $title, $sep, $seplocation );
                break;

            case 'search':
                $view_title =  _x( 'Find a Listing', 'title', 'WPBDM' );
                return $this->_maybe_do_wpseo_title( $view_title, $title, $sep, $seplocation );
                break;

            case 'all_listings':
                $view_title = _x( 'View All Listings', 'title', 'WPBDM' );
                return $this->_maybe_do_wpseo_title( $view_title, $title, $sep, $seplocation );
                break;

            case 'show_tag':
                $term = get_term_by(
                    'slug',
                    get_query_var( '_' . wpbdp_get_option( 'permalinks-tags-slug' ) ),
                    WPBDP_TAGS_TAX
                );

                if ( ! $term ) {
                    return $title;
                }

                if ( $this->_do_wpseo ) {
                    if ( method_exists( 'WPSEO_Taxonomy_Meta', 'get_term_meta' ) ) {
                        $title = WPSEO_Taxonomy_Meta::get_term_meta( $term, $term->taxonomy, 'title' );
                    } else {
                        $title = trim( wpseo_get_term_meta( $term, $term->taxonomy, 'title' ) );
                    }

                    if ( !empty( $title ) )
                        return wpseo_replace_vars( $title, (array) $term );

                    if ( is_object( $wpseo_front ) )
                        return $wpseo_front->get_title_from_options( 'title-tax-' . $term->taxonomy, $term );
                }

                return sprintf( _x( 'Listings tagged: %s', 'title', 'WPBDM' ), $term->name ) . ' ' . $sep . ' ' . $title;

                break;

            case 'show_category':
                $term = get_term_by(
                    'slug',
                    get_query_var( '_' . wpbdp_get_option( 'permalinks-category-slug' ) ),
                    WPBDP_CATEGORY_TAX
                );

                if ( ! $term && get_query_var( 'category_id' ) ) {
                    $term = get_term_by( 'id', get_query_var( 'category_id' ), WPBDP_CATEGORY_TAX );
                }

                if ( ! $term ) {
                    return $title;
                }

                if ( $this->_do_wpseo ) {
                    if ( method_exists( 'WPSEO_Taxonomy_Meta', 'get_term_meta' ) ) {
                        $title = WPSEO_Taxonomy_Meta::get_term_meta( $term, $term->taxonomy, 'title' );
                    } else {
                        $title = trim( wpseo_get_term_meta( $term, $term->taxonomy, 'title' ) );
                    }

                    if ( !empty( $title ) )
                        return wpseo_replace_vars( $title, (array) $term );

                    if ( is_object( $wpseo_front ) )
                        return $wpseo_front->get_title_from_options( 'title-tax-' . $term->taxonomy, $term );
                }

                return $term->name . ' ' . $sep . ' ' . $title;

                break;

            case 'show_listing':
                $listing_id = wpbdp_get_post_by_id_or_slug(
                    get_query_var( '_' . wpbdp_get_option( 'permalinks-directory-slug' ) ),
                    'id',
                    'id'
                );

                if ( $this->_do_wpseo ) {
                    $title = $wpseo_front->get_content_title( get_post( $listing_id ) );
                    $title = esc_html( strip_tags( stripslashes( apply_filters( 'wpseo_title', $title ) ) ) );

                    return $title;
                    break;
                } else {
                    $post_title = get_the_title($listing_id);
                }

                return $post_title . ' '.  $sep . ' ' . $title;
                break;

            case 'main':
                break;

            default:
                break;
        }

        return $title;
    }

    private function _maybe_do_wpseo_title( $view_title, $title, $sep, $seplocation ) {
        $wpseo_front = $this->_get_wpseo_frontend();

        if ( $this->_do_wpseo && is_object( $wpseo_front ) ) {
            return $wpseo_front->get_title_from_options( 'title-page', array( 'post_title' => $view_title ) );
        }

        if ( 'left' == $seplocation ) {
            return $title . ' ' . $sep . ' ' . $view_title;
        } else {
            return $view_title . ' ' . $sep . ' ' . $title;
        }
    }    

    public function _meta_keywords() {
        $wpseo_front = $this->_get_wpseo_frontend();

        $current_view = wpbdp_current_view();

        switch ( $current_view ){
            case 'show_listing':
                global $post;

                $listing_id = wpbdp_get_post_by_id_or_slug(
                    get_query_var( '_' . wpbdp_get_option( 'permalinks-directory-slug' ) ),
                    'id',
                    'id'
                );

                $prev_post = $post;
                $post = get_post( $listing_id );

                if ( is_object( $wpseo_front ) ) {
                    $wpseo_front->metadesc();
                    $wpseo_front->metakeywords();
                    $wpseo_front->webmaster_tools_authentication();
                }

                $post = $prev_post;

                break;
            case 'show_category':
            case 'show_tag':
                if ( $current_view == 'show_tag' ) {
                    $term = get_term_by(
                        'slug',
                        get_query_var( '_' . wpbdp_get_option( 'permalinks-tags-slug' ) ),
                        WPBDP_TAGS_TAX
                    );
                } else {
                    $term = get_term_by(
                        'slug',
                        get_query_var( '_' . wpbdp_get_option( 'permalinks-category-slug' ) ),
                        WPBDP_CATEGORY_TAX
                    );

                    if ( ! $term && get_query_var( 'category_id' ) ) {
                        $term = get_term_by( 'id', get_query_var( 'category_id' ), WPBDP_CATEGORY_TAX );
                    }
                }

                if ( $term ) {
                    $metadesc = method_exists( 'WPSEO_Taxonomy_Meta', 'get_term_meta' ) ?
                                WPSEO_Taxonomy_Meta::get_term_meta( $term, $term->taxonomy, 'desc' ) :
                                wpseo_get_term_meta( $term, $term->taxonomy, 'desc' );

                    if ( !$metadesc && is_object( $wpseo_front ) && isset( $wpseo_front->options['metadesc-tax-' . $term->taxonomy] ) )
                        $metadesc = wpseo_replace_vars( $wpseo_front->options['metadesc-tax-' . $term->taxonomy], (array) $term );

                    if ( $metadesc )
                        echo '<meta name="description" content="' . esc_attr( strip_tags( stripslashes( $metadesc ) ) ) . '"/>' . "\n";
                }

                break;

            case 'main':
                if ( is_object( $wpseo_front ) ) {
                    $wpseo_front->metadesc();
                    $wpseo_front->metakeywords();
                    $wpseo_front->webmaster_tools_authentication();
                }

                break;

            default:
                break;
        }

    }

    public function _meta_rel_canonical() {
        $action = wpbdp_current_view();

        if ( !$action )
            return rel_canonical();

        $not_supported_views = array(
            'edit_listing', 'submit_listing', 'delete_listing', 'renew_listing',
            'listing_contact'
        );

        if ( in_array( $action, $not_supported_views ) )
            return;

        if ( $action == 'show_listing' ) {
            $listing_id = wpbdp_get_post_by_id_or_slug(
                get_query_var( '_' . wpbdp_get_option( 'permalinks-directory-slug' ) ),
                'id',
                'id'
            );
            $url = get_permalink( $listing_id );
        } else {
            $url = site_url( $_SERVER['REQUEST_URI'] );
        }

        echo sprintf( '<link rel="canonical" href="%s" />', esc_url( user_trailingslashit( $url ) ) );
    }

    function listing_opentags() {
        $listing_id = wpbdp_get_post_by_id_or_slug(
            get_query_var( '_' . wpbdp_get_option( 'permalinks-directory-slug' ) ),
            'id',
            'id'
        );

        $listing = WPBDP_Listing::get( $listing_id );

        if ( ! $listing )
            return;

        echo '<meta property="og:type" content="website" />';
        echo '<meta property="og:title" content="' . esc_attr( WPBDP_SEO::listing_title( $listing_id ) ) . '" />';
        echo '<meta property="og:url" content="' . esc_url( user_trailingslashit( $listing->get_permalink() ) ) . '" />';
        echo '<meta property="og:description" content="' . esc_attr( WPBDP_SEO::listing_og_description( $listing_id ) ) . '" />';

        if ( $thumbnail_id = $listing->get_thumbnail_id() ) {
            if ( $img = wp_get_attachment_image_src( $thumbnail_id, 'wpbdp-large' ) )
                echo '<meta property="og:image" content="' . $img[0] . '" />';
        } else {
            $image_url = WPBDP_URL . 'assets/images/default-image-big.gif';
            echo '<meta property="og:image" content="' . $image_url . '" />';
        }
    }    

}
