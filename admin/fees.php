<?php
/**
 * @since next-release
 */
class WPBDP__Admin__Fees extends WPBDP__Admin__Controller {

    function __construct() {
        parent::__construct();
        $this->api = $this->wpbdp->fees;
    }

    function index() {
        require_once( WPBDP_PATH . 'admin/helpers/class-fees-table.php' );

        $table = new WPBDP__Admin__Fees_Table();
        $table->prepare_items();

        $order_options = array();
        foreach ( array( 'label' => _x( 'Label', 'fees order', 'WPBDM' ),
                         'amount' => _x( 'Amount', 'fees order', 'WPBDM' ),
                         'days' => _x( 'Duration', 'fees order', 'WPBDM' ),
                         'images' => _x( 'Images', 'fees order', 'WPBDM' ),
                         'custom' => _x( 'Custom Order', 'fees order', 'WPBDM' ) ) as $k => $l ) {
            $order_options[ $k ] = $l;
        }

        return array(
            'table' => $table,
            'order_options' => $order_options,
            'current_order' => wpbdp_get_option( 'fee-order' )
        );
    }

    function add_fee() {
        if ( ! empty( $_POST['fee'] ) ) {
            $posted_values = stripslashes_deep( $_POST['fee'] );

            if ( ! isset( $_POST['limit_categories'] ) )
                $posted_values['supported_categories'] = 'all';

            $fee = new WPBDP_Fee_Plan( $posted_values );

            if ( $fee->save() ) {
                wpbdp_admin_message( _x( 'Fee updated.', 'fees admin', 'WPBDM' ) );
                return $this->_redirect( 'index' );
            }

            wpbdp_admin_message( $fee->errors->html(), 'error' );
        } else {
            $fee = new WPBDP_Fee_Plan();
        }

        return array( 'fee' => $fee );
    }

}
