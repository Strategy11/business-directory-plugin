<?php
/**
 * @since 4.0
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
        if ( ! class_exists( 'WPBDP__Views__Listing_Contact' ) )
            require_once( WPBDP_PATH . 'core/views/listing_contact.php' );

        $v = new WPBDP__Views__Listing_Contact();
        return $v->render_form( $vars['listing_id'] );
    }

    function add_comments( $vars, $template ) {
        if ( 'single' != $template )
            return $vars;

        $vars['#comments'] = array( 'callback' => array( $this, 'listing_comments' ),
                                    'position' => 'after',
                                    'weight' => 100 );
        return $vars;
    }

    function listing_comments( $listing_id ) {
        if ( ! wpbdp_get_option( 'show-comment-form' ) )
            return;

        $html = '<div class="comments">';

        ob_start();
        comments_template( null, true );
        $html .= ob_get_clean();

        $html .= '</div>';

        return $html;
    }



}

new _WPBDP_Template_Sections();

