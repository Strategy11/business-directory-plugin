<?php
echo wpbdp_admin_header( __( 'Business Directory Settings', 'WPBDM' ),
                         'admin-settings',
                         array( array( _x( 'Reset Defaults', 'settings', 'WPBDM' ),
                                       admin_url( 'admin.php?page=wpbdp_admin_settings&action=reset' ) )
                              ) );
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
	   href="<?php echo esc_url( add_query_arg('groupid', $g->slug, remove_query_arg('settings-updated')) ); ?>">
	   <?php echo apply_filters( 'wpbdp_settings_group_tab_name', $g->name, $g ); ?>
	</a>
<?php endforeach; ?>
</h3>

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

<?php
	echo wpbdp_admin_footer();
?>
