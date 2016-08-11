<?php
$modules = array(
    array( 'discount-codes-module', _x( 'Discount Codes Module', 'admin sidebar', 'WPBDM' ), 'new' ),
    array( 'claim-listings-module', _x( 'Claim Listings Module', 'admin sidebar', 'WPBDM' ), 'new' ),
    array( 'payfast-payment-module', _x( 'PayFast Payment Module', 'admin sidebar', 'WPBDM' ) ),
    array( 'stripe-payment-module', _x( 'Stripe Payment Module', 'admin sidebar', 'WPBDM' ) ),
    array( 'file-attachments-module', _x( 'File Upload Module', 'admin sidebar', 'WPBDM' ) ),
    array( 'featured-levels-module', _x( 'Featured Levels Module', 'admin sidebar', 'WPBDM' ) ),
    array( 'zip-search-module', _x( 'ZIP Code Search Module', 'admin sidebar', 'WPBDM' ) ),
    array( 'regions-module', _x( 'Regions Module', 'admin sidebar', 'WPBDM' ) ),
    array( 'ratings-module', _x( 'Ratings Module', 'admin sidebar', 'WPBDM' ) ),
    array( 'google-maps-module', _x( 'Google Maps Module', 'admin sidebar', 'WPBDM' ) ),
    array( 'paypal-gateway-module', _x( 'PayPal Gateway Module', 'admin sidebar', 'WPBDM' ) ),
    array( '2checkout-gateway-module', _x( '2Checkout Gateway Module', 'admin sidebar', 'WPBDM' ) )
);

$themes = array(
    array( 'business-card-theme', _x( 'Business Card Theme', 'admin sidebar', 'WPBDM' ), 'new' ),
    array( 'mobile-compact-theme', _x( 'Mobile Compact Theme', 'admin sidebar', 'WPBDM' ), 'new' ),
    array( 'restaurant-theme', _x( 'Restaurant Theme', 'admin sidebar', 'WPBDM' ), 'new' ),
    array( 'tabbed-business-theme', _x( 'Tabbed Business Theme', 'admin sidebar', 'WPBDM' ), 'new' ),
    array( 'elegant-business-theme', _x( 'Elegant Business Theme', 'admin sidebar', 'WPBDM' ), 'new' )
);
?>
<div class="sidebar">
    <div class="meta-box-sortables metabox-holder ui-sortable" id="side-sortables">
        <!-- Like this plugin? -->
        <div class="postbox">
            <h3 class="hndle"><span><?php _ex( 'Like this plugin?', 'admin sidebar', 'WPBDM'); ?></span></h3>
            <div class="inside">
                <p><?php _ex( 'Why not do any or all of the following:', 'admin sidebar', 'WPBDM'); ?></p>
                <ul>
                    <li class="li_link"><a href="http://wordpress.org/extend/plugins/business-directory-plugin/"><?php _ex( 'Give it a good rating on WordPress.org.', 'admin sidebar', 'WPBDM'); ?></a></li>
                    <li class="li_link"><a href="http://wordpress.org/extend/plugins/business-directory-plugin/"><?php _ex( 'Let other people know that it works with your WordPress setup.', 'admin sidebar', 'WPBDM'); ?></a></li>
                    <li class="li_link"><a href="http://businessdirectoryplugin.com/premium-modules/"><?php _ex( 'Buy a Premium Module', 'admin sidebar', 'WPBDM'); ?></a></li>
                </ul>
            </div>
        </div>

        <!-- Premium modules -->
        <div class="postbox premium-modules">
            <h3 class="hndle"><span><?php _ex( 'Get a Premium Module', 'admin sidebar', 'WPBDM'); ?></span></h3>
            <div class="inside">
                <ul>
                <li class="li_link"><span class="tag best-deal"><?php _ex( 'best deal', 'admin sidebar', 'WPBDM' ); ?></span> <strong><a href="http://businessdirectoryplugin.com/premium-modules/business-directory-combo-pack/"><?php _ex( 'Combo Pack', 'admin sidebar', 'WPBDM' ); ?></a><br /><?php _ex( '(All Modules)', 'admin sidebar', 'WPBDM' ); ?></strong></li>
                <?php foreach ( $modules as $mod_info ): ?>
                    <li class="li_link">
                        <?php if ( isset( $mod_info[2] ) && 'new' == $mod_info[2] ): ?><span class="tag new"><?php _ex( 'new', 'admin sidebar', 'WPBDM' ); ?></span> <?php endif; ?>
                        <a href="http://businessdirectoryplugin.com/downloads/<?php echo $mod_info[0]; ?>/?ref=wp" target="_blank"><?php echo $mod_info[1]; ?></a>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Themes -->
        <div class="postbox premium-modules">
            <h3 class="hndle"><span><?php _ex( 'Get a Directory Theme', 'admin sidebar', 'WPBDM'); ?></span></h3>
            <div class="inside">
                <ul>
                <li class="li_link"><span class="tag best-deal"><?php _ex( 'best deal', 'admin sidebar', 'WPBDM' ); ?></span> <strong><a href="http://businessdirectoryplugin.com/downloads/business-directory-theme-pack/"><?php _ex( 'Theme Pack', 'admin sidebar', 'WPBDM' ); ?></a><br /><?php _ex( '(All Themes)', 'admin sidebar', 'WPBDM' ); ?></strong></li>
                <?php foreach ( $themes as $mod_info ): ?>
                    <li class="li_link">
                        <?php if ( isset( $mod_info[2] ) && 'new' == $mod_info[2] ): ?><span class="tag new"><?php _ex( 'new', 'admin sidebar', 'WPBDM' ); ?></span> <?php endif; ?>
                        <a href="http://businessdirectoryplugin.com/downloads/<?php echo $mod_info[0]; ?>/?ref=wp" target="_blank"><?php echo $mod_info[1]; ?></a>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- support -->
        <div class="postbox">
            <h3 class="hndle"><span><?php _ex('Found a bug? Need support?', 'admin sidebar', 'WPBDM'); ?></span></h3>
            <div class="inside">
                <p>
                    <?php echo str_replace( '<a>',
                                            '<a href="http://businessdirectoryplugin.com/forums/" target="_blank">',
                                            _x( 'If you\'ve found a bug or need support <a>visit the forums!</a>', 'admin sidebar', 'WPBDM' ) ); ?>
                </p>
                <p>
                    &#149; <a href="http://businessdirectoryplugin.com/docs/" target="_blank"><?php _ex( 'Full plugin documentation', 'admin sidebar', 'WPBDM' ); ?></a><br />
                    &#149; <a href="http://businessdirectoryplugin.com/quick-start-guide/" target="_blank"><?php _ex( 'Quick Start Guide', 'admin sidebar', 'WPBDM' ); ?></a><br />
                    &#149; <a href="http://businessdirectoryplugin.com/video-tutorials/" target="_blank"><?php _ex( 'Video Tutorials', 'admin sidebar', 'WPBDM' ); ?></a>
                </p>
            </div>
        </div>
        <!-- /support -->

        <!-- Installed modules -->
        <div class="postbox installed-modules">
        <h3 class="hndle"><span><?php _ex( 'Installed Modules', 'admin sidebar', 'WPBDM' ); ?></span></h3>
            <div class="inside">
                <ul>
                <?php
                global $wpbdp;
                foreach ( $modules as $mod_info ):
                ?>
                    <li class="li_link">
                        <a href="http://businessdirectoryplugin.com/downloads/<?php echo $mod_info[0]; ?>/?ref=wp" target="_blank"><?php echo $mod_info[1]; ?></a>:<br />
                        <?php
                        if ( $wpbdp->has_module( $mod_info[0] ) ):
                            echo _x( 'Installed', 'admin sidebar', 'WPBDM' );
                        else:
                            echo _x( 'Not Installed', 'admin sidebar', 'WPBDM' );
                        endif;
                        ?>
                    </li>
                <?php endforeach; ?>
                    <li class="li_link">
                        <a href="http://businessdirectoryplugin.com/"><?php _ex('Enhanced Categories Module', 'admin sidebar', 'WPBDM'); ?></a>:<br />
                        <?php echo $wpbdp->has_module('categories') ? _x('Installed', 'admin sidebar', 'WPBDM') : _x('Not Installed', 'admin sidebar', 'WPBDM'); ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
