<div id="wpbdmentry">

	<div id="lco">
		<div class="title">
			<?php echo !$listing_data['listing_id'] ? _x('Submit A Listing', 'templates', 'WPBDM') : _x('Edit Your Listing', 'templates', 'WPBDM'); ?>
		</div>
		<div class="button">
			<?php echo wpbusdirman_post_menu_button_viewlistings(); ?>
			<?php echo wpbusdirman_post_menu_button_directory(); ?>
		</div>
		<div style="clear: both;"></div>
	</div>

	<div class="clear"></div>

	<h2><?php _ex('Step 4 - Checkout', 'templates', 'WPBDM'); ?></h2>
	<?php foreach ($gateways as $payment_option) : ?>
		<h4 class="paymentheader">
			<?php
			echo sprintf(_x('Pay %s listing fee via %s', 'templates', 'WPBDM'), wpbdp_get_option('currency-symbol') . $cost, $payment_option['name']);
			?>
		</h4>
		<div class="paymentbuttondiv payment-gateway-<?php echo $payment_option['id']; ?>">
			<?php echo $payment_option['html']; ?>
		</div>
	<?php endforeach; ?>

</div>