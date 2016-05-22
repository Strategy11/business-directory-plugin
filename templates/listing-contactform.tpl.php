<?php if ($validation_errors): ?>
    <ul class="validation-errors">
        <?php foreach($validation_errors as $error_msg): ?>
            <li><?php echo $error_msg; ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<p><label><?php _ex('Listing Title: ', 'templates', 'WPBDM'); ?></label><?php echo get_the_title($listing_id); ?></p>

<form method="POST" action="<?php echo wpbdp_url( 'listing_contact', $listing_id ); ?>">
    <?php wp_nonce_field( 'contact-form-' . $listing_id ); ?>

    <?php if ($current_user): ?>
        <p>
            <?php echo sprintf(_x('You are currently logged in as %s. Your message will be sent using your logged in contact email.', 'templates', 'WPBDM'),
                               $current_user->user_login); ?>
        </p>
    <?php else: ?>
            <p>
               <label><?php _ex('Your Name', 'templates', 'WPBDM'); ?></label>
               <input type="text" class="intextbox" name="commentauthorname" value="<?php echo esc_attr(wpbdp_getv($_POST, 'commentauthorname', '')); ?>" />
            </p>
            <p>
                <label><?php _ex("Your Email", 'templates', "WPBDM"); ?></label>
                <input type="text" class="intextbox" name="commentauthoremail" value="<?php echo esc_attr(wpbdp_getv($_POST, 'commentauthoremail')); ?>" />
            </p>
    <?php endif; ?>

    <p><label><?php _ex("Message", 'templates', "WPBDM"); ?></label><br/>
       <textarea id="wpbdp-contact-form-message" name="commentauthormessage" rows="4" class="intextarea"><?php echo esc_textarea(wpbdp_getv($_POST, 'commentauthormessage', '')); ?></textarea>
    </p>

    <?php if ($recaptcha): ?>
    <?php echo $recaptcha; ?>
    <?php endif; ?> 

    <input type="submit" class="wpbdp-button wpbdp-submit submit" value="<?php _ex('Send', 'templates', 'WPBDM'); ?>" />
</form>
