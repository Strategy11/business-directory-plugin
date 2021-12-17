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
			<svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
				<rect width="60" height="60" rx="12" fill="white"/>
				<path d="M37.5 25C37.5 23.0109 36.7098 21.1032 35.3033 19.6967C33.8968 18.2902 31.9891 17.5 30 17.5C28.0109 17.5 26.1032 18.2902 24.6967 19.6967C23.2902 21.1032 22.5 23.0109 22.5 25C22.5 33.75 18.75 36.25 18.75 36.25H41.25C41.25 36.25 37.5 33.75 37.5 25Z" stroke="#3C4B5D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M32.1619 41.25C31.9421 41.6288 31.6267 41.9433 31.2472 42.1619C30.8677 42.3805 30.4374 42.4956 29.9994 42.4956C29.5614 42.4956 29.1312 42.3805 28.7516 42.1619C28.3721 41.9433 28.0567 41.6288 27.8369 41.25" stroke="#3C4B5D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				<circle class="wpbdp-bell-notification-dot" cx="39.375" cy="20.625" r="6.125" fill="#FF5A5A" stroke="white"/>
			</svg>
		</a>
	</div>
	<?php
	if ( ! $echo ) {
		return ob_get_clean();
	}
}
