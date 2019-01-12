<?php
/**
 * Exports all personal data the plugin has for the given email address.
 *
 * @package BDP\Admin
 * @since 5.5
 */

// phpcs:disable
/**
 * Class WPBDP_PersonalDataExporter
 */
class WPBDP_PersonalDataExporter {
    /**
     * WPBDP_PersonalDataExporter constructor.
     *
     * @param $data_exporter
     */
    public function __construct( $data_exporter ) {
        $this->data_exporter = $data_exporter;
    }

    /**
     * @param $email_address
     * @param int           $page
     * @return array
     */
    public function export_personal_data( $email_address, $page = 1 ) {
        $user    = get_user_by( 'email', $email_address );
        $objects = $this->data_exporter->get_objects( $user, $email_address, $page );
        return array(
            'data' => $this->export_objects( $objects ),
            'done' => count( $objects ) < $this->data_exporter->get_page_size(),
        );
    }

    /**
     * @param $objects
     * @return array
     */
    private function export_objects( $objects ) {
        if ( empty( $objects ) ) {
            return array();
        }
        return $this->data_exporter->export_objects( $objects );
    }
}
