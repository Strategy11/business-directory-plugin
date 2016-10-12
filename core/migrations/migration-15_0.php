<?php

class WPBDP__Migrations__15_0 extends WPBDP__Migration {

    public function migrate() {
        global $wpdb;

        // Remove orphan everything first to make things easier for us.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id NOT IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)", WPBDP_POST_TYPE ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id NOT IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)", WPBDP_POST_TYPE ) );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}wpbdp_payments_items WHERE payment_id NOT IN (SELECT id FROM {$wpdb->prefix}wpbdp_payments)" );

        $this->request_manual_upgrade( '_upgrade_to_15_migrate_fees' );
    }

    public function _upgrade_to_15_migrate_fees() {
        $status_msg = '';
        $done = false;
        $done = $this->_migrate_fee_plans( $status_msg );

        if ( $done )
            $done = $this->_upgrade_to_15_migrate_fees_fees( $status_msg );

        if ( $done )
            $done = $this->_upgrade_to_15_migrate_fees_payments( $status_msg );

        if ( $done )
            $done = $this->fix_orphans( $status_msg );

        if ( $done )
            $done = $this->migrate_sticky_info( $status_msg );

        return array( 'ok' => true, 'done' => $done, 'status' => $status_msg );
    }

    public function _migrate_fee_plans( &$msg ) {
        global $wpdb;

        $msg = _x( 'Migrating fee plans columns...', 'installer', 'WPBDM' );

        foreach ( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpbdp_fees" ) as $fee ) {
            $old_categories = isset( $fee->categories ) ? unserialize( $fee->categories ) : array();
            $update = array();

            if ( empty( $fee->supported_categories ) ) {
                if ( ! is_array( $old_categories ) || empty( $old_categories ) || ( isset( $old_categories['all'] ) && $old_categories['all'] ) )
                    $update['supported_categories'] = 'all';
                else
                    $update['supported_categories'] = implode( ',', array_map( 'absint', $old_categories['categories'] ) );
            }

            if ( empty( $fee->pricing_model ) )
                $update['pricing_model'] = 'flat';

            if ( ! $update )
                continue;

            if ( false === $wpdb->update( $wpdb->prefix . 'wpbdp_fees', $update, array( 'id' => $fee->id ) ) ) {
                $msg = sprintf( _x( '! Could not migrate fee "%s" (%d)', 'installer', 'WPBDM' ), $fee->label, $fee->id );
                return false;
            }
        }

        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_fees DROP COLUMN categories" );
        return true;
    }

    public function _upgrade_to_15_migrate_fees_fees( &$msg ) {
        global $wpdb;
        static $batch_size = 20;

        $listings_count = $wpdb->get_var( "SELECT COUNT(DISTINCT listing_id) FROM {$wpdb->prefix}wpbdp_listing_fees" );
        $listings = $wpdb->get_col( "SELECT DISTINCT listing_id FROM {$wpdb->prefix}wpbdp_listing_fees ORDER BY listing_id LIMIT {$batch_size}" );

        if ( ! $listings )
            return true;

        foreach ( $listings as $listing_id ) {
            $fee_id = 0;
            $fee_price = 0.0;
            $fee_days = -1;
            $fee_images = -1;
            $expires_on = -1;
            $is_sticky = 0;
            $is_recurring = 0;
            $recurring_id = '';

            // Check if the listing has a recurring fee. Use it if available and remove the others.
            if ( $recurring_fee = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND recurring = %d LIMIT 1", $listing_id, 1 ) ) ) {
                $is_recurring = 1;
                $fee_days = (int) $recurring_fee->fee_days;
                $fee_images = (int) $recurring_fee->fee_images;
                $expires_on = $recurring_fee->expires_on;
                $is_sticky = (int) $recurring_fee->sticky;
                $recurring_id = $recurring_fee->recurring_id;

                if ( $_ = wpbdp_get_fee( $recurring_fee->fee_id ) ) {
                    $fee_id = (int) $recurring_fee->fee_id;
                    $fee_price = floatval( $_->amount );
                }
            } else {
                // For non-recurring listings, obtain the "best" features of all fees (including expiration).
                $fees = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d", $listing_id ) );
                foreach ( $fees as $f ) {
                    if ( ! $f->expires_on )
                        $expires_on = null;
                    else if ( ! is_null( $expires_on ) )
                        $expires_on = max( strtotime( $f->expires_on ), $expires_on );

                    if ( 0 == (int) $f->fee_days )
                        $fee_days = 0;
                    else if ( 0 !== $fee_days )
                        $fee_days = max( (int) $f->fee_days, $fee_days );

                    $fee_images = max( (int) $f->fee_images, $fee_images );
                    $is_sticky = max( (int) $f->sticky, $is_sticky );

                    if ( $_ = wpbdp_get_fee( $f->fee_id ) ) {
                        $fee_price += floatval( $_->amount );
                        $fee_id = (int) $f->fee_id;
                    }
                }
                $expires_on = ! is_null( $expires_on ) ? date( 'Y-m-d H:i:s', $expires_on ) : null;
            }

            // Insert new plan record and delete everything from old fees table.
            $record = array( 'listing_id' => $listing_id,
                             'status' => 'ok',
                             'fee_id' => $fee_id,
                             'fee_price' => $fee_price,
                             'fee_days' => $fee_days,
                             'fee_images' => $fee_images,
                             'is_sticky' => $is_sticky,
                             'is_recurring' => $is_recurring,
                             'subscription_id' => $recurring_id );
            if ( $expires_on )
                $record['expiration_date'] = $expires_on;

            $wpdb->insert( $wpdb->prefix . 'wpbdp_listings_plans', $record );
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d", $listing_id ) );
        }

        $msg = sprintf( _x( 'Updating listing fees: %d listings remaining...', 'installer', 'WPBDM' ), max( $listings_count - $batch_size, 0 ) );
        return false;
    }

    public function _upgrade_to_15_migrate_fees_payments( &$msg ) {
        global $wpdb;

        static $batch_size = 10;

        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT p.listing_id) FROM {$wpdb->prefix}wpbdp_payments p WHERE p.status = %s AND p.listing_id NOT IN (SELECT listing_id FROM {$wpdb->prefix}wpbdp_listings_plans) AND EXISTS(SELECT 1 FROM {$wpdb->prefix}wpbdp_payments_items WHERE payment_id = p.id AND (item_type = %s OR item_type = %s))", 'pending', 'fee', 'recurring_fee' ) );

        $listings = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p.listing_id FROM {$wpdb->prefix}wpbdp_payments p WHERE p.status = %s AND p.listing_id NOT IN (SELECT listing_id FROM {$wpdb->prefix}wpbdp_listings_plans) AND EXISTS(SELECT 1 FROM {$wpdb->prefix}wpbdp_payments_items WHERE payment_id = p.id AND (item_type = %s OR item_type = %s)) ORDER BY listing_id LIMIT {$batch_size}", 'pending', 'fee', 'recurring_fee' ) );

        if ( ! $listings )
            return true;

        foreach ( $listings as $listing_id ) {
            $pending_items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wpbdp_payments_items WHERE payment_id IN (SELECT id FROM {$wpdb->prefix}wpbdp_payments WHERE status = %s AND listing_id = %d) AND (item_type = %s OR item_type = %s)",
                    'pending',
                    $listing_id,
                    'recurring_fee',
                    'fee' )
            );

            $record = array( 'listing_id' => $listing_id,
                             'status' => 'pending',
                             'fee_id' => 0,
                             'fee_price' => 0,
                             'fee_days' => 0,
                             'fee_images' => 0,
                             'is_sticky' => 0,
                             'is_recurring' => 0,
                             'subscription_id' => '',
                             'expiration_date' => 0 );

            foreach ( $pending_items as $i ) {
                $data = unserialize( $i->data );

                if ( 'recurring_fee' == $i->item_type ) {
                    $record['fee_id'] = $data['fee_id'];
                    $record['fee_days'] = $data['fee_days'];
                    $record['fee_images'] = $data['fee_images'];
                    $record['fee_price'] = $i->amount;
                    $record['is_recurring'] = 1;

                    if ( $_ = wpbdp_get_fee( $data['fee_id'] ) )
                        $record['is_sticky'] = $_->sticky;

                    break;
                }

                $record['fee_id'] = $data['fee_id'];
                $record['fee_price'] = $i->amount;
                $record['fee_days'] = $data['fee_days'];
                $record['fee_images'] = $data['fee_images'];

                if ( $_ = wpbdp_get_fee( $data['fee_id'] ) )
                    $record['is_sticky'] = $_->sticky;
            }

            if ( 0 == $record['fee_days'] ) {
                unset( $record['expiration_date'] );
            } else {
                $time = strtotime( $wpdb->get_var( $wpdb->prepare( "SELECT post_date FROM {$wpdb->posts} WHERE ID = %d", $listing_id ) ) );
                $record['expiration_date'] = date( 'Y-m-d H:i:s', strtotime( sprintf( '+%d days', $record['fee_days'] ), $time ) );
            }

            $wpdb->insert( $wpdb->prefix . 'wpbdp_listings_plans', $record );
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d", $listing_id ) );
        }

        $msg = sprintf( _x( 'Updating listing pending payments: %d listings remaining...', 'installer', 'WPBDM' ), max( $count - $batch_size, 0 ) );
        return false;
    }

    public function fix_orphans( &$msg ) {
        global $wpdb;

        $msg = '';
        static $batch_size = 20;

        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND ID NOT IN (SELECT listing_id FROM {$wpdb->prefix}wpbdp_listings_plans)", WPBDP_POST_TYPE ) );
        $listings = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID NOT IN (SELECT listing_id FROM {$wpdb->prefix}wpbdp_listings_plans) ORDER BY ID LIMIT {$batch_size}", WPBDP_POST_TYPE ) );
        $free_plan = WPBDP_Fee_Plan::get_free_plan();

        if ( ! $listings )
            return true;

        foreach ( $listings as $listing_id ) {
            $l = WPBDP_Listing::get( $listing_id );
            $l->set_fee_plan( $free_plan );
        }

        $msg = sprintf( _x( 'Assigning fees to orphan listings: %d listings remaining...', 'installer', 'WPBDM' ), max( $count - $batch_size, 0 ) );
        return false;
    }

    public function migrate_sticky_info( &$msg ) {
        global $wpdb;
        static $batch_size = 40;

        $msg = '';
        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_listings_plans WHERE featured_level IS NULL OR featured_level = %s", '' ) );
        $listings = $wpdb->get_col( $wpdb->prepare( "SELECT listing_id FROM {$wpdb->prefix}wpbdp_listings_plans WHERE featured_level IS NULL OR featured_level = %s ORDER BY listing_id LIMIT %d", '', $batch_size ) );

        if ( ! $listings ) {
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listings_plans SET featured_level = NULL WHERE featured_level = %s", 'normal' ) );
            return true;
        }

        $record = array( 'featured_price' => 0.0, 'featured_level' => 'normal', 'is_sticky' => 0 );

        foreach ( $listings as $listing_id ) {
            $status = get_post_meta( $listing_id, '_wpbdp[sticky]', true );
            $level = get_post_meta( $listing_id, '_wpbdp[sticky_level]', true );

            if ( ! $status || 'pending' == $status ) {
                $record['featured_level'] = 'normal';
            } elseif ( 'sticky' == $status ) {
                $record['is_sticky'] = 1;

                if ( $level != 'sticky' )
                    $price = (float) $wpdb->get_var( $wpdb->prepare( "SELECT cost FROM {$wpdb->prefix}wpbdp_x_featured_levels WHERE id = %s", $level ) );
                else
                    $price = (float) wpbdp_get_option( 'featured-price' );

                $record['featured_price'] = $price;
                $record['featured_level'] = ( $level ? $level : 'sticky' );
            }

            if ( false !== $wpdb->update( $wpdb->prefix . 'wpbdp_listings_plans', $record, array( 'listing_id' => $listing_id ) ) ) {
                delete_post_meta( $listing_id, '_wpbdp[sticky]' );
                delete_post_meta( $listing_id, '_wpbdp[sticky_level]' );
            }
        }

        $msg = sprintf( _x( 'Migrating featured level information: %d listings remaining...', 'installer', 'WPBDM' ), max( $count - $batch_size, 0 ) );

        return false;
    }

}
