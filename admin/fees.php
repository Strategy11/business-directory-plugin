<?php
if (!class_exists('WP_List_Table'))
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class WPBDP_FeesTable extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => _x('fee', 'fees admin', 'WPBDM'),
            'plural' => _x('fees', 'fees admin', 'WPBDM'),
            'ajax' => false
        ));
    }

    public function no_items() {
        echo _x('You do not have any listing fees setup yet.', 'fees admin', 'WPBDM');
    }

    public function get_columns() {
        return array(
/*            'order' => _x( 'Order', 'fees admin', 'WPBDM' ),*/
            'label' => _x('Label', 'fees admin', 'WPBDM'),
            'amount' => _x('Amount', 'fees admin', 'WPBDM'),
            'duration' => _x('Duration', 'fees admin', 'WPBDM'),
            'images' => _x('Images', 'fees admin', 'WPBDM'),
            'categories' => _x('Applied To', 'fees admin', 'WPBDM')
        );
    }

    public function prepare_items() {
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        // XXX: For now, we keep the free plan a 'secret' when payments are enabled. This is for backwards compat.
        if ( wpbdp_payments_possible() ) {
            $this->items = WPBDP_Fee_Plan::find( array( '-tag' => 'free' ) );
        } else {
            $this->items = WPBDP_Fee_Plan::find( 'all' );
        }
    }

    /* Rows */
    public function single_row( $item ) {
        $free_mode = ( ! wpbdp_payments_possible() );
        $classes = '';

        if ( $free_mode && $item->amount > 0.0 )
            $classes .= 'disabled-fee';
        elseif ( 'free' == $item->tag )
            $classes .= 'free-fee';

        echo '<tr class="' . $classes . '">';
        $this->single_row_columns( $item );
        echo '</tr>';

        if ( 'free' == $item->tag ) {
            echo '<tr></tr>';
            echo '<tr class="wpbdp-item-message-tr">';
            echo '<td colspan="' . count( $this->get_columns() ) . '">';
            echo '<div>Message about this fee plan here...</div>';
            echo '</td>';
            echo '</tr>';
        }

        if ( $free_mode && $item->amount > 0.0 ) {
            echo '<tr></tr>';
            echo '<tr class="wpbdp-item-message-tr">';
            echo '<td colspan="' . count( $this->get_columns() ) . '">';
            echo '<div>';
            _ex( 'Fee plan disabled because directory is in free mode.', 'fees admin', 'WPBDM' );
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
    }

    public function column_order( $fee ) {
        return sprintf( '<span class="wpbdp-drag-handle" data-fee-id="%s"></span> <a href="%s"><strong>↑</strong></a> | <a href="%s"><strong>↓</strong></a>',
                        $fee->id, 
                        esc_url( add_query_arg( array('action' => 'feeup', 'id' => $fee->id ) ) ),
                        esc_url( add_query_arg( array('action' => 'feedown', 'id' => $fee->id ) ) )
                       );
    }

    public function column_label($fee) {
        $actions = array();
        $actions['edit'] = sprintf('<a href="%s">%s</a>',
                                   esc_url(add_query_arg(array('action' => 'editfee', 'id' => $fee->id))),
                                   _x('Edit', 'fees admin', 'WPBDM'));

        if ( 'free' == $fee->tag ) {
            $actions['delete'] = sprintf('<a href="%s">%s</a>',
                                       esc_url(add_query_arg(array('action' => 'deletefee', 'id' => $fee->id))),
                                       _x('Disable', 'fees admin', 'WPBDM'));
        } else {
            $actions['delete'] = sprintf('<a href="%s">%s</a>',
                                       esc_url(add_query_arg(array('action' => 'deletefee', 'id' => $fee->id))),
                                       _x('Delete', 'fees admin', 'WPBDM'));
        }

        $html = '';
        $html .= sprintf( '<span class="wpbdp-drag-handle" data-fee-id="%s"></span></a>',
                        $fee->id );

        $html .= sprintf('<strong><a href="%s">%s</a></strong>',
                         esc_url(add_query_arg(array('action' => 'editfee', 'id' => $fee->id))),
                         esc_attr($fee->label));
        $html .= $this->row_actions($actions);

        return $html;
    }

    public function column_amount($fee) {
        return $fee->amount;
    }

    public function column_duration($fee) {
        if ($fee->days == 0)
            return _x('Forever', 'fees admin', 'WPBDM');
        return sprintf(_nx('%d day', '%d days', $fee->days, 'fees admin', 'WPBDM'), $fee->days);
    }

    public function column_images($fee) {
        return sprintf(_nx('%d image', '%d images', $fee->images, 'fees admin', 'WPBDM'), $fee->images);
    }

    public function column_categories($fee) {
        if ($fee->categories['all'])
            return _x('All categories', 'fees admin', 'WPBDM');

        $names = array();

        foreach ($fee->categories['categories'] as $category_id) {
            if ($category = get_term($category_id, wpbdp()->get_post_type_category())) {
                $names[] = $category->name;
            }
        }

        return $names ? join($names, ', ') : '--';
    }

}


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
            case 'deletefee':
                $this->delete_fee();
                break;
            default:
                $this->feesTable();
                break;
        }
    }

    public static function admin_menu_cb() {
        $instance = new WPBDP_FeesAdmin();
        $instance->dispatch();
    }

    /* field list */
    private function feesTable() {
        $table = new WPBDP_FeesTable();
        $table->prepare_items();

        $order_options = array();
        foreach ( array( 'label' => _x( 'Label', 'fees order', 'WPBDM' ),
                         'amount' => _x( 'Amount', 'fees order', 'WPBDM' ),
                         'days' => _x( 'Duration', 'fees order', 'WPBDM' ),
                         'images' => _x( 'Images', 'fees order', 'WPBDM' ),
                         'custom' => _x( 'Custom Order', 'fees order', 'WPBDM' ) ) as $k => $l ) {
            $order_options[ $k ] = $l;
        }

        wpbdp_render_page(WPBDP_PATH . 'admin/templates/fees.tpl.php',
                          array( 'table' => $table,
                                 'order_options' => $order_options,
                                 'current_order' => wpbdp_get_option( 'fee-order' ) ),
                          true);
    }

    private function processFieldForm() {
        $fee = isset( $_GET['id'] ) ? WPBDP_Fee_Plan::find( absint( $_GET['id'] ) ) : new WPBDP_Fee_Plan();

        if ( isset( $_POST['fee'] ) ) {
            if ( ! $fee->save_or_update( $_POST['fee'] ) )
                $this->admin->messages[] = array( $fee->errors->html() , 'error' );
            else
                $this->admin->messages[] = _x('Fee updated.', 'fees admin', 'WPBDM');
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
