<?php
/**
 * E-mail handling class.
 * @since 2.1
 */
class WPBDP_Email {

    public $headers = array();
    public $subject = '';
    public $from = '';
    public $reply_to = '';
    public $to = array();
    public $cc = array();
    public $bcc = array();

    public $body = '';
    public $plain = '';
    public $html = '';
    public $template = '';
    public $boundary = '';

    public function __construct() {
    }

	private function set_boundary() {
        $this->boundary = uniqid('wpbdp');
    }

    private function prepare_html() {
        $text = "\r\n\r\n--" . $this->boundary . "\r\n";
        $text .= "Content-type: text/html; charset=" . get_option( 'blog_charset' ) ."\r\n\r\n";
        $text .= '<html>';

        if ( ! $this->html ) {
            $_text = $this->body ? $this->body : $this->plain;
            $_text = str_ireplace(array("<br>", "<br/>", "<br />"), "\n", $_text);
            $text .= nl2br( $_text );
        } else {
            $text .= $this->html;
        }
        $text .= '</html>';
        $this->html = $text;
    }

    private function prepare_plain() {
        $text = "\r\n\r\n--" . $this->boundary . "\r\n";
        $text .= "Content-type: text/plain; charset=" . get_option( 'blog_charset' ) . "\r\n\r\n";

        if ( !$this->plain ) {
            $text .= $this->body ? $this->body : $this->html;
            $this->plain = strip_tags( $text ); // FIXME: this removes 'valid' plain text like <whatever>
        } else {
            $this->plain = $text . $this->plain;
        }
    }

    private function get_message() {
        $this->prepare_plain();

        if ( $this->template ) {
            if ( $html_ = wpbdp_render( $this->template, array( 'subject' => $this->subject,
                'body' => $this->html ) ) ) {
                $this->html = $html_;
            }
        }
        $this->prepare_html();

        $message = $this->plain . $this->html;
        $message .= "\r\n\r\n--" . $this->boundary . "--";

        return $message;
    }

    private function get_headers() {
        $headers = array();

        if ( ! isset( $this->headers['MIME-Version'] ) ) {
            $headers[] = 'MIME-Version: 1.0';
        }

        if ( ! isset( $this->headers['Content-Type'] ) ) {
            $headers[] = 'Content-Type: multipart/alternative; boundary=' . $this->boundary . '; charset=' . get_option( 'blog_charset' );
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
            if ( in_array( $k, array( 'MIME-Version', 'Content-Type', 'From', 'Cc', 'Bcc' ) ) ) {
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
        $this->set_boundary();
        $this->subject = preg_replace( '/[\n\r]/', '', strip_tags( html_entity_decode( $this->subject ) ) );

        $this->from = preg_replace( '/[\n\r]/', '', $this->from ? $this->from : sprintf( '%s <%s>', get_option( 'blogname' ), get_option( 'admin_email' ) ) );
        $this->to = preg_replace( '/[\n\r]/', '', $this->to );

        if ( ! $this->to ) {
            return false;
        }

        return wp_mail( $this->to, $this->subject, $this->get_message(), $this->get_headers() );
    }
}

