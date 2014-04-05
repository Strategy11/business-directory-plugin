<?php
require_once( WPBDP_PATH . 'core/class-view.php' );

/**
 * Listing contact page.
 * @since 3.4
 */
class WPBDP_Listing_Contact_Page extends WPBDP_View {

    private $errors = array();

    private $name = '';
    private $email = '';
    private $message = '';


    public function __construct() {
    }

    public function get_page_name() {
        return 'sendcontactmessage';
    }

    private function prepare_input() {
        if ( $_POST )
            $_POST = stripslashes_deep( $_POST );

        $current_user = is_user_logged_in() ? wp_get_current_user() : null;

        $this->name = $current_user ? $current_user->data->user_login : ( isset( $_POST['commentauthorname'] ) ? trim( $_POST['commentauthorname'] ) : '' );
        $this->email = $current_user ? $current_user->data->user_email : ( isset( $_POST['commentauthoremail'] ) ? trim( $_POST['commentauthoremail'] ) : '' );
        $this->message = isset( $_POST['commentauthormessage'] ) ? trim( wp_kses( $_POST['commentauthormessage'], array() ) ) : '';
    }

    private function validate() {
        $this->errors = array();

        if ( ! $this->name )
            $this->errors[] = _x( 'Please enter your name.', 'contact-message', 'WPBDM' );

        if ( ! wpbdp_validate_value( $this->email, 'email' ) )
            $this->errors[] = _x( "Please enter a valid email.", 'contact-message', "WPBDM" );

        if ( ! $this->message )
            $this->errors[] = _x( 'You did not enter a message.', 'contact-message', 'WPBDM' );

        if ( $this->errors )
            return false;

        if ( wpbdp_get_option( 'recaptcha-on' ) ) {
            if ( $private_key = wpbdp_get_option( 'recaptcha-private-key' ) ) {
                if ( ! function_exists( 'recaptcha_get_html' ) )
                    require_once( WPBDP_PATH . 'vendors/recaptcha/recaptchalib.php' );

                $resp = recaptcha_check_answer( $private_key, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field'] );

                if ( ! $resp->is_valid )
                    $this->errors[] = _x( "The reCAPTCHA wasn't entered correctly.", 'contact-message', 'WPBDM' );
            }
        }

        return true;
    }

    public function dispatch() {
        $listing_id = intval( isset( $_REQUEST['listing_id'] ) ? $_REQUEST['listing_id'] : 0 );

        if ( ! $listing_id )
            return;

        $this->prepare_input();

        if ( ! $this->validate() )
            return wpbdp_listing_contact_form( $listing_id, $this->errors );

        // Prepare e-mail message.
        $email = new WPBDP_Email();
        $email->subject = "[" . get_option( 'blogname' ) . "] " . sprintf(_x('Contact via "%s"', 'contact email', 'WPBDM'), wp_kses( get_the_title($listing_id), array() ) );
        $email->from = "{$this->name} <{$this->email}>";
        $email->to = wpbusdirman_get_the_business_email( $listing_id );
        $email->reply_to = $this->email;

        if ( in_array( 'listing-contact', wpbdp_get_option( 'admin-notifications' ), true ) ) {
            $email->cc[] = get_bloginfo( 'admin_email' );

            if ( wpbdp_get_option( 'admin-notifications-cc' ) )
                $email->cc[] = wpbdp_get_option( 'admin-notifications-cc' );
        }

        $replacements = array( '[listing-url]' => get_permalink( $listing_id  ),
                               '[listing]' => get_the_title( $listing_id ),
                               '[name]' => $this->name,
                               '[email]' => $this->email,
                               '[message]' => $this->message,
                               '[date]' => date_i18n( __('l F j, Y \a\t g:i a'), current_time( 'timestamp' ) ) );
        $email->body = str_replace( array_keys( $replacements ), $replacements, wpbdp_get_option( 'email-templates-contact' ) );

        $html = '';

        if( $email->send() ) {
            $html .= "<p>" . _x("Your message has been sent.", 'contact-message', "WPBDM") . "</p>";
        } else {
            $html .= "<p>" . _x("There was a problem encountered. Your message has not been sent", 'contact-message', "WPBDM") . "</p>";
        }

        $html .= sprintf('<p><a href="%s">%s</a></p>', get_permalink($listing_id), _x('Return to listing.', 'contact-message', "WPBDM"));
        return $html;
    }

}

