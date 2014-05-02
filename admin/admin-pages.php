<?php
function wpbdp_admin_sidebar() {
    return wpbdp_render_page(WPBDP_PATH . 'admin/templates/sidebar.tpl.php');
}

function wpbdp_admin_header($title_ = null, $id = null, $h2items =array(), $sidebar = true) {
	global $title;

	if (!$title_) $title_ = $title;
    return wpbdp_render_page( WPBDP_PATH . 'admin/templates/header.tpl.php', array( 'page_title' => $title_, 'page_id' => $id, 'h2items' => $h2items, 'sidebar' => $sidebar ) );
}


function wpbdp_admin_footer()
{
	$html = '<!--</div>--></div><br class="clear" /></div>';
	return $html;
}

