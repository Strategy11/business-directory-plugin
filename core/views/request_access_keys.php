<?php
require_once( WPBDP_PATH . 'core/class-view.php' );

/**
 * @since next-release
 */
class WPBDP_Request_Access_Keys_View extends WPBDP_NView {

    public function dispatch( $params = array() ) {
        $nonce = ! empty( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
        $errors = array();

        if ( $nonce && wp_verify_nonce( $nonce, 'request_access_keys' ) ) {
            $email = ! empty( $_POST['email'] ) ? trim( $_POST['email'] ) : '';

            if ( ! $email || ! is_email( $email ) )
                return wpbdp_render_msg( _x( 'Please enter a valid e-mail address.', 'request_access_keys', 'WPBDM' ), 'error' );

            $listings = $this->find_listings( $email );

            if ( ! $listings )
                return wpbdp_render_msg( _x( 'There are no listings associated to your e-mail address.', 'request_access_keys', 'WPBDM' ), 'error' );

            $message = wpbdp_email_from_template( WPBDP_PATH . 'templates/email-access-keys.tpl.php',
                                                  array( 'listings' => $listings ) );
            $message->subject = sprintf( '[%s] %s', get_bloginfo( 'name' ), _x( 'Listing Access Keys', 'request_access_keys', 'WPBDM' ) );
            $message->to = $email;

            if ( $message->send() ) {
                return wpbdp_render_msg( _x( 'Access keys have been sent to your e-mail address.', 'request_access_keys', 'WPBDM' ) );
            } else {
                return wpbdp_render_msg( _x( 'An error occurred while sending the access keys to your e-mail address. Please try again.', 'request_access_keys', 'WPBDM' ), 'error' );
            }
        }

        return $this->render( 'send-access-keys' );
    }

    private function find_listings( $email ) {
        $user = get_user_by( 'email', $email );

        if ( ! $user )
            return array();

        $posts = get_posts( array( 'post_type' => WPBDP_POST_TYPE,
                                   'author' => $user->ID,
                                   'posts_per_page' => -1,
                                   'fields' => 'ids' ) );
        $res = array();

        foreach ( $posts as $p_id )
            $res[] = WPBDP_Listing::get( $p_id );

        return $res;
    }

}
