<?php
/**
 * Interface Personal Data Exporter
 *
 * @since 5.4
 * @package BDP\Admin|Interface data exporter
 */

// phpcs:disable

/**
 * Interface WPBDP_PersonalDataExporterInterface for Data Exporter implementations.
 */
interface WPBDP_PersonalDataExporterInterface {
    /**
     * @since 5.4
     */
    public function get_page_size();

    /**
     * @param $user
     * @param $email_address
     * @param $page
     * @return mixed
     *
     * @since 5.4
     */
    public function get_objects( $user, $email_address, $page );

    /**
     * @param $objects
     * @return mixed
     *
     * @since 5.4
     */
    public function export_objects( $objects );
}
