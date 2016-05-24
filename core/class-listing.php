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

    /**
     * Sets the values for listing fields.
     * @param array $values field_id => value associative array.
     * @param boolean $append if TRUE the specified field values are set without clearing the values for the other fields.
     */
    public function set_field_values( $values = array(), $append = false ) {
        $fields = wpbdp_get_form_fields( array( 'association' => array( '-category' ) ) );

        foreach ( $fields as &$f ) {
            if ( isset( $values[ $f->get_id() ] ) )
                $f->store_value( $this->id, $values[ $f->get_id() ] );
            elseif ( ! $append )
                $f->store_value( $this->id, $f->convert_input( null ) );
        }

        do_action_ref_array( 'WPBDP_Listing::set_field_values', array( &$this, $values ) );
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


    public function get_category_info( $category ) {
        $category_id = intval( is_object( $category ) ? $category->term_id : $category );
        $categories = $this->get_categories( 'all' );

        if ( isset( $categories[ $category_id ] ) )
            return $categories[ $category_id ];

        return null;
    }

    public function remove_category( $category, $remove_fee = true, $cleanup = false ) {
        global $wpdb;

        $category_id = intval( is_object( $category ) ? $category->term_id : $category );

        if ( $remove_fee )
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d",
                                          $this->id,
                                          $category_id ) );

        $listing_terms = wp_get_post_terms( $this->id, WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) );
        wpbdp_array_remove_value( $listing_terms, $category_id );
        wp_set_post_terms( $this->id, $listing_terms, WPBDP_CATEGORY_TAX );

        if ( $cleanup ) {
            // Remove all payment items related to this category.
            $payment_ids = $wpdb->get_col( $wpdb->prepare( "SELECT p.id FROM {$wpdb->prefix}wpbdp_payments p WHERE p.listing_id = %d AND
                                                            p.status = %s AND
                                                            EXISTS( SELECT 1 FROM {$wpdb->prefix}wpbdp_payments_items pi WHERE pi.payment_id = p.id
                                                            AND pi.item_type IN (%s, %s) AND pi.rel_id_1 = %d)",
                                                           $this->id,
                                                           'pending',
                                                           'fee',
                                                           'recurring_fee',
                                                           $category_id ) );
            foreach ( $payment_ids as $pid ) {
                $payment = WPBDP_Payment::get( $pid );
                $items = $payment->get_items( array( 'item_type' => array( 'fee', 'recurring_fee' ),
                                                     'rel_id_1' => $category_id ) );
                foreach ( $items as &$item ) {
                    $payment->delete_item( $item );
                }

                $payment->save();
            }
        }
    }

    // TODO: if there is 'current' information for the category respect the expiration time left.
    public function add_category( $category, $fee, $recurring = false, $recurring_data = array(), $cleanup = false ) {
        global $wpdb;

        $this->remove_category( $category, true, $cleanup );

        $category_id = intval( is_object( $category ) ? $category->term_id : $category );
        $fee =  ( null === $fee ) ? $fee : ( is_object( $fee ) ? $fee : wpbdp_get_fee( $fee ) );

        if ( is_null( $fee ) || ! $fee || ! term_exists( $category_id ) )
            return;

        $fee = (array) $fee;

        $fee_info = array();
        $fee_info['listing_id'] = $this->id;
        $fee_info['category_id'] = $category_id;
        $fee_info['fee_id'] = intval( isset( $fee['id'] ) ? $fee['id'] : ( isset( $fee['fee_id'] ) ? $fee['fee_id'] : 0 ) );
        $fee_info['fee_days'] = intval( isset( $fee['days'] ) ? $fee['days'] : $fee['fee_days'] );
        $fee_info['fee_images'] = intval( isset( $fee['images'] ) ? $fee['images'] : $fee['fee_images'] );
        $fee_info['recurring'] = $recurring ? 1 : 0;
        $fee_info['sticky'] = $fee['sticky'] ? 1 : 0;

        if ( isset( $recurring_data ) )
            $fee_info['recurring_data'] = serialize( $recurring_data );

        if ( isset( $recurring_data['recurring_id'] ) )
            $fee_info['recurring_id'] = $recurring_data['recurring_id'];

        if ( $expiration_date = $this->calculate_expiration_date( time(), $fee ) )
            $fee_info['expires_on'] = $expiration_date;

        $wpdb->insert( $wpdb->prefix . 'wpbdp_listing_fees', $fee_info );
        wp_set_post_terms( $this->id, array( $category_id ), WPBDP_CATEGORY_TAX, true );
    }


    private function calculate_expiration_date( $time, &$fee ) {
        $days = isset( $fee['days'] ) ? $fee['days'] : $fee['fee_days'];

        if ( 0 == $days )
            return null;

        $expire_time = strtotime( sprintf( '+%d days', $days ), $time );
        return date( 'Y-m-d H:i:s', $expire_time );
    }

    // TODO: what happens when sections clash? i.e. there is a payment pending for a renewal and somehow the category is also in 'expired'
    public function get_categories( $info = 'current' ) {
        global $wpdb;

        $current_ids = array();
        $expired_ids = array();
        $pending_ids = array();
        $known = array();

        foreach ( $wpdb->get_results( $wpdb->prepare(
                    "SELECT *, IF ( expires_on >= %s OR expires_on IS NULL, 0, 1 ) AS _expired FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d",
                    current_time( 'mysql' ),
                    $this->id ) ) as $r ) {
            $known[ $r->category_id ] = $r;

            if ( 1 == $r->_expired )
                $expired_ids[] = $r->category_id;
            else
                $current_ids[] = $r->category_id;
        }

        $pending_payments = $wpdb->get_results( $wpdb->prepare( "SELECT pi.payment_id, pi.id, pi.rel_id_1 FROM {$wpdb->prefix}wpbdp_payments_items pi INNER JOIN {$wpdb->prefix}wpbdp_payments p ON p.id = pi.payment_id WHERE pi.item_type IN (%s, %s) AND p.status = %s AND p.listing_id = %d",
                                                                'fee', 'recurring_fee',
                                                                'pending',
                                                                $this->id ) );

        $pending = array();
        foreach ( $pending_payments as &$p ) {
            $pending[ intval( $p->rel_id_1 ) ] = $p->id;
        }

        $pending_ids = array_keys( $pending );

        $category_ids = array();
        switch ( $info ) {
            case 'all':
                $category_ids = array_merge( $current_ids, $expired_ids, $pending_ids );
                break;
            case 'pending':
                $category_ids = $pending_ids;
                break;
            case 'expired':
                $category_ids = $expired_ids;
                break;
            case 'current':
            default:
                $category_ids = $current_ids;
                break;
        }

        $results = array();

        foreach ( $category_ids as $category_id ) {
            if ( $category_info = get_term( intval( $category_id ), WPBDP_CATEGORY_TAX ) ) {
                $category = new StdClass();
                $category->id = intval( $category_info->term_id );
                $category->name = $category_info->name;
                $category->slug = $category_info->slug;
                $category->term_id = intval( $category_info->term_id );
                $category->term_taxonomy_id = intval( $category_info->term_taxonomy_id );
                $category->status = in_array( $category_id, $pending_ids, true ) ? 'pending' : ( in_array( $category_id, $expired_ids, true ) ? 'expired' : 'ok' );

                switch ( $category->status ) {
                    case 'expired':
                    case 'ok':
                        $fee_info = $known[ $category_id ];
                        //$fee_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d", $this->id, $category_id ) );
                        $fee_info_recurring_data = unserialize( $fee_info->recurring_data );

                        if ( ! $fee_info ) {
                            // $this->remove_category( $category_id );
                            continue;
                        }

                        $category->fee_id = intval( $fee_info->fee_id );
                        $category->fee_days = intval( $fee_info->fee_days );
                        $category->fee_images = intval( $fee_info->fee_images );

                        $category->fee = wpbdp_get_fee( $category->fee_id );
                        if ( ! $category->fee ) {
                            $category->fee = new StdClass();
                            $category->fee->id = $category->fee_id;
                            $category->fee->label = _x( '(Fee Unavailable)', 'listing', 'WPBDM' );
                            $category->fee->amount = 0.0;
                            $category->fee->days = $category->fee_days;
                            $category->fee->images = $category->fee_images;
                            $category->fee->categories = array();
                            $category->fee->extra_data = array();
                        }

                        $category->expires_on = $fee_info->expires_on;
                        $category->expired = ( $category->expires_on && strtotime( $category->expires_on ) < time() ) ? true : false;
                        $category->renewal_id = $fee_info->id;
                        $category->recurring = $fee_info->recurring ? true : false;
                        $category->recurring_id = trim( $fee_info->recurring_id );
                        $category->payment_id = isset( $fee_info_recurring_data['payment_id'] ) ? $fee_info_recurring_data['payment_id'] : 0;

                        break;

                    case 'pending':
                        $payment_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_payments_items WHERE id = %d", $pending[ $category_id ] ) );
                        $payment_info->data = unserialize( $payment_info->data );

                        $category->fee_id = intval( $payment_info->rel_id_2 );
                        $category->fee = wpbdp_get_fee( $category->fee_id );
                        if ( ! $category->fee ) {
                            $category->fee = new StdClass();
                            $category->fee->id = $category->fee_id;
                            $category->fee->label = _x( '(Fee Unavailable)', 'listing', 'WPBDM' );
                            $category->fee->amount = 0.0;
                            $category->fee_days = intval( $payment_info->data['fee_days'] );
                            $category->fee_images = intval( $payment_info->data['fee_images'] );
//                            $category->fee->days = $category->fee_days;
//                            $category->fee->images = $category->fee_images;
                            $category->fee->categories = array();
                            $category->fee->extra_data = array();
                        }

                        $category->fee_days = intval( $payment_info->data['fee_days'] );
                        $category->fee_images = intval( $payment_info->data['fee_images'] );
                        $category->expires_on = null; // TODO: calculate expiration date.
                        $category->expired = false;
                        $category->renewal_id = 0;
                        $category->recurring = ( 'recurring_fee' == $payment_info->item_type ? true : false );
                        $category->recurring_id = '';
                        $category->payment_id = intval( $payment_info->payment_id );

                        break;
                }

                $results[ $category_id ] = $category;
            }
        }

        return $results;
    }

    public function set_categories( $categories ) {
        $category_ids = array_map( 'intval', $categories );

        wp_set_post_terms( $this->id, $category_ids, WPBDP_CATEGORY_TAX, false );
        $this->fix_categories();
    }

    public function fix_categories( $charge = false ) {
        global $wpdb;

        // Delete fee information for categories that no longer exist.
        $wpdb->query( $wpdb->prepare( "DELETE lf FROM {$wpdb->prefix}wpbdp_listing_fees lf WHERE lf.listing_id = %d AND lf.category_id NOT IN (SELECT tt.term_id FROM {$wpdb->term_taxonomy} tt WHERE tt.taxonomy=%s)",
                                      $this->id, WPBDP_CATEGORY_TAX ) );

        $terms = wp_get_post_terms( $this->id, WPBDP_CATEGORY_TAX, 'fields=ids' );

        // Remove listing information for categories that no longer apply to the listing.
        $removed_cats = array_diff( array_keys( $this->get_categories( 'current' ) ), $terms );
        if ( $removed_cats ) {
            $cats = implode( ',', $removed_cats );
            $wpdb->query( $wpdb->prepare( "DELETE lf FROM {$wpdb->prefix}wpbdp_listing_fees lf WHERE lf.listing_id = %d AND lf.category_id IN ({$cats})", $this->id ) );
        }

        // Assign a default fee for categories without a fee.
        foreach ( $terms as $category_id ) {
            $category_info = $this->get_category_info( $category_id );

            if ( $category_info && 'pending' == $category_info->status ) {
                $this->add_category( $category_id, $category_info->fee, false, null, true );
            } elseif ( ! $category_info ) {
                $fee_options = wpbdp_get_fees_for_category( $category_id );

                // Allow backend listing categories editing to always succeed.
                if ( ! $fee_options && is_admin() && current_user_can( 'administrator' ) )
                    $fee_options[] = WPBDP_Fee_Plan::get_free_plan();

                if ( $charge ) {
                    $payment = new WPBDP_Payment( array( 'listing_id' => $this->id ) );
                    $payment->add_category_fee_item( $category_id, reset( $fee_options ) );
                    $payment->set_status( WPBDP_Payment::STATUS_COMPLETED );
                    $payment->save();
                } else {
                    $this->add_category( $category_id, reset( $fee_options ) );
                }
            }
        }
    }

    public function make_category_non_recurring( $category_id ) {
        global $wpdb;
        $wpdb->update( "{$wpdb->prefix}wpbdp_listing_fees",
                       array( 'recurring' => 0,
                              'recurring_id' => null,
                              'recurring_data' => null ),
                       array( 'listing_id' => $this->id, 'category_id' => $category_id ) );
    }

    public function get_total_cost() {
        $cost = 0.0;

        foreach ( $this->get_categories( 'current' ) as $c ) {
            if ( $c->fee )
                $cost += floatval( $c->fee->amount );
        }

        return $cost;
//        global $wpdb;
//        $cost = floatval( $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d", $this->id ) ) );
//        return round( $cost, 2 );
    }

    public function is_published() {
        return 'publish' == get_post_status( $this->id );
    }

    public function get_permalink() {
        if ( ! $this->id )
            return '';

        return get_permalink( $this->id );
    }

    public function get_payment_status() {
        $status = 'ok';

        if ( WPBDP_Payment::find( array( 'listing_id' => $this->id, 'status' => 'pending' ), true ) )
            $status = 'pending';

        return apply_filters( 'WPBDP_Listing::get_payment_status', $status, $this->id );
    }

    public function mark_as_paid() {
        $pending = WPBDP_Payment::find( array( 'listing_id' => $this->id, 'status' => 'pending' ) );
        $ok = true;

        foreach ( $pending as &$p ) {
            if ( $p->has_item_type( 'recurring_fee' ) ) {
                $ok = false;
                continue;
            }

            $p->set_status( WPBDP_Payment::STATUS_COMPLETED, 'admin' );
            $p->save();
        }

        return $ok;
    }

    public function get_latest_payments() {
        return WPBDP_Payment::find( array( 'listing_id' => $this->id, '_order' => '-id', '_limit' => 10 ) );
    }

    public function publish() {
        if ( ! $this->id )
            return;

        wp_update_post( array( 'post_status' => 'publish', 'ID' => $this->id ) );
    }

    public function set_post_status( $status ) {
        if ( ! $this->id )
            return;

        wp_update_post( array( 'post_status' => $status, 'ID' => $this->id ) );
    }

    public function save() {
        if ( $this->new )
            do_action_ref_array( 'WPBDP_Listing::listing_created', array( &$this ) );
        else
            do_action_ref_array( 'WPBDP_Listing::listing_edited', array( &$this ) );

        $this->new = false;
        do_action_ref_array( 'WPBDP_Listing::listing_saved', array( &$this ) );

        // do_action( 'wpbdp_save_listing', $listing_id, $data->fields, $data );
        do_action_ref_array( 'wpbdp_save_listing', array( &$this ) );
    }

    public function delete() {
        global $wpdb;
        $wpdb->update( $wpdb->posts, array( 'post_status' => wpbdp_get_option( 'deleted-status' ) ), array( 'ID' => $this->id ) );
        clean_post_cache( $this->id );
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
    public function get_renewal_hash( $category_id ) {
        $hash = base64_encode( 'listing_id=' . $this->id . '&category_id=' . $category_id );
        return $hash;
    }

    public function get_renewal_url( $category_id ) {
        $hash = $this->get_renewal_hash( $category_id );
        return wpbdp_url( 'renew_listing', urlencode( $hash ) );
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
        $sticky_status = get_post_meta( $this->id, '_wpbdp[sticky]', true );

        if ( $sticky_status )
            return $sticky_status;

        if ( $consider_plans ) {
            global $wpdb;

            $has_sticky_plan = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 AS x FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND sticky = %d", $this->id, 1 ) );

            if ( $has_sticky_plan )
                $sticky_status = 'sticky';
        }

        return $sticky_status ? $sticky_status : 'normal';
    }


    public function update( $state, $opts = array() ) {
        // Set title.
        $title = false;

        if ( isset( $state->title ) ) {
            $title = $state->title;
        } else {
            if ( $title_field = wpbdp_get_form_fields( array( 'association' => 'title', 'unique' => true ) ) ) {
                if ( isset( $state->fields[ $title_field->get_id() ] ) )
                    $title = $state->fields[ $title_field->get_id() ];
            }
        }

        if ( $title )
            $this->set_title( $title );

        // Set categories.
        if ( isset( $state->categories ) ) {
            $this->set_categories( $state->categories );
        }

        if ( isset( $state->fields ) ) {
            $this->set_field_values( $state->fields );
        }

        if ( isset( $state->images ) ) {
            $append = ( ! empty( $opts['append-images'] ) );
            $this->set_images( $state->images, $append );
        }

        $this->save();
    }

    public static function create( &$state ) {
        $title = 'Untitled Listing';

        if ( isset( $state->title ) ) {
            $title = $state->title;
        } else {
            $title_field = wpbdp_get_form_fields( array( 'association' => 'title', 'unique' => true ) );

            if ( isset( $state->fields[ $title_field->get_id() ] ) )
                $title = $state->fields[ $title_field->get_id() ];
        }

        $title = trim( strip_tags( $title ) );

        $post_data = array(
            'post_title' => $title,
            'post_status' => 'pending',
            'post_type' => WPBDP_POST_TYPE
        );

        $post_id = wp_insert_post( $post_data );

        // Create author user if needed.
        $current_user = wp_get_current_user();

        if ( $current_user->ID == 0 ) {
            if ( wpbdp_get_option( 'require-login' ) )
                throw new Exception('Login required.');

            // Create user.
            if ( $email_field = wpbdp_get_form_fields( array( 'validators' => 'email', 'unique' => 1 ) ) ) {
                $email = $state->fields[ $email_field->get_id() ];

                if ( email_exists( $email ) ) {
                    $post_author = get_user_by( 'email', $email );
                    $post_author = $post_author->ID;
                } else {
                    $post_author = wp_insert_user( array(
                        'user_login' => 'guest_' . wp_generate_password( 5, false, false ),
                        'user_email' => $email,
                        'user_pass' => wp_generate_password()
                    ) );
                }

                wp_update_post( array( 'ID' => $post_id, 'post_author' => $post_author ) );
            }
        }

        return new self( $post_id );
    }

    public static function get( $id ) {
        if ( WPBDP_POST_TYPE !== get_post_type( $id ) )
            return null;

        $l = new self( $id );
        $l->new = false;

        return $l;
    }

}
