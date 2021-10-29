<?php

class WPBDP_SEO {

    public static function is_wp_seo_enabled() {
        return defined( 'WPSEO_VERSION' ) ? true : false;
    }

    public static function listing_title( $listing_id ) {
        if ( self::is_wp_seo_enabled() ) {
            $wpseo_front = null;

            if ( isset( $GLOBALS['wpseo_front'] ) )
                $wpseo_front = $GLOBALS['wpseo_front'];
            elseif ( class_exists( 'WPSEO_Frontend' ) && method_exists( 'WPSEO_Frontend', 'get_instance' ) )
                $wpseo_front = WPSEO_Frontend::get_instance();

            $title = $wpseo_front->get_content_title( get_post( $listing_id ) );
            $title = esc_html( strip_tags( stripslashes( apply_filters( 'wpseo_title', $title ) ) ) );

            return $title;
        }

        return get_the_title( $listing_id );
    }

    public static function listing_og_description( $listing_id ) {
        if ( self::is_wp_seo_enabled() ) {
            $wpseo_front = null;

            if ( isset( $GLOBALS['wpseo_front'] ) )
                $wpseo_front = $GLOBALS['wpseo_front'];
            elseif ( class_exists( 'WPSEO_Frontend' ) && method_exists( 'WPSEO_Frontend', 'get_instance' ) )
                $wpseo_front = WPSEO_Frontend::get_instance();

            global $post;

            $prev_post = $post;
            $post = get_post( $listing_id );
            $desc = $wpseo_front->metadesc( false );
            $post = $prev_post;

            return $desc;
        }

        $listing = WPBDP_Listing::get( $listing_id );
        return $listing->get_field_value( 'excerpt' );
    }

}
