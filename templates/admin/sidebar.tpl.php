<?php
$modules = array(
    array( 'discount-codes', 'discount-codes-module', _x( 'Discount Codes Module', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'claim-listings', 'claim-listings-module', _x( 'Claim Listings Module', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'payfast', 'payfast-payment-module', _x( 'PayFast Payment Module', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'stripe', 'stripe-payment-module', _x( 'Stripe Payment Module', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'attachments', 'file-attachments-module', _x( 'File Upload Module', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'featured-levels', 'featured-levels-module', _x( 'Featured Levels Module', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'zipcodesearch', 'zip-search-module', _x( 'ZIP Code Search Module', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'regions', 'regions-module', _x( 'Regions Module', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'ratings', 'ratings-module', _x( 'Ratings Module', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'googlemaps', 'google-maps-module', _x( 'Google Maps Module', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'paypal', 'paypal-gateway-module', _x( 'PayPal Gateway Module', 'admin sidebar', 'business-directory-plugin' ) ),
    array( '2checkout', '2checkout-gateway-module', _x( '2Checkout Gateway Module', 'admin sidebar', 'business-directory-plugin' ) ),
);

$themes = array(
    array( 'business-card-theme', _x( 'Business Card Theme', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'mobile-compact-theme', _x( 'Mobile Compact Theme', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'restaurant-theme', _x( 'Restaurant Theme', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'tabbed-business-theme', _x( 'Tabbed Business Theme', 'admin sidebar', 'business-directory-plugin' ) ),
    array( 'elegant-business-theme', _x( 'Elegant Business Theme', 'admin sidebar', 'business-directory-plugin' ) ),
);
?>
<div class="sidebar">
    <div class="meta-box-sortables metabox-holder ui-sortable" id="side-sortables">
        <!-- Like this plugin? -->
        <div class="postbox">
            <h3 class="hndle"><span><?php echo esc_html_x( 'Like this plugin?', 'admin sidebar', 'business-directory-plugin' ); ?></span></h3>
            <div class="inside">
                <p><?php echo esc_html_x( 'Why not do any or all of the following:', 'admin sidebar', 'business-directory-plugin' ); ?></p>
                <ul>
                    <li class="li_link"><a href="http://wordpress.org/extend/plugins/business-directory-plugin/"><?php echo esc_html_x( 'Give it a good rating on WordPress.org.', 'admin sidebar', 'business-directory-plugin' ); ?></a></li>
                    <li class="li_link"><a href="http://wordpress.org/extend/plugins/business-directory-plugin/"><?php echo esc_html_x( 'Let other people know that it works with your WordPress setup.', 'admin sidebar', 'business-directory-plugin' ); ?></a></li>
                    <li class="li_link"><a href="http://businessdirectoryplugin.com/premium-modules/"><?php echo esc_html_x( 'Buy a Premium Module', 'admin sidebar', 'business-directory-plugin' ); ?></a></li>
                </ul>
            </div>
        </div>

        <!-- Premium modules -->
        <div class="postbox premium-modules">
            <h3 class="hndle"><span><?php echo esc_html_x( 'Get a Premium Module', 'admin sidebar', 'business-directory-plugin' ); ?></span></h3>
            <div class="inside">
                <ul>
                <li class="li_link"><span class="tag best-deal"><?php echo esc_html_x( 'best deal', 'admin sidebar', 'business-directory-plugin' ); ?></span> <strong><a href="http://businessdirectoryplugin.com/premium-modules/business-directory-combo-pack/"><?php echo esc_html_x( 'Combo Pack', 'admin sidebar', 'business-directory-plugin' ); ?></a><br /><?php echo esc_html_x( '(All Modules)', 'admin sidebar', 'business-directory-plugin' ); ?></strong></li>
                <?php foreach ( $modules as $mod_info ) : ?>
                    <li class="li_link">
                        <?php
                        if ( isset( $mod_info[3] ) && 'new' === $mod_info[3] ) :
							?>
                            <span class="tag new"><?php echo esc_html_x( 'new', 'admin sidebar', 'business-directory-plugin' ); ?></span> <?php endif; ?>
                        <a href="http://businessdirectoryplugin.com/downloads/<?php echo esc_attr( $mod_info[1] ); ?>/?ref=wp" target="_blank" rel="noopener"><?php echo esc_html( $mod_info[2] ); ?></a>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Themes -->
        <div class="postbox premium-modules">
            <h3 class="hndle"><span><?php echo esc_html_x( 'Get a Directory Theme', 'admin sidebar', 'business-directory-plugin' ); ?></span></h3>
            <div class="inside">
                <ul>
                <li class="li_link"><span class="tag best-deal"><?php echo esc_html_x( 'best deal', 'admin sidebar', 'business-directory-plugin' ); ?></span> <strong><a href="http://businessdirectoryplugin.com/downloads/business-directory-theme-pack/"><?php echo esc_html_x( 'Theme Pack', 'admin sidebar', 'business-directory-plugin' ); ?></a><br /><?php echo esc_html_x( '(All Themes)', 'admin sidebar', 'business-directory-plugin' ); ?></strong></li>
                <?php foreach ( $themes as $mod_info ) : ?>
                    <li class="li_link">
                        <?php
                        if ( isset( $mod_info[2] ) && 'new' === $mod_info[2] ) :
							?>
                            <span class="tag new"><?php echo esc_html_x( 'new', 'admin sidebar', 'business-directory-plugin' ); ?></span> <?php endif; ?>
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
                        '<a href="http://businessdirectoryplugin.com/forums/" target="_blank" rel="noopener">',
                        _x( 'If you\'ve found a bug or need support <a>visit the forums!</a>', 'admin sidebar', 'business-directory-plugin' )
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

        <!-- Installed modules -->
        <div class="postbox installed-modules">
        <h3 class="hndle"><span><?php echo esc_html_x( 'Installed Modules', 'admin sidebar', 'business-directory-plugin' ); ?></span></h3>
            <div class="inside">
                <ul>
                <?php
                global $wpbdp;
                foreach ( $modules as $mod_info ) :
					?>
                    <li class="li_link">
                        <a href="http://businessdirectoryplugin.com/downloads/<?php echo esc_attr( $mod_info[1] ); ?>/?ref=wp" target="_blank" rel="noopener"><?php echo esc_html( $mod_info[2] ); ?></a>:<br />
                        <?php
                        if ( wpbdp_has_module( $mod_info[0] ) ) :
                            echo esc_html_x( 'Installed', 'admin sidebar', 'business-directory-plugin' );
                        else :
                            echo esc_html_x( 'Not Installed', 'admin sidebar', 'business-directory-plugin' );
                        endif;
                        ?>
                    </li>
                <?php endforeach; ?>
                    <li class="li_link">
                        <a href="http://businessdirectoryplugin.com/"><?php echo esc_html_x( 'Enhanced Categories Module', 'admin sidebar', 'business-directory-plugin' ); ?></a>:<br />
                        <?php echo wpbdp_has_module( 'categories' ) ? esc_html_x( 'Installed', 'admin sidebar', 'business-directory-plugin' ) : esc_html_x( 'Not Installed', 'admin sidebar', 'business-directory-plugin' ); ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
