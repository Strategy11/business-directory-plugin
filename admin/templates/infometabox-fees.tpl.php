<div id="listing-metabox-fees">
	<strong><?php _ex('Fee Information', 'admin infometabox', 'WPBDM'); ?></strong>

	<dl>
		<?php foreach ($post_categories as $term): ?>
		<dt class="category-name"><?php echo $term->name; ?></dt>
		<dd>
			<?php if ($fee = wpbdp_listings_api()->get_listing_fee_for_category($post_id, $term->term_id)) : ?>
				<dl class="feeinfo">
					<dt><?php _ex('Fee', 'admin infometabox', 'WPBDM'); ?></dt>
					<dd><?php echo $fee->label; ?></dd>
					<dt><?php _ex('# Images', 'admin infometabox', 'WPBDM'); ?></dt>
					<dd><?php echo min($image_count, $fee->images); ?> / <?php echo $fee->images; ?></dd>
					<dt>
						<?php if ($fee->expires_on && (strtotime($fee->expires_on) <= time())): ?>
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
					</dd>
				</dl>
			<?php else: ?>
				<?php _ex('No fee assigned.', 'admin infometabox', 'WPBDM'); ?>
				<a href="#assignfee" class="assignfee-link"><?php _ex('Assign one.', 'admin infometabox', 'WPBDM'); ?></a>

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

</div>