<?php
/**
 * View API/class.
 * @since 5.0
 */
class WPBDP__View {

    public function __construct( $args = null ) {
        if ( is_array( $args ) ) {
            foreach ( $args as $k => $v )
                $this->{$k} = $v;
        }
    }

    public function get_title() {
        return '';
    }

    public function enqueue_resources() {
    }

    public function dispatch() {
        return '';
    }


    //
    // API for views. {
    //

    protected final function _http_404() {
        status_header( 404 );
        nocache_headers();

        if ( $template_404 = get_404_template() )
            include( $template_404 );

        exit;
    }

    protected final function _redirect( $url ) {
        wp_redirect( $url );
        exit;
    }

    protected final function _render() {
        $args = func_get_args();
        return call_user_func_array( 'wpbdp_x_render', $args );
    }

    protected final function _render_page() {
        $args = func_get_args();
        return call_user_func_array( 'wpbdp_x_render_page', $args );
    }

    protected final function _auth_required( $args = array() ) {
        $defaults = array(
            'test' => '',
            'login_url' => wpbdp_url( 'login' ),
            'redirect_on_failure' => true
        );
        $args = wp_parse_args( $args, $defaults );
        extract( $args );

        if ( ! $test && method_exists( $this, 'authenticate' ) )
            $test = array( $this, 'authenticate' );

        if ( is_callable( $test ) )
            $passes = call_user_func( $test );
        elseif ( 'administrator' == $test )
            $passes = current_user_can( 'administrator' );
        else
            $passes = is_user_logged_in();

        if ( $passes )
            return;

        if ( is_user_logged_in() )
            $redirect_on_failure = false;

        if ( $redirect_on_failure ) {
            $current_url = urlencode( site_url( $_SERVER['REQUEST_URI'] ) );
            $login_url = add_query_arg( 'redirect_to', $current_url, $login_url );

            return $this->_redirect( $login_url );
        } else {
            return wpbdp_render_msg( _x( 'Invalid credentials.', 'views', 'WPBDM' ), 'error' );
        }
    }

    //
    // }
    //
}

/**
 * @deprecated since 5.0. Use {@link WPBDP__View}.
 */
class WPBDP_NView extends WPBDP__View {}
