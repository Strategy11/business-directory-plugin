<?php
/**
 * @since 5.4
 * @package BDP\Admin
 */

// phpcs:disable
/**
 * Class WPBDP_DataFormatter Formats data from a list of properties in format expected by the Data Exporter API.
 */
class WPBDP_DataFormatter {
    /**
     *
     * @param $items
     * @param $properties
     * @return array
     *
     * @since 5.4
     */
    public function format_data( $items, $properties ) {
        $data = array();
        foreach ( $items as $key => $name ) {
            if ( empty( $properties[ $key ] ) ) {
                continue;
            }
            $data[] = array(
                'name'  => $name,
                'value' => $properties[ $key ],
            );
        }
        return $data;
    }
}
