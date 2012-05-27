<script type="text/javascript">
function wpbdp_listingform_delete_image(id) {
	var form = document.getElementById('wpbdp-listing-form-images');
	form.delete_image.value = id;
	return true;
}
</script>

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

	<form id="wpbdp-listing-form-images" method="POST" action="" enctype="multipart/form-data">
		<input type="hidden" name="_step" value="images" />
		<input type="hidden" name="listing_id" value="<?php echo $listing ? $listing->ID : 0; ?>" />
		<input type="hidden" name="listing_data" value="<?php echo base64_encode(serialize($listing_data)); ?>" />
		<input type="hidden" name="delete_image" value="0" />

		<?php foreach ($images as $image_id): ?>
			<div style="float: left; margin-right: 10px; margin-bottom: 10px;">
				<input type="submit" onclick="return wpbdp_listingform_delete_image('<?php echo $image_id; ?>');" value="delete image" />
				<img src="<?php echo wp_get_attachment_thumb_url($image_id); ?>" /><br />

				<label>
					<input type="radio" name="thumbnail_id" value="<?php echo $image_id; ?>" <?php echo $thumbnail_id == $image_id ? 'checked="checked"' : ''; ?> />
					<?php _ex('Use this image as thumbnail.', 'templates', 'WPBDM'); ?>
				</label>
			</div>

			<p style="clear: both;"></p>
		<?php endforeach; ?>

		<?php if ($can_upload_images): ?>
			<p><?php echo sprintf(_x("If you would like to include an image with your listing please upload the image of your choice. You are allowed [%s] images and have [%s] image slots still available.", 'templates', 'WPBDM'),		
							   $images_allowed,
							   $images_left); ?></p>
		upload image<br />
		<input type="file" name="image" />
		<input type="submit" name="upload_image" value="Upload" />

		<p><?php _ex('If you prefer not to add an image click exit now. Your listing will be submitted.', 'templates', 'WPBDM'); ?></p>
		<?php else: ?>
			<p><?php _ex("It appears you do not have the ability to upload additional images at this time.", 'templates', 'WPBDM'); ?></p>		
		<?php endif; ?>

		<input type="submit" name="submit" value="<?php _ex('Exit Now', 'templates', 'WPBDM'); ?>" />

	</form>

</div>