<?php if ( $images->thumbnail ): ?>
    <?php echo $images->thumbnail->html; ?>
<?php endif; ?>

<div class="listing-details">
    <?php foreach ( $fields->not( 'social' ) as $field ): ?>
        <?php echo $field->html; ?>
    <?php endforeach; ?>

    <?php
    $social = $fields->filter( 'social' );
    ?>
    <?php if ( $social ): ?>
    <div class="social-fields cf"><?php echo $social->html; ?></div>
    <?php endif; ?>
</div>
