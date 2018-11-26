<?php
/**
 * @package BDP\Admin
 * @since 5.4
 */

// phpcs:disable

/**
 * Class WPBDP_ListingsPersonalDataExporter Exporter for Listings personal data.
 */
class WPBDP_ListingsPersonalDataExporter implements WPBDP_PersonalDataExporterInterface {

    /**
     * @var $data_formatter
     */
    private $data_formatter;

    /**
     * WPBDP_ListingsPersonalDataExporter constructor.
     *
     * @since 5.4
     */
    public function __construct( $data_formatter ) {
        $this->data_formatter = $data_formatter;
    }

    /**
     * @return int
     *
     * @since 5.4
     */
    public function get_page_size() {
        return 10;
    }

    /**
     * @param $user
     * @param $email_address
     * @param $page
     * @return array
     *
     * @since 5.4
     * @SuppressWarnings(PHPMD)
     */
    public function get_objects( $user, $email_address, $page ) {
        $items_per_page = $this->get_page_size();
        return wpbdp_get_listings_by_email(
            $email_address,
            $items_per_page,
            ( $page - 1 ) * $items_per_page
        );
    }

    /**
     * @param $listing_ids
     * @return array
     *
     * @since 5.4
     */
    public function export_objects( $listing_ids ) {
        // TODO: Let premium modules define additional properties.
        $items = $this->get_privacy_fields_items();

        $media_items = array(
            'URL' => __( 'Image URL', 'business-directory-plugin' ),
        );

        $export_items = array();

        foreach ( $listing_ids as $listing_id ) {
            $data = $this->data_formatter->format_data( $items, $this->get_listing_properties( $listing_id ) );

            foreach ( wpbdp_get_listing( $listing_id )->get_images( 'ids' ) as $image ) {
                $data = array_merge( $data, $this->data_formatter->format_data( $media_items, $this->get_media_properties( $image ) ) );
            }

            $export_items[] = array(
                'group_id'    => 'wpbdp-listings',
                'group_label' => __( 'Business Directory Listings', 'business-directory-plugin' ),
                'item_id'     => "wpbdp-listing-{$listing_id}",
                'data'        => apply_filters( 'wpbdp_export_listing_objects', $data, $listing_id, $this->data_formatter )
            );

        }

        return $export_items;
    }

    /**
     * @param $listing_id
     * @return mixed
     *
     * @since 5.4
     */
    private function get_listing_properties( $listing_id ) {
        $default_tags = array( 'title', 'website', 'email', 'phone', 'fax', 'address', 'zip' );

        $properties = array( 'ID' => $listing_id );

        foreach ( $default_tags as $tag ) {
            $properties[ $tag ] = WPBDP_Form_Field::find_by_tag( $tag )->plain_value( $listing_id );
        }

        $data   = array();
        $fields = wpbdp_get_form_fields( array( 'display_flags' => 'privacy' ) );

        foreach ( $fields as $field ) {
            $tag = $field->get_tag();
            $data[ $tag ? $tag : $field->get_short_name() ] = $field->plain_value( $listing_id );

        }

        return array_merge( $properties, $data );
    }
    /**
     * @since 5.4
     */
    private function get_media_properties( $media_id ) {
        return array(
            'URL' => wp_get_attachment_url( $media_id ),
        );
    }

    /**
     * @return array
     *
     * @since 5.4
     */
    private function get_privacy_fields_items() {
        $default_tags = array( 'title', 'website', 'email', 'phone', 'fax', 'address', 'zip' );

        $items = array( 'ID' => __( 'Listing ID', 'business-directory-plugin' ) );

        foreach ( $default_tags as $tag ) {
            $items[ $tag ] = WPBDP_Form_Field::find_by_tag( $tag )->get_label();
        }

        $privacy_items = array();

        foreach ( wpbdp_get_form_fields( array( 'display_flags' => 'privacy' ) ) as $field ) {
            $tag = $field->get_tag();
            $privacy_items[ $tag ? $tag : $field->get_short_name() ] = $field->get_label();
        }

        return array_merge( $items, $privacy_items );
    }
}
