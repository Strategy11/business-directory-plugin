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

function wpbdp_get_page_id($name='main', $unique=true) {
    global $wpdb;

    static $shortcodes = array(
        'main' => array('businessdirectory', 'business-directory', 'WPBUSDIRMANUI'),
        'add-listing' => array('businessdirectory-submitlisting', 'WPBUSDIRMANADDLISTING'),
        'manage-listings' => array('businessdirectory-managelistings', 'WPBUSDIRMANMANAGELISTING'),
        'view-listings' => array('businessdirectory-viewlistings', 'businessdirectory-listings', 'WPBUSDIRMANMVIEWLISTINGS'),
        'paypal' => 'WPBUSDIRMANPAYPAL',
        '2checkout' => 'WPBUSDIRMANTWOCHECKOUT',
        'googlecheckout' => 'WPBUSDIRMANGOOGLECHECKOUT'
    );

    if (!array_key_exists($name, $shortcodes))
        return null;

    $where = '1=0';
    $options = is_string($shortcodes[$name]) ? array($shortcodes[$name]) : $shortcodes[$name];
    foreach ($options as $shortcode) {
        $where .= sprintf(" OR post_content LIKE '%%[%s]%%'", $shortcode);
    }

    $page_ids = wp_cache_get( $name, 'wpbdp pages' );

    if ( ! $page_ids ) {
        $query = "SELECT ID FROM {$wpdb->posts} WHERE ({$where}) AND post_status = 'publish' AND post_type = 'page' ORDER BY ID";
        $page_ids = $wpdb->get_col( $query );
        wp_cache_set( $name, $page_ids, 'wpbdp pages' );
    }

    if ( ! $page_ids )
        return $unique ? false : array();

    if ( ! is_array( $page_ids ) )
        $page_ids = array( $page_ids );

    return $unique ? apply_filters( 'wpbdp_get_page_id', $page_ids[0], $name ) : $page_ids;
}

function wpbdp_get_page_link($name='main', $arg0=null) {
    $page_id = wpbdp_get_page_id( $name );

    if ( $page_id ) {
        $link = _get_page_link( $page_id );
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
                $link = add_query_arg( array( 'action' => $name, 'listing_id' => intval( $arg0 ) ), wpbdp_get_page_link( 'main' ) );
                break;
            case 'viewlistings':
            case 'view-listings':
                $link = add_query_arg( array( 'action' => 'viewlistings' ), wpbdp_get_page_link( 'main' ) );
                break;
            case 'add':
            case 'addlisting':
            case 'add-listing':
            case 'submit':
            case 'submitlisting':
            case 'submit-listing':
                $link = add_query_arg( array( 'action' => 'submitlisting' ), wpbdp_get_page_link( 'main' ) );
                break;
            case 'search':
                $link = add_query_arg( array( 'action' => 'search' ), wpbdp_get_page_link( 'main' ) );
                break;
            default:
                if ( !wpbdp_get_page_id( 'main' ) )
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

    switch ($action) {
        case 'view':
            return true;
            break;
        case 'edit':
        case 'delete':
            return user_can($user_id, 'administrator') || ( $post->post_author && $post->post_author == $user_id );
            break;
        case 'upgrade-to-sticky':
            if ( !wpbdp_get_option('featured-on') || !wpbdp_get_option('payments-on') )
                return false;

            if ( !wpbdp_payments_possible() )
                return false;

            $sticky_info = wpbdp_listing_upgrades_api()->get_info( $listing_id );
            return $sticky_info->upgradeable && (user_can($user_id, 'administrator') || ($post->post_author == $user_id));
            break;
    }

    return false;
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
 */
function wpbdp_format_currency($amount, $decimals = 2, $currency = null) {
    if ( $amount == 0.0 )
        return '—';
    
    return ( ! $currency ? wpbdp_get_option( 'currency-symbol' ) : $currency ) . ' ' . number_format( $amount, $decimals );
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
