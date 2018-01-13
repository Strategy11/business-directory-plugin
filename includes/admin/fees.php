<?php
/**
 * @since 5.0
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

            wp_enqueue_script(
                'wpbdp-admin-fees-js',
                WPBDP_URL . 'assets/js/admin-fees.min.js',
                array( 'wp-color-picker', 'wpbdp-js-select2' ),
                WPBDP_VERSION
            );

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
        return $this->insert_or_update_fee( 'insert' );
    }

    function edit_fee() {
        return $this->insert_or_update_fee( 'update' );
    }

    private function insert_or_update_fee( $mode ) {
        if ( ! empty( $_POST['fee'] ) ) {
            $posted_values = stripslashes_deep( $_POST['fee'] );

            if ( ! isset( $_POST['limit_categories'] ) || 0 == $_POST['limit_categories'] )
                $posted_values['supported_categories'] = 'all';

            if ( ! isset( $posted_values['sticky'] ) )
                $posted_values['sticky'] = 0;

            if ( ! isset( $posted_values['recurring'] ) )
                $posted_values['recurring'] = 0;
        } else {
            $posted_values = array();
        }

        if ( 'insert' == $mode ) {
            $fee = new WPBDP__Fee_Plan( $posted_values );
        } else {
            $fee = wpbdp_get_fee_plan( $_GET['id'] ) or die();
        }

        if ( $posted_values ) {
            if ( $fee->exists() ) {
                $result = $fee->update( $posted_values );
            } else {
                $result = $fee->save();
            }

            if ( ! is_wp_error( $result ) ) {
                if ( 'insert' == $mode ) {
                    wpbdp_admin_message( _x( 'Fee plan added.', 'fees admin', 'WPBDM' ) );
                } else {
                    wpbdp_admin_message( _x( 'Fee plan updated.', 'fees admin', 'WPBDM' ) );
                }

                return $this->_redirect( 'index' );
            } else {
                foreach ( $result->get_error_messages() as $msg ) {
                    wpbdp_admin_message( $msg, 'error' );
                }
            }
        }

        return array( 'fee' => $fee );
    }

    function delete_fee() {
        $fee = wpbdp_get_fee_plan( $_GET['id'] ) or die();

        list( $do, $html ) = $this->_confirm_action( array(
            'cancel_url' => remove_query_arg( array( 'wpbdp-view', 'id' ) ),
        ) );

        if ( $do && $fee->delete() ) {
            wpbdp_admin_message( sprintf( _x( 'Fee "%s" deleted.', 'fees admin', 'WPBDM' ), $fee->label ) );
            return $this->_redirect( 'index' );
        }

        return $html;
    }

    function toggle_fee() {
        $fee = wpbdp_get_fee_plan( $_GET['id'] ) or die();
        $fee->enabled = ! $fee->enabled;
        $fee->save();

        wpbdp_admin_message( _x( 'Fee disabled.', 'fees admin', 'WPBDM' ) );
        return $this->_redirect( 'index' );
    }

}
