<?php
function wpbdp_admin_sidebar() {
    return wpbdp_render_page( WPBDP_PATH . 'templates/admin/sidebar.tpl.php' );
}

function wpbdp_admin_header( $args_or_title = null, $id = null, $h2items = array(), $sidebar = null ) {
    // For backwards compatibility.
    if ( empty( $args_or_title ) || is_string( $args_or_title ) ) {
        $buttons = array();

        if ( $h2items ) {
            foreach ( $h2items as $item ) {
                $buttons[ $item[0] ] = $item[1];
            }
        }

        $args_or_title = array(
            'title'   => $args_or_title,
            'id'      => $id,
            'buttons' => $buttons,
            'sidebar' => $sidebar
        );

        if ( empty( $args_or_title['title'] ) ) {
            unset( $args_or_title['title'] );
        }

        if ( empty( $args_or_title['id'] ) ) {
            unset( $args_or_title['id'] );
        }

        if ( is_null( $args_or_title['sidebar'] ) ) {
            unset( $args_or_title['sidebar'] );
        }
    }

    $default_title = '';
    if ( empty( $GLOBALS['title'] ) ) {
        if ( function_exists( 'get_admin_page_title' ) ) {
            $default_title = get_admin_page_title();
        }
    } else {
        $default_title = $GLOBALS['title'];
    }

    $defaults = array(
        'title'   => $default_title,
        'id'      => ! empty( $_GET['page'] ) ? $_GET['page'] : '',
        'buttons' => array(),
        'sidebar' => true
    );


    $args = wp_parse_args( $args_or_title, $defaults);

    extract( $args );

    $id = str_replace( array( 'wpbdp_', 'wpbdp-' ), '', $id );
    $id = str_replace( array( 'admin-', 'admin_' ), '', $id );

    ob_start();
?>
<div class="wrap wpbdp-admin wpbdp-admin-page wpbdp-admin-page-<?php echo $id; ?>" id="wpbdp-admin-page-<?php echo $id; ?>">
	<div id="icon-edit-pages" class="icon32"></div>
		<h1>
            <?php echo $title; ?>

            <?php foreach ( $buttons as $label => $url ): ?>
                <a href="<?php echo $url; ?>" class="add-new-h2"><?php echo $label; ?></a>
            <?php endforeach; ?>
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

