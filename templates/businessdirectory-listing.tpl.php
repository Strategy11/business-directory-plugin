<div class="title"><?php echo $title; ?></div>

<?php if ($main_image): ?>
<div class="main-image"><?php echo $main_image; ?></div>
<?php endif; ?>

<div class="listing-details">
    <?php echo $listing_fields; ?>
</div>

<?php if ($extra_images): ?>
<div class="extra-images">
    <ul>
    <?php foreach ($extra_images as $image): ?>
        <li><?php echo $image; ?></li>
    <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php // comments_template(); ?>