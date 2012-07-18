
<?php echo wpbusdirman_post_menu_buttons(); ?>

<div class="wpbdp-listing single">
    <div class="title"><?php the_title(); ?></div>

    <div class="mainimage">
        <?php echo wpbusdirman_post_main_image(); ?>
    </div>

    <div class="listing-details">
        <?php echo wpbusdirman_post_single_listing_details(); ?>
    </div>

    <div class="extraimages">
        <?php echo wpbusdirman_post_extra_thumbnails(); ?>
    </div>

    <?php // comments_template(); ?>
</div>
