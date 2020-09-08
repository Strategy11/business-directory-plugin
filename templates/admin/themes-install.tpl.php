<?php
echo wpbdp_admin_header( _x( 'Upload Directory Theme', 'themes', 'business-directory-plugin' ), 'themes-install', array() );
?>
<?php echo wpbdp_admin_notices(); ?>

<div class="wpbdp-note">
<p><?php
printf( _x( 'This is a theme or skin from %s and is NOT a regular WordPress theme.', 'themes', 'business-directory-plugin' ),
        '<a href="http://businessdirectoryplugin.com/premium-themes/">http://businessdirectoryplugin.com/premium-themes/</a>' );
?></p>
</div>

<form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="wpbdp-action" value="upload-theme" />
    <?php wp_nonce_field( 'upload theme zip' ); ?>

    <table class="form-table">
        <tbody>
            <tr>
                <th>
                    <?php esc_html_e( 'Business Directory Theme archive (ZIP file)', 'business-directory-plugin' ); ?>
                </th>
                <td>
                    <input type="file" name="themezip" />
                </td>
            </tr>
        </tbody>
    </table>

    <?php submit_button( _x( 'Begin Upload', 'themes', 'business-directory-plugin' ), 'primary', 'begin-theme-upload' ); ?>
</form>

<?php
echo wpbdp_admin_footer();
?>

