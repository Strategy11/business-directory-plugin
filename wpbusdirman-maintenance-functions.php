<?php

function wpbusdirman_search_template($search) {
	global $wp_query, $post, $wpbdmposttype;

		if(isset($_REQUEST['post_type']) && ( $_REQUEST['post_type'] == $wpbdmposttype ))
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