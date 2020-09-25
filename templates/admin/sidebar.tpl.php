<?php
$modules = array(
    array( 'categories', 'enhanced-categories', __( 'Category Images', 'business-directory-plugin' ) ),
    array( 'paypal', 'paypal-gateway-module', __( 'PayPal Payments', 'business-directory-plugin' ) ),
    array( 'googlemaps', 'google-maps-module', __( 'Google Maps', 'business-directory-plugin' ) ),
    array( 'regions', 'regions-module', __( 'Regions', 'business-directory-plugin' ) ),
    array( 'zipcodesearch', 'zip-search-module', __( 'ZIP Code Search', 'business-directory-plugin' ) ),
    array( 'ratings', 'ratings-module', __( 'Ratings', 'business-directory-plugin' ) ),
    array( 'stripe', 'stripe-payment-module', __( 'Stripe Payments', 'business-directory-plugin' ) ),
    array( 'featured-levels', 'featured-levels-module', __( 'Featured Levels', 'business-directory-plugin' ) ),
    array( 'claim-listings', 'claim-listings-module', __( 'Claim Listings', 'business-directory-plugin' ) ),
    array( 'attachments', 'file-attachments-module', __( 'File Upload', 'business-directory-plugin' ) ),
    array( 'discount-codes', 'discount-codes-module', __( 'Discount Codes', 'business-directory-plugin' ) ),
);

$themes = array(
    array( 'modern-business-theme', __( 'Modern Business Theme', 'business-directory-plugin' ) ),
    array( 'business-card-theme', __( 'Business Card Theme', 'business-directory-plugin' ) ),
    array( 'mobile-compact-theme', __( 'Mobile Compact Theme', 'business-directory-plugin' ) ),
    array( 'restaurant-theme', __( 'Restaurant Theme', 'business-directory-plugin' ) ),
    array( 'tabbed-business-theme', __( 'Tabbed Business Theme', 'business-directory-plugin' ) ),
    array( 'elegant-business-theme', __( 'Elegant Business Theme', 'business-directory-plugin' ) ),
);
?>
<div class="sidebar">
    <div class="meta-box-sortables metabox-holder ui-sortable" id="side-sortables">
        <!-- Like this plugin? -->
        <div class="postbox">
            <h3 class="hndle"><span><?php esc_html_e( 'Like this plugin?', 'business-directory-plugin' ); ?></span></h3>
            <div class="inside">
                <ul>
                    <li class="li_link">
                        Please rate <strong>Business Directory Plugin</strong> <a href="https://wordpress.org/support/plugin/business-directory-plugin/reviews/?filter=5#new-post" target="_blank" rel="noopener">★★★★★ on WordPress.org</a> to help us spread the word.</a></li>
                </ul>
            </div>
        </div>

        <!-- Premium modules -->
        <div class="postbox premium-modules">
            <h3 class="hndle"><span><?php esc_html_e( 'Make better directories', 'business-directory-plugin' ); ?></span></h3>
            <div class="inside">
                <ul>
                <?php foreach ( $modules as $mod_info ) : ?>
                    <?php
                    if ( wpbdp_has_module( $mod_info[0] ) ) {
                        continue;
                    }
                    ?>
                    <li class="li_link">
                        <?php
                        if ( isset( $mod_info[3] ) && 'new' === $mod_info[3] ) :
							?>
                            <span class="tag new"><?php esc_html_e( 'new', 'business-directory-plugin' ); ?></span> <?php endif; ?>
                        <a href="https://businessdirectoryplugin.com/downloads/<?php echo esc_attr( $mod_info[1] ); ?>/?ref=wp" target="_blank" rel="noopener"><?php echo esc_html( $mod_info[2] ); ?></a>
                    </li>
                <?php endforeach; ?>
                </ul>
                <a href="https://businessdirectoryplugin.com/lite-upgrade/?utm_source=WordPress&utm_campaign=liteplugin&utm_medium=sidebar" target="_blank" rel="noopener" class="button-primary">
                    <?php esc_html_e( 'Upgrade Now', 'business-directory-plugin' ); ?>
                </a>
            </div>
        </div>

        <!-- Themes -->
        <div class="postbox premium-modules">
            <h3 class="hndle"><span><?php echo esc_html_x( 'Get a Directory Theme', 'admin sidebar', 'business-directory-plugin' ); ?></span></h3>
            <div class="inside">
                <ul>
                <?php foreach ( $themes as $mod_info ) : ?>
                    <li class="li_link">
                        <?php
                        if ( isset( $mod_info[2] ) && 'new' === $mod_info[2] ) :
							?>
                            <span class="tag new"><?php esc_html_e( 'new', 'business-directory-plugin' ); ?></span> <?php endif; ?>
                        <a href="http://businessdirectoryplugin.com/downloads/<?php echo esc_attr( $mod_info[0] ); ?>/?ref=wp" target="_blank" rel="noopener"><?php echo esc_html( $mod_info[1] ); ?></a>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- support -->
        <div class="postbox">
            <h3 class="hndle"><span><?php echo esc_html_x( 'Found a bug? Need support?', 'admin sidebar', 'business-directory-plugin' ); ?></span></h3>
            <div class="inside">
                <p>
                    <?php
                    echo str_replace(
                        '<a>',
                        '<a href="https://businessdirectoryplugin.com/get-help/" target="_blank" rel="noopener">',
                        _x( 'If you\'ve found a bug or need support <a>let us know!</a>', 'admin sidebar', 'business-directory-plugin' )
                    );
					?>
                </p>
                <p>
                    &#149; <a href="https://businessdirectoryplugin.com/knowledge-base/" target="_blank" rel="noopener"><?php echo esc_html_x( 'Full plugin documentation', 'admin sidebar', 'business-directory-plugin' ); ?></a><br />
                    &#149; <a href="http://businessdirectoryplugin.com/quick-start-guide/" target="_blank" rel="noopener"><?php echo esc_html_x( 'Quick Start Guide', 'admin sidebar', 'business-directory-plugin' ); ?></a><br />
                    &#149; <a href="http://businessdirectoryplugin.com/video-tutorials/" target="_blank" rel="noopener"><?php echo esc_html_x( 'Video Tutorials', 'admin sidebar', 'business-directory-plugin' ); ?></a>
                </p>
            </div>
        </div>
        <!-- /support -->

    </div>
</div>
