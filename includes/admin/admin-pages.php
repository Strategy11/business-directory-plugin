<?php

/*
 * @since x.x
 */
class WPBDP_Admin_Pages {

	/*
	 * @since x.x
	 */
	public static function load_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'views_edit-wpbdp_listing', 'WPBDP_Admin_Pages::add_listings_nav' );
	}

	/*
	 * @since x.x
	 */
	public static function add_listings_nav( $views ) {
		global $post_type_object;
		add_action( 'admin_footer', 'WPBDP_Admin_Pages::show_full_footer' );

		$args = array(
			'sub'        => __( 'Listings', 'business-directory-plugin' ),
			'active_tab' => 'edit.php?post_type=wpbdp_listing',
		);

		if ( current_user_can( $post_type_object->cap->create_posts ) ) {
			$args['buttons'] = array(
				__( 'Add New Listing', 'business-directory-plugin' ) => admin_url( 'post-new.php?post_type=wpbdp_listing' ),
			);
		}

		self::show_tabs( $args );

		return $views;
	}

	/**
	 * Admin header.
	 *
	 * @since x.x
	 */
	public static function show_tabs( $args = array() ) {

		$defaults = array(
			'title'      => 'Business Directory', // Don't translate this.
			'id'         => wpbdp_get_var( array( 'param' => 'page' ) ),
			'tabs'       => array(),
			'buttons'    => array(),
			'active_tab' => self::get_active_tab(),
			'show_nav'   => true,
		);

		$args = wp_parse_args( $args, $defaults );

		$id = str_replace( array( 'wpbdp_', 'wpbdp-' ), '', $args['id'] );
		$id = str_replace( array( 'admin-', 'admin_' ), '', $id );

		$active_tab = $args['active_tab'];
		$tabs       = $args['tabs'];
		if ( empty( $tabs ) ) {
			$tabs = self::get_content_tabs();
		}
		$title = $args['title'];

	?>
	<div class="wrap wpbdp-admin wpbdp-admin-layout wpbdp-admin-page wpbdp-admin-page-<?php echo esc_attr( $id ); ?>" id="wpbdp-admin-page-<?php echo esc_attr( $id ); ?>">
		<div class="wpbdp-admin-row">
			<?php if ( $args['show_nav'] ) : ?>
				<?php include WPBDP_PATH . 'templates/admin/_admin-menu.php'; ?>
			<?php endif; ?>
			<div class="wpbdp-content-area">
			<?php
			wpbdp_admin_notices();
			if ( ! isset( $args['sub'] ) ) {
				return;
			}
			?>
			<div class="wpbdp-content-area-header">
				<h2 class="wpbdp-sub-section-title"><?php echo esc_html( $args['sub'] ); ?></h2>
				<div class="wpbdp-content-area-header-actions">
					<?php foreach ( $args['buttons'] as $label => $url ) : ?>
		                <a href="<?php echo esc_url( $url ); ?>" class="wpbdp-button-secondary">
							<?php echo esc_html( $label ); ?>
						</a>
		            <?php endforeach; ?>
				</div>
			</div>
			<div class="wpbdp-content-area-body">
			<?php
	}

	/**
	 * Includes the end div for the tabs section.
	 *
	 * @since x.x
	 */
	public static function show_full_footer() {
		self::show_tabs_footer( array( 'sub' => true ) );
	}

	/**
	 * @since x.x
	 */
	public static function show_tabs_footer( $args = array() ) {
		if ( isset( $args['sub'] ) ) {
			echo '</div>'; // end wpbdp-content-area-body
		}
		echo '</div>'; // end wpbdp-content-area
		echo '</div></div>'; // end wpbdp-admin & wpbdp-admin-row
	}

	/**
	 * Admin title.
	 *
	 * @since x.x
	 */
	public static function show_title( $args = array() ) {
		$title = isset( $args['title'] ) ? $args['title'] : '';
		$title = self::get_title( $title );

		$buttons = isset( $args['buttons'] ) ? $args['buttons'] : array();

		?>
		<h1 class="wpbdp-page-title">
			<?php WPBDP_App_Helper::show_logo( 35, 'wpbdp-logo-center', true ); ?>
			<span class="title-text"><?php echo esc_html( $title ); ?></span>

			<?php foreach ( $buttons as $label => $url ) : ?>
                <a href="<?php echo esc_url( $url ); ?>" class="add-new-h2">
					<?php echo esc_html( $label ); ?>
				</a>
            <?php endforeach; ?>
		</h1>
		<?php
	}

	/**
	 * @since x.x
	 */
	private static function get_title( $title = '' ) {
		if ( isset( $args['title'] ) ) {
			$title = $args['title'];
		}
		if ( $title ) {
			return $title;
		}

		if ( empty( $GLOBALS['title'] ) ) {
			$title = get_admin_page_title();
		} else {
			$title = $GLOBALS['title'];
		}
		return $title;
	}

	/**
	 * Admin floating notification bell.
	 *
	 * @since x.x
	 */
	public static function notification_bell() {
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
	}

	/**
	 * Display the help section icon.
	 *
	 * @todo Is this being used? Not at the moment, but its to be menu with links to Knowledge base, contact support, etc.
	 */
	public static function help_section() {
		?>
		<div class="wpbdp-admin-info-centre">
			<a class="wpbdp-admin-info-centre-icon" href="#">
				<img src="<?php echo esc_url( WPBDP_ASSETS_URL . 'images/icons/help-circle.svg' ); ?>" width="60" height="60"/>
			</a>
		</div>
		<?php
	}

	private static function get_content_tabs() {
		global $wpbdp, $submenu;

		$menu = $wpbdp->admin->get_menu();

		$tabs = array();

		if ( empty( $menu ) && empty( $submenu['wpbdp_admin'] ) ) {
			return $tabs;
		}

		$this_menu = array();
		foreach ( $submenu['wpbdp_admin'] as $sub_item ) {
			$this_menu[ $sub_item[2] ] = array(
				'title' => $sub_item[0],
			);
		}

		$this_menu = $this_menu + $menu;

		$exclude = array( 'wpbdp_settings', 'wpbdp-smtp', 'wpbdp_admin', 'wpbdp_admin_payments', 'post-new.php?post_type=wpbdp_listing', 'wpbdp-addons', 'wpbdp-themes', 'wpbdp-debug-info' );

		foreach ( $this_menu as $id => $menu_item ) {
			$requires = empty( $menu_item['capability'] ) ? 'administrator' : $menu_item['capability'];
			if ( ! current_user_can( $requires ) || in_array( $id, $exclude ) ) {
				continue;
			}

			$tabs[ $id ] = array(
				'title' => str_replace(
					__( 'Directory', 'business-directory-plugin' ) . ' ',
					'',
					strip_tags( $menu_item['title'] )
				),
				'icon'  => self::get_admin_menu_icon( $id, $menu_item ),
			);
		}

		return $tabs;
	}

	/**
	 * Set the admin menu icon with their corresponding inner locations.
	 *
	 * @param int   $menu_id The menu id.
	 * @param array $menu_item The menu item as an array.
	 *
	 * @return string
	 */
	private static function get_admin_menu_icon( $menu_id, $menu_item ) {
		$menu_icons = apply_filters( 'wpbdp_admin_menu_icons',
			array(
				'edit.php?post_type=wpbdp_listing' => 'wpbdp-admin-icon wpbdp-admin-icon-list',
				'edit-tags.php?taxonomy=wpbdp_category&amp;post_type=wpbdp_listing' => 'wpbdp-admin-icon wpbdp-admin-icon-folder',
				'edit-tags.php?taxonomy=wpbdp_tag&amp;post_type=wpbdp_listing' => 'wpbdp-admin-icon wpbdp-admin-icon-tag',
				'wpbdp-admin-fees' => 'wpbdp-admin-icon wpbdp-admin-icon-money',
				'wpbdp_admin_formfields' => 'wpbdp-admin-icon wpbdp-admin-icon-clipboard',
				'wpbdp_admin_csv' => 'wpbdp-admin-icon wpbdp-admin-icon-import',
			)
		);
		if ( isset( $menu_icons[ $menu_id ] ) ) {
			return $menu_icons[ $menu_id ];
		}
		return isset( $menu_item['icon'] ) ? $menu_item['icon'] : 'dashicons dashicons-archive';
	}

	/**
	 * Get the active tab
	 *
	 * @return string
	 */
	private static function get_active_tab() {
		if ( WPBDP_App_Helper::is_bd_post_page() ) {
			$taxonomy = wpbdp_get_var( array( 'param' => 'taxonomy' ) );
			if ( ! $taxonomy ) {
				return 'edit.php?post_type=wpbdp_listing';
			}
			return add_query_arg( 'taxonomy', $taxonomy, 'edit-tags.php?post_type=wpbdp_listing' );
		}
		return wpbdp_get_var( array( 'param' => 'page' ) );
	}
}

WPBDP_Admin_Pages::load_hooks();

function wpbdp_admin_sidebar( $echo = false ) {
	$page = wpbdp_render_page( WPBDP_PATH . 'templates/admin/sidebar.tpl.php', array(), $echo );

	if ( ! $echo ) {
		return $page;
	}

	return ! empty( $page );
}

function wpbdp_admin_header( $args_or_title = null, $id = null, $h2items = array(), $sidebar = true ) {
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
        'title'   => $default_title,
        'id'      => wpbdp_get_var( array( 'param' => 'page' ) ),
        'buttons' => array(),
        'sidebar' => true,
        'echo'    => false,
    );

	$args = wp_parse_args( $args_or_title, $defaults );

    $id = str_replace( array( 'wpbdp_', 'wpbdp-' ), '', $args['id'] );
    $id = str_replace( array( 'admin-', 'admin_' ), '', $id );

    if ( empty( $args['echo'] ) ) {
        ob_start();
    }

	WPBDP_Admin_Pages::show_tabs(
		array(
			'id'       => $id,
			'sub'      => $args['title'],
			'buttons'  => isset( $args['button'] ) ? $args['button'] : array(),
			'show_nav' => $args['sidebar'],
		)
	);

    if ( empty( $args['echo'] ) ) {
        return ob_get_clean();
    }
}

/*
 * @param bool|string Use 'echo' or true to show the footer.
 */
function wpbdp_admin_footer( $echo = false ) {
    if ( ! $echo ) {
        ob_start();
    }

	WPBDP_Admin_Pages::show_tabs_footer();

    if ( ! $echo ) {
        return ob_get_clean();
    }
}
