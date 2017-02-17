<?php
class WPBDP_Admin_Listing_Fields_Metabox {
    private $listing = null;

    public function __construct( &$listing ) {
        $this->listing = $listing;
    }

    public function render() {
        echo '<div id="wpbdp-submit-listing">';
        $this->listing_fields();
        $this->listing_images();
        echo '</div>';
    }

    private function listing_fields() {
        // echo sprintf( '<strong>%s</strong>', _x( 'Listing Fields', 'admin', 'WPBDM' ) );
        wp_nonce_field( 'save listing fields', 'wpbdp-admin-listing-fields-nonce', false );

        foreach ( wpbdp_get_form_fields( array( 'association' => 'meta' ) ) as $field ) {
            if ( ! empty( $_POST['listingfields'][ $field->get_id() ] ) ) {
                $value = $field->convert_input( $_POST['listingfields'][ $field->get_id() ] );
            } else {
                $value = $field->value( $this->listing->get_id() );
            }

            echo $field->render( $value, 'admin-submit' );
        }
    }

    private function listing_images() {
        if ( ! current_user_can( 'edit_posts' ) )
            return;

        $images = $this->listing->get_images( 'all', true );

        echo '<div class="wpbdp-submit-listing-section-listing_images">';
        echo wpbdp_render( 'submit-listing-images',
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

