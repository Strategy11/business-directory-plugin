<?php
	echo wpbdp_admin_header( _x( 'Transactions', 'admin transactions', 'WPBDM' ), 'admin-transactions' );
	wpbdp_admin_notices();
?>

<?php $table->views(); ?>
<?php $table->display(); ?>

<?php echo wpbdp_admin_footer(); ?>