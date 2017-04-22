<?php
echo wpbdp_admin_header( _x( 'Upload Directory Theme', 'themes', 'WPBDM' ), 'themes-install', array() );
?>
<?php echo wpbdp_admin_notices(); ?>

<div class="wpbdp-note">
<p><?php
printf( _x( 'This is a theme or skin from %s and is NOT a regular WordPress theme.', 'themes', 'WPBDM' ),
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
                    <?php _ex( 'BD Theme archive (ZIP file)', 'themes', 'WPBDM' ); ?>
                </th>
                <td>
                    <input type="file" name="themezip" />
                </td>
            </tr>
        </tbody>
    </table>

    <?php submit_button( _x( 'Begin Upload', 'themes', 'WPBDM' ), 'primary', 'begin-theme-upload' ); ?>
</form>

<?php
echo wpbdp_admin_footer();
?>

