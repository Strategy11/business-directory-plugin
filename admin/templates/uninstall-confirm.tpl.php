<?php echo wpbdp_admin_header( _x( 'Uninstall Business Directory Plugin', 'uninstall', 'WPBDM' ), 'admin-uninstall' ); ?>

<?php wpbdp_admin_notices(); ?>

<?php
    echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/uninstall-capture-form.tpl.php' );
?>

<?php echo wpbdp_admin_footer(); ?>
