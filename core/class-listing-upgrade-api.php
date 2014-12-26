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
        $sticky_status = get_post_meta( $listing_id, '_wpbdp[sticky]', true );
        $level = get_post_meta( $listing_id, '_wpbdp[sticky_level]', true );

        switch ($sticky_status) {
            case 'sticky':
                if (!$level)
                    return $this->get('sticky');
                else
                    return $this->get($level) ? $this->get($level) : $this->get('sticky');

                break;
            case 'pending':
                if (!$level)
                    return $this->get('normal');
                else
                    return $this->get($level) ? $this->get($level) : $this->get('sticky');

                break;
            case 'normal':
            default:
                return $this->get('normal');
                break;
        }

    }

    public function get_info($listing_id) {
        if (!$listing_id)
            return null;

        $sticky_status = get_post_meta( $listing_id, '_wpbdp[sticky]', true );

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
        $current_info = $this->get_info( $listing_id );

        if ( $only_upgrade && (array_search($level_id, $this->_order) < array_search($current_info->level->id, $this->_order)) )
            return false;

        if ( $level_id == 'normal' ) {
            delete_post_meta( $listing_id, '_wpbdp[sticky]' );
            delete_post_meta( $listing_id, '_wpbdp[sticky_level]' );
        } else {
            update_post_meta( $listing_id, '_wpbdp[sticky]', 'sticky' );
            update_post_meta( $listing_id, '_wpbdp[sticky_level]', $level_id );
        }

        // TODO: approve/cancel transactions related to this operation.
    }

}

/*
 * For compat. with other APIs (< 3.5.4)
 */
class WPBDP_ListingUpgrades extends WPBDP_Listing_Upgrade_API {}

