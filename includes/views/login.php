<?php
/**
 * WPBDP Login page.
 *
 * @package WPBDP/Views/Login
 */

// phpcs:disable

/**
 * @since 5.0
 *
 * @SuppressWarnings(PHPMD)
 */
class WPBDP__Views__Login extends WPBDP__View {

    public function dispatch() {
        $redirect_to = ! empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : wp_get_referer();

        if ( ! $redirect_to ) {
            $redirect_to = wpbdp_url( 'main' );
        }

        $redirect_to = remove_query_arg( 'access_key_hash', $redirect_to );

        $key_access_enabled = wpbdp_get_option( 'enable-key-access' );

        if ( is_user_logged_in() ) {
            return $this->_redirect( $redirect_to );
        }

        $login_url = trim( wpbdp_get_option( 'login-url' ) );

        if ( $login_url ) {
            return $this->_redirect( add_query_arg( 'redirect_to', urlencode( $redirect_to ), $login_url ) );
        }

        if ( ! empty( $_POST['method'] ) && 'access_key' == $_POST['method'] ) {
            $email = trim( $_POST['email'] );
            $key = trim( $_POST['access_key'] );

            if ( WPBDP_Listing::validate_access_key( $key, $email ) ) {
                $hash = sha1( AUTH_KEY . $key );
                $redirect_to = add_query_arg( 'access_key_hash', $hash, $redirect_to );
                $this->_redirect( $redirect_to );
            } else {
                $errors = array( _x( 'Please enter a valid e-mail/access key combination.', 'views:login', 'WPBDM' ) );
            }
        }

        $params = array(
            'redirect_to' => $redirect_to,
            'access_key_enabled' => $key_access_enabled,
            'request_access_key_url' => add_query_arg( 'redirect_to', urlencode( $redirect_to ), wpbdp_url( 'request_access_keys' ) ),
        );

        return $this->_render( 'login', $params );
    }

}
