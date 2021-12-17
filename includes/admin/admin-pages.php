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
        'title'   => $default_title,
        'id'      => wpbdp_get_var( array( 'param' => 'page' ) ),
        'buttons' => array(),
        'sidebar' => true,
        'echo'    => false,
    );

	$args = wp_parse_args( $args_or_title, $defaults );

    extract( $args );

    $id = str_replace( array( 'wpbdp_', 'wpbdp-' ), '', $id );
    $id = str_replace( array( 'admin-', 'admin_' ), '', $id );

    if ( empty( $args['echo'] ) ) {
        ob_start();
    }
?>
<div class="wrap wpbdp-admin wpbdp-admin-page wpbdp-admin-page-<?php echo esc_attr( $id ); ?>" id="wpbdp-admin-page-<?php echo esc_attr( $id ); ?>">
		<h1>
			<?php WPBDP_App_Helper::show_logo( 55 ); ?>
            <?php echo esc_html( $title ); ?>

			<?php foreach ( $buttons as $label => $url ) : ?>
                <a href="<?php echo esc_url( $url ); ?>" class="add-new-h2">
					<?php echo esc_html( $label ); ?>
				</a>
            <?php endforeach; ?>
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
 * Admin header.
 *
 * @since x.x
 */
function wpbdp_admin_bootstrap_header( $args_or_title = null, $id = null ) {
	// For backwards compatibility.
	if ( empty( $args_or_title ) || is_string( $args_or_title ) ) {
		$args_or_title = array(
			'id' => $id,
		);

		if ( empty( $args_or_title['id'] ) ) {
			unset( $args_or_title['id'] );
		}
	}

	$defaults = array(
		'id'   => wpbdp_get_var( array( 'param' => 'page' ) ),
		'echo' => false,
	);

	$args = wp_parse_args( $args_or_title, $defaults );

	extract( $args );

	$id = str_replace( array( 'wpbdp_', 'wpbdp-' ), '', $id );
	$id = str_replace( array( 'admin-', 'admin_' ), '', $id );

	if ( empty( $args['echo'] ) ) {
		ob_start();
	}
?>
<div class="wrap wpbdp-admin wpbdp-admin-layout wpbdp-admin-page wpbdp-admin-page-<?php echo esc_attr( $id ); ?>" id="wpbdp-admin-page-<?php echo esc_attr( $id ); ?>">
	<div class="wpbdp-admin-row">
	<?php
	if ( empty( $args['echo'] ) ) {
		return ob_get_clean();
	}
}

/**
 * Admin title.
 *
 * @since x.x
 */
function wpbdp_admin_title( $args_or_title = null ) {
	// For backwards compatibility.
	if ( empty( $args_or_title ) || is_string( $args_or_title ) ) {
		$args_or_title = array(
			'title'   => $args_or_title,
		);

		if ( empty( $args_or_title['title'] ) ) {
			unset( $args_or_title['title'] );
		}
	}

	$default_title = '';
	if ( empty( $GLOBALS['title'] ) ) {
		$default_title = get_admin_page_title();
	} else {
		$default_title = $GLOBALS['title'];
	}

	$defaults = array(
		'title'   => $default_title,
		'echo'    => false,
	);

	$args = wp_parse_args( $args_or_title, $defaults );

	extract( $args );

	if ( empty( $args['echo'] ) ) {
		ob_start();
	}
	?>
	<h1 class="wpbdp-page-title">
		<?php WPBDP_App_Helper::show_logo( 35, 'wpbdp-logo-center', true ); ?>
		<span class="title-text"><?php echo esc_html( $title ); ?></span>
	</h1>
	<?php
	if ( empty( $args['echo'] ) ) {
		return ob_get_clean();
	}
}

/**
 * Admin floating notification bell.
 *
 * @since x.x
 */
function wpbdp_admin_notification_bell( $echo = false ) {
	if ( ! $echo ) {
		ob_start();
	}
	?>
	<div class="wpbdp-bell-notifications hidden">
		<a href="#" class="wpbdp-bell-notifications-close"><?php esc_html_e( 'Hide notifications', 'business-directory-plugin' ); ?></a>
		<ul class="wpbdp-bell-notifications-list"></ul>
	</div>
	<div class="wpbdp-bell-notification">
		<a class="wpbdp-bell-notification-icon" href="#">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 60 60"><rect width="60" height="60" fill="#fff" rx="12"/><path stroke="#3C4B5D" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M37.5 25a7.5 7.5 0 0 0-15 0c0 8.8-3.8 11.3-3.8 11.3h22.6s-3.8-2.5-3.8-11.3ZM32.2 41.3a2.5 2.5 0 0 1-4.4 0"/><circle cx="39.4" cy="20.6" r="6.1" fill="#FF5A5A" stroke="#fff"/></svg>
		</a>
	</div>
	<?php
	if ( ! $echo ) {
		return ob_get_clean();
	}
}

/**
 * Display the help section icon.
 *
 * @since x.x
 */
function wpbdp_admin_help_section( $echo = false ) {
	if ( ! $echo ) {
		ob_start();
	}
	?>
	<div class="wpbdp-admin-info-centre">
		<a class="wpbdp-admin-info-centre-icon" href="#">
			<img src="<?php echo esc_url( WPBDP_ASSETS_URL . 'images/icons/help-circle.svg' ); ?>" width="60" height="60"/>
		</a>
	</div>
	<?php
	if ( ! $echo ) {
		return ob_get_clean();
	}
}
