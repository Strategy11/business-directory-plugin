<?php
require_once( WPBDP_PATH . 'core/class-db-entity.php' );


class WPBDP_Fee_Plan extends WPBDP_DB_Entity {

    static $_table_name = 'wpbdp_plans';
    static $_serialized = array( 'pricing_details', 'extra_data' );


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
        $this->pricing_model = empty( $this->pricing_model  ) ? 'flat' : $this->pricing_model;
        $this->category_limit = 0;

        if ( 'free' == $this->tag ) {
            $this->amount = 0.0;
            $this->sticky = false;
            $this->supported_categories = 'all';
            $this->enabled = true;
        }

        if ( null === $this->supported_categories ) {
            $this->supported_categories = 'all';
        }

        if ( 'all' !== $this->supported_categories ) {
            if ( is_string( $this->supported_categories ) ) {
                $this->supported_categories = explode( ',', $this->supported_categories );
            }

            $this->supported_categories = array_map( 'absint', (array) $this->supported_categories  );
        }

        // Remove unnecessary pricing details.
        if ( 'extra' != $this->pricing_model )
            unset( $this->pricing_details['extra'] );

        if ( 'extra' == $this->pricing_model ) {
            $this->pricing_details = array( 'extra' => $this->pricing_details['extra'] );
        }

        if ( 'variable' == $this->pricing_model && 'all' != $this->supported_categories ) {
            // Unset details for categories that are not supported.
            $this->pricing_details = wp_array_slice_assoc( $this->pricing_details, $this->supported_categories );
        }

        if ( 'variable' == $this->pricing_model ) {
            $this->amount = '0.0';
        }

        if ( 'flat' == $this->pricing_model ) {
            $this->pricing_details = array();
        }

        if ( ! is_array( $this->extra_data ) )
            $this->extra_data = array();
    }

    protected function validate() {
        if ( ! $this->label )
            $this->errors->add( 'label', _x('Fee label is required.', 'fees-api', 'WPBDM') );

        if ( $this->amount < 0.0 )
            $this->errors->add( 'amount', _x('Fee amount must be a non-negative decimal number.', 'fees-api', 'WPBDM') );

        if ( ! $this->supported_categories )
            $this->errors->add( 'supported_categories', _x('Fee must apply to at least one category.', 'fees-api', 'WPBDM') );

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

    protected function prepare_row() {
        $row = parent::prepare_row();

        if ( 'all' != $row['supported_categories'] )
            $row['supported_categories'] = implode( ',', $row['supported_categories'] );

        return $row;
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

    /**
     * @since next-release
     */
    public function get_feature_list() {
        $items = array();

        if ( wpbdp_get_option( 'allow-images' ) ) {
            if ( ! $this->images )
                $items['images'] = _x( 'No images allowed.', 'fee plan', 'WPBDM' );
            else
                $items['images'] = sprintf( _nx( '%d image allowed.', '%d images allowed.', $this->images, 'fee plan', 'WPBDM' ), $this->images );
        }

        return $items;
    }

    /**
     * @since next-release
     */
    public function calculate_amount( $categories = array() ) {
        $amount = 0.0;
        $pricing_info = $this->pricing_details;

        switch ( $this->pricing_model ) {
        case 'variable':
            $amount = array_sum( wp_array_slice_assoc( $pricing_info, $categories ) );
            break;
        case 'extra':
            $amount = $this->amount + ( $pricing_info['extra'] * count( $categories ) );
            break;
        case 'flat':
        default:
            $amount = $this->amount;
            break;
        }

        return $amount;
    }

    /**
     * @since next-release
     */
    public function supports_category_selection( $categories = array() ) {
        if ( ! $categories )
            return true;

        if ( is_string( $this->supported_categories ) && 'all' == $this->supported_categories )
            return true;

        if ( array_diff( $categories, $this->supported_categories ) )
            return false;

        return true;
    }

    /**
     * @since next-release
     */
    public function calculate_expiration_time( $base_time ) {
        if ( ! $base_time )
            $base_time = current_time( 'timestamp' );

        if ( $this->days == 0 )
            return null;

        $expire_time = strtotime( sprintf( '+%d days', $this->days ), $base_time );
        return date( 'Y-m-d H:i:s', $expire_time );
    }

    public static function for_category( $category_id ) {
        return self::filter_for_category( self::find(), $category_id );
    }

    private static function filter_for_category( $fees, $category_id ) {
        $res = array();

        foreach ( $fees as $f ) {
            if ( $f->supports_category( $category_id ) )
                $res[] = $f;
        }

        return $res;
    }

    public static function active_fees_for_category( $category_id ) {
        return self::filter_for_category( self::active_fees(), $category_id );
    }

    public static function active_fees() {
        if ( wpbdp_payments_possible() ) {
            $fees = self::find( array( 'enabled' => 1, '-tag' => 'free' ) );
        } else {
            $fees = self::find( array( 'enabled' => 1, 'tag' => 'free' ) );
        }

        return $fees;
    }

    public static function get_free_plan() {
        return self::find( array( 'tag' => 'free', '_limit' => 1 ) );
    }

    public static function create( $args ) { return parent::_create( $args, __class__ ); }
    public static function find( $args = '' ) { return parent::_find( $args, __class__ ); }

}

