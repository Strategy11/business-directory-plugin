<?php
/**
 * Listing title
 *
 * @package BDP/Templates/parts
 */
?>
<div class="listing-title">
	<<?php echo esc_attr( $title_type ); ?>><?php echo esc_html( $title ); ?></<?php echo esc_attr( $title_type ); ?>>
	<?php if ( in_array( 'single', wpbdp_get_option( 'display-sticky-badge' ) ) ) : ?>
		<?php echo $sticky_tag; ?>
	<?php endif; ?>
</div>
