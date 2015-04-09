<?php

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

    if ($view == 'excerpt')
        $html = _wpbdp_render_excerpt();
    else
        $html = _wpbdp_render_single();

    if ( $echo )
        echo $html;

    wp_reset_postdata();

    return $html;
}

function _wpbdp_render_single() {
    global $post;

    $html = '';

    $sticky_status = wpbdp_listings_api()->get_sticky_status($post->ID);

    $html .= sprintf( '<div id="wpbdp-listing-%d" class="wpbdp-listing wpbdp-listing-single %s %s %s" itemscope itemtype="http://schema.org/LocalBusiness">',
                      $post->ID,
                      'single',
                      $sticky_status,
                      apply_filters( 'wpbdp_listing_view_css', '', $post->ID ) );
    $html .= apply_filters('wpbdp_listing_view_before', '', $post->ID, 'single');
    $html .= wpbdp_capture_action('wpbdp_before_single_view', $post->ID);

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
    $thumbnail_id = wpbdp_listings_api()->get_thumbnail_id($post->ID);
    $images = wpbdp_listings_api()->get_images($post->ID);
    $extra_images = array();

    if ( wpbdp_get_option( 'allow-images' ) ) {
        foreach ($images as $img) {
            // create thumbnail of correct size if needed (only in single view to avoid consuming server resources)
            _wpbdp_resize_image_if_needed( $img->ID );

            if ($img->ID == $thumbnail_id) continue;

            $full_image_data = wp_get_attachment_image_src( $img->ID, 'wpbdp-large', false );
            $full_image_url = $full_image_data[0];

            $extra_images[] = sprintf('<a href="%s" class="thickbox" data-lightbox="wpbdpgal" rel="wpbdpgal" target="_blank">%s</a>',
                                        $full_image_url,
                                        wp_get_attachment_image( $img->ID, 'wpbdp-thumb', false, array(
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

    $sticky_status = wpbdp_listings_api()->get_sticky_status($post->ID);

    $html = '';
    $html .= sprintf('<div id="wpbdp-listing-%d" class="wpbdp-listing excerpt wpbdp-listing-excerpt %s %s %s cf">',
                     $post->ID,
                     $sticky_status,
                     ($counter & 1) ? 'odd':  'even',
                     apply_filters( 'wpbdp_excerpt_view_css', '', $post->ID ) );
    $html .= wpbdp_capture_action('wpbdp_before_excerpt_view', $post->ID);

    $d = WPBDP_ListingFieldDisplayItem::prepare_set( $post->ID, 'excerpt' );
    $listing_fields = implode( '', WPBDP_ListingFieldDisplayItem::walk_set( 'html', $d->fields ) );
    $social_fields = implode( '', WPBDP_ListingFieldDisplayItem::walk_set( 'html', $d->social ) );

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
