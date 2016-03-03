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



            wpbdp_debug_e( $listings );
        }

        return $this->render( 'send-access-keys' );
    }

    private function find_listings( $email ) {
        $user = get_user_by( 'email', $email );

        if ( ! $user )
            return array();

        return get_posts( array(
            'post_type' => WPBDP_POST_TYPE,
            'author' => $user->ID,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ) );
    }

}
