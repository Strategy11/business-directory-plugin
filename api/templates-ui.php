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

    if (wpbdp_get_option('show-directory-button')) {
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
    $html .= sprintf('<form id="wpbdmsearchform" action="" method="GET" class="wpbdp-search-form">
                      <input type="hidden" name="action" value="search" />
                      <input type="hidden" name="page_id" value="%d" />
                      <input type="hidden" name="dosrch" value="1" />',
                      wpbdp_get_page_id('main'));
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