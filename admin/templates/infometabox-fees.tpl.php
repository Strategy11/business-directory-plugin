<div id="listing-metabox-fees">
	<strong><?php _ex('Fee Information', 'admin infometabox', 'WPBDM'); ?></strong>

	<?php _ex('Payment Mode:', 'admin infometabox', 'WPBDM'); ?> <?php echo wpbdp_payments_api()->payments_possible() ? _x('Paid', 'admin infometabox', 'WPBDM') : _x('Free', 'admin infometabox', 'WPBDM'); ?><br />
	<?php
		if (current_user_can('administrator')) {
			echo sprintf(_x('To change your payment mode, go to <a href="%s">Payment Settings</a>.', 'admin infometabox', 'WPBDM'), 
				 admin_url('admin.php?page=wpbdp_admin_settings&groupid=payment')  );
		}
	?>

	<?php if (!wpbdp_payments_api()->payments_possible() && current_user_can('administrator')): ?>
	<p><i><?php _ex('Note: In Free mode, the fee plans will always be set to "Free Listing" below.', 'admin infometabox', 'WPBDM'); ?></i></p>
	<?php endif; ?>

	<?php if ( ! $post_categories ): ?>
	<p><?php _ex( 'No categories on this listing. Please add one to associate fees.', 'admin infometabox', 'WPBDM' ); ?></p>
	<?php else: ?>
	<dl>
		<?php foreach ($post_categories as $term): ?>
		<?php $fee = wpbdp_listings_api()->get_listing_fee_for_category( $post_id, $term->term_id ); ?>
		<?php $expired = $fee && $fee->expires_on && ( strtotime( $fee->expires_on ) < time() ) ? true : false; ?>

		<dt class="category-name">
			<?php if ( $expired ): ?><s><?php endif; ?><?php echo $term->name; ?><?php if ( $expired ): ?></s><?php endif; ?> 
			<a href="<?php echo add_query_arg( array( 'wpbdmaction' => 'removecategory', 'category_id' => $term->term_id ) ); ?>" class="removecategory-link"><?php _ex( 'Remove Category', 'admin infometabox', 'WPBDM' ); ?></a>
		</dt>
		<dd>
			<?php if ( $fee ) : ?>
				<?php if ( $expired ): ?> (<?php _ex( 'Expired', 'admin infometabox', 'WPBDM' ); ?>)<?php endif; ?>
				<dl class="feeinfo">
					<dt><?php _ex('Fee', 'admin infometabox', 'WPBDM'); ?></dt>
					<dd><?php echo $fee->label; ?></dd>
					<dt><?php _ex('# Images', 'admin infometabox', 'WPBDM'); ?></dt>
					<dd><?php echo min($image_count, $fee->images); ?> / <?php echo $fee->images; ?></dd>
					<dt>
						<?php if ( $fee->expires_on && $expired ): ?>
							<?php _ex('Expired on', 'admin infometabox', 'WPBDM'); ?>
						<?php else: ?>
							<?php _ex('Expires on', 'admin infometabox', 'WPBDM'); ?>
						<?php endif; ?>	
					</dt>
					<dd>
						<?php if ($fee->expires_on): ?>
							<?php echo date_i18n(get_option('date_format'), strtotime($fee->expires_on)); ?>
						<?php else: ?>
							<?php _ex('never', 'admin infometabox', 'WPBDM'); ?>
						<?php endif; ?>
                        <?php if ( current_user_can( 'administrator' ) ): ?>
                            <a href="<?php echo add_query_arg( array( 'wpbdmaction' => 'change_expiration', 'listing_fee_id' => $fee->renewal_id ) ); ?>"
                               class="listing-fee-expiration-change-link"
                               title="<?php _ex( 'Click to manually change expiration date.', 'admin infometabox', 'WPBDM' ); ?>"
                               data-renewalid="<?php echo $fee->renewal_id; ?>"
                               data-date="<?php echo date('Y-m-d', strtotime( $fee->expires_on ) ); ?>"><?php _ex( 'Edit', 'admin infometabox', 'WPBDM' ); ?></a>

                            <div class="listing-fee-expiration-datepicker renewal-<?php echo $fee->renewal_id; ?>"></div>
                        <?php endif; ?>
					</dd>
				</dl>

			<?php else: ?>
				<?php _ex('No fee assigned.', 'admin infometabox', 'WPBDM'); ?>
			<?php endif; ?>
				<?php if (current_user_can('administrator')): ?>
				<?php if ( $fee ): ?>
				- <a href="#" onclick="window.prompt('<?php _ex( 'Renewal URL (copy & paste)', 'admin infometabox', 'WPBDM' ); ?>', '<?php echo wpbdp_listings_api()->get_renewal_url( $fee->renewal_id ); ?>'); return false;"><?php _ex( 'Show renewal link', 'admin infometabox', 'WPBDM' ); ?></a><br />
				- <a href="<?php echo add_query_arg( array( 'wpbdmaction' => 'send-renewal-email',
															'renewal_id' => $fee->renewal_id ) ); ?>"><?php _ex( 'Send renewal e-mail to user', 'admin infometabox', 'WPBDM' ); ?></a><br /><?php endif; ?>
				- <a href="#assignfee" class="assignfee-link">
					<?php $fee ? ( $expired ? _ex( 'Renew manually...', 'admin infometabox', 'WPBDM' ) : _ex('Change fee...', 'admin infometabox', 'WPBDM') ) : _ex('Assign one', 'admin infometabox', 'WPBDM'); ?>
				</a>				

				<div class="assignfee">
					<span class="close-handle"><a href="#" title="<?php _ex('close', 'admin infometabox', 'WPBDM'); ?>">[x]</a></span>
					<?php foreach (wpbdp_fees_api()->get_fees_for_category($term->term_id) as $fee_option): ?>
					<div class="feeoption">
						<strong><?php echo $fee_option->label; ?></strong> (<?php echo wpbdp_get_option('currency-symbol'); ?><?php echo $fee_option->amount; ?>)

						<a href="<?php echo add_query_arg(array('wpbdmaction' => 'assignfee', 'category_id' => $term->term_id, 'fee_id' => $fee_option->id)); ?>" class="button">
							<?php _ex('Use this', 'admin infometabox', 'WPBDM'); ?>
						</a>
												
						<div class="details">
							<?php echo sprintf(_nx('%d image', '%d images', $fee_option->images, 'admin infometabox', 'WPBDM'), $fee_option->images); ?> &#149;
							<?php if ($fee_option->days == 0): ?>
								<?php _ex('Unlimited listing days', 'admin infometabox', 'WPBDM'); ?>
							<?php else: ?>
								<?php echo sprintf(_nx('%d day', '%d days', $fee_option->days, 'admin infometabox', 'WPBDM'), $fee_option->days); ?>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

		</dd>
		<?php endforeach; ?>
	</dl>
	<?php endif; ?>

    <?php if ( $expired_categories ): ?>
	<a href="<?php echo add_query_arg( 'wpbdmaction', 'renewlisting' ); ?>" class="button-primary button"><?php _ex( 'Renew listing in all expired categories', 'admin infometabox', 'WPBDM'); ?></a>
    <?php endif; ?>

</div>