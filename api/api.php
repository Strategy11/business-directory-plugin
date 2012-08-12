<?php
/*
 * Plugin API
 */

function wpbdp() {
    global $wpbdp;
    return $wpbdp;
}

function wpbdp_get_version() {
    return wpbdp()->get_version();
}

function wpbdp_get_db_version() {
    return wpbdp()->get_db_version();
}

function wpbdp_post_type() {
    return wpbdp()->get_post_type();
}

function wpbdp_categories_taxonomy() {
    return wpbdp()->get_post_type_category();
}

function wpbdp_tags_taxonomy() {
    return wpbdp()->get_post_type_tags();
}

function wpbdp_get_page_id($name='main') {
    global $wpdb;

    static $shortcodes = array(
        'main' => array('businessdirectory', 'WPBUSDIRMANUI'),
        'showlisting' => array('businessdirectory', 'WPBUSDIRMANUI'),
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

    $id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE ({$where}) AND post_status = 'publish' AND post_type = 'page' LIMIT 1");
    return $id;
}

function wpbdp_get_page_link($name='main', $arg0=null) {
    $main_page_id = wpbdp_get_page_id('main');
    $page_id = wpbdp_get_page_id($name);

    // wpbdp_debug_e(get_option('page_on_front'), $page_id);
    // wpbdp_debug_e($main_page_id);

    if ($page_id)
        return get_permalink($page_id);

    if ($name == 'showlisting')
        return add_query_arg('action', 'showlisting', get_permalink($main_page_id));

    if ($name == 'editlisting' || $name == 'deletelisting' || $name == 'upgradetostickylisting')
        return add_query_arg(array('action' => $name, 'listing_id' => $arg0),
                             get_permalink($main_page_id));

    if ($name == 'view-listings')
        return add_query_arg('action', 'viewlistings', get_permalink($main_page_id));

    if ($name == 'add-listing')
        return add_query_arg('action', 'submitlisting', get_permalink($main_page_id));

    return get_permalink($main_page_id);
}

/* Admin API */
function wpbdp_admin() {
    return wpbdp()->admin;
}

function wpbdp_admin_notices() {
    wpbdp_admin()->admin_notices();
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

function wpbdp_get_formfields() {
    return wpbdp_formfields_api()->getFields();
}

function wpbdp_get_formfield($id) {
    if (is_numeric($id) && is_string($id))
        return wpbdp_get_formfield(intval($id));

    if (is_string($id))
        return wpbdp_formfields_api()->getFieldsByAssociation($id, true);

    return wpbdp_formfields_api()->getField($id);
}

function wpbdp_validate_value($validator, $value, &$errors=null) {
    return wpbdp_formfields_api()->validate_value($validator, $value, $errors);
}

/* Listings */
function wpbdp_get_listing_field_value($listing, $field) {
    $listing = !is_object($listing) ? get_post($listing) : $listing;
    $field = !is_object($field) ? wpbdp_get_formfield($field) : $field;

    if ($listing && $field) {
        switch ($field->association) {
            case 'title':
                return $listing->post_title;
                break;
            case 'excerpt':
                return $listing->post_excerpt;
                break;
            case 'content':
                return $listing->post_content;
                break;
            case 'category':
                return get_the_terms($listing->ID, wpbdp()->get_post_type_category());
                break;
            case 'tags':
                return get_the_terms($listing->ID, wpbdp()->get_post_type_tags());
                break;
            case 'meta':
            default:
                $value = get_post_meta($listing->ID, '_wpbdp[fields][' . $field->id . ']', true);
                return $value;
                break;
        }
    }

    return null;
}

function wpbdp_get_listing_field_html_value($listing, $field) {
    $listing = !is_object($listing) ? get_post($listing) : $listing;
    $field = !is_object($field) ? wpbdp_get_formfield($field) : $field;

    if ($listing && $field) {
        switch ($field->association) {
            case 'title':
                return sprintf('<a href="%s">%s</a>', get_permalink($listing->ID), get_the_title($listing->ID));
                break;
            case 'excerpt':
                return apply_filters('get_the_excerpt', $listing->post_excerpt);
                break;
            case 'content':
                return apply_filters('the_content', $listing->post_content);
                break;
            case 'category':
                return get_the_term_list($listing->ID, wpbdp()->get_post_type_category(), '', ', ', '' );
                break;
            case 'tags':
                return get_the_term_list($listing->ID, wpbdp()->get_post_type_tags(), '', ', ', '' );
                break;
            case 'meta':
            default:
                $value = wpbdp_get_listing_field_value($listing, $field);

                if ($value) {
                    if (in_array($field->type, array('multiselect', 'checkbox'))) {
                        return esc_attr(str_replace("\t", ', ', $value));
                    } else {
                        if ($field->validator == 'URLValidator')
                            return sprintf('<a href="%s" rel="no follow" target="%s">%s</a>',
                                           esc_url($value),
                                           isset($field->field_data['open_in_new_window']) && $field->field_data['open_in_new_window'] ? '_blank' : '_self',
                                           esc_url($value));

                        return esc_attr(wpbdp_get_listing_field_value($listing, $field));
                    }
                }

                break;
        }
    }

    return null;
}

function wpbdp_format_field_output($field, $value='', $listing=null) {
    $field = !is_object($field) ? wpbdp_get_formfield($field) : $field;
    $value = $listing ? wpbdp_get_listing_field_html_value($listing, $field) : $value;

    if ($field->validator == 'EmailValidator' && !wpbdp_get_option('override-email-blocking'))
        return '';

    if ($field && $value && !$field->display_options['hide_field'])
        return sprintf('<div class="field-value wpbdp-field-%s %s"><label>%s</label>: <span class="value">%s</span></div>',
                       strtolower(str_replace(array(' ', '/'), '', $field->label)), /* normalized field label */
                       $field->association,
                       esc_attr($field->label),
                       $value);
}

/* Fees/Payment API */
function wpbdp_payments_possible() {
    return wpbdp_payments_api()->payments_possible();
}

function wpbdp_payment_status($listing_id) {
    return wpbdp_listings_api()->get_payment_status($listing_id);
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
function _wpbdp_save_object($obj_, $table, $id='id') {
    global $wpdb;

    $obj = is_object($obj_) ? (array) $obj_ : $obj_;

    if (!$obj) return 0;

    foreach ($obj as $k => $v) {
        if (is_array($v) || is_object($v))
            $obj[$k] = serialize($v);
    }

    $obj_id = 0;

    if (isset($obj[$id])) {
        if ($wpdb->update("{$wpdb->prefix}wpbdp_" . $table, $obj, array($id => $obj[$id])) !== false)
            $obj_id = $obj[$id];
    } else {
        if ($wpdb->insert("{$wpdb->prefix}wpbdp_" . $table, $obj)) {
            $obj_id = $wpdb->insert_id;
        }
    }

    // if ($obj_id)
    //  do_action('wpbdp_save_' . $table, $obj_id);

    return $obj_id;
}

function wpbdp_categories_list($parent=0, $hierarchical=true) {
    $terms = get_categories(array(
        'taxonomy' => wpbdp_categories_taxonomy(),
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
    $category = get_term(intval($catid), wpbdp_categories_taxonomy());

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

function wpbdp_locate_template($template, $allow_override=true, $try_defaults=true) {
    $template_file = '';

    if (!is_array($template))
        $template = array($template);

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
    return wpbdp_render_page(wpbdp_locate_template($template, $allow_override), $vars, false);
}

function wpbdp_render_msg($msg, $type='status') {
    $html = '';
    $html .= sprintf('<div class="%s">%s</div>', $type == 'error' ? 'wpbusdirmanerroralert' : $type, $msg);
    return $html;
}


/*
 * Template functions
 */

function wpbdp_sticky_loop($category_id=null) {
    $category_id = $category_id ? $category_id : (isset($_REQUEST['category_id']) ? intval($_REQUEST['category_id']) : null);

    $args = array(
        'post_type' => wpbdp_post_type(),
        'posts_per_page' => 0,
        'post_status' => 'publish',
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        'meta_key' => '_wpbdp[sticky]',
        'meta_value' => 'sticky',
        'orderby' => wpbdp_get_option('listings-order-by', 'date'),
        'order' => wpbdp_get_option('listings-sort', 'ASC')
    );

    if ($category_id) {
        $args['tax_query'] = array(
            array('taxonomy' => wpbdp_categories_taxonomy(),
                  'field' => 'id',
                  'terms' => $category_id)
        );
    }

    $stickies = get_posts($args);

    $html = '';

    foreach ($stickies as $sticky_post)
        $html .= wpbdp_render_listing($sticky_post->ID, 'excerpt');

    return $html;
}

/**
 * Displays a single listing view taking into account all of the theme overrides.
 * @param mixed $listing_id listing object or listing id to display.
 * @param string $view 'single' for single view or 'excerpt' for summary view.
 * @return string HTML output.
 */
function wpbdp_render_listing($listing_id=null, $view='single', $echo=false) {
    if (is_object($listing_id)) $listing_id = $listing_id->ID;

    global $post;
    $listings_api = wpbdp_listings_api();

    if ($listing_id)  {
        query_posts(array(
            'post_type' => wpbdp_post_type(),
            'p' => $listing_id
        ));

        if (have_posts()) the_post();
    }

    if (!$post || $post->post_type != wpbdp_post_type())
        return '';

    if ($view == 'excerpt')
        $html = _wpbdp_render_excerpt();
    else
        $html = _wpbdp_render_single();

    if ($listing_id)
        wp_reset_query();

    if ($echo)
        echo $html;

    return $html;
}

function _wpbdp_render_single() {
    global $post;

    $html = '';

    $sticky_status = wpbdp_listings_api()->get_sticky_status($post->ID);

    $html .= sprintf('<div id="wpbdp-listing-%d" class="wpbdp-listing wpbdp-listing-single %s %s">', $post->ID, 'single', $sticky_status);
    $html .= apply_filters('wpbdp_listing_view_before', '', $post->ID, 'single');

    $sticky_tag = '';
    if ($sticky_status == 'sticky')
        $sticky_tag = sprintf('<div class="stickytag"><img src="%s" alt="%s" border="0" title="%s"></div>',
                        WPBDP_URL . 'resources/images/featuredlisting.png',
                        _x('Featured Listing', 'templates', 'WPBDM'),
                        the_title(null, null, false));

    $listing_fields = '';
    foreach (wpbdp_get_formfields() as $field) {
        $listing_fields .= wpbdp_format_field_output($field, null, $post);
    }

    // images
    $thumbnail_id = wpbdp_listings_api()->get_thumbnail_id($post->ID);
    $images = wpbdp_listings_api()->get_images($post->ID);
    $extra_images = array();

    foreach ($images as $img) {
        if ($img->ID == $thumbnail_id) continue;

        $extra_images[] = sprintf('<a class="thickbox" href="%s"><img class="wpbdp-thumbnail" src="%s" alt="%s" title="%s" border="0" /></a>',
                                    wp_get_attachment_url($img->ID),
                                    wp_get_attachment_thumb_url($img->ID),
                                    the_title(null, null, false),
                                    the_title(null, null, false));
    }

    $vars = array(
        'actions' => wpbdp_render('parts/listing-buttons', array('listing_id' => $post->ID, 'view' => 'single'), false),
        'is_sticky' => $sticky_status == 'sticky',
        'sticky_tag' => $sticky_tag,
        'title' => get_the_title(),
        'main_image' => wpbusdirman_post_main_image(),
        'listing_fields' => $listing_fields,
        'extra_images' => $extra_images
    );

    $html .= wpbdp_render('businessdirectory-listing', $vars, false);
    $html .= apply_filters('wpbdp_listing_view_after', '', $post->ID, 'single');

    $html .= '<div class="contact-form">';
    $html .= wpbusdirman_contactform(null,$post->ID,$commentauthorname='',$commentauthoremail='',$commentauthorwebsite='',$commentauthormessage='',$wpbusdirman_contact_form_errors='');
    $html .= '</div>';

    if (wpbdp_get_option('show-comment-form')) {
        $html .= '<div class="comments">';

        ob_start();
        comments_template();
        $html .= ob_get_contents();
        ob_end_clean();

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

function _wpbdp_render_excerpt() {
    global $post;
    static $counter = 0;

    $sticky_status = wpbdp_listings_api()->get_sticky_status($post->ID);

    $html = '';
    $html .= sprintf('<div id="wpbdp-listing-%d" class="wpbdp-listing excerpt wpbdp-listing-excerpt %s %s cf">',
                     $post->ID,
                     $sticky_status,
                     ($counter & 1) ? 'odd':  'even');
    //$html .= apply_filters('wpbdp_render_listing_before', '', $post->ID, 'excerpt');

    $listing_fields = '';
    foreach (wpbdp_get_formfields() as $field) {
        if (!$field->display_options['show_in_excerpt'])
            continue;

        $listing_fields .= wpbdp_format_field_output($field, null, $post);
    }

    $vars = array(
        'is_sticky' => $sticky_status == 'sticky',
        'thumbnail' => wpbusdirman_display_the_thumbnail(),
        'title' => get_the_title(),
        'listing_fields' => $listing_fields
    );

    $html .= wpbdp_render('businessdirectory-excerpt', $vars, true);
    $html .= wpbdp_render('parts/listing-buttons', array('listing_id' => $post->ID, 'view' => 'excerpt'), false);

    //$html .= apply_filters('wpbdp_render_listing_after', '', $post->ID, 'excerpt');
    $html .= '</div>';

    $counter++;

    return $html;
}

function wpbdp_latest_listings($n=10, $before='<ul>', $after='</ul>', $before_item='<li>', $after_item = '</li>') {
    $n = max(intval($n), 0);

    $posts = get_posts(array(
        'post_type' => wpbdp_post_type(),
        'post_status' => 'publish',
        'numberposts' => $n,
        'orderby' => 'date'
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

function _wpbdp_template_mode($template) {
    if ( wpbdp_locate_template(array('businessdirectory-' . $template, 'wpbusdirman-' . $template), true, false) )
        return 'template';
    return 'page';
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

    if ($post->post_type != wpbdp_post_type())
        return false;

    switch ($action) {
        case 'view':
            return true;
            break;
        case 'edit':
        case 'delete':
            return user_can($user_id, 'administrator') || ($post->post_author == $user_id);
            break;
        case 'upgrade-to-sticky':
            if (wpbdp_listings_api()->get_sticky_status($listing_id) == 'normal')
                return user_can($user_id, 'administrator') || ($post->post_author == $user_id);
            return false;
            break;
    }

    return false;
}

function _wpbdp_current_action() {
    return wpbdp()->controller->get_current_action();
}