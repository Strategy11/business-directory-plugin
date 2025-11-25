<?php
$entries     = WPBDP__Listing_Flagging::get_flagging_meta( $listing->get_id() );
$is_reported = WPBDP__Listing_Flagging::is_flagged( $listing->get_id() );
?>
<table class="widefat fixed" cellspacing="0">
	<tbody>
		<tr class="no-items" style="<?php echo ( $is_reported ? 'display : none;' : '' ); ?>">
			<td colspan="2"><?php esc_html_e( 'This listing has not been reported.', 'business-directory-plugin' ); ?></td>
		</tr>
		<?php if ( $is_reported ) : ?>
			<?php
			foreach ( $entries as $key => $value ) :
				echo wpbdp_render_page(
					WPBDP_PATH . 'templates/admin/metaboxes-listing-flagging-row.tpl.php',
					array(
						'listing' => $listing,
						'key'     => $key,
						'value'   => $value,
					)
				);
			endforeach;
			?>
		<?php endif; ?>
	</tbody>
</table>

<?php if ( $is_reported ) : ?>
<div class="wpbdp-remove-listing-reports">
	<a class="button button-small" href="
	<?php
	echo esc_url(
		wp_nonce_url(
			add_query_arg(
				array(
					'wpbdmaction' => 'delete-flagging',
					'listing_id'  => $listing->get_id(),
					'meta_pos'    => 'all',
				),
				get_edit_post_link( $listing->get_id() )
			),
			'wpbdp_handle_action_delete-flagging'
		)
	);
	?>
											">
		<?php esc_html_e( 'Clear listing reports.', 'business-directory-plugin' ); ?>
	</a>
</div>
<?php endif; ?>
