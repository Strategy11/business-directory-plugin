<?php
echo wpbdp_admin_header( __( 'Business Directory - Reset Defaults', 'WPBDM' ),
                         'admin-settings',
                         array( array( _x( '← Return to "Manage Options"', 'settings', 'WPBDM' ),
                                       admin_url( 'admin.php?page=wpbdp_admin_settings' ) )
                              ) );
?>

<div class="wpbdp-note warning">
    <p><?php _e( 'Use this option if you want to go back to the original factory settings for BD.', 'WPBDM' ); ?></p>
    <p><b><?php _e( 'Please note that all of your existing settings will be lost.', 'WPBDM' ); ?></b></p>
</div>

<form action="" method="POST">
    <input type="hidden" name="wpbdp-action" value="reset-default-settings" />
    <?php wp_nonce_field( 'reset defaults' ); ?>
	<?php echo submit_button( __( 'Reset Defaults', 'WPBDM' ), 'delete button-primary' ); ?>
</form>

<?php
	echo wpbdp_admin_footer();
?>
