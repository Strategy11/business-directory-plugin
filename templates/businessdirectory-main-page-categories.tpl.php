<div id="wpbdmentry">
	<div id="lco">
		<div class="left buttons">
			<?php echo $submit_listing_button; ?>
			<?php echo $view_listings_button; ?>
		</div>
		
		<div class="right">
			<?php if (wpbdp_get_option('show-search-listings')): ?>
			<?php echo wpbdp_search_form(); ?>
			<?php endif; ?>
		</div>
	</div>

	<div id="wpbusdirmancats">
		<div style="clear:both;"></div>
		<ul>
			<?php echo wpbusdirman_post_list_categories(); ?>
		</ul>
	</div>
	<br style="clear: both;" />
</div>