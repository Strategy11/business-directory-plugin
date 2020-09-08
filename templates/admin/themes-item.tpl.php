    <div class="wpbdp-theme <?php echo $theme->id; ?> <?php echo ( $theme->active ? 'active' : '' ); ?> <?php do_action( 'wpbdp-admin-themes-item-css', $theme ); ?> ">
        <h3 class="wpbdp-theme-name">
            <?php if ( $theme->active ): ?><span><?php _ex( 'Active:', 'themes', 'business-directory-plugin' ); ?></span> <?php endif; ?>
            <?php echo $theme->name; ?>
        </h3>

        <div class="wpbdp-theme-actions">
            <?php if ( ! $theme->active && ! in_array( $theme->id, array( 'default', 'no_theme' ), true ) ): ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp-themes&action=delete-theme&theme_id=' . $theme->id ) ); ?>" class="button delete-theme-link">Delete</a>
            <?php endif; ?>

            <?php if ( $theme->can_be_activated ): ?>
            <form action="" method="post">
                <input type="hidden" name="wpbdp-action" value="set-active-theme" />
                <input type="hidden" name="theme_id" value="<?php echo $theme->id; ?>" />
                <?php wp_nonce_field( 'activate theme ' . $theme->id ); ?>
                <input type="submit" class="button choose-theme button-primary" value="<?php _ex( 'Activate', 'themes', 'business-directory-plugin' ); ?>" />
            </form>
            <?php endif; ?>
        </div>

        <?php if ( $theme->can_be_activated && $is_outdated ) :
            printf( '<div class="wpbdp-theme-update-info update-available" data-l10n-updating="%s" data-l10n-updated="%s">',
                    _x( 'Updating theme...', 'themes', 'business-directory-plugin' ),
                    _x( 'Theme updated.', 'themes', 'business-directory-plugin' ) );
            ?>
                <div class="update-message">
                    <?php 
                    $msg = _x( 'New version available. <a>Update now.</a>', 'themes', 'business-directory-plugin' );
                    $msg = str_replace( '<a>', '<a href="#" data-theme-id="' . $theme->id . '" data-nonce="' . wp_create_nonce( 'update theme ' . $theme->id ) . '" class="update-link">', $msg );
                    echo $msg;
                    ?>
                </div>

            </div>
        <?php endif; ?>

        <div class="wpbdp-theme-details-wrapper">
            <?php if ( $theme->thumbnail ): ?>
                <a href="<?php echo $theme->thumbnail; ?>" title="<?php esc_attr_e( $theme->name ); ?>" class="thickbox" rel="wpbdp-theme-<?php echo $theme->id; ?>-gallery"><img src="<?php echo $theme->thumbnail; ?>" class="wpbdp-theme-thumbnail" /></a>
                <!-- Other images -->
                <?php foreach ( $theme->thumbnails as $imgpath => $title ): ?>
                    <a href="<?php echo $theme->url; ?><?php echo $imgpath; ?>" class="thickbox" title="<?php esc_attr_e( $title ); ?>" class="thickbox" rel="wpbdp-theme-<?php echo $theme->id; ?>-gallery" style="display: none;"></a>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="wpbdp-theme-thumbnail"></div>
            <?php endif; ?>

            <div class="wpbdp-theme-details">
                <dl>
                    <dt class="version"><?php _ex( 'Version:', 'themes', 'business-directory-plugin' ); ?></dt>
                    <dd class="version"><?php echo $theme->version; ?></dd>

                    <dt class="author"><?php _ex( 'Author:', 'themes', 'business-directory-plugin' ); ?></dt>
                    <dd class="author"><?php echo $theme->author; ?></dd>
                </dl>

                <p class="desc"><?php echo $theme->description; ?></p>
            </div>

        </div>

        <?php do_action( 'wpbdp-admin-themes-extra', $theme ); ?>
        
    </div>
