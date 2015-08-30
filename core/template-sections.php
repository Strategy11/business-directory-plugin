<?php
/**
 * @since themes-release
 */
class _WPBDP_Template_Sections {

    function __construct() {
        add_action( 'wpbdp_template_variables', array( &$this, 'add_contact_form' ), 10, 2 );
    }

    function register_sections( $themes ) {
        $themes->register_template_section( 'single', 'after', 'comments', array( &$this, 'listing_comments' ), 11 );
    }

    function add_contact_form( $vars, $template ) {
        if ( 'single' != $template )
            return $vars;

        $vars['#contact_form'] = array( 'callback' => array( &$this, 'listing_contact_form' ),
                                        'position' => 'after',
                                        'weight' => 0 );
        return $vars;
    }

    function listing_comments( $listing_id ) {
        if ( ! wpbdp_get_option( 'show-comment-form' ) )
            return;

        echo '<div class="comments">';
        comments_template( null, true );
        echo '</div>';
    }

    function listing_contact_form( $vars ) {
        if ( ! class_exists( 'WPBDP_Listing_Contact_View' ) )
            require_once( WPBDP_PATH . 'core/view-listing-contact.php' );

        $v = new WPBDP_Listing_Contact_View();
        return $v->render_form( $vars['listing_id'] );
    }

}

new _WPBDP_Template_Sections();

