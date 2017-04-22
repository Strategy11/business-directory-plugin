<?php
$action = isset( $action ) ? $action : '';

$reasons = array(
    '1' => _x( 'It doesn\'t work with my theme/plugins/site', 'uninstall', 'WPBDM' ),
    '2' => _x( 'I can\'t set it up/Too complicated', 'uninstall', 'WPBDM' ),
    '3' => _x( 'Doesn\'t solve my problem', 'uninstall', 'WPBDM' ),
    '4' => _x( 'Don\'t need it anymore/Not using it', 'uninstall', 'WPBDM' ),
    '0' => _x( 'Other', 'uninstall', 'WPBDM' )
);
?>

<form id="wpbdp-uninstall-capture-form" action="<?php echo $action; ?>" method="post">
    <?php wp_nonce_field( 'uninstall bd' ); ?>

    <p><?php _ex( 'We\'re sorry to see you leave. Could you take 10 seconds and answer one question for us to help us make the product better for everyone in the future?',
                  'uninstall',
                  'WPBDM' ); ?></p>
    <p><b><?php _ex( 'Why are you deleting Business Directory Plugin?', 'uninstall', 'WPBDM' ); ?></b></p>

    <div class="reasons">
        <?php foreach ( $reasons as $r => $l ): ?>
        <div class="reason">
            <label>
                <input type="radio" name="uninstall[reason_id]" value="<?php echo $r; ?>" /> <?php echo $l; ?>
            </label>

            <?php if ( 0 == $r ): ?>
            <br /><textarea name="uninstall[reason_text]" placeholder="<?php _ex( 'Please tell us why are you deleting Business Directory Plugin.', 'uninstall', 'WPBDM' ); ?>"></textarea>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <p class="buttons">
        <input type="submit" value="<?php _ex( 'Uninstall Plugin', 'uninstall', 'WPBDM'); ?>" class="button button-primary" />
    </p>
</form>
