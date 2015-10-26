<?php
echo wpbdp_admin_header( null, 'themes', array(
    array( _x( 'Upload Directory Theme', 'themes', 'WPBDM' ), esc_url( add_query_arg( 'action', 'theme-install' ) ) )
) );

echo wpbdp_admin_notices();
?>

<div class="wpbdp-theme-selection cf">
<?php foreach ( $themes as &$t ): ?>
    <div class="wpbdp-theme <?php echo $t->id; ?> <?php echo ( $t->id == $active_theme ) ? 'active' : ''; ?>">
        <h3 class="wpbdp-theme-name">
            <?php if ( $t->id == $active_theme ): ?><span><?php _ex( 'Active:', 'themes', 'WPBDM' ); ?></span> <?php endif; ?>
            <?php echo $t->name; ?>
        </h3>

        <div class="wpbdp-theme-actions">
            <form action="" method="post">
                <input type="hidden" name="wpbdp-action" value="set-active-theme" />
                <input type="hidden" name="theme_id" value="<?php echo $t->id; ?>" />
                <?php wp_nonce_field( 'activate theme ' . $t->id ); ?>
                <input type="submit" class="button choose-theme button-primary" value="<?php _ex( 'Activate', 'themes', 'WPBDM' ); ?>" />
            </form>
        </div>

        <?php if ( $t->thumbnail ): ?>
        <img src="<?php echo $t->thumbnail; ?>" class="wpbdp-theme-thumbnail" />
        <?php else: ?>
        <div class="wpbdp-theme-thumbnail"></div>
        <?php endif; ?>

        <div class="wpbdp-theme-details">
            <dl>
                <dt class="version"><?php _ex( 'Version:', 'themes', 'WPBDM' ); ?></dt>
                <dd class="version"><?php echo $t->version; ?></dd>

                <dt class="author"><?php _ex( 'Author:', 'themes', 'WPBDM' ); ?></dt>
                <dd class="author"><?php echo $t->author; ?></dd>
            </dl>

            <p class="desc"><?php echo $t->description; ?></p>
        </div>

            <?php if ( ! in_array( $t->id, array( $active_theme, 'default', 'no_theme' ), true ) ): ?>
            <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'delete-theme', 'theme_id' => $t->id ) ) ); ?>" class="delete-theme-link">Delete</a>
            <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<?php
echo wpbdp_admin_footer();
?>
