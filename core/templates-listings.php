<?php
require_once ( WPBDP_PATH . 'core/helpers/class-listing-display-helper.php' );


/**
 * Displays a single listing view taking into account all of the theme overrides.
 * @param mixed $listing_id listing object or listing id to display.
 * @param string $view 'single' for single view or 'excerpt' for summary view.
 * @return string HTML output.
 */
function wpbdp_render_listing($listing_id=null, $view='single', $echo=false) {
    $listing_id = $listing_id ? ( is_object( $listing_id ) ? $listing_id->ID : absint( $listing_id ) ) : get_the_ID();

    $args = array( 'post_type' => WPBDP_POST_TYPE, 'p' => $listing_id );
    if ( ! current_user_can( 'edit_posts' ) )
        $args['post_status'] = 'publish';

    $q = new WP_Query( $args );
    if ( ! $q->have_posts() )
        return '';

    $q->the_post();

    // TODO: review filters/actions before next-release (previously _wpbdp_render_excerpt() and _wpbdp_render_single().
    if ( 'excerpt' == $view )
        $html = WPBDP_Listing_Display_Helper::excerpt();
    else
        $html = WPBDP_Listing_Display_Helper::single();

    if ( $echo )
        echo $html;

    wp_reset_postdata();

    return $html;
}

function _wpbdp_render_single() {
    global $post;

    $listing = WPBDP_Listing::get( $post->ID );

    $html  = '';
    $html .= sprintf( '<div id="wpbdp-listing-%d" class="%s" itemscope itemtype="http://schema.org/LocalBusiness">',
                      $post->ID,
                      wpbdp_listing_css_class( array( 'single', 'wpbdp-listing-single' ) ) );
    $html .= apply_filters('wpbdp_listing_view_before', '', $post->ID, 'single');
    $html .= wpbdp_capture_action('wpbdp_before_single_view', $post->ID);

    $sticky_status = $listing->get_sticky_status();
    $sticky_tag = '';
    if ($sticky_status == 'sticky')
        $sticky_tag = sprintf('<div class="stickytag"><img src="%s" alt="%s" border="0" title="%s"></div>',
                        WPBDP_URL . 'core/images/featuredlisting.png',
                        _x('Featured Listing', 'templates', 'WPBDM'),
                        the_title(null, null, false));

    $d = WPBDP_ListingFieldDisplayItem::prepare_set( $post->ID, 'listing' );
    $listing_fields = implode( '', WPBDP_ListingFieldDisplayItem::walk_set( 'html', $d->fields ) );
    $social_fields = implode( '', WPBDP_ListingFieldDisplayItem::walk_set( 'html', $d->social ) );

    // images
    $thumbnail_id = $listing->get_thumbnail_id();
    $images = $listing->get_images( 'all', true );
    $extra_images = array();

    if ( wpbdp_get_option( 'allow-images' ) ) {
        foreach ($images as $img) {
            // create thumbnail of correct size if needed (only in single view to avoid consuming server resources)
            _wpbdp_resize_image_if_needed( $img->id );

            if ($img->id == $thumbnail_id) continue;

            $full_image_data = wp_get_attachment_image_src( $img->id, 'wpbdp-large', false );
            $full_image_url = $full_image_data[0];

            $extra_images[] = sprintf('<a href="%s" class="thickbox" data-lightbox="wpbdpgal" rel="wpbdpgal" target="_blank">%s</a>',
                                        $full_image_url,
                                        wp_get_attachment_image( $img->id, 'wpbdp-thumb', false, array(
                                            'class' => 'wpbdp-thumbnail size-thumbnail',
                                            'alt' => the_title(null, null, false),
                                            'title' => the_title(null, null, false)
                                        ) ));
        }
    }

    $vars = array(
        'actions' => wpbdp_render('parts/listing-buttons', array('listing_id' => $post->ID, 'view' => 'single'), false),
        'is_sticky' => $sticky_status == 'sticky',
        'sticky_tag' => $sticky_tag,
        'title' => get_the_title(),
        'main_image' => wpbdp_get_option( 'allow-images' ) ? wpbdp_listing_thumbnail( null, 'link=picture&class=wpbdp-single-thumbnail' ) : '',
        'listing_fields' => apply_filters('wpbdp_single_listing_fields', $listing_fields, $post->ID),
        'fields' => $d->fields,
        'listing_id' => $post->ID,
        'extra_images' => $extra_images
    );
    $vars = apply_filters( 'wpbdp_listing_template_vars', $vars, $post->ID );
    $vars = apply_filters( 'wpbdp_single_template_vars', $vars, $post->ID );

    $html .= wpbdp_render('businessdirectory-listing', $vars, true);

    $social_fields = apply_filters('wpbdp_single_social_fields', $social_fields, $post->ID);
    if ($social_fields)
        $html .= '<div class="social-fields cf">' . $social_fields . '</div>';

    $html .= apply_filters('wpbdp_listing_view_after', '', $post->ID, 'single');
    $html .= wpbdp_capture_action('wpbdp_after_single_view', $post->ID);

    if (wpbdp_get_option('show-comment-form')) {
        $html .= '<div class="comments">';

        ob_start();
        comments_template(null, true);
        $html .= ob_get_contents();
        ob_end_clean();

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

function _wpbdp_render_excerpt() {
    global $post;
    static $counter = 0;

    $listing = WPBDP_Listing::get( $post->ID );

    $html = '';
    $html .= sprintf('<div id="wpbdp-listing-%d" class="%s">',
                     $post->ID,
                     wpbdp_listing_css_class( array( ( $counter & 1 ) ? 'odd' : 'even',
                                                     'excerpt',
                                                     'wpbdp-listing-excerpt' ) ) );
    $html .= wpbdp_capture_action( 'wpbdp_before_excerpt_view', $post->ID );

    $d = WPBDP_ListingFieldDisplayItem::prepare_set( $post->ID, 'excerpt' );
    $listing_fields = implode( '', WPBDP_ListingFieldDisplayItem::walk_set( 'html', $d->fields ) );
    $social_fields = implode( '', WPBDP_ListingFieldDisplayItem::walk_set( 'html', $d->social ) );

    $sticky_status = $listing->get_sticky_status();

    $vars = array(
        'is_sticky' => $sticky_status == 'sticky',
        'thumbnail' => ( wpbdp_get_option( 'allow-images' ) && wpbdp_get_option( 'show-thumbnail' ) ) ? wpbdp_listing_thumbnail( null, 'link=listing&class=wpbdmthumbs wpbdp-excerpt-thumbnail' ) : '',
        'title' => get_the_title(),
        'listing_fields' => apply_filters('wpbdp_excerpt_listing_fields', $listing_fields, $post->ID),
        'fields' => $d->fields,
        'listing_id' => $post->ID
    );
    $vars = apply_filters( 'wpbdp_listing_template_vars', $vars, $post->ID );
    $vars = apply_filters( 'wpbdp_excerpt_template_vars', $vars, $post->ID );

    $html .= wpbdp_render('businessdirectory-excerpt', $vars, true);

    $social_fields = apply_filters('wpbdp_excerpt_social_fields', $social_fields, $post->ID);
    if ($social_fields)
        $html .= '<div class="social-fields cf">' . $social_fields . '</div>';

    $html .= wpbdp_capture_action('wpbdp_after_excerpt_view', $post->ID);
    $html .= wpbdp_render('parts/listing-buttons', array('listing_id' => $post->ID, 'view' => 'excerpt'), false);
    $html .= '</div>';

    $counter++;

    return $html;
}

function wpbdp_latest_listings($n=10, $before='<ul>', $after='</ul>', $before_item='<li>', $after_item = '</li>') {
    $n = max(intval($n), 0);

    $posts = get_posts(array(
        'post_type' => WPBDP_POST_TYPE,
        'post_status' => 'publish',
        'numberposts' => $n,
        'orderby' => 'date'
    ));

    $html = '';

    $html .= $before;

    foreach ($posts as $post) {
        $html .= $before_item;
        $html .= sprintf('<a href="%s">%s</a>', get_permalink($post->ID), get_the_title($post->ID));
        $html .= $after_item;
    }

    $html .= $after;

    return $html;
}

/**
 * @since 3.6.10
 */
function wpbdp_listing_css_class( $class_ = '', $post_id = null ) {
    global $wpdb;
    global $post;

    if ( ! $post_id && $post )
        $post_id = $post->ID;

    if ( WPBDP_POST_TYPE != get_post_type( $post_id ) )
        return '';

    $listing = WPBDP_Listing::get( $post_id );

    $css_classes = array();
    $css_classes[] = 'wpbdp-listing';
    $css_classes[] = 'wpbdp-listing-' . $post_id;
    $css_classes[] = 'cf';

    if ( is_string( $class_ ) )
        $css_classes = array_merge( $css_classes, explode( ' ', $class_ ) );
    elseif ( is_array( $class_ ) )
        $css_classes = array_merge( $css_classes, $class_ );

    // Sticky status.
    $sticky = $listing->get_sticky_status();
    $css_classes[] = $sticky; // For backwards compat.
    $css_classes[] = 'wpbdp-' . $sticky;
    $css_classes[] = 'wpbdp-level-' . $sticky;

    // Fees and categories.
    $listing = WPBDP_Listing::get( $post_id );
    foreach ( $listing->get_categories() as $c ) {
        $css_classes[] = 'wpbdp-listing-category-' . $c->term_id;
        $css_classes[] = 'wpbdp-listing-category-' . $c->slug;

        $css_classes[] = 'wpbdp-listing-fee-' . $c->fee_id;

        if ( isset( $c->fee ) && isset( $c->fee->label ) )
            $css_classes[] = 'wpbdp-listing-fee-' . WPBDP_Utils::normalize( $c->fee->label );
    }

    $css_classes = apply_filters( 'wpbdp_listing_css_class', $css_classes, $post_id );

    return implode( ' ', $css_classes );
}

/**
 * @since 4.0
 */
function wpbdp_the_listing_actions( $args = array() ) {
    echo wpbdp_listing_actions();
}

/**
 * @since 4.0
 */
function wpbdp_listing_actions( $args = array() ) {
    return wpbdp_render( 'parts/listing-buttons',
                         array( 'listing_id' => get_the_ID(),
                         'view' => 'excerpt' ),
                         false );
}
