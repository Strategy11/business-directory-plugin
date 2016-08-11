<?php
/*
 * Deprecated functionality.
 */

$wpbusdirmanconfigoptionsprefix = "wpbusdirman";

$wpbdmposttype = "wpbdp_listing";
$wpbdmposttypecategory = "wpbdp_category";
$wpbdmposttypetags = "wpbdp_tag";

define('WPBUSDIRMAN_TEMPLATES_PATH', WPBDP_PATH . '/core/compatibility/deprecated/templates');


/* template-related */
function wpbusdirman_single_listing_details() {
    echo wpbusdirman_post_single_listing_details();
}

function wpbusdirman_post_single_listing_details() {
    return wpbdp_render_listing( null, 'single' );
}

function wpbusdirman_the_listing_title() {
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'title', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_excerpt() {
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'excerpt', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_content() {
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'content', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_category() {
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'category', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_tags() {
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'tags', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_meta($excerptorsingle) {
    $html = '';
    $fields = wpbdp_get_form_fields( array( 'association' => 'meta' ) );

    foreach ( $fields as &$f ) {
        if ( $excerptorsingle == 'excerpt' && !$field->display_in( 'excerpt' ) )
            continue;

        $html .= $f->display( get_the_ID() );
    }

    return $html;
}

function wpbusdirman_display_excerpt($deprecated=null) {
    echo wpbusdirman_post_excerpt($deprecated);
}

function wpbusdirman_post_excerpt($deprecated=null) {
    return wpbdp_render_listing( null, 'excerpt' );
}


/**
 * @deprecated since 2.3
 */
function wpbusdirman_display_main_image() {
    echo wpbusdirman_post_main_image();
}

/**
 * @deprecated since 2.3
 */
function wpbusdirman_post_main_image() {
    return wpbdp_listing_thumbnail();
}

function wpbusdirman_display_extra_thumbnails() {
    echo wpbusdirman_post_extra_thumbnails();
}

function wpbusdirman_post_extra_thumbnails() {
    $html = '';

    $thumbnail_id = wpbdp_listings_api()->get_thumbnail_id(get_the_ID());
    $images = wpbdp_listings_api()->get_images(get_the_ID());

    if ($images) {
        $html .= '<div class="extrathumbnails">';

        foreach ($images as $img) {
            if ($img->ID == $thumbnail_id)
                continue;

            $html .= sprintf('<a class="thickbox" href="%s"><img class="wpbdmthumbs" src="%s" alt="%s" title="%s" border="0" /></a>',
                             wp_get_attachment_url($img->ID),
                             wp_get_attachment_thumb_url($img->ID),
                             the_title(null, null, false),
                             the_title(null, null, false)
                             );
        }

        $html .= '</div>';      
    }

    return $html;
}

// Display the listing fields in excerpt view
function wpbusdirman_display_the_listing_fields() {
    global $post;

    $html = '';

    foreach ( wpbdp_formfields_api()->get_fields() as $field ) {
        if ( !$field->display_in( 'excerpt' ) )
            continue;

        $html .= $field->display( $post->ID, 'excerpt' );
    }

    return $html;
}

//Display the listing thumbnail
function wpbusdirman_display_the_thumbnail() {
    return wpbdp_listing_thumbnail();
}

function wpbusdirman_sticky_loop() { return; }

function wpbusdirman_latest_listings($numlistings) {
    return wpbdp_latest_listings($numlistings);
}

function wpbusdirman_post_catpage_title() {
    $categories = WPBDP_CATEGORY_TAX;

    if ( get_query_var($categories) ) {
        $term = get_term_by('slug', get_query_var($categories), $categories);
    } else if ( get_query_var('taxonomy') == $categories ) {
        $term = get_term_by('slug', get_query_var('term'), $categories);
    } elseif ( get_query_var('taxonomy') == WPBDP_TAGS_TAX ) {
        $term = get_term_by('slug', get_query_var('term'), WPBDP_TAGS_TAX);
    }

    return esc_attr($term->name);
}

function wpbusdirman_list_categories() {
    echo wpbusdirman_post_list_categories();
}

function wpbusdirman_post_list_categories() {
    return wpbdp_directory_categories();
}

/* deprecated since 2.1.4 */
function wpbdp_sticky_loop($category_id=null, $taxonomy=null) { return ''; }

/**
 * Small compatibility layer with old forms API. To be removed in later releases.
 * @deprecated
 * @since 2.3
 */
function wpbdp_get_formfields() {
    global $wpbdp;
    $res = array();

    foreach ( $wpbdp->formfields->get_fields() as $new_field ) {
        $field = new StdClass();
        $field->id = $new_field->get_id();
        $field->label = $new_field->get_label();
        $field->association = $new_field->get_association();
        $field->type = $new_field->get_field_type()->get_id();

        $res[] = $field;
    }

    return $res;
}


/**
 * @deprecated
 * @since 2.3
 */
function wpbusdirman_get_the_business_email($post_id) {
    $email_mode = wpbdp_get_option( 'listing-email-mode' );
    
    $email_field_value = '';
    if ( $email_field = wpbdp_get_form_fields( 'validators=email&unique=1' ) ) {
        $email_field_value = trim( $email_field->plain_value( $post_id ) );
    }

    if ( $email_mode == 'field' && !empty( $email_field_value ) )
        return $email_field_value;

    $author_email = '';
    $post = get_post( $post_id );
    $author_email = trim( get_the_author_meta( 'user_email', $post->post_author ) );

    if ( empty( $author_email ) && !empty( $email_field_value ) )
        return $email_field_value;
    
    return $author_email ? $author_email : '';
}

/**
 * @deprecated since 2.3
 */
function wpbdp_post_type() {
    return WPBDP_POST_TYPE;
}

/**
 * @deprecated since 2.3
 */
function wpbdp_categories_taxonomy() {
    return WPBDP_CATEGORY_TAX;
}

/**
 * Finds a fee by its ID. The special ID of 0 is reserved for the "free fee".
 * @param int $fee_id fee ID
 * @return object a fee object or NULL if nothing is found
 * @since 3.0.3
 * @deprecated since 3.7. Use {@link WPBDP_Fee_Plan::find()} instead.
 */
function wpbdp_get_fee( $fee_id ) {
    if ( 0 == $fee_id )
        return WPBDP_Fee_Plan::get_free_plan();

    $fee = WPBDP_Fee_Plan::find( $fee_id );
    return $fee;
}

/**
 * Finds fees available for one or more directory categories.
 * @param int|array $categories term ID or array of term IDs
 * @return object|
 * @since 3.0.3
 * @deprecated since 3.7. Use {@link WPBDP_Fee_Plan::for_category()} instead.
 */
function wpbdp_get_fees_for_category( $categories=null ) {
    $categories_ = is_array( $categories ) ? $categories : array( $categories );

    if ( wpbdp_payments_possible() ) {
        $results = wpbdp_fees_api()->get_fees( $categories_ );
    } else {
        $results = array( WPBDP_Fee_Plan::find( array( 'tag' => 'free' ) ) );
    }

    return is_array( $categories) ? $results : array_pop( $results );
}
