<?php
echo wpbdp_admin_header(
    null,
    'themes',
    array(
		array( _x( 'Upload Directory Theme', 'themes', 'business-directory-plugin' ), esc_url( add_query_arg( 'action', 'theme-install' ) ) ),
		array( _x( 'Manage Theme Tags', 'form-fields admin', 'business-directory-plugin' ), esc_url( 'edit.php?post_type=wpbdp_listing&page=wpbdp_admin_formfields&action=updatetags' ) ),
		array( _x( 'Settings', 'themes', 'business-directory-plugin' ), esc_url( admin_url( 'edit.php?post_type=wpbdp_listing&page=wpbdp_settings&tab=appearance&subtab=themes' ) ) ),
    ),
    true
);

echo wpbdp_admin_notices();
?>

<div class="wpbdp-note">
<?php
echo str_replace(
    '<a>',
    '<a href="http://businessdirectoryplugin.com/premium-themes/" target="_blank" rel="noopener">',
    _x( '<a><b>Directory Themes</b></a> are pre-made templates for the <i>Business Directory Plugin</i> to change the look of the directory quickly and easily. We have a number of them available for purchase <a>here</a>.', 'themes', 'business-directory-plugin' )
);
?>
                  <br />
<?php echo _x( 'They are <strong>different</strong> than your regular WordPress theme and they are <strong>not</strong> a replacement for WP themes either. They will change the look and feel of your business directory only.', 'themes', 'business-directory-plugin' ); ?>
</div>

<div id="wpbdp-theme-selection" class="wpbdp-theme-selection cf">
<?php foreach ( $themes as &$t ) : ?>
    <?php
    echo wpbdp_render_page(
        WPBDP_PATH . 'templates/admin/themes-item.tpl.php',
        array(
			'theme'       => $t,
			'is_outdated' => in_array(
                $t->id,
                $outdated_themes
			),
        )
    );
	?>
<?php endforeach; ?>
</div>

<?php
echo wpbdp_admin_footer();
?>
