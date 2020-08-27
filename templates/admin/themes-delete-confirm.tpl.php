<?php echo wpbdp_admin_header( _x( 'Delete Directory Theme', 'themes admin', 'business-directory-plugin' ), 'theme-delete' ); ?>

<p><?php printf( _x( 'Are you sure you want to delete the directory theme "%s"?', 'themes admin', 'business-directory-plugin' ),
                 $theme->name ); ?></p>

<form action="" method="post">
    <input type="hidden" name="theme_id" value="<?php echo $theme->id; ?>" />
    <input type="hidden" name="dodelete" value="1" />
    <input type="hidden" name="wpbdp-action" value="delete-theme" />
    <?php wp_nonce_field( 'delete theme ' . $theme->id ); ?>

    <?php submit_button( _x('Cancel', 'themes admin', 'business-directory-plugin' ), 'secondary', 'cancel', false ); ?>
    <?php submit_button( _x('Delete Directory Theme', 'themes admin', 'business-directory-plugin' ), 'delete', 'delete-theme', false ); ?>
</form>

<?php echo wpbdp_admin_footer(); ?>
