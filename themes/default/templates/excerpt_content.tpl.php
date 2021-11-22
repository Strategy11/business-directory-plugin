<?php
/**
 * Listings except content template
 *
 * @package BDP/Themes/Default/Templates/Excerpt Content
 */

?>

<div class="listing-title">
	<?php echo $fields->t_title->value; ?>
</div>

<div class="excerpt-content">
	<?php if ( $images->thumbnail ): ?>
		<?php echo $images->thumbnail->html; ?>
	<?php endif; ?>

	<div class="listing-details">
		<?php foreach ( $fields->not( 'social' ) as $field ) : ?>
			<?php echo $field->html; ?>
		<?php endforeach; ?>

		<?php
			$social = $fields->filter( 'social' );
		?>
		<?php if ( $social && $social->html ) : ?>
			<div class="social-fields cf"><?php echo $social->html; ?></div>
		<?php endif; ?>
	</div>
</div>
