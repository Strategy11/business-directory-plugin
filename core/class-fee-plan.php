<?php
require_once( WPBDP_PATH . 'core/class-db-entity.php' );


class WPBDP_Fee_Plan extends WPBDP_DB_Entity {

    static $_table_name = 'wpbdp_fees';
    static $_serialized = array( 'categories', 'extra_data' );


    public function __construct( $args = array() ) {
        parent::__construct( $args );
        $this->sanitize(); // XXX: Maybe this can be done for ALL entities at construct time?
    }

    protected function sanitize() {
        $this->label = trim( $this->label );
        $this->amount = floatval( trim( $this->amount ) );
        $this->images = absint( $this->images );
        $this->days = absint( $this->days );
        $this->sticky = (bool) $this->sticky;

        if ( 'free' == $this->tag ) {
            $this->amount = 0.0;
            $this->sticky = false;
            $this->categories = array( 'all' => true, 'categories' => array() );
            $this->enabled = true;
        }

        $this->categories = wp_parse_args( $this->categories,
                                           array( 'all' => false, 'categories' => array() ) );
        $this->categories['categories'] = array_map( 'absint', $this->categories['categories'] );

        // Adding 0 as a supported category is a shortcut to allowing all categories.
        if ( in_array( 0, $this->categories['categories'], true ) ) {
            $this->categories['all'] = true;
            $this->categories['categories'] = array();
        }

        if ( ! is_array( $this->extra_data ) )
            $this->extra_data = array();
    }

    protected function validate() {
        if ( ! $this->label )
            $this->errors->add( 'label', _x('Fee label is required.', 'fees-api', 'WPBDM') );

        if ( $this->amount < 0.0 )
            $this->errors->add( 'amount', _x('Fee amount must be a non-negative decimal number.', 'fees-api', 'WPBDM') );

        if ( ! $this->categories || ( empty( $this->categories['all'] ) && empty( $this->categories['categories'] ) ) )
            $this->errors->add( 'categories', _x('Fee must apply to at least one category.', 'fees-api', 'WPBDM') );

        // limit 'duration' because of TIMESTAMP limited range (issue #157).
        // FIXME: this is not a long-term fix. we should move to DATETIME to avoid this entirely.
        if ( $this->days > 3650 )
            $this->errors->add( 'days', _x('Fee listing duration must be a number less than 10 years (3650 days).', 'fees-api', 'WPBDM') );
    }

    public function save( $validate = true ) {
        // For backwards compat.
        $fee = (array) $this;
        do_action_ref_array( 'wpbdp_fee_before_save', array( &$fee ) );

        if ( 'free' == $this->tag ) {
            global $_wpbdp_fee_plan_recursion_guard;
            $_wpbdp_fee_plan_recursion_guard = true;

            // Update associated settings.
            wpbdp_set_option( 'free-images', $this->images );
            wpbdp_set_option( 'listing-duration', $this->days );

            $_wpbdp_fee_plan_recursion_guard = false;
        }

        return parent::save( $validate );
    }

    public function supports_category( $category_id ) {
        if ( $this->categories['all'] )
            return true;

        $requested_cats = wpbdp_get_parent_catids( $category_id );

        foreach ( $this->categories['categories'] as $s_cat_id ) {
            if ( in_array( $s_cat_id, $requested_cats ) )
                return true;
        }

        return false;
    }

    public static function for_category( $category_id ) {
        $res = array();

        foreach ( self::find() as $f ) {
            if ( $f->supports_category( $category_id ) )
                $res[] = $f;
        }

        return $res;
    }

    public static function get_free_plan() {
        return self::find( array( 'tag' => 'free', '_limit' => 1 ) );
    }

    static function before_find( $args ) {
        if ( $args['limit'] > 0 || $args['order'] )
            return $args;

        $order = wpbdp_get_option( 'fee-order' );

        if ( ! $order )
            return $args;

        $args['orderby'] = ( 'custom' == $order['method'] ) ? 'weight' : $order['method'];
        $args['order'] = ( 'custom' == $order['method'] ) ? 'DESC' : $order['order'];

        return $args;
    }

    public static function create( $args ) { return parent::_create( $args, __class__ ); }
    public static function find( $args = '' ) { return parent::_find( $args, __class__ ); }

}

