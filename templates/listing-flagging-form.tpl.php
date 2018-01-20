<?php
$user_flagged = WPBDP__Listing_Flagging::user_has_flagged( $listing->get_id(), get_current_user_id() );
$flagging_text = false !== $user_flagged ? _x( 'Unreport Listing', 'templates', 'WPBDM') : _x( 'Report Listing', 'templates', 'WPBDM');
?>

<div id="wpbdp-listing-flagging-page">
    <h3><?php echo $flagging_text; ?></h3>

    <form class="confirm-form" action="" method="post">
        <?php wp_nonce_field( 'flag listing report ' . $listing->get_id() ); ?>

        <p>
            <?php if ( false === $user_flagged ): ?>
                <?php printf( _x( 'You are about to report the listing "<b>%s</b>" as inappropriate.', 'flag listing', 'WPBDM' ), $listing->get_title() ); ?>
            <?php else: ?>
                <?php printf( _x( 'You are about to unreport the listing "<b>%s</b>" as inappropriate.', 'flag listing', 'WPBDM' ), $listing->get_title() ); ?>
            <?php endif; ?>
        </p>

        <?php if ( false === $user_flagged ) : ?>
            <?php if ( $flagging_options = WPBDP__Listing_Flagging::get_flagging_options() ): ?>
                <p><?php _ex( 'Please select the reasons to report this listing:', 'flag listing', 'WPBDM' ); ?></p>

                <div class="wpbdp-listing-flagging-options">
                    <?php foreach ( $flagging_options as $option ) : ?>
                        <p><label><input type="radio" name="flagging_option" value="<?php echo esc_attr( $option ); ?>"/> <span><?php echo esc_html( $option ); ?></span></label></p>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php _ex( 'Please enter the reasons to report this listing:', 'flag listing', 'WPBDM' ); ?></p>
            <?php endif; ?>

            <textarea name="flagging_more_info" value="" placeholder="<?php _ex( 'Additional info.', 'flag listing', 'WPBDM' ); ?>" <?php echo $flagging_options ? '' : 'required' ?>></textarea>
            
            <?php echo $recaptcha; ?>
        <?php endif; ?>

        <p>
            <input type="button" onclick="location.href = '<?php echo wpbdp_url( 'main' ); ?>'; return false;" value="<?php _ex( 'Cancel', 'flag listing', 'WPBDM' ); ?>" class="wpbdp-button button" />
            <input class="wpbdp-submit wpbdp-button" type="submit" value="<?php echo esc_attr( $flagging_text ); ?>" />
        </p>
    </form>
</div>
