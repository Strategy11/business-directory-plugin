<?php
function _wpbdp_is_fee_selected($category, $feeid) {
	$fees = isset($_POST['fees']) ? $_POST['fees'] : array();
	return wpbdp_getv($fees, $category) == $feeid;
}
?>

<?php
// TODO
					// $html .= __("You have selected more than one category. Each category you to which you elect to submit your listing incurs a separate fee.", "WPBDM");
					// if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_6'] == "yes")
					// {
					// 	$html .=__(" The number of images attached to your listing will be set according to option you choose that has the most images. So if for one category you chose an option with 2 images but for another category you chose an option with 4 images your listing will be allotted 4 image slots", "WPBDM");
?>

<div id="wpbdmentry">

	<div id="lco">
		<div class="title">
			<?php echo !$listing_id ? _x('Submit A Listing', 'templates', 'WPBDM') : _x('Edit Your Listing', 'templates', 'WPBDM'); ?>
		</div>
		<div class="button">
			<?php echo wpbusdirman_post_menu_button_viewlistings(); ?>
			<?php echo wpbusdirman_post_menu_button_directory(); ?>
		</div>
		<div style="clear: both;"></div>
	</div>

	<div class="clear"></div>

	<h2><?php _ex('Step 2 - Payment Options', 'templates', 'WPBDM'); ?></h2>

	<?php if ($validation_errors): ?>
		<ul id="wpbusdirmanerrors">
			<?php foreach ($validation_errors as $error_msg): ?>
				<li class="wpbusdirmanerroralert"><?php echo $error_msg; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>	

	<form id="wpbdp-listing-form-fees" method="POST" action="">
		<input type="hidden" name="action" value="<?php echo $listing_id ? 'editlisting' : 'submitlisting'; ?>" />
		<input type="hidden" name="_step" value="payment" />
		<input type="hidden" name="listing_data" value="<?php echo base64_encode(serialize($listing_data)); ?>" />

		<?php foreach ($fee_options as $fee_option): ?>
			<h4 class="feecategoriesheader"><?php echo sprintf(_x('"%s" fee options', 'templates', 'WPBDM'), $fee_option['category']->name); ?></h4>
			<?php foreach ($fee_option['fees'] as $fee): ?>
					<p>
						<input type="radio" name="fees[<?php echo $fee_option['category']->term_id; ?>]" value="<?php echo $fee->id; ?>"
							<?php echo _wpbdp_is_fee_selected($fee_option['category']->term_id, $fee->id) ? 'checked="checked"' : ''; ?>>
							<b><?php echo esc_attr($fee->label); ?> <?php echo wpbdp_get_option('currency-symbol'); ?><?php echo $fee->amount; ?></b><br />
							<?php if (wpbdp_get_option('allow-images') && ($fee->images > 0)): ?>
								<?php if ($fee->days == 0): ?>
									<?php echo sprintf(_nx('Listing will run forever and includes %d image.', 'Listing will run forever and includes %d images.', $fee->images, 'templates', 'WPBDM'), $fee->images); ?>
								<?php else: ?>
									<?php echo sprintf(_nx('Listing will run for %d day', 'Listing will run for %d days', $fee->days, 'templates', 'WPBDM'), $fee->days) . ' '; ?>
									<?php echo sprintf(_nx('and includes %d image.', 'and includes %d images.', $fee->images, 'templates', 'WPBDM'), $fee->images); ?>
								<?php endif; ?>
							<?php else: ?>
								<?php if ($fee->days == 0): ?>
									<?php _ex('Listing will run forever.', 'templates', 'WPBDM'); ?>
								<?php else: ?>
									<?php echo sprintf(_nx('Listing will run for %d day.', 'Listing will run for %d days.', $fee->days, 'templates', 'WPBDM'), $fee->days); ?>
								<?php endif; ?>
							<?php endif; ?>
					</p>
			<?php endforeach; ?>
		<?php endforeach; ?>

		<input type="submit" name="submit" class="insubmitbutton" value="<?php _ex('Continue', 'templates', 'WPBDM'); ?>" />

	</form>

</div>