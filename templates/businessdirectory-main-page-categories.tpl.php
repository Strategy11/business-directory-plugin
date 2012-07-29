<div id="wpbdp-main-page" class="wpbdp-main-page wpbdp-main businessdirectory wpbdp-page">
	<div class="wpbdp-bar cf">
        <?php wpbdp_the_main_links(); ?>
        <?php wpbdp_the_search_form(); ?>
	</div>

	<div id="wpbdp-categories" class="cf">
		<?php wpbdp_the_directory_categories(); ?>
	</div>

    <?php if ($listings) echo $listings; ?>

</div>