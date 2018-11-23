<?php
/**
 * Field to Handle Social Networks
 *
 * @package BDP/Includes/Fields/Social
 * @since 5.3.5
 */

// phpcs:disable Squiz.Commenting.FunctionComment.Missing
// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript

/**
 * Class WPBDP_FieldTypes_Social
 *
 * @SuppressWarnings(PHPMD)
 */
class WPBDP_FieldTypes_Social extends WPBDP_Form_Field_Type {
    /**
     * WPBDP_FieldTypes_Social constructor.
     */
    public function __construct() {
        parent::__construct( _x( 'Social Site', 'form-fields api', 'WPBDM' ) );
    }

    public function get_id() {
        return 'social-network';
    }

    public function setup_field( &$field ) {
        $field->add_display_flag( 'social' );
    }

    public function render_field_inner( &$field, $value, $context, &$extra = null, $field_settings = array() ) {
        // Social fields are rendered as normal textfields.
        global $wpbdp;
        return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value, $context, $extra, $field_settings );
    }

    public function get_supported_associations() {
        return array( 'meta' );
    }

    public function get_field_html_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        if ( ! $value ) {
            return '';
        }

        $social_type = $this->get_social_network_type( $value );

        if ( in_array( $social_type, array( 'twitter', 'facebook', 'linkedin' ), true ) ) {
            global $wpbdp;
            return $wpbdp->formfields->get_field_type( $social_type )->get_field_html_value( $field, $post_id );
        }

        $html  = '';
        $html .= '<div class="social-field ' . $social_type . '">';
        $html .= $this->get_social_network_script( $social_type, $value );
        $html .= '</div>';

        return $html;
    }

    public function get_social_network_type( $field_value ) {
        $social_types = array( 'twitter', 'facebook', 'linkedin', 'youtube', 'pinterest', 'instagram', 'tumblr', 'flickr', 'reddit' );

        foreach ( $social_types as $type ) {
            if ( stripos( $field_value, $type ) !== false ) {
                return $type;
            }
        }

        return 'social-network';
    }

    public function get_social_network_script( $social_type, $value ) {
        $html = '';
        switch ( $social_type ) {
            case 'youtube':
                $channel = str_ireplace( array( 'http://youtube.com/channel/', 'https://youtube.com/channel/', 'http://www.youtube.com/channel/', 'https://www.youtube.com/channel/' ), '', $value );

                $html .= '<script src="https://apis.google.com/js/platform.js"></script>';
                $html .= '<div class="g-ytsubscribe" data-channelid="' . $channel . '" data-layout="full" data-count="default"></div>';
                break;
            case 'instagram':
                $profile = preg_match( '/https?:\/\/.*\.instagram\..*\/(.*)\//i', $value, $match );

                if ( ! $match ) {
                    return;
                }

                $profile = array_pop( $match );

                $html .= '<a aria-label="Home" class="wpbdp-reddit-logo" href="https://www.reddit.com/' . $profile . '">';
                // $html .= wpbdp_render_page( WPBDP_PATH . 'templates/social/reddit-logo.tpl.php');
                $html .= wpbdp_render_page( WPBDP_PATH . 'templates/social/instagram-logo.tpl.php' );
                $html .= '</a>';
                break;

            case 'pinterest':
                $profile = preg_match( '/https?:\/\/.*pinterest\..*?\/(.*)\//i', $value, $match );

                if ( ! $match ) {
                    return;
                }

                $profile = array_pop( $match );

                $html .= '<script async defer src="//assets.pinterest.com/js/pinit.js"></script>';
                $html .= '<a data-pin-do="buttonFollow" href="https://www.pinterest.com/' . $profile . '">' . __( 'Follow on Pinterest', 'wpbdp' ) . '</a>';
                break;

            case 'tumblr':
                $profile = preg_match( '/https?:\/\/(.*)\.tumblr\..*?\//i', $value, $match );

                if ( ! $match ) {
                    return;
                }

                $profile = array_pop( $match );

                $html .= '<iframe class="btn" frameborder="0" border="0" scrolling="no" allowtransparency="true" height="20" width="65" src="https://platform.tumblr.com/v2/follow_button.html?type=follow&amp;tumblelog=' . $profile . '&amp;color=white"></iframe>';
                break;

            case 'reddit':
                $profile = preg_match( '/((?:user|u|r)\/[A-Za-z0-9_-]+)/i', $value, $match );

                if ( ! $match ) {
                    return;
                }

                $profile = array_pop( $match );

                $html .= '<a aria-label="Home" class="wpbdp-reddit-logo" href="https://www.reddit.com/' . $profile . '">';
                $html .= wpbdp_render_page( WPBDP_PATH . 'templates/social/reddit-logo.tpl.php' );
                $html .= '</a>';
                break;

        }

        return $html;
    }

}
