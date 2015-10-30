<?php
echo wpbdp_admin_header( null, 'themes', array(
    array( _x( 'Upload Directory Theme', 'themes', 'WPBDM' ), esc_url( add_query_arg( 'action', 'theme-install' ) ) )
) );

echo wpbdp_admin_notices();
?>

<div id="wpbdp-admin-page-themes-tabs">

<?php echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/themes-tabs.tpl.php' ); ?>

<div id="wpbdp-theme-selection" class="wpbdp-theme-selection cf">
<?php foreach ( $themes as &$t ): ?>
    <?php echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/themes-item.tpl.php', array( 'theme' => $t ) ); ?>
<?php endforeach; ?>
</div>

</div>

<?php
echo wpbdp_admin_footer();
?>
