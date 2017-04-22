<?php
/**
 * @since next-release
 */
class WPBDP__Admin__Fees extends WPBDP__Admin__Controller {

    function __construct() {
        parent::__construct();
        $this->api = $this->wpbdp->fees;
    }

    /**
     * @override
     */
    function _enqueue_scripts() {
        switch ( $this->current_view ) {
        case 'add-fee':
        case 'edit-fee':
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_style( 'wpbdp-js-select2-css' );
            wp_enqueue_script( 'wpbdp-admin-fees-js', WPBDP_URL . 'assets/js/admin-fees.min.js', array( 'wp-color-picker', 'wpbdp-js-select2' ) );

            break;
        default:
            break;
        }

        if ( ! in_array( $this->current_view, array( 'add-fee', 'edit-fee' ), true ) )
            return;
    }

    function index() {
        require_once( WPBDP_PATH . 'includes/admin/helpers/class-fees-table.php' );

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

            if ( ! isset( $posted_values['sticky'] ) )
                $posted_values['sticky'] = 0;

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

    function edit_fee() {
        $fee = WPBDP_Fee_Plan::find( $_GET['id'] ) or die();

        if ( ! empty( $_POST['fee'] ) ) {
            $posted_values = stripslashes_deep( $_POST['fee'] );

            if ( ! isset( $_POST['limit_categories'] ) || 0 == $_POST['limit_categories'] )
                $posted_values['supported_categories'] = 'all';

            if ( ! isset( $posted_values['sticky'] ) )
                $posted_values['sticky'] = 0;

            if ( $fee->update( $posted_values ) ) {
                wpbdp_admin_message( _x( 'Fee updated.', 'fees admin', 'WPBDM' ) );
                return $this->_redirect( 'index' );
            } else {
                wpbdp_admin_message( $fee->errors->html(), 'error' );
            }
        }

        return array( 'fee' => $fee );
    }

    function delete_fee() {
        $fee = WPBDP_Fee_Plan::find( $_GET['id'] ) or die();
        list( $do, $html ) = $this->_confirm_action();

        if ( $do && $fee->destroy() ) {
            wpbdp_admin_message( sprintf( _x( 'Fee "%s" deleted.', 'fees admin', 'WPBDM' ), $fee->label ) );
            return $this->_redirect( 'index' );
        }

        return $html;
    }

    function toggle_fee() {
        $fee = WPBDP_Fee_Plan::find( $_GET['id'] ) or die();
        $fee->enabled = ! $fee->enabled;
        $fee->save();

        wpbdp_admin_message( _x( 'Fee disabled.', 'fees admin', 'WPBDM' ) );
        return $this->_redirect( 'index' );
    }

}
