<?php
require_once ( WPBDP_PATH . 'core/class-gateway.php' );

/**
 * Dummy gateway used for testing.
 * @since 3.5.3
 */
class WPBDP_Dummy_Gateway extends WPBDP_Payment_Gateway {

    public function get_id() {
        return 'dummy';
    }

    public function get_name() {
        return _x( 'Dummy', 'dummy gateway', 'WPBDM' );
    }

    public function get_integration_method() {
        return WPBDP_Payment_Gateway::INTEGRATION_BUTTON;
    }

    public function get_supported_currencies() {
        return array( 'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'NOK',
                      'NZD', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'TWD', 'THB', 'TRY', 'USD' );
    }

    public function get_capabilities() {
        return array( 'recurring' );
    }

    public function validate_config() {
        return array();
    }

    public function render_integration( &$payment ) {
        $html  = '';
        $html .= sprintf( '<form action="%s" method="post">', $this->get_url( $payment, 'process' ) );
        $html .= '<b><u>' . _x( 'Dummy Gateway', 'dummy gateway', 'WPBDM' ) . '</u></b><br />';
        $html .= '<b>' . _x( 'New Status:', 'dummy gateway', 'WPBDM' ) . '</b> ';
        $html .= sprintf( '<label><input type="radio" name="status" value="completed" checked="checked" /> %s</label> ',
                          _x( 'Completed', 'dummy gateway', 'WPBDM' ) );
        $html .= sprintf( '<label><input type="radio" name="status" value="pending" /> %s</label> ',
                          _x( 'Pending', 'dummy gateway', 'WPBDM' ) );
        $html .= sprintf( '<label><input type="radio" name="status" value="canceled" /> %s</label> ',
                          _x( 'Canceled', 'dummy gateway', 'WPBDM' ) );
        $html .= sprintf( '<label><input type="radio" name="status" value="rejected" /> %s</label> ',
                          _x( 'Rejected', 'dummy gateway', 'WPBDM' ) );
        $html .= '<br />';
        $html .= sprintf( '<input type="submit" value="%s"/>', _x( 'Process Payment', 'dummy gateway', 'WPBDM' ) );
        $html .= '<hr />';
        $html .= '<textarea rows="10" style="width: 100%; font-family: monospace; font-size: 12px;">';
        $html .= esc_textarea( print_r( $payment, 1 ) );
        $html .= '</textarea>';
        $html .= '</form>';

        return $html;
    }

    public function process( &$payment, $action ) {
        if ( ! $payment->is_pending() )
            return;

        $new_status = isset( $_POST['status'] ) ? $_POST['status'] : '';

        if ( ! $new_status )
            return;

        $payment->set_status( $new_status, WPBDP_Payment::HANDLER_GATEWAY );
        $payment->save();

        wp_redirect( esc_url_raw( $payment->get_redirect_url() ) );
    }

}
