<h4><?php _ex('Upgrade listing', 'templates', 'WPBDM'); ?></h4>

<?php if ($gateways): ?>
	<?php foreach ($gateways as $payment_option) : ?>
		<h4 class="paymentheader">
			<?php
			echo sprintf(_x('Pay %s upgrade fee via %s', 'templates', 'WPBDM'), wpbdp_get_option('currency-symbol') . $cost, $payment_option['name']);
			?>
		</h4>
		<div class="paymentbuttondiv payment-gateway-<?php echo $payment_option['id']; ?>">
			<?php echo $payment_option['html']; ?>
		</div>
	<?php endforeach; ?>
<?php else: ?>
	<?php _ex('We can not process your payment at this moment.', 'templates', 'WPBDM'); ?>
<?php endif; ?>