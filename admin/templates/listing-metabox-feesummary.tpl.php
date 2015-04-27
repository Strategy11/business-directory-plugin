<p><strong><?php _ex('Categories for this listing', 'admin infometabox', 'WPBDM'); ?></strong></p>

<?php
echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/listing-metabox-categories.tpl.php', array(
                            'categories' => $categories,
                            'listing' => $listing,
                            'display' => array( 'expiration' ),
                            'admin_actions' => array( 'delete', 'renewal url', 'renewal email' )  ) );
?>

<?php if ( $listing->get_categories( 'expired' ) ): ?>
<a href="<?php echo esc_url( add_query_arg( 'wpbdmaction', 'renewlisting' ) ); ?>" class="button-primary button"><?php _ex( 'Renew listing in all expired categories', 'admin infometabox', 'WPBDM'); ?></a>
<?php endif; ?>
