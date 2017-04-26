<?php
require_once( WPBDP_PATH . 'includes/class-query-integration.php' );
require_once( WPBDP_PATH . 'includes/class-dispatcher.php' );
require_once( WPBDP_PATH . 'includes/class-cpt-integration.php' );
require_once( WPBDP_PATH . 'includes/class-wordpress-template-integration.php' );

require_once( WPBDP_PATH . 'includes/class-listing-expiration.php' );
require_once( WPBDP_PATH . 'includes/class-listing-email-notification.php' );


final class WPBDP {

    // public $cpt_integration = null;
    // public $query_integration = null;
    // public $dispatcher = null;
    // public $query_integration = null;
    // public $template_integration = null;


    public function __construct() {
    }

    public function init() {
        $this->cpt_integration = new WPBDP__CPT_Integration();
        $this->query_integration = new WPBDP__Query_Integration();
        $this->dispatcher = new WPBDP__Dispatcher();
        $this->template_integration = new WPBDP__WordPress_Template_Integration();

        $this->listing_expiration = new WPBDP__Listing_Expiration();
        $this->listing_email_notification = new WPBDP__Listing_Email_Notification();
    }

    // Inject new vars to old plugin class while we complete the move.
    // FIXME: this is not done.
    public function _inject_vars( $old_object ) {
        foreach ( get_object_vars( $this ) as $var_name => $var_content ) {
            $old_object->{$var_name} = $var_content;
        }
    }

}

?>
