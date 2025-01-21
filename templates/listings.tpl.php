<?php
/**
 * Listings display template
 *
 * @package BDP/Templates/Listings
 */

wpbdp_the_listing_sort_options();
?>

<div id="wpbdp-listings-list" class="listings wpbdp-listings-list list wpbdp-grid <?php echo esc_attr( apply_filters( 'wpbdp_listings_class', '' ) ); ?>">
	<?php
	/**
	 * Filters whether to display the pagination in the listings wrapper or outside of it.
	 * 
	 * @since 6.4.10
	 */
	$display_pagination_in_listings_wrapper = apply_filters( 'wpbdp_display_pagination_in_listings_wrapper', true );

	wpbdp_x_part(
		'parts/listings-loop',
		array(
			'query'                                  => $query,
			'display_pagination_in_listings_wrapper' => $display_pagination_in_listings_wrapper,
		)
	);
	?>
</div>
<?php

if ( ! $display_pagination_in_listings_wrapper ) {
	/** @phpstan-ignore-next-line */
	wpbdp_x_part(
		'parts/pagination',
		array(
			'query' => $query,
		)
	);
}
