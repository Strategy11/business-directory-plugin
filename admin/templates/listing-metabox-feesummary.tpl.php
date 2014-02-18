<p><strong><?php _ex('Categories for this listing', 'admin infometabox', 'WPBDM'); ?></strong></p>

<?php if ( ! $categories ): ?>
<p><?php _ex( 'No categories on this listing. Please add one to associate fees.', 'admin infometabox', 'WPBDM' ); ?></p>
<?php else: ?>
<dl>
	<?php foreach ( $categories as &$category ): ?>
	<dt class="category-name">
		<?php if ( $category->expired ): ?><s><?php endif; ?><?php echo $category->name; ?><?php if ( $category->expired ): ?></s><?php endif; ?>
		<a href="<?php echo add_query_arg( array( 'wpbdmaction' => 'removecategory', 'category_id' => $category->id ) ); ?>" class="removecategory-link"><?php _ex( 'Remove Category', 'admin infometabox', 'WPBDM' ); ?></a>
	</dt>
	<dd>
		<?php if ( $category->expired ): ?> (<?php _ex( 'Expired', 'admin infometabox', 'WPBDM' ); ?>)<?php endif; ?>
		<dl class="feeinfo">
			<dt>
				<?php if ( $category->expired ): ?>
					<?php _ex('Expired on', 'admin infometabox', 'WPBDM'); ?>
				<?php else: ?>
					<?php _ex('Expires on', 'admin infometabox', 'WPBDM'); ?>
				<?php endif; ?>	
			</dt>
			<dd>
				<?php if ( $category->expires_on ): ?>
					<?php echo date_i18n(get_option('date_format'), strtotime($category->expires_on)); ?>
				<?php else: ?>
					<?php _ex('never', 'admin infometabox', 'WPBDM'); ?>
				<?php endif; ?>
                <?php if ( current_user_can( 'administrator' ) ): ?>
                    <a href="<?php echo add_query_arg( array( 'wpbdmaction' => 'change_expiration', 'listing_fee_id' => $category->renewal_id ) ); ?>"
                       class="listing-fee-expiration-change-link"
                       title="<?php _ex( 'Click to manually change expiration date.', 'admin infometabox', 'WPBDM' ); ?>"
                       data-renewalid="<?php echo $category->renewal_id; ?>"
                       data-date="<?php echo date('Y-m-d', strtotime( $category->expires_on ) ); ?>"><?php _ex( 'Edit', 'admin infometabox', 'WPBDM' ); ?></a>

                    <div class="listing-fee-expiration-datepicker renewal-<?php echo $category->renewal_id; ?>"></div>
                <?php endif; ?>
			</dd>
		</dl>
        
		<?php if (current_user_can('administrator') ): ?>
		- <a href="#" onclick="window.prompt('<?php _ex( 'Renewal URL (copy & paste)', 'admin infometabox', 'WPBDM' ); ?>', '<?php echo wpbdp_listings_api()->get_renewal_url( $category->renewal_id ); ?>'); return false;"><?php _ex( 'Show renewal link', 'admin infometabox', 'WPBDM' ); ?></a><br />
		- <a href="<?php echo add_query_arg( array( 'wpbdmaction' => 'send-renewal-email',
													'renewal_id' => $category->renewal_id ) ); ?>"><?php _ex( 'Send renewal e-mail to user', 'admin infometabox', 'WPBDM' ); ?></a><br />
		</a>
        <?php endif; ?>

    </dd>
    <?php endforeach; ?>
</dl>
<?php endif; ?>