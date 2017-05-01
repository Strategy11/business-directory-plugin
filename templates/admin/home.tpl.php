<?php echo wpbdp_admin_header(); ?>

<div class="wpbdp-note welcome-message">
    <h4><?php printf( _x( 'Welcome to Business Directory Plugin. You are using %s.', 'admin home', 'WPBDM' ), '<span class="version">' . wpbdp_get_version() . '</span>' ); ?></h4>
    <p><?php _ex( 'Thanks for choosing us.  There\'s a lot you probably want to get done, so let\'s jump right in!',
                  'admin home',
                  'WPBDM' ); ?></p>
    <ul>
        <li>
            <?php echo str_replace( '<a>', '<a href="http://businessdirectoryplugin.com/docs" target="_blank">',
                                    _x( 'Our complete documentation is <a>here</a> which we encourage you to use while setting things up.', 'admin home', 'WPBDM' ) ); ?>
        <li>
            <?php echo str_replace( '<a>', '<a href="http://businessdirectoryplugin.com/quick-start-guide/" target="_blank">',
                                    _x( 'We have some quick-start scenarios that you will find useful regarding setup and configuration <a>here</a>.', 'admin home', 'WPBDM' ) ); ?>
        </li>
        <li>
            <?php echo str_replace( '<a>', '<a href="http://businessdirectoryplugin.com/support-forum/" target="_blank">',
                                    _x( 'If you have questions, please post a comment on <a>support forum</a> and we\'ll answer it within 24 hours most days.', 'admin home', 'WPBDM' ) ); ?>

    </ul>
</div>

<ul class="shortcuts">
    <li>
        <a href="<?php echo admin_url( 'admin.php?page=wpbdp_admin_settings' ); ?>" class="button"><?php _e( 'Configure/Manage Options', 'WPBDM' ); ?></a>
    </li>
    <li>
        <a href="<?php echo admin_url( 'admin.php?page=wpbdp_admin_formfields' ); ?>" class="button"><?php _e( 'Setup/Manage Form Fields', 'WPBDM' ); ?></a>
    </li>
    <li>
        <a href="<?php echo admin_url( 'admin.php?page=wpbdp_admin_fees' ); ?>" class="button"><?php echo _e( 'Setup/Manage Fees', 'WPBDM' ); ?></a>
    </li>
    <li class="clear"></li>

    <?php if ( wpbdp_get_option( 'featured-on' ) ): ?>
	<li>
        <a href="<?php echo admin_url( sprintf( 'edit.php?post_type=%s&wpbdmfilter=pendingupgrade', WPBDP_POST_TYPE ) ); ?>" class="button"><?php _e( 'Featured Listings Pending Upgrade', 'WPBDM' ); ?></a>
    </li>
    <?php endif; ?>


    <?php if ( wpbdp_get_option( 'payments-on' ) ): ?>
    <li>
        <a href="<?php echo admin_url( sprintf( 'edit.php?post_type=%s&wpbdmfilter=unpaid', WPBDP_POST_TYPE ) ) ?>" class="button"><?php _e( 'Manage Paid Listings', 'WPBDM' ); ?></a>
    </li>
    <?php endif; ?>
</ul>

<?php echo wpbdp_admin_footer(); ?>
