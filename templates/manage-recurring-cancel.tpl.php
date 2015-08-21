<h3><?php _ex( 'Manage Recurring Payments - Cancel', 'manage recurring', 'WPBDM' ); ?></h3>
    <?php //_ex( 'If you want to cancel your subscription you can do so on this page. When the renewal time comes you\'ll be able to change your settings again'. )> ?>

<div id="wpbdp-manage-recurring-cancel">
    <h4><?php _ex( 'Plan Details', 'manage recurring', 'WPBDM' ); ?></h4>

    <dl>
        <dt>
            <?php _ex( 'Name:', 'manage recurring', 'WPBDM' ); ?>
        </dt>
        <dd>
            <?php echo $subscription->fee->label; ?>
        </dd>
        <dt>
            <?php _ex( 'Cost:', 'manage recurring', 'WPBDM' ); ?>
        </dt>
        <dd>
            <?php printf( _x( '%s every %s days.', 'manage recurring', 'WPBDM' ),
                              wpbdp_currency_format( $subscription->fee->amount ),
                              $subscription->fee_days ); ?>
        </dd>
        <!--<dt>
            <?php _ex( 'Number of images:', 'manage recurring', 'WPBDM' ); ?>
        </dt>
        <dd>
            <?php echo $subscription->fee_images; ?>
        </dd>-->
        <dt>
            <?php _ex( 'Expires on:', 'manage recurring', 'WPBDM' ); ?>
        </dt>
        <dd>
            <?php echo date_i18n( get_option( 'date_format' ), strtotime( $subscription->expires_on ) ); ?>
        </dd>
    </dl>

    <div class="cancel-instructions wpbdp-msg error">
        <?php echo $unsubscribe_form; ?>
    </div>

</div>
