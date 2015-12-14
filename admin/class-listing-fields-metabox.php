<?php
class WPBDP_Admin_Listing_Fields_Metabox {
    private $listing = null;

    public function __construct( &$listing ) {
        $this->listing = $listing;
    }

    public function render() {
        $this->listing_fields();
        $this->listing_images();
    }

    private function listing_fields() {
        $formfields_api = wpbdp_formfields_api();
        $post_values = wpbdp_getv( $_POST, 'listingfields', array() );

        echo wp_nonce_field( plugin_basename( __FILE__ ), 'wpbdp-listing-fields-nonce' );

        echo '<div style="border-bottom: solid 1px #dedede; padding-bottom: 10px;">';
        echo sprintf( '<strong>%s</strong>', _x( 'Listing Fields', 'admin', 'WPBDM' ) );
        echo '<div style="padding-left: 10px;">';
        foreach ($formfields_api->find_fields( array( 'association' => 'meta' ) ) as $field ) {
            $value = isset( $post_values[ $field->get_id() ] ) ? $field->convert_input( $post_values[ $field->get_id() ] ) : $field->value( $this->listing->get_id() );
            echo $field->render( $value, 'admin-submit' );
        }
        echo '</div>';
        echo '</div>';
        echo '<div class="clear"></div>';
    }

    private function listing_images() {
        if ( ! current_user_can( 'edit_posts' ) )
            return;

        $images = $this->listing->get_images( 'all', true );
        $thumbnail_id = $this->listing->get_thumbnail_id();

        // Current images.
        echo '<h4>' . _x( 'Current Images', 'templates', 'WPBDM' ) . '</h4>';
        echo '<div id="no-images-message" style="' . ( $images ? 'display: none;' : '' ) . '">' . _x( 'There are no images currently attached to the listing.', 'templates', 'WPBDM' ) . '</div>';
        echo '<div id="wpbdp-uploaded-images" class="cf">';
        
        foreach ( $images as $image ):
            echo wpbdp_render( 'submit-listing/images-single',
                           array( 'image' => $image,
                                  'is_thumbnail' => ( 1 == count( $images ) || $thumbnail_id == $image->id ) ),
                           false );
        endforeach;
        echo '</div>';

        echo wpbdp_render( 'submit-listing/images-upload-form',
                           array( 'admin' => true, 'listing_id' => $this->listing->get_id() ),
                           false );
    }

    public static function metabox_callback( $post ) {
        $listing = WPBDP_Listing::get( $post->ID );

        if ( ! $listing )
            return '';

        $instance = new self( $listing );
        return $instance->render();
    }
}
