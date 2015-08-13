<?php
echo wpbdp_admin_header( _x( 'Upload Theme', 'themes', 'WPBDM' ), 'themes-install', array() );
?>
<?php echo wpbdp_admin_notices(); ?>

<form action="" method="post" enctype="multipart/form-data">
    <?php wp_nonce_field( 'upload theme zip' ); ?>
    <input type="file" name="themezip" />
    <input type="submit" />
</form>

<?php
echo wpbdp_admin_footer();
?>

