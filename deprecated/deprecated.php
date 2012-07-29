<?php
/*
 * Deprecated functionality.
 */

$wpbusdirmanconfigoptionsprefix = "wpbusdirman";

$wpbdmposttype = "wpbdm-directory";
$wpbdmposttypecategory = "wpbdm-category";
$wpbdmposttypetags = "wpbdm-tags";

define('WPBUSDIRMAN_TEMPLATES_PATH', WPBDP_PATH . '/deprecated/templates');


function get_wpbusdirman_config_options() {
	wpbdp_log_deprecated();

	global $wpbdp;
	return $wpbdp->settings->pre_2_0_compat_get_config_options();
}

function wpbusdirman_search_template($search) {
    global $wp_query, $post;

        if(isset($_REQUEST['post_type']) && ( $_REQUEST['post_type'] == wpbdp_post_type() ))
        {
            if(file_exists(get_stylesheet_directory() . '/single/wpbusdirman-search.php'))
            return get_stylesheet_directory() . '/single/wpbusdirman-search.php';
            if(file_exists(get_template_directory() . '/single/wpbusdirman-search.php'))
            return get_template_directory() . '/single/wpbusdirman-search.php';     
            if(file_exists(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-search.php'))
            return WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-search.php';
        }

    return $search;
}
add_filter('search_template', 'wpbusdirman_search_template');


function wpbusdirman_filterinput($input) {
    $input = strip_tags($input);
    $input = trim($input);
    return $input;
}


/* template-related */
function wpbusdirman_single_listing_details() {
    echo wpbusdirman_post_single_listing_details();
}

function wpbusdirman_post_single_listing_details() {
    global $post;
    $wpbusdirman_permalink=get_permalink(wpbdp_get_page_id('main'));
    $html = '';

    if(is_user_logged_in()) {
        global $current_user;
        $html .= get_currentuserinfo();
        $wpbusdirmanloggedinuseremail=$current_user->user_email;
        $wpbusdirmanauthoremail=get_the_author_meta('user_email');
        $wpbdmpostissticky=get_post_meta($post->ID, "_wpbdp[sticky]", $single=true);
        if ($wpbusdirmanloggedinuseremail == $wpbusdirmanauthoremail) {
            $html .= '<div id="editlistingsingleview">' . wpbusdirman_menu_button_editlisting() . wpbusdirman_menu_button_upgradelisting() . '</div><div style="clear:both;"></div>';
        }
    }

    if(isset($wpbdmpostissticky) && !empty($wpbdmpostissticky) && ($wpbdmpostissticky  == 'sticky') ) {
        $html .= '<span class="featuredlisting"><img src="' . WPBDP_URL . 'resources/images/' . '/featuredlisting.png" alt="' . __("Featured Listing","WPBDM") . '" border="0" title="' . the_title(null, null, false) . '"></span>';
    }

    $html .= apply_filters('wpbdp_listing_view_before', '', $post->ID);

    $html .= '<div class="singledetailsview">';

    foreach (wpbdp_get_formfields() as $field) {
        if ($field->association == 'excerpt'):
            $html .= wpbdp_format_field_output($field, $post->post_excerpt);
        else:
            $html .= wpbdp_format_field_output($field, null, $post);
        endif;
    }

    $html .= apply_filters('wpbdp_listing_view_after', '', $post->ID);
    $html .= wpbusdirman_contactform($wpbusdirman_permalink,$post->ID,$commentauthorname='',$commentauthoremail='',$commentauthorwebsite='',$commentauthormessage='',$wpbusdirman_contact_form_errors='');
    $html .= '</div>';

    return $html;
}

function wpbusdirman_the_listing_title() {
    return wpbdp_format_field_output('title', null, get_the_ID());
}

function wpbusdirman_the_listing_excerpt() {
    if (has_excerpt(get_the_ID()))
        return wpbdp_format_field_output('excerpt', null, get_the_ID());
}

function wpbusdirman_the_listing_content() {
    return wpbdp_format_field_output('content', null, get_the_ID());
}

function wpbusdirman_the_listing_category() {
    return wpbdp_format_field_output('category', null, get_the_ID());
}

function wpbusdirman_the_listing_tags() {
    return wpbdp_format_field_output('tags', null, get_the_ID());
}

function wpbusdirman_the_listing_meta($excerptorsingle) {
    global $post;
    $html = '';

    foreach (wpbdp_formfields_api()->getFieldsByAssociation('meta') as $field) {
        if ($excerptorsingle == 'excerpt' && !$field->display_options['show_in_excerpt'])
            continue;

        $html .= wpbdp_format_field_output($field, null, $post);
    }

    return $html;
}

function wpbusdirman_display_excerpt($deprecated=null) {
    echo wpbusdirman_post_excerpt($deprecated);
}

function wpbusdirman_post_excerpt($deprecated=null) {
    static $count = 0;

    $is_sticky = wpbdp_listings_api()->get_sticky_status(get_the_ID()) == 'sticky' ? true : false;

    $html = '';
    $html .= sprintf('<div id="wpbdmlistings" class="wpbdp-listing excerpt %s %s %s">',
                    $is_sticky ? 'sticky' : '',
                    $is_sticky ? (($count & 1) ? 'wpbdmoddsticky' : 'wpbdmevensticky') : '',
                    ($count & 1) ? 'wpbdmodd' : 'wpbdmeven');

    $html .= wpbusdirman_display_the_thumbnail();

    $html .= '<div class="listingdetails">';
    $html .= apply_filters('wpbdp_listing_excerpt_view_before', '', get_the_ID());
    $html .= wpbusdirman_display_the_listing_fields();
    $html .= apply_filters('wpbdp_listing_excerpt_view_after', '', get_the_ID());
    $html .= wpbusdirman_view_edit_delete_listing_button();
    $html .= '</div>';
    $html .= '<div style="clear: both;"></div>';
    $html .= '</div>';

    $count++;

    return $html;
}


function wpbusdirman_display_main_image() {
    echo wpbusdirman_post_main_image();
}

function wpbusdirman_post_main_image() {
    $main_image = null;

    if ($thumbnail_id = wpbdp_listings_api()->get_thumbnail_id(get_the_ID())) {
        $main_image = get_post($thumbnail_id);
    } else {
        $images = wpbdp_listings_api()->get_images(get_the_ID());

        if ($images)
            $main_image = $images[0];
    }

    if (!$main_image && function_exists('has_post_thumbnail') && has_post_thumbnail()) {
        return '<a href="' . get_permalink() . '">' .the_post_thumbnail('medium') . '</a><br/>';
    }

    if (!$main_image && wpbdp_get_option('use-default-picture')) {
        if (wpbdp_get_option('use-default-picture')) {
            return sprintf('<a href="%s"><img src="%s" alt="%s" title="%s" border="0" /></a><br />',
                            get_permalink(),
                            WPBDP_URL . 'resources/images/default-image-big.gif',
                            the_title(null, null, false),
                            the_title(null, null, false)
                          );
        }
    } elseif ($main_image) {
        return wp_get_attachment_image($main_image->ID, 'medium', false, array(
            'alt' => the_title(null, null, false),
            'title' => the_title(null, null, false)
            ));
    }

    return '';
}

function wpbusdirman_display_extra_thumbnails() {
    echo wpbusdirman_post_extra_thumbnails();
}

function wpbusdirman_post_extra_thumbnails() {
    $html = '';

    $thumbnail_id = wpbdp_listings_api()->get_thumbnail_id(get_the_ID());
    $images = wpbdp_listings_api()->get_images(get_the_ID());

    if ($images) {
        $html .= '<div class="extrathumbnails">';

        foreach ($images as $img) {
            if ($img->ID == $thumbnail_id)
                continue;

            $html .= sprintf('<a class="thickbox" href="%s"><img class="wpbdmthumbs" src="%s" alt="%s" title="%s" border="0" /></a>',
                             wp_get_attachment_url($img->ID),
                             wp_get_attachment_thumb_url($img->ID),
                             the_title(null, null, false),
                             the_title(null, null, false)
                             );
        }

        $html .= '</div>';      
    }

    return $html;
}

// Display the listing fields in excerpt view
function wpbusdirman_display_the_listing_fields() {
    global $post;

    $html = '';

    foreach (wpbdp_get_formfields() as $field) {
        if (!$field->display_options['show_in_excerpt'])
            continue;

        $html .= wpbdp_format_field_output($field, null, $post);
    }

    return $html;
}

//Display the listing thumbnail
function wpbusdirman_display_the_thumbnail() {
    global $post;

    if (!wpbdp_get_option('allow-images') || !wpbdp_get_option('show-thumbnail'))
        return '';

    $html = '';
    $thumbnail = null;

    $listings_api = wpbdp_listings_api();
    
    if ($thumbnail_id = $listings_api->get_thumbnail_id($post->ID)) {
        $thumbnail = wp_get_attachment_thumb_url($thumbnail_id);
    }

    if (!$thumbnail && function_exists('has_post_thumbnail') && has_post_thumbnail($post->ID))
        return sprintf('<div class="listing-thumbnail"><a href="%s">%s</a></div>',
                       get_permalink(),
                       get_the_post_thumbnail($post->ID,
                                        array(wpbdp_get_option('thumbnail-width', '120'), wpbdp_get_option('thumbnail-width', '120')),
                                        array('class' => 'wpbdmthumbs',
                                              'alt' => the_title(null, null, false),
                                              'title' => the_title(null, null, false) ))
                      );

    if (!$thumbnail && wpbdp_get_option('use-default-picture'))
        $thumbnail = WPBDP_URL . 'resources/images/default.png';

    if ($thumbnail) {
        $html .= '<div class="listing-thumbnail">';
        $html .= sprintf('<a href="%s"><img class="wpbdmthumbs" src="%s" width="%s" alt="%s" title="%s" border="0" /></a>',
                         get_permalink(),
                         $thumbnail,
                         wpbdp_get_option('thumbnail-width', '120'),
                         the_title(null, null, false),
                         the_title(null, null, false)
                        );
        $html .= '</div>';
    }

    return $html;
}

function wpbusdirman_sticky_loop() {
    $args = array(
        'post_type' => wpbdp_post_type(),
        'posts_per_page' => 0,
        'post_status' => 'publish',
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        'meta_key' => '_wpbdp[sticky]',
        'meta_value' => 'sticky',
        'orderby' => wpbdp_get_option('listings-order-by', 'date'),
        'order' => wpbdp_get_option('listings-sort', 'ASC')
    );

    if (get_query_var('term')) {
        $args['tax_query'] = array(
            array('taxonomy' => get_query_var('taxonomy'),
                  'field' => 'slug',
                  'terms' => get_query_var('term'))
        );
    }

    query_posts($args);

    while (have_posts()) {
        the_post();
        echo wpbdp_the_listing_excerpt();
    }

    wp_reset_query();
}

function wpbusdirman_view_edit_delete_listing_button() {
    $wpbusdirman_permalink=get_permalink(wpbdp_get_page_id('main'));
    $html = '';

    $html .= '<div style="clear:both;"></div><div class="vieweditbuttons"><div class="vieweditbutton"><form method="post" action="' . get_permalink() . '"><input type="hidden" name="action" value="viewlisting" /><input type="hidden" name="wpbusdirmanlistingid" value="' . get_the_id() . '" /><input type="submit" value="' . __("View","WPBDM") . '" /></form></div>';

    if ( (wp_get_current_user()->ID == get_the_author_meta('ID')) || current_user_can('administrator')) {
        $html .= '<div class="vieweditbutton"><form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="editlisting" /><input type="hidden" name="listing_id" value="' . get_the_id() . '" /><input type="submit" value="' . __("Edit","WPBDM") . '" /></form></div><div class="vieweditbutton"><form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="deletelisting" /><input type="hidden" name="listing_id" value="' . get_the_id() . '" /><input type="submit" value="' . __("Delete","WPBDM") . '" /></form></div>';
    }
    $html .= '</div>';

    return $html;
}

function wpbusdirman_menu_button_upgradelisting() {
    $post_id = get_the_ID();

    if ( wpbdp_get_option('featured-on') &&
         (get_post($post_id)->post_author == wp_get_current_user()->ID) &&
         wpbdp_listings_api()->get_sticky_status(get_the_ID()) == 'normal' ) {
            return '<form method="post" action="' . wpbdp_get_page_link('main') . '"><input type="hidden" name="action" value="upgradetostickylisting" /><input type="hidden" name="listing_id" value="' . $post_id . '" /><input type="submit" class="updradetostickylistingbutton" value="' . __("Upgrade Listing","WPBDM") . '" /></form>';
    }

    return '';
}

function wpbusdirman_latest_listings($numlistings) {
    return wpbdp_latest_listings($numlistings);
}

function wpbusdirman_catpage_title() {
    echo wpbusdirman_post_catpage_title();
}

function wpbusdirman_post_catpage_title() {
    global $post;
    $term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
    $html = '';

    $html .=  $term->name;

    return $html;
}

function wpbusdirman_list_categories()
{
    echo wpbusdirman_post_list_categories();
}

function wpbusdirman_post_list_categories() {
    $wpbdm_hide_empty = wpbdp_get_option('hide-empty-categories');
    $wpbdm_show_count= wpbdp_get_option('show-category-post-count');
    $wpbdm_show_parent_categories_only= wpbdp_get_option('show-only-parent-categories');

    $html = '';

    $taxonomy     = wpbdp_categories_taxonomy();
    $orderby      = wpbdp_get_option('categories-order-by');
    $show_count   = $wpbdm_show_count;      // 1 for yes, 0 for no
    $pad_counts   = 0;      // 1 for yes, 0 for no
    $order= wpbdp_get_option('categories-sort');
    $hide_empty=$wpbdm_hide_empty;

    $html .= wp_list_categories(array(
        'taxonomy' => $taxonomy,
        'echo' => false,
        'title_li' => '',
        'orderby' => $orderby,
        'order' => $order,
        'show_count' => $show_count,
        'pad_counts' => true,
        'hide_empty' => $hide_empty,
        'hierarchical' => 1,
        'depth' => $wpbdm_show_parent_categories_only ? 1 : 0
    ));

    return $html;
}

function wpbusdirman_menu_buttons()
{
    echo wpbusdirman_post_menu_buttons();
}

function wpbusdirman_post_menu_buttons()
{
    $html = '';
    $html .= '<div>' . wpbusdirman_post_menu_button_submitlisting() . wpbusdirman_menu_button_directory() . '</div><div style="clear: both;"></div>';
    return $html;
}

function wpbusdirman_menu_button_submitlisting()
{
    echo wpbusdirman_post_menu_button_submitlisting();
}

function wpbusdirman_post_menu_button_submitlisting()
{
    if (!wpbdp_get_option('show-submit-listing'))
        return '';

    return '<form method="post" action="' . wpbdp_get_page_link('add-listing') . '"><input type="hidden" name="action" value="submitlisting" /><input type="submit" class="submitlistingbutton" value="' . __("Submit A Listing","WPBDM") . '" /></form>';
}

function wpbusdirman_menu_button_viewlistings()
{
    echo wpbusdirman_post_menu_button_viewlistings();
}

function wpbusdirman_post_menu_button_viewlistings()
{
    if (!wpbdp_get_option('show-view-listings'))
        return '';
    
    return '<form method="post" action="' . wpbdp_get_page_link('view-listings') . '"><input type="hidden" name="action" value="viewlistings" /><input type="submit" class="viewlistingsbutton" value="' . __("View Listings","WPBDM") . '" /></form>';
}

function wpbusdirman_menu_button_directory()
{

    echo wpbusdirman_post_menu_button_directory();
}

function wpbusdirman_post_menu_button_directory()
{
    return '<form method="post" action="' . wpbdp_get_page_link('main') . '"><input type="submit" class="viewlistingsbutton" value="' . __("Directory","WPBDM") . '" /></form>';
}

function wpbusdirman_menu_button_editlisting()
{
    global $post;
    $wpbusdirman_permalink=get_permalink(wpbdp_get_page_id('main'));
    $html = '';

    if(is_user_logged_in())
    {
        global $current_user;
        get_currentuserinfo();
        $wpbusdirmanloggedinuseremail=$current_user->user_email;
        $wpbusdirmanauthoremail=get_the_author_meta('user_email');
        if($wpbusdirmanloggedinuseremail == $wpbusdirmanauthoremail || current_user_can('administrator') || (wp_get_current_user()->ID == get_the_author_meta('ID')))
        {
            $html .= '<form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="editlisting" /><input type="hidden" name="listing_id" value="' . $post->ID . '" /><input type="submit" class="editlistingbutton" value="' . __("Edit Listing","WPBDM") . '" /></form>';
        }
    }

    return $html;
}

function remove_no_categories_msg($content) {
  if (!empty($content)) {
  if(function_exists('str_ireplace')){
    $content = str_ireplace('<li>' .__( "No categories" ). '</li>', "", $content);
    }
  }
  return $content;
}
add_filter('wp_list_categories','remove_no_categories_msg');