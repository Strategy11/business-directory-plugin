<?php
/**
 * Uninstall confirm template
 *
 * @package WPBDP/Templates/Admin
 */

wpbdp_admin_header(
    array(
        'title' => __( 'Uninstall Business Directory Plugin', 'business-directory-plugin' ),
        'id'    => 'admin-uninstall',
        'echo'  => true,
    )
);
?>

<?php wpbdp_admin_notices(); ?>

<div id="wpbdp-uninstall-messages">
    <div id="wpbdp-uninstall-warning">
        <div class="wpbdp-warning-margin">
            <p><span class="dashicons dashicons-warning"></span></p>
        </div>
        <div class="wpbdp-warning-content">
            <p><?php _ex( 'Uninstalling Business Directory Plugin will do the following:', 'uninstall', 'business-directory-plugin' ); ?></p>

            <ul>
                <li><?php _ex( 'Remove ALL directory listings', 'uninstall', 'business-directory-plugin' ); ?></li>
                <li><?php _ex( 'Remove ALL directory categories', 'uninstall', 'business-directory-plugin' ); ?></li>
                <li><?php _ex( 'Remove ALL directory settings', 'uninstall', 'business-directory-plugin' ); ?></li>
                <li><?php _ex( 'Remove ALL premium module configuration data (regions, maps, ratings, featured levels)', 'uninstall', 'business-directory-plugin' ); ?></li>
                <li><?php _ex( 'Deactivate the plugin from the file system', 'uninstall', 'business-directory-plugin' ); ?></li>
            </ul>

            <p><?php _ex( 'ONLY do this if you are sure you\'re OK with LOSING ALL OF YOUR DATA.', 'uninstall', 'business-directory-plugin' ); ?></p>
        </div>

        <a id="wpbdp-uninstall-proceed-btn" class="button"><?php _ex( 'Yes, I want to uninstall', 'uninstall', 'business-directory-plugin' ); ?></a>
    </div>

    <div id="wpbdp-uninstall-reinstall-suggestion">
        <p><?php _ex( 'If you just need to reinstall the plugin, please do the following:', 'uninstall', 'business-directory-plugin' ); ?></p>

        <ul>
            <li><?php echo str_replace( '<a>', '<a href="' . admin_url( 'plugins.php?plugin_status=active' ) . '">', _x( 'Go to <a>Plugins->Installed Plugins', 'uninstall', 'business-directory-plugin' ) ); ?></a></li>
            <li><?php _ex( 'Click on "Deactivate" for Business Directory Plugin. Wait for this to finish', 'uninstall', 'business-directory-plugin' ); ?></li>
            <li><?php _ex( 'Click on "Delete" for Business Directory Plugin. <i>THIS OPERATION IS SAFE--your data will NOT BE LOST doing this</i>', 'uninstall', 'business-directory-plugin' ); ?></li>
            <li><?php _ex( 'Wait for the delete to finish', 'uninstall', 'business-directory-plugin' ); ?></li>
            <li><?php _ex( 'The plugin is now removed, but your data is still present inside of your database.', 'uninstall', 'business-directory-plugin' ); ?></li>
            <li><?php echo str_replace( '<a>', '<a href="' . admin_url( 'plugin-install.php' ) . '">', _x( 'You can reinstall the plugin again under <a>Plugins->Add New</a>', 'uninstall', 'business-directory-plugin' ) ); ?></li>
        </ul>

        <a href="<?php echo admin_url( 'plugins.php' ); ?>" class="button"><?php _ex( 'Take me to the <b>Plugins</b> screen', 'uninstall', 'business-directory-plugin' ); ?></a>
    </div>
</div>

<?php echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/uninstall-capture-form.tpl.php' ); ?>

<?php echo wpbdp_admin_footer(); ?>
