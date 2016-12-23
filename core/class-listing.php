<?php
require_once( WPBDP_PATH . 'core/class-payment.php' );
require_once( WPBDP_PATH . 'core/class-listing-image.php' );

/**
 * @since 3.4
 */
class WPBDP_Listing {

    private $id = 0;
    private $new = true;

    private function __construct( $id ) {
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

    public function get_thumbnail_id() {
        if ( $thumbnail_id = get_post_meta( $this->id, '_wpbdp[thumbnail_id]', true ) ) {
            return intval( $thumbnail_id );
        } else {
            if ( $images = $this->get_images( 'ids' ) ) {
                update_post_meta( $this->id, '_wpbdp[thumbnail_id]', $images[0] );
                return $images[0];
            }
        }

        return 0;
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
        $fee = (array) $fee;
        $days = isset( $fee['days'] ) ? $fee['days'] : $fee['fee_days'];

        if ( 0 == $days )
            return null;

        $expire_time = strtotime( sprintf( '+%d days', $days ), $time );
        return date( 'Y-m-d H:i:s', $expire_time );
    }

    public function get_categories() {
        return wp_get_post_terms( $this->id, WPBDP_CATEGORY_TAX );
    }

    public function set_categories( $categories ) {
        $category_ids = array_map( 'intval', $categories );
        wp_set_post_terms( $this->id, $category_ids, WPBDP_CATEGORY_TAX, false );
    }

    /**
     * @since next-release
     */
    public function cancel_recurring() {
        global $wpdb;
        $wpdb->update( "{$wpdb->prefix}wpbdp_listings",
                       array( 'is_recurring' => 0,
                              'subscription_id' => '' ),
                       array( 'listing_id' => $this->id ) );
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
     * @since next-release
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

    public function get_latest_payments() {
        return WPBDP_Payment::objects()->filter( array( 'listing_id' => $this->id ) )->order_by( '-id' );
    }

    public function publish() {
        if ( ! $this->id )
            return;

        wp_update_post( array( 'post_status' => 'publish', 'ID' => $this->id ) );
    }

    /**
     * @since next-release
     */
    public function send_renewal_notice( $notice = 'expired', $force_resend = false ) {
        static $templates = array(
            'expired' => 'listing-renewal-message',
            'future' => 'renewal-pending-message',
            'reminder' => 'renewal-reminder-message'
        );

        if ( 'auto' == $notice ) {
            $now = (int) current_time( 'timestamp' );
            $exp = (int) strtotime( $this->get_expiration_date() );

            if ( $now >= $exp )
                $notice = 'expired';
            else
                $notice = 'future';
        }

        $already_sent = (int) get_post_meta( $this->id, '_wpbdp_renewal_notice_sent_' . $notice, true );

        if ( $already_sent && ! $force_resend )
            return false;

        $replacements = array(
            'site' => sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'name' ) ),
            'author' => $this->get_author_meta( 'display_name' ),
            'listing' => sprintf( '<a href="%s">%s</a>', $this->get_permalink(), esc_attr( $this->get_title() ) ),
            'expiration' => date_i18n( get_option( 'date_format' ), strtotime( $this->get_expiration_date() ) ),
            'link' => sprintf( '<a href="%1$s">%1$s</a>', $this->get_renewal_url() )
        );

        $email = wpbdp_email_from_template( $templates[ $notice ], $replacements );
        $email->template = 'businessdirectory-email';
        $email->to[] = wpbusdirman_get_the_business_email( $this->id );

        if ( in_array( 'renewal', wpbdp_get_option( 'admin-notifications' ), true ) ) {
            $email->cc[] = get_option( 'admin_email' );

            if ( wpbdp_get_option( 'admin-notifications-cc' ) )
                $email->cc[] = wpbdp_get_option( 'admin-notifications-cc' );
        }

        $res = $email->send();

        if ( $res )
            update_post_meta( $this->id, '_wpbdp_renewal_notice_sent_' . $notice, current_time( 'timestamp' ) );

        return $res;
    }

    /**
     * @since next-release
     */
    public function set_status( $status ) {
        wpbdp_debug_e('set status!');

        // global $wpdb;
        // // XXX: we don't really do much here, for now...
        //
        // switch ( $status ) {
        // case 'expired':
        //     wp_update_post( array( 'ID' => $this->id, 'post_status' => 'draft' ) ); // Change status to draft.
        //
        //     // TODO(next-release): Maybe drop sticky status?.
        //     $wpdb->update( $wpdb->prefix . 'wpbdp_listings_plans', array( 'is_sticky' => 0 ), array( 'listing_id' => $this->id ) );
        //
        //     if ( ! wpbdp_get_option( 'listing-renewal' ) )
        //         break;
        //
        //     $this->send_renewal_notice( 'expired' );
        //     break;
        // }
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
        if ( in_array( $kind, array( 'save', 'edit', 'new' ), true ) )
            $this->save();

        switch ( $kind ) {
            case 'save':
                break;

            case 'edit':
                do_action_ref_array( 'wpbdp_edit_listing', array( &$this, &$extra ) );
                break;

            default:
                break;
        }
    }

    /**
     * @since 3.5.3
     */
    public function get_renewal_hash( $deprecated = 0 ) {
        $hash = base64_encode( 'listing_id=' . $this->id . '&category_id=' . $deprecated );
        return $hash;
    }

    /**
     * @since next-release
     */
    public function renew() {
        $plan = $this->get_fee_plan();

        if ( ! $plan )
            return false;

        $this->set_fee_plan( $plan );
        $this->set_status( 'complete' );
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
     * @since next-release
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
     * @since next-release
     */
    public function has_fee_plan( $fee = false ) {
        $current = $this->get_fee_plan();
        return ( ! $fee && ! empty( $current ) ) || ( $fee && $current && $current->id == $fee );
    }

    /**
     * @since next-release
     */
    public function get_fee_plan() {
        global $wpdb;

        $res = $wpdb->get_row( $wpdb->prepare( "SELECT listing_id, fee_id, fee_price, fee_days, fee_images, expiration_date, is_recurring, is_sticky FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d LIMIT 1", $this->id ) );
        if ( ! $res )
            return false;

        if ( $res->fee_id )
            $fee = WPBDP_Fee_Plan::find( $res->fee_id );
        else
            $fee = null;

        $res->fee = $fee;
        $res->fee_label = $fee ? $fee->label : _x( '(Unavailable Plan)', 'listing', 'WPBDM' );
        $res->expired = $res->expiration_date ? strtotime( $res->expiration_date ) <= current_time( 'timestamp' ) : false;

        return $res;
    }

    /**
     * @since next-release
     */
    public function set_fee_plan( $fee, $recurring = false, $status = 'ok' ) {
        global $wpdb;

        if ( is_null( $fee ) ) {
            $wpdb->update( $wpdb->prefix . 'wpbdp_listings', array( 'fee_id' => 0, 'fee_days' => 0, 'fee_images' => 0, 'is_sticky' => 0, 'expiration_date' => null ), array( 'listing_id' => $this->id ) );
            // $wpdb->delete( $wpdb->prefix . 'wpbdp_listings_plans', array( 'listing_id' => $this->id ) );
            return true;
        }

        $fee = is_numeric( $fee ) ? WPBDP_Fee_Plan::find( $fee ) : $fee;

        if ( ! $fee )
            return false;

        $row =  array( 'listing_id' => $this->id,
                       'fee_id' => $fee->id,
                       'fee_days' => $fee->days,
                       'fee_images' => $fee->images,
                       'fee_price' => $fee->calculate_amount( wp_get_post_terms( $this->id, WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) ) ),
                       'is_recurring' => 0,
                       'is_sticky' => (int) $fee->sticky );

        if ( $expiration = $this->calculate_expiration_date( current_time( 'timestamp' ), $fee ) )
            $row['expiration_date'] = $expiration;

        return $wpdb->update( $wpdb->prefix . 'wpbdp_listings', $row, array( 'listing_id' => $this->id ) );
    }

    /**
     * @since next-release
     */
    public function set_fee_plan_with_payment( $fee, $recurring = false, $status = 'ok' ) {
        $plan1 = $this->get_fee_plan();
        $fee = is_numeric( $fee ) ? WPBDP_Fee_Plan::find( $fee ) : $fee;
        $this->set_fee_plan( $fee, $recurring, $status );
        $plan = $this->get_fee_plan();

        if ( $fee->id == $plan1->fee_id )
            return null;

        $payment = new WPBDP_Payment( array( 'listing_id' => $this->id, 'payment_type' => 'initial' ) );

        $item = array(
            'type' => $plan->is_recurring ? 'recurring_plan' : 'plan',
            'description' => sprintf( _x( 'Plan "%s"', 'listing', 'WPBDM' ), $plan->fee_label ),
            'amount' => $plan->fee_price,
            'fee_id' => $plan->fee_id,
            'fee_days' => $plan->fee_days,
            'fee_images' => $plan->fee_images
        );

        $payment->payment_items[] = $item;
        $payment->save();

        return $payment;
    }

    public function generate_or_retrieve_payment() {
        $plan = $this->get_fee_plan();

        if ( ! $plan || 'pending' != $plan->status )
            return false;

        $existing_payment = WPBDP_Payment::objects()->filter( array( 'listing_id' => $this->id, 'status' => 'pending', 'payment_type' => 'initial' ) )->get();

        if ( $existing_payment )
            return $existing_payment;

        $payment = new WPBDP_Payment( array( 'listing_id' => $this->id, 'payment_type' => 'initial' ) );

        $item = array(
            'type' => $plan->is_recurring ? 'recurring_plan' : 'plan',
            'description' => sprintf( _x( 'Plan "%s"', 'listing', 'WPBDM' ), $plan->fee_label ),
            'amount' => $plan->fee_price,
            'fee_id' => $plan->fee_id,
            'fee_days' => $plan->fee_days,
            'fee_images' => $plan->fee_images
        );

        $payment->payment_items[] = $item;
        $payment->save();

        return $payment;
    }


    /**
     * @since next-release
     */
    public function get_expiration_date() {
        $plan = $this->get_fee_plan();
        return $plan ? $plan->expiration_date : null;
    }

    /**
     * @since next-release
     */
    public function get_expiration_time() {
        return strtotime( $this->get_expiration_date() );
    }

    /**
     * @since next-release
     */
    public function get_status() {
        global $wpdb;

        $status_ = $wpdb->get_var( $wpdb->prepare( "SELECT listing_status FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d", $this->id ) );

        if ( ! $status_ )
            $status = $this->calculate_status();
        else
            $status = $status_;

        $status = apply_filters( 'wpbdp_listing_status', $status, $this->id );

        if ( ! $status_ || $status_ != $status )
            $wpdb->update( $wpdb->prefix . 'wpbdp_listings', array( 'listing_status' => $status ), array( 'listing_id' => $this->id ) );

        return $status;
    }

    /**
     * @since next-release
     */
    public function get_status_label() {
        $stati = self::get_stati();

        return $stati[ $this->get_status() ];
    }

    /**
     * @since next-release
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
     * @since next-release
     */
    public static function get_stati() {
        $stati = array(
            'unknown' => _x( 'Unknown', 'listing status', 'WPBDM' ),
            'legacy' => _x( 'Legacy', 'listing status', 'WPBDM' ),
            'incomplete' => _x( 'Incomplete', 'listing status', 'WPBDM' ),
            'pending_payment' => _x( 'Incomplete', 'listing status', 'WPBDM' ),
            'complete' => _x( 'Complete', 'listing status', 'WPBDM' ),
            'pending_upgrade' => _x( 'Pending Upgrade', 'listing status', 'WPBDM' ),
            'expired' => _x( 'Expired', 'listing status', 'WPBDM' ),
            'pending_renewal' => _x( 'Pending Renewal', 'listing status', 'WPBDM' ),
            'abandoned' => _x( 'Abandoned', 'listing status', 'WPBDM' )
        );
        $stati = apply_filters( 'wpbdp_listing_stati', $stati );

        return $stati;
    }

    /**
     * @since next-release
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
     * @since next-release
     */
    public function _after_save( $context = '' ) {
        $is_new = (bool) ! get_post_meta( $post_id, '_wpbdp[status]', true );

        if ( $is_new ) {
            do_action( 'WPBDP_Listing::listing_created', $post_id );
            do_action( 'wpbdp_add_listing', $post_id );
        } else {
            do_action( 'wpbdp_edit_listing', $post_id );
            do_action( 'WPBDP_Listing::listing_edited', $post_id );
        }

        do_action( 'wpbdp_save_listing', $post_id, $is_new );

        $this->get_status(); // This forces a status refresh if there's no status.
    }

    /**
     * @since next-release
     */
    public function _after_delete( $context = '' ) {
        global $wpdb;

        // Remove attachments.
        $attachments = get_posts( array( 'post_type' => 'attachment', 'post_parent' => $this->id, 'numberposts' => -1, 'fields' => 'ids' ) );
        foreach ( $attachments as $attachment_id )
            wp_delete_attachment( $attachment_id, true );

        // Remove listing fees.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listings WHERE listing_id = %d", $this->id ) );

        // Remove payment information.
        $wpdb->query( $wpdb->prepare( "DELETE pi.* FROM {$wpdb->prefix}wpbdp_payments_items pi WHERE pi.payment_id IN (SELECT p.id FROM {$wpdb->prefix}wpbdp_payments p WHERE p.listing_id = %d)", $this->id ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d", $this->id ) );
    }

    /**
     * @since next-release
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
