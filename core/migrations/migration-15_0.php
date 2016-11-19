<?php

class WPBDP__Migrations__15_0 extends WPBDP__Migration {

    private function _validate_config( $levels ) {
        if ( empty( $_POST ) || empty( $_POST['level'] ) )
            return false;

        $config = $_POST['level'];

        foreach ( array_keys( $levels ) as $level_id ) {
            if ( empty( $config[ $level_id ]['strategy'] ) )
                return false;

            $strategy = $config[ $level_id ]['strategy'];
            $fee_id = absint( $config[ $level_id ]['move_to'] );

            if ( 'move' == $strategy && ! $fee_id )
                return false;
        }

        return true;
    }

    public function _upgrade_config() {
        global $wpdb;

        echo '<div id="wpbdp-manual-upgrade-15_0-config">';

        _ex( 'Business Directory <b>version @next-release</b> is removing support for featured levels. We already have fee plans with the ability to make listings sticky/featured and we think this setup offer more flexibility and reduces both user and admin confusion.', 'migration-15', 'WPBDM' );
        echo '<br />';
        _ex( 'Before continuing with the migration, we need to ask you what to do with your current featured levels and listings using them.', 'migration-15', 'WPBDM' );

        $levels = array();

        if ( $wpdb->get_row( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'wpbdp_x_featured_levels' ) ) ) {
            $db_levels = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpbdp_x_featured_levels" );

            foreach ( $db_levels as $db_level ) {
                $levels[ $db_level->id ] = (array) $db_level;
            }
        }

        unset( $levels['normal'] );
        if ( ! isset( $levels['sticky'] ) ) {
            $levels['sticky'] = array( 'name' => _x( 'Featured Listing', 'listings-api', 'WPBDM' ),
                                       'description' => wpbdp_get_option( 'featured-description' ),
                                       'cost' => floatval( wpbdp_get_option( 'featured-price' ) ) );
        }

        // Validate (in case data was POSTed).
        if ( $this->_validate_config( $levels ) ) {
            $newconfig = array();

            foreach ( $levels as $level_id => $level_info ) {
                $newconfig[ $level_id ] = array(
                    'strategy' => $_POST['level'][ $level_id ]['strategy'],
                    'move_to' => absint( $_POST['level'][ $level_id ]['move_to'] ),
                    'fee_days' => absint( $_POST['level'][ $level_id ]['fee_days'] ),
                    'fee_images' => absint( $_POST['level'][ $level_id ]['fee_images'] ),
                    'label' => $level_info['name'],
                    'description' => $level_info['description'],
                    'cost' => $level_info['cost']
                );
            }

            $this->set_manual_upgrade_config( $newconfig );
            $this->manual_upgrade_configured();
            return;
        }

        // Compute listing counts.
        foreach ( $levels as $level_id => &$level )
            $level['count'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID WHERE p.post_type = %s AND pm.meta_key = %s AND pm.meta_value = %s", WPBDP_POST_TYPE, '_wpbdp[sticky_level]', $level_id ) );

        // Gather possible feet options for migration.
        $fee_options = '';
        foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT id, label FROM {$wpdb->prefix}wpbdp_fees WHERE sticky = %d", 1 ) ) as $r ) {
            $fee_options .= '<option value="' . $r->id . '">' . $r->label . '</option>';
        }

        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="level-name">' . _x( 'Featured Level', 'upgrade-15', 'WPBDM' ) . '</th>';
        echo '<th>' . _x( 'What to do with it?', 'upgrade-15', 'WPBDM' ) . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ( $levels as $level_id => $level ) {
            echo '<tr>';
            echo '<td class="level-name">';
            echo '<strong>' . $level['name'] . '</strong><br />';
            echo sprintf( _nx( '%d listing is on this level.', '%d listings are on this level.', $level['count'], 'upgrade-15', 'WPBDM' ), $level['count'] );
            echo '</td>';
            echo '<td>';
            echo '<select class="level-migration" name="level[' . $level_id . '][strategy]">';
            echo '<option class="placeholder" value="">' . _x( 'Select an option', 'upgrade-15', 'WPBDM' ) . '</option>';
            echo '<option data-description="' . esc_attr( _x( 'Listings will keep their current fee plan and the featured flag on up until expiration.', 'upgrade-15', 'WPBDM' ) ) . '" value="remove">' . _x( 'Delete this level and keep listings on their respective plans only.', 'upgrade-15', 'WPBDM' ) . '</option>';

            if ( $fee_options )
                echo '<option data-description="' . esc_attr( _x( 'Listings will be moved from their current fee plan to the one specified below. They will keep the featured flag on up until expiration.', 'upgrade-15', 'WPBDM' ) ) . '" value="move">' . _x( 'Move listings using this level to an existing fee plan.', 'upgrade-15', 'WPBDM' ) . '</option>';

            echo '<option data-description="' . esc_attr( _x( 'Listings will be moved from their current fee plan to this new fee plan using the same configuration as this featured level as starting point.', 'upgrade-15', 'WPBDM' ) ) . '" value="create">' . _x( 'Replace this featured level with a new fee plan.', 'upgrade-15', 'WPBDM' ) . '</option>';
            echo '</select>';
            echo '<div class="option-description"></div>';


            if ( $fee_options ):
                echo '<div class="option-configuration option-move" >';
                echo '<select name="level[' . $level_id . '][move_to]">';
                echo $fee_options;
                echo '</select>';
                echo '</div>';
            endif;

            echo '<div class="option-configuration option-create">';
            echo '<label> ' ._x( 'Listing run (in days) for new fee:', 'upgrade-15', 'WPBDM' ) . ' <input type="text" name="level[' . $level_id . '][fee_days]" size="6" /></label>';
            echo '<br />';
            echo '<label> ' ._x( '# of images for new fee:', 'upgrade-15', 'WPBDM' ) . ' <input type="text" name="level[' . $level_id . '][fee_images]" size="3" /></label>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

            // $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s", $level->downgrade, '_wpbdp[sticky_level]', $level->id) );
        echo '</div>';
   }

    public function migrate() {
        global $wpdb;

        // Remove orphan everything first to make things easier for us.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id NOT IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)", WPBDP_POST_TYPE ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id NOT IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)", WPBDP_POST_TYPE ) );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}wpbdp_payments_items WHERE payment_id NOT IN (SELECT id FROM {$wpdb->prefix}wpbdp_payments)" );

        $this->request_manual_upgrade_with_configuration( '_upgrade_to_15_migrate_fees', '_upgrade_config' );
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

        // $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_fees DROP COLUMN categories" );
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

    private function register_featured_to_fee( $sticky_level, $fee_id ) {
        $data = get_option( 'wpbdp-featured-to-fee', array() );

        if ( ! is_array( $data ) )
            $data = array();

        if ( isset( $data[ $sticky_level ] ) )
            return;

        $data[ $sticky_level ] = absint( $fee_id );

        update_option( 'wpbdp-featured-to-fee', $data, false );
    }

    private function get_fee_for_featured( $sticky_level ) {
        $data = get_option( 'wpbdp-featured-to-fee', array() );

        if ( ! is_array( $data ) || ! array_key_exists( $sticky_level, $data ) )
            return false;

        return $data[ $sticky_level ];
    }

    public function migrate_sticky_info( &$msg ) {
        global $wpdb;
        static $batch_size = 40;

        $config = $this->get_config();

        if ( ! $config ) {
            // Delete all sticky info.
            $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp[sticky]' );
            $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp[sticky_level]' );

            return true;
        }

        foreach ( $config as $level_id => $level_config ) {
            $fee_id = 0;

            switch ( $level_config['strategy'] ) {
            case 'remove':
                $this->register_featured_to_fee( $level_id, 0 );
                break;
            case 'move':
                $this->register_featured_to_fee( $level_id, $level_config['move_to'] );
                $fee = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_fees WHERE id = %d", $level_config['move_to'] ) );

                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listings_plans SET fee_id = %d, fee_price = %s, fee_days = %d, fee_images = %d, is_sticky = %d WHERE listing_id IN ( SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_key = %s AND pm.meta_value = %s )",
                $fee->id,
                $fee->amount,
                $fee->days,
                $fee->images,
                1,
                '_wpbdp[sticky_level]',
                $level_id ) );

                break;
            case 'create':
                $fee_id = $this->get_fee_for_featured( $level_id );

                if ( false === $fee_id ) {
                    // Create fee.
                    $wpdb->insert( $wpdb->prefix . 'wpbdp_fees',
                        array(
                        'label' => $level_config['label'],
                        'description' => $level_config['description'],
                        'amount' => $level_config['cost'],
                        'days' => $level_config['fee_days'],
                        'images' => $level_config['fee_images'],
                        'sticky' => 1,
                        'enabled' => 1,
                        'pricing_model' => 'flat',
                        'supported_categories' => 'all' ) );
                    $fee_id = $wpdb->insert_id;
                    $this->register_featured_to_fee( $level_id, $fee_id );
                }

                $fee = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_fees WHERE id = %d", $fee_id ) );

                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listings_plans SET fee_id = %d, fee_price = %s, fee_days = %d, fee_images = %d, is_sticky = %d WHERE listing_id IN ( SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_key = %s AND pm.meta_value = %s )",
                $fee->id,
                $fee->amount,
                $fee->days,
                $fee->images,
                1,
                '_wpbdp[sticky_level]',
                $level_id ) );

                break;
            default:
                break;
            }

            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", '_wpbdp[sticky_level]', $level_id ) );
        }

        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listings_plans pl JOIN {$wpdb->postmeta} pm ON pm.post_id = pl.listing_id SET pl.is_sticky = %d WHERE pm.meta_key = %s AND pm.meta_value = %s", 1, '_wpbdp[sticky]', 'sticky' ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp[sticky]' ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp[sticky_level]' ) );
        $msg = _x( 'Migrating featured level information.', 'installer', 'WPBDM' );

        return true;
    }

}
