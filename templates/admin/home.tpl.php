<?php
/**
 * BD home template
 *
 * @package WPBDP/Templates/Admin/Home
 */

// phpcs:disable

echo wpbdp_admin_header();
?>

<div class="wpbdp-note welcome-message">
    <h4><?php printf( _x( 'Welcome to Business Directory Plugin. You are using %s.', 'admin home', 'business-directory-plugin' ), '<span class="version">' . wpbdp_get_version() . '</span>' ); ?></h4>
    <p>
    <?php
    _ex(
        'Thanks for choosing us.  There\'s a lot you probably want to get done, so let\'s jump right in!',
        'admin home',
        'business-directory-plugin'
    );
					?>
    </p>
    <ul>
        <li>
            <?php
            echo str_replace(
                '<a>', '<a href="https://businessdirectoryplugin.com/knowledge-base/" target="_blank" rel="noopener">',
                _x( 'Our complete documentation is <a>here</a> which we encourage you to use while setting things up.', 'admin home', 'business-directory-plugin' )
            );
                                    ?>
        <li>
            <?php
            echo str_replace(
                '<a>', '<a href="https://businessdirectoryplugin.com/article-categories/getting-started/" target="_blank" rel="noopener">',
                _x( 'We have some quick-start scenarios that you will find useful regarding setup and configuration <a>here</a>.', 'admin home', 'business-directory-plugin' )
            );
                                    ?>
        </li>
        <li>
            <?php
            echo str_replace(
                '<a>', '<a href="http://businessdirectoryplugin.com/support-forum/" target="_blank" rel="noopener">',
                _x( 'If you have questions, please post a comment on <a>support forum</a> and we\'ll answer it within 24 hours most days.', 'admin home', 'business-directory-plugin' )
            );
                                    ?>
        </li>
    </ul>
</div>

<ul class="shortcuts">
    <li>
        <a href="<?php echo admin_url( 'edit.php?post_type=wpbdp_listing&page=wpbdp_settings' ); ?>" class="button"><?php _e( 'Manage Options', 'business-directory-plugin' ); ?></a>
    </li>
    <li>
        <a href="<?php echo admin_url( 'edit.php?post_type=wpbdp_listing&page=wpbdp_admin_formfields' ); ?>" class="button"><?php _e( 'Manage Form Fields', 'business-directory-plugin' ); ?></a>
    </li>
    <li>
        <a href="<?php echo admin_url( 'edit.php?post_type=wpbdp_listing&page=wpbdp-admin-fees' ); ?>" class="button"><?php _e( 'Manage Fees', 'business-directory-plugin' ); ?></a>
    </li>
    <li class="clear"></li>


    <?php if ( wpbdp_get_option( 'payments-on' ) ) : ?>
    <li>
        <a href="<?php echo admin_url( sprintf( 'edit.php?post_type=%s&wpbdmfilter=unpaid', WPBDP_POST_TYPE ) ); ?>" class="button"><?php _e( 'Manage Paid Listings', 'business-directory-plugin' ); ?></a>
    </li>
    <?php endif; ?>
</ul>

<?php echo wpbdp_admin_footer(); ?>
