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
                    <div class="license-activation">
                    <input type="textfield" size="25" name="license" value="<?php echo $t->license_key; ?>" <?php echo $t->license_key ? 'readonly="readonly"' : ''; ?> />

                        <input type="button" name="deactivate" class="button button-secondary" value="<?php _ex( 'Deactivate License', 'themes', 'WPBDM' ); ?>"
                               data-nonce="<?php echo wp_create_nonce( 'deactivate ' . $t->id ); ?>"
                               data-theme="<?php echo $t->id; ?>"
                               data-l10n="<?php _ex( 'Deactivating license...', 'themes', 'WPBDM' ); ?>"
                               style="<?php echo ( empty( $t->license_key ) ) ? 'display: none;' : '' ; ?>" />
                        <input type="button" name="activate" class="button button-primary" value="<?php _ex( 'Activate License', 'themes', 'WPBDM' ); ?>"
                               data-l10n="<?php _ex( 'Activating license...', 'themes', 'WPBDM' ); ?>"
                               data-nonce="<?php echo wp_create_nonce( 'activate ' . $t->id ); ?>"
                               data-theme="<?php echo $t->id; ?>"
                               style="<?php echo ( ! empty( $t->license_key ) ) ? 'display: none;' : '' ; ?>" />
                        <span class="status-message"></span>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
echo wpbdp_admin_footer();
?>
