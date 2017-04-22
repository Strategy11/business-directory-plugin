<?php echo wpbdp_admin_header( _x( 'Edit Listing Fee', 'fees admin', 'WPBDM' ) ); ?>
<?php wpbdp_admin_notices(); ?>
<?php echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/fees-form.tpl.php', array( 'fee' => $fee ) ); ?>
<?php echo wpbdp_admin_footer(); ?>
