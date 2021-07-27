<?php
/**
 * Template listing single view.
 *
 * @package BDP/Templates/Single
 */
?>

<div id="<?php echo esc_attr( $listing_css_id ); ?>" class="<?php echo esc_attr( $listing_css_class ); ?>">
    <?php wpbdp_get_return_link(); ?>
    <div class="listing-title">
        <<?php echo esc_attr( $title_type ); ?>><?php echo esc_html( $title ); ?></<?php echo esc_attr( $title_type ); ?>>
	    <?php if ( in_array( 'single', wpbdp_get_option( 'display-sticky-badge' ) ) ) : ?>
	        <?php echo $sticky_tag; ?>
	    <?php endif; ?>
    </div>

    <?php
	wpbdp_x_part(
		'parts/listing-buttons',
		array(
			'listing_id' => $listing_id,
			'view'       => 'single',
		)
	);

	wpbdp_x_part( 'single_content' );
	?>
</div>
