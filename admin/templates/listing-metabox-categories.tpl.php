<?php
/**
 * Listing category (general & fee information) display.
 * @since 3.4
 * @param $categories array The categories (from {@link WPBDP_Listing::get_categories()}).
 * @param $display array() Optional.
 * @param $admin_actions array() Optional.
 */

$image_count = count( $listing->get_images( 'ids' ) );

if ( ! isset( $display ) )
    $display = array( 'fee label', 'fee images', 'expiration' );

if ( ! isset( $admin_actions ) )
    $admin_actions = array( 'delete', 'renewal url', 'renewal email', 'change fee' );
?>

<div class="listing-categories">

<?php if ( ! $categories ): ?>
<p><?php _ex( 'No categories on this listing. Please add one to associate fees.', 'admin infometabox', 'WPBDM' ); ?></p>
<?php else: ?>
<?php foreach ( $categories as &$category ): ?>
<div class="listing-category <?php echo $category->expired ? 'expired' : ''; ?> listing-category-<?php echo $category->id; ?>">

<div class="header">
    <?php if ( current_user_can( 'administrator' ) ): ?><span class="spinner"></span><?php endif; ?>
    <span class="category-name"><?php echo $category->name; ?> <?php echo $category->recurring ? ' ' . _x( '(recurring)', 'admin infometabox', 'WPBDM' ) : ''; ?></span>
    <span class="tag category-status <?php echo $category->status; ?>">
        <?php
        switch ( $category->status ):
            case 'expired':
                _ex( 'Expired', 'admin infometabox', 'WPBDM' );
                break;
            case 'pending':
                _ex( 'Payment Pending', 'admin infometabox', 'WPBDM' );
                break;
            case 'ok':
            default:
                _ex( 'OK', 'admin infometabox', 'WPBDM');
        endswitch;
        ?>
    </span>
</div>
<div class="category-details">
    <dl>
        <?php if ( in_array( 'fee label', $display, true ) ): ?>
            <dt><?php _ex('Fee', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $category->fee->label; ?></dd>
        <?php endif; ?>

        <?php if ( in_array( 'fee images', $display, true ) ): ?>
        <dt><?php _ex('# Images', 'admin infometabox', 'WPBDM'); ?></dt>
        <dd><?php echo min( $image_count, $category->fee_images); ?> / <?php echo $category->fee_images; ?></dd>
        <?php endif; // ?>

        <?php if ( in_array( 'expiration', $display, true ) ): ?>
            <?php if ( 'pending' != $category->status ): ?>
            <dt>
                <?php if ( $category->expired ): ?>
                    <?php _ex('Expired on', 'admin infometabox', 'WPBDM'); ?>
                <?php else: ?>
                    <?php _ex('Expires on', 'admin infometabox', 'WPBDM'); ?>
                <?php endif; ?> 
            </dt>
            <dd class="expiration-date-info">
                <span class="expiration-date">
                <?php echo $category->expires_on ? date_i18n( get_option( 'date_format' ), strtotime( $category->expires_on ) ) : _x( 'never', 'admin infometabox', 'WPBDM' ); ?>
                </span>
                <?php if ( current_user_can( 'administrator' ) ): ?>
                    <a href="#" class="expiration-change-link" title="<?php _ex( 'Click to manually change expiration date.', 'admin infometabox', 'WPBDM' ); ?>"
                       data-renewal_id="<?php echo $category->renewal_id; ?>"
                       data-date="<?php echo $category->expires_on ? date('Y-m-d', strtotime( $category->expires_on ) ) : date( 'Y-m-d', strtotime( '+10 years' ) ); ?>"
                       data-never-text="<?php _ex( 'Never expires', 'admin infometabox', 'WPBDM' ); ?>"><?php _ex( 'Edit', 'admin infometabox', 'WPBDM' ); ?></a>
                    <div class="datepicker renewal-<?php echo $category->renewal_id; ?>"></div>
                <?php endif; ?>
            </dd>
            <?php endif; ?>
        <?php endif; ?>
    </dl>
</div>

<?php if ( $admin_actions && current_user_can( 'administrator' ) ): ?>
<ul class="admin-actions">
    <?php if ( 'pending' == $category->status ) : ?>
        <li><a href="#" class="payment-details-link" data-id="<?php echo $category->payment_id; ?>"><?php _ex( 'See payment info', 'admin infometabox', 'WPBDM' ); ?></a></li>
    <?php else: ?>

    <?php if ( in_array( 'renewal url', $admin_actions, true ) ): ?>
        <li>
            <a href="#" onclick="window.prompt('<?php _ex( 'Renewal URL (copy & paste)', 'admin infometabox', 'WPBDM' ); ?>', '<?php echo $listing->get_renewal_url( $category->id ); ?>'); return false;"><?php _ex( 'Show renewal link', 'admin infometabox', 'WPBDM' ); ?></a>
        </li>
    <?php endif; ?>

    <?php if ( in_array( 'renewal email', $admin_actions, true ) ): ?>
        <li>
            <a href="<?php echo esc_url( add_query_arg( array( 'wpbdmaction' => 'send-renewal-email', 'renewal_id' => $category->renewal_id ) ) ); ?>">
                <?php _ex( 'Send renewal e-mail to user', 'admin infometabox', 'WPBDM' ); ?>
            </a>
        </li>
    <?php endif; ?>

    <?php if ( in_array( 'change fee', $admin_actions, true ) ): ?>
        <li>
            <a href="#" data-renewal="<?php echo $category->renewal_id; ?>" class="category-change-fee">
                <?php ( $category->expired ? _ex( 'Renew manually...', 'admin infometabox', 'WPBDM' ) : _ex('Change fee...', 'admin infometabox', 'WPBDM') ); ?>
            </a>
        </li>
    <?php endif; ?>
    
    <?php if ( in_array( 'delete', $admin_actions, true ) ): ?>
        <li class="delete">
            <a href="#" data-listing="<?php echo $listing->get_id(); ?>" data-category="<?php echo $category->id; ?>" class="category-delete">
                <?php _ex('Remove category', 'admin infometabox', 'WPBDM'); ?>
            </a>            
        </li>
    <?php endif; ?>    

    <?php endif; ?>
</ul>
<?php endif; ?>

</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
