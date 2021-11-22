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
	<h1>
		<?php WPBDP_App_Helper::show_logo( 55, 'rounded-circle' ); ?>
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
	<div class="wpbdp-bell-notification">
		<a class="wpbdp-bell-notification-icon" href="#">
			<svg version="1.1" width="142" height="118" viewBox="0 0 142 118" fill="none" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
				<g filter="url(#filter0_d_807_45823)">
					<rect x="54" y="30" width="48" height="48" rx="12" fill="white"/>
					<path d="M84 50C84 48.4087 83.3679 46.8826 82.2426 45.7574C81.1174 44.6321 79.5913 44 78 44C76.4087 44 74.8826 44.6321 73.7574 45.7574C72.6321 46.8826 72 48.4087 72 50C72 57 69 59 69 59H87C87 59 84 57 84 50Z" stroke="#3C4B5D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M79.7295 63C79.5537 63.3031 79.3014 63.5547 78.9978 63.7295C78.6941 63.9044 78.3499 63.9965 77.9995 63.9965C77.6492 63.9965 77.3049 63.9044 77.0013 63.7295C76.6977 63.5547 76.4453 63.3031 76.2695 63" stroke="#3C4B5D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<circle class="wpbdp-bell-notification-dot" cx="85.5" cy="46.5" r="5" fill="#FF5A5A" stroke="white"/>
				</g>
				<defs>
					<filter id="filter0_d_807_45823" x="0" y="0" width="156" height="156" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
						<feFlood flood-opacity="0" result="BackgroundImageFix"/>
						<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
						<feOffset dy="24"/>
						<feGaussianBlur stdDeviation="27"/>
						<feComposite in2="hardAlpha" operator="out"/>
						<feColorMatrix type="matrix" values="0 0 0 0 0.388235 0 0 0 0 0.435294 0 0 0 0 0.490196 0 0 0 0.4 0"/>
						<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_807_45823"/>
						<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_807_45823" result="shape"/>
					</filter>
				</defs>
			</svg>
		</a>
	</div>
	<?php
	if ( ! $echo ) {
		return ob_get_clean();
	}
}
