<?php
echo wpbdp_admin_header( _x( 'Upload Theme', 'themes', 'WPBDM' ), 'themes-install', array() );
?>
<?php echo wpbdp_admin_notices(); ?>

<form action="" method="post" enctype="multipart/form-data">
    <?php wp_nonce_field( 'upload theme zip' ); ?>

    <table class="form-table">
        <tbody>
            <tr>
                <th>
                    <?php _ex( 'Theme file', 'themes', 'WPBDM' ); ?>
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

