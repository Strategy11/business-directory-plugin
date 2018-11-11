<?php
/**
 * Privacy Policy
 *
 * @package BDP/Includes/Admin/Privacy Policy
 */

// phpcs:disable

/**
 * Class WPBDP_Privacy_Policy
 */
class WPBDP_Privacy_Policy {

    /**
     * WPBDP_Privacy_Policy constructor.
     *
     * @since 5.3.5
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
        add_action( 'wp_privacy_personal_data_exporters', array( $this, 'register_personal_data_exporters' ) );
    }

    /**
     * @since 5.3.5
     */
    public function add_privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }
        wp_add_privacy_policy_content( 'Business Directory Plugin', $this->get_privacy_policy_content() );
    }

    /**
     * @since 5.3.5
     */
    private function get_privacy_policy_content() {
        $content = wpbdp_render_page( WPBDP_PATH . 'templates/admin/privacy-policy.tpl.php', array() );
        return wp_kses_post( $content );
    }

    /**
     * @param $exporters
     *
     * @since 5.3.5
     */
    public function register_personal_data_exporters( $exporters ) {
        $exporters['business-directory-plugin-listings'] = array(
            'exporter_friendly_name' => __( 'Business Directory Plugin', 'WPBDP' ),
            'callback'               => array( $this, 'export_listing_data' ),
        );
    }

    /**
     * @param $items
     * @param $properties
     * @return array
     *
     * @since 5.3.5
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

    public function export_listing_data() {

    }
}
