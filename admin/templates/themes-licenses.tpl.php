<?php
echo wpbdp_admin_header( null, 'themes', array(
    array( _x( 'Upload Directory Theme', 'themes', 'WPBDM' ), esc_url( add_query_arg( 'action', 'theme-install' ) ) )
) );

echo wpbdp_admin_notices();
?>

<?php echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/themes-tabs.tpl.php', array( 'active' => 'licenses' ) ); ?>

<div id="wpbdp-theme-licenses">
    <table class="form-table">
        <tbody>
        <?php foreach ( $themes as $t ): ?>
        <?php if ( $t->is_core_theme ): continue; endif; ?>
            <tr>
                <th scope="row">
                    <?php echo $t->name; ?>
                </th>
                <td>
                    <form action="" method="post" class="license-activation">
                        <input type="hidden" name="action" value="wpbdp-themes-activate-license" />
                        <?php wp_nonce_field( 'activate ' . $t->id ); ?>
                        <input type="hidden" name="theme" value="<?php echo $t->id; ?>" />
                        <input type="textfield" size="25" name="license" value="<?php echo $t->license_key; ?>" />
                        <input type="submit" name="activate" class="button button-primary" value="Activate License"
                               data-l10n="Activating license..." />
                        <span class="status-message"></span>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
echo wpbdp_admin_footer();
?>
