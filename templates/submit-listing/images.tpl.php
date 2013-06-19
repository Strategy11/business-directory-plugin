<script type="text/javascript">
function wpbdp_listingform_delete_image(id) {
	var form = document.getElementById('wpbdp-listing-form-images');
	form['delete-image-id'].value = id;
	return true;
}
</script>

<h3>
	<?php _ex( '4 - Listing Images', 'templates', 'WPBDM' ); ?>
</h3>

<form id="wpbdp-listing-form-images" class="wpbdp-listing-form" method="POST" action="" enctype="multipart/form-data">
	<input type="hidden" name="_state" value="<?php echo $_state; ?>" />
	<input type="hidden" name="delete-image-id" value="0" />

	<dl class="image-conditions">
		<dt><?php _ex( 'Image slots available:', 'templates', 'WPBDM' ); ?></dt>
		<dd>
			<?php printf( '%d / %d', $state->allowed_images - count( $state->images ), $state->allowed_images ); ?>
		</dd>

		<dt><?php _ex( 'Max. file size:', 'templates', 'WPBDM' ); ?></dt>
		<dd>
			<?php echo size_format( intval( wpbdp_get_option( 'image-max-filesize' ) ) * 1024 ); ?>
		</dd>
	</dl>

	<?php if ( $state->images ): ?>
	<h4><?php _ex( 'Current Images', 'templates', 'WPBDM' ); ?></h4>
		<?php foreach ($state->images as $image_id): ?>
		<div class="image">
			<img src="<?php echo wp_get_attachment_thumb_url($image_id); ?>" /><br />
			<input type="submit" class="submit" name="delete-image" onclick="return wpbdp_listingform_delete_image('<?php echo $image_id; ?>');" class="delete-image" value="<?php _ex('Delete Image', 'templates', 'WPBDM'); ?>" /> <br />

			<label>
				<input type="radio" name="thumbnail_id" value="<?php echo $image_id; ?>" <?php echo (count($state->images) == 1 || $thumbnail_id == $image_id) ? 'checked="checked"' : ''; ?> />
				<?php _ex('Set this image as the listing thumbnail.', 'templates', 'WPBDM'); ?>
			</label>
		</div>
	<?php endforeach; ?>
	<?php endif; ?>

	<?php if ( ( $state->allowed_images - count( $state->images ) ) > 0 ): ?>
	<h4><?php _ex( 'Upload Image', 'templates', 'WPBDM' ); ?></h4>
	<div class="upload-form">
		<input type="file" name="image" />
		<input type="submit" class="submit" name="upload-image" value="<?php _ex('Upload Image', 'templates', 'WPBDM'); ?>" />
	</div>
	<?php else: ?>
	<p style="clear: both;"><?php _ex( 'Your image slots are all full at this time.  You may click "Continue" if you are done, or "Delete Image" to upload a new image in place of a current one.', 'templates', 'WPBDM' ); ?></p>
	<?php endif; ?>

	<input type="submit" class="submit" name="finish" value="<?php _ex( 'Continue', 'templates', 'WPBDM' ); ?>" />		


</form>