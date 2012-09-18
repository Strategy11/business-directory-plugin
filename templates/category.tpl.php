<div id="wpbdp-category-page" class="wpbdp-category-page businessdirectory-category businessdirectory wpbdp-page">
    <div class="wpbdp-bar cf">
        <?php wpbdp_the_main_links(); ?>
        <?php wpbdp_the_search_form(); ?>
    </div>

    <h2 class="category-name"><?php echo esc_attr($category->name); ?></h2>    

    <?php echo wpbdp_render('businessdirectory-listings', array('excludebuttons' => true)); ?>

</div>