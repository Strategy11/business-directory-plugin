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

	<?php if ($validation_errors): ?>
		<ul id="wpbusdirmanerrors">
			<?php foreach ($validation_errors as $error_msg): ?>
				<li class="wpbusdirmanerroralert"><?php echo $error_msg; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<form method="POST" action="" enctype="application/x-www-form-urlencoded">
		<input type="hidden" name="action" value="<?php echo $listing_id ? 'editlisting' : 'submitlisting'; ?>" />
		<input type="hidden" name="_step" value="fields" />
		<input type="hidden" name="listing_id" value="<?php echo $listing_id ? $listing_id : 0; ?>" />

		<?php foreach ($fields as $field): ?>
			<?php echo $field['html']; ?>
		<?php endforeach; ?>

		<p><input type="submit" class="insubmitbutton" value="<?php _ex('Submit', 'templates', 'WPBDM'); ?>" /></p>
	</form>

</div>