<?php
/**
 * Interface Personal Data Provider implementations.
 *
 * @package BDP\Admin|Interface data Provider
 * @since 5.5
 */

// phpcs:disable

/**
 * Interface WPBDP_PersonalDataProvider Interface for Data Provider implementations.
 */
interface WPBDP_PersonalDataProviderInterface {
    /**
     * @return mixed
     */
    public function get_page_size();

    /**
     * @param $user
     * @param $email_address
     * @param $page
     * @return mixed
     */
    public function get_objects( $user, $email_address, $page );

    /**
     * @param $objects
     * @return mixed
     */
    public function export_objects( $objects );

    /**
     * @param $objects
     * @return mixed
     */
    public function erase_objects( $objects );
}
