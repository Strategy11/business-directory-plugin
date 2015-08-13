<?php
echo wpbdp_admin_header( null, 'themes', array(
    array( _x( 'Upload Theme', 'themes', 'WPBDM' ), esc_url( add_query_arg( 'action', 'theme-install' ) ) )
) );
?>

<div class="wpbdp-theme-selection cf">
<?php foreach ( $themes as &$t ): ?>
    <div class="wpbdp-theme <?php echo $t->id; ?> <?php echo ( $t->id == $active_theme ) ? 'active' : ''; ?>" data-theme-id="<?php echo $t->id; ?>">
        <h3 class="wpbdp-theme-name"><?php echo $t->name; ?></h3>

        <div class="wpbdp-theme-actions">
            <a href="#"
               class="choose-theme button button-primary"
               data-nonce="<?php echo wp_create_nonce( 'activate theme ' . $t->id ) ;?>">
                <?php _ex( 'Activate', 'themes', 'WPBDM' ); ?>
            </a>
        </div>

        <?php if ( $t->thumbnail ): ?>
        <img src="<?php echo $t->thumbnail; ?>" class="wpbdp-theme-thumbnail" />
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
    </div>
<?php endforeach; ?>
</div>

<?php
echo wpbdp_admin_footer();
?>
