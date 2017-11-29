<?php
$original_uri = $_SERVER['REQUEST_URI'];
$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'tab', 'subtab' ) );

echo wpbdp_admin_header( array(
    'title'   => __( 'Business Directory Settings', 'WPBDM' ),
    'buttons' => array( _x( 'Reset Defaults', 'settings', 'WPBDM' ) => add_query_arg( 'reset_defaults', 1 ) )
) );
?>

<h2 class="nav-tab-wrapper">
    <?php foreach ( $tabs as $tab_id => $tab ): ?>
    <a class="nav-tab <?php echo $active_tab == $tab_id ? 'nav-tab-active' : ''; ?> <?php echo apply_filters( 'wpbdp_settings_tab_css', '', $tab_id ); ?>" href="<?php echo esc_url( add_query_arg( 'tab', $tab_id ) ); ?>"><?php echo $tab['title']; ?></a>
    <?php endforeach; ?>
</h2>

<?php if ( count( $subtabs ) > 1 || 'modules' == $active_tab ): ?>
<div class="wpbdp-settings-tab-subtabs wpbdp-clearfix">
    <ul class="subsubsub">
    <?php
    $n = 0;
    foreach ( $subtabs as $subtab_id => $subtab ):
        $n++;
    ?>
        <?php
        $subtab_url = add_query_arg( 'tab', $active_tab );
        $subtab_url = add_query_arg( 'subtab', $subtab_id, $subtab_url );
        ?>
        <li>
            <a class="<?php echo $active_subtab == $subtab_id ? 'current' : ''; ?>" href="<?php echo esc_url( $subtab_url ); ?>"><?php echo $subtab['title']; ?></a>
            <?php if ( $n != count( $subtabs ) ): ?> | <?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php settings_errors(); ?>

<?php if ( $active_subtab_description ): ?>
<p class="wpbdp-settings-subtab-description wpbdp-setting-description"><?php echo $active_subtab_description; ?></p>
<?php endif; ?>

<?php if ( ! $custom_form ): ?>
<form action="options.php" method="post">
<?php endif; ?>

<?php
    $_SERVER['REQUEST_URI'] = $original_uri;

    if ( ! $custom_form ):
        settings_fields( 'wpbdp_settings' );
    endif;

    do_settings_sections( 'wpbdp_settings_subtab_' . $active_subtab );

    if ( ! $custom_form ):
        // Submit button shouldn't use 'submit' as name to avoid conflicts with
        // actual properties of the parent form.
        //
        // See http://kangax.github.io/domlint/
        submit_button( null, 'primary', 'save-changes' );
    endif;
?>

<?php if ( ! $custom_form ): ?>
</form>
<?php endif; ?>

<?php
    echo wpbdp_admin_footer();

    /*
<h3 class="nav-tab-wrapper">
<?php if (isset($_REQUEST['settings-updated'])): ?>
	<div class="updated fade">
		<p><?php _e('Settings updated.', 'WPBDM'); ?></p>
	</div>
<?php endif; ?>
</h3>
	<?php if ($group->help_text): ?>
		<p class="description"><?php echo $group->help_text; ?></p>
	<?php endif; ?>

<?php
	echo wpbdp_admin_footer();
?>
 */
        // $reset_defaults = ( isset( $_GET['action'] ) && 'reset' == $_GET['action'] );
        // if ( $reset_defaults ) {
        //     echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/settings-reset.tpl.php' );
        //     return;
        // }
?>
