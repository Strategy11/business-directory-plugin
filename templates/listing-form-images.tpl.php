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
			<?php echo !$listing_data['listing_id'] ? _x('Submit A Listing', 'templates', 'WPBDM') : _x('Edit Your Listing', 'templates', 'WPBDM'); ?>
		</div>
		<div class="button">
			<?php echo wpbusdirman_post_menu_button_viewlistings(); ?>
			<?php echo wpbusdirman_post_menu_button_directory(); ?>
		</div>
		<div style="clear: both;"></div>
	</div>

	<div class="clear"></div>

	<h2><?php _ex('Step 3 - Listing Images', 'templates', 'WPBDM'); ?></h2>

	<form id="wpbdp-listing-form-images" method="POST" action="" enctype="multipart/form-data">
		<input type="hidden" name="action" value="<?php echo $listing ? 'editlisting' : 'submitlisting'; ?>" />		
		<input type="hidden" name="_step" value="images" />
		<input type="hidden" name="listing_data" value="<?php echo base64_encode(serialize($listing_data)); ?>" />
		<input type="hidden" name="delete_image" value="0" />

		<?php foreach ($images as $image_id): ?>
			<div style="float: left; margin-right: 10px; margin-bottom: 10px; border-bottom: dotted 1px #efefef;">
				<img src="<?php echo wp_get_attachment_thumb_url($image_id); ?>" />
				<input type="submit" onclick="return wpbdp_listingform_delete_image('<?php echo $image_id; ?>');" value="<?php _ex('Delete Image', 'templates', 'WPBDM'); ?>" class="insubmitbutton" style="float: none; margin-left: 5px;" /> <br />

				<label>
					<input type="radio" name="thumbnail_id" value="<?php echo $image_id; ?>" <?php echo (count($images) == 1 || $thumbnail_id == $image_id) ? 'checked="checked"' : ''; ?> />
					<?php _ex('Set this image as the listing thumbnail.', 'templates', 'WPBDM'); ?>
				</label>
			</div>

			<p style="clear: both;"></p>
		<?php endforeach; ?>

		<?php if ($can_upload_images): ?>
			<p><?php echo sprintf(_x("If you would like to include an image with your listing please upload the image of your choice. You are allowed [%s] images and have [%s] image slots still available.", 'templates', 'WPBDM'),		
							   $images_allowed,
							   $images_left); ?></p>
		<p>
			<input type="file" name="image" />
			<input type="submit" name="upload_image" value="<?php _ex('Upload Image', 'templates', 'WPBDM'); ?>" class="insubmitbutton" style="float: none;" />
		</p>

		<p><?php _ex('If you prefer not to add an image click "Finish". Your listing will be submitted.', 'templates', 'WPBDM'); ?></p>
		<?php else: ?>
			<p><?php _ex("Your image slots are all full at this time.  You may click Finish if you are done, or Delete Image to reupload a new image in place of a new one.", 'templates', 'WPBDM'); ?></p>		
		<?php endif; ?>

		<input type="submit" name="submit" value="<?php _ex('Finish', 'templates', 'WPBDM'); ?>" class="insubmitbutton" />

	</form>

</div>