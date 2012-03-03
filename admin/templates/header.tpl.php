<div class="wrap wpbdp-admin <?php echo isset($page_id) ? 'wpbdp-page-' . $page_id : ''; ?>">
	<div id="icon-edit-pages" class="icon32"></div>
		<h2><?php echo isset($page_title) ? $page_title : __("Business Directory Plugin","WPBDM"); ?></h2>
		
		<?php echo $sidebar = wpbdp_admin_sidebar(); ?>

		<div class="wpbdp-admin-content <?php echo !empty($sidebar) ? 'with-sidebar' : 'without-sidebar'; ?>">
			<!-- <div class="postbox"> -->