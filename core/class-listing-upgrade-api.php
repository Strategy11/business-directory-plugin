<?php
/**
 * @since 2.1.6
 */
class WPBDP_Listing_Upgrade_API {

    private static $instance = null;

    private function __construct() {
        $this->register_default_levels();
    }

    public function register_default_levels() {
        // register default levels
        $this->register('normal', null, array(
            'name' => _x('Normal Listing', 'listings-api', 'WPBDM'),
            'is_sticky' => false
        ));
        $this->register('sticky', 'normal', array(
            'name' => _x('Featured Listing', 'listings-api', 'WPBDM'),
            'cost' => wpbdp_get_option('featured-price'),
            'description' => wpbdp_get_option('featured-description'),
            'is_sticky' => true
        ));
    }

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /*
     * General functions.
     */
    public function get_levels() {
        $res = array();

        foreach ($this->_order as $level_id) {
            $res[] = $this->get($level_id);
        }

        return $res;
    }

    public function register($upgrade_id, $after_id, $data) {
        if ( !isset($this->_levels) )
            $this->_levels = array();

        if ( !isset($this->_order) )
            $this->_order = array();

        if ( empty($upgrade_id) )
            return false;

       if ( $upgrade_id != 'normal' && (!$after_id || !in_array( $after_id, array_keys ($this->_levels) )) )
            $after_id = end ( $this->_order );

        $data = array_merge(array(
            'name' => $upgrade_id,
            'cost' => 0.0,
            'description' => '',
            'is_sticky' => false,
            'downgrade' => $after_id,
            'upgrade' => null,
        ), $data);

        if ( !isset($this->_levels[$upgrade_id]) ) {
            $obj = (object) $data;
            $obj->id = $upgrade_id;

            if ($obj->downgrade) {
                $prev_upgrade = $this->next($obj->downgrade);
                $this->_levels[$obj->downgrade]->upgrade = $obj->id;

                if ($prev_upgrade)
                    $this->_levels[$prev_upgrade]->downgrade = $obj->id;
            }

            $this->_levels[$upgrade_id] = $obj;
        } else {
            // XXX We only allow changes to name, cost and description of currently registered levels.
            foreach ( array( 'name', 'cost', 'description' ) as $k ) {
                if ( isset( $data[ $k ] ) )
                    $this->_levels[ $upgrade_id ]->{$k} = $data[ $k ];
             }

             return;
        }

        if ($obj->downgrade) {
            $down_key = array_search($obj->downgrade, $this->_order);

            array_splice($this->_order, max(0, $down_key + 1), 0, array($obj->id));
        } else {
            $this->_order[] = $upgrade_id;
        }

    }

    public function get($upgrade_id) {
        return wpbdp_getv($this->_levels, $upgrade_id, null);
    }

    public function prev($upgrade_id) {
        if ($u = $this->get($upgrade_id))
            return $u->downgrade;
        return null;
    }

    public function next($upgrade_id) {
        if ($u = $this->get($upgrade_id))
            return $u->upgrade;
        return null;
    }

    /**
     * Generates a unique level id from a given name. Useful for plugins extending functionality the
     * number of featured levels.
     * @since 2.1.7
     */
    public function unique_id($name) {
        $key = sanitize_key( $name );

        if ( !in_array( $key, $this->_order ) )
            return $key;

        $n = 0;
        while ( true ) {
            $key = $key . strval( $n );

            if ( !in_array( $key, $this->_order ) )
                return $key;

            $n += 1;
        }

    }

    /*
     * Listing-related.
     */

    public function is_sticky($listing_id) {

        //      if ($sticky_status = get_post_meta($listing_id, '_wpbdp[sticky]', true)) {
        //     return $sticky_status;
        // }

        // return 'normal';
    }

    public function get_listing_level($listing_id) {
        $listing = WPBDP_Listing::get( $listing_id );
        $plan = $listing->get_fee_plan();

        if ( ! $plan->is_sticky )
            return $this->get( 'normal' );

        if ( $plan->featured_level )
            return $this->get( $plan->featured_level );
    }

    public function get_info($listing_id) {
        global $wpdb;

        if (!$listing_id)
            return null;

        $plan = WPBDP_Listing::get( $listing_id );
        $is_pending = (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT 1 AS x_ FROM {$wpdb->prefix}wpbdp_payments_items pi JOIN {$wpdb->prefix}wpbdp_payments p ON p.id = pi.payment_id WHERE pi.item_type = %s AND p.status = %s AND p.listing_id = %d",
            'upgrade',
            'pending',
            $listing_id ) );

        $sticky_status = 'normal';
        if ( $is_pending )
            $sticky_status = 'pending';
        else
            $sticky_status = ( ( $plan->is_sticky || $plan->featured_level ) ? 'sticky' : 'normal' );

        $res = new StdClass();
        $res->level = $this->get_listing_level( $listing_id );
        $res->status = $sticky_status ? $sticky_status : 'normal';
        $res->pending = $sticky_status == 'pending' ? true : false;
        $res->sticky = $res->level->is_sticky;
        $res->upgradeable = !empty($res->level->upgrade);
        $res->upgrade = $res->upgradeable ? $this->get($res->level->upgrade) : null;
        $res->downgradeable = $res->pending ? true : !empty($res->level->downgrade);
        $res->downgrade = $res->pending ? $this->get($res->level->id) : ($res->downgradeable ? $this->get($res->level->downgrade) : null);

        return $res;
    }

    public function set_sticky($listing_id, $level_id, $only_upgrade=false) {
        global $wpdb;

        $current_info = $this->get_info( $listing_id );

        if ( $only_upgrade && (array_search($level_id, $this->_order) < array_search($current_info->level->id, $this->_order)) )
            return false;

        if ( $level_id == 'normal' ) {
            $wpdb->query(
                $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listings_plans SET is_sticky = 0, featured_price = 0.0, featured_level = NULL WHERE listing_id = %d",
                                $listing_id )
            );
        } else {
            $wpdb->query(
                $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listings_plans SET is_sticky = 1, featured_price = %s, featured_level = %s WHERE listing_id = %d",
                                $this->get( $level_id )->cost,
                                $level_id,
                                $listing_id )
            );
        }

        // TODO: approve/cancel transactions related to this operation.
    }

}

/*
 * For compat. with other APIs (< 3.5.4)
 */
class WPBDP_ListingUpgrades extends WPBDP_Listing_Upgrade_API {}

