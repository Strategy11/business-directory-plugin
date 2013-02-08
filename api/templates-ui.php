<?php
/*
 * UI Functions to be called from templates.
 */

function wpbdp_directory_categories() {
    return wpbusdirman_post_list_categories();
}

function wpbdp_the_directory_categories() {
    echo wpbdp_directory_categories();
}

function wpbdp_main_links() {
    $html  = '';
    $html .= '<div class="wpbdp-main-links">';

    if (wpbdp_get_option('show-submit-listing')) {
        $html .= sprintf('<input id="wpbdp-bar-submit-listing-button" type="button" value="%s" onclick="window.location.href = \'%s\'" class="button" />',
                          __('Submit A Listing', 'WPBDM'),
                          wpbdp_get_page_link('add-listing'));
/*        $html .= sprintf('<a href="%s">%s</a>',
                         wpbdp_get_page_link('add-listing'),
                         __('Submit A Listing', 'WPBDM'));*/
    }

    if (wpbdp_get_option('show-view-listings')) {
        $html .= sprintf('<input id="wpbdp-bar-view-listings-button" type="button" value="%s" onclick="window.location.href = \'%s\'" class="button" />',
                          __('View Listings', 'WPBDM'),
                          wpbdp_get_page_link('view-listings'));        
/*        $html .= sprintf('<a href="%s">%s</a>',
                         wpbdp_get_page_link('view-listings'),
                         __('View Listings', 'WPBDM')
                        );*/
    }

    if (wpbdp_get_option('show-directory-button')) {
        $html .= sprintf('<input id="wpbdp-bar-show-directory-button" type="button" value="%s" onclick="window.location.href = \'%s\'" class="button" />',
                          __('Directory', 'WPBDM'),
                          wpbdp_get_page_link('main'));
/*        $html .= sprintf('<a href="%s">%s</a>',
                         wpbdp_get_page_link('main'),
                         __('Directory', 'WPBDM')
                        );*/
    }

    $html .= '</div>';

    return $html;
}

function wpbdp_the_main_links() {
    echo wpbdp_main_links();
}

function wpbdp_search_form() {
    $html = '';
    $html .= sprintf('<form id="wpbdmsearchform" action="" method="GET" class="wpbdp-search-form">
                      <input type="hidden" name="action" value="search" />
                      <input type="hidden" name="page_id" value="%d" />
                      <input type="hidden" name="dosrch" value="1" />',
                      wpbdp_get_page_id('main'));
    $html .= '<input id="intextbox" maxlength="150" name="q" size="20" type="text" value="" />';
    $html .= sprintf('<input id="wpbdmsearchsubmit" class="submit" type="submit" value="%s" />',
                     _x('Search Listings', 'templates', 'WPBDM'));
    $html .= sprintf('<a href="%s" class="advanced-search-link">%s</a>',
                     add_query_arg('action', 'search', wpbdp_get_page_link('main')),
                     _x('Advanced Search', 'templates', 'WPBDM'));
    $html .= '</form>';

    return $html;
}

function wpbdp_the_search_form() {
    if (wpbdp_get_option('show-search-listings'))
        echo wpbdp_search_form();
}

function wpbdp_the_listing_excerpt() {
    echo wpbdp_render_listing(null, 'excerpt');
}

function wpbdp_listing_sort_options() {
    $sort_options = array();
    $sort_options = apply_filters('wpbdp_listing_sort_options', $sort_options);

    if (!$sort_options)
        return '';

    $current_sort = wpbdp_get_current_sort_option();

    $html  = '';
    $html .= '<div class="wpbdp-listings-sort-options">';
    $html .= _x('Sort By:', 'templates sort', 'WPBDM') . ' ';

    foreach ($sort_options as $id => $option) {
        $html .= sprintf('<span class="%s %s"><a href="%s">%s</a> %s</span>',
                        $id,
                        ($current_sort && $current_sort->option == $id) ? 'current': '',
                        ($current_sort && $current_sort->option == $id) ? add_query_arg('wpbdp_sort', ($current_sort->order == 'ASC' ? '-' : '') . $id) : add_query_arg('wpbdp_sort', $id),
                        $option[0],
                        ($current_sort && $current_sort->option == $id) ? ($current_sort->order == 'ASC' ? '↑' : '↓') : '↑'
                        );
        $html .= ' | ';
    }
    $html = substr($html, 0, -3);
    $html .= '<br />';

    if ($current_sort)
        $html .= sprintf('(<a href="%s" class="reset">Reset</a>)', remove_query_arg('wpbdp_sort'));
    $html .= '</div>';

    return $html;
}

function wpbdp_the_listing_sort_options() {
    echo wpbdp_listing_sort_options();
}

/**
 * @deprecated since 2.2.1
 */
function wpbdp_bar($parts=array()) {
    $parts = wp_parse_args($parts, array(
        'links' => true,
        'search' => false
    ));

    $html  = '<div class="wpbdp-bar cf">';
    $html .= apply_filters('wpbdp_bar_before', '', $parts);

    if ($parts['links'])
        $html .= wpbdp_main_links();
    if ($parts['search'])
        $html .= wpbdp_search_form();

    $html .= apply_filters('wpbdp_bar_after', '', $parts);
    $html .= '</div>';

    return $html;
}

/**
 * @deprecated since 2.2.1
 */
function wpbdp_the_bar($parts=array()) {
    echo wpbdp_bar($parts);
}

/**
 * Displays the listing main image.
 * @since 2.3
 */
function wpbdp_listing_thumbnail( $listing_id=null, $args=array() ) {
    if ( !$listing_id ) $listing_id = get_the_ID();

    $args = wp_parse_args( $args, array(
        'link' => 'picture',
        'class' => '',
        'echo' => false,
    ) );

    $main_image = false;
    $image_img = '';
    $image_link = '';
    $image_classes = 'wpbdp-thumbnail attachment-wpbdp-thumb ' . $args['class'];

    if ( $thumbnail_id = wpbdp_listings_api()->get_thumbnail_id( $listing_id ) ) {
        $main_image = get_post( $thumbnail_id );
    } else {
        $images = wpbdp_listings_api()->get_images( $listing_id );
        
        if ( $images )
            $main_image = $images[0];
    }

    if ( !$main_image && function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( $listing_id ) ) {
        $image_img = get_the_post_thumbnail( $listing_id, 'wpbdp-thumb' );
    } elseif( !$main_image && wpbdp_get_option( 'use-default-picture' ) ) {
        $image_img = sprintf( '<img src="%s" alt="%s" title="%s" border="0" width="%d" class="%s" />',
                              WPBDP_URL . 'resources/images/default-image-big.gif',
                              get_the_title( $listing_id ),
                              get_the_title( $listing_id ),
                              wpbdp_get_option( 'thumbnail-width' ),
                              $image_classes
                            );
        $image_link = $args['link'] == 'picture' ? WPBDP_URL . 'resources/images/default-image-big.gif' : '';
    } elseif ( $main_image ) {
        $image_img = wp_get_attachment_image( $main_image->ID,
                                              'wpbdp-thumb',
                                              false,
                                              array(
                                                'alt' => get_the_title( $listing_id ),
                                                'title' => get_the_title( $listing_id ),
                                                'class' => $image_classes
                                                )
                                             );

        if ( $args['link'] == 'picture' ) {
            $full_image_data = wp_get_attachment_image_src( $main_image->ID, 'wpbdp-large' );
            $image_link = $full_image_data[0];
        }

    }

    if ( !$image_link && $args['link'] == 'listing' )
        $image_link = get_permalink( $listing_id );

    if ( $image_img ) {
        if ( !$image_link ) {
            return $image_img;
        } else {
            return sprintf( '<div class="listing-thumbnail"><a href="%s" class="%s">%s</a></div>',
                            $image_link,
                            $args['link'] == 'picture' ? 'thickbox lightbox fancybox' : '',
                            $image_img );
        }
    }

    return '' ;
}
