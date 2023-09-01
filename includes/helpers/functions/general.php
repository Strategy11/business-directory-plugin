<?php
/**
 * @package WPBDP
 */

require_once WPBDP_INC . 'helpers/functions/logging.php';
require_once WPBDP_PATH . 'includes/class-listings-api.php';
require_once WPBDP_INC . 'helpers/functions/listings.php';
require_once WPBDP_PATH . 'includes/helpers/class-listing-display-helper.php';

function wpbdp_get_version() {
	return WPBDP_VERSION;
}

/**
 * Check pages that have the shortcode and are public.
 * This is used in the main query to ensure that the views for the main pages are loaded.
 * Allow shortcodes to be used in private pages.
 *
 * @since 5.14.3
 */
function _wpbdp_page_lookup_query( $page_id, $count = false ) {
	global $wpdb;

	static $shortcodes = array(
		'main'            => array( 'businessdirectory', 'business-directory', 'WPBUSDIRMANUI' ),
		'add-listing'     => array( 'businessdirectory-submitlisting', 'WPBUSDIRMANADDLISTING' ),
		'manage-listings' => array( 'businessdirectory-managelistings', 'WPBUSDIRMANMANAGELISTING' ),
		'view-listings'   => array( 'businessdirectory-viewlistings', 'businessdirectory-listings', 'WPBUSDIRMANMVIEWLISTINGS' ),
	);

	if ( ! array_key_exists( $page_id, $shortcodes ) ) {
		return false;
	}

	if ( $count ) {
		_deprecated_argument( __FUNCTION__, '5.16.1', '$count is no longer supported' );
		$query = 'SELECT COUNT(*)';
	} else {
		$query = 'SELECT ID';
	}

	$query .= " FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status in ( 'publish', 'private' ) AND ";
	$query .= $wpdb->prepare( 'post_content REGEXP %s', '\[' . implode( '\]|\[', $shortcodes[ $page_id ] ) );

	return $query;
}

function wpbdp_get_page_ids( $page_id = 'main' ) {
	static $request_cached = array();

	if ( isset( $request_cached[ $page_id ] ) ) {
		$page_ids = $request_cached[ $page_id ];
	} else {
		$page_ids                   = wpbdp_get_page_ids_with_query( $page_id );
		$request_cached[ $page_id ] = $page_ids;
	}

	return apply_filters( 'wpbdp_get_page_ids', $page_ids, $page_id );
}

function wpbdp_get_page_ids_with_query( $page_id ) {
	// Look up for pages.
	$q = _wpbdp_page_lookup_query( $page_id );

	if ( ! $q ) {
		return null;
	}

	$q .= ' ORDER BY ID DESC ';

	if ( ! wp_using_ext_object_cache() ) {
		$results = get_transient( 'wpbdp_page_ids_' . $page_id );
		if ( false !== $results ) {
			return $results;
		}
	}

	$results = WPBDP_Utils::check_cache(
		array(
			'cache_key' => 'wpbdp_page_ids' . $page_id,
			'group'     => 'wpbdp_pages',
			'query'     => $q,
			'type'      => 'get_col',
		)
	);

	if ( ! wp_using_ext_object_cache() ) {
		set_transient( 'wpbdp_page_ids_' . $page_id, $results );
	}

	return $results;
}

function wpbdp_get_page_id( $name = 'main' ) {
	$page_ids = wpbdp_get_page_ids( $name );

	if ( ! $page_ids ) {
		$page_id = false;
	} else {
		$page_id = $page_ids[0];
	}

	return apply_filters( 'wpbdp_get_page_id', $page_id, $name );
}

/**
 * @deprecated since 4.0. Use `wpbdp_url()` instead.
 * @see wpbdp_url()
 */
function wpbdp_get_page_link( $name = 'main', $arg0 = null ) {
	//_deprecated_function( __FUNCTION__, '4.0', 'wpbdp_url' );

	$page_id = wpbdp_get_page_id( $name );

	if ( $page_id ) {
		$link = _get_page_link( $page_id );
		$link = apply_filters( 'wpbdp__get_page_link', $link, $page_id, $name, $arg0 );
	} else {
		switch ( $name ) {
			case 'view':
			case 'viewlisting':
			case 'show-listing':
			case 'showlisting':
				$link = get_permalink( intval( $arg0 ) );
				break;
			case 'edit':
			case 'editlisting':
			case 'edit-listing':
			case 'delete':
			case 'deletelisting':
			case 'delete-listing':
				break;
			case 'viewlistings':
			case 'view-listings':
				$link = wpbdp_url( 'all_listings' );
				break;
			case 'add':
			case 'addlisting':
			case 'add-listing':
			case 'submit':
			case 'submitlisting':
			case 'submit-listing':
				$link = wpbdp_url( 'submit_listing' );
				break;
			case 'search':
				$link = wpbdp_url( 'search' );
				break;
			default:
				if ( ! wpbdp_get_page_id( 'main' ) ) {
					return '';
				}

				$link = wpbdp_get_page_link( 'main' );
				break;
		}
	}

	return apply_filters( 'wpbdp_get_page_link', $link, $name, $arg0 );
}

/* Admin API */

function wpbdp_admin() {
	return wpbdp()->admin;
}

function wpbdp_admin_notices() {
	global $wpbdp;
	return $wpbdp->admin->admin_notices();
}

/* Settings API */

function wpbdp_settings_api() {
	global $wpbdp;
	return $wpbdp->settings;
}

function wpbdp_get_option( $key, $default = false ) {
	$args_ = func_get_args();
	if ( empty( wpbdp()->settings ) ) {
		return $default;
	}
	return call_user_func_array( array( wpbdp()->settings, 'get_option' ), $args_ );
}

function wpbdp_set_option( $key, $value ) {
	$args_ = func_get_args();
	return call_user_func_array( array( wpbdp()->settings, 'set_option' ), $args_ );
}

/**
 * @since 5.0
 */
function wpbdp_delete_option( $key ) {
	$args_ = func_get_args();
	return call_user_func_array( array( wpbdp()->settings, 'delete_option' ), $args_ );
}

/**
 * @since 5.0
 */
function wpbdp_register_settings_group( $args ) {
	$args_ = func_get_args();
	return call_user_func_array( array( wpbdp()->settings, 'register_group' ), $args_ );
}

/**
 * @since 5.0
 */
function wpbdp_register_setting( $args ) {
	$args_ = func_get_args();
	return call_user_func_array( array( wpbdp()->settings, 'register_setting' ), $args_ );
}

/* Form Fields API */

function wpbdp_formfields_api() {
	global $wpbdp;
	return $wpbdp->formfields;
}

function wpbdp_get_formfield( $id ) {
	if ( is_numeric( $id ) && is_string( $id ) ) {
		return wpbdp_get_formfield( intval( $id ) );
	}

	if ( is_string( $id ) ) {
		return wpbdp_formfields_api()->getFieldsByAssociation( $id, true );
	}

	return wpbdp_formfields_api()->get_field( $id );
}

/* Fees/Payment API */

/**
 * Check if payments are possible.
 * This first checks if there are available enabled premium fees, then checks if there are enabled gateways.
 *
 * @return bool
 */
function wpbdp_payments_possible() {
	if ( ! WPBDP_Fees_API::has_paid_plans() ) {
		return false;
	}
	return wpbdp()->payment_gateways->can_pay();
}

function wpbdp_fees_api() {
	return wpbdp()->fees;
}

function wpbdp_payments_api() {
	return wpbdp()->payments;
}

/* Listings API */

function wpbdp_listings_api() {
	return wpbdp()->listings;
}

/* Misc. */

function wpbdp_get_parent_categories( $catid ) {
	$category = get_term( intval( $catid ), WPBDP_CATEGORY_TAX );

	if ( $category->parent ) {
		return array_merge( array( $category ), wpbdp_get_parent_categories( $category->parent ) );
	}

	return array( $category );
}

function wpbdp_get_parent_catids( $catid ) {
	$parent_categories = wpbdp_get_parent_categories( $catid );
	array_walk(
		$parent_categories,
		function ( &$x ) {
			$x = intval( $x->term_id );
		}
	);

	return $parent_categories;
}

/**
 * Checks if permalinks are enabled.
 *
 * @return boolean
 * @since 2.1
 */
function wpbdp_rewrite_on() {
	global $wp_rewrite;
	return $wp_rewrite->permalink_structure ? true : false;
}

/**
 * Checks if a given user can perform some action to a listing.
 *
 * @param string       $action the action to be checked. available actions are 'view', 'edit', 'delete' and 'upgrade-to-sticky'
 * @param (object|int) $listing_id the listing ID. if null, the current post ID will be used
 * @param int          $user_id the user ID. if null, the current user will be used
 * @return boolean
 * @since 2.1
 */
function wpbdp_user_can( $action, $listing_id = null, $user_id = null ) {
	$listing_id = $listing_id ? ( is_object( $listing_id ) ? $listing_id->ID : intval( $listing_id ) ) : get_the_ID();
	$user_id    = $user_id ? $user_id : wp_get_current_user()->ID;
	$post       = get_post( $listing_id );

	if ( ! $post ) {
		return false;
	}

	if ( $post->post_type != WPBDP_POST_TYPE ) {
		return false;
	}

	if ( isset( $_GET['preview'] ) && ( $action != 'view' ) ) {
		return false;
	}

	$res = false;

	switch ( $action ) {
		case 'view':
			if ( isset( $_GET['preview'] ) ) {
				$res = user_can( $user_id, 'edit_others_posts' ) || ( $post->post_author && $post->post_author == $user_id );
			} else {
				$res = true;
			}
			// return apply_filters( 'wpbdp_user_can_view', true, $action, $listing_id );
			break;
		case 'flagging':
			if ( wpbdp_get_option( 'listing-flagging-register-users' ) ) {
				$res = is_user_logged_in() && false === WPBDP__Listing_Flagging::user_has_flagged( $listing_id, get_current_user_id() );
			} else {
				$res = true;
			}

			break;
		case 'edit':
		case 'delete':
			$res = user_can( $user_id, 'manage_options' );
			$res = $res || ( $user_id && $post->post_author && $post->post_author == $user_id );
			$res = $res || ( ! $user_id && wpbdp_get_option( 'enable-key-access' ) );
			break;
		default:
			break;
	}

	$res = apply_filters( 'wpbdp_user_can', $res, $action, $listing_id, $user_id );
	$res = apply_filters( 'wpbdp_user_can_' . $action, $res, $listing_id, $user_id );

	return $res;
}

function wpbdp_get_post_by_slug( $slug, $post_type = null ) {
	$post_type = $post_type ? $post_type : WPBDP_POST_TYPE;

	$posts = get_posts(
		array(
			'name'             => $slug,
			'post_type'        => $post_type,
			'post_status'      => 'publish',
			'numberposts'      => 1,
			'suppress_filters' => false,
		)
	);

	if ( $posts ) {
		return $posts[0];
	} else {
		return 0;
	}
}

function wpbdp_get_current_sort_option() {
	$sort = trim( wpbdp_get_var( array( 'param' => 'wpbdp_sort' ) ) );
	if ( $sort ) {
		$order = substr( $sort, 0, 1 ) == '-' ? 'DESC' : 'ASC';
		$sort  = ltrim( $sort, '-' );

		$obj         = new StdClass();
		$obj->option = $sort;
		$obj->order  = $order;

		return $obj;
	}

	return null;
}

/**
 * Maybe resize image.
 *
 * @param int   $id   The media attachment id.
 * @param array $args Optional. Accepts an array of width and height in pixels and crop as a boolean.
 *
 * @since 2.1.6
 * @since 5.14.3 The second parameter is added.
 */
function _wpbdp_resize_image_if_needed( $id, $args = array() ) {

	/**
	 * Add filter to allow user to skin image resizing.
	 *
	 * @param bool  $resize Whether to resize the image or not.
	 * @param int   $id     The media attachment id.
	 * @param array $args   Optional. An array of width and height in pixels and crop as a boolean.
	 *
	 * @since 5.14.3
	 */
	$resize_image = apply_filters( 'wpbdp_resize_image_if_needed', true, $id, $args );
	if ( ! $resize_image ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';

	// Check if image should be resized.
	$should_resized = _wpbdp_should_image_be_resized( $id, $args );
	if ( ! $should_resized ) {
		return;
	}

	$filename = get_attached_file( $id, true );
	if ( ! $filename ) {
		return;
	}

	$attach_data = wp_generate_attachment_metadata( $id, $filename );
	$updated     = wp_update_attachment_metadata( $id, $attach_data );
	if ( ! $updated ) {
		wpbdp_log( sprintf( 'Resize Error "%s" [ID: %d] Unable to update attachment metadata.', $filename, $id ) );
	} else {
		wpbdp_log( sprintf( 'Resized image "%s" [ID: %d] to match updated size constraints.', $filename, $id ) );
	}
}

/**
 * Check if we should resize the image.
 * This checks against the image dimensions and the database image settings.
 * If the dimensions are the same or smaller than what is saved, we skip the resize.
 *
 * @param int   $id   The media attachment id.
 * @param array $args Optional. Accepts an array of width and height in pixels and crop as a boolean.
 *
 * @since 5.14.3
 *
 * @return bool
 */
function _wpbdp_should_image_be_resized( $id, $args = array() ) {

	$metadata = wp_get_attachment_metadata( $id );

	if ( ! $metadata ) {
		return false;
	}

	$def_width = absint( isset( $args['width'] ) ? $args['width'] : wpbdp_get_option( 'thumbnail-width' ) );
	$width     = absint( $metadata['width'] );

	if ( ! $width || $width <= $def_width ) {
		return false;
	}

	$def_height = absint( isset( $args['height'] ) ? $args['height'] : wpbdp_get_option( 'thumbnail-height' ) );
	$height     = absint( $metadata['height'] );

	if ( ! $height || $height <= $def_height ) {
		return false;
	}

	$thumb_info = isset( $metadata['sizes']['wpbdp-thumb'] ) ? $metadata['sizes']['wpbdp-thumb'] : false;

	if ( ! $thumb_info ) {
		return true;
	}

	$thumb_width  = absint( $thumb_info['width'] );
	$thumb_height = absint( $thumb_info['height'] );

	// 10px of tolerance.
	if ( abs( $thumb_width - $def_width ) < 10 ) {
		return false;
	}

	$crop = isset( $args['crop'] ) ? $args['crop'] : wpbdp_get_option( 'thumbnail-crop' );

	if ( $crop && abs( $thumb_height - $def_height ) < 10 ) {
		return false;
	}

	return true;
}

/**
 * @since 3.6.10
 */
function wpbdp_currency_format( $amount, $args = array() ) {
	return WPBDP_Currency_Helper::currency_format( $amount, $args );
}

/**
 * @since 5.1.9
 */
function wpbdp_date_full_format( $timestamp ) {
	return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
}

/**
 * @since 5.1.9
 */
function wpbdp_date( $timestamp ) {
	return date_i18n( get_option( 'date_format' ), $timestamp );
}


/**
 * @since 3.5.3
 */
function wpbdp_get_post_by_id_or_slug( $id_or_slug = false, $try_first = 'id', $result = 'post' ) {
	if ( 'slug' === $try_first ) {
		$strategies = array( 'post_name', 'ID' );
	} else {
		$strategies = is_numeric( $id_or_slug ) ? array( 'ID', 'post_name' ) : array( 'post_name' );
	}

	global $wpdb;
	$listing_id = 0;

	foreach ( $strategies as $s ) {
		$q = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE $s = %s AND post_type = %s", $id_or_slug, WPBDP_POST_TYPE );

		$listing_id = WPBDP_Utils::check_cache(
			array(
				'cache_key' => 'get_id_' . $id_or_slug,
				'group'     => 'wpbdp_listings',
				'query'     => $q,
				'type'      => 'get_var',
			)
		);
		$listing_id = intval( $listing_id );

		if ( $listing_id ) {
			break;
		}
	}

	if ( ! $listing_id ) {
		return null;
	}

	if ( 'id' == $result ) {
		return $listing_id;
	}

	return get_post( $listing_id );
}

/**
 * @since 3.5.8
 */
function wpbdp_push_query( &$q ) {
	global $wpbdp;

	$wpbdp->_query_stack[] = $q;
}

/**
 * @since 3.5.8
 */
function wpbdp_pop_query() {
	global $wpbdp;
	return array_pop( $wpbdp->_query_stack );
}

/**
 * @since 3.5.8
 */
function wpbdp_current_query() {
	global $wpbdp;

	$len = count( $wpbdp->_query_stack );

	if ( $len == 0 ) {
		return null;
	}

	return $wpbdp->_query_stack[ $len - 1 ];
}

/**
 * @since 3.6.10
 */
function wpbdp_experimental( $feature ) {
	static $file_overrides = false;
	global $wpbdp_development;

	if ( file_exists( WPBDP_PATH . 'experimental' ) ) {
		$file_overrides = explode( ',', trim( file_get_contents( WPBDP_PATH . 'experimental' ) ) );
	}

	$res = false;
	if ( isset( $wpbdp_development ) ) {
		$res = $wpbdp_development->option_get( $feature );
	}

	if ( $file_overrides && in_array( $feature, $file_overrides, true ) ) {
		$res = true;
	}

	return $res;
}

/**
 * @since 4.0
 */
function wpbdp_current_view_output() {
	global $wpbdp;
	return $wpbdp->dispatcher->current_view_output();
}

/**
 * @since 4.0
 */
function wpbdp_url( $pathorview = '/', $args = array() ) {
	if ( $pathorview === 'admin' ) {
		// If this is an admin page, bail early to avoid extra database calls.
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );
		return apply_filters( 'wpbdp_url', $url, $pathorview, $args );
	}

	$base_id  = wpbdp_get_page_id( 'main' );
	$base_url = _get_page_link( $base_id );
	$base_url = apply_filters( 'wpbdp_url_base_url', $base_url, $base_id, $pathorview, $args );
	$url      = '';

	switch ( $pathorview ) {
		case 'submit_listing':
		case 'all_listings':
		case 'view_listings':
		case 'manage_listings':
		case 'search':
		case 'login':
		case 'request_access_keys':
			$url = add_query_arg( 'wpbdp_view', $pathorview, $base_url );
			break;
		case 'flag_listing':
		case 'delete_listing':
		case 'edit_listing':
		case 'listing_contact':
			$url = add_query_arg(
				array(
					'wpbdp_view' => $pathorview,
					'listing_id' => $args,
				),
				$base_url
			);
			break;
		case 'renew_listing':
			$url = add_query_arg(
				array(
					'wpbdp_view' => $pathorview,
					'renewal_id' => $args,
				),
				$base_url
			);
			break;
		case 'main':
		case '/':
			$url = $base_url;
			break;
		case 'checkout':
			$url = $base_url;
			$url = add_query_arg(
				array(
					'wpbdp_view' => 'checkout',
					'payment'    => $args,
				),
				$base_url
			);
			break;
		default:
			if ( wpbdp_starts_with( $pathorview, '/' ) ) {
				$url = rtrim( wpbdp_url( '/' ), '/' ) . '/' . substr( $pathorview, 1 );
			}

			break;
	}

	$url = apply_filters( 'wpbdp_url', $url, $pathorview, $args );
	return $url;
}

/**
 * Generates Ajax URL and allows plugins to alter it through a filter.
 *
 * @since 5.0.3
 */
function wpbdp_ajax_url() {
	return apply_filters( 'wpbdp_ajax_url', admin_url( 'admin-ajax.php' ) );
}

/**
 * TODO: update before themes-release
 * TODO: Sometimes this functions is called from
 *       WPBDP_WPML_Compat->language_switcher even though no category
 *       is available thorugh get_queried_object(), triggering a
 *       "Trying to get property of non-object" notice.
 *
 *       The is_object() if-statement that is commented out below can prevent
 *       the notice, but the real issue is the fact that the plugin thinks
 *       we are showing a category while the main query has no queried object.
 *
 *       If the rewrite rule for a cateagry matches, but we can't retrieve
 *       a term from the database, we should mark the query as not-found
 *       from the beginning.
 */
function wpbdp_current_category_id() {
	global $wp_query;

	if ( empty( $wp_query->wpbdp_is_category ) ) {
		return false;
	}

	/** @var WP_Query $wp_query */
	$term = $wp_query->get_queried_object();

	return $term->term_id;
}

/**
 * @since 4.1.12
 */
function _wpbdp_current_category_id() {
	$term = _wpbpd_current_category();

	if ( ! $term ) {
		return null;
	}

	return $term->term_id;
}

/**
 * @since 4.1.12
 */
function _wpbpd_current_category() {
	global $wp_query;

	if ( $wp_query->wpbdp_is_category ) {
		/** @var WP_Query $wp_query */
		$term = $wp_query->get_queried_object();
	} else {
		$term = null;
	}

	if ( ! $term ) {
		$category_id = get_query_var( '_' . wpbdp_get_option( 'permalinks-category-slug' ) );

		if ( $category_id ) {
			$term = get_term_by( 'slug', $category_id, WPBDP_CATEGORY_TAX );
		}
	}

	if ( ! $term ) {
		$category_id = get_query_var( 'category_id' );

		if ( $category_id ) {
			$term = get_term_by( 'id', $category_id, WPBDP_CATEGORY_TAX );
		}
	}

	return $term;
}

function wpbdp_current_tag_id() {
	global $wp_query;

	if ( empty( $wp_query->wpbdp_is_tag ) ) {
		return false;
	}

	/** @var WP_Query $wp_query */
	$term = $wp_query->get_queried_object();
	return $term->term_id;
}

function wpbdp_current_action() {
	return wpbdp_current_view();
}

// TODO: how to implement now with CPT? (themes-release)

function wpbdp_current_listing_id() {
	return 0;
}

/**
 * @since 4.0
 */
function wpbdp_current_view() {
	global $wpbdp;

	if ( ! isset( $wpbdp->dispatcher ) || ! is_object( $wpbdp->dispatcher ) ) {
		return '';
	}

	return $wpbdp->dispatcher->current_view();
}

/**
 * @since 4.0
 */
function wpbdp_load_view( $view, $arg0 = null ) {
	global $wpbdp;
	return $wpbdp->dispatcher->load_view( $view, $arg0 );
}

function wpbdp_get_payment( $id ) {
	return WPBDP_Payment::objects()->get( $id );
}

/**
 * @since 5.0
 */
function wpbdp_get_fee_plans( $args = array() ) {
	global $wpdb;
	$payments_on = wpbdp_payments_possible();
	$defaults    = array(
		'include_free'    => ! $payments_on,
		'enabled'         => 1,
		'tag'             => '',
		'orderby'         => 'label',
		'order'           => 'ASC',
		'categories'      => array(),
		'include_private' => false,
		'admin_view'      => false,
	);

	$order = wpbdp_get_option( 'fee-order' );
	if ( $order ) {
		$defaults['orderby'] = ( 'custom' == $order['method'] ) ? 'weight' : $order['method'];
		$defaults['order']   = ( 'custom' == $order['method'] ) ? 'DESC' : $order['order'];
	}

	$args = wp_parse_args( $args, $defaults );
	$args = apply_filters( 'wpbdp_get_fee_plans_args', $args );
	// Add payments status in arguments to refresh the cache based on payment status.
	$args['payments_on'] = $payments_on;

	$where = '1=1';
	if ( 'all' !== $args['enabled'] ) {
		$where .= $wpdb->prepare( ' AND p.enabled = %d ', (bool) $args['enabled'] );
	}

	if ( ! $args['admin_view'] && ! $payments_on ) {
		$where .= $wpdb->prepare( ' AND p.amount = %d ', 0 );
	}

	if ( $args['tag'] ) {
		$where .= $wpdb->prepare( ' AND p.tag = %s', $args['tag'] );
	}

	$categories = $args['categories'];
	if ( ! empty( $categories ) ) {
		if ( ! is_array( $categories ) ) {
			$categories = array( $categories );
		}

		$categories = array_map( 'absint', $categories );
	}

	$order   = strtoupper( $args['order'] );
	$orderby = $args['orderby'];
	$query   = "SELECT p.id FROM {$wpdb->prefix}wpbdp_plans p WHERE {$where} ORDER BY {$orderby} {$order}";

	$plan_ids = WPBDP_Utils::check_cache(
		array(
			'cache_key' => json_encode( $args ),
			'group'     => 'wpbdp_plans',
			'query'     => $query,
			'type'      => 'get_col',
		)
	);

	$plan_ids = apply_filters( 'wpbdp_pre_get_fee_plans', $plan_ids );

	$plans = array();
	foreach ( $plan_ids as $plan_id ) {
		if ( $plan = wpbdp_get_fee_plan( $plan_id ) ) {
			if ( $categories && ! $plan->supports_category_selection( $categories ) ) {
				continue;
			}
			if ( ! $args['include_private'] && ! empty( $plan->extra_data['private'] ) && ! current_user_can( 'manage_options' ) ) {
				continue;
			}
			$plans[] = $plan;
		}
	}

	$plans = apply_filters( 'wpbdp_get_fee_plans', $plans );

	return $plans;
}

/**
 * @since 5.0
 */
function wpbdp_get_fee_plan( $plan_id ) {
	global $wpdb;

	if ( 0 === $plan_id || 'free' === $plan_id ) {
		$plan_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}wpbdp_plans WHERE tag = %s", 'free' ) ) );
	}

	$plan_id = absint( $plan_id );

	return WPBDP__Fee_Plan::get_instance( $plan_id );
}

/**
 * @since 4.1.8
 */
function wpbdp_is_taxonomy() {
	$current_view = wpbdp_current_view();
	$is_taxonomy  = in_array( $current_view, array( 'show_category', 'show_tag' ), true );

	return apply_filters( 'wpbdp_is_taxonomy', $is_taxonomy, $current_view );
}

/**
 * @since 5.5.2
 */
function wpbdp_get_taxonomy_link( $taxonomy, $link = '' ) {
	$taxonomy_type = '';

	switch ( $taxonomy->taxonomy ) {
		case WPBDP_CATEGORY_TAX:
			$taxonomy_type = 'category';
			break;
		case WPBDP_TAGS_TAX:
			$taxonomy_type = 'tags';
			break;
	}

	if ( ! $taxonomy_type ) {
		return $link;
	}

	if ( ! wpbdp_rewrite_on() ) {
		if ( wpbdp_get_option( 'disable-cpt' ) ) {
			return wpbdp_url( '/' ) . '&_' . wpbdp_get_option( 'permalinks-' . $taxonomy_type . '-slug' ) . '=' . $taxonomy->slug;
		}

		return $link ? $link : get_category_link( $taxonomy->term_id );
	}

	return wpbdp_url( sprintf( '/%s/%s/', wpbdp_get_option( 'permalinks-' . $taxonomy_type . '-slug' ), $taxonomy->slug ) );
}

function wpbdp_render_page( $template, $vars = array(), $echo_output = false ) {
	if ( empty( $template ) ) {
		return '';
	}

	if ( $vars ) {
		extract( $vars );
	}

	ob_start();
	include $template;
	$html = ob_get_contents();
	ob_end_clean();

	if ( $echo_output ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}

	return $html;
}

function wpbdp_locate_template( $template, $allow_override = true, $try_defaults = true ) {
	$template_file = '';

	if ( ! is_array( $template ) ) {
		$template = array( $template );
	}

	if ( $allow_override ) {
		global $wpbdp;

		$search_for    = array();
		$template_file = '';

		foreach ( $template as $t ) {
			$template_file = $wpbdp->themes->locate_template( $t );
			if ( $template_file ) {
				break;
			}

			// These file checks could be a little risky and get unintended results.
			if ( wpbdp_get_option( 'disable-cpt' ) ) {
				$search_for[] = $t . '.tpl.php';
				$search_for[] = $t . '.php';
				$search_for[] = 'single/' . $t . '.tpl.php';
				$search_for[] = 'single/' . $t . '.php';
			}
		}

		// Check for the template in the WP theme.
		if ( empty( $template_file ) ) {
			$template_file = locate_template( $search_for );
		}
	}

	if ( $template_file && ! $try_defaults ) {
		_deprecated_argument( __FUNCTION__, '5.13.2', 'Defaults are always checked here. Use $wpbdp->themes->template_has_override' );

		// Temporary reverse compatibility: The BD folder was checked when it shouldn't be. Remove it.
		if ( strpos( $template_file, WPBDP_TEMPLATES_PATH ) !== false ) {
			$template_file = '';
		}
	} elseif ( ! $allow_override ) {
		// Only get the core file if it hasn't already been checked.
		foreach ( $template as $t ) {
			$template_path = WPBDP_TEMPLATES_PATH . '/' . $t . '.tpl.php';

			if ( file_exists( $template_path ) ) {
				$template_file = $template_path;
				break;
			}
		}
	}

	return $template_file;
}

function wpbdp_render( $template, $vars = array(), $allow_override = true ) {
	$vars          = wp_parse_args(
		$vars,
		array(
			'__page__' => array(
				'class'          => array(),
				'content_class'  => array(),
				'before_content' => '',
			),
		)
	);
	$template_name = is_array( $template ) ? $template[0] : $template;
	$vars          = apply_filters( 'wpbdp_template_vars', $vars, $template_name );

	$content = wpbdp_render_page( wpbdp_locate_template( $template, $allow_override ), $vars, false );
	$content = apply_filters( "wpbdp_render_{$template_name}", $content );

	WPBDP_App_Helper::add_theme_button_class( $content );

	if ( isset( $vars['echo'] ) ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $content;
	}
	return $content;
}

function wpbdp_render_msg( $msg, $type = 'status', $echo = false ) {
	$classes = array( 'wpbdp-msg', $type );
	if ( is_admin() ) {
		$classes[] = 'wpbdp-notice notice';
		$classes[] = 'notice-' . $type;
	}
	$msg = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . wp_kses_post( $msg ) . '</div>';
	if ( $echo ) {
		echo $msg;
	}

	return $msg;
}

function _wpbdp_template_mode( $template ) {
	global $wpbdp;

	$has_override = $wpbdp->themes->template_has_override( 'businessdirectory-' . $template ) || $wpbdp->themes->template_has_override( 'wpbusdirman-' . $template );
	if ( $has_override ) {
		return 'template';
	}
	return 'page';
}


/**
 * Displays a single listing view taking into account all of the theme overrides.
 *
 * @param mixed  $listing_id listing object or listing id to display.
 * @param string $view 'single' for single view or 'excerpt' for summary view.
 * @return string HTML output.
 */
function wpbdp_render_listing( $listing_id = null, $view = 'single', $echo = false ) {
	$listing_id = $listing_id ? ( is_object( $listing_id ) ? $listing_id->ID : absint( $listing_id ) ) : get_the_ID();

	$args = array(
		'post_type' => WPBDP_POST_TYPE,
		'p'         => $listing_id,
	);
	if ( ! current_user_can( 'edit_posts' ) ) {
		$args['post_status'] = 'publish';
	}

	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) {
		return '';
	}

	$q->the_post();

	if ( 'excerpt' === $view ) {
		$html = WPBDP_Listing_Display_Helper::excerpt();
	} else {
		$html = WPBDP_Listing_Display_Helper::single();
	}

	if ( $echo ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}

	wp_reset_postdata();

	return $html;
}

function wpbdp_latest_listings( $n = 10, $before = '<ul>', $after = '</ul>', $before_item = '<li>', $after_item = '</li>' ) {
	$n = max( intval( $n ), 0 );

	$posts = get_posts(
		array(
			'post_type'        => WPBDP_POST_TYPE,
			'post_status'      => 'publish',
			'numberposts'      => $n,
			'orderby'          => 'date',
			'suppress_filters' => false,
		)
	);

	$html = '';

	$html .= $before;

	foreach ( $posts as $post ) {
		$html .= $before_item;
		$html .= sprintf( '<a href="%s">%s</a>', get_permalink( $post->ID ), get_the_title( $post->ID ) );
		$html .= $after_item;
	}

	$html .= $after;

	return $html;
}

/**
 * @since 4.0
 */
function wpbdp_the_listing_actions() {
	echo wpbdp_listing_actions();
}

/**
 * @since 4.0
 */
function wpbdp_listing_actions() {
	return wpbdp_render(
		'parts/listing-buttons',
		array(
			'listing_id' => get_the_ID(),
			'view'       => 'excerpt',
		),
		false
	);
}

function wpbdp_sortbar_get_field_options() {
	$options = array();

	foreach ( wpbdp_get_form_fields() as $field ) {
		if ( in_array( $field->get_field_type_id(), array( 'image', 'social-network', 'multiselect', 'checkbox', 'url' ) ) || in_array( $field->get_association(), array( 'category', 'tags', 'regions' ) ) ) {
			continue;
		}

		$options[ $field->get_id() ] = apply_filters( 'wpbdp_render_field_label', $field->get_label(), $field );
	}

	$options = apply_filters( 'wpbdp_sortbar_get_field_options', $options );

	$options['user_login']      = _x( 'User', 'admin settings', 'business-directory-plugin' );
	$options['user_registered'] = _x( 'User registration date', 'admin settings', 'business-directory-plugin' );
	$options['date']            = _x( 'Date posted', 'admin settings', 'business-directory-plugin' );
	$options['modified']        = _x( 'Date last modified', 'admin settings', 'business-directory-plugin' );

	return $options;
}

/**
 * Returns the admin edit link for the listing.
 *
 * @param int $listing_id the listing ID
 * @return string The admin edit link for the listing (if available).
 * @since 5.1.3
 */
function wpbdp_get_edit_post_link( $listing_id ) {
	if ( ! $post = get_post( $listing_id ) ) {
		return '';
	}

	$post_type_object = get_post_type_object( $post->post_type );
	if ( ! $post_type_object ) {
		return '';
	}

	if ( $post_type_object->_edit_link ) {
		$link = admin_url( sprintf( $post_type_object->_edit_link . '&action=edit', $post->ID ) );
	} else {
		$link = '';
	}

	return $link;
}

/**
 * @since 5.1.6
 */
function wpbdp_get_client_ip_address() {
	$ip = '0.0.0.0';

	$check_vars = array( 'REMOTE_ADDR', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR' );

	foreach ( $check_vars as $varname ) {
		$value = wpbdp_get_server_value( $varname );
		if ( $value ) {
			return $value;
		}
	}

	return $ip;
}

/**
 * @since 5.2.1
 */
function wpbdp_delete_page_ids_cache() {
	WPBDP_Utils::cache_delete_group( 'wpbdp_pages' );
	// Delete page transient cache for the main plugin pages.
	delete_transient( 'wpbdp_page_ids_main' );
	delete_transient( 'wpbdp_page_ids_add-listing' );
	delete_transient( 'wpbdp_page_ids_manage-listings' );
	delete_transient( 'wpbdp_page_ids_view-listings' );
}

/**
 * Echoes a link to return to previous page.
 *
 * @since 5.5.2
 */
function wpbdp_get_return_link() {
	$referer      = ! empty( $_SERVER['HTTP_REFERER'] ) ? filter_var( wpbdp_get_server_value( 'HTTP_REFERER' ), FILTER_VALIDATE_URL ) : '';
	$msg          = '';
	$referer_vars = array();

	if ( $referer ) {
		wp_parse_str( wp_parse_url( $referer, PHP_URL_QUERY ), $referer_vars );
	} else {
		$msg     = __( 'Return to Directory', 'business-directory-plugin' );
		$referer = wpbdp_url( '/' );
	}

	if ( $referer_vars && isset( $referer_vars['wpbdp_view'] ) ) {
		if ( 'search' === $referer_vars['wpbdp_view'] ) {
			$msg = _x( 'Return to results', 'templates', 'business-directory-plugin' );
		}

		if ( 'all_listings' === $referer_vars['wpbdp_view'] ) {
			$msg = __( 'Go back', 'business-directory-plugin' );
		}
	}

	if ( strpos( $referer, wpbdp_get_option( 'permalinks-category-slug' ) ) || strpos( $referer, wpbdp_get_option( 'permalinks-tags-slug' ) ) ) {
		$msg = __( 'Go back', 'business-directory-plugin' );
	}

	if ( $msg ) {
		echo '<span class="wpbdp-goback"><a href="' . esc_url( $referer ) . '" >' . esc_html( $msg ) . '</a></span>';
	}
}

/**
 * @since 5.6.3
 */
function wpbdp_users_dropdown() {
	$users = get_users( array( 'fields' => array( 'ID', 'user_login' ) ) );
	$res   = array();

	foreach ( $users as $u ) {
		$res[ $u->ID ] = $u->user_login;
	}

	return $res;
}

/**
 * Check if user is admin.
 *
 * @return bool Whether user is admin.
 */
function current_user_is_admin() {
 	return current_user_can( 'manage_options' );
}

/**
 * Check if user has specific role.
 *
 * @return bool Whether user has the role.
 */
function current_user_has_role( $role ){
	$user = wp_get_current_user();
	return in_array( $role, $user->roles );
}

/**
 * Restructures multidimensional $_FILES arrays into one key-based array per file.
 * Single-file arrays are returned as an array of one item for consistency.
 *
 * @since 3.4
 *
 * @param array $files $_FILES array
 * @return array
 */
function wpbdp_flatten_files_array( $files = array() ) {
	if ( ! isset( $files['tmp_name'] ) ) {
		return $files;
	}

	if ( ! is_array( $files['tmp_name'] ) ) {
		return array( $files );
	}

	$res = array();
	foreach ( $files as $k1 => $v1 ) {
		foreach ( $v1 as $k2 => $v2 ) {
			$res[ $k2 ][ $k1 ] = $v2;
		}
	}

	return $res;
}


/**
 * Returns properties and array values from objects or arrays, resp.
 *
 * @param array|object $dict
 * @param string|int   $key Property name or array key.
 * @param mixed        $default Optional. Defaults to `false`.
 */
function wpbdp_getv( $dict, $key, $default = false ) {
	$_dict = is_object( $dict ) ? (array) $dict : $dict;

	if ( is_array( $_dict ) && isset( $_dict[ $key ] ) ) {
		return $_dict[ $key ];
	}

	return $default;
}

/**
 * Get any value from the $_SERVER
 *
 * @since 5.7.6
 *
 * @param string $value
 *
 * @return string
 */
function wpbdp_get_server_value( $value ) {
	return isset( $_SERVER[ $value ] ) ? wp_strip_all_tags( wp_unslash( $_SERVER[ $value ] ) ) : '';
}

/**
 * @since 5.7.6
 *
 * @param array $args - Includes 'param' and 'sanitize'.
 *
 * @return array|string|int|float|mixed
 */
function wpbdp_get_var( $args, $type = 'get' ) {
	$defaults = array(
		'sanitize' => 'sanitize_text_field',
		'default'  => '',
	);
	$args     = wp_parse_args( $args, $defaults );
	$value    = $args['default'];
	if ( $type === 'get' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = isset( $_GET[ $args['param'] ] ) ? wp_unslash( $_GET[ $args['param'] ] ) : $value;
	} elseif ( $type === 'post' ) {
		// phpcs:ignore Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = isset( $_POST[ $args['param'] ] ) ? wp_unslash( $_POST[ $args['param'] ] ) : $value;
	} else {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = isset( $_REQUEST[ $args['param'] ] ) ? wp_unslash( $_REQUEST[ $args['param'] ] ) : $value;
	}

	wpbdp_sanitize_value( $args['sanitize'], $value );

	return $value;
}

/**
 * @since 5.7.6
 *
 * @param string $sanitize
 * @param array|string $value
 */
function wpbdp_sanitize_value( $sanitize, &$value ) {
	if ( empty( $sanitize ) ) {
		return;
	}
	if ( is_array( $value ) ) {
		$temp_values = $value;
		foreach ( $temp_values as $k => $v ) {
			wpbdp_sanitize_value( $sanitize, $value[ $k ] );
		}
	} else {
		$value = call_user_func( $sanitize, $value );
	}
}

function wpbdp_capture_action( $hook ) {
	$output = '';

	$args = func_get_args();
	if ( count( $args ) > 1 ) {
		$args = array_slice( $args, 1 );
	} else {
		$args = array();
	}

	ob_start();
	do_action_ref_array( $hook, $args );
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}

function wpbdp_capture_action_array( $hook, $args = array() ) {
	$output = '';

	ob_start();
	do_action_ref_array( $hook, $args );
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}

function wpbdp_php_ini_size_to_bytes( $val ) {
	$val  = trim( $val );
	$size = intval( $val );
	$unit = strtoupper( $val[ strlen( $val ) - 1 ] );

	switch ( $unit ) {
		case 'G':
			$size *= 1024;
		// no break
		case 'M':
			$size *= 1024;
		// no break
		case 'K':
			$size *= 1024;
	}

	return $size;
}

function wpbdp_media_upload_check_env( &$error ) {
	if ( empty( $_FILES ) && empty( $_POST ) && isset( $_SERVER['REQUEST_METHOD'] ) &&
		strtolower( wpbdp_get_server_value( 'REQUEST_METHOD' ) ) === 'post' ) {
		$post_max    = wpbdp_php_ini_size_to_bytes( ini_get( 'post_max_size' ) );
		$posted_size = intval( wpbdp_get_server_value( 'CONTENT_LENGTH' ) );

		if ( $posted_size > $post_max ) {
			$error = _x( 'POSTed data exceeds PHP config. maximum. See "post_max_size" directive.', 'utils', 'business-directory-plugin' );
			return false;
		}
	}

	return true;
}

/**
 * @since 2.1.6
 */
function wpbdp_media_upload( $file_, $use_media_library = true, $check_image = false, $constraints = array(), &$error_msg = null, $sideload = false ) {
	return WPBDP_Utils::media_upload( $file_, $use_media_library, $check_image, $constraints, $error_msg, $sideload );
}

/**
 * Attempts to get the mimetype of a file.
 *
 * @param string $file The path to a file.
 *
 * @since 5.0.5
 */
function wpbdp_get_mimetype( $file ) {
	return WPBDP_Utils::get_mimetype( $file );
}

/**
 * Returns the domain used in the current request, optionally stripping
 * the www part of the domain.
 *
 * @since 2.1.5
 * @param boolean $www true to include the 'www' part.
 */
function wpbdp_get_current_domain( $www = true, $prefix = '' ) {
	$domain = wpbdp_get_server_value( 'HTTP_HOST' );
	if ( empty( $domain ) ) {
		$domain = wpbdp_get_server_value( 'SERVER_NAME' );
	}

	if ( ! $www && substr( $domain, 0, 4 ) === 'www.' ) {
		$domain = $prefix . substr( $domain, 4 );
	}

	return $domain;
}

/**
 * Prepare an external link with utm parameters.
 *
 * @since 5.7.5
 *
 * @param array|string $args
 * @param string $page
 */
function wpbdp_admin_upgrade_link( $args, $page = '' ) {
	if ( empty( $page ) ) {
		$page = 'https://businessdirectoryplugin.com/lite-upgrade/';
	} else {
		$page = str_replace( 'https://businessdirectoryplugin.com/', '', $page );
		$page = 'https://businessdirectoryplugin.com/' . $page;
	}

	$anchor = '';
	if ( is_array( $args ) ) {
		$medium  = $args['medium'];
		$content = $args['content'];
		if ( isset( $args['anchor'] ) ) {
			$anchor = '#' . $args['anchor'];
		}
	} else {
		$medium = $args;
	}

	$query_args = array(
		'utm_source'   => 'WordPress',
		'utm_medium'   => $medium,
		'utm_campaign' => 'liteplugin',
	);

	if ( isset( $content ) ) {
		$query_args['utm_content'] = $content;
	}

	if ( is_array( $args ) && isset( $args['param'] ) ) {
		$query_args['f'] = $args['param'];
	}

	$link = add_query_arg( $query_args, $page ) . $anchor;
	return $link;
}

/**
 * Bulds WordPress ajax URL using the same domain used in the current request.
 *
 * @since 2.1.5
 */
function wpbdp_ajaxurl( $overwrite = false ) {
	static $ajaxurl = false;

	if ( $overwrite || $ajaxurl === false ) {
		$url   = admin_url( 'admin-ajax.php' );
		$parts = parse_url( $url );

		$domain = wpbdp_get_current_domain();

		// Since $domain already contains the port remove it.
		if ( isset( $parts['port'] ) && $parts['port'] ) {
			$domain = str_replace( ':' . $parts['port'], '', $domain );
		}

		$ajaxurl = str_replace( $parts['host'], $domain, $url );
	}

	return $ajaxurl;
}

/**
 * Removes a value from an array.
 *
 * @since 2.3
 */
function wpbdp_array_remove_value( &$array_, &$value_ ) {
	$key = array_search( $value_, $array_ );

	if ( $key !== false ) {
		unset( $array_[ $key ] );
	}

	return true;
}

/**
 * Checks if a given string starts with another string.
 *
 * @param string $str the string to be searched
 * @param string $prefix the prefix to search for
 * @return bool  true if $str starts with $prefix or FALSE otherwise
 * @since 3.0.3
 */
function wpbdp_starts_with( $str, $prefix, $case_sensitive = true ) {
	if ( ! $case_sensitive ) {
		return stripos( $str, $prefix, 0 ) === 0;
	}

	return strpos( $str, $prefix, 0 ) === 0;
}

/**
 * @since 3.1
 */
function wpbdp_format_time( $time = null, $format = 'mysql', $time_is_date = false ) {
	if ( $format === 'mysql' ) {
		$time = date( 'Y-m-d H:i:s', $time );
	}

	return $time;
}

/**
 * Returns the contents of a directory (ignoring . and .. special files).
 *
 * @param string $path a directory.
 * @return array list of files within the directory.
 * @since 3.3
 */
function wpbdp_scandir( $path, $args = array() ) {
	if ( ! is_dir( $path ) ) {
		return array();
	}

	$files = scandir( $path );
	if ( empty( $files ) ) {
		return array();
	}

	return array_diff( $files, array( '.', '..' ) );
}

/**
 * Returns the name of a term.
 *
 * @param int|string $id_or_slug The term ID or slug (see `$field`).
 * @param string     $taxonomy Taxonomy name. Defaults to `WPBDP_CATEGORY_TAX` (BD's category taxonomy).
 * @param string     $field Field used for the term lookup. Defaults to "id".
 * @param boolean    $escape Whether to escape the name before returning or not. Defaults to `True`.
 *
 * @return string The term name (if found) or an empty string otherwise.
 * @since 3.3
 */
function wpbdp_get_term_name( $id_or_slug, $taxonomy = WPBDP_CATEGORY_TAX, $field = 'id', $escape = true ) {
	$term = get_term_by(
		$field,
		'id' == $field ? intval( $id_or_slug ) : $id_or_slug,
		$taxonomy
	);

	if ( ! $term ) {
		return '';
	}

	return $term->name;
}

function wpbdp_has_shortcode( $content, $shortcode ) {
	$check = has_shortcode( $content, $shortcode );

	if ( ! $check ) {
		// Sometimes has_shortcode() fails so we try another approach.
		if ( false !== stripos( $content, '[' . $shortcode . ']' ) ) {
			$check = true;
		}
	}

	return $check;
}

/**
 * TODO: dodoc.
 *
 * @since 3.4.2
 */
function wpbdp_text_from_template( $setting_name, $replacements = array() ) {
	$setting = wpbdp()->settings->get_setting( $setting_name );

	if ( ! $setting ) {
		return false;
	}

	$text = wpbdp_get_option( $setting_name );

	if ( ! $text ) {
		return false;
	}

	$placeholders = isset( $setting['placeholders'] ) ? array_keys( $setting['placeholders'] ) : array();

	foreach ( $replacements as $pholder => $repl ) {
		if ( ! in_array( $pholder, $placeholders, true ) ) {
			continue;
		}

		$text = str_replace( '[' . $pholder . ']', $repl, $text );
	}

	return $text;
}

/**
 * @since 3.5.4
 */
function wpbdp_email_from_template( $setting_or_file, $replacements = array(), $args = array() ) {
	$setting = null;
	$file    = null;
	$object  = null;

	if ( is_string( $setting_or_file ) && is_file( $setting_or_file ) && is_readable( $setting_or_file ) ) {
		$file = $setting_or_file;
	} elseif ( is_array( $setting_or_file ) || is_object( $setting_or_file ) ) {
		$object = $setting_or_file;
	} else {
		$setting = wpbdp()->settings->get_setting( $setting_or_file );
	}

	if ( ( ! $setting && ! $file && ! $object ) || ( $setting && 'email_template' != $setting['type'] ) ) {
		return false;
	}

	if ( ! class_exists( 'WPBDP_Email' ) ) {
		require_once WPBDP_PATH . 'includes/helpers/class-email.php';
	}

	$placeholders = $setting ? ( isset( $setting['placeholders'] ) && is_array( $setting['placeholders'] ) ? $setting['placeholders'] : array() ) : array();

	// Add core replacements.
	$replacements = array_merge(
		$replacements,
		array(
			'site-title'    => get_bloginfo( 'name' ),
			'site-link'     => sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'name' ) ),
			'site-url'      => sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'url' ) ),
			'directory-url' => sprintf( '<a href="%1$s">%1$s</a>', wpbdp_get_page_link( 'main' ) ),
			'today'         => date_i18n( get_option( 'date_format' ) ),
			'now'           => date_i18n( get_option( 'time_format' ) ),
		)
	);

	if ( $file ) {
		$keys   = array_keys( $replacements );
		$values = array_values( $replacements );

		// Normalize keys for PHP usage.
		foreach ( $keys as &$k ) {
			$k = str_replace( '-', '_', $k );
		}

		$replacements = array_combine( $keys, $values );
	}

	$subject = '';
	$body    = '';

	if ( $setting || $object ) {
		$value = $setting ? wpbdp_get_option( $setting['id'] ) : (array) $object;

		// Support old-style settings.
		if ( ! is_array( $value ) ) {
			$subject = $setting['default']['subject'];
			$body    = $setting['default']['body'];
		} else {
			$subject = $value['subject'];
			$body    = $value['body'];
		}

		$placeholders = $replacements; // XXX: does this work ok?
		foreach ( array_keys( $placeholders ) as $placeholder ) {
			if ( ! isset( $replacements[ $placeholder ] ) ) {
				continue;
			}

			$subject = str_replace( '[' . $placeholder . ']', $replacements[ $placeholder ], $subject );
			$body    = str_replace( '[' . $placeholder . ']', $replacements[ $placeholder ], $body );
		}
	} elseif ( $file ) {
		$body = wpbdp_render_page( $file, $replacements );
	}

	$email = new WPBDP_Email();

	if ( $subject ) {
		$email->subject = $subject;
	}

	$email->body = $body;

	return $email;
}

function wpbdp_admin_pointer(
	$selector,
	$title,
	$content_ = '',
	$primary_button = false,
	$primary_action = '',
	$secondary_button = false,
	$secondary_action = '',
	$options = array()
) {
	if ( ! current_user_is_admin() || ( get_bloginfo( 'version' ) < '3.3' ) ) {
		return;
	}

	$content  = '';
	$content .= '<h3>' . $title . '</h3>';
	$content .= '<p>' . $content_ . '</p>';
	?>
	<script>
		//<![CDATA[
		jQuery(function( $ ) {
			var wpbdp_pointer = $( '<?php echo $selector; ?>' ).pointer({
				'content': <?php echo json_encode( $content ); ?>,
				'position': { 'edge': '<?php echo isset( $options['edge'] ) ? $options['edge'] : 'top'; ?>',
					'align': '<?php echo isset( $options['align'] ) ? $options['align'] : 'center'; ?>' },
				'buttons': function( e, t ) {
					<?php if ( ! $secondary_button ) : ?>
					var b = $( '<a id="wpbdp-pointer-b1" class="button button-primary">' + '<?php echo $primary_button; ?>' + '</a>' );
					<?php else : ?>
					var b = $( '<a id="wpbdp-pointer-b2" class="button" style="margin-right: 15px;">' + '<?php echo $secondary_button; ?>' + '</a>' );
					<?php endif; ?>
					return b;
				}
			}).pointer('open');

			<?php if ( $secondary_button ) : ?>
			$( '#wpbdp-pointer-b2' ).before( '<a id="wpbdp-pointer-b1" class="button button-primary">' + '<?php echo $primary_button; ?>' + '</a>' );
			$( '#wpbdp-pointer-b2' ).click(function(e) {
				e.preventDefault();
				<?php if ( $secondary_action ) : ?>
				<?php echo $secondary_action; ?>
				<?php endif; ?>
				wpbdp_pointer.pointer( 'close' );
			});
			<?php endif; ?>

			$( '#wpbdp-pointer-b1' ).click(function(e) {
				e.preventDefault();
				<?php if ( $primary_action ) : ?>
				<?php echo $primary_action; ?>
				<?php endif; ?>
				wpbdp_pointer.pointer( 'close' );
			});

		});
		//]]>
	</script>
	<?php
}

// For compat with PHP < 5.3
if ( ! function_exists( 'str_getcsv' ) ) {

	function str_getcsv( $input, $delimiter = ',', $enclosure = '"' ) {
		$file = tmpfile();

		fwrite( $file, $input );
		fseek( $file, 0 );

		$res = fgetcsv( $file, 0, $delimiter, $enclosure );

		fclose( $file );

		return $res;
	}
}

/**
 * @since 4.0.5dev
 */
function wpbdp_detect_encoding( $content ) {
	static $encodings = array(
		'UTF-8',
		'UTF-16LE',
		'ASCII',
		'ISO-8859-1',
		'ISO-8859-2',
		'ISO-8859-3',
		'ISO-8859-4',
		'ISO-8859-5',
		'ISO-8859-6',
		'ISO-8859-7',
		'ISO-8859-8',
		'ISO-8859-9',
		'ISO-8859-10',
		'ISO-8859-13',
		'ISO-8859-14',
		'ISO-8859-15',
		'ISO-8859-16',
		'Windows-1251',
		'Windows-1252',
		'Windows-1254',
	);

	if ( function_exists( 'mb_detect_encoding' ) ) {
		// XXX: mb_detect_encoding() can't detect UTF-16* encodings
		// See documentation for mb_detect_order()
		return mb_detect_encoding( $content, $encodings, true );
	} elseif ( ! function_exists( 'iconv' ) ) {
		return 'UTF-8';
	} else {
		return wpbdp_mb_detect_encoding( $content, $encodings );
	}
}

/**
 * Taken from http://php.net/manual/en/function.mb-detect-encoding.php#113983
 *
 * @since 4.0.5dev
 */
function wpbdp_mb_detect_encoding( $content, $encodings ) {
	foreach ( $encodings as $encoding ) {
		$sample = iconv( $encoding, $encoding, $content );
		if ( md5( $sample ) == md5( $content ) ) {
			return $encoding;
		}
	}

	return false;
}

function wpbdp_render_user_field( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'class' => '',
			'name'  => 'user',
			'value' => null,
		)
	);

	$users_query = new WP_User_Query(
		array(
			'count_total' => true,
			'fields'      => 'ID',
			'number'      => 200,
		)
	);

	if ( $users_query->get_total() <= 200 ) {
		$output = '<select class="' . esc_attr( $args['class'] ) . '" name="' . esc_attr( $args['name'] ) . '">';

		foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $user ) {
			$selected = $args['value'] == $user->ID ? ' selected="selected"' : '';

			$output .= '<option value="' . $user->ID . '"' . $selected . '>';
			$output .= "{$user->display_name} ({$user->user_login})";
			$output .= '</option>';
		}

		$output .= '</select>';
	} else {
		if ( $args['value'] ) {
			$user         = get_user_by( 'ID', $args['value'] );
			$text_value   = "{$user->display_name} ({$user->user_login})";
			$hidden_value = $user->ID;
		} else {
			$text_value   = '';
			$hidden_value = 0;
		}

		$hidden_field_id = 'autocomplete-value-' . uniqid();

		$output  = '<input class="wpbdp-user-autocomplete ' . esc_attr( $args['class'] ) . '" type="text" value="' . esc_attr( $text_value ) . '" data-hidden-field="' . $hidden_field_id . '" />';
		$output .= '<input id="' . esc_attr( $hidden_field_id ) . '" name="' . esc_attr( $args['name'] ) . '" type="hidden" value="' . absint( $hidden_value ) . '">';
	}

	return $output;
}

function wpbdp_enqueue_jquery_ui_style() {
	WPBDP__Assets::load_datepicker();
}

function wpbdp_buckwalter_arabic_transliteration( $content ) {
	$arabic_characters = array(
		'ء',
		'آ',
		'أ',
		'ؤ',
		'إ',
		'ئ',
		'ا',
		'ب',
		'ة',
		'ت',
		'ث',
		'ج',
		'ح',
		'خ',
		'د',
		'ذ',
		'ر',
		'ز',
		'س',
		'ش',
		'ص',
		'ض',
		'ط',
		'ظ',
		'ع',
		'غ',
		'ـ',
		'ف',
		'ق',
		'ك',
		'ل',
		'م',
		'ن',
		'ه',
		'و',
		'ى',
		'ي',
		'ً',
		'ٌ',
		'ٍ',
		'َ',
		'ُ',
		'ِ',
		'ّ',
		'ْ',
		'ٰ',
		'ٱ',
	);

	$english_characters = array(
		'\'',
		'|',
		'O', // replaced '>' with 'O' as suggested in http://www.qamus.org/transliteration.htm
		'W', // replaced '&' with 'W'
		'I', // replaced '<' with 'I'
		'}',
		'A',
		'b',
		'p',
		't',
		'v',
		'j',
		'H',
		'x',
		'd',
		'*',
		'r',
		'z',
		's',
		'$',
		'S',
		'D',
		'T',
		'Z',
		'E',
		'g',
		'_',
		'f',
		'q',
		'k',
		'l',
		'm',
		'n',
		'h',
		'w',
		'Y',
		'y',
		'F',
		'N',
		'K',
		'a',
		'u',
		'i',
		'~',
		'o',
		'`',
		'{',
	);

	return str_replace( $arabic_characters, $english_characters, $content );
}

/**
 * The function was originally developed as an static method in
 * WPBDP_Form_Field_Type. It has always rendered values as given, indirectly
 * expecting them to be already escaped with `esc_attr`.
 *
 * If you decide to use `esc_attr` inside the function, make sure to check
 * all places where the function is called, to avoid scaping values twice.
 *
 * @since 4.1.10
 */
function wpbdp_html_attributes( $attrs, $exceptions = array(), $echo = false ) {
	$html = '';

	foreach ( $attrs as $k => $v ) {
		if ( in_array( $k, $exceptions, true ) ) {
			continue;
		}

		$html .= sprintf( '%s="%s" ', esc_attr( $k ), esc_attr( $v ) );
	}

	if ( ! $echo ) {
		return $html;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $html;
}

/**
 * @since 4.1.11
 */
function wpbdp_table_exists( $table_name ) {
	global $wpdb;

	$result = $wpdb->get_var( "SHOW TABLES LIKE '" . $table_name . "'" );

	return strcasecmp( $result, $table_name ) === 0;
}

/**
 * @since 5.0.5
 */
function wpbdp_column_exists( $table_name, $column_name ) {
	global $wpdb;

	$display_errors = $wpdb->hide_errors();
	$result         = $wpdb->get_col( sprintf( 'SELECT %s FROM %s LIMIT 1', $column_name, $table_name ) );
	$wpdb->show_errors( $display_errors );

	return empty( $wpdb->last_error );
}

/**
 * @since 5.0
 */
function wpbdp_is_request( $type ) {
	switch ( $type ) {
		case 'admin':
			return is_admin();
		case 'ajax':
			return defined( 'DOING_AJAX' ) && DOING_AJAX;
		case 'cron':
			return defined( 'DOING_CRON' ) && DOING_CRON;
		case 'frontend':
			return ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) );
	}
}

/**
 * @since 5.0
 */
function wpbdp_deprecation_warning( $msg = '' ) {
	global $wpbdp_deprecation_warnings;

	if ( ! isset( $wpbdp_deprecation_warnings ) ) {
		$wpbdp_deprecation_warnings = array();
	}

	$wpbdp_deprecation_warnings[] = $msg;
}

function wpbdp_x_render( $template_id, $vars = array(), $wrapper = '' ) {
	global $wpbdp;

	if ( $wrapper && ! isset( $vars['_wrapper'] ) ) {
		$vars['_wrapper'] = $wrapper;
	}

	return $wpbdp->themes->render( $template_id, $vars );
}

function wpbdp_x_render_page( $template_id, $vars = array() ) {
	return wpbdp_x_render( $template_id, $vars, 'page' );
}

/**
 * Used when we want a BD theme to be able to override.
 */
function wpbdp_x_part( $template_id, $vars = array() ) {
	global $wpbdp;
	$echo = ! isset( $vars['echo'] ) || $vars['echo'] === true;

	// Temporary reverse compatibilty
	if ( isset( $vars['images'] ) && $template_id === 'parts/listing-images' ) {
		_deprecated_argument( __FUNCTION__, '5.13.1', '$vars[images] has been replaced with $vars[extra_images]' );
		$vars['extra_images'] = $vars['images'];
		unset( $vars['images'] );
	}

	$part = $wpbdp->themes->render_part( $template_id, $vars );
	if ( ! $echo ) {
		return $part;
	}
	echo $part;
}

function wpbdp_add_template_dir( $dir_or_file ) {
	global $wpbdp;
	return $wpbdp->themes->add_template_dir( $dir_or_file );
}

function wpbdp_admin_message( $msg, $kind = '', $extra = array() ) {
	global $wpbdp;
	$wpbdp->admin->messages[] = ( $kind || $extra ) ? array( $msg, $kind, $extra ) : $msg;
}
