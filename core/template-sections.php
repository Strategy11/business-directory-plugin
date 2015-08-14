<?php
/**
 * @since themes-release
 */
class _WPBDP_Template_Sections {

    function __construct() {
        add_action( 'wpbdp_register_template_sections', array( &$this, 'register_sections' ) );
    }

    function register_sections( $themes ) {
        $themes->register_template_section( 'single', 'after', 'comments', array( &$this, 'listing_comments' ), 11 );
        $themes->register_template_section( 'single', 'after', 'contact_form', array( &$this, 'listing_contact_form' ), 10 );
    }

    function listing_comments( $listing_id ) {
        if ( ! wpbdp_get_option( 'show-comment-form' ) )
            return;

        echo '<div class="comments">';
        comments_template( null, true );
        echo '</div>';
    }

    function listing_contact_form( $listing_id ) {
        if ( ! class_exists( 'WPBDP_Listing_Contact_View' ) )
            require_once( WPBDP_PATH . 'core/view-listing-contact.php' );

        $v = new WPBDP_Listing_Contact_View();
        echo $v->render_form( $listing_id );
    }

}

new _WPBDP_Template_Sections();

