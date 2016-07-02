<?php
/**
 * @since 3.6.8
 */
class WPBDP_reCAPTCHA {

    private $public_key = '';
    private $private_key = '';

    private $current_id = 1;

    private $comment_error = null;


    function __construct() {
        $this->public_key = trim( wpbdp_get_option( 'recaptcha-public-key' ) );
        $this->private_key = trim( wpbdp_get_option( 'recaptcha-private-key' ) );

        if ( empty( $this->public_key ) || empty( $this->private_key ) )
            return;

        add_action( 'wp_enqueue_scripts', array( &$this, '_enqueue_js_api' ) );

        if ( wpbdp_get_option( 'recaptcha-for-comments' ) ) {
            add_filter( 'comment_form_field_comment', array( &$this, '_recaptcha_in_comments' ) );
            add_filter( 'preprocess_comment', array( &$this, '_check_comment_recaptcha' ), 0 );
            add_action( 'comment_post_redirect', array( &$this, '_comment_relative_redirect' ), 0, 2 );
        }

    }

    function _enqueue_js_api() {
        global $wpbdp;

        if ( ! $wpbdp->is_plugin_page() )
            return '';

        wp_enqueue_script( 'wpbdp-recaptcha',
                            WPBDP_URL . 'core/js/recaptcha' . ( ! $wpbdp->is_debug_on() ? '.min' : '' ) . '.js',
                            false,
                            true );
        wp_enqueue_script( 'google-recaptcha',
                           'https://www.google.com/recaptcha/api.js?onload=wpbdp_recaptcha_callback&render=explicit' );
    }

    function render( $name = '' ) {
        if ( empty( $this->public_key ) || empty( $this->private_key ) )
            return '';

        $hide_recaptcha  = wpbdp_get_option( 'hide-recaptcha-loggedin' );
        if( is_user_logged_in() && $hide_recaptcha ){
            return '';
        }

        $html  = '';

        if ( $name )
            $html .= '<div id="' . $name . '">';

        $html .= sprintf( '<div id="wpbdp_recaptcha_%d" class="wpbdp-recaptcha" data-key="%s"></div>',
                          $this->current_id,
                          $this->public_key );

        if ( $name )
            $html .= '</div>';

        $this->current_id++;

        return $html;
    }

    public function verify( &$error_msg = null ) {
        global $wpbdp;

        if ( empty( $this->public_key ) || empty( $this->private_key ) )
            return true;

        $hide_recaptcha  = wpbdp_get_option( 'hide-recaptcha-loggedin' );
        if( is_user_logged_in() && $hide_recaptcha ){
            return true;
        }

        if ( ! $_REQUEST['g-recaptcha-response'] )
            return false;

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $res = wp_remote_post( $url,
                               array( 'body' => array( 'secret' => $this->private_key,
                                                       'response' => $_REQUEST['g-recaptcha-response'],
                                                       'remoteip' => $_SERVER['REMOTE_ADDR'] ) )
        );

        if ( ! is_wp_error( $res ) ) {
            $js = json_decode( $res['body'] );

            if ( $js && isset( $js->success ) && $js->success )
                return true;
        }

        $error_msg = _x( 'The reCAPTCHA wasn\'t entered correctly.', 'recaptcha', 'WPBDM' );
        return false;
    }

    function _recaptcha_in_comments( $field ) {
        global $wpbdp;

        if ( ! wpbdp_current_view() )
            return $field;

        $html  = '';
        $html .= $field;

        if ( ! empty( $_GET['wre'] ) ) {
            $html .= '<p class="wpbdp-recaptcha-error">';
            $html .= _x( 'The reCAPTCHA wasn\'t entered correctly.', 'recaptcha', 'WPBDM' );
            $html .= '</p>';

            add_action( 'wp_footer', array( &$this, '_restore_comment_fields' ) );
        }

        $html .= $this->render();
        return $html;
    }

    function _check_comment_recaptcha( $comment_data ) {
        $post_id = isset( $comment_data['comment_post_ID'] ) ? $comment_data['comment_post_ID'] : 0;

        if ( WPBDP_POST_TYPE != get_post_type( $post_id ) )
            return $comment_data;

        if ( ! $this->verify() ) {
            $this->comment_error = true;
            add_filter( 'pre_comment_approved', create_function( '$a', 'return \'spam\';' ) );
        }

        return $comment_data;
    }

    function _comment_relative_redirect( $location, $comment ) {
        if ( is_null( $this->comment_error ) )
            return $location;

        $location = substr( $location, 0, strpos( $location, '#' ) );
        $location = add_query_arg( 'wre', urlencode( base64_encode( $comment->comment_ID ) ), $location );
        $location .= '#commentform';

        return $location;
    }

    function _restore_comment_fields() {
        $comment_id = isset( $_GET['wre'] ) ? absint( base64_decode( urldecode( $_GET['wre'] ) ) ) : 0;

        if ( ! $comment_id )
            return;

        $comment = get_comment( $comment_id );
        if ( ! $comment )
            return;

        echo <<<JS
        <script type="text/javascript">//<![CDATA[
            jQuery( '#comment' ).val( "{$comment->comment_content}" );
        //}}>
        </script>
JS;
    }

}


/**
 * Displays a reCAPTCHA field using the configured settings.
 * @return string HTML for the reCAPTCHA field.
 * @since 3.4.2
 */
function wpbdp_recaptcha( $name = '' ) {
    global $wpbdp;
    return $wpbdp->recaptcha->render( $name );
}

/**
 * Validates reCAPTCHA input.
 * @return boolean TRUE if validation succeeded, FALSE otherwise.
 * @since 3.4.2
 */
function wpbdp_recaptcha_check_answer( &$error_msg = null ) {
    global $wpbdp;
    return $wpbdp->recaptcha->verify( $error_msg );
}

