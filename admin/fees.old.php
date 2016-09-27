<?php



class WPBDP_FeesAdmin {

    public function __construct() {
        $this->admin = wpbdp()->admin;
        $this->api = wpbdp()->fees;
    }

    public function dispatch() {
        $action = wpbdp_getv($_REQUEST, 'action');
        $_SERVER['REQUEST_URI'] = remove_query_arg(array('action', 'id'), $_SERVER['REQUEST_URI']);

        switch ($action) {
            case 'addfee':
            case 'editfee':
                $this->processFieldForm();
                break;
            case 'enablefee':
                $fee = WPBDP_Fee_Plan::find( $_REQUEST['id'] );
                if ( $fee && $fee->update( array( 'enabled' => 1 ) ) )
                    wpbdp_admin_message( _x( 'Fee enabled.', 'fees admin', 'WPBDM' ) );

                return $this->feesTable();

                break;
            case 'disablefee':
                $fee = WPBDP_Fee_Plan::find( $_REQUEST['id'] );
                if ( $fee && $fee->update( array( 'enabled' => 0 ) ) )
                    wpbdp_admin_message( _x( 'Fee disabled.', 'fees admin', 'WPBDM' ) );

                return $this->feesTable();

                break;
            case 'deletefee':
                $this->delete_fee();
                break;
            default:
                $this->feesTable();
                break;
        }
    }

    private function processFieldForm() {
        $fee_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $fee = $fee_id ? WPBDP_Fee_Plan::find( $fee_id ) : new WPBDP_Fee_Plan();

        if ( isset( $_POST['fee'] ) ) {
            if ( ! isset( $_POST['fee']['sticky'] ) )
                $_POST['fee']['sticky'] = 0;

            if ( $fee->update( stripslashes_deep( $_POST['fee'] ) ) ) {
                $this->admin->messages[] = _x('Fee updated.', 'fees admin', 'WPBDM');
                return $this->feesTable();
            }

            $this->admin->messages[] = array( $fee->errors->html() , 'error' );
        }

        wpbdp_render_page( WPBDP_PATH . 'admin/templates/fees-addoredit.tpl.php',
                           array(
                             'fee' => $fee,
                             'fee_extra_settings' => wpbdp_capture_action_array( 'wpbdp_admin_fee_form_extra_settings', array( &$fee ) )
                           ),
                           true );
    }

    private function delete_fee() {
        global $wpdb;

        $fee = WPBDP_Fee_Plan::find( $_REQUEST['id'] );

        if ( ! $fee )
            die();

        if (isset($_POST['doit'])) {
            if ( $fee->destroy() )
                $this->admin->messages[] = _x('Fee deleted.', 'fees admin', 'WPBDM');

            return $this->feesTable();
        } else {
            wpbdp_render_page(WPBDP_PATH . 'admin/templates/fees-confirm-delete.tpl.php',
                              array('fee' => $fee),
                              true);
        }
    }

}
