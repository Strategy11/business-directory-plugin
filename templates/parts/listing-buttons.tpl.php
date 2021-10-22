<?php
/**
 * Listing Buttons template
 *
 * @package BDP/Templates/parts/Listing Buttons
 */

$buttons = '';
?>
<div class="listing-actions cf">
<?php
if ( 'single' === $view ) :
    if ( wpbdp_user_can( 'edit', $listing_id ) ) :
		$buttons .= sprintf(
            '<a href="%s" class="wpbdp-button button edit-listing" rel="nofollow">%s</a>',
            wpbdp_url( 'edit_listing', $listing_id ),
            _x( 'Edit', 'templates', 'business-directory-plugin' )
		);
    endif;

    if ( wpbdp_get_option( 'enable-listing-flagging' ) && wpbdp_user_can( 'flagging', $listing_id ) ) :
        $buttons .= sprintf(
			' <a href="%s" class="wpbdp-button button report-listing" rel="nofollow">%s</a>',
			esc_url( wpbdp_url( 'flag_listing', $listing_id ) ),
            apply_filters( 'wpbdp_listing_flagging_button_text', _x( 'Flag Listing', 'templates', 'business-directory-plugin' ) )
        );
    endif;

    if ( wpbdp_user_can( 'delete', $listing_id ) ) :
        $buttons .= sprintf(
            '<a href="%s" class="delete-listing" rel="nofollow">%s</a>',
            wpbdp_url( 'delete_listing', $listing_id ),
            esc_html__( 'Delete', 'business-directory-plugin' )
        );
    endif;

    if ( wpbdp_get_option( 'show-directory-button' ) ) :
        $buttons .= sprintf(
            '<span class="back-to-dir-buttons"><a href="%2$s" class="back-to-dir">%1$s</a></span>',
            esc_html__( 'Return to Directory', 'business-directory-plugin' ),
            esc_url( wpbdp_url( '/' ) )
        );
    endif;

elseif ( 'excerpt' === $view ) :

    if ( wpbdp_user_can( 'edit', $listing_id ) ) :
        $buttons .= sprintf(
            '<a class="wpbdp-button button edit-listing" href="%s" rel="nofollow">%s</a>',
            wpbdp_url( 'edit_listing', $listing_id ),
            _x( 'Edit', 'templates', 'business-directory-plugin' )
        );
    endif;

    if ( wpbdp_get_option( 'enable-listing-flagging' ) && wpbdp_user_can( 'flagging', $listing_id ) ) :
        $buttons .= sprintf(
            '<a class="wpbdp-button button report-listing" href="%s" rel="nofollow">%s</a>',
            esc_url( wpbdp_url( 'flag_listing', $listing_id ) ),
            apply_filters( 'wpbdp_listing_flagging_button_text', _x( 'Flag Listing', 'templates', 'business-directory-plugin' ) )
        );
    endif;

    if ( wpbdp_user_can( 'delete', $listing_id ) ) :
        $buttons .= sprintf(
            '<a class="wpbdp-button button delete-listing" href="%s" rel="nofollow">%s</a>',
            wpbdp_url( 'delete_listing', $listing_id ),
            esc_html__( 'Delete', 'business-directory-plugin' )
        );
    endif;
endif;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo apply_filters( 'wpbdp-listing-buttons', $buttons, $listing_id );
?>
</div>
