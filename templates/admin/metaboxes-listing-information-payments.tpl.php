<?php
/**
 * Listing recent payments metabox template
 *
 * @package BDP/Templates/Admin/Metabox listing payments
 */

?>
<!-- {{ Recent payments. -->
<div id="wpbdp-listing-metabox-payments" class="wpbdp-listing-metabox-tab wpbdp-admin-tab-content" tabindex="2">
	<div id="wpbdp-listing-payment-message" style="display: none;"></div>
	<?php if ( $payments ) : ?>
		<div class="wpbdp-payment-items">
			<?php esc_html_e( 'Click a transaction to see its details (and approve/reject).', 'business-directory-plugin' ); ?>
			<?php foreach ( $payments as $payment ) : ?>
				<?php $payment_link = esc_url( admin_url( 'admin.php?page=wpbdp_admin_payments&wpbdp-view=details&payment-id=' . $payment->id ) ); ?>
			<div class="wpbdp-payment-item wpbdp-payment-status-<?php echo esc_attr( $payment->status ); ?> cf">
				<div class="wpbdp-payment-item-row">
					<div class="wpbdp-payment-date">
						<a href="<?php echo esc_url( $payment_link ); ?>">#<?php echo esc_html( $payment->id ); ?> - <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $payment->created_at ) ) ); ?></a>
					</div>
					<div class="wpbdp-payment-status"><span class="tag paymentstatus <?php echo esc_attr( $payment->status ); ?>"><?php echo esc_html( $payment->status ); ?></span></div>
				</div>
				<div class="wpbdp-payment-item-row">
					<div class="wpbdp-payment-summary">
						<a href="<?php echo esc_url( $payment_link ); ?>" title="<?php echo esc_attr( $payment->summary ); ?>">
							<?php echo esc_html( $payment->summary ); ?>
						</a>
					</div>
					<div class="wpbdp-payment-total"><?php echo esc_html( wpbdp_currency_format( $payment->amount ) ); ?></div>
				</div>
			</div>
			<?php endforeach; ?>
			<span class="payment-delete-action" style="font-size: 13px; padding: 2px 0 0;">
				<a href="#" class="wpbdp-admin-delete-link" name="delete-payments" data-id="<?php echo esc_attr( $listing->get_id() ); ?>">Delete payment history</a>
			</span>
		</div>
	<?php else : ?>
		<?php esc_html_e( 'No payments available.', 'business-directory-plugin' ); ?>
	<?php endif; ?>
</div>
<!-- }} -->
