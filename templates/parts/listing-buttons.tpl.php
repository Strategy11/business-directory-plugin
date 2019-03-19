<?php
/**
 * Listing Buttons template
 *
 * @package BDP/Templates/parts/Listing Buttons
 */

// phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
// phpcs:disable WordPress.XSS.EscapeOutput.UnsafePrintingFunction
$buttons = '';
?>
<div class="listing-actions cf">
<?php
if ( 'single' === $view ) :
    if ( wpbdp_user_can( 'edit', $listing_id ) ) :
		$buttons .= sprintf(
            '<form action="%s" method="post"><input type="submit" name="" value="%s" class="button wpbdp-button edit-listing" /></form>',
            wpbdp_url( 'edit_listing', $listing_id ),
            _x( 'Edit', 'templates', 'WPBDM' )
		);
    endif;

    if ( wpbdp_get_option( 'enable-listing-flagging' ) && wpbdp_user_can( 'flagging', $listing_id ) ) :
        $buttons .= sprintf(
            ' <form action="%s" method="post"><input type="submit" name="" value="%s" class="button wpbdp-button report-listing" /></form>',
            wpbdp_url( 'flag_listing', $listing_id ),
            apply_filters( 'wpbdp_listing_flagging_button_text', _x( 'Flag Listing', 'templates', 'WPBDM' ) )
        );
    endif;

    if ( wpbdp_user_can( 'delete', $listing_id ) ) :
        $buttons .= sprintf(
            '<form action="%s" method="post"><input type="submit" name="" value="%s" class="button wpbdp-button delete-listing" data-confirmation-message="%s" /></form>',
            wpbdp_url( 'delete_listing', $listing_id ),
            _x( 'Delete', 'templates', 'WPBDM' ),
            _x( 'Are you sure you wish to delete this listing?', 'templates', 'WPBDM' )
        );
    endif;

    if ( wpbdp_get_option( 'show-directory-button' ) ) :
        $buttons .= sprintf(
            '<div style="display: inline;" class="back-to-dir-buttons"><input type="button" value="%1$s" onclick="window.location.href = \'%2$s\'" class="wpbdp-hide-on-mobile button back-to-dir wpbdp-button" /><input type="button" value="←" onclick="window.location.href = \'%2$s\'" class="wpbdp-show-on-mobile button back-to-dir wpbdp-button" /></div>',
            __( '← Return to Directory', 'WPBDM' ),
            wpbdp_url( '/' )
        );
    endif;
    ?>
<?php elseif ( 'excerpt' === $view ) : ?>
    <?php
    if ( wpbdp_user_can( 'view', $listing_id ) ) :
        $buttons .= sprintf(
            '<a class="wpbdp-button button view-listing" href="%s" %s >%s</a>',
            get_permalink(),
            wpbdp_get_option( 'listing-link-in-new-tab' ) ? esc_html( 'target="_blank" rel="noopener"' ) : null,
            _x( 'View', 'templates', 'WPBDM' )
        );
    endif;

    if ( wpbdp_user_can( 'edit', $listing_id ) ) :
        $buttons .= sprintf(
            '<a class="wpbdp-button button edit-listing" href="%s">%s</a>',
            wpbdp_url( 'edit_listing', $listing_id ),
            _x( 'Edit', 'templates', 'WPBDM' )
        );
    endif;

    if ( wpbdp_get_option( 'enable-listing-flagging' ) && wpbdp_user_can( 'flagging', $listing_id ) ) :
        $buttons .= sprintf(
            '<a class="wpbdp-button button report-listing" href="%s">%s</a>',
            esc_url( wpbdp_url( 'flag_listing', $listing_id ) ),
            apply_filters( 'wpbdp_listing_flagging_button_text', _x( 'Flag Listing', 'templates', 'WPBDM' ) )
        );
    endif;

    if ( wpbdp_user_can( 'delete', $listing_id ) ) :
        $buttons .= sprintf(
            '<a class="wpbdp-button button delete-listing" href="%s">%s</a>',
            wpbdp_url( 'delete_listing', $listing_id ),
            _x( 'Delete', 'templates', 'WPBDM' )
        );
    endif;
    ?>
<?php endif; ?>
<?php echo $buttons; ?>
</div>
