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

		self::taxonomy_hooks();
	}

	/**
	 * Register hooks for the category and tags page.
	 *
	 * @since x.x
	 */
	private static function taxonomy_hooks() {
		// Categories and tags.
		add_filter( 'views_edit-wpbdp_tag', 'WPBDP_Admin_Pages::add_tag_nav' );
		add_filter( 'views_edit-wpbdp_category', 'WPBDP_Admin_Pages::add_category_nav' );

		// Category and tag edit page.
		add_filter( 'wpbdp_tag_pre_edit_form', 'WPBDP_Admin_Pages::edit_tag_nav' );
		add_filter( 'wpbdp_category_pre_edit_form', 'WPBDP_Admin_Pages::edit_category_nav' );

		// Add search form.
		add_action( 'wpbdp_admin_pages_show_tabs', 'WPBDP_Admin_Pages::taxonomy_search_form', 10, 2 );

		// Add wrapper before form.
		add_action( 'wpbdp_tag_pre_add_form', 'WPBDP_Admin_Pages::taxonomy_before_form_wrapper' );
		add_action( 'wpbdp_category_pre_add_form', 'WPBDP_Admin_Pages::taxonomy_before_form_wrapper' );

		// Close button.
		add_action( 'wpbdp_tag_add_form_fields', 'WPBDP_Admin_Pages::taxonomy_close_button', 9999 );
		add_action( 'wpbdp_category_add_form_fields', 'WPBDP_Admin_Pages::taxonomy_close_button', 9999 );

		add_action( 'wpbdp_tag_add_form', 'WPBDP_Admin_Pages::taxonomy_after_form_wrapper' );
		add_action( 'wpbdp_category_add_form', 'WPBDP_Admin_Pages::taxonomy_after_form_wrapper' );
	}

	/**
	 * Add listing nav header to the post listing page.
	 *
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
				'add_listing' => array(
					'label' => __( 'Add New Listing', 'business-directory-plugin' ),
					'url'   => esc_url( admin_url( 'post-new.php?post_type=wpbdp_listing' ) ),
				),
			);
		}

		self::show_tabs( $args );

		return $views;
	}

	/**
	 * Add listing category nav.
	 *
	 * @since x.x
	 */
	public static function add_category_nav( $views ) {
		global $tax;
		$views = self::add_taxonomy_nav( $views, $tax, array(
			'title'        => __( 'Categories', 'business-directory-plugin' ),
			'taxonomy'     => 'wpbdp_category',
			'button_name'  => __( 'Add New Category', 'business-directory-plugin' ),
			'button_url'   => '#',
			'button_class' => 'wpbdp-add-taxonomy-form',
		) );
		return $views;
	}

	/**
	 * Add listing tags nav.
	 *
	 * @since x.x
	 */
	public static function add_tag_nav( $views ) {
		global $tax;
		$views = self::add_taxonomy_nav( $views, $tax, array(
			'title'        => __( 'Tags', 'business-directory-plugin' ),
			'taxonomy'     => 'wpbdp_tag',
			'button_name'  => __( 'Add New Tag', 'business-directory-plugin' ),
			'button_url'   => '#',
			'button_class' => 'wpbdp-add-taxonomy-form',
		) );
		return $views;
	}

	/**
	 * Add listing category nav.
	 *
	 * @since x.x
	 */
	public static function edit_category_nav( $views ) {
		global $tax;
		$views = self::add_taxonomy_nav( $views, $tax, array(
			'title'       => __( 'Edit Category', 'business-directory-plugin' ),
			'taxonomy'    => 'wpbdp_category',
			'button_name' => __( 'Back to Categories', 'business-directory-plugin' ),
			'button_url'  => admin_url( 'edit-tags.php?taxonomy=wpbdp_category&amp;post_type=wpbdp_listing' ),
		) );
		return $views;
	}

	/**
	 * Add listing tags nav.
	 *
	 * @since x.x
	 */
	public static function edit_tag_nav( $views ) {
		global $tax;
		$views = self::add_taxonomy_nav( $views, $tax, array(
			'title'       => __( 'Edit Tag', 'business-directory-plugin' ),
			'taxonomy'    => 'wpbdp_tag',
			'button_name' => __( 'Back To Tags', 'business-directory-plugin' ),
			'button_url'  => admin_url( 'edit-tags.php?taxonomy=wpbdp_tag&amp;post_type=wpbdp_listing' ),
		) );
		return $views;
	}


	/**
	 * Add taxonomy navigation.
	 *
	 * @since x.x
	 */
	private static function add_taxonomy_nav( $views, $tax, $params = array() ) {
		add_action( 'admin_footer', 'WPBDP_Admin_Pages::show_full_footer' );

		$args = array(
			'sub'        => $params['title'],
			'active_tab' => 'edit-tags.php?taxonomy=' . $params['taxonomy'] . '&amp;post_type=wpbdp_listing',
		);
		if ( current_user_can( $tax->cap->edit_terms ) && isset( $params['button_name'] ) ) {
			$args['buttons'] = array(
				'add_listing' => array(
					'label' => $params['button_name'],
					'url'   => $params['button_url'],
					'class' => isset( $params['button_class'] ) ? $params['button_class'] : '',
				),
			);
		}

		self::show_tabs( $args );

		return $views;
	}

	/**
	 * Search form for taxonomies.
	 *
	 * @since x.x
	 */
	public static function taxonomy_search_form( $active_tab, $id ) {
		$active_screens = array(
			'edit-wpbdp_tag',
			'edit-wpbdp_category',
		);
		$current_screen = get_current_screen();
		if ( ! in_array( $current_screen->id, $active_screens, true ) ) {
			return;
		}
		$tag_id = wpbdp_get_var( array( 'param' => 'tag_ID' ), 'get' );
		if ( $tag_id ) {
			return;
		}
		global $post_type, $taxonomy, $tax, $wp_list_table;
		$search_param = wpbdp_get_var( array( 'param' => 's' ), 'request' );
		if ( $search_param ) {
			echo '<span class="wpbdp-taxonomy-search-results">';
			printf(
				/* translators: %s: Search query. */
				// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				__( 'Search results for: %s' ),
				'<strong>' . esc_html( $search_param ) . '</strong>'
			);
			echo '</span>';
		}
		?>
		<form class="search-form wp-clearfix" method="get">
			<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>" />
			<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>" />

			<?php $wp_list_table->search_box( $tax->labels->search_items, 'tag' ); ?>
		</form>
		<?php
	}

	/**
	 * Wrapper before the taxonomy form.
	 *
	 * @since x.x
	 */
	public static function taxonomy_before_form_wrapper() {
		?>
		<div id="wpbdp-add-taxonomy-form" class="hidden settings-lite-cta">
			<div class="metabox-holder">
				<div class="postbox">
					<a href="#" class="dismiss alignright" title="<?php esc_attr_e( 'Dismiss', 'business-directory-plugin' ); ?>">
						<img src="<?php echo esc_url( WPBDP_ASSETS_URL . 'images/icons/close.svg' ); ?>" width="24" height="24"/>
					</a>
					<div class="inside">
		<?php
	}

	/**
	 * Inner create form container.
	 *
	 * @since x.x
	 */
	public static function taxonomy_opening_tag_wrapper() {
		?>
		<div class="wpbdp-add-taxonomy-form-wrapper">
		<?php
	}

	/**
	 * Footer close button
	 *
	 * @since x.x
	 */
	public static function taxonomy_close_button() {
		global $taxonomy;
		if ( 'wpbdp_category' === $taxonomy ) {
			?><div class="form-field"><?php
			WPBDP_Admin_Education::show_tip( 'categories' );
			?></div><?php
		}
		?>
		<div class="clear"></div>
		<p class="close">
			<a class="dismiss-button" href="#"><?php esc_html_e( 'Cancel', 'business-directory-plugin' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Wrapper after the taxonomy form.
	 *
	 * @since x.x
	 */
	public static function taxonomy_after_form_wrapper() {
		?>
		</form></div></div></div></div>
		<?php
	}

	/**
	 * Admin header.
	 *
	 * @since x.x
	 */
	public static function show_tabs( $args = array() ) {

		$defaults = array(
			'title'        => 'Business Directory', // Don't translate this.
			'id'           => wpbdp_get_var( array( 'param' => 'page' ) ),
			'tabs'         => array(),
			'buttons'      => array(),
			'active_tab'   => self::get_active_tab(),
			'show_nav'     => true,
			'tabbed_title' => false,
			'titles'       => array(),
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
			<div class="wpbdp-content-area-header <?php echo $args['tabbed_title'] ? 'wpbdp-content-area-header-tabbed' : '' ?>">
				<?php if ( $args['tabbed_title'] ) :
					$current_tab = isset( $args['current_tab'] ) ? $args['current_tab'] : '';
					self::show_tabbed_title( $args['titles'], $current_tab );
				else : ?>
				<h2 class="wpbdp-sub-section-title"><?php echo esc_html( $args['sub'] ); ?></h2>
				<?php endif; ?>
				<div class="wpbdp-content-area-header-actions">
					<?php self::show_buttons( $args['buttons'] ); ?>
				</div>
			</div>
			<div class="wpbdp-content-area-body">
			<?php
			do_action( 'wpbdp_admin_pages_show_tabs', $active_tab, $id );
	}

	/**
	 * Show action buttons at the top of the page.
	 *
	 * @since x.x
	 */
	private static function show_buttons( $buttons ) {
		$button_class = 'wpbdp-button-primary';
		foreach ( $buttons as $id => $button ) {
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
			$button_class = 'wpbdp-button-secondary';
		}
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
			<?php WPBDP_App_Helper::show_round_logo( 35, 'wpbdp-logo-center', true ); ?>
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
	 * Admin tabbed title.
	 *
	 * @param array $titles The titles as an array.
	 * @param string $current_tab The current selected tab.
	 *
	 * @since x.x
	 */
	public static function show_tabbed_title( $titles, $current_tab = '' ) {
		?>
		<div class="wpbdp-content-area-header-tabs">
		<?php
		foreach ( $titles as $key => $title ) : ?>
			<a class="wpbdp-header-tab <?php echo $key === $current_tab ? 'wpbdp-header-tab-active' : ''; ?>" href="<?php echo esc_url( $title['url'] ); ?>"><?php echo esc_html( $title['name'] ); ?></a>
		<?php endforeach;
		?>
		</div>
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
	 * Show the settings notice.
	 * Renders settings notice in notification area.
	 *
	 * @since x.x
	 */
	public static function settings_errors( $setting = '', $sanitize = false, $hide_on_update = false ) {
		if ( $hide_on_update && ! empty( $_GET['settings-updated'] ) ) {
			return;
		}

		$settings_errors = get_settings_errors( $setting, $sanitize );

		if ( empty( $settings_errors ) ) {
			return;
		}

		$output = '';

		foreach ( $settings_errors as $key => $details ) {
			if ( 'updated' === $details['type'] ) {
				$details['type'] = 'success';
			}

			if ( in_array( $details['type'], array( 'error', 'success', 'warning', 'info' ), true ) ) {
				$details['type'] = 'notice-' . $details['type'];
			}

			$css_id    = sprintf(
				'setting-error-%s',
				esc_attr( $details['code'] )
			);
			$css_class = sprintf(
				'notice wpbdp-notice %s settings-error is-dismissible',
				esc_attr( $details['type'] )
			);

			$output .= "<div id='$css_id' class='$css_class'> \n";
			$output .= "<p><strong>{$details['message']}</strong></p>";
			$output .= "</div> \n";
		}

		echo $output;
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

	/**
	 * Prints out all settings sections added to a particular settings page.
	 *
	 * @link https://developer.wordpress.org/reference/functions/do_settings_sections/
	 *
	 * @param string $page
	 *
	 * @since x.x
	 */
	public static function render_settings_sections( $page ) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[ $page ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
			if ( $section['title'] ) {
				echo '<div class="wpbdp-settings-form-title">';
				echo '<h3>' . esc_html( $section['title'] ) . '</h3>';
				echo '</div>';
			}

			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}

			if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
				continue;
			}
			echo '<div class="form-table wpbdp-settings-form wpbdp-grid">';
			self::render_settings_fields( $page, $section['id'] );
			echo '</div>';
		}
	}

	/**
	 * Print out the settings fields for a particular settings section.
	 *
	 * @link https://developer.wordpress.org/reference/functions/do_settings_fields/
	 *
	 * @param string $page
	 * @param string $section
	 *
	 * @since x.x
	 */
	public static function render_settings_fields( $page, $section ) {
		global $wp_settings_fields;

		if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
			return;
		}

		$hide_labels = array( 'checkbox', 'select', 'number' );

		foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
			$class = ' class="wpbdp-setting-row"';

			if ( ! empty( $field['args']['class'] ) ) {
				$class = ' class="wpbdp-setting-row ' . WPBDP_App_Helper::sanitize_html_classes( $field['args']['class'] ) . '"';
			}

			echo "<div{$class}>";

			if ( ! in_array( $field['args']['type'], $hide_labels, true ) && ! empty( $field['title'] ) ) {
				if ( ! empty( $field['args']['label_for'] ) ) {
					echo '<div class="wpbdp-setting-label"><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . $field['title'] . '</label></div>';
				} else {
					echo '<div class="wpbdp-setting-label">' . $field['title'] . '</div>';
				}
			}

			echo '<div class="wpbdp-setting-content">';
			call_user_func( $field['callback'], $field['args'] );
			echo '</div>';
			echo '</div>';
		}
	}

	private static function get_content_tabs() {
		global $wpbdp;

		$menu = $wpbdp->admin->get_menu();
		$tabs = array();

		if ( empty( $menu ) ) {
			return $tabs;
		}

		$exclude = $wpbdp->admin->top_level_nav();

		foreach ( $menu as $id => $menu_item ) {
			$requires = empty( $menu_item['capability'] ) ? 'administrator' : $menu_item['capability'];
			if ( ! current_user_can( $requires ) || in_array( $id, $exclude ) ) {
				continue;
			}

			$title = strip_tags( $menu_item['title'] );

			// change_menu_name() changes the name here. This changes it back.
			if ( $title === __( 'Directory Content', 'business-directory-plugin' ) ) {
				$title = __( 'Listings', 'business-directory-plugin' );
			}

			$tabs[ $id ] = array(
				'title' => str_replace(
					__( 'Directory', 'business-directory-plugin' ) . ' ',
					'',
					$title
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
			return add_query_arg(
				array(
					'taxonomy' => $taxonomy,
					'post_type' => 'wpbdp_listing'
				), 'edit-tags.php' );
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
		'title'        => $default_title,
		'id'           => wpbdp_get_var( array( 'param' => 'page' ) ),
		'buttons'      => array(),
		'sidebar'      => true,
		'echo'         => false,
		'tabbed_title' => false,
		'titles'       => array(),
		'current_tab'  => '',
	);

	$args = wp_parse_args( $args_or_title, $defaults );

	$id = str_replace( array( 'wpbdp_', 'wpbdp-' ), '', $args['id'] );
	$id = str_replace( array( 'admin-', 'admin_' ), '', $id );

	if ( empty( $args['echo'] ) ) {
		ob_start();
	}

	WPBDP_Admin_Pages::show_tabs(
		array(
			'id'           => $id,
			'sub'          => $args['title'],
			'buttons'      => isset( $args['buttons'] ) ? $args['buttons'] : array(),
			'show_nav'     => $args['sidebar'],
			'tabbed_title' => $args['tabbed_title'],
			'titles'       => $args['titles'],
			'current_tab'  => $args['current_tab'],
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
