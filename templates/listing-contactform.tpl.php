
<?php if ($validation_errors): ?>
	<ul id="wpbusdirmanerrors">
		<?php foreach($validation_errors as $error_msg): ?>
			<li class="wpbusdirmanerroralert"><?php echo $error_msg; ?></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<h4><?php _ex('Send Message to listing owner', 'templates', 'WPBDM'); ?></h4>
<p><label><?php _ex('Listing Title: ', 'templates', 'WPBDM'); ?></label><?php echo get_the_title($listing_id); ?></p>

<form method="POST" action="<?php echo wpbdp_get_page_link('main'); ?>">
	<input type="hidden" name="action" value="sendcontactmessage" />
	<input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>" />

	<?php if ($current_user): ?>
		<p>
			<?php echo sprintf(_x('You are currently logged in as %s. Your message will be sent using your logged in contact email.', 'templates', 'WPBDM'),
						  	   $current_user->user_login); ?>
		</p>
	<?php else: ?>
			<p>
			   <label style="width:4em;"><?php _ex('Your Name', 'templates', 'WPBDM'); ?></label>
			   <input type="text" class="intextbox" name="commentauthorname" value="<?php echo esc_attr(wpbdp_getv($_POST, 'commentauthorname', '')); ?>" />
			</p>
			<p>
				<label style="width:4em;"><?php _ex("Your Email", 'templates', "WPBDM"); ?></label>
				<input type="text" class="intextbox" name="commentauthoremail" value="<?php esc_attr(wpbdp_getv($_POST, 'commentauthoremail')); ?>" />
			</p>
			<p>
				<label style="width:4em;"><?php _ex("Website url", 'templates', "WPBDM"); ?></label>
				<input type="text" class="intextbox" name="commentauthorwebsite" value="<?php esc_attr(wpbdp_getv($_POST, 'commentauthorwebsite')); ?>" />
			</p>
	<?php endif; ?>

	<?php if ($recaptcha): ?>
		<?php echo $recaptcha; ?>
	<?php endif; ?>

	<p><label style="width:4em;"><?php _ex("Message", 'templates', "WPBDM"); ?></label><br/>
	   <textarea name="commentauthormessage" class="intextarea"><?php esc_textarea(wpbdp_getv($_POST, 'commentauthormessage', '')); ?></textarea>
	</p>

	<input type="submit" class="insubmitbutton" value="<?php _ex('Send', 'templates', 'WPBDM'); ?>" />
</form>