<?php
$action = isset( $action ) ? $action : '';

$reasons = array(
    '1' => _x( 'It doesn\'t work with my theme/plugins/site', 'uninstall', 'business-directory-plugin' ),
    '2' => _x( 'I can\'t set it up/Too complicated', 'uninstall', 'business-directory-plugin' ),
    '3' => _x( 'Doesn\'t solve my problem', 'uninstall', 'business-directory-plugin' ),
    '4' => _x( 'Don\'t need it anymore/Not using it', 'uninstall', 'business-directory-plugin' ),
    '0' => _x( 'Other', 'uninstall', 'business-directory-plugin' )
);
?>

<form id="wpbdp-uninstall-capture-form" action="<?php echo $action; ?>" method="post">
    <?php wp_nonce_field( 'uninstall bd' ); ?>

    <p><?php _ex( 'We\'re sorry to see you leave. Could you take 10 seconds and answer one question for us to help us make the product better for everyone in the future?',
                  'uninstall',
                  'business-directory-plugin' ); ?></p>
    <p><b><?php _ex( 'Why are you deleting Business Directory Plugin?', 'uninstall', 'business-directory-plugin' ); ?></b></p>

    <div class="wpbdp-validation-error no-reason wpbdp-hidden">
        <?php _ex( 'Please choose an option.', 'uninstall', 'business-directory-plugin' ); ?>
    </div>

    <div class="reasons">
		<?php foreach ( $reasons as $r => $l ) : ?>
        <div class="reason">
            <label>
                <input type="radio" name="uninstall[reason_id]" value="<?php echo $r; ?>" /> <?php echo $l; ?>
            </label>

			<?php if ( 0 == $r ) : ?>
            <div class="custom-reason">
                <textarea name="uninstall[reason_text]" placeholder="<?php _ex( 'Please tell us why are you deleting Business Directory Plugin.', 'uninstall', 'business-directory-plugin' ); ?>"></textarea>

                <div class="wpbdp-validation-error no-reason-text wpbdp-hidden">
                    <?php _ex( 'Please enter your reasons.', 'uninstall', 'business-directory-plugin' ); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <p class="buttons">
        <input type="submit" value="<?php _ex( 'Uninstall Plugin', 'uninstall', 'business-directory-plugin' ); ?>" class="button button-primary" />
    </p>
</form>
