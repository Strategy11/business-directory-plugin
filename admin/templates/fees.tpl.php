<?php
	echo wpbdp_admin_header(null, null, wpbdp_get_option('payments-on') ? array(
		array(_x('Add New Form Field', 'form-fields admin', 'WPBDM'), esc_url(add_query_arg('action', 'addfield'))),
	) : null);
?>
	<?php wpbdp_admin_notices(); ?>

	<?php if (!wpbdp_get_option('payments-on')): ?>
		<p><?php _ex('Payments are currently turned off. To manage fees you need to go to the Manage Options page and check the box next to \'Turn on payments\' under \'General Payment Settings\'', 'fees admin', 'WPBDM'); ?></p>
	<?php else: ?>
		<?php // _ex('Make changes to your existing form fields.', 'form-fields admin', 'WPBDM'); ?>

		<?php $table->views(); ?>
		<?php $table->display(); ?>	
	<?php endif; ?>

<?php echo wpbdp_admin_footer(); ?>