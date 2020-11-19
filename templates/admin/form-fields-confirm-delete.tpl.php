<?php
wpbdp_admin_header(
    array(
        'title' => __( 'Delete Form Field', 'business-directory-plugin' ),
        'echo'  => true,
    )
);
?>

<p>
	<?php esc_html_e( 'Are you sure you want to delete that field?', 'business-directory-plugin' ); ?>
</p>

<form action="" method="POST">
	<input type="hidden" name="id" value="<?php echo esc_attr( $field->get_id() ); ?>" />
	<input type="hidden" name="doit" value="1" />
	<?php submit_button( esc_html__( 'Delete Field', 'business-directory-plugin' ), 'delete' ); ?>
</form>

<?php wpbdp_admin_footer( true ); ?>
