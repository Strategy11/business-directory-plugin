<?php
/**
 * View API/class.
 * @since next-release
 */
class WPBDP__View {

    public function __construct( $args = null ) {
        if ( is_array( $args ) ) {
            foreach ( $args as $k => $v )
                $this->{$k} = $v;
        }
    }

    public function get_title() {
        return 'Unnamed View';
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

    //
    // }
    //
}

