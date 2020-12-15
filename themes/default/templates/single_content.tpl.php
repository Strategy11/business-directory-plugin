<?php
/**
 * Listing detail view rendering template
 *
 * @package BDP/Templates/Single Content
 */

?>
<?php if ( $images->main || $images->thumbnail ) : ?>
    <?php echo $images->main ? $images->main->html : $images->thumbnail->html; ?>
<?php endif; ?>

<div class="listing-details cf">
    <?php foreach ( $fields->not( 'social' ) as $field ) : ?>
        <?php echo $field->html; ?>
    <?php endforeach; ?>

	<?php
	wpbdp_render(
		'parts/listing-socials',
		array(
			'fields' => $fields,
			'echo'   => true,
		),
		true
	);
	?>
</div>

<?php
wpbdp_render(
	'parts/listing-images',
	array(
		'images' => $images->extra,
		'echo'   => true,
	),
	true
);
?>
