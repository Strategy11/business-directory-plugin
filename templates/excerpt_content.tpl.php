<?php if ( $images->thumbnail ) : ?>
	<?php echo $images->thumbnail->html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php endif; ?>

<div class="listing-details<?php echo esc_attr( $images->thumbnail ? '' : ' wpbdp-no-thumb' ); ?>">
	<?php foreach ( $fields->not( 'social' ) as $field ) : ?>
		<?php
		$address    = array( 'address', 'address2', 'city', 'state', 'country', 'zip' );
		$has_street = ! empty( $fields->t_address->value );
		if ( in_array( $field->tag, $address, true ) ) :
			if ( $has_street ) :
				if ( empty( $skip_address ) && $field->html ) :
					$skip_address = $address;
					?>
		<div class="address-info wpbdp-field-display wpbdp-field wpbdp-field-value">
				<?php echo wp_kses_post( $fields->_h_address_label ); ?>
			<div><?php echo wp_kses_post( $fields->_h_address ); ?></div>
		</div>
				<?php endif; ?>
			<?php elseif ( $field->html ) : ?>
			<?php echo $field->html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		<?php else : ?>
			<?php echo $field->html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>
	<?php endforeach; ?>

	<?php
	$social = $fields->filter( 'social' );
	?>
	<?php if ( $social && $social->html ) : ?>
	<div class="social-fields wpbdp-flex"><?php echo $social->html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<?php endif; ?>
</div>
