
<?php if (!$current_user): ?>
	<p><?php _ex("You are not currently logged in. Please login or register first. When registering, you will receive an activation email. Be sure to check your spam if you don't see it in your email within 60 minutes.", 'templates', 'WPBDM'); ?></p>
	<form method="POST" action="<?php echo wpbdp_get_option('login-url', get_option('siteurl') . '/wp-login.php'); ?>">
		<input type="submit" class="insubmitbutton" value="<?php _ex('Login Now', 'templates', 'WPBDM'); ?>" />
	</form>
<?php else: ?>
	<?php if (have_posts()): ?>
		<p><?php _ex("Your current listings are shown below. To edit a listing click the edit button. To delete a listing click the delete button.", 'templates', "WPBDM"); ?></p>

		<?php while(have_posts()): the_post(); ?>
			<?php echo wpbusdirman_post_excerpt(); ?>
		<?php endwhile; ?>

		<div class="navigation">
			<?php if (function_exists('wp_pagenavi')) : ?>
				<?php echo wp_pagenavi(); ?>
			<?php else: ?>
				<div class="alignleft">
					<?php next_posts_link(_x('&laquo; Older Entries', 'templates', 'WPBDM')); ?>
				</div>
				<div class="alignright">
					<?php previous_posts_link(_x('Newer Entries &raquo;', 'templates', 'WPBDM')); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php else: ?>
		<p><?php _ex('You do not currently have any listings in the directory.', 'templates', 'WPBDM'); ?></p>
	<?php endif; ?>
<?php endif; ?>