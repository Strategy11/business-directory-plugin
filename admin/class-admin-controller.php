<?php
/**
 * @since next-release
 */
class WPBDP__Admin__Controller {

    protected $wpbdp;
    protected $current_view = '';
    protected $controller_id = '';


    function __construct() {
        $this->wpbdp = $GLOBALS['wpbdp'];
        $this->controller_id = str_replace( 'wpbdp__admin__', '', WPBDP_Utils::normalize( get_class( $this ) ) );
    }

    function _dispatch() {
        $this->current_view = isset( $_GET['wpbdp-view'] ) ? $_GET['wpbdp-view'] : 'index';
        $this->current_view = WPBDP_Utils::normalize( $this->current_view );

        $result = false;
        $output = '';

        $callback = ( false !== strpos( $this->current_view, '-' ) ? str_replace( '-', '_', $this->current_view ) : $this->current_view );

        if ( method_exists( $this, $callback ) )
            $result = call_user_func( array( $this, $callback ) );

        if ( is_array( $result ) ) {
            $template = WPBDP_PATH . 'admin/templates/' . $this->controller_id . '-' . $this->current_view . '.tpl.php';

            if ( ! file_exists( $template ) )
                $output = json_encode( $result );
            else
                $output = wpbdp_render_page( $template, $result );
        } else {
            $output = $result;
        }

        echo $output;
    }

}
