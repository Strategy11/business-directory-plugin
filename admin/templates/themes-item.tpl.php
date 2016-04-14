    <div class="wpbdp-theme <?php echo $theme->id; ?> <?php echo ( $theme->active ? 'active' : '' ); ?> <?php do_action( 'wpbdp-admin-themes-item-css', $theme ); ?> ">
        <h3 class="wpbdp-theme-name">
            <?php if ( $theme->active ): ?><span><?php _ex( 'Active:', 'themes', 'WPBDM' ); ?></span> <?php endif; ?>
            <?php echo $theme->name; ?>
        </h3>

        <div class="wpbdp-theme-actions">
            <?php if ( ! $theme->active && ! in_array( $theme->id, array( 'default', 'no_theme' ), true ) ): ?>
            <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'delete-theme', 'theme_id' => $theme->id ) ) ); ?>" class="button delete-theme-link">Delete</a>
            <?php endif; ?>

            <?php if ( $theme->can_be_activated ): ?>
            <form action="" method="post">
                <input type="hidden" name="wpbdp-action" value="set-active-theme" />
                <input type="hidden" name="theme_id" value="<?php echo $theme->id; ?>" />
                <?php wp_nonce_field( 'activate theme ' . $theme->id ); ?>
                <input type="submit" class="button choose-theme button-primary" value="<?php _ex( 'Activate', 'themes', 'WPBDM' ); ?>" />
            </form>
            <?php endif; ?>
        </div>

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
                    <dt class="version"><?php _ex( 'Version:', 'themes', 'WPBDM' ); ?></dt>
                    <dd class="version"><?php echo $theme->version; ?></dd>

                    <dt class="author"><?php _ex( 'Author:', 'themes', 'WPBDM' ); ?></dt>
                    <dd class="author"><?php echo $theme->author; ?></dd>
                </dl>

                <p class="desc"><?php echo $theme->description; ?></p>
            </div>

        </div>

        <?php do_action( 'wpbdp-admin-themes-extra', $theme ); ?>
    </div>
