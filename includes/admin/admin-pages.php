<?php
function wpbdp_admin_sidebar() {
    return wpbdp_render_page( WPBDP_PATH . 'templates/admin/sidebar.tpl.php' );
}

function wpbdp_admin_header( $title_ = null, $id = null, $h2items = array(), $sidebar = true ) {
    global $title;

    $title_ = ! $title_ ? $title : $title_;

    $css_id = ! empty( $id ) ? 'wpbdp-admin-page-' . $id : '';
    $css_class  = '';
    $css_class .= ! empty( $id ) ? 'wpbdp-page-' . $id : 'wpbdp-page';

    ob_start();
?>
<div class="wrap wpbdp-admin <?php echo $css_class; ?>" id="<?php echo $css_id; ?>">
	<div id="icon-edit-pages" class="icon32"></div>
		<h1>
			<?php echo ! empty( $title_ ) ? $title_ : __( 'Business Directory Plugin', 'WPBDM' ); ?>

            <?php if ( ! empty( $h2items ) ): ?>
				<?php foreach ( $h2items as $item ): ?>
					<a href="<?php echo $item[1]; ?>" class="add-new-h2"><?php echo $item[0]; ?></a>
				<?php endforeach; ?>
			<?php endif; ?>
		</h1>
		
		<?php echo $sidebar = $sidebar ? wpbdp_admin_sidebar() : ''; ?>

		<div class="wpbdp-admin-content <?php echo ! empty( $sidebar ) ? 'with-sidebar' : 'without-sidebar'; ?>">
<?php
    return ob_get_clean();
}


function wpbdp_admin_footer() {
    ob_start();
?>
</div><br class="clear" /></div>
<?php
	return ob_get_clean();
}

