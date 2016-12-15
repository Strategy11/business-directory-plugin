<?php

class WPBDP__Installer__Installation_Error {

    private $exception;

    public function __construct( $exception ) {
        $this->exception = $exception;

        add_action( 'admin_notices', array( $this, 'installation_error_notice' ) );
    }

    public function installation_error_notice() {
        print '<div class="notice notice-error"><p>';
        print '<strong>' . __( 'Business Directory - Installation Failed', 'WPBDM' ) . '</strong>';
        print '<br />';
        print  __( 'Business Directory installation failed. An exception with following message was generated:', 'WPBDM' );
        print '<br/><br/>';
        print '<i>' . $this->exception->getMessage() . '</i>';
        print '<br /><br />';

        $message = __( 'Please <contact-link>contact customer support</a>.', 'WPBDM' );
        $message = str_replace( '<contact-link>', sprintf( '<a href="%s">', 'http://businessdirectoryplugin.com/contact/' ), $message );

        print $message;
        print '</p></div>';
    }
}
