<?php
/**
 * Payment details template.
 * @since 3.4
 */
?>
<div id="wpbdp-payment-details-<?php echo $payment->get_id(); ?>" class="<?php echo $payment->get_status(); ?> wpbdp-payment-details" data-title="<?php echo esc_attr( _x( 'Payment Details', 'admin payments', 'WPBDM' ) ); ?>">
    <div class="header">
        <h2><?php printf( _x( 'Payment #%d', 'admin payments', 'WPBDM'), $payment->get_id() ); ?></h2>
        <span class="date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $payment->get_created_on() ) ); ?></span>
        <span class="payment-status tag <?php echo $payment->get_status(); ?>"><?php echo $payment->get_status(); ?></span>
    </div>

    <div class="details">
        <dl>
            <dt><?php echo _ex('Created on', 'admin payments', 'WPBDM'); ?></dt>
            <dd><?php echo $payment->get_created_on(); ?></dd>

            <dt><?php _ex('Amount', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo wpbdp_currency_format( $payment->get_total(), array( 'currency' => $payment->get_currency_code() ) ); ?></dd>            

            <dt><?php _ex('Gateway', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $payment->get_gateway() ? $payment->get_gateway() : '–'; ?></dd>

            <?php if ( $payment->has_been_processed() ): ?>
            <dt><?php _ex('Processed on', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $payment->get_processed_on(); ?></dd>
            <dt><?php _ex('Processed by', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd><?php echo $payment->get_handler(); ?></dd>
            <?php endif; ?>            
        </dl>
    </div>

    <div class="invoice"><?php echo $invoice; ?></div>

    <div class="actions">
        <?php if ( $payment->is_pending() ): ?>
            <?php if ( ! $payment->has_item_type( 'recurring_fee' ) ): ?>
            <a href="<?php echo esc_url( add_query_arg( array( 'wpbdmaction' => 'approvetransaction', 'transaction_id' => $payment->get_id() ),
                                               admin_url('post.php?post=' . $payment->get_listing_id() . '&action=edit' ) ) ); ?>" class="button-primary">
                <?php _ex('Approve payment', 'admin payments', 'WPBDM'); ?>
            </a>&nbsp;
            <?php endif; ?>
            <a href="<?php echo esc_url( add_query_arg( array( 'wpbdmaction' => 'rejecttransaction', 'transaction_id' => $payment->get_id() ),
                                               admin_url('post.php?post=' . $payment->get_listing_id() . '&action=edit' )  ) ); ?>" class="button">
                <?php _ex('Reject payment', 'admin payments', 'WPBDM'); ?>
            </a>
        <?php endif; ?>  
    </div>  
</div>
<?php
/*
    <div class="details">
        <dl>
            <dt><?php _ex('Payer Info', 'admin infometabox', 'WPBDM'); ?></dt>
            <dd>
                Name: <span class="name"><?php echo $payment->get_payer_info( 'name' ) ? $payment->get_payer_info( 'name' ) : '--'; ?></span><br />
                Email: <span class="email"><?php echo $payment->get_payer_info( 'email' ) ? $payment->get_payer_info( 'email' ) : '--'; ?></span>
            </dd>
        </dl>*/
?>
