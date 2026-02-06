<?php
/**
 * Listing title
 *
 * @package BDP/Templates/parts
 */
?>
<?php if ( $title_type !== 'h1' ) : ?>
	<?php
	$class_name = 'listing-title';

	if ( $title_type === true ) {
		$original_title_type = $title_type;

		$title_type  = 'h1';
		$class_name .= ' show-listing-title';
	}
	?>

	<div class="<?php echo esc_attr( $class_name ); ?>">
		<<?php echo esc_attr( $title_type ); ?>><?php echo esc_html( $title ); ?></<?php echo esc_attr( $title_type ); ?>>

	<?php
	if ( $original_title_type === true ) {
		$title_type = $original_title_type;
	}
	?>
<?php endif; ?>

<?php if ( in_array( 'single', wpbdp_get_option( 'display-sticky-badge' ), true ) ) : ?>
	<?php echo $sticky_tag; ?>
<?php endif; ?>

<?php if ( $title_type !== 'h1' ) : ?>
	</div>
<?php endif; ?>
