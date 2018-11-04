<?php
/**
 * @package WPBDP\Admin
 * @since 5.3.4
 */

/**
 * @return WPBDP_PrivacyPolicyContent
 */
function wpbdp_privacy_policy_content() {
    return new WPBDP_PrivacyPolicyContent();
}

/**
 * Suggests content for the website's Privacy Policy page.
 */
class WPBDP_PrivacyPolicyContent {
    /**
     * @var string
     */
    private $template = WPBDP_PATH . 'templates/admin/privacy-policy.tpl.php';
    /**
     * @since 5.3.4
     */
    public function add_privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }
        wp_add_privacy_policy_content( 'Business Directory Plugin', $this->get_privacy_policy_content() );
    }
    /**
     * @since 5.3.4
     */
    private function get_privacy_policy_content() {
        $content = wpbdp_render_page( $this->template, array() );
        return wp_kses_post( $content );
    }
}
