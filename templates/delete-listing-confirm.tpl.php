<div id="wpbdp-delete-listing-page">

<h3><?php _ex( 'Delete Listing', 'manage recurring', 'WPBDM' ); ?></h3>

<?php if ( $has_recurring ): ?>
<div class="wpbdp-msg error">
<?php _ex( 'Your listing is associated to a recurring payment. If you don\'t cancel the recurring payment before deleting the listing, you might be charged for additional periods even though your listing won\'t be available.',
           'delete listing',
           'WPBDM' ); ?><br />
<b><?php echo str_replace( '<a>',
                        '<a href="' . esc_url( add_query_arg( 'action', 'manage-recurring', wpbdp_get_page_link( 'main' ) ) ) . '">',
                        _x( 'Please visit <a>Manage recurring payments</a> to review your current recurring payments.', 'delete listing', 'WPBDM' ) ); ?></b>
</div>
<?php endif; ?>

<form class="confirm-form" action="" method="post">
<p>
<?php printf( _x( 'You are about to remove your listing "%s" from the directory.', 'delete listing', 'WPBDM' ), $listing->get_title() ); ?><br />
<b><?php _ex( 'Are you sure you want to do this?', 'delete listing', 'WPBDM' ); ?></b>
</p>

<?php wp_nonce_field( 'delete listing ' . $listing->get_id() ); ?>

<input type="button" onclick="location.href = '<?php echo wpbdp_get_page_link( 'main'); ?>'; return false;" value="<?php _ex('No. Take me back to the directory.', 'delete listing', 'WPBDM' ); ?>" class="wpbdp-button button" />
<input class="delete-listing-confirm wpbdp-submit wpbdp-button" type="submit" value="<?php _ex( 'Yes. Delete my listing.', 'delete listing', 'WPBDM' ); ?>" />
</form>

</div>
