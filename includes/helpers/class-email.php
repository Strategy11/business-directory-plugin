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


	public function __construct() {
	}

	private function prepare_html() {
		if (!$this->html) {
			$text = $this->body ? $this->body : $this->plain;
			$text = str_ireplace(array("<br>", "<br/>", "<br />"), "\n", $text);
			$this->html = nl2br($text);
		}
	}

	private function prepare_plain() {
		if (!$this->plain) {
			$text = $this->body ? $this->body : $this->html;
			$this->plain = strip_tags($text); // FIXME: this removes 'valid' plain text like <whatever>
		}
	}

    private function get_headers() {
        $headers = array();

        if ( ! isset( $this->headers['MIME-Version'] ) ) {
            $headers[] = 'MIME-Version: 1.0';
        }

        if ( ! isset( $this->headers['Content-Type'] ) ) {
            $headers[] = 'Content-Type: text/html; charset=' . get_option( 'blog_charset' );
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
		    if ( in_array( $k, array( 'MIME-Version', 'Content-Type', 'From', 'Cc', 'Bcc' ) ) )
		        continue;

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

		// TODO: implement 'plain' and 'both'
		$this->prepare_html();
		$this->prepare_plain();

		$this->from = preg_replace( '/[\n\r]/', '', $this->from ? $this->from : sprintf( '%s <%s>', get_option( 'blogname' ), get_option( 'admin_email' ) ) );
		$to = preg_replace( '/[\n\r]/', '', $this->to );

		if ( ! $this->to )
		    return false;

        $html = $this->html;
        if ( $this->template ) {
            if ( $html_ = wpbdp_render( $this->template, array( 'subject' => $this->subject,
                                                                'body' => $this->html ) ) ) {
                $html = $html_;
            }
        }

		return wp_mail( $this->to, $this->subject, $html, $this->get_headers() );
	}

}

