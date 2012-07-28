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


// TODO
function wpbdp_main_links() {
    return '[action links]';
}

function wpbdp_the_main_links() {
    echo wpbdp_main_links();
}

// TODO
function wpbdp_the_search_form() {
    echo '[search form]';
}

function wpbdp_the_listing_excerpt() {
    echo wpbdp_render_listing(null, 'excerpt');
}