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

function wpbdp_the_bar($parts=array()) {
    echo wpbdp_bar($parts);
}

/* Social sites support. */
function _wpbdp_display_linkedin_button($value) {
    static $js_loaded = false;

    $html  = '';

    if ($value) {
        if (!$js_loaded) {
            $html .= '<script src="//platform.linkedin.com/in.js" type="text/javascript"></script>';
            $js_loaded = true;
        }

        $html .= '<script type="IN/FollowCompany" data-id="1035" data-counter="none"></script>';
    }

    return $html;
}

function _wpbdp_display_facebook_button($page) {

    $html  = '';

    $html .= '<div class="social-field facebook">';

    $html .= '<div id="fb-root"></div>';
    $html .= '<script>(function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
        fjs.parentNode.insertBefore(js, fjs);
      }(document, \'script\', \'facebook-jssdk\'));</script>';

    // data-layout can be 'box_count', 'standard' or 'button_count'
    // ref: https://developers.facebook.com/docs/reference/plugins/like/
    $html .= sprintf('<div class="fb-like" data-href="%s" data-send="false" data-width="200" data-layout="button_count" data-show-faces="false"></div>', $page);
    $html .= '</div>';

    return $html;
}

function _wpbdp_display_twitter_button($handle, $settings=array()) {
    $handle = ltrim($handle, ' @');
    
    $html  = '';

    $html .= '<div class="social-field twitter">';
    $html .= sprintf('<a href="https://twitter.com/%s" class="twitter-follow-button" data-show-count="false" data-lang="%s">Follow @%s</a>',
                     $handle, wpbdp_getv($settings, 'lang', 'en'), $handle);
    $html .= '<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>';
    $html .= '</div>';

    return $html;
}
