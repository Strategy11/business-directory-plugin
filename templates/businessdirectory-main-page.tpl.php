<div id="wpbdp-main-page" class="wpbdp-main-page wpbdp-main businessdirectory wpbdp-page">
	<?php wpbdp_the_bar(array('search' => true)); ?>

	<div id="wpbdp-categories" class="cf">
		<?php wpbdp_the_directory_categories(); ?>
	</div>

    <?php if ($listings) echo $listings; ?>

</div>