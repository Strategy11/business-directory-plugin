<?php
/*
 * Deprecated functionality.
 */

$wpbusdirmanconfigoptionsprefix = 'wpbusdirman';

$wpbdmposttype         = 'wpbdp_listing';
$wpbdmposttypecategory = 'wpbdp_category';
$wpbdmposttypetags     = 'wpbdp_tag';

define( 'WPBUSDIRMAN_TEMPLATES_PATH', WPBDP_PATH . '/includes/compatibility/templates' );


/* template-related */
function wpbusdirman_single_listing_details() {
	_deprecated_function( __FUNCTION__, '', 'wpbdp_render_listing' );
    echo wpbusdirman_post_single_listing_details();
}

function wpbusdirman_post_single_listing_details() {
	_deprecated_function( __FUNCTION__, '', 'wpbdp_render_listing' );
    return wpbdp_render_listing( null, 'single' );
}

function wpbusdirman_the_listing_title() {
	_deprecated_function( __FUNCTION__, '' );
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'title', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_excerpt() {
	_deprecated_function( __FUNCTION__, '' );
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'excerpt', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_content() {
	_deprecated_function( __FUNCTION__, '' );
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'content', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_category() {
	_deprecated_function( __FUNCTION__, '' );
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'category', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_tags() {
	_deprecated_function( __FUNCTION__, '' );
	$field = wpbdp_get_form_fields( array( 'association' => 'tags', 'unique' => true ) );
    if ( $field ) {
        return $field->display( get_the_ID() );
	}
}

function wpbusdirman_the_listing_meta( $excerptorsingle ) {
	_deprecated_function( __FUNCTION__, '' );

    $html = '';
    $fields = wpbdp_get_form_fields( array( 'association' => 'meta' ) );

    foreach ( $fields as &$f ) {
		if ( $excerptorsingle === 'excerpt' && ! $field->display_in( 'excerpt' ) ) {
            continue;
		}

        $html .= $f->display( get_the_ID() );
    }

    return $html;
}

function wpbusdirman_display_excerpt( $deprecated = null ) {
	_deprecated_function( __FUNCTION__, '', 'wpbusdirman_post_excerpt' );
	echo wpbusdirman_post_excerpt( $deprecated );
}

function wpbusdirman_post_excerpt() {
	_deprecated_function( __FUNCTION__, '', 'wpbdp_render_listing' );
    return wpbdp_render_listing( null, 'excerpt' );
}


/**
 * @deprecated since 2.3
 */
function wpbusdirman_display_main_image() {
	_deprecated_function( __FUNCTION__, '2.3', 'wpbdp_listing_thumbnail' );
    echo wpbusdirman_post_main_image();
}

/**
 * @deprecated since 2.3
 */
function wpbusdirman_post_main_image() {
	_deprecated_function( __FUNCTION__, '2.3', 'wpbdp_listing_thumbnail' );
    return wpbdp_listing_thumbnail();
}

function wpbusdirman_display_extra_thumbnails() {
	_deprecated_function( __FUNCTION__, '' );
    echo wpbusdirman_post_extra_thumbnails();
}

function wpbusdirman_post_extra_thumbnails() {
	_deprecated_function( __FUNCTION__, '' );

    $html = '';

    $listing = WPBDP_Listing::get( get_the_ID() );
    $thumbnail_id = $listing->get_thumbnail_id();
    $images = $listing->get_images();

    if ($images) {
        $html .= '<div class="extrathumbnails">';

        foreach ($images as $img) {
            if ($img->ID == $thumbnail_id)
                continue;

            $html .= sprintf(
				'<a class="thickbox" href="%s"><img class="wpbdmthumbs" src="%s" alt="%s" title="%s" border="0" /></a>',
				esc_url( wp_get_attachment_url( $img->ID ) ),
				esc_url( wp_get_attachment_thumb_url( $img->ID ) ),
				esc_attr( the_title( null, null, false ) ),
				esc_attr( the_title( null, null, false ) )
			);
        }

		$html .= '</div>';
    }

    return $html;
}

// Display the listing fields in excerpt view
function wpbusdirman_display_the_listing_fields() {
	_deprecated_function( __FUNCTION__, '' );
    global $post;

    $html = '';

    foreach ( wpbdp_formfields_api()->get_fields() as $field ) {
		if ( ! $field->display_in( 'excerpt' ) ) {
            continue;
		}

        $html .= $field->display( $post->ID, 'excerpt' );
    }

    return $html;
}

//Display the listing thumbnail
function wpbusdirman_display_the_thumbnail() {
	_deprecated_function( __FUNCTION__, '', 'wpbdp_listing_thumbnail' );
    return wpbdp_listing_thumbnail();
}

function wpbusdirman_sticky_loop() {
	_deprecated_function( __FUNCTION__, '' );
	return;
}

function wpbusdirman_latest_listings( $numlistings ) {
	_deprecated_function( __FUNCTION__, '', 'wpbdp_latest_listings' );
	return wpbdp_latest_listings( $numlistings );
}

function wpbusdirman_post_catpage_title() {
	_deprecated_function( __FUNCTION__, '' );
    $categories = WPBDP_CATEGORY_TAX;

	if ( get_query_var( $categories ) ) {
		$term = get_term_by( 'slug', get_query_var( $categories ), $categories );
	} elseif ( get_query_var( 'taxonomy' ) == $categories ) {
		$term = get_term_by( 'slug', get_query_var( 'term' ), $categories );
	} elseif ( get_query_var( 'taxonomy' ) == WPBDP_TAGS_TAX ) {
		$term = get_term_by( 'slug', get_query_var( 'term' ), WPBDP_TAGS_TAX );
	}

	return esc_attr( $term->name );
}

function wpbusdirman_list_categories() {
	_deprecated_function( __FUNCTION__, '', 'wpbdp_directory_categories' );
	echo wpbdp_directory_categories();
}

function wpbusdirman_post_list_categories() {
	_deprecated_function( __FUNCTION__, '', 'wpbdp_directory_categories' );
    return wpbdp_directory_categories();
}

/**
 * @deprecated since 2.1.4
 */
function wpbdp_sticky_loop() {
	_deprecated_function( __FUNCTION__, '2.1.4' );
	return '';
}

/**
 * Small compatibility layer with old forms API. To be removed in later releases.
 *
 * @deprecated since 2.3
 */
function wpbdp_get_formfields() {
	_deprecated_function( __FUNCTION__, '2.3' );

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
 * TODO: There doesn't seem to be a replacement for this deprecated function.
 *
 * @deprecated
 * @since 2.3
 */
function wpbusdirman_get_the_business_email( $post_id ) {
	// _deprecated_function( __FUNCTION__, '2.3' );

    $email_mode = wpbdp_get_option( 'listing-email-mode' );

    $email_field_value = '';
    if ( $email_field = wpbdp_get_form_fields( 'validators=email&unique=1' ) ) {
        $email_field_value = trim( $email_field->plain_value( $post_id ) );
    }

	if ( $email_mode === 'field' && ! empty( $email_field_value ) ) {
        return $email_field_value;
	}

    $author_email = '';
    $post = get_post( $post_id );
    $author_email = trim( get_the_author_meta( 'user_email', $post->post_author ) );

	if ( empty( $author_email ) && ! empty( $email_field_value ) ) {
        return $email_field_value;
	}

    return $author_email ? $author_email : '';
}

/**
 * @deprecated since 2.3
 */
function wpbdp_post_type() {
	_deprecated_function( __FUNCTION__, '2.3', 'WPBDP_POST_TYPE' );
    return WPBDP_POST_TYPE;
}

/**
 * @deprecated since 2.3
 */
function wpbdp_categories_taxonomy() {
	_deprecated_function( __FUNCTION__, '2.3', 'WPBDP_CATEGORY_TAX' );
    return WPBDP_CATEGORY_TAX;
}

/**
 * Finds a fee by its ID. The special ID of 0 is reserved for the "free fee".
 *
 * @param int $fee_id fee ID
 * @return object a fee object or NULL if nothing is found
 * @since 3.0.3
 * @deprecated since 3.7. Use {@link wpbdp_get_fee_plan()} instead.
 */
function wpbdp_get_fee( $fee_id ) {
	_deprecated_function( __FUNCTION__, '3.7', 'wpbdp_get_fee_plan' );

    return wpbdp_get_fee_plan( $fee_id );
}

/**
 * Finds fees available for one or more directory categories.
 *
 * @param int|array $categories term ID or array of term IDs
 * @return object|
 * @since 3.0.3
 * @deprecated since 3.7. Use {@link wpbdp_get_fee_plans()} instead.
 */
function wpbdp_get_fees_for_category( $categories = null ) {
	_deprecated_function( __FUNCTION__, '3.7', 'wpbdp_get_fee_plans' );

    return wpbdp_get_fee_plans( array( 'categories' => $categories ) );
}

/**
 * @deprecated since next-release
 */
function wpbdp_categories_list( $parent = 0, $hierarchical = true) {
	_deprecated_function( __FUNCTION__, 'Unknown' );

	$terms = get_categories(
		array(
			'taxonomy'     => WPBDP_CATEGORY_TAX,
			'parent'       => $parent,
			'orderby'      => 'name',
			'hide_empty'   => 0,
			'hierarchical' => 0
		)
	);

    if ($hierarchical) {
		foreach ( $terms as &$term ) {
			$term->subcategories = wpbdp_categories_list( $term->term_id, true );
        }
    }

    return $terms;
}

/**
 * @since 2.3
 * @deprecated since 5.0
 */
function wpbdp_has_module( $module ) {
	_deprecated_function( __FUNCTION__, '5.0', 'wpbdp()->modules->is_loaded' );
    return wpbdp()->modules->is_loaded( $module );
}

function wpbdp_listing_upgrades_api() {
    return new WPBDP_NoopObject();
}
