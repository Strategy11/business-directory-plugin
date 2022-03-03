<?php
function wpbdp_admin_sidebar( $echo = false ) {
	$page = wpbdp_render_page( WPBDP_PATH . 'templates/admin/sidebar.tpl.php', array(), $echo );

	if ( ! $echo ) {
		return $page;
	}

	return ! empty( $page );
}

function wpbdp_admin_header( $args_or_title = null, $id = null, $h2items = array(), $sidebar = null ) {
	// For backwards compatibility.
	if ( ! is_array( $args_or_title ) ) {
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
			'sidebar' => $sidebar,
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
		$default_title = get_admin_page_title();
	} else {
		$default_title = $GLOBALS['title'];
	}

	$defaults = array(
		'title'        => $default_title,
		'id'           => wpbdp_get_var( array( 'param' => 'page' ) ),
		'buttons'      => array(),
		'sidebar'      => true,
		'echo'         => false,
		'tabbed_title' => false,
		'titles'       => array(),
		'current_tab'  => '',
		'full_width'   => false,
	);

	$args = wp_parse_args( $args_or_title, $defaults );

    extract( $args );

	$id = str_replace( array( 'wpbdp_', 'wpbdp-' ), '', $args['id'] );
	$id = str_replace( array( 'admin-', 'admin_' ), '', $id );

	if ( empty( $args['echo'] ) ) {
		ob_start();
	}
?>
<div class="wrap wpbdp-admin wpbdp-admin-page wpbdp-admin-page-<?php echo esc_attr( $id ); ?>" id="wpbdp-admin-page-<?php echo esc_attr( $id ); ?>">
		<h1>
			<?php WPBDP_App_Helper::show_logo( 55 ); ?>
            <?php echo esc_html( $title ); ?>

			<?php
			$button_class = 'add-new-h2';
			foreach ( $buttons as $id => $button ) :
				if ( ! is_array( $button ) ) {
					$button = array(
						'url'   => $button,
						'label' => $id,
					);
				}
				?>
				<a href="<?php echo esc_url( $button['url'] ); ?>" class="<?php echo esc_attr( $button_class . ( isset( $button['class'] ) ? ' ' . $button['class'] : '' ) ); ?>">
					<?php echo esc_html( $button['label'] ); ?>
				</a>
				<?php
			endforeach;
			?>
        </h1>

		<?php $sidebar = $sidebar ? wpbdp_admin_sidebar( true ) : ''; ?>

		<div class="wpbdp-admin-content <?php echo ! empty( $sidebar ) ? 'with-sidebar' : 'without-sidebar'; ?>">
    <?php
    if ( empty( $args['echo'] ) ) {
        return ob_get_clean();
    }
}

/*
 * @param bool|string Use 'echo' or true to show the footer.
 */
function wpbdp_admin_footer( $echo = false ) {
	$footer = '</div><br class="clear" /></div>';
    if ( ! $echo ) {
        return $footer;
    }

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $footer;
}

/**
 * Admin floating notification bell.
 *
 * @since x.x
 */
function wpbdp_admin_notification_bell() {
	?>
	<div class="wpbdp-bell-notifications hidden">
		<a href="#" class="wpbdp-bell-notifications-close"><?php esc_html_e( 'Hide notifications', 'business-directory-plugin' ); ?></a>
		<ul class="wpbdp-bell-notifications-list"></ul>
	</div>
	<div class="wpbdp-bell-notification">
		<a class="wpbdp-bell-notification-icon" href="#">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 60 60"><rect width="60" height="60" fill="#fff" rx="12"/><path stroke="#3C4B5D" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M37.5 25a7.5 7.5 0 0 0-15 0c0 8.8-3.8 11.3-3.8 11.3h22.6s-3.8-2.5-3.8-11.3ZM32.2 41.3a2.5 2.5 0 0 1-4.4 0"/><circle class="wpbdp-bell-notification-icon-indicator" cx="39.4" cy="20.6" r="6.1" fill="#FF5A5A" stroke="#fff"><animate attributeName="r" from="6" to="8" dur="1.5s" begin="0s" repeatCount="indefinite"/><animate attributeName="opacity" from="1" to="0.8" dur="1.5s" begin="0s" repeatCount="indefinite"/></circle></svg>
		</a>
	</div>
	<?php
}
