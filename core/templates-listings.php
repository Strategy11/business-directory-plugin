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

function wpbdp_latest_listings($n=10, $before='<ul>', $after='</ul>', $before_item='<li>', $after_item = '</li>') {
    $n = max(intval($n), 0);

    $posts = get_posts(array(
        'post_type' => WPBDP_POST_TYPE,
        'post_status' => 'publish',
        'numberposts' => $n,
        'orderby' => 'date',
        'suppress_filters' => false,
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
