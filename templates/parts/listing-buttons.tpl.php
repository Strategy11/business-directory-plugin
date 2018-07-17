<?php
/**
 * Listing Buttons template
 *
 * @package BDP/Templates/parts/Listing Buttons
 */

// phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
// phpcs:disable WordPress.XSS.EscapeOutput.UnsafePrintingFunction
?>
<div class="listing-actions cf">
<?php if ( 'single' === $view ) : ?>
    <?php if ( wpbdp_user_can( 'edit', $listing_id ) ) : ?>
    <form action="<?php echo wpbdp_url( 'edit_listing', $listing_id ); ?>" method="post"><input type="submit" name="" value="<?php _ex( 'Edit', 'templates', 'WPBDM' ); ?>" class="button wpbdp-button edit-listing" /></form>
    <?php endif; ?>
    <?php if ( wpbdp_get_option( 'enable-listing-flagging' ) && wpbdp_user_can( 'flagging', $listing_id ) ) : ?>
    <form action="<?php echo wpbdp_url( 'flag_listing', $listing_id ); ?>" method="post"><input type="submit" name="" value="<?php echo apply_filters( 'wpbdp_listing_flagging_button_text', _x( 'Flag Listing', 'templates', 'WPBDM' ) ); ?>" class="button wpbdp-button report-listing" /></form>
    <?php endif; ?>
    <?php if ( wpbdp_user_can( 'delete', $listing_id ) ) : ?>
    <form action="<?php echo wpbdp_url( 'delete_listing', $listing_id ); ?>" method="post"><input type="submit" name="" value="<?php _ex( 'Delete', 'templates', 'WPBDM' ); ?>" class="button wpbdp-button delete-listing" data-confirmation-message="<?php _ex( 'Are you sure you wish to delete this listing?', 'templates', 'WPBDM' ); ?>" /></form>
    <?php endif; ?>
    <?php if ( wpbdp_get_option( 'show-directory-button' ) ) : ?>
        <input type="button" value="<?php echo __( '← Return to Directory', 'WPBDM' ); ?>" onclick="window.location.href = '<?php echo wpbdp_url( '/' ); ?>'" class="wpbdp-hide-on-mobile button back-to-dir wpbdp-button" />
        <input type="button" value="←" onclick="window.location.href = '<?php echo wpbdp_url( '/' ); ?>'" class="wpbdp-show-on-mobile button back-to-dir wpbdp-button" />
    <?php endif; ?>
<?php elseif ( 'excerpt' === $view ) : ?>
    <?php if ( wpbdp_user_can( 'view', $listing_id ) ) : ?>
        <a class="wpbdp-button button view-listing" href="<?php the_permalink(); ?>" <?php wpbdp_get_option( 'listing-link-in-new-tab' ) ? esc_html( 'target="_blank" rel="noopener"' ) : null; ?>>
            <?php _ex( 'View', 'templates', 'WPBDM' ); ?>
        </a>
    <?php endif; ?>
    <?php if ( wpbdp_user_can( 'edit', $listing_id ) ) : ?>
        <a class="wpbdp-button button edit-listing" href="<?php echo wpbdp_url( 'edit_listing', $listing_id ); ?>">
            <?php _ex( 'Edit', 'templates', 'WPBDM' ); ?>
        </a>
    <?php endif; ?>
    <?php if ( wpbdp_get_option( 'enable-listing-flagging' ) && wpbdp_user_can( 'flagging', $listing_id ) ) : ?>
        <a class="wpbdp-button button report-listing" href="<?php echo esc_url( wpbdp_url( 'flag_listing', $listing_id ) ); ?>">
            <?php echo apply_filters( 'wpbdp_listing_flagging_button_text', _x( 'Flag Listing', 'templates', 'WPBDM' ) ); ?>
        </a>
    <?php endif; ?>
    <?php if ( wpbdp_user_can( 'delete', $listing_id ) ) : ?>
        <a class="wpbdp-button button delete-listing" href="<?php echo wpbdp_url( 'delete_listing', $listing_id ); ?>">
            <?php _ex( 'Delete', 'templates', 'WPBDM' ); ?>
        </a>
    <?php endif; ?>
<?php endif; ?>
</div>
