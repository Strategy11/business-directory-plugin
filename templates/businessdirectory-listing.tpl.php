<div class="listing-title">
	<h1><?php echo esc_html( $title ); ?></h1>
	<?php echo $is_sticky ? wp_kses_post( $sticky_tag ) : ''; ?>
</div>

<?php if ( $actions ) : ?>
	<?php echo $actions; // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php endif; ?>

<?php if ( $main_image ) : ?>
	<div class="main-image"><?php echo wp_kses_post( $main_image ); ?></div>
<?php endif; ?>

<div class="listing-details cf
<?php
if ( $main_image ) :
	?>
	with-image<?php endif; ?>">
	<?php echo $listing_fields; // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>

<?php
wpbdp_x_part(
	'parts/listing-images',
	array(
		'extra_images' => $extra_images,
	)
);
?>
