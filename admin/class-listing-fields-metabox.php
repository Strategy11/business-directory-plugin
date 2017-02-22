<?php
class WPBDP_Admin_Listing_Fields_Metabox {
    private $listing = null;

    public function __construct( &$listing ) {
        $this->listing = $listing;
    }

    public function render() {
        echo '<div id="wpbdp-submit-listing">';

         echo '<div id="wpbdp-listing-fields-fields" class="wpbdp-admin-tab-content" tabindex="1">';
        $this->listing_fields();
        echo '</div>';

        echo '<div id="wpbdp-listing-fields-images" class="wpbdp-admin-tab-content" tabindex="2">';
        $this->listing_images();
        echo '</div>';

        echo '</div>';
    }

    private function listing_fields() {
        foreach ( wpbdp_get_form_fields( array( 'association' => 'meta' ) ) as $field ) {
            if ( ! empty( $_POST['listingfields'][ $field->get_id() ] ) ) {
                $value = $field->convert_input( $_POST['listingfields'][ $field->get_id() ] );
            } else {
                $value = $field->value( $this->listing->get_id() );
            }
            echo $field->render( $value, 'admin-submit' );
        }

        wp_nonce_field( 'save listing fields', 'wpbdp-admin-listing-fields-nonce', false );
    }

    private function listing_images() {
        if ( ! current_user_can( 'edit_posts' ) )
            return;

        $images = $this->listing->get_images( 'all', true );

        echo '<div class="wpbdp-submit-page step-images">';
        echo wpbdp_render( 'submit-listing/images',
                            array(
                                'admin' => true,
                                'listing' => $this->listing,
                                'images' => $images ) );
        echo '</div>';
    }

    public static function metabox_callback( $post ) {
        $listing = WPBDP_Listing::get( $post->ID );

        if ( ! $listing )
            return '';

        $instance = new self( $listing );
        return $instance->render();
    }
}
