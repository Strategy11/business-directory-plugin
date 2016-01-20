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

    public function get_current_view() {
        return wpbdp_getv( $_GET, 'fee_status', 'active' );
    }

    public function get_views() {
        global $wpdb;

        $views = array();

        if ( wpbdp_payments_possible() )
            return $views;

        $all = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_fees" ) );
        $views['all'] = sprintf( '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
                                 esc_url( add_query_arg( 'fee_status', 'all' ) ),
                                 'all' == $this->get_current_view() ? 'current' : '',
                                 _x( 'All', 'admin fees table', 'WPBDM' ),
                                 number_format_i18n( $all ) );

        $active = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_fees WHERE amount = %s", '0.0' ) ) );
        $views['active'] = sprintf( '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
                                    esc_url( add_query_arg( 'fee_status', 'active' ) ),
                                    'active' == $this->get_current_view() ? 'current' : '',
                                    _x( 'Active', 'admin fees table', 'WPBDM' ),
                                    number_format_i18n( $active ) );

        $views['disabled'] = sprintf( '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
                                      esc_url( add_query_arg( 'fee_status', 'disabled' ) ),
                                      'disabled' == $this->get_current_view() ? 'current' : '',
                                      _x( 'Disabled', 'admin fees table', 'WPBDM' ),
                                      number_format_i18n( $all - $active ) );


        return $views;
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
            switch ( $this->get_current_view() ) {
                case 'active':
                    $this->items = WPBDP_Fee_Plan::find( array( 'amount' => 0.0 ) );
                    break;
                case 'disabled':
                    $this->items = WPBDP_Fee_Plan::find( array( '-amount' => 0.0 ) );
                    break;
                case 'all':
                default:
                    $this->items = WPBDP_Fee_Plan::find();
                    break;
            }
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
            echo '<tr class="free-fee-related-tr"></tr>';
            echo '<tr class="wpbdp-item-message-tr free-fee-related-tr">';
            echo '<td colspan="' . count( $this->get_columns() ) . '">';
            echo '<div>';
            _ex( 'This is the default free plan for your directory.  You can\'t delete it and it\'s always free, but you can edit the name and other settings. It\'s only available when the directory is in Free mode.  You can always create other fee plans, including ones for 0.00 (free) if you wish.',
                 'fees admin',
                 'WPBDM' );
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }

//        if ( $free_mode && $item->amount > 0.0 ) {
//            echo '<tr></tr>';
//            echo '<tr class="wpbdp-item-message-tr">';
//            echo '<td colspan="' . count( $this->get_columns() ) . '">';
//            echo '<div>';
//            _ex( 'Fee plan disabled because directory is in free mode.', 'fees admin', 'WPBDM' );
//            echo '</div>';
//            echo '</td>';
//            echo '</tr>';
//        }
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
//            $actions['delete'] = sprintf('<a href="%s">%s</a>',
//                                       esc_url(add_query_arg(array('action' => 'deletefee', 'id' => $fee->id))),
//                                       _x('Disable', 'fees admin', 'WPBDM'));
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
        $fee_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $fee = $fee_id ? WPBDP_Fee_Plan::find( $fee_id ) : new WPBDP_Fee_Plan();

        if ( isset( $_POST['fee'] ) ) {
            if ( ! $fee->update( $_POST['fee'] ) )
                $this->admin->messages[] = array( $fee->errors->html() , 'error' );
            else
                $this->admin->messages[] = _x('Fee updated.', 'fees admin', 'WPBDM');

            return $this->feesTable();
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
