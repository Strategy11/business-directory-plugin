<?php echo wpbdp_admin_header( _x( 'Add Listing Fee', 'fees admin', 'WPBDM' ) ); ?>
<?php wpbdp_admin_notices(); ?>
<?php echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/fees-form.tpl.php', array( 'fee' => $fee ) ); ?>
<?php echo wpbdp_admin_footer(); ?>
