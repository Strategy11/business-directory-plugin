<?php
/*
 * Plugin API
 */

function wpbdp() {
    global $wpbdp;
    return $wpbdp;
}

function wpbdp_get_version() {
    return WPBDP_VERSION;
}

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

function wpbdp_get_page_ids( $page_id = 'main' ) {
    global $wpdb;

    static $request_cached = array();

    if ( isset( $request_cached[ $page_id ] ) ) {
        return $request_cached[ $page_id ];
    }

    $cached_ids = get_transient( 'wpbdp-page-ids' );

    if ( is_array( $cached_ids ) && isset( $cached_ids[ $page_id ] ) ) {
        // Validate the cached IDs.

        if ( $page_ids = $cached_ids[ $page_id ] ) {
            $query  = _wpbdp_page_lookup_query( $page_id, true );
            $query .= ' AND ID IN ( ' . implode( ',', array_map( 'intval', $page_ids ) ) . ' ) ';

            $count = intval( $wpdb->get_var( $query ) );

            if ( $count == count( $page_ids ) ) {
                // Cache is valid.
                $request_cached[ $page_id ] = $page_ids;
                return $page_ids;
            }

            wpbdp_debug( 'Page cache is invalid.' );
        }
    }

    // Look up for pages.
    $q = _wpbdp_page_lookup_query( $page_id );
    if ( ! $q )
        return array();

    $q .= ' ORDER BY ID ASC ';

    $page_ids = $wpdb->get_col( $q );
    $request_cached[ $page_id ] = $page_ids;

    if ( ! is_array( $cached_ids ) )
        $cached_ids = array();

    $cached_ids[ $page_id ] = $page_ids;
    set_transient( 'wpbdp-page-ids', $cached_ids, 60 * 60 * 24 * 30 );

    return (array) $page_ids;
}

function wpbdp_get_page_id( $name = 'main' ) {
    $page_ids = wpbdp_get_page_ids( $name );

    if ( ! $page_ids )
        return false;

    return apply_filters( 'wpbdp_get_page_id', $page_ids[0], $name );
}

/**
 * @deprecated since 4.0. Use `wpbdp_url()` instead.
 * @see wpbdp_url()
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
            case 'upgrade':
            case 'upgradetostickylisting':
            case 'upgradelisting':
            case 'upgrade-listing':
                $link = wpbdp_url( 'upgrade_listing', $arg0 );
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

function wpbdp_get_option($key, $def=null) {
    global $wpbdp;
    return $wpbdp->settings->get($key, $def);
}

function wpbdp_set_option($key, $value) {
    global $wpbdp;
    return $wpbdp->settings->set($key, $value);
}

/* Form Fields API */
function wpbdp_formfields_api() {
    global $wpbdp;
    return $wpbdp->formfields;
}

function wpbdp_get_formfield($id) {
    if (is_numeric($id) && is_string($id))
        return wpbdp_get_formfield(intval($id));

    if (is_string($id))
        return wpbdp_formfields_api()->getFieldsByAssociation($id, true);

    return wpbdp_formfields_api()->get_field($id);
}

/* Fees/Payment API */
function wpbdp_payments_possible() {
    return wpbdp_payments_api()->payments_possible();
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

function wpbdp_listing_upgrades_api() {
    return wpbdp()->listings->upgrades;
}

/* Misc. */
function wpbdp_categories_list($parent=0, $hierarchical=true) {
    $terms = get_categories(array(
        'taxonomy' => WPBDP_CATEGORY_TAX,
        'parent' => $parent,
        'orderby' => 'name',
        'hide_empty' => 0,
        'hierarchical' => 0
    ));

    if ($hierarchical) {
        foreach ($terms as &$term) {
            $term->subcategories = wpbdp_categories_list($term->term_id, true);
        }
    }

    return $terms;
}

function wpbdp_get_parent_categories($catid) {
    $category = get_term(intval($catid), WPBDP_CATEGORY_TAX);

    if ($category->parent) {
        return array_merge(array($category), wpbdp_get_parent_categories($category->parent));
    }

    return array($category);
}

function wpbdp_get_parent_catids($catid) {
    $parent_categories = wpbdp_get_parent_categories($catid);
    array_walk($parent_categories, create_function('&$x', '$x = intval($x->term_id);'));

    return $parent_categories;
}

/**
 * Checks if permalinks are enabled.
 * @return boolean
 * @since 2.1
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
 * @return boolean
 * @since 2.1
 */
function wpbdp_user_can($action, $listing_id=null, $user_id=null) {
    $listing_id = $listing_id ? ( is_object($listing_id) ? $listing_id->ID : intval($listing_id) ) : get_the_ID();
    $user_id = $user_id ? $user_id : wp_get_current_user()->ID;
    $post = get_post($listing_id);

    if ($post->post_type != WPBDP_POST_TYPE)
        return false;

    if ( isset($_GET['preview']) )
        return false;

    $res = false;

    switch ($action) {
        case 'view':
            $res = true;
            // return apply_filters( 'wpbdp_user_can_view', true, $action, $listing_id );
            break;
        case 'edit':
        case 'delete':
            $res = user_can($user_id, 'administrator') || ( $post->post_author && $post->post_author == $user_id );
            break;
        case 'upgrade-to-sticky':
            $sticky_info = wpbdp_listing_upgrades_api()->get_info( $listing_id );
            $res = wpbdp_get_option( 'featured-on' ) && wpbdp_payments_possible() && $sticky_info->upgradeable && ( user_can($user_id, 'administrator') || ( $post->post_author == $user_id ) );
            break;
    }

    $res = apply_filters( 'wpbdp_user_can', $res, $action, $listing_id, $user_id );
    $res = apply_filters( 'wpbdp_user_can_' . $action, $res, $listing_id, $user_id );

    return $res;
}

function wpbdp_get_post_by_slug($slug, $post_type=null) {
    $post_type = $post_type ? $post_type : WPBDP_POST_TYPE;

    $posts = get_posts(array(
        'name' => $slug,
        'post_type' => $post_type,
        'post_status' => 'publish',
        'numberposts' => 1
    ));

    if ($posts)
        return $posts[0];
    else
        return 0;
}

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
 */
function _wpbdp_resize_image_if_needed($id) {
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    $metadata = wp_get_attachment_metadata( $id );

    if ( ! $metadata )
        return;

    $crop = (bool) wpbdp_get_option( 'thumbnail-crop' );
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
 */
function wpbdp_format_currency($amount, $decimals = 2, $currency = null) {
    if ( $amount == 0.0 )
        return '—';

    return ( ! $currency ? wpbdp_get_option( 'currency-symbol' ) : $currency ) . ' ' . number_format( $amount, $decimals );
}

/**
 * @since 3.6.10
 */
function wpbdp_currency_format( $amount, $args = array() ) {

    if( $amount == '0'){
        return __( 'Free', 'WPBDM' );
    }

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
                       'currency' => wpbdp_get_option( 'currency' ),
                       'symbol' => wpbdp_get_option( 'currency-symbol' ),
                       'format' => $def_format );

    $args = wp_parse_args( $args, $defaults );
    extract( $args );

    if ( ! $symbol )
        $symbol = strtoupper( $currency );

    $number = number_format_i18n( $amount, $decimals );
    $format = strtolower( $format );

    if ( false === strpos( $format, '[amount]' ) )
        $format .= ' [amount]';

    $replacements = array( '[currency]' => strtoupper( $currency ),
                           '[symbol]' => $symbol,
                           '[amount]' => $number );

    return str_replace( array_keys( $replacements ), array_values( $replacements ), $format );
}


/**
 * @since 2.3
 */
function wpbdp_has_module( $module ) {
    global $wpbdp;
    return $wpbdp->has_module( $module );
}

/**
 * @since 3.5.3
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

    if ( $len == 0 )
        return null;

    return $wpbdp->_query_stack[ $len - 1 ];
}

/**
 * @since 3.6.10
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
 */
function wpbdp_current_view_output() {
    global $wpbdp;
    return $wpbdp->dispatcher->current_view_output();
}

/**
 * @since 4.0
 */
function wpbdp_url( $pathorview = '/', $args = array() ) {
    $base_url = wpbdp_get_page_link( 'main' );
    $url = '';

    switch ( $pathorview ) {
        case 'submit_listing':
        case 'all_listings':
        case 'view_listings':
        case 'search':
            $url = add_query_arg( 'wpbdp_view', $pathorview, $base_url );
            break;
        case 'delete_listing':
        case 'edit_listing':
        case 'upgrade_listing':
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
        default:
            if ( wpbdp_starts_with( $pathorview, '/' ) )
                $url = rtrim( wpbdp_url( '/' ), '/' ) . '/' . substr( $pathorview, 1 );

            break;
    }

    return $url;
}

// TODO: update before themes-release
function wpbdp_current_category_id() {
    global $wp_query;

    if ( empty( $wp_query->wpbdp_is_category ) )
        return false;

    $term = $wp_query->get_queried_object();
    return $term->term_id;
}

function wpbdp_current_tag_id() {
    global $wp_query;

    if ( empty( $wp_query->wpbdp_is_tag ) )
        return false;

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
    return $wpbdp->dispatcher->current_view();
}

/**
 * @since 4.0
 */
function wpbdp_load_view( $view, $arg0 = null ) {
    global $wpbdp;
    return $wpbdp->dispatcher->load_view( $view, $arg0 );
}
