<?php
/**
 * Listing title
 *
 * @package BDP/Templates/parts
 */

$force_show_title = apply_filters( 'wpbdp_force_show_listing_title', false );
?>
<?php if ( $title_type !== 'h1' || $force_show_title ) : ?>
	<?php $class_name = $force_show_title ? ' show-listing-title' : ''; ?>
	<div class="<?php echo esc_attr( 'listing-title' . $class_name ); ?>">
		<<?php echo esc_attr( $title_type ); ?>><?php echo esc_html( $title ); ?></<?php echo esc_attr( $title_type ); ?>>
<?php endif; ?>

<?php if ( in_array( 'single', wpbdp_get_option( 'display-sticky-badge' ), true ) ) : ?>
	<?php echo $sticky_tag; ?>
<?php endif; ?>

<?php if ( $title_type !== 'h1' || $force_show_title ) : ?>
	</div>
<?php endif; ?>
