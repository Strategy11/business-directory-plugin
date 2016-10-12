<?php
if ( ! class_exists( 'WP_List_Table' ) )
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * @since next-release
 */
class WPBDP__Admin__Payments_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => _x( 'payment', 'payments admin', 'WPBDM' ),
            'plural' => _x( 'payments', 'payments admin', 'WPBDM' ),
            'ajax' => false
        ) );
    }

    public function no_items() {
        echo _x( 'No payments found.', 'payments admin', 'WPBDM' );
    }

    public function get_current_view() {
        return wpbdp_getv( $_GET, 'status', 'all' );
    }

    public function get_views() {
        global $wpdb;

        $views_ = array();

        $count = 0;
        $views_['all'] = array( _x( 'All', 'payments admin', 'WPBDM' ), $count );

        foreach ( WPBDP_Payment::get_statuses() as $status => $status_label ) {
            $count = 0;
            $views_[ $status ] = array( $status_label, $count );
        }

        $views = array();
        foreach ( $views_ as $view_id => $view_data ) {
            $views[ $view_id ] = sprintf( '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
                                          esc_url( add_query_arg( 'status', $view_id ) ),
                                          $view_id == $this->get_current_view() ? 'current': '',
                                          $view_data[0],
                                          number_format_i18n( $view_data[1] ) );
        }

        return $views;
    }

    public function get_columns() {
        $cols = array(
            'id' => _x( 'ID', 'fees admin', 'WPBDM' ),
            'date' => _x( 'Date', 'fees admin', 'WPBDM' ),
            'details' => _x( 'Details', 'fees admin', 'WPBDM' ),
            'amount' => _x( 'Amount', 'fees admin', 'WPBDM' ),
            'status' => _x( 'Status', 'fees admin', 'WPBDM' ),
            'listing' => _x( 'Listing', 'fees admin', 'WPBDM' )
        );

        return $cols;
    }

    public function prepare_items() {
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
        $this->items = array();

        $args = array();

        if ( 'all' != $this->get_current_view() )
            $args['status'] = $this->get_current_view();

        if ( ! empty( $_GET['listing'] ) )
            $args['listing_id'] = absint( $_GET['listing'] );

        $this->items = WPBDP_Payment::find( $args );
    }

    public function column_id( $payment ) {
        return sprintf( '<a href="%s">%d</a>', add_query_arg( array( 'wpbdp-view' => 'details', 'payment-id' => $payment->get_id() ) ), $payment->get_id() );
    }

    public function column_date( $payment ) {
        return date_i18n( get_option( 'date_format' ), strtotime( $payment->get_created_on() ));
    }

    public function column_amount( $payment ) {
        return wpbdp_currency_format( $payment->get_total() );
    }

    public function column_status( $payment ) {
        return $payment->get_status_string();
    }

    public function column_details( $payment ) {
        return $payment->get_short_description();
    }

    public function column_listing( $payment ) {
        return $payment->get_listing_id();
    }

//     public function column_label($fee) {
//         $actions = array();
//         $actions['edit'] = sprintf('<a href="%s">%s</a>',
//                                    esc_url(add_query_arg(array('wpbdp-view' => 'edit-fee', 'id' => $fee->id))),
//                                    _x('Edit', 'fees admin', 'WPBDM'));
//
//         if ( 'free' == $fee->tag ) {
// //            $actions['delete'] = sprintf('<a href="%s">%s</a>',
// //                                       esc_url(add_query_arg(array('action' => 'deletefee', 'id' => $fee->id))),
// //                                       _x('Disable', 'fees admin', 'WPBDM'));
//         } else {
//             if ( $fee->enabled )
//                 $actions['disable'] = sprintf('<a href="%s">%s</a>',
//                                            esc_url(add_query_arg(array('wpbdp-view' => 'toggle-fee', 'id' => $fee->id))),
//                                            _x('Disable', 'fees admin', 'WPBDM'));
//             else
//                 $actions['enable'] = sprintf('<a href="%s">%s</a>',
//                                            esc_url(add_query_arg(array('wpbdp-view' => 'toggle-fee', 'id' => $fee->id))),
//                                            _x('Enable', 'fees admin', 'WPBDM'));
//
//             $actions['delete'] = sprintf('<a href="%s">%s</a>',
//                                        esc_url(add_query_arg(array('wpbdp-view' => 'delete-fee', 'id' => $fee->id))),
//                                        _x('Delete', 'fees admin', 'WPBDM'));
//         }
//
//         $html = '';
//         $html .= sprintf( '<span class="wpbdp-drag-handle" data-fee-id="%s"></span></a>',
//                         $fee->id );
//
//         $html .= sprintf('<strong><a href="%s">%s</a></strong>',
//                          esc_url(add_query_arg(array('wpbdp-view' => 'edit-fee', 'id' => $fee->id))),
//                          esc_attr($fee->label));
//         $html .= $this->row_actions($actions);
//
//         return $html;
//     }

}
