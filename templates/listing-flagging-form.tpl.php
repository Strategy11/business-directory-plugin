<?php
$user_flagged = WPBDP__Listing_Flagging::user_has_flagged( $listing->get_id(), get_current_user_id() );
$flagging_text = _x( 'Report Listing', 'templates', 'WPBDM');
?>

<div id="wpbdp-listing-flagging-page">
    <h3><?php echo $flagging_text; ?></h3>

    <form class="confirm-form" action="" method="post">
        <?php wp_nonce_field( 'flag listing report ' . $listing->get_id() ); ?>

        <?php if ( false === $user_flagged ) : ?>
            <?php if ($current_user): ?>
                <p>
                    <?php echo sprintf( _x( 'You are about to report the listing "<b>%s</b>" as inappropriate. ', 'flag listing', 'WPBDM' ), $listing->get_title() ); ?>
                </p>
                <p>
                    <?php echo sprintf( _x( 'You are currently logged in as %s. Listing report will be sent using your logged in contact email.', 'flag listing', 'WPBDM'), $current_user->user_login ); ?>
                </p>
            <?php else: ?>
                <p>
                    <label><?php _ex('Your Name', 'templates', 'WPBDM'); ?></label>
                    <input type="text" class="intextbox" name="reportauthorname" value="<?php echo esc_attr( wpbdp_getv( $_POST, 'commentauthorname', '' ) ); ?>" />
                </p>
                <p>
                    <label><?php _ex("Your Email", 'templates', "WPBDM"); ?></label>
                    <input type="text" class="intextbox" name="reportauthoremail" value="<?php echo esc_attr( wpbdp_getv($_POST, 'commentauthoremail' ) ); ?>" />
                </p>
            <?php endif; ?>

            <?php if ( $flagging_options = WPBDP__Listing_Flagging::get_flagging_options() ): ?>
                <p><?php _ex( 'Please select the reason to report this listing:', 'flag listing', 'WPBDM' ); ?></p>

                <div class="wpbdp-listing-flagging-options">
                    <?php foreach ( $flagging_options as $option ) : ?>
                        <p><label><input type="radio" name="flagging_option" value="<?php echo esc_attr( $option ); ?>" required> <span><?php echo esc_html( $option ); ?></span></label></p>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php _ex( 'Please enter the reasons to report this listing:', 'flag listing', 'WPBDM' ); ?></p>
            <?php endif; ?>

            <textarea name="flagging_more_info" value="" placeholder="<?php _ex( 'Additional info.', 'flag listing', 'WPBDM' ); ?>" <?php echo $flagging_options ? '' : 'required' ?>></textarea>
            
            <?php echo $recaptcha; ?>

            <p>
                <input type="button" onclick="location.href = '<?php echo wpbdp_url( 'main' ); ?>'; return false;" value="<?php _ex( 'Cancel', 'flag listing', 'WPBDM' ); ?>" class="wpbdp-button button" />
                <input class="wpbdp-submit wpbdp-button" type="submit" value="<?php echo esc_attr( $flagging_text ); ?>" />
            </p>
        <?php else: ?>
            <?php printf( _x( 'You already reported the listing "<b>%s</b>" as inappropriate.', 'flag listing', 'WPBDM' ), $listing->get_title() ); ?>
            <p>
                Return to <a href="<?php echo $listing->get_permalink(); ?>">listing</a>.
            </p>
        <?php endif; ?>

    </form>
</div>
