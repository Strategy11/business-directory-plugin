<?php
/*
 * UI Functions to be called from templates.
 */

function wpbdp_directory_categories() {
    $html  = '';
    $html .= '<ul class="wpbdp-categories">';
    $html .= wpbusdirman_post_list_categories();
    $html .= '</ul>';

    return $html;
}

function wpbdp_the_directory_categories() {
    echo wpbdp_directory_categories();
}

function wpbdp_main_links() {
    $html  = '';
    $html .= '<div class="wpbdp-main-links">';

    if (wpbdp_get_option('show-submit-listing')) {
        $html .= sprintf('<input type="button" value="%s" onclick="window.location.href = \'%s\'" />',
                          __('Submit A Listing', 'WPBDM'),
                          wpbdp_get_page_link('add-listing'));
/*        $html .= sprintf('<a href="%s">%s</a>',
                         wpbdp_get_page_link('add-listing'),
                         __('Submit A Listing', 'WPBDM'));*/
    }

    if (wpbdp_get_option('show-view-listings')) {
        $html .= sprintf('<input type="button" value="%s" onclick="window.location.href = \'%s\'" />',
                          __('View Listings', 'WPBDM'),
                          wpbdp_get_page_link('view-listings'));        
/*        $html .= sprintf('<a href="%s">%s</a>',
                         wpbdp_get_page_link('view-listings'),
                         __('View Listings', 'WPBDM')
                        );*/
    }

    if (_wpbdp_current_action() != 'main') {
        $html .= sprintf('<input type="button" value="%s" onclick="window.location.href = \'%s\'" />',
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
    $html .= sprintf('<form id="wpbdmsearchform" action="%s" method="POST" class="wpbdp-search-form">',
                     add_query_arg('action', 'search', wpbdp_get_page_link('main')));
    $html .= '<input id="intextbox" maxlength="150" name="q" size="20" type="text" value="" />';
    $html .= sprintf('<input id="wpbdmsearchsubmit" class="wpbdmsearchbutton" type="submit" value="%s" />',
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