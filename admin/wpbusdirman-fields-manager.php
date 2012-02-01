<?php

function wpbusdirman_fields_list()
{
	global $wpbusdirmanconfigoptionsprefix,$wpbusdirman_hide_formlist, $wpbusdirman_labeltext, $wpbusdirman_typetext,$wpbusdirman_optionstext,$wpbusdirman_ordertext,$wpbusdirman_actiontext,$wpbusdirman_associationtext,$wpbusdirman_validationtext,$wpbusdirman_requiredtext,$wpbusdirman_showinexcerpttext;
	$html = '';

	if(!$wpbusdirman_hide_formlist)
	{
		$html .= "<h3 style=\"padding:10px;\">" . __("Manage Form Fields","WPBDM") . "</h3><p>" . __("Make changes to your existing form fields.","WPBDM") . "<p><a href=\"?page=wpbdman_c3&action=addnewfield\">" . __("Add New Form Field","WPBDM") . "</a> | <a href=\"?page=wpbdman_c3&action=viewpostform\">" . __("Preview Form","WPBDM") . "</a> | <a href=\"?page=wpbdman_c3&action=addnewlisting\">" . __("Add New Listing","WPBDM") . "</a></p>";
		$wpbusdirman_field_vals=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_label_');
		$html .= "<table class=\"widefat\" cellspacing=\"0\"><thead><tr>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_labeltext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_typetext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_associationtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_validationtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_optionstext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_requiredtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_showinexcerpttext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_actiontext . "</th>";
		$html .= "</tr></thead><tfoot><tr>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_labeltext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_typetext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_associationtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_validationtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_optionstext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_requiredtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_showinexcerpttext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_actiontext . "</th>";
		$html .= "</tr></tfoot><tbody>";
		if($wpbusdirman_field_vals)
		{
			foreach($wpbusdirman_field_vals as $wpbusdirman_field_val)
			{
				$wpbdm_thefieldlabel=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
				$wpbdm_thefieldassociation=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
				$wpbdm_thefieldrequired=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_required_'.$wpbusdirman_field_val);
				$wpbdm_thefieldshowinexcerpt=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_showinexcerpt_'.$wpbusdirman_field_val);
				/* New option added by Mike Bronner */ $wpbdm_thefieldhide = get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_hide_'.$wpbusdirman_field_val);
				$wpbdm_thefieldtype=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_type_'.$wpbusdirman_field_val);


				$html .= "<tr><td>".get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val)."</td><td>";
				$wpbusdirman_optypeval=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_type_'.$wpbusdirman_field_val);
				switch ($wpbusdirman_optypeval)
				{
					case 1:
						$wpbusdirman_optype_descr="Text Box";
						break;
					case 2:
						$wpbusdirman_optype_descr="Select List";
						break;
					case 3:
						$wpbusdirman_optype_descr="Textarea";
						break;
					case 4:
						$wpbusdirman_optype_descr="Radio Button";
						break;
					case 5:
						$wpbusdirman_optype_descr="Multi-Select List";
						break;
					case 6:
						$wpbusdirman_optype_descr="Checkbox";
						break;
				}
				$html .= $wpbusdirman_optype_descr . "</td>";
				$html .= "<td>".get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val)."</td>";
				$html .= "<td>".get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_validation_'.$wpbusdirman_field_val)."</td>";
				$html .= "<td>";
				$wpbusdirman_field_options=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_options_'.$wpbusdirman_field_val);
				$wpbusdirman_field_options_array=explode(",",$wpbusdirman_field_options);
				for ($i=0;isset($wpbusdirman_field_options_array[$i]);++$i)
				{
					$wpbusdirman_field_options_arritems[$i]=trim($wpbusdirman_field_options_array[$i]);
					$html .= "<ul><li>" . $wpbusdirman_field_options_array[$i] . "</li></ul>";
				}
				$html .= "</td><td>" . get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_required_'.$wpbusdirman_field_val) . "</td>";
				$html .= "<td>".get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_showinexcerpt_'.$wpbusdirman_field_val)."</td>";
				$html .= "<td><a href=\"?page=wpbdman_c3&action=editfield&id=$wpbusdirman_field_val\">" . __("Edit","WPBDM") . "</a> | <a href=\"?page=wpbdman_c3&action=deletefield&id=$wpbusdirman_field_val\">" . __("Delete","WPBDM") . "</a></td></tr>";
			}
		}
		$html .= "</tbody></table>";
	}

	return $html;
}
?>