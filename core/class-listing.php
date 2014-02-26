<?php
require_once( WPBDP_PATH . 'core/class-payment.php' );

/**
 * @since 3.4
 */
class WPBDP_Listing {

    private $id = 0;

    private function __construct( $id ) {
        $this->id = intval( $id );
    }

    /**
     * Sets the values for listing fields.
     * @param array $values field_id => value associative array.
     * @param boolean $append if TRUE the specified field values are set without clearing the values for the other fields.
     */
    public function set_field_values( $values = array(), $append = false ) {
        $fields = wpbdp_get_form_fields( array( 'association' => array( '-title', '-category' ) ) );

        foreach ( $fields as &$f ) {
            if ( isset( $values[ $f->get_id() ] ) )
                $f->store_value( $this->id, $values[ $f->get_id() ] );
            elseif ( ! $append )
                $f->store_value( $this->id, $f->convert_input( null ) );
        }
    }

    public function get_images( $fields = 'all' ) {
        $attachments = get_posts( array( 'numberposts' => -1, 'post_type' => 'attachment', 'post_parent' => $this->id ));
        $result = array();

        foreach ( $attachments as $attachment ) {
            if ( wp_attachment_is_image( $attachment->ID ) )
                $result[] = $attachment;
        }

        if ( 'ids' === $fields )
            return array_map( create_function( '$x', 'return $x->ID;' ), $result );

        return $result;
    }    

    /**
     * Sets listing images.
     * @param array $images array of image IDs.
     * @param boolean $append TODO: if TRUE images will be appended without clearing previous ones.
     */
    public function set_images( $images = array(), $append = false ) {
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

    public function get_title() {
        return get_the_title( $this->id );
    }

    public function get_id() {
        return $this->id;
    }

    // public function get_category_fee( $category ) {
    //     $category_id = intval( is_object( $category ) ? $category->term_id  : $category );

    //     global $wpdb;
    //     $listing_fee = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d", $this->id, $category_id ) );

    //     if ( $listing_fee ) {
    //         // TODO: Enhance object.
    //     }

    //     return $listing_fee;
    // }

    public function get_category_info( $category ) {
        $category_id = intval( is_object( $category ) ? $category->term_id : $category );
        $categories = $this->get_categories( 'all' );

        if ( isset( $categories[ $category_id ] ) )
            return $categories[ $category_id ];

        return null;
    }

    public function remove_category( $category, $remove_fee = true ) {
        // TODO: maybe delete pending transactions involving this category?
        global $wpdb;

        $category_id = intval( is_object( $category ) ? $category->term_id : $category );

        if ( $remove_fee )
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d",
                                          $this->id,
                                          $category_id ) );

        $listing_terms = wp_get_post_terms( $this->id, WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) );
        wpbdp_array_remove_value( $listing_terms, $category_id );
        wp_set_post_terms( $this->id, $listing_terms, WPBDP_CATEGORY_TAX );
    }

    public function add_category( $category, $fee, $recurring = false, $recurring_data = array() ) {
        global $wpdb;

        $this->remove_category( $category );

        $category_id = intval( is_object( $category ) ? $category->term_id : $category );
        $fee = is_null( $fee ) ? $fee : ( is_object( $fee ) ? $fee : wpbdp_get_fee( $fee ) );

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

    // TODO: if there are pending payments for a category but the category is already approved/not expired correct the information and don't consider it pending.
    public function get_categories( $info = 'current' ) {
        global $wpdb;

        $current_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT category_id FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND (expires_on >= %s OR expires_on IS NULL)",
                                                   $this->id,
                                                   current_time( 'mysql' ) ) );    
        $expired_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT category_id FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND expires_on IS NOT NULL AND expires_on < %s",
                                                   $this->id,
                                                   current_time( 'mysql' ) ) );
        
        // Pending info.
        $pending_payments = $wpdb->get_results( $wpdb->prepare( "SELECT pi.id, pi.rel_id_1 FROM {$wpdb->prefix}wpbdp_payments_items pi INNER JOIN {$wpdb->prefix}wpbdp_payments p ON p.id = pi.payment_id WHERE pi.item_type IN (%s, %s) AND p.status = %s AND p.listing_id = %d",
                                                           'fee', 'recurring_fee',
                                                           'pending',
                                                           $this->id ) );

        $pending = array();
        foreach ( $pending_payments as &$p )
            $pending[ intval( $p->rel_id_1 ) ] = $p->id;
        $pending_ids = array_keys( $pending );

        $category_ids = array();
        switch ( $info ) {
            case 'all':
                $category_ids = array_merge( $current_ids, $expired_ids, $pending_ids );
                break;
            case 'pending':
                $category_ids = $pending_ids;
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
                $category->id = $category_info->term_id;
                $category->name = $category_info->name;
                $category->slug = $category_info->slug;
                $category->term_id = $category_info->term_id;
                $category->term_taxonomy_id = $category_info->term_taxonomy_id;
                $category->status = in_array( $category_id, $pending_ids, true ) ? 'pending' : ( in_array( $category_id, $expired_ids, true ) ? 'expired' : 'ok' );

                switch ( $category->status ) {
                    case 'expired':
                    case 'ok':
                        $fee_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d", $this->id, $category_id ) );
                        
                        if ( ! $fee_info ) {
                            // $this->remove_category( $category_id );
                            continue;
                        }

                        $category->fee_id = intval( $fee_info->fee_id );
                        $category->fee = wpbdp_get_fee( $category->fee_id );
                        $category->fee_days = intval( $fee_info->fee_days );
                        $category->fee_images = intval( $fee_info->fee_images );
                        $category->expires_on = $fee_info->expires_on;
                        $category->expired = ( $category->expires_on && strtotime( $category->expires_on ) < time() ) ? true : false;
                        $category->renewal_id = $fee_info->id;
                        $category->recurring = $fee_info->recurring ? true : false;
                        $category->recurring_id = trim( $fee_info->recurring_id );
                        $category->payment_id = 0;

                        break;

                    case 'pending':
                        $payment_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_payments_items WHERE id = %d", $pending[ $category_id ] ) );
                        $payment_info->data = unserialize( $payment_info->data );

                        $category->fee_id = intval( $payment_info->rel_id_2 );
                        $category->fee = wpbdp_get_fee( $category->fee_id );
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

    public function fix_categories() {
        // TODO: Remove fee information related to categories that no longer exist.

        // Assign a default fee for categories without a fee.
        foreach ( wp_get_post_terms( $this->id, WPBDP_CATEGORY_TAX, 'fields=ids' ) as $category_id ) {
            if ( ! $this->get_category_info( $category_id ) ) {
                $fee_choices = wpbdp_get_fees_for_category( $category_id );
                $this->add_category( $category_id, $fee_choices[0] );
            }
        }
    }

    public function get_total_cost() {
        global $wpdb;
        $cost = floatval( $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d", $this->id ) ) );
        return round( $cost, 2 );
    }

    public function is_published() {
        return 'publish' == get_post_status( $this->id );
    }

    public function get_payment_status() {
        return WPBDP_Payment::find( array( 'listing_id' => $this->id, 'status' => 'pending' ), true ) ? 'pending' : 'ok';
    }

    public function get_latest_payments() {
        return WPBDP_Payment::find( array( 'listing_id' => $this->id, '_order' => '-id', '_limit' => 10 ) );
    }

    public function publish() {
        if ( ! $this->id )
            return;

        wp_update_post( array( 'post_status' => 'publish', 'ID' => $this->id ) );
    }

    public function save() {
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

            case 'new':
                do_action_ref_array( 'wpbdp_create_listing', array( &$this, &$extra ) );
                break;

            default:
                break;
        }
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
        
        return new self( $post_id );
    }

    public static function get( $id ) {
        if ( WPBDP_POST_TYPE !== get_post_type( $id ) )
            return null;

        return new self( $id );
    }

}


//     public function __construct( $data = array() ) {
//         global $wpdb;

//         $this->id = isset( $data['id'] ) ? intval( $data['id'] ) : 0;

//         if ( $this->id > 0 ) {
//             $post = get_post( $this->id );

//             if ( ! $post || $post->post_type != WPBDP_POST_TYPE )
//                 throw new Exception( 'Invalid listing ID.' );

//             $this->post_status = $post->post_status;
            
//             // Obtain fees & categories.
//             $categories = array_merge( wp_get_post_terms( $this->id, WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) ),
//                                        $wpdb->get_col( $wpdb->prepare( "SELECT category_id FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d", $this->id ) )
//                                      );
//             foreach ( $categories as $category_id ) {
//                 if ( $fee_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d", $this->id, $category_id ) ) ) {
//                     $fee_info->fee = (object) unserialize( $fee_info->fee );
//                 } else {
//                     $fee_info = null;
//                     // TODO: handle non existant fees. (should not happen)
//                     // $fee_info = new StdClass();
//                     // $fee_info->fee = wpbdp_get_fee( 0 );
//                     // $fee_info->fee_id = 0;
//                     // $fee_info->category_id = $category_id;
//                     // $fee_info->id = 
//                 }

//                 $this->fees[ $category_id ] = $fee_info;
//             }

//             // Obtain images.
//             $attachments = get_posts( array( 'numberposts' => -1, 'post_type' => 'attachment', 'post_parent' => $this->id, 'fields' => 'ids' ) );
//             foreach ( $attachments as &$a ) {
//                 if ( wp_attachment_is_image( $a ) )
//                     $this->images[] = $a;
//             }

//             // Fill in fields.
//             $fields = wpbdp_get_form_fields( array( 'association' => '-category' ) );
//             foreach ( $fields as &$f )
//                 $this->fields[ $f->get_id() ] = $f->value( $this->id );

//             // TODO: fill in sticky status and thumbnail_id.

//         } else {
//             $this->post_status = isset( $data['post_status'] ) ? $data['post_status'] : wpbdp_get_option( 'new-post-status' );
//         }
//     }

//     public function upgrade( $newlevel = 'sticky' ) {
//         $upgrades_api = wpbdp_listing_upgrades_api();
//         $sticky_info = $upgrades_api->get_info( $this->id );

//         if ( $sticky_info->upgradeable )
//             $upgrades_api->set_sticky( $this->id, $sticky_info->upgrade->id, true );
//     }
    
//     /*
//      * Category-related.
//      */
//     public function save() {
//         global $wpdb;

//         $editing = $this->id > 0 ? true : false;

//         // Obtain listing's title
//         $title = 'Untitled Listing';
//         $title_field = wpbdp_get_form_fields( array( 'association' => 'title', 'unique' => true ) );
//         if ( isset( $this->fields[ $title_field->get_id() ] ) ) {
//             $title = trim( strip_tags( $this->fields[ $title_field->get_id() ] ) );
//         }

//         $post_data = array(
//             'post_title' => $title,
//             'post_status' => $editing ? wpbdp_get_option( 'edit-post-status' ) : 'pending',
//             'post_type' => WPBDP_POST_TYPE
//         );

//         if ( $this->id )
//             $post_data['ID'] = $this->id;

//         $this->id = $this->id ? wp_update_post( $post_data ) : wp_insert_post( $post_data );

//         // Create author user if needed.
//         if ( ! $editing ) {
//             $current_user = wp_get_current_user();

//             if ( $current_user->ID == 0 ) {
//                 if ( wpbdp_get_option( 'require-login' ) ) {
//                     return false;
//                 }

//                 // Create user.
//                 if ( $email_field = wpbdp_get_form_fields( array( 'validator' => 'email', 'unique' => 1 ) ) ) {
//                     $email = $this->fields[ $email_field->get_id() ];
                    
//                     if ( email_exists( $email ) ) {
//                         $post_author = get_user_by_email( $email )->ID;
//                     } else {
//                         $randvalue = wpbdp_generate_password( 5, 2 );
//                         $post_author = wp_insert_user( array(
//                             'display_name' => 'Guest ' . $randvalue,
//                             'user_login' => 'guest_' . $randvalue,
//                             'user_email' => $email,
//                             'user_pass' => wpbdp_generate_password( 7, 2 )
//                         ) );
//                     }

//                     wp_update_post( array( 'ID' => $this->id, 'post_author' => $post_author ) );
//                 }
//             }
//         }

       
//         // Set thumbnail image.
//         if ( $this->thumbnail_id && in_array( $this->thumbnail_id, $this->images, true ) )
//             update_post_meta( $this->id, '_wpbdp[thumbnail_id]', $this->thumbnail_id );
//         else
//             update_post_meta( $this->id, '_wpbdp[thumbnail_id]', $this->images ? $this->images[0] : 0 );

//         // Save categories & fees.
//         if ( $this->fees )
//            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id NOT IN (" . join( ',', array_keys( $this->fees ) ) . ")", $this->id ) );
        
//         wp_set_post_terms( $this->id, array(), WPBDP_CATEGORY_TAX, false );

//         foreach ( $this->fees as $category_id => &$fee_info ) {
//             // TODO: do not set expired categories here.
//             wp_set_post_terms( $this->id, $category_id, WPBDP_CATEGORY_TAX, true );
//         }

//         if ( ! $editing )
//            wp_update_post( array( 'ID' => $this->id, 'post_status' => wpbdp_get_option( 'new-post-status' ) ) );

//         return true;
//     }

// }
