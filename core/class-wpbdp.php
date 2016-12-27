<?php
require_once( WPBDP_PATH . 'core/class-query-integration.php' );
require_once( WPBDP_PATH . 'core/class-dispatcher.php' );
require_once( WPBDP_PATH . 'core/class-cpt-integration.php' );
require_once( WPBDP_PATH . 'core/class-wordpress-template-integration.php' );

require_once( WPBDP_PATH . 'core/class-listing-expiration.php' );
require_once( WPBDP_PATH . 'core/class-listing-email-notification.php' );


final class WPBDP {

    // FIXME: only to allow the global object to access it (for now).
    public $dispatcher = null;
    public $query_integration = null;
    public $template_integration = null;


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

}

?>
