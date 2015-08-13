<?php
require_once ( WPBDP_PATH . 'core/class-field-display-list.php' );

/**
 * @since themes-release
 */
class WPBDP_Listing_Display_Helper {

    public static function excerpt() {
        global $post;

        $vars = array();
        $vars = array_merge( $vars, self::basic_vars( $post->ID ) );
        $vars = array_merge( $vars, self::fields_vars( $post->ID, 'excerpt' ) );
        $vars = array_merge( $vars, self::images_vars( $post->ID, 'excerpt' ) );

        $vars = apply_filters( 'wpbdp_listing_template_vars', $vars, $post->ID );
        $vars = apply_filters( 'wpbdp_excerpt_template_vars', $vars, $post->ID );

        // TODO: what do we do with 'wpbdp_excerpt_listing_fields' ?

        return wpbdp_x_render( 'excerpt-wrapper', array_merge( $vars,
                                                               array( 'content' => wpbdp_x_render( 'excerpt', $vars ) ) ) );
    }

    public static function single() {
        global $post;

        $vars = array();
        $vars = array_merge( $vars, self::basic_vars( $post->ID ) );
        $vars = array_merge( $vars, self::fields_vars( $post->ID, 'listing' ) );
        $vars = array_merge( $vars, self::images_vars( $post->ID, 'listing' ) );

        $vars = apply_filters( 'wpbdp_listing_template_vars', $vars, $post->ID );
        $vars = apply_filters( 'wpbdp_single_template_vars', $vars, $post->ID );

        // TODO: is this really used? can it be changed to something else?
        // 'listing_fields' => apply_filters('wpbdp_single_listing_fields', $listing_fields, $post->ID), This is 
        // complete HTML
        return wpbdp_x_render( 'single-wrapper', array_merge( $vars,
                                                              array( 'content' => wpbdp_x_render( 'single', $vars ) ) ) );
    }

    private static function basic_vars( $listing_id ) {
        $listing = WPBDP_Listing::get( $listing_id );

        $vars = array();
        $vars['listing_id'] = $listing_id;
        $vars['listing'] = $listing;
        $vars['is_sticky'] = ( 'normal' != $listing->get_sticky_status() );
        $vars['sticky_tag'] = '';
        $vars['title'] = the_title( null, null, false );

        if ( $vars['is_sticky'] )
            $vars['sticky_tag'] = wpbdp_x_render( 'listing sticky tag', array( 'listing' => $listing ) );

        return $vars;
    }

    private static function fields_vars( $listing_id, $display ) {
        $fields = wpbdp_get_form_fields();
        $fields = apply_filters_ref_array( 'wpbdp_render_listing_fields', array( &$fields, $listing_id ) );

        $list = new WPBDP_Field_Display_List( $listing_id, $display, $fields );
        $list->freeze();

        return array( 'fields' => $list );
    }

    private static function images_vars( $listing_id, $display ) {
        $vars = array();
        $vars['images'] = (object) array( 'main' => false,
                                          'extra' => array(),
                                          'thumbnail' => false );

        if ( ! wpbdp_get_option( 'allow-images' ) )
            return $vars;

        $listing = WPBDP_Listing::get( $listing_id );

        // Thumbnail.
        if ( wpbdp_get_option( 'show-thumbnail' ) ) {
            $thumb = new StdClass();
            $thumb->html = wpbdp_listing_thumbnail( null, 'link=listing&class=wpbdmthumbs wpbdp-excerpt-thumbnail' );

            $vars['images']->thumbnail = $thumb;
        }

        // Main image.
        $thumbnail_id = $listing->get_thumbnail_id();
        $main_image = new StdClass();
        $main_image->html = wpbdp_listing_thumbnail( null, 'link=picture&class=wpbdp-single-thumbnail' );

        $vars['images']->main = $main_image;

        // Other images.
        $listing_images = $listing->get_images( 'ids' );

        foreach ( $listing_images as $img_id ) {
            // Correct size of thumbnail if needed.
            _wpbdp_resize_image_if_needed( $img_id );

            if ( $img_id == $thumbnail_id )
                continue;

            $data = wp_get_attachment_image_src( $img_id, 'wpbdp-large', false );

            $image = new StdClass();
            $image->id = $img_id;
            $image->url = $data[0];
            $image->width = $data[1];
            $image->height = $data[2];
            $image->html = sprintf( '<a href="%s" class="thickbox" data-lightbox="wpbdpgal" rel="wpbdpgal" target="_blank">%s</a>',
                                    $image->url,
                                    wp_get_attachment_image( $image->id, 'wpbdp-thumb', false, array(
                                            'class' => 'wpbdp-thumbnail size-thumbnail',
                                            'alt' => the_title( null, null, false ),
                                            'title' => the_title( null, null, false )
                                        ) ) );

            $vars['images']->extra[] = $image;
        }

        return $vars;
    }

}

/**
 * @since themes-release
 */
class _WPBDP_Listing_Display_Image {
}
