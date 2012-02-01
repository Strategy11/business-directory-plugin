<?php
function wpbusdirman_feepay_configure($post_category_item)
{

	global $wpbusdirmanconfigoptionsprefix,$wpbdmposttypecategory;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_get_currency_symbol=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_12'];

	if(!isset($wpbusdirman_get_currency_symbol)
		|| empty($wpbusdirman_get_currency_symbol))
	{
		$wpbusdirman_get_currency_symbol="$";
	}



	$wpbusdirman_settings_fees_ops=wpbusdirman_retrieveoptions($whichoptionvalue='wpbusdirman_settings_fees_label_');
	$wpbusdirman_fee_to_pay_li = '';
	$html = '';

	global $wpbusdirman_hastwocheckoutmodule,$wpbusdirman_haspaypalmodule,$wpbusdirman_hasgooglecheckoutmodule;
	if( $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_40'] == "yes"
		&& $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_41'] == "yes"
			&& $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_43'] == "yes"
				&& $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "yes" )
						{
						$wpbusdirman_fee_to_pay_li='';
						return $wpbusdirman_fee_to_pay_li;
						}

						else
						{


	if($wpbusdirman_settings_fees_ops)
	{

		foreach($wpbusdirman_settings_fees_ops as $wpbusdirman_settings_fees_op)
		{
			// Retrieve the categories that are saved under this fee,check if the posted category is one and if so add $wpbusdirman_settings_fees_op to array with category and fee objects

			// Retrieve the categories
			$wpbusdirman_categories_under=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_categories_'.$wpbusdirman_settings_fees_op);


				$temp = explode(',',$wpbusdirman_categories_under);
				foreach ($temp as $categ_id)
				{

					$wpbusdirman_savedcatid=trim($categ_id);

					$wpbusdirman_get_fee=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_amount_'.$wpbusdirman_settings_fees_op);
					$wpbusdirman_get_fee_arr[]=$wpbusdirman_get_fee;

					$wpbusdirman_fee_op_name=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_label_'.$wpbusdirman_settings_fees_op);
					$wpbusdirman_fee_op_images=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_images_'.$wpbusdirman_settings_fees_op);
					$wpbusdirman_fee_op_increment=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_increment_'.$wpbusdirman_settings_fees_op);

					$wpbusdirman_settings_fees_and_cats[]=array('category'=> $wpbusdirman_savedcatid,'feeop'=> $wpbusdirman_settings_fees_op,'feeamount' => $wpbusdirman_get_fee, 'feelabelname' => $wpbusdirman_fee_op_name,'feeimages' => $wpbusdirman_fee_op_images,'feeincrement' =>$wpbusdirman_fee_op_increment  );

				} // End foreach

		} // End foreach



				foreach( $post_category_item as $mypostcategory )
				{

					$term = get_term($mypostcategory,$wpbdmposttypecategory,'','');
					$thecategoryname=$term->name;

					$wpbusdirman_fee_to_pay_li.='<h4 class="feecategoriesheader">';
					$wpbusdirman_fee_to_pay_li.=$thecategoryname;
					$wpbusdirman_fee_to_pay_li.=__(" fee options", "WPBDM");
					$wpbusdirman_fee_to_pay_li.="</h4>";

					foreach($wpbusdirman_settings_fees_and_cats as $wpbusdirmansettingsfeeandcat)
					{
						$catid=$wpbusdirmansettingsfeeandcat['category'];
						if($catid == 0){$catid=$mypostcategory;}

						$feeid=$wpbusdirmansettingsfeeandcat['feeop'];
						$feeamt=$wpbusdirmansettingsfeeandcat['feeamount'];
						$feelname=$wpbusdirmansettingsfeeandcat['feelabelname'];
						$feeimages=$wpbusdirmansettingsfeeandcat['feeimages'];
						$feeduration=$wpbusdirmansettingsfeeandcat['feeincrement'];


						if( $mypostcategory == $catid )
						{

							$checked='';
							$myfeamt='';

							$wpbusdirman_fee_to_pay_li.="<p><input type=\"radio\" name=\"whichfeeoption_$catid\" value=\"$feeid\" checked />$feelname $wpbusdirman_get_currency_symbol$feeamt (";
								$wpbusdirman_fee_to_pay_li.=__(" Listing will run for ","WPBDM");
								$wpbusdirman_fee_to_pay_li.= $feeduration;
								$wpbusdirman_fee_to_pay_li.=__(" days","WPBDM");

								if( ($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_6'] == "yes") && ($feeimages > 0))
								{
									$wpbusdirman_fee_to_pay_li.=__(" - listing includes ","WPBDM");
									$wpbusdirman_fee_to_pay_li.= $feeimages;
									$wpbusdirman_fee_to_pay_li.=__(" images","WPBDM");
								}

								$wpbusdirman_fee_to_pay_li.=")</p>";
						}

					} // End foreach

				} // End foreach


		}

	}


	return $wpbusdirman_fee_to_pay_li;
}
?>