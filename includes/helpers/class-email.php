<?php
/**
 * E-mail handling class.
 * @since 2.1
 */
class WPBDP_Email {

    public $headers = array();
    public $subject = '';
    public $content_type = '';
    public $from = '';
    public $reply_to = '';
    public $to = array();
    public $cc = array();
    public $bcc = array();

    public $body = '';
    public $html = '';
    public $template = '';
    public $boundary = '';

    public function __construct() {
        $this->content_type = wpbdp_get_option( 'listing-email-content-type', 'html' );
    }

    public function wpbdp_email_config ( &$phpmailer ) {
        if ( 'plain' == $this->content_type ) {
            $phpmailer->Body = $phpmailer->normalizeBreaks( $phpmailer->html2text( $this->html ) );
            $phpmailer->isHTML( false );
        }

        if ( 'html' == $this->content_type ) {
            $phpmailer->Body = $phpmailer->normalizeBreaks( $this->html );
            $phpmailer->isHTML( true );
        }

        if ( 'both' == $this->content_type ) {
            $phpmailer->msgHTML( $this->html );
        } else {
            $phpmailer->AltBody = '';
        }
    }

    private function prepare_html() {
        if ( ! $this->html ) {
            $text = '<html>';
            $_text = $this->body ? $this->body : '';
            $_text = str_ireplace(array("<br>", "<br/>", "<br />"), "\n", $_text);
            $text .= nl2br( $_text );
            $text .= '</html>';
        } else {
            $text = $this->html;
        }

        $this->html = $text;
    }

    private function get_headers() {
        $headers = array();

        if ( ! isset( $this->headers['MIME-Version'] ) ) {
            $headers[] = 'MIME-Version: 1.0';
        }

        $headers[] = 'From: ' . $this->from;

        foreach ( (array) $this->cc as $address ) {
            $headers[] = 'Cc: ' . $address;
        }

        foreach ( (array) $this->bcc as $address ) {
            $headers[] = 'Bcc: ' . $address;
        }

        if ( $this->reply_to ) {
            $headers[] = 'Reply-To: ' . $this->reply_to;
        }

        foreach ( $this->headers as $k => $v ) {
            if ( in_array( $k, array( 'MIME-Version', 'From', 'Cc', 'Bcc' ) ) ) {
                continue;
            }

            $headers[] = "$k: $v";
        }

        return $headers;
    }

    /**
     * Sends the email.
     * @param string $format allowed values are 'html', 'plain' or 'both'
     * @return boolean true on success, false otherwise
     */
    public function send($format='both') {
        $this->subject = preg_replace( '/[\n\r]/', '', strip_tags( html_entity_decode( $this->subject ) ) );
        $this->from = preg_replace( '/[\n\r]/', '', $this->from ? $this->from : sprintf( '%s <%s>', get_option( 'blogname' ), get_option( 'admin_email' ) ) );
        $this->to = preg_replace( '/[\n\r]/', '', $this->to );

        if ( ! $this->to ) {
            return false;
        }
        if ( $this->template ) {
            if ( $html_ = wpbdp_render( $this->template, array( 'subject' => $this->subject,
                'body' => $this->html ) ) ) {
                $this->html = $html_;
            }
        }

        $this->prepare_html();
        $message = $this->html;
        $headers = $this->get_headers();

        add_action( 'phpmailer_init', array( $this, 'wpbdp_email_config' ), 10 );
        $result = wp_mail( $this->to, $this->subject, $message, $headers );
        remove_action( 'phpmailer_init', array( $this, 'wpbdp_email_config' ), 10 );

        return $result;
    }
}
