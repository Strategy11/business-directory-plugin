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

	<h2 style="padding: 10px;"><?php _ex('Submission received', 'templates', 'WPBDM'); ?></h2>
	<p><?php _ex('Your submission has been received.', 'templates', 'WPBDM'); ?></p>

</div>