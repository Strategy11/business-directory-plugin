<?php
/**
 * @package WPBDP
 */

// phpcs:disable

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_version() {
    return WPBDP_VERSION;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function _wpbdp_page_lookup_query( $page_id, $count = false ) {
    global $wpdb;

    static $shortcodes = array(
        'main' => array('businessdirectory', 'business-directory', 'WPBUSDIRMANUI'),
        'add-listing' => array('businessdirectory-submitlisting', 'WPBUSDIRMANADDLISTING'),
        'manage-listings' => array('businessdirectory-managelistings', 'WPBUSDIRMANMANAGELISTING'),
        'view-listings' => array('businessdirectory-viewlistings', 'businessdirectory-listings', 'WPBUSDIRMANMVIEWLISTINGS')
    );

    if ( ! array_key_exists( $page_id, $shortcodes ) )
        return false;

    if ( $count ) {
        $query  = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND ( 1=0";
    } else {
        $query  = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND ( 1=0";
    }

    foreach ( $shortcodes[ $page_id ] as $s ) {
        $query .= sprintf( " OR post_content LIKE '%%[%s]%%' ", $s );
    }
    $query .= ')';

    return $query;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_page_ids( $page_id = 'main' ) {
    static $request_cached = array();

    if ( isset( $request_cached[ $page_id ] ) ) {
        $page_ids = $request_cached[ $page_id ];
    } else {
        $page_ids = null;
    }

    $cached_ids = get_transient( 'wpbdp-page-ids' );

    if ( is_null( $page_ids ) ) {
        $page_ids = wpbdp_get_page_ids_from_cache( $cached_ids, $page_id );
    }

    if ( is_null( $page_ids ) ) {
        $page_ids = wpbdp_get_page_ids_with_query( $page_id );
    }

    if ( is_array( $cached_ids ) ) {
        $cached_ids[ $page_id ] = $page_ids;
    } else {
        $cached_ids = array( $page_id => $page_ids );
    }

    set_transient( 'wpbdp-page-ids', $cached_ids, 60 * 60 * 24 * 30 );

    $request_cached[ $page_id ] = $page_ids;

    return apply_filters( 'wpbdp_get_page_ids', $page_ids, $page_id );
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_page_ids_from_cache( $cache, $page_id ) {
    global $wpdb;

    if ( ! is_array( $cache ) || empty( $cache[ $page_id ] ) ) {
        return null;
    }

    // Validate the cached IDs.
    $query  = _wpbdp_page_lookup_query( $page_id, true );
    $query .= ' AND ID IN ( ' . implode( ',', array_map( 'intval', $cache[ $page_id ] ) ) . ' ) ';

    $count = intval( $wpdb->get_var( $query ) );

    if ( $count != count( $cache[ $page_id ] ) ) {
        wpbdp_debug( 'Page cache is invalid.' );
        return null;
    }

    return $cache[ $page_id ];
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_page_ids_with_query( $page_id ) {
    global $wpdb;

    // Look up for pages.
    $q = _wpbdp_page_lookup_query( $page_id );

    if ( ! $q ) {
        return null;
    }

    $q .= ' ORDER BY ID ASC ';

    return $wpdb->get_col( $q );
}

/**
 * @SuppressWarnings(PHPMD)
 */
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
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_page_link($name='main', $arg0=null) {
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
                if ( ! wpbdp_get_page_id( 'main' ) )
                    return '';

                $link = wpbdp_get_page_link( 'main' );
                break;
        }
    }

    return apply_filters( 'wpbdp_get_page_link', $link, $name, $arg0 );
}

/* Admin API */

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_admin() {
    return wpbdp()->admin;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_admin_notices() {
    global $wpbdp;
    return $wpbdp->admin->admin_notices();
}

/* Settings API */

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_settings_api() {
    global $wpbdp;
    return $wpbdp->settings;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_option( $key, $default = false ) {
    $args_ = func_get_args();
    return call_user_func_array( array( wpbdp()->settings, 'get_option' ), $args_ );
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_set_option( $key, $value ) {
    $args_ = func_get_args();
    return call_user_func_array( array( wpbdp()->settings, 'set_option' ), $args_ );
}

/**
 * @since 5.0
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_delete_option( $key ) {
    $args_ = func_get_args();
    return call_user_func_array( array( wpbdp()->settings, 'delete_option' ), $args_ );
}

/**
 * @since 5.0
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_register_settings_group( $args ) {
    $args_ = func_get_args();
    return call_user_func_array( array( wpbdp()->settings, 'register_group' ), $args_ );
}

/**
 * @since 5.0
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_register_setting( $args ) {
    $args_ = func_get_args();
    return call_user_func_array( array( wpbdp()->settings, 'register_setting' ), $args_ );
}

/* Form Fields API */

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_formfields_api() {
    global $wpbdp;
    return $wpbdp->formfields;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_formfield($id) {
    if (is_numeric($id) && is_string($id))
        return wpbdp_get_formfield(intval($id));

    if (is_string($id))
        return wpbdp_formfields_api()->getFieldsByAssociation($id, true);

    return wpbdp_formfields_api()->get_field($id);
}

/* Fees/Payment API */

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_payments_possible() {
    if ( ! wpbdp_get_option( 'payments-on' ) ) {
        return false;
    }

    return wpbdp()->payment_gateways->can_pay();
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_fees_api() {
    return wpbdp()->fees;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_payments_api() {
    return wpbdp()->payments;
}

/* Listings API */

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_listings_api() {
    return wpbdp()->listings;
}

/* Misc. */

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_parent_categories($catid) {
    $category = get_term(intval($catid), WPBDP_CATEGORY_TAX);

    if ($category->parent) {
        return array_merge(array($category), wpbdp_get_parent_categories($category->parent));
    }

    return array($category);
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_parent_catids($catid) {
    $parent_categories = wpbdp_get_parent_categories($catid);
    array_walk($parent_categories, create_function('&$x', '$x = intval($x->term_id);'));

    return $parent_categories;
}

/**
 * Checks if permalinks are enabled.
 * @return boolean
 * @since 2.1
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_rewrite_on() {
    global $wp_rewrite;
    return $wp_rewrite->permalink_structure ? true : false;
}

/**
 * Checks if a given user can perform some action to a listing.
 * @param string $action the action to be checked. available actions are 'view', 'edit', 'delete' and 'upgrade-to-sticky'
 * @param (object|int) $listing_id the listing ID. if null, the current post ID will be used
 * @param int $user_id the user ID. if null, the current user will be used
 * @return boolean
 * @since 2.1
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_user_can($action, $listing_id=null, $user_id=null) {
    $listing_id = $listing_id ? ( is_object($listing_id) ? $listing_id->ID : intval($listing_id) ) : get_the_ID();
    $user_id = $user_id ? $user_id : wp_get_current_user()->ID;
    $post = get_post($listing_id);

    if ( ! $post )
        return false;

    if ($post->post_type != WPBDP_POST_TYPE)
        return false;

    if ( isset( $_GET['preview'] ) && ( $action != 'view' ) )
        return false;

    $res = false;

    switch ($action) {
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
            $res = user_can( $user_id, 'administrator' );
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

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_post_by_slug($slug, $post_type=null) {
    $post_type = $post_type ? $post_type : WPBDP_POST_TYPE;

    $posts = get_posts(array(
        'name' => $slug,
        'post_type' => $post_type,
        'post_status' => 'publish',
        'numberposts' => 1,
        'suppress_filters' => false,
    ));

    if ($posts)
        return $posts[0];
    else
        return 0;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_current_sort_option() {
    if ($sort = trim(wpbdp_getv($_GET, 'wpbdp_sort', null))) {
        $order = substr($sort, 0, 1) == '-' ? 'DESC' : 'ASC';
        $sort = ltrim($sort, '-');

        $obj = new StdClass();
        $obj->option = $sort;
        $obj->order = $order;

        return $obj;
    }

    return null;
}

/*
 * @since 2.1.6
 * @SuppressWarnings(PHPMD)
 */
function _wpbdp_resize_image_if_needed($id) {
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    $metadata = wp_get_attachment_metadata( $id );

    if ( ! $metadata )
        return;

    $def_width = absint( wpbdp_get_option( 'thumbnail-width' ) );

    $width = absint( isset( $metadata['width'] ) ? $metadata['width'] : 0 );

    if ( $width < $def_width )
        return;

    $thumb_info = isset( $metadata['sizes']['wpbdp-thumb'] ) ? $metadata['sizes']['wpbdp-thumb'] : false;

    if ( ! $width )
        return;

    if ( $thumb_info ) {
        $thumb_width = absint( $thumb_info['width'] );
        $def_width = absint( wpbdp_get_option( 'thumbnail-width' ) );

        // 10px of tolerance.
        if ( abs( $thumb_width - $def_width ) < 10 )
            return;
    }

    $filename = get_attached_file( $id, true );
    $attach_data = wp_generate_attachment_metadata( $id, $filename );
    wp_update_attachment_metadata( $id, $attach_data );

    wpbdp_log( sprintf( 'Resized image "%s" [ID: %d] to match updated size constraints.', $filename, $id ) );
}

/*
 * @since 2.1.7
 * @deprecated since 3.6.10. See {@link wpbdp_currency_format()}.
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_format_currency($amount, $decimals = 2, $currency = null) {
    if ( $amount == 0.0 )
        return '—';

    return ( ! $currency ? wpbdp_get_option( 'currency-symbol' ) : $currency ) . ' ' . number_format( $amount, $decimals );
}

/**
 * @since 3.6.10
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_currency_format( $amount, $args = array() ) {
    // We don't actually allow modification of the "format" string for now, but it could be useful in the future.
    switch ( wpbdp_get_option( 'currency-symbol-position' ) ) {
        case 'none':
            $def_format = '[amount]';
            break;
       case 'right':
            $def_format = '[amount] [symbol]';
            break;
        case 'left':
        default:
            $def_format = '[symbol] [amount]';
            break;
    }

    $defaults = array( 'decimals' => 2,
                       'force_numeric' => false,
                       'currency' => wpbdp_get_option( 'currency' ),
                       'symbol' => wpbdp_get_option( 'currency-symbol' ),
                       'format' => $def_format );
    $args = wp_parse_args( $args, $defaults );
    extract( $args );

    if ( ! $force_numeric && $amount == '0' ) {
        return __( 'Free', 'WPBDM' );
    }

    if ( ! $symbol )
        $symbol = strtoupper( $currency );

    $number = ( 'placeholder' != $amount ? number_format_i18n( $amount, $decimals ) : '[amount]' );
    $format = strtolower( $format );

    if ( false === strpos( $format, '[amount]' ) )
        $format .= ' [amount]';

    $replacements = array( '[currency]' => strtoupper( $currency ),
                           '[symbol]' => $symbol,
                           '[amount]' => $number );

    return str_replace( array_keys( $replacements ), array_values( $replacements ), $format );
}

/**
 * @since 5.1.9
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_date_full_format( $timestamp ) {
    return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
}

/**
 * @since 5.1.9
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_date( $timestamp ) {
    return date_i18n( get_option( 'date_format' ), $timestamp );
}


/**
 * @since 3.5.3
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_post_by_id_or_slug( $id_or_slug = false, $try_first = 'id', $result = 'post' ) {
    if ( 'slug' == $try_first )
        $strategies = array( 'slug', 'id' );
    else
        $strategies = is_numeric( $id_or_slug ) ? array( 'id', 'slug' ) : array( 'slug' );

    global $wpdb;
    $listing_id = 0;

    foreach ( $strategies as $s ) {
        switch ( $s ) {
            case 'id':
                $listing_id = intval( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE ID = %d AND post_type = %s", $id_or_slug, WPBDP_POST_TYPE ) ) );
                break;
            case 'slug':
                $listing_id = intval( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s", $id_or_slug, WPBDP_POST_TYPE ) ) );
                break;
        }

        if ( $listing_id )
            break;
    }

    if ( ! $listing_id )
        return null;

    if ( 'id' == $result )
        return $listing_id;

    return get_post( $listing_id );
}

/**
 * @since 3.5.8
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_push_query( &$q ) {
    global $wpbdp;

    $wpbdp->_query_stack[] = $q;
}

/**
 * @since 3.5.8
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_pop_query() {
    global $wpbdp;
    return array_pop( $wpbdp->_query_stack );
}

/**
 * @since 3.5.8
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_current_query() {
    global $wpbdp;

    $len = count( $wpbdp->_query_stack );

    if ( $len == 0 )
        return null;

    return $wpbdp->_query_stack[ $len - 1 ];
}

/**
 * @since 3.6.10
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_experimental( $feature ) {
    static $file_overrides = false;
    global $wpbdp_development;

    if ( file_exists( WPBDP_PATH . 'experimental' ) )
        $file_overrides = explode( ',', trim( file_get_contents( WPBDP_PATH . 'experimental' ) ) );

    $res = false;
    if ( isset( $wpbdp_development ) )
        $res = $wpbdp_development->option_get( $feature );

    if ( $file_overrides && in_array( $feature, $file_overrides, true ) )
        $res = true;

    return $res;
}

/**
 * @since 4.0
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_current_view_output() {
    global $wpbdp;
    return $wpbdp->dispatcher->current_view_output();
}

/**
 * @since 4.0
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_url( $pathorview = '/', $args = array() ) {
    $base_id = wpbdp_get_page_id( 'main' );
    $base_url = _get_page_link( $base_id );
    $base_url = apply_filters( 'wpbdp_url_base_url', $base_url, $base_id, $pathorview, $args );
    $url = '';

    switch ( $pathorview ) {
        case 'submit_listing':
        case 'all_listings':
        case 'view_listings':
        case 'search':
        case 'login':
        case 'request_access_keys':
            $url = add_query_arg( 'wpbdp_view', $pathorview, $base_url );
            break;
        case 'flag_listing':
        case 'delete_listing':
        case 'edit_listing':
        case 'listing_contact':
            $url = add_query_arg( array( 'wpbdp_view' => $pathorview, 'listing_id' => $args ), $base_url );
            break;
        case 'renew_listing':
            $url = add_query_arg( array( 'wpbdp_view' => $pathorview, 'renewal_id' => $args ), $base_url );
            break;
        case 'main':
        case '/':
            $url = $base_url;
            break;
        case 'checkout':
            $url = $base_url;
            $url = add_query_arg( array( 'wpbdp_view' => 'checkout', 'payment' => $args ), $base_url );
            break;
        default:
            if ( wpbdp_starts_with( $pathorview, '/' ) )
                $url = rtrim( wpbdp_url( '/' ), '/' ) . '/' . substr( $pathorview, 1 );

            break;
    }

    $url = apply_filters( 'wpbdp_url', $url, $pathorview, $args );
    return $url;
}

/**
 * Generates Ajax URL and allows plugins to alter it through a filter.
 *
 * @since 5.0.3
 * @SuppressWarnings(PHPMD)
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
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_current_category_id() {
    global $wp_query;

    if ( empty( $wp_query->wpbdp_is_category ) )
        return false;

    $term = $wp_query->get_queried_object();

    // if ( ! is_object( $term ) ) {
    //     return false;
    // }

    return $term->term_id;
}

/**
 * @since 4.1.12
 * @SuppressWarnings(PHPMD)
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
 * @SuppressWarnings(PHPMD)
 */
function _wpbpd_current_category() {
    global $wp_query;

    if ( $wp_query->wpbdp_is_category ) {
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

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_current_tag_id() {
    global $wp_query;

    if ( empty( $wp_query->wpbdp_is_tag ) )
        return false;

    $term = $wp_query->get_queried_object();
    return $term->term_id;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_current_action() {
    return wpbdp_current_view();
}

// TODO: how to implement now with CPT? (themes-release)
/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_current_listing_id() {
    return 0;
}

/**
 * @since 4.0
 * @SuppressWarnings(PHPMD)
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
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_load_view( $view, $arg0 = null ) {
    global $wpbdp;
    return $wpbdp->dispatcher->load_view( $view, $arg0 );
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_payment( $id ) {
    return WPBDP_Payment::objects()->get( $id );
}

/**
 * @since 5.0
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_fee_plans( $args = array() ) {
    global $wpdb;

    $defaults = array(
        'enabled'         => 1,
        'include_free'    => wpbdp_payments_possible() ? false : true,
        'tag'             => wpbdp_payments_possible() ? '' : 'free',
        'orderby'         => 'label',
        'order'           => 'ASC',
        'categories'      => array()
    );
    if ( $order = wpbdp_get_option( 'fee-order' ) ) {
        $defaults['orderby'] = ( 'custom' == $order['method'] ) ? 'weight' : $order['method'];
        $defaults['order']   = ( 'custom' == $order['method'] ) ? 'DESC' : $order['order'];
    }

    $args = wp_parse_args( $args, $defaults );
    $args = apply_filters( 'wpbdp_get_fee_plans_args', $args );

    $where = '1=1';
    if ( 'all' !== $args['enabled'] ) {
        $where .= $wpdb->prepare( ' AND p.enabled = %d ', (bool) $args['enabled'] );
    }

    if ( $args['tag'] ) {
        $where .= $wpdb->prepare( ' AND p.tag = %s', $args['tag'] );
    }

    if ( ! $args['include_free'] && 'free' != $args['tag'] ) {
        $where .= $wpdb->prepare( ' AND p.tag != %s', 'free' );
    }

    $categories = $args['categories'];
    if ( ! empty( $categories ) ) {
        if ( ! is_array( $categories ) ) {
            $categories = array( $categories );
        }

        $categories = array_map( 'absint', $categories );
    }

    $order = strtoupper( $args['order'] );
    $orderby = $args['orderby'];
    $query = "SELECT p.id FROM {$wpdb->prefix}wpbdp_plans p WHERE {$where} ORDER BY {$orderby} {$order}";

    $plan_ids = $wpdb->get_col( $query );
    $plan_ids = apply_filters( 'wpbdp_pre_get_fee_plans', $plan_ids );

    $plans = array();
    foreach ( $plan_ids as $plan_id ) {
        if ( $plan = wpbdp_get_fee_plan( $plan_id ) ) {
            if ( $categories && ! $plan->supports_category_selection( $categories ) ) {
                continue;
            }
            if ( ! empty( $plan->extra_data['private'] ) && ! current_user_can( 'administrator' ) ) {
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
 * @SuppressWarnings(PHPMD)
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
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_is_taxonomy() {
    $current_view = wpbdp_current_view();
    $is_taxonomy = in_array( $current_view, array( 'show_category', 'show_tag' ), true );

    return apply_filters( 'wpbdp_is_taxonomy', $is_taxonomy, $current_view );
}

/**
 * @since 5.5.2
 * @SuppressWarnings(PHPMD)
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

    return wpbdp_url( sprintf( '/%s/%s/', wpbdp_get_option( 'permalinks-'. $taxonomy_type . '-slug' ), $taxonomy->slug ) );
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_render_page($template, $vars=array(), $echo_output=false) {
    if ($vars) {
        extract($vars);
    }

    ob_start();
    include($template);
    $html = ob_get_contents();
    ob_end_clean();

    if ($echo_output)
        echo $html;

    return $html;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_locate_template($template, $allow_override=true, $try_defaults=true) {
    $template_file = '';

    if (!is_array($template))
        $template = array($template);

    if ( wpbdp_get_option( 'disable-cpt' ) ) {
        if ($allow_override) {
            $search_for = array();

            foreach ($template as $t) {
                $search_for[] = $t . '.tpl.php';
                $search_for[] = $t . '.php';
                $search_for[] = 'single/' . $t . '.tpl.php';
                $search_for[] = 'single/' . $t . '.php';
            }

            $template_file = locate_template($search_for);
        }
    }

    if (!$template_file && $try_defaults) {
        foreach ($template as $t) {
            $template_path = WPBDP_TEMPLATES_PATH . '/' . $t . '.tpl.php';

            if (file_exists($template_path)) {
                $template_file = $template_path;
                break;
            }
        }
    }

    return $template_file;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_render($template, $vars=array(), $allow_override=true) {
    $vars = wp_parse_args($vars, array(
        '__page__' => array(
            'class' => array(),
            'content_class' => array(),
            'before_content' => '')));
    $template_name = is_array( $template ) ? $template[0] : $template;
    $vars = apply_filters('wpbdp_template_vars', $vars, $template_name);
    return apply_filters( "wpbdp_render_{$template_name}", wpbdp_render_page(wpbdp_locate_template($template, $allow_override), $vars, false) );
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_render_msg($msg, $type='status') {
    $html = '';
    $html .= sprintf('<div class="wpbdp-msg %s">%s</div>', $type, $msg);
    return $html;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function _wpbdp_template_mode($template) {
    if ( wpbdp_locate_template(array('businessdirectory-' . $template, 'wpbusdirman-' . $template), true, false) )
        return 'template';
    return 'page';
}

require_once ( WPBDP_PATH . 'includes/helpers/class-listing-display-helper.php' );


/**
 * Displays a single listing view taking into account all of the theme overrides.
 * @param mixed $listing_id listing object or listing id to display.
 * @param string $view 'single' for single view or 'excerpt' for summary view.
 * @return string HTML output.
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_render_listing($listing_id=null, $view='single', $echo=false) {
    $listing_id = $listing_id ? ( is_object( $listing_id ) ? $listing_id->ID : absint( $listing_id ) ) : get_the_ID();

    $args = array( 'post_type' => WPBDP_POST_TYPE, 'p' => $listing_id );
    if ( ! current_user_can( 'edit_posts' ) )
        $args['post_status'] = 'publish';

    $q = new WP_Query( $args );
    if ( ! $q->have_posts() )
        return '';

    $q->the_post();

    // TODO: review filters/actions before next-release (previously _wpbdp_render_excerpt() and _wpbdp_render_single().
    if ( 'excerpt' == $view )
        $html = WPBDP_Listing_Display_Helper::excerpt();
    else
        $html = WPBDP_Listing_Display_Helper::single();

    if ( $echo )
        echo $html;

    wp_reset_postdata();

    return $html;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_latest_listings($n=10, $before='<ul>', $after='</ul>', $before_item='<li>', $after_item = '</li>') {
    $n = max(intval($n), 0);

    $posts = get_posts(array(
        'post_type' => WPBDP_POST_TYPE,
        'post_status' => 'publish',
        'numberposts' => $n,
        'orderby' => 'date',
        'suppress_filters' => false,
    ));

    $html = '';

    $html .= $before;

    foreach ($posts as $post) {
        $html .= $before_item;
        $html .= sprintf('<a href="%s">%s</a>', get_permalink($post->ID), get_the_title($post->ID));
        $html .= $after_item;
    }

    $html .= $after;

    return $html;
}

/**
 * @since 4.0
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_the_listing_actions( $args = array() ) {
    echo wpbdp_listing_actions();
}

/**
 * @since 4.0
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_listing_actions( $args = array() ) {
    return wpbdp_render( 'parts/listing-buttons',
                         array( 'listing_id' => get_the_ID(),
                         'view' => 'excerpt' ),
                         false );
}

require_once( WPBDP_INC . 'logging.php' );
require_once( WPBDP_PATH . 'includes/class-listings-api.php' );
require_once( WPBDP_INC . 'listings.php' );

/**
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_sortbar_get_field_options() {
    $options = array();

    foreach( wpbdp_get_form_fields() as $field ) {
        if ( in_array( $field->get_field_type_id(), array( 'textarea', 'select', 'checkbox', 'url' ) ) || in_array( $field->get_association(), array( 'category', 'tags' ) ) ) {
            continue;
        }

        $options[ $field->get_id() ] = apply_filters( 'wpbdp_render_field_label', $field->get_label(), $field );
    }

    $options['user_login'] = _x( 'User', 'admin settings', 'WPBDM' );
    $options['user_registered'] = _x( 'User registration date', 'admin settings', 'WPBDM' );
    $options['date'] = _x( 'Date posted', 'admin settings', 'WPBDM' );
    $options['modified'] = _x( 'Date last modified', 'admin settings', 'WPBDM' );

    return $options;
}

/**
 * Returns the admin edit link for the listing.
 * @param int $listing_id the listing ID
 * @return string The admin edit link for the listing (if available).
 * @since 5.1.3
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_edit_post_link( $listing_id ){
    if ( ! $post = get_post( $listing_id ) )
        return '';

    $post_type_object = get_post_type_object( $post->post_type );
    if ( !$post_type_object )
        return '';

    if ( $post_type_object->_edit_link ) {
        $link = admin_url( sprintf( $post_type_object->_edit_link . '&action=edit', $post->ID ) );
    } else {
        $link = '';
    }

    return $link;
}

/**
 * @since 5.1.6
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_client_ip_address() {
    $ip = '0.0.0.0';

    $check_vars = array( 'REMOTE_ADDR', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR' );

    foreach ( $check_vars as $varname ) {
        if ( isset($_SERVER[$varname]) && !empty($_SERVER[$varname] ) ) {
            return $_SERVER[$varname];
        }
    }

    return $ip;
}

/**
 * Added as replacement for a function crated with create_function().
 *
 * @since 5.2.1
 */
function wpbdp_delete_page_ids_cache() {
    delete_transient( 'wpbdp-page-ids' );
}

/**
 * Echoes a link to return to previous page.
 *
 * @since 5.5.2
 * @SuppressWarnings(PHPMD)
 */
function wpbdp_get_return_link() {
    $server  = wp_unslash( $_SERVER );
    $referer = ! empty( $server['HTTP_REFERER'] ) ? filter_var( $server['HTTP_REFERER'], FILTER_VALIDATE_URL ) : '';

    if ( ! $referer ) {
        return;
    }

    $referer_vars = array();
    $msg          = '';

    wp_parse_str( wp_parse_url( $referer, PHP_URL_QUERY ), $referer_vars );

    if ( $referer_vars && isset( $referer_vars['wpbdp_view'] ) ) {
        if ( 'search' === $referer_vars['wpbdp_view'] ) {
            $msg = _x( 'Return to results', 'templates', 'WPBDM' );
        }

        if ( 'all_listings' === $referer_vars['wpbdp_view'] ) {
            $msg = _x( 'Go back', 'templates', 'WPBDM' );
        }
    }

    if ( strpos( $referer, wpbdp_get_option( 'permalinks-category-slug' ) ) || strpos( $referer, wpbdp_get_option( 'permalinks-tags-slug' ) ) ) {
        $msg = _x( 'Go back', 'templates', 'WPBDM' );
    }

    if ( $msg ) {
        echo '<p><a href="' . esc_url( $referer ) . '" >&laquo; ' . esc_html( $msg ) . '</a></p>';
    }

}

// phpcs:enable