<?php

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

function wpbdp_get_option( $key, $default = false ) {
    return call_user_func_array( array( wpbdp()->settings, 'get_option' ), func_get_args() );
}

function wpbdp_set_option( $key, $value ) {
    return call_user_func_array( array( wpbdp()->settings, 'set_option' ), func_get_args() );
}

/**
 * @since 5.0
 */
function wpbdp_delete_option( $key ) {
    return call_user_func_array( array( wpbdp()->settings, 'delete_option' ), func_get_args() );
}

/**
 * @since 5.0
 */
function wpbdp_register_settings_group( $args ) {
    return call_user_func_array( array( wpbdp()->settings, 'register_group' ), func_get_args() );
}

/**
 * @since 5.0
 */
function wpbdp_register_setting( $args ) {
    return call_user_func_array( array( wpbdp()->settings, 'register_setting' ), func_get_args() );
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
            $url = add_query_arg( 'wpbdp_view', $pathorview, $base_url );
            break;
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

// TODO: update before themes-release
// TODO: Sometimes this functions is called from
//       WPBDP_WPML_Compat->language_switcher even though no category
//       is available thorugh get_queried_object(), triggering a
//       "Trying to get property of non-object" notice.
//
//       The is_object() if-statement that is commented out below can prevent
//       the notice, but the real issue is the fact that the plugin thinks
//       we are showing a category while the main query has no queried object.
//
//       If the rewrite rule for a cateagry matches, but we can't retrieve
//       a term from the database, we should mark the query as not-found
//       from the beginning.
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
 * @since fees-revamp
 */
function wpbdp_get_fee_plans() {
    $args = array(
        'enabled' => 1
    );

    if ( wpbdp_payments_possible() ) {
        $args['-tag'] = 'free';
    } else {
        $args['tag'] = 'free';
    }

    // if ( is_admin() && current_user_can( 'administrator' ) ) {
    //     $args['recurring'] = '0';
    // }

    if ( $order = wpbdp_get_option( 'fee-order' ) ) {
        $args['_orderby'] = ( 'custom' == $order['method'] ) ? 'weight' : $order['method'];
        $args['_order'] = ( 'custom' == $order['method'] ) ? 'DESC' : $order['order'];
    }

    $plans = WPBDP_Fee_Plan::find( $args );
    return $plans;
}

/**
 * @since fees-revamp
 */
function wpbdp_get_fee_plan( $id ) {
    $id = absint( $id );
    return WPBDP_Fee_Plan::find( $id );
}

/**
 * @since 4.1.8
 */
function wpbdp_is_taxonomy() {
    $current_view = wpbdp_current_view();
    $is_taxonomy = in_array( $current_view, array( 'show_category', 'show_tag' ), true );

    return apply_filters( 'wpbdp_is_taxonomy', $is_taxonomy, $current_view );
}

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

function wpbdp_render_msg($msg, $type='status') {
    $html = '';
    $html .= sprintf('<div class="wpbdp-msg %s">%s</div>', $type, $msg);
    return $html;
}

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
 */
function wpbdp_the_listing_actions( $args = array() ) {
    echo wpbdp_listing_actions();
}

/**
 * @since 4.0
 */
function wpbdp_listing_actions( $args = array() ) {
    return wpbdp_render( 'parts/listing-buttons',
                         array( 'listing_id' => get_the_ID(),
                         'view' => 'excerpt' ),
                         false );
}

require_once( WPBDP_PATH . 'includes/class-listing.php' );
require_once( WPBDP_PATH . 'includes/class-listings-api.php' );


/**
 * @since next-release
 */
function wpbdp_save_listing( $data, $error = false ) {
    return WPBDP_Listing::insert_or_update( $data, $error );
}

/**
 * @since next-release
 */
function wpbdp_get_listing( $listing_id ) {
    return WPBDP_Listing::get( $listing_id );
}


/**
 * @since next-release
 */
function wpbdp_insert_log( $args = array() ) {
    $defaults = array(
        'object_id' => 0,
        'rel_object_id' => 0,
        'object_type' => '',
        'created_at' => current_time( 'mysql' ),
        'log_type' => '',
        'actor' => 'system',
        'message' => '',
        'data' => null
    );
    $args = wp_parse_args( $args, $defaults );
    extract( $args );

    if ( ! $object_type && false !== strstr( $log_type, '.' ) ) {
        $parts = explode( '.', $log_type );
        $object_type = $parts[0];
    }

    $object_id = absint( $object_id );
    $message = trim( $message );
    $data = $data ? serialize( $data ) : null;

    $row = compact( 'object_type', 'object_id', 'rel_object_id', 'created_at', 'log_type', 'actor', 'message', 'data' );

    if ( ! $data )
        unset( $row['data'] );

    global $wpdb;
    if ( ! $wpdb->insert( $wpdb->prefix . 'wpbdp_logs', $row ) )
        return false;

    $row['id'] = absint( $wpdb->insert_id );

    return (object) $row;
}


/**
 * @since next-release
 */
function wpbdp_delete_log( $log_id ) {
    global $wpdb;
    return $wpdb->delete( $wpdb->prefix . 'wpbdp_logs', array( 'id' => $log_id ) );
}

/**
 * @since next-release
 */
function wpbdp_get_log( $id ) {
    $results = wpbdp_get_logs( array( 'id' => $id ) );

    if ( ! $results )
        return false;

    return $results[0];
}

/**
 * @since next-release
 */
function wpbdp_get_logs( $args = array() ) {
    $defaults = array(
        'limit' => 0,
        'orderby' => 'created_at',
        'order' => 'DESC'
    );
    $args = wp_parse_args( $args, $defaults );


    global $wpdb;

    $query  = '';
    $query .= "SELECT * FROM {$wpdb->prefix}wpbdp_logs WHERE 1=1";

    foreach ( $args as $arg_k => $arg_v ) {
        if ( in_array( $arg_k, array( 'id', 'object_id', 'object_type', 'created_at', 'log_type', 'actor' ) ) )
            $query .= $wpdb->prepare( " AND {$arg_k} = %s", $arg_v );
    }

    $query .= " ORDER BY {$args['orderby']} {$args['order']}, id {$args['order']}";

    return $wpdb->get_results( $query );
}
