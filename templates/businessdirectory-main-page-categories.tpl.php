<div id="wpbdp-main-page" class="wpbdp-main-page wpbdp-main businessdirectory wpbdp-page">
	<div class="wpbdp-bar">
        <?php wpbdp_the_main_links(); ?>
        <?php wpbdp_the_search_form(); ?>		
		<div class="left actions">
			<?php echo $action_links; ?>
		</div>
		<?php if ($search_form): ?>
			<div class="right search-form">
				<?php echo $search_form; ?>
			</div>
		<?php endif; ?>
	</div>

	<div id="wpbdp-categories" class="cf">
		<?php wpbdp_the_directory_categories(); ?>
	</div>

</div>