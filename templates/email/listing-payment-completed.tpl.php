<?php
	esc_html_e( 'A new listing payment has been completed. Payment details can be found below.', 'business-directory-plugin' );
?>

----

<?php esc_html_e( 'Payment ID', 'business-directory-plugin' ); ?>:
	<?php
	printf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=wpbdp_admin_payments&wpbdp-view=details&payment-id=' . $payment->id ) ),
		esc_html( $payment->id )
	);
	?>


<?php if ( ! empty( $payment_datails ) ) : ?>
	<?php esc_html_e( 'Payment Details', 'business-directory-plugin' ); ?>:
		<?php echo $payment_datails; ?>
<?php else : ?>
	<?php esc_html_e( 'Amount', 'business-directory-plugin' ); ?>: <?php echo esc_html( $plan->fee_amount ); ?>
<?php endif; ?>


<?php esc_html_e( 'Plan', 'business-directory-plugin' ); ?>:
<?php
	printf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=wpbdp-admin-fees&wpbdp-view=edit-fee&id=' . $plan->fee_id ) ),
		esc_html( $plan->fee_label )
	);
	?>


<?php esc_html_e( 'Listing URL', 'business-directory-plugin' ); ?>: <?php echo esc_url_raw( $listing->is_published() ? $listing->get_permalink() : get_preview_post_link( $listing->get_id() ) ); ?>

<?php esc_html_e( 'Listing admin URL', 'business-directory-plugin' ); ?>: <?php echo esc_url_raw( wpbdp_get_edit_post_link( $listing->get_id() ) ); ?>
