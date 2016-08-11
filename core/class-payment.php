<?php
require_once( WPBDP_PATH . 'core/class-db-model.php' );

/**
 * This class represents a listing payment.
 *
 * @since 3.3
 */
class WPBDP_Payment extends WPBDP_DB_Model {

    const STATUS_UNKNOWN = 'unknown';
    const STATUS_NEW = 'new';
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELED = 'canceled';
    const STATUS_REJECTED = 'rejected';

    const HANDLER_GATEWAY = 'gateway';
    const HANDLER_ADMIN = 'admin';
    const HANDLER_SYSTEM = 'system';

    private $items = array();

    public function __construct( $data = array() ) {
        $this->fill_from_data( $data, array(
            'id' => 0,
            'listing_id' => 0,
            'gateway' => '',
            'currency_code' => wpbdp_get_option( 'currency' ),
            'amount' => 0.0,
            'status' => self::STATUS_PENDING,
            'created_on' => current_time( 'mysql' ),
            'processed_on' => '',
            'processed_by' => '',
            'payerinfo' => array(),
            'extra_data' => array(),
            'notes' => array(),
            'tag' => ''
        ) );

        $this->amount = floatval( $this->amount );

        global $wpdb;

        if ( $this->id > 0 ) {
            foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_payments_items WHERE payment_id = %d", $this->id ), ARRAY_A ) as $item ) {
                $item['data'] = maybe_unserialize( $item['data'] );
                $this->items[] = $item;
            }
        }
    }

    public function reset() {
        $this->gateway = '';
        $this->processed_by = '';
        $this->processed_on = '';
        $this->payerinfo = array();
        $this->status = self::STATUS_PENDING;
    }


    // TODO: when a payment is saved (and it's completed) all payments it superseeds should be removed/rejected (i.e. a payment for the same category or an already pending upgrade)
    public function save() {
        global $wpdb;

//        do_action_ref_array( 'WPBDP_Payment::before_save', array( &$this ) );

        $row = array(
            'listing_id' => $this->listing_id,
            'gateway' => $this->gateway,
            'amount' => $this->amount,
            'status' => $this->status,
            'created_on' => $this->created_on,
            'processed_on' => $this->processed_on,
            'processed_by' => $this->processed_by,
            'currency_code' => $this->currency_code,
            'payerinfo' => serialize( is_array( $this->payerinfo ) ? $this->payerinfo : array() ),
            'extra_data' => serialize( is_array( $this->extra_data ) ? $this->extra_data : array() ),
            'notes' => serialize( is_array( $this->notes ) ? $this->notes : array() ),
            'tag' => $this->tag
        );

        if ( ! $this->processed_on )
            unset( $row['processed_on'] );

        if ( $this->id )
            $row['id'] = $this->id;

        if ( false === $wpdb->replace( $wpdb->prefix . 'wpbdp_payments', $row ) )
            return false;

        $this->id = $this->id ? $this->id : $wpdb->insert_id;

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_payments_items WHERE payment_id = %d", $this->id ) );

        foreach ( $this->items as &$item ) {
            $wpdb->insert( $wpdb->prefix . 'wpbdp_payments_items',
                           array( 'item_type' => $item['item_type'],
                                  'amount' => $item['amount'],
                                  'description' => $item['description'],
                                  'payment_id' => $this->id,
                                  'rel_id_1' => $item['rel_id_1'],
                                  'rel_id_2' => $item['rel_id_2'],
                                  'data' => serialize( $item['data'] ) )
                         );
        }

        do_action_ref_array( 'WPBDP_Payment::save', array( &$this ) );

        if ( $this->status != self::STATUS_COMPLETED && $this->amount == 0.0 ) {
            $this->set_status( self::STATUS_COMPLETED );
            $this->save();
        }

        return true;
    }

    public function delete() {
    }

    public function is_payment_due() {
        return $this->amount > 0.0 && $this->status != self::STATUS_COMPLETED;
    }

    public function is_pending() {
        return $this->status == self::STATUS_PENDING;
    }

    public function is_completed() {
        return $this->status == self::STATUS_COMPLETED;
    }

    public function is_canceled() {
        return $this->status == self::STATUS_CANCELED;
    }

    public function is_rejected() {
        return $this->status == self::STATUS_REJECTED;
    }

    public function has_been_processed() {
        return ! empty( $this->processed_by );
    }

    public function is_first_recurring_payment() {
        return $this->has_item_type( 'recurring_fee' ) && ( ! $this->get_data( 'recurring_id' ) );
    }

    public function generate_recurring_payment() {
        $recurring_item = $this->get_recurring_item();

        if ( ! $recurring_item )
            return null;

        $rp = new WPBDP_Payment( array( 'listing_id' => $this->get_listing_id(),
                                        'gateway' => $this->get_gateway(),
                                        'currency_code' => $this->get_currency_code(),
                                        'amount' => 0.0,
                                        'payerinfo' => $this->payerinfo,
                                        'extra_data' => array( 'recurring_id' => $this->get_data( 'recurring_id' ),
                                                               'parent_payment_id' => $this->id )
                                 ) );
        $rp->add_item( 'recurring_fee',
                       $recurring_item->amount,
                       $recurring_item->description,
                       $recurring_item->data,
                       $recurring_item->rel_id_1,
                       $recurring_item->rel_id_2 );
        $rp->save();
        return $rp;
    }

    public function get_handler() {
        return $this->processed_by;
    }

    public function get_processed_on() {
        return $this->processed_on;
    }

    public function cancel_recurring() {
        if ( ! $this->id )
            return;

        $listing = WPBDP_Listing::get( $this->get_listing_id() );
        $recurring_item = $this->get_recurring_item();

        if ( $recurring_item ) {
            $listing->make_category_non_recurring( $recurring_item->rel_id_1 );
            // $listing->remove_category( $recurring_item->rel_id_1 );
        }
    }

    public function get_recurring_item() {
        $items = $this->get_items( array( 'item_type' => 'recurring_fee' ) );
        return $items ? $items[0] : null;
    }

    public function add_item( $item_type = 'charge', $amount = 0.0, $description = '', $data = array(), $rel_id_1 = 0, $rel_id_2 = 0 ) {
        $item = array();
        $item['item_type'] = $item_type;
        $item['amount'] = floatval( $amount );
        $item['description'] = $description;
        $item['data'] = $data;

        $item['rel_id_1'] = $rel_id_1;
        $item['rel_id_2'] = $rel_id_2;

        $this->items[] = $item;
        $this->amount += $amount;
    }

    public function add_category_fee_item( $category_id, $fee, $recurring = false ) {
        if ( is_int( $fee ) ) {
            $fee = wpbdp_get_fee( $fee );

            if ( ! $fee )
                return false;
        }

        $this->add_item( $recurring ? 'recurring_fee' : 'fee',
                         $fee->amount,
                         sprintf( _x( 'Fee "%s" for category "%s"%s', 'listings', 'WPBDM' ),
                                  $fee->label,
                                  wpbdp_get_term_name( $category_id ),
                                  $recurring ? ( ' ' . _x( '(recurring)', 'listings', 'WPBDM' ) ) : '' ),
                         array( 'fee_id' => $fee->id, 'fee_days' => $fee->days, 'fee_images' => $fee->images ),
                         $category_id,
                         $fee->id );
        return true;
    }

    public function update_items( $items = array() ) {
        $this->amount = 0.0;
        $this->items = array();

        foreach ( $items as $item ) {
            $item = (array) $item;
            $this->items[] = $item;
            $this->amount += $item['amount'];
        }
    }

    public function delete_item( &$item ) {
        $index = array_search( (array) $item, $this->items, true );

        if ( false === $index )
            return;

        unset( $this->items[ $index ] );
        $this->amount -= $item->amount;
    }

    public function has_item_type( $item_type ) {
        foreach ( $this->items as &$item ) {
            if ( $item['item_type'] == $item_type )
                return true;
        }

        return false;
    }

    public function get_items( $args = array() ) {
        $items = array();

        if ( isset( $args['item_type'] ) )
            $args['item_type'] = is_array( $args['item_type'] ) ? $args['item_type'] : array( $args['item_type'] );
        else
            $args['item_type'] = null;

        foreach ( $this->items as &$item ) {
            if ( isset( $args['item_type'] ) && ! in_array( $item['item_type'], $args['item_type'], true ) )
                continue;

            if ( isset( $args['rel_id_1'] ) && $args['rel_id_1'] != $item['rel_id_1'] )
                continue;

            $items[] = $item;
        }

        return array_map( create_function( '$x', 'return (object) $x;' ), $items );
    }

    public function get_item( $args = array() ) {
        $items = $this->get_items( $args );

        if ( $items )
            return array_pop( $items );

        return null;
    }

    public function summarize() {
        $regular = 0.0;
        $discounts = 0.0;
        $recurring = 0.0;
        $recurring_days = 0;

        $res = array( 'trial' => false,
                      'trial_amount' => 0.0,
                      'recurring' => false,
                      'recurring_amount' => 0.0,
                      'recurring_days' => 0,
                      'recurring_description' => '',
                      'recurring_obj' => null,
                      'balance' => 0.0,
                      'description' => $this->get_short_description() );

        $recurring = $this->get_item( array( 'item_type' => 'recurring_fee' ) );
        $res['recurring_obj'] = $recurring;

        if ( ! $recurring ) {
            $res['balance'] = floatval( $this->get_total() );
        } else {
            $recurring_amt = floatval( $recurring->amount );
            $discounts_amt = abs( floatval( $this->get_total( 'coupon' ) ) );
            $others_amt = ( floatval( $this->get_total() ) + $discounts_amt ) - $recurring_amt;

            $res['recurring'] = true;
            $res['recurring_amount'] = $recurring_amt;
            $res['recurring_days'] = $recurring->data['fee_days'];
            $res['recurring_description'] = $recurring->description;

            if ( $discounts_amt > 0.0 ) {
                if ( $others_amt > 0.0 ) {
                    $others_rem = $others_amt - $discounts_amt;

                    if ( $others_rem > 0.0 ) {
                        $res['balance'] = $others_rem;
                    } else {
                        $res['trial_amount'] = max( 0.0, $recurring_amt + $others_rem );
                        $res['trial'] = true;
                    }
                } else {
                    $res['trial'] = true;
                    $res['trial_amount'] = max( 0.0, $recurring_amt - $discounts_amt );
                }
            } else {
                $res['balance'] = $others_amt;
            }
        }

        return $res;
    }

    public function get_listing_id() {
        return $this->listing_id;
    }

    public function get_total( $item_type = 'all' ) {
        if ( $item_type && 'all' != $item_type ) {
            $res = 0.0;
            $items = $this->get_items( array( 'item_type' =>  $item_type ) );

            foreach ( $items as $i )
                $res += $i->amount;

            return $res;
        }

        return $this->amount;
    }

    public function get_status() {
        return $this->status;
    }

    public function set_status( $newstatus, $processed_by = 'system', $processed_on = null ) {
        $prev_status = $this->status;
        $this->status = $newstatus;
        $this->processed_by = $processed_by;
        $this->processed_on = ! $processed_on ? current_time( 'mysql' ) : $processed_on;

        if ( $prev_status != $newstatus )
            do_action_ref_array( 'WPBDP_Payment::status_change', array( &$this, $prev_status, $newstatus ) );
    }

    public function get_gateway() {
        return $this->gateway;
    }

    public function set_listing( $listing ) {
        if ( is_object( $listing ) )
            $this->listing_id = $listing->ID;
        else
            $this->listing_id = $listing;
    }

    public function set_payment_method( $method_id ) {
        $this->gateway = $method_id;

        do_action_ref_array( 'WPBDP_Payment::set_payment_method', array( &$this, $method_id ) );
    }

    public function get_currency_code() {
        return $this->currency_code;
    }

    public function get_short_description() {
        return $this->get_description();
    }

    // TODO
    public function get_description() {
        if ( count( $this->items ) == 1 )
            return $this->items[0]['description'];

        return sprintf( 'Listing Payment (ID: %s)', $this->id );
    }

    public function set_data( $key, $value ) {
        if ( !is_array( $this->extra_data ) )
            $this->extra_data = array();

        $this->extra_data[ $key ] = $value;
    }

    public function get_data( $key ) {
        if ( ! is_array( $this->extra_data ) || ! isset( $this->extra_data[ $key ] ) )
            return null;

        return $this->extra_data[ $key ];
    }

    public function set_submit_state_id( $id ) {
        $this->set_data( 'submit_state_id', $id );
    }

    public function get_submit_state_id() {
        return $this->get_data( 'submit_state_id' );
    }

    public function set_payer_info( $key, $value ) {
        $this->payerinfo[ $key ] = $value;
    }

    public function get_payer_info( $key ) {
        if ( isset( $this->payerinfo[ $key ] ) )
            return $this->payerinfo[ $key ];

        return '';
    }

    public function get_checkout_url( $force_http = false ) {
        $payment_id = $this->id;
        $payment_q = base64_encode('payment_id=' . $payment_id . '&verify=0' ); // TODO: add a 'verify' parameter to avoid false links being generated.

        $base_url = wpbdp_get_page_link( 'main' );

        if ( ! $force_http && ! is_ssl() && wpbdp_get_option( 'payments-use-https' ) ) {
            $base_url = set_url_scheme( $base_url, 'https' );
        }

        return add_query_arg( array( 'wpbdp_view' => 'checkout', 'payment' => urlencode( $payment_q ) ), $base_url );
    }

    public function get_redirect_url() {
        if ( $this->get_submit_state_id() ) {
            if ( $this->is_completed() )
                return add_query_arg( array( '_state' => $this->get_submit_state_id() ), wpbdp_get_page_link( 'submit' ) );
        }

        return $this->get_checkout_url( true );
    }

    public function get_created_on() {
        return $this->created_on;
    }

    public function tag( $tag ) {
        $this->tag = strtolower( trim( $tag ) );
    }

    public function add_error( $error_msg ) {
        // TODO: add datetime support.
        $errors = $this->get_data( 'errors' );
        $errors = ! $errors ? array() : $errors;

        $errors[] = $error_msg;

        $this->set_data( 'errors', $errors );
    }

    public function clear_errors() {
        $this->set_data( 'errors', array() );
    }

    public function get_notes() {
        return $this->notes;
    }

    /* @override */
    public static function find( $args = array(), $lightweight = false ) {
        global $wpdb;
        return parent::_find( $args, $lightweight, $wpdb->prefix . 'wpbdp_payments', __CLASS__ );
    }

    /* @override */
    public static function get( $id ) {
        global $wpdb;
        return parent::_get( $id, $wpdb->prefix . 'wpbdp_payments', __CLASS__ );
    }

}
?>
