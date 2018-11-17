<?php // phpcs:disable
/**
 * Privacy Policy
 *
 * @package BDP/Includes/Admin/Privacy Policy
 * @since 5.4
 */

require_once WPBDP_INC . 'admin/interface-personal-data-exporter.php';
require_once WPBDP_INC . 'admin/class-data-formatter.php';
require_once WPBDP_INC . 'admin/class-personal-data-exporter.php';
require_once WPBDP_INC . 'admin/class-listings-personal-data-exporter.php';

/**
 * Class WPBDP_Privacy_Policy
 */
class WPBDP_Privacy_Policy {

    /**
     * @var int
     */
    public $items_per_page = 10;

    /**
     * WPBDP_Privacy_Policy constructor.
     *
     * @since 5.4
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
        add_action( 'wp_privacy_personal_data_exporters', array( $this, 'register_personal_data_exporters' ) );
    }

    /**
     * @since 5.4
     */
    public function add_privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }
        wp_add_privacy_policy_content( 'Business Directory Plugin', $this->get_privacy_policy_content() );
    }

    /**
     * @since 5.4
     */
    private function get_privacy_policy_content() {
        $content = wpbdp_render_page( WPBDP_PATH . 'templates/admin/privacy-policy.tpl.php', array() );
        return wp_kses_post( $content );
    }

    /**
     * @param $exporters
     * @return mixed
     *
     * @since 5.4
     */
    public function register_personal_data_exporters( $exporters ) {
        $data_formatter = new WPBDP_DataFormatter();

        $exporters['business-directory-plugin-listings'] = array(
            'exporter_friendly_name' => __( 'Business Directory Plugin', 'WPBDP' ),
            'callback'               => array(
                new WPBDP_PersonalDataExporter(
                    new WPBDP_ListingsPersonalDataExporter(
                        $data_formatter
                    )
                ),
                'export_personal_data',
            ),
        );

        return $exporters;

    }
}
