<?php
	echo wpbdp_admin_header(__('Business Directory Settings'), 'admin-settings');
?>

<h3 class="nav-tab-wrapper">
<?php foreach($wpbdp_settings->groups as $g): ?>
	<a class="nav-tab <?php echo $g->slug == wpbdp_getv($_REQUEST, 'groupid', 'general') ? 'nav-tab-active': ''; ?>"
	   href="<?php echo add_query_arg('groupid', $g->slug); ?>">
	   <?php echo $g->name; ?>
	</a>
<?php endforeach; ?>
</h3>

<?php
	$group = $wpbdp_settings->groups[wpbdp_getv($_REQUEST, 'groupid', 'general')];
?>

<form action="<?php echo admin_url('options.php'); ?>" method="POST">
	<input type="hidden" name="groupid" value="<?php echo $group->slug; ?>" />
	<?php settings_fields($group->wpslug); ?>
	<?php do_settings_sections($group->wpslug); ?>
	<?php echo submit_button(); ?>
</form>

<?php
	echo wpbdp_admin_footer();
?>