<?php

function wpbusdirman_exclude_payment_pages($output = '')
	{

		$wpbdmpaymentpages=array();
		global $wpdb,$table_prefix;

		$query="SELECT ID FROM {$table_prefix}posts WHERE post_content LIKE '%WPBUSDIRMANGOOGLECHECKOUT%' OR post_content LIKE '%WPBUSDIRMANPAYPAL%' OR post_content LIKE '%WPBUSDIRMANTWOCHECKOUT%'";
		 if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}

		 	while ($rsrow=mysql_fetch_row($res))
 			{
 				$wpbdmpaymentpages[]=$rsrow[0];
 			}

		if($wpbdmpaymentpages)
		{
			foreach ($wpbdmpaymentpages as $wpbdmpaymentpagestoexclude)
			{
				array_push($output, $wpbdmpaymentpagestoexclude);
			}
		}

		return $output;


	}

function wpbusdirman_single_template($single)
{
	global $wp_query, $post, $wpbdmposttype;
	$mywpbdmposttype=$post->post_type;

	if ($mywpbdmposttype == $wpbdmposttype) {
		if(file_exists(get_template_directory() . '/single/wpbusdirman-single.php'))
		return get_template_directory() . '/single/wpbusdirman-single.php';
		if(file_exists(get_stylesheet_directory() . '/single/wpbusdirman-single.php'))
		return get_stylesheet_directory() . '/single/wpbusdirman-single.php';
		if(file_exists(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-single.php'))
		return WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-single.php';
	}

	return $single;

}

function wpbusdirman_search_template($search) {
	global $wp_query, $post, $wpbdmposttype;

		if(isset($_REQUEST['post_type']) && ( $_REQUEST['post_type'] == $wpbdmposttype ))
		{
			if(file_exists(get_template_directory() . '/single/wpbusdirman-search.php'))
			return get_template_directory() . '/single/wpbusdirman-search.php';
			if(file_exists(get_stylesheet_directory() . '/single/wpbusdirman-search.php'))
			return get_stylesheet_directory() . '/single/wpbusdirman-search.php';
			if(file_exists(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-search.php'))
			return WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-search.php';
		}

	return $search;
}

function wpbusdirman_category_template($category)
{
	if (get_query_var(WPBDP_Plugin::POST_TYPE_CATEGORY) && taxonomy_exists(WPBDP_Plugin::POST_TYPE_CATEGORY)) {
				if(file_exists(get_template_directory() . '/single/wpbusdirman-category.php'))
				return get_template_directory() . '/single/wpbusdirman-category.php';
				if(file_exists(get_stylesheet_directory() . '/single/wpbusdirman-category.php'))
				return get_stylesheet_directory() . '/single/wpbusdirman-category.php';
				if(file_exists(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-category.php'))
				return WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-category.php';
	}

	return $category;
}

function wpbusdirman_template_the_title($title)
{

	global $wp_query, $post, $wpbdmposttype,$wpbdmposttypecategory;
	$mywpbdmposttype=$post->post_type;


		    global $id, $post;
    if ( $id && $post && ($mywpbdmposttype == $wpbdmposttype ))
		{
			if(is_single() || taxonomy_exists($wpbdmposttypecategory)){$title='';

			}
		}

return $title;
}

function wpbusdirman_remove_post_dates_author_etc() {
	global $wp_query, $post, $wpbdmposttype,$wpbusdirmanconfigoptionsprefix;
	$mywpbdmposttype=$post->post_type;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();

    if ( $mywpbdmposttype == $wpbdmposttype )
		{
			if(is_single())
			{

	add_filter('the_date', '__return_false');
	add_filter('the_time', '__return_false');
	add_filter('the_modified_date', '__return_false');
	add_filter('get_the_date', '__return_false');
	add_filter('get_the_time', '__return_false');
	add_filter('get_the_modified_date', '__return_false');
	add_filter('get_the_author', '__return_false');
	add_filter('the_author', '__return_false');

		}
	}

}


function wpbusdirman_addcss()
{
    $wpbusdirmanstylesheet="wpbusdirman.css";
    if(file_exists(get_template_directory() .'/css/'.$wpbusdirmanstylesheet))
    {
		$myWPBDMStyleUrl = get_template_directory_uri() . '/css/' .$wpbusdirmanstylesheet;
    }
    elseif(file_exists(get_stylesheet_directory() .'/css/'.$wpbusdirmanstylesheet))
    {
		$myWPBDMStyleUrl = get_stylesheet_directory_uri() . '/css/' .$wpbusdirmanstylesheet;
    }
    elseif(file_exists(WPBUSDIRMANPATH .'css/'.$wpbusdirmanstylesheet))
    {
		$myWPBDMStyleUrl = WPBUSDIRMANURL . 'css/' .$wpbusdirmanstylesheet;
    }
    if (0 < strlen('myWPBDMStyleFile'))
    {
		wp_register_style('myWPBDMStyleSheets', $myWPBDMStyleUrl);
		wp_enqueue_style( 'myWPBDMStyleSheets');
    }
}


function wpbusdirman_change_taxonomy_type_category($taxonomy)
{
	global $wpdb,$table_prefix,$wpbdmposttypecategory;
	$wpbusdirman_query="UPDATE $wpdb->term_taxonomy SET taxonomy='".$wpbdmposttypecategory."', parent='0',count='0' WHERE term_id='$taxonomy'";
	@mysql_query($wpbusdirman_query);
}

function wpbusdirman_change_taxonomy_type_tags($taxonomy)
{
	global $wpdb,$table_prefix,$wpbdmposttypetags;
	$wpbusdirman_query="UPDATE $wpdb->term_taxonomy SET taxonomy='".$wpbdmposttypetags."',count='0' WHERE term_id='$taxonomy'";
	@mysql_query($wpbusdirman_query);
}

function wpbusdirman_update_taxonomy_type_category($taxonomynm)
{
	global $wpdb,$table_prefix,$wpbdmposttypecategory;
	$wpbusdirman_query="UPDATE $wpdb->term_taxonomy SET taxonomy='".$wpbdmposttypecategory."' WHERE taxonomy='$taxonomynm'";
	@mysql_query($wpbusdirman_query);
}

function wpbusdirman_update_taxonomy_type_tags($taxonomynm)
{
	global $wpdb,$table_prefix,$wpbdmposttypetags;
	$wpbusdirman_query="UPDATE $wpdb->term_taxonomy SET taxonomy='".$wpbdmposttypetags."' WHERE taxonomy='$taxonomynm'";
	@mysql_query($wpbusdirman_query);
}

function wpbusdirman_adexpirations_hook(){}


		function wpbusdirman_generatePassword($length=6,$level=2)
		{

		   list($usec, $sec) = explode(' ', microtime());
		   srand((float) $sec + ((float) $usec * 100000));

		   $validchars[1] = "0123456789abcdfghjkmnpqrstvwxyz";
		   $validchars[2] = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		   $validchars[3] = "0123456789_!@#$%&*()-=+/abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_!@#$%&*()-=+/";

		   $password  = "";
		   $counter   = 0;

		   while ($counter < $length)
		   {
			 $actChar = substr($validchars[$level], rand(0, strlen($validchars[$level])-1), 1);

			 // All character must be different
			 if (!strstr($password, $actChar))
			 {
				$password .= $actChar;
				$counter++;
			 }
		   }

		   return $password;

		}

function wpbusdirman_filterinput($input) {
	$input = strip_tags($input);
	$input = trim($input);
	return $input;
}

?>