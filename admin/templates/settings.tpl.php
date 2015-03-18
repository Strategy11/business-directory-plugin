<?php
	echo wpbdp_admin_header(__('Business Directory Settings', 'WPBDM'), 'admin-settings');
?>
<script type="text/javascript">
<?php
foreach ( $wpbdp_settings->get_dependencies( 'type=requires-true' ) as $s => $data_ ):
    $parent = array_pop( $data_ );
?>
WPBDP_Admin.settings.add_requirement( '<?php echo $s; ?>', '<?php echo $parent; ?>', 'boolean-true' );
<?php
endforeach;
?>
</script>

<h3 class="nav-tab-wrapper">
<?php if (isset($_REQUEST['settings-updated'])): ?>
	<div class="updated fade">
		<p><?php _e('Settings updated.', 'WPBDM'); ?></p>
	</div>
<?php endif; ?>

<?php foreach($wpbdp_settings->groups as $g): ?>
	<a class="nav-tab <?php echo $g->slug == wpbdp_getv($_REQUEST, 'groupid', 'general') ? 'nav-tab-active': ''; ?> <?php echo apply_filters( 'wpbdp_settings_group_tab_css', '', $g ); ?>"
	   href="<?php echo add_query_arg('groupid', $g->slug, remove_query_arg('settings-updated')); ?>">
	   <?php echo apply_filters( 'wpbdp_settings_group_tab_name', $g->name, $g ); ?>
	</a>
<?php endforeach; ?>
	<a class="nav-tab <?php echo wpbdp_getv($_REQUEST, 'groupid') == 'resetdefaults' ? 'nav-tab-active' : ''; ?>"
        href="<?php echo add_query_arg('groupid', 'resetdefaults', remove_query_arg('settings-updated')); ?>">
        <?php _e('Reset Defaults', 'WPBDM'); ?>
   	</a>
</h3>

<?php if (wpbdp_getv($_REQUEST, 'groupid', 'general') == 'resetdefaults'): ?>

<p><?php _e('Use this option if you want to go back to the original factory settings for BD. <b>Please note that all of your existing settings will be lost.</b>', 'WPBDM'); ?></p>
<form action="" method="POST">
	<input type="hidden" name="resetdefaults" value="1" />
	<?php echo submit_button(__('Reset Defaults', 'WPBDM')); ?>
</form>

<?php else: ?>
<?php
	$group = $wpbdp_settings->groups[wpbdp_getv($_REQUEST, 'groupid', 'general')];
?>

<form action="<?php echo admin_url('options.php'); ?>" method="POST" id="wpbdp-admin-settings">
	<input type="hidden" name="groupid" value="<?php echo $group->slug; ?>" />
	<?php if ($group->help_text): ?>
		<p class="description"><?php echo $group->help_text; ?></p>
	<?php endif; ?>
	<?php settings_fields($group->wpslug); ?>
	<?php do_settings_sections($group->wpslug); ?>
	<?php echo submit_button(); ?>
</form>
<?php endif; ?>

<?php
	echo wpbdp_admin_footer();
?>
