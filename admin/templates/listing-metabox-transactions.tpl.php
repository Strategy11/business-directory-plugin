<?php
$_transaction_types = array(
        'initial' => _x('Initial Payment', 'admin infometabox', 'WPBDM'),
        'edit' => _x('Listing Edit', 'admin infometabox', 'WPBDM'),
        'renewal' => _x('Listing Renewal', 'admin infometabox', 'WPBDM'),
        'upgrade-to-sticky' => _x('Upgrade to sticky', 'admin infometabox', 'WPBDM'),
);

?>
<strong><?php _ex('Payments History', 'admin', 'WPBDM'); ?></strong>

<?php foreach ( $payments as &$payment ): ?>
<div class="transaction">
    <div class="summary">
        <span class="handle"><a href="#" title="<?php _ex('Click for more details', 'admin infometabox', 'WPBDM'); ?>">+</a></span>
        <span class="date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $payment->get_created_on() ) ); ?></span>
        <div class="transaction-status-container">
            <span class="status tag <?php echo $payment->get_status();?> "><?php echo $payment->get_status(); ?></span>
        </div>
    </div>
    <div class="details">
        <dl>
            <dt><?php echo _ex('Date', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $payment->get_created_on(); ?></dd>

            <dt><?php _ex('Amount', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo wpbdp_format_currency( $payment->get_total(), 2, $payment->get_currency_code() ); ?></dd>

            <dt><?php _ex('Gateway', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $payment->get_gateway(); ?></dd>

            <dt><?php _ex('Payer Info', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd>
                Name: <span class="name"><?php echo $payment->get_payer_info( 'name' ) ? $payment->get_payer_info( 'name' ) : '--'; ?></span><br />
                Email: <span class="email"><?php echo $payment->get_payer_info( 'email' ) ? $payment->get_payer_info( 'email' ) : '--'; ?></span>
            </dd>

            <?php if ( $payment->has_been_processed() ): ?>
            <dt><?php _ex('Processed on', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $payment->get_processed_on(); ?></dd>
            <dt><?php _ex('Processed by', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $payment->get_handler(); ?></dd>
            <?php endif; ?>
        </dl>

        <?php if ( $payment->is_pending() && current_user_can( 'administrator' ) ): ?>
        <p>
            <a href="<?php echo add_query_arg( array( 'wpbdmaction' => 'approvetransaction', 'transaction_id' => $payment->get_id() ) ); ?>" class="button-primary">
                <?php _ex('Approve payment', 'admin infometabox', 'WPBDM'); ?>
            </a>&nbsp;
            <a href="<?php echo add_query_arg( array( 'wpbdmaction' => 'rejecttransaction', 'transaction_id' => $payment->get_id() ) ); ?>" class="button">
                <?php _ex('Reject payment', 'admin infometabox', 'WPBDM'); ?>
            </a>
        </p>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>