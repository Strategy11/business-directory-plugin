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

function wpbusdirman_addcss()
{
    $wpbusdirmanstylesheet="wpbusdirman.css";
    if(file_exists(get_stylesheet_directory() .'/css/'.$wpbusdirmanstylesheet))
    {
        $myWPBDMStyleUrl = get_stylesheet_directory_uri() . '/css/' .$wpbusdirmanstylesheet;
    }
    elseif(file_exists(get_template_directory() .'/css/'.$wpbusdirmanstylesheet))
    {
        $myWPBDMStyleUrl = get_template_directory_uri() . '/css/' .$wpbusdirmanstylesheet;
    }    
    elseif(file_exists(WPBDP_PATH .'resources/css/'.$wpbusdirmanstylesheet))
    {
        $myWPBDMStyleUrl = WPBDP_URL . 'resources/css/' .$wpbusdirmanstylesheet;
    }
    if (0 < strlen('myWPBDMStyleFile'))
    {
        wp_register_style('myWPBDMStyleSheets', $myWPBDMStyleUrl);
        wp_enqueue_style( 'myWPBDMStyleSheets');
    }
}
add_action('wp_print_styles', 'wpbusdirman_addcss');


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