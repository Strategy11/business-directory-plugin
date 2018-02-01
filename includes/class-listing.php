<?php
require_once( WPBDP_PATH . 'includes/class-payment.php' );
require_once( WPBDP_PATH . 'includes/class-listing-subscription.php' );
require_once( WPBDP_PATH . 'includes/helpers/class-listing-image.php' );

/**
 * @since 3.4
 */
class WPBDP_Listing {

    private $id = 0;
    private $new = true;

    public function __construct( $id ) {
        $this->id = intval( $id );
    }

    public function get_field_value( $id ) {
        $field = null;

        if ( is_numeric( $id ) ) {
            $field = wpbdp_get_form_field( $id );
        } else {
            $field = wpbdp_get_form_fields( array( 'association' => $id, 'unique' => true ) );
        }

        return $field ? $field->html_value( $this->id )  : '';
    }

    public function get_modified_date() {
        if ( ! $this->id )
            return '';

        return date_i18n( get_option( 'date_format' ), get_post_modified_time( 'U', false, $this->id ) );
    }

    public function get_images( $fields = 'all', $sorted = false ) {
        $q = array( 'numberposts' => -1, 'post_type' => 'attachment', 'post_parent' => $this->id );
        $result = array();

        foreach ( get_posts( $q ) as $attachment ) {
            if ( ! wp_attachment_is_image( $attachment->ID ) )
                continue;

            if ( 'id' == $fields || 'ids' == $fields )
                $result[] = $attachment->ID;
            else
                $result[] = WPBDP_Listing_Image::get( $attachment->ID );
        }

        if ( $result && $sorted ) {
            uasort( $result, create_function( '$x, $y', "return \$y->weight - \$x->weight;" ) );
        }

        return $result;
    }

    /**
     * @since 3.6.11
     */
    public function get_images_meta() {
        $images = $this->get_images( 'ids' );
        $meta = array();

        foreach ( $images as $img_id ) {
            $meta[ $img_id ] = array( 'order' => (int) get_post_meta( $img_id, '_wpbdp_image_weight', true ),
                                      'caption' => strval( get_post_meta( $img_id, '_wpbdp_image_caption', true ) ) );
        }

        return $meta;
    }

    /**
     * Sets listing images.
     * @param array $images array of image IDs.
     * @param boolean $append if TRUE images will be appended without clearing previous ones.
     */
    public function set_images( $images = array(), $append = false ) {
        if ( ! $append ) {
            $current = $this->get_images( 'ids' );

            foreach ( $current as $img_id ) {
                if ( ! in_array( $img_id, $images, true ) && wp_attachment_is_image( $img_id ) )
                    wp_delete_attachment( $img_id, true );
            }
        }

        foreach ( $images as $image_id )
            wp_update_post( array( 'ID' => $image_id, 'post_parent' => $this->id ) );
    }

    public function set_thumbnail_id( $image_id ) {
        if ( ! $image_id )
            return delete_post_meta( $this->id, '_wpbdp[thumbnail_id]' );

        return update_post_meta( $this->id, '_wpbdp[thumbnail_id]', $image_id );
    }

    /**
     * Gets the attachment object that representes this listing's thumbnail.
     *
     * @since 5.1.7
     *
     * @return Post     An attachment of this listing.
     */
    public function get_thumbnail() {
        $thumbnail_id = get_post_meta( $this->id, '_wpbdp[thumbnail_id]', true );

        if ( $thumbnail_id ) {
            $thumbnail = get_post( $thumbnail_id );
        } else {
            $thumbnail = null;
        }

        if ( $thumbnail ) {
            return $thumbnail;
        }

        $images = $this->get_images( 'ids' );

        if ( ! $images && $thumbnail_id ) {
            $this->set_thumbnail_id( 0 );
            return null;
        }

        if ( ! $images ) {
            return null;
        }

        $this->set_thumbnail_id( $images[0] );

        return get_post( $images[0] );
    }

    /**
     * Get the ID of the attachment that represents this listing's thumbnail.
     *
     * @return int  An ID or 0.
     */
    public function get_thumbnail_id() {
        $thumbnail = $this->get_thumbnail();

        if ( ! $thumbnail ) {
            return 0;
        }

        return $thumbnail->ID;
    }

    public function set_title( $title ) {
        wp_update_post( array( 'ID' => $this->id, 'post_title' => $title ) );
    }

    public function get_title() {
        return get_the_title( $this->id );
    }

    public function get_id() {
        return $this->id;
    }

    public function calculate_expiration_date( $time, &$fee ) {
        if ( is_array( $fee ) ) {
            $days = isset( $fee['days'] ) ? $fee['days'] : $fee['fee_days'];
        } else if ( is_a( $fee, 'WPBDP__Fee_Plan' ) ) {
            $days = $fee->days;
        } elseif ( is_object( $fee ) && isset( $fee->fee_days ) ) {
            $days = $fee->fee_days;
        } else {
            $days = 0;
        }

        if ( 0 == $days )
            return null;

        $expire_time = strtotime( sprintf( '+%d days', $days ), $time );
        return date( 'Y-m-d H:i:s', $expire_time );
    }

    public function get_categories( $fields='all' ) {
        $args = array();
        $args['fields'] = $fields;

        return wp_get_post_terms( $this->id, WPBDP_CATEGORY_TAX, $args );
    }

    public function set_categories( $categories ) {
        $category_ids = array_map( 'intval', $categories );
        wp_set_post_terms( $this->id, $category_ids, WPBDP_CATEGORY_TAX, false );
    }

    /**
     * @since 5.0
     */
    public function is_recurring() {
        if ( $plan = $this->get_fee_plan() ) {
            return $plan->is_recurring;
        }

        return false;
    }

    /**
     * @since 5.0
     */
    public function get_subscription() {
        return new WPBDP__Listing_Subscription( $this->id );
    }

    /**
     * @since 5.0
     */
    public function has_subscription() {
        global $wpdb;
        return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d AND is_recurring = %d", $this->id, 1 ) ) ) > 0;
    }

    public function is_published() {
        return 'publish' == get_post_status( $this->id );
    }

    public function get_permalink() {
        if ( ! $this->id )
            return '';

        return get_permalink( $this->id );
    }

    /**
     * @since 5.0
     */
    public function get_admin_edit_link() {
        return admin_url( 'post.php?post=' . $this->id . '&action=edit' );
    }

    public function get_payment_status() {
        $status = 'ok';

        if ( WPBDP_Payment::objects()->filter( array( 'listing_id' => $this->id, 'status' => 'pending' ) )->count() > 0 )
            $status = 'pending';

        return apply_filters( 'WPBDP_Listing::get_payment_status', $status, $this->id );
    }

    /**
     * @since 5.0
     */
    public function get_payments() {
        $payments = WPBDP_Payment::objects()->filter( array( 'listing_id' => $this->id ) );
        return $payments;
    }

    public function get_latest_payments() {
        return WPBDP_Payment::objects()->filter( array( 'listing_id' => $this->id ) )->order_by( '-id' )->to_array();
    }

    public function publish() {
        if ( ! $this->id )
            return;

        wp_update_post( array( 'post_status' => 'publish', 'ID' => $this->id ) );
    }

    /**
     * @since 5.0
     */
    public function set_status( $status ) {
        global $wpdb;

        $old_status = $this->get_status( false, false );
        $new_status = $status;

        if ( $old_status == $new_status || ! in_array( $new_status, array_keys( self::get_stati() ), true ) )
            return;

        $wpdb->update( $wpdb->prefix . 'wpbdp_listings', array( 'listing_status' => $new_status ), array( 'listing_id' => $this->id ) );

        switch ( $new_status ) {
        case 'expired':
            if ( 'trash' != get_post_status( $this->id ) ) {
                $this->set_post_status( 'draft' );
            }

            wpbdp_insert_log( array( 'log_type' => 'listing.expired', 'object_id' => $this->id, 'message' => _x( 'Listing expired', 'listing', 'WPBDM' ) ) );
            do_action( 'wpbdp_listing_expired', $this );
            break;
        default:
            break;
        }

        do_action( 'wpbdp_listing_status_change', $this, $old_status, $new_status );
    }

    public function set_post_status( $status ) {
        if ( ! $this->id )
            return;

        wp_update_post( array( 'post_status' => $status, 'ID' => $this->id ) );
    }

    public function delete() {
        global $wpdb;
        $wpdb->update( $wpdb->posts, array( 'post_status' => wpbdp_get_option( 'deleted-status' ) ), array( 'ID' => $this->id ) );
        clean_post_cache( $this->id );

        return true;
    }

    public function notify( $kind = 'save', &$extra = null ) {
        // if ( in_array( $kind, array( 'save', 'edit', 'new' ), true ) )
        //     $this->save();
        //
        // switch ( $kind ) {
        //     case 'save':
        //         break;
        //
        //     case 'edit':
        //         do_action_ref_array( 'wpbdp_edit_listing', array( &$this, &$extra ) );
        //         break;
        //
        //     default:
        //         break;
        // }
    }

    /**
     * @since 3.5.3
     */
    public function get_renewal_hash( $deprecated = 0 ) {
        $hash = base64_encode( 'listing_id=' . $this->id . '&category_id=' . $deprecated );
        return $hash;
    }

    /**
     * @since 5.0
     */
    public function renew() {
        $plan = $this->get_fee_plan();

        if ( ! $plan )
            return false;


        global $wpdb;

        $row = array();
        if ( $expiration = $this->calculate_expiration_date( current_time( 'timestamp' ), $plan ) )
            $row['expiration_date'] = $expiration;

        if ( ! empty( $row ) ) {
            $wpdb->update( $wpdb->prefix . 'wpbdp_listings', $row, array( 'listing_id' => $this->id ) );
        } else {
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listings SET expiration_date = NULL WHERE listing_id = %d", $this->id ) );
        }

        $this->set_status( 'complete' );
        $this->set_post_status( 'publish' );

        do_action( 'wpbdp_listing_renewed', $this, false, 'admin' );
    }

    public function get_renewal_url( $deprecated = 0 ) {
        // TODO: we should probably encode the ID somehow using info that only we have so external users can't
        // start checking renewal for all listings just by changing the ID.
        return wpbdp_url( 'renew_listing', $this->id );
    }

    /**
     * @since 4.0
     */
    public function get_access_key() {
        if ( $key = get_post_meta( $this->id, '_wpbdp[access_key]', true ) )
            return $key;

        // Generate access key.
        $new_key = sha1( sprintf( '%s%s%d', AUTH_KEY, uniqid( '', true ), rand( 1, 1000 ) ) );
        if ( update_post_meta( $this->id, '_wpbdp[access_key]', $new_key ) )
            return $new_key;
    }

    /**
     * @since 5.0
     */
    public function validate_access_key_hash( $hash ) {
        $key = $this->get_access_key();
        return sha1( AUTH_KEY . $key ) == $hash;
    }

    public function get_author_meta( $meta ) {
        if ( ! $this->id )
            return '';

        $post = get_post( $this->id );
        return get_the_author_meta( $meta, $post->post_author );
    }

    /**
     * @since 3.6.9
     */
    public function get_sticky_status( $consider_plans = true ) {
        global $wpdb;
        $is_sticky = (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 AS x FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d AND is_sticky = %d",
                $this->id,
                1 )
        );

        return $is_sticky ? 'sticky' : 'normal';
    }

    /**
     * @since 5.0
     */
    public function has_fee_plan( $fee = false ) {
        $current = $this->get_fee_plan();
        return ( ! $fee && ! empty( $current ) ) || ( $fee && $current && $current->id == $fee );
    }

    /**
     * @since 5.0
     */
    public function get_fee_plan() {
        global $wpdb;

        $res = $wpdb->get_row( $wpdb->prepare( "SELECT listing_id, fee_id, fee_price, fee_days, fee_images, expiration_date, is_recurring, is_sticky FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d LIMIT 1", $this->id ) );
        if ( ! $res )
            return false;

        if ( $res->fee_id )
            $fee = wpbdp_get_fee_plan( $res->fee_id );
        else
            $fee = null;

        $res->fee = $fee;
        $res->fee_label = $fee ? $fee->label : _x( '(Unavailable Plan)', 'listing', 'WPBDM' );
        $res->expired = $res->expiration_date ? strtotime( $res->expiration_date ) <= current_time( 'timestamp' ) : false;

        return $res;
    }

    /**
     * @since 5.0
     */
    public function update_plan( $plan = null, $args = array() ) {
        global $wpdb;

        $args = wp_parse_args( $args, array(
            'clear'       => 0, /* Whether to use old values (if available). */
            'recalculate' => 1 /* Whether to recalculate the expiration or not */
        ) );

        $row = array();

        if ( is_numeric( $plan ) || ( is_array( $plan ) && ! empty( $plan['fee_id'] ) ) ) {
            $plan_id = is_numeric( $plan ) ? absint( $plan ) : absint( $plan['fee_id'] );

            if ( $plan_ = wpbdp_get_fee_plan( $plan_id ) ) {
                $row['fee_id'] = $plan_id;
                $row['fee_images'] = $plan_->images;
                $row['fee_days'] = $plan_->days;
                $row['is_sticky'] = $plan_->sticky;
                $row['fee_price'] = $plan_->amount;
            }
        }

        if ( is_array( $plan ) ) {
            foreach ( array( 'fee_days', 'fee_images', 'fee_price', 'is_sticky', 'expiration_date', 'is_recurring', 'subscription_id', 'subscription_data' ) as $key ) {
                if ( array_key_exists( $key, $plan ) ) {
                    $row[ $key ] = $plan[ $key ];
                }
            }

            if ( ! empty( $plan['amount'] ) ) {
                $row['fee_price'] = $plan['amount'];
            }
        }

        if ( ! $args['clear'] ) {
            $old_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d", $this->id ), ARRAY_A );

            if ( $old_row ) {
                $row = array_merge( $old_row, $row );
            }
        }

        if ( empty( $row ) )
            return false;

        $row['listing_id'] = $this->id;
        $row['is_sticky'] = (int) $row['is_sticky'];

        if ( $args['recalculate'] ) {
            if ( ! $plan || ! array_key_exists( 'expiration_date', $plan ) ) {
                $expiration = $this->calculate_expiration_date( current_time( 'timestamp' ), $row );

                if ( $expiration ) {
                    $row['expiration_date'] = $expiration;
                }
            }
        }

        if ( is_null( $row['expiration_date'] ) || empty( $row['expiration_date'] ) ) {
            unset( $row['expiration_date'] );
        }

        if ( ! empty( $row['recurring_data'] ) ) {
            $row['recurring_data'] = maybe_serialize( $row['recurring_data'] );
        }

        return $wpdb->replace( "{$wpdb->prefix}wpbdp_listings", $row );
    }

    /**
     * @since 5.0
     */
    public function set_fee_plan( $fee, $recurring_data = array() ) {
        global $wpdb;

        if ( is_null( $fee ) ) {
            $wpdb->delete( $wpdb->prefix . 'wpbdp_listings', array( 'listing_id' => $this->id ) );
            // $wpdb->replace( $wpdb->prefix . 'wpbdp_listings', array( 'listing_id' => $this->id, 'fee_id' => null, 'fee_days' => 0, 'fee_images' => 0, 'is_sticky' => 0, 'expiration_date' => null ) );
            return true;
        }

        $fee = is_numeric( $fee ) ? wpbdp_get_fee_plan( $fee ) : $fee;

        if ( ! $fee )
            return false;

        $row =  array( 'listing_id' => $this->id,
                       'fee_id' => $fee->id,
                       'fee_days' => $fee->days,
                       'fee_images' => $fee->images,
                       'fee_price' => $fee->calculate_amount( wp_get_post_terms( $this->id, WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) ) ),
                       'is_recurring' => $fee->recurring || ! empty( $recurring_data ),
                       'is_sticky' => (int) $fee->sticky );

        if ( $expiration = $this->calculate_expiration_date( current_time( 'timestamp' ), $fee ) )
            $row['expiration_date'] = $expiration;

        if ( ! empty( $recurring_data ) ) {
            $row['subscription_id']   = ! empty( $recurring_data['subscription_id'] ) ? $recurring_data['subscription_id'] : '';
            $row['subscription_data'] = ! empty( $recurring_data['subscription_data'] ) ? serialize( $recurring_data['subscription_data'] ) : '';
        }

        return $wpdb->replace( $wpdb->prefix . 'wpbdp_listings', $row );
    }

    /**
     * @since 5.0
     */
    public function set_fee_plan_with_payment( $fee, $recurring = false ) {
        $previous_plan = $this->get_fee_plan();
        $fee = is_numeric( $fee ) ? wpbdp_get_fee_plan( $fee ) : $fee;
        $this->set_fee_plan( $fee );
        $plan = $this->get_fee_plan();

        if ( $previous_plan && $fee->id == $previous_plan->fee_id ) {
            return null;
        }

        $payment = new WPBDP_Payment( array( 'listing_id' => $this->id, 'payment_type' => $previous_plan ? 'plan_change' : 'initial' ) );

        if ( $plan->is_recurring ) {
            $item_description = sprintf( _x( 'Plan "%s" (recurring)', 'listing', 'WPBDM' ), $plan->fee_label );
        } else {
            $item_description = sprintf( _x( 'Plan "%s"', 'listing', 'WPBDM' ), $plan->fee_label );
        }

        $payment->payment_items[] = array(
            'type' => $plan->is_recurring ? 'recurring_plan' : 'plan',
            'description' => $item_description,
            'amount' => $plan->fee_price,
            'fee_id' => $plan->fee_id,
            'fee_days' => $plan->fee_days,
            'fee_images' => $plan->fee_images
        );

        $payment->save();

        return $payment;
    }

    public function generate_or_retrieve_payment() {
        $plan = $this->get_fee_plan();

        if ( ! $plan )
            return false;

        $existing_payment = WPBDP_Payment::objects()->filter( array( 'listing_id' => $this->id, 'payment_type' => 'initial' ) )->get();

        if ( $existing_payment )
            return $existing_payment;

        $payment = new WPBDP_Payment( array( 'listing_id' => $this->id, 'payment_type' => 'initial' ) );

        $item = array(
            'type' => $plan->is_recurring ? 'recurring_plan' : 'plan',
            'description' => sprintf( _x( 'Plan "%s"', 'listing', 'WPBDM' ), $plan->fee_label ),
            'amount' => $plan->fee_price,
            'fee_id' => $plan->fee_id,
            'fee_days' => $plan->fee_days,
            'fee_images' => $plan->fee_images,
        );

        $payment->payment_items[] = $item;
        $payment->save();

        return $payment;
    }


    /**
     * @since 5.0
     */
    public function get_expiration_date() {
        $plan = $this->get_fee_plan();
        return $plan ? $plan->expiration_date : null;
    }

    /**
     * @since 5.0
     */
    public function get_expiration_time() {
        return strtotime( $this->get_expiration_date() );
    }

    /**
     * @since 5.0
     */
    public function get_status( $force_refresh = false, $calculate = true ) {
        global $wpdb;

        $status_ = $wpdb->get_var( $wpdb->prepare( "SELECT listing_status FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d", $this->id ) );

        if ( 'unknown' == $status_ || $force_refresh ) {
            if ( $calculate ) {
                $status = $this->calculate_status();
            } else {
                $status = 'unknown';
            }
        } else if ( ! $status_ ) {
            $status = 'incomplete';
        } else {
            $status = $status_;
        }

        $status = apply_filters( 'wpbdp_listing_status', $status, $this->id );

        if ( ! $status_ || $status_ != $status || $force_refresh )
            $wpdb->update( $wpdb->prefix . 'wpbdp_listings', array( 'listing_status' => $status ), array( 'listing_id' => $this->id ) );

        return $status;
    }

    /**
     * @since 5.0
     */
    public function get_status_label() {
        $stati = self::get_stati();

        return $stati[ $this->get_status() ];
    }

    /**
     * @since 5.0
     */
    private function calculate_status() {
        global $wpdb;

        $is_expired = (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 AS x FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d AND expiration_date IS NOT NULL AND expiration_date < %s",
                $this->id,
                current_time( 'mysql' )
            )
        );
        $pending_payment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d AND status = %s ORDER BY id DESC LIMIT 1",
                $this->id,
                'pending'
            )
        );

        if ( ! $pending_payment || ! in_array( $pending_payment->payment_type, array( 'initial', 'renewal' ), true ) )
            return $is_expired ? 'expired' : 'complete';

        return ( 'initial' == $pending_payment->payment_type ? 'pending_payment' : 'pending_renewal' );
    }

    /**
     * @since 5.0
     */
    public static function get_stati() {
        $stati = array(
            'unknown' => _x( 'Unknown', 'listing status', 'WPBDM' ),
            'legacy' => _x( 'Legacy', 'listing status', 'WPBDM' ),
            'incomplete' => _x( 'Incomplete', 'listing status', 'WPBDM' ),
            'pending_payment' => _x( 'Pending Payment', 'listing status', 'WPBDM' ),
            'complete' => _x( 'Complete', 'listing status', 'WPBDM' ),
            'pending_upgrade' => _x( 'Pending Upgrade', 'listing status', 'WPBDM' ),
            'expired' => _x( 'Expired', 'listing status', 'WPBDM' ),
            'pending_renewal' => _x( 'Pending Renewal', 'listing status', 'WPBDM' ),
            'abandoned' => _x( 'Abandoned', 'listing status', 'WPBDM' ),
        );
        $stati = apply_filters( 'wpbdp_listing_stati', $stati );

        return $stati;
    }

    /**
     * @since next-release
     */
    public static function count_listings( $args = array() ) {
        global $wpdb;

        $args = self::parse_count_args( $args );
        extract( $args );

        $query_post_statuses = "'" . implode( "','", $post_status ) . "'";
        $query_listing_statuses = "'" . implode( "','", $status ) . "'";
        $query = "SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->prefix}wpbdp_listings l ON p.ID = l.listing_id WHERE p.post_type = %s AND p.post_status IN ({$query_post_statuses}) AND l.listing_status IN ({$query_listing_statuses})";
        $query = $wpdb->prepare( $query, WPBDP_POST_TYPE );

        return absint( $wpdb->get_var( $query ) );
    }

    private static function parse_count_args( $args = array() ) {
        $args = wp_parse_args( $args, array(
            'post_status' => 'all',
            'status' => 'all',
        ) );

        if ( ! is_array( $args['post_status'] ) ) {
            if ( 'all' == $args['post_status'] ) {
                $args['post_status'] = array_keys( get_post_statuses() );
            } else {
                $args['post_status']= explode( ',', $args['post_status'] );
            }
        }

        if ( ! is_array( $args['status'] ) ) {
            if ( 'all' == $args['status'] ) {
                $args['status'] = array_keys( WPBDP_Listing::get_stati() );
            } else {
                $args['status'] = explode( ',', $args['status'] );
            }
        }

        return $args;
    }

    public static function count_listings_with_no_fee_plan( $args = array() ) {
        global $wpdb;

        $args = self::parse_count_args( $args );

        $query_post_statuses = "'" . implode( "','", $args['post_status'] ) . "'";

        $query = "SELECT COUNT(*) FROM {$wpdb->posts} p ";
        $query.= "LEFT JOIN {$wpdb->prefix}wpbdp_listings l ON ( p.ID = l.listing_id ) ";
        $query.= "WHERE p.post_type = %s ";
        $query.= "AND post_status IN ({$query_post_statuses}) ";
        $query.= "AND l.listing_id IS NULL ";

        return absint( $wpdb->get_var( $wpdb->prepare( $query, WPBDP_POST_TYPE ) ) );
    }

    /**
     * @since 5.0
     */
    public static function validate_access_key( $key, $email = '' ) {
        if ( ! $key )
            return false;

        global $wpdb;

        return intval( $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
            '_wpbdp[access_key]',
            $key  )
        ) ) > 0;
    }

    /**
     * @since 5.0
     */
    public function get_sequence_id() {
        $sequence_id = get_post_meta( $this->id, '_wpbdp[import_sequence_id]', true );

        if ( ! $sequence_id ) {
            global $wpdb;

            $candidate = intval( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(CAST(meta_value AS UNSIGNED INTEGER )) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                                                                 '_wpbdp[import_sequence_id]' ) ) );
            $candidate++;

            if ( false == add_post_meta( $this->id, '_wpbdp[import_sequence_id]', $candidate, true ) )
                $sequence_id = 0;
            else
                $sequence_id = $candidate;
        }

        return $sequence_id;
    }

    /**
     * @since 5.0
     */
    public function get_flags() {
        global $wpdb;

        $flags = trim( $wpdb->get_var( $wpdb->prepare( "SELECT flags FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d", $this->id ) ) );

        if ( ! $flags )
            return array();

        return explode( ',', $flags );
    }

    /**
     * @since 5.0
     */
    public function set_flag( $flag ) {
        global $wpdb;

        $flags = $this->get_flags();

        if ( ! in_array( $flag, $flags, true ) )
            $flags[] = $flag;

        $wpdb->update( $wpdb->prefix . 'wpbdp_listings', array( 'flags' => implode( ',', $flags ) ), array( 'listing_id' => $this->id ) );
    }

    /**
     * @since 5.0
     */
    public function _after_save( $context = '' ) {
        if ( 'submit-new' == $context ) {
            do_action( 'WPBDP_Listing::listing_created', $this->id );
            do_action( 'wpbdp_add_listing', $this->id );
        } elseif ( 'submit-edit' == $context ) {
            do_action( 'wpbdp_edit_listing', $this->id );
            do_action( 'WPBDP_Listing::listing_edited', $this->id );
        }

        do_action( 'wpbdp_save_listing', $this->id, 'submit-new' == $context );

        $this->get_status(); // This forces a status refresh if there's no status.

        // Do not let expired listings be public.
        if ( $this->get_status() && in_array( $this->get_status(), array( 'expired', 'pending_renewal' ) ) && 'publish' == get_post_status( $this->id ) ) {
            $this->set_post_status( 'draft' );
        }
    }

    /**
     * @since 5.0
     */
    public function after_delete( $context = '' ) {
        global $wpdb;

        // Remove attachments.
        $attachments = get_posts( array( 'post_type' => 'attachment', 'post_parent' => $this->id, 'numberposts' => -1, 'fields' => 'ids' ) );
        foreach ( $attachments as $attachment_id )
            wp_delete_attachment( $attachment_id, true );

        // Remove listing fees.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d", $this->id ) );

        // Delete logs.
        $wpdb->delete( $wpdb->prefix . 'wpbdp_logs', array( 'object_type' => 'listing', 'object_id' => $this->id ) );

        // Remove payment information.
        foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d", $this->id ) ) as $payment_id ) {
            $payment = WPBDP_Payment::objects()->get( $payment_id );
            $payment->delete();
        }
    }

    /**
     * @since 5.0
     */
    public static function insert_or_update( $args = array(), $error = false ) {
    }

    public static function get( $id ) {
        if ( WPBDP_POST_TYPE !== get_post_type( $id ) )
            return null;

        $l = new self( $id );
        $l->new = false;
        return $l;
    }
}
