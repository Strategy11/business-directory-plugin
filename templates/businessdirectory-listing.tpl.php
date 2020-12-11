<div class="listing-title">
    <h1><?php echo $title; ?></h1>
</div>

<?php echo $is_sticky ? $sticky_tag : ''; ?>

<?php if ($actions): ?>
    <?php echo $actions; ?>
<?php endif; ?>

<?php if ($main_image): ?>
    <div class="main-image"><?php echo $main_image; ?></div>
<?php endif; ?>

<div class="listing-details cf <?php if ($main_image): ?>with-image<?php endif; ?>">
    <?php echo $listing_fields; ?>
</div>

<?php
wpbdp_render(
	'parts/listing-images',
	array(
		'images' => $extra_images,
		'echo'   => true,
	),
	true
);
?>
