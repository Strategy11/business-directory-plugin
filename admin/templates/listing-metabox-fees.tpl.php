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

<?php
echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/listing-metabox-categories.tpl.php', array(
                            'categories' => $categories,
                            'listing' => $listing ) );
?>

<?php if ( $listing->get_categories( 'expired' ) ): ?>
<a href="<?php echo esc_url( add_query_arg( 'wpbdmaction', 'renewlisting' ) ); ?>" class="button-primary button"><?php _ex( 'Renew listing in all expired categories', 'admin infometabox', 'WPBDM'); ?></a>
<?php endif; ?>
