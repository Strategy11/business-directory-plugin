<?php
function _wpbdp_is_fee_selected($category, $feeid) {
	$fees = isset($_POST['fees']) ? $_POST['fees'] : array();
	return wpbdp_getv($fees, $category) == $feeid;
}
?>

<div id="wpbdmentry">

	<div id="lco">
		<div class="title">
			<?php echo !$listing ? _x('Submit A Listing', 'templates', 'WPBDM') : _x('Edit Your Listing', 'templates', 'WPBDM'); ?>
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

	<form id="wpbdp-listing-form-images" method="POST" action="">
		<input type="hidden" name="_step" value="payment" />
		<input type="hidden" name="listing_data" value="<?php echo base64_encode(serialize($listing_data)); ?>" />

		<?php foreach ($fee_options as $fee_option): ?>
			<h4 class="feecategoriesheader"><?php echo sprintf(_x('"%s" fee options', 'templates', 'WPBDM'), $fee_option['category']->name); ?></h4>
			<?php foreach ($fee_option['fees'] as $fee): ?>
					<p>
						<input type="radio" name="fees[<?php echo $fee_option['category']->term_id; ?>]" value="<?php echo $fee->id; ?>"
							<?php echo _wpbdp_is_fee_selected($fee_option['category']->term_id, $fee->id) ? 'checked="checked"' : ''; ?>>
							<b><?php echo esc_attr($fee->label); ?> <?php echo wpbdp_get_option('currency-symbol'); ?><?php echo $fee->amount; ?></b><br />
							<?php if (wpbdp_get_option('allow-images') && ($fee->images > 0)) :?>
								<?php echo sprintf(_x('Listing will run for %d days and includes %d images.', 'templates', 'WPBDM'), $fee->days, $fee->images); ?>
							<?php else: ?>
								<?php echo sprintf(_x('Listing will run for %d days.', 'templates', 'WPBDM'), $fee->days); ?>
							<?php endif; ?>
					</p>
			<?php endforeach; ?>
		<?php endforeach; ?>

		<input type="submit" name="submit" value="<?php _ex('Next', 'templates', 'WPBDM'); ?>" />

	</form>

</div>