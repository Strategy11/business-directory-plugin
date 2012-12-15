<div id="wpbdp-category-page" class="wpbdp-category-page businessdirectory-category businessdirectory wpbdp-page">
    <?php wpbdp_the_bar(array('search' => true)); ?>

    <h2 class="category-name"><?php echo esc_attr($category->name); ?></h2>

    <?php echo wpbdp_render('businessdirectory-listings', array('excludebuttons' => true)); ?>

</div>