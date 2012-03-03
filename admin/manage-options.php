<?php
function wpbusdirman_retrieveoptions($whichoptions)
{
	$wpbusdirman_field_vals=array();
	global $table_prefix;

	$query="SELECT count(*) FROM {$table_prefix}options WHERE option_name LIKE '%".$whichoptions."%'";
	if (!($res=mysql_query($query)))
	{
		die(__(' Failure retrieving table data ['.$query.'].'));
	}
	while ($rsrow=mysql_fetch_row($res))
	{
		list($wpbusdirman_count_label)=$rsrow;
	}
	for ($i=0;$i<($wpbusdirman_count_label);$i++)
	{
		$wpbusdirman_field_vals[]=($i+1);
	}

	return $wpbusdirman_field_vals;
}


function get_wpbusdirman_config_options()
{
	wpbdp_log_deprecated();

	global $wpbdp;
	return $wpbdp->settings->pre_2_0_compat_get_config_options();
}


?>