<div class="wrap wpbdp-admin <?php echo isset($page_id) ? 'wpbdp-page-' . $page_id : 'wpbdp-page'; ?>" id="<?php echo isset( $page_id ) ? 'wpbdp-admin-page-' . $page_id : ''; ?>">
	<div id="icon-edit-pages" class="icon32"></div>
		<h1>
			<?php echo isset($page_title) ? $page_title : __("Business Directory Plugin","WPBDM"); ?>

			<?php if ($h2items): ?>
				<?php foreach ($h2items as $item): ?>
					<a href="<?php echo $item[1]; ?>" class="add-new-h2"><?php echo $item[0]; ?></a>
				<?php endforeach; ?>
			<?php endif; ?>
		</h1>
		
		<?php echo $sidebar = $sidebar ? wpbdp_admin_sidebar() : false; ?>

		<div class="wpbdp-admin-content <?php echo !empty($sidebar) ? 'with-sidebar' : 'without-sidebar'; ?>">
			<!-- <div class="postbox"> -->
