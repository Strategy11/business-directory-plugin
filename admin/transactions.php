<?php
if ( !class_exists( 'WP_List_Table' ) )
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class WPBDP_TransactionsTable extends WP_List_Table {
    
    public function __construct() {
        parent::__construct(array(
            'singular' => _x( 'transaction', 'admin transactions', 'WPBDM' ),
            'plural' => _x( 'transactions', 'admin transactions', 'WPBDM' ),
            'ajax' => false
        ));
    }

    public function get_columns() {
        return array(
            'id' => _x( 'ID', 'admin transactions', 'WPBDM' ),
            'payment_type' => _x( 'Type', 'admin transactions', 'WPBDM' ),
            'listing' => _x( 'Listing', 'admin transactions', 'WPBDM' ),
            'status' => _x( 'Status', 'admin transactions', 'WPBDM' ),
            'amount' => _x( 'Amount', 'admin transactions', 'WPBDM' ),
            'created_on' => _x( 'Date', 'admin transactions', 'WPBDM' ),
            'actions' => ''
        );
    }

    protected function column_id( $item ) {
        return $item->id;
    }

    protected function column_payment_type( $item ) {
        $payment_trans = array(
            'initial' => _x( 'Listing Submit (Initial Payment)', 'admin transactions', 'WPBDM' ),
            'edit' => _x( 'Listing Edit (Category Fee)', 'admin transactions', 'WPBDM' ),
            'renewal' => _x( 'Renewal', 'admin transactions', 'WPBDM' ),
            'upgrade-to-sticky' => _x( 'Upgrade to Featured', 'admin transactions', 'WPBDM' )  
        );

        $html = '';
        $html .= $payment_trans[ $item->payment_type ];

        $html .= '<div class="more-details" style="display: none;">' . $this->more_details( $item ) . '</div>';

        return $html;
    }

    private function more_details( $item ) {
        $item->payerinfo = unserialize( $item->payerinfo );

        $html  = '';
        $html .= '<dl>';

        $html .= '<dt>' . _x( 'Gateway', 'admin transactions', 'WPBDM' ) . '</dt>';
        $html .= '<dd>'. ( $item->gateway ? $item->gateway : '--' ) . '</dd>';
        $html .= '<dt>' . _x( 'Payer Info', 'admin transactions', 'WPBDM' ) . '</dt>';
        $html .= '<dd>';
        $html .= sprintf( '%s: %s', _x( 'Name', 'admin transactions', 'WPBDM' ), wpbdp_getv( $item->payerinfo, 'name', '--' ) );
        $html .= '<br />';
        $html .= sprintf( '%s: %s', _x( 'E-Mail', 'admin transactions', 'WPBDM' ), wpbdp_getv( $item->payerinfo, 'email', '--' ) );
        $html .= '</dd>';

        if ( $item->processed_on ) {
            $html .= '<dt>' . _x( 'Processed On', 'admin transactions', 'WPBDM' ) . '</dt>';
            $html .= '<dd>' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format ' ), strtotime( $item->processed_on ) ) . '</dd>';
            $html .= '<dt>' . _x( 'Processed By', 'admin transactions', 'WPBDM' ) . '</dt>';
            $html .= '<dd>' . $item->processed_by . '</dd>';
        }

        $html .= '</dl>';

        return $html;
    }

    protected function column_listing( $item ) {
        return sprintf( '<a href="%s">%s</a>',
                        get_permalink( $item->listing_id ),
                        get_the_title( $item->listing_id ) );
    }

    protected function column_status( $item ) {
        return sprintf( '<span class="tag %s">%s</span>',
                        $item->status,
                        strtoupper( $item->status ) );
    }

    protected function column_amount( $item ) {
        return wpbdp_format_currency( $item->amount );
    }

    protected function column_created_on( $item ) {
        return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                          strtotime( $item->created_on ) );
    }

    protected function column_actions( $item ) {
        $actions = array();

        if ( $item->status == 'pending' ) {
            $actions['approve_'] = sprintf( '<a href="%s">%s</a>',
                                            add_query_arg( array( 'action' => 'approve', 'id' => $item->id ) ),
                                            _x( 'Approve', 'admin transactions', 'WPBDM' )
                                          );
            $actions['reject_'] = sprintf( '<a href="%s">%s</a>',
                                            add_query_arg( array( 'action' => 'reject', 'id' => $item->id ) ),
                                            _x( 'Reject', 'admin transactions', 'WPBDM' )
                                          );
        }

        if ( $item->status != 'pending' )
            $actions['details'] = sprintf( '<a href="#" class="details-link">%s</a>',
                                            _x( '+ Details', 'admin transactions', 'WPBDM' )
                                          );

        $actions['delete'] = sprintf( '<a href="%s" class="delete">%s</a>',
                                        add_query_arg( array( 'action' => 'delete', 'id' => $item->id ) ),
                                        _x( 'Delete', 'admin transactions', 'WPBDM' )
                                      );        

        return implode( ' | ', $actions );
/*        if ( $item->processed_on ) {
            return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                              strtotime( $item->processed_on ) );
        }

        return '—';*/
    }



    public function get_views() {
        global $wpdb;

        $views = array();

        // filter by status
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_payments" );
        $views['all'] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                 add_query_arg( 'status', 'all' ),
                                 wpbdp_getv( $_REQUEST, 'status' ) == 'all' ? 'current' : '',
                                 _x( 'All', 'admin transactions', 'WPBDM' ) ,
                                 number_format_i18n( $count )
                               );

        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_payments WHERE status = %s", 'approved' ) );
        $views['approved'] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                      add_query_arg( 'status', 'approved' ),
                                      wpbdp_getv( $_REQUEST, 'status' ) == 'approved' ? 'current' : '',
                                      _x( 'Approved', 'admin transactions', 'WPBDM' ) ,
                                      number_format_i18n( $count )
                                    );

        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_payments WHERE status = %s", 'pending' ) );
        $views['pending'] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                      add_query_arg( 'status', 'pending' ),
                                      wpbdp_getv( $_REQUEST, 'status', 'pending' ) == 'pending' ? 'current' : '',
                                      _x( 'Pending', 'admin transactions', 'WPBDM' ) ,
                                      number_format_i18n( $count )
                                    );
        
        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_payments WHERE status = %s", 'rejected' ) );        
        $views['rejected'] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                      add_query_arg( 'status', 'rejected' ),
                                      wpbdp_getv( $_REQUEST, 'status' ) == 'rejected' ? 'current' : '',
                                      _x( 'Rejected', 'admin transactions', 'WPBDM' ) ,
                                      number_format_i18n( $count )
                                    );

        // by type
        /*$views['initial'] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                      add_query_arg( 'filter', 'rejected' ),
                                      wpbdp_getv( $_REQUEST, 'filter' ) == 'rejected' ? 'current' : '',
                                      _x( 'Rejected', 'admin transactions', 'WPBDM' ) ,
                                      number_format_i18n( $count )
                                    );        */

        return $views;
    }

    public function prepare_items() {
        global $wpdb;

        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

        $where = '';
        $current_filter = wpbdp_getv( $_GET, 'status', 'pending' );
        if ( $current_filter != 'all' )
            $where .= $wpdb->prepare( "AND status = %s", $current_filter );

        $query = "SELECT * FROM {$wpdb->prefix}wpbdp_payments WHERE 1=1 {$where} ORDER BY created_on DESC";
        $this->items = $wpdb->get_results( $query );
    }

}

class WPBDP_TransactionsAdmin {

    private function transactions_table() {
        $table = new WPBDP_TransactionsTable();
        $table->prepare_items();

        wpbdp_render_page( WPBDP_PATH . 'admin/templates/transactions.tpl.php',
                           array( 'table' => $table ),
                           true );
    }

    private function clear_transactions() {
        global $wpdb;

        // TODO: delete transactions for posts that do not exist


        // delete unnecessary renewals
        $renewals = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}wpbdp_payments WHERE payment_type = %s AND status = %s",
                                                        'renewal',
                                                        'pending' ) );
        foreach ( $renewals as $tid ) {
            $trans = wpbdp_payments_api()->get_transaction( $tid );
            if ( !is_array( $trans->extra_data ) ) {
                $trans->extra_data = unserialize( $trans->extra_data );
            }

            if ( intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d", $trans->extra_data['renewal_id'] ) ) ) == 0 ) {
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE id = %d", $tid ) );
            }
        }

    }

    public function dispatch() {
        global $wpdb;

        $api = wpbdp_payments_api();

        switch ( wpbdp_getv( $_REQUEST, 'action' ) ) {
            case 'approve':
                if ( $trans = $api->get_transaction( $_GET['id'] ) ) {
                    $trans->processed_on = current_time( 'mysql' );
                    $trans->processed_by = 'admin';
                    $trans->status = 'approved';
                    $api->save_transaction( $trans );
                }

                wpbdp_admin()->messages[] = _x( 'The transaction has been approved.', 'admin', 'WPBDM' );
                break;

            case 'reject':
                if ( $trans = $api->get_transaction( $_GET['id'] ) ) {
                    $trans->processed_on = current_time( 'mysql' );
                    $trans->processed_by = 'admin';
                    $trans->status = 'rejected';
                    $api->save_transaction( $trans );
                }
                
                wpbdp_admin()->messages[] = _x( 'The transaction has been rejected.', 'admin', 'WPBDM' );                  
                break;

            case 'delete':
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE id = %d", $_GET['id'] ) );
                wpbdp_admin()->messages[] = _x( 'The transaction has been deleted.', 'admin', 'WPBDM' );  

                break;

            default:
                break;
        }

        $_SERVER['REQUEST_URI'] = remove_query_arg( array( 'action', 'id' ), $_SERVER['REQUEST_URI'] );

        $this->clear_transactions();
        $this->transactions_table();
    }

    public static function admin_menu_cb() {
        $instance = new WPBDP_TransactionsAdmin();
        $instance->dispatch();      
    }

}