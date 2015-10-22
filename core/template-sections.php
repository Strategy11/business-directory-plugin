<?php
/**
 * @since themes-release
 */
class _WPBDP_Template_Sections {

    function __construct() {
        add_action( 'wpbdp_template_variables', array( &$this, 'add_contact_form' ), 10, 2 );
        add_action( 'wpbdp_template_variables', array( &$this, 'add_comments' ), 999, 2 );
    }

    function add_contact_form( $vars, $template ) {
        if ( 'single' != $template )
            return $vars;

        $vars['#contact_form'] = array( 'callback' => array( &$this, 'listing_contact_form' ),
                                        'position' => 'after',
                                        'weight' => 10 );
        return $vars;
    }

    function listing_contact_form( $vars ) {
        if ( ! class_exists( 'WPBDP_Listing_Contact_View' ) )
            require_once( WPBDP_PATH . 'core/view-listing-contact.php' );

        $v = new WPBDP_Listing_Contact_View();
        return $v->render_form( $vars['listing_id'] );
    }

    function add_comments( $vars, $template ) {
        if ( 'single' != $template )
            return $vars;

        return $vars;
    }

    function listing_comments( $listing_id ) {
        if ( ! wpbdp_get_option( 'show-comment-form' ) )
            return;

        echo '<div class="comments">';
        comments_template( null, true );
        echo '</div>';
    }



}

new _WPBDP_Template_Sections();

