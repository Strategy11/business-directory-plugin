<?php
echo wpbdp_admin_header( null, 'themes', array(
    array( _x( 'Upload Directory Theme', 'themes', 'WPBDM' ), esc_url( add_query_arg( 'action', 'theme-install' ) ) ),
    array( _x( 'Manage Theme Tags', 'form-fields admin', 'WPBDM' ), esc_url( add_query_arg( 'action', 'updatetags' ) ) ),
    array( _x( 'Settings', 'themes', 'WPBDM' ), esc_url( admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=themes' ) ) )
) );

echo wpbdp_admin_notices();
?>

<div class="wpbdp-note">
<?php
echo str_replace( '<a>',
                  '<a href="http://businessdirectoryplugin.com/premium-themes/" target="_blank">',
                  _x( '<a><b>Directory Themes</b></a> are pre-made templates for the <i>Business Directory Plugin</i> to change the look of the directory quickly and easily. We have a number of them available for purchase <a>here</a>.', 'themes', 'WPBDM' ) ); ?><br />
<?php echo _x( 'They are <strong>different</strong> than your regular WordPress theme and they are <strong>not</strong> a replacement for WP themes either. They will change the look and feel of your business directory only.', 'themes', 'WPBDM' ); ?>
</div>

<!--[Directory Themes] and [here] should link to -->

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
