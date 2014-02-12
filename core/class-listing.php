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

    /**
     * Sets listing images.
     * @param array $images array of image IDs.
     * @param boolean $append TODO: if TRUE images will be appended without clearing previous ones.
     */
    public function set_images( $images = array(), $append = false ) {
        foreach ( $images as $image_id )
            wp_update_post( array( 'ID' => $image_id, 'post_parent' => $this->id ) );
    }

    public function get_id() {
        return $this->id;
    }

    public function get_category_fee( $category ) {
        $category_id = intval( is_object( $category ) ? $category->term_id  : $category );

        global $wpdb;
        $listing_fee = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d", $this->id, $category_id ) );

        if ( $listing_fee ) {
            // TODO: Enhance object.
        }

        return $listing_fee;
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

}


// TODO: make this more 'calculated fields' and immediate action instead of doing stuff in ->save().
// class WPBDP_Listing extends WPBDP_DB_Model {

//     const TABLE_NAME = null;

//     private $fees = array();
//     private $fields = array();
//     private $images = array();
//     private $thumbnail_id = 0;    

//     private $post_status = 'draft';

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

//     public function set_dirty_flag( $flag = true ) {
//         $this->dirty = (bool) $flag;
//     }

//     public static function get( $id ) {
//         return new self( array( 'id' =>  $id ) );
//     }

//     public function upgrade( $newlevel = 'sticky' ) {
//         $upgrades_api = wpbdp_listing_upgrades_api();
//         $sticky_info = $upgrades_api->get_info( $this->id );

//         if ( $sticky_info->upgradeable )
//             $upgrades_api->set_sticky( $this->id, $sticky_info->upgrade->id, true );
//     }

//     /*
//      * Field-related methods.
//      */

//     public function set_field_values( &$fields = array() ) {
//         foreach ( $fields as $field => $value )
//             $this->fields[ $field ] = $value;
//     }

//     /*
//      * Image-related methods.
//      */
    
//     /*
//      * Category-related.
//      */
//     public function assign_fee( $category, $fee, $recurring = false ) {
//         global $wpdb;

//         $category_id = intval( is_object( $category ) ? $category->ID : $category );

//         if ( null !== $fee )
//             $this->fees[ $category_id ] = array( 'fee' => is_object( $fee ) ? $fee : wpbdp_get_fee( $fee ),
//                                                  'recurring' => (bool) $recurring );
//         else
//             unset( $this->fees[ $category_id ] );

//         if ( ! $this->id )
//             return;

//         if ( null !== $fee ) {
//             wp_set_post_terms( $this->id, array( $category_id ), WPBDP_CATEGORY_TAX, true );
//             // TODO:        if ($fee->categories['all'] || count(array_intersect(wpbdp_get_parent_catids($category_id), $fee->categories['categories'])) > 0) {

//             $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d",
//                                           $this->id,
//                                           $category_id ) );

//             $row = array();
//             $row['listing_id'] = $this->id;
//             $row['category_id'] = $category_id;
//             $row['fee'] = serialize( (array) $fee );
//             $row['charged'] = 1;
//             $row['recurring'] = (bool) $recurring;            
            
//             if ( $fee->id )
//                 $row['fee_id'] = $fee->id;

//             if ( $expiration_date = $this->calculate_expiration_date( time(), $fee ) )
//                 $row['expires_on'] = $expiration_date;

//             $wpdb->insert( $wpdb->prefix . 'wpbdp_listing_fees', $row );
//         } else {
//             // Remove categoy from listing.
//             $listing_terms = wp_get_post_terms( $this->id, WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) );
//             wpbdp_array_remove_value( $listing_terms, $category_id );
//             wp_set_post_terms( $this->id, $listing_terms, WPBDP_CATEGORY_TAX );
//         }
//     }

//     public function calculate_expiration_time($time, $fee) {
//         if ($fee->days == 0)
//             return null;

//         $expire_time = strtotime(sprintf('+%d days', $fee->days), $time);
//         return $expire_time;
//     }
//             // $start_time = get_post_time('U', false, $listing_id);

//     public function calculate_expiration_date($time, $fee) {
//         if ($expire_time = $this->calculate_expiration_time($time, $fee))
//             return date('Y-m-d H:i:s', $expire_time);
        
//         return null;
//     }    

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

//        // TODO:
//         // if ( !$editing ) {
//         //     do_action( 'wpbdp_create_listing', $listing_id, $data->fields, $data );
//         //     do_action( 'wpbdp_create_listing', $listing_id, $this->fields, (array) $this );
//         // }
//         // else
//         //     do_action( 'wpbdp_edit_listing', $listing_id, $data->fields, $data );

//         // do_action( 'wpbdp_save_listing', $listing_id, $data->fields, $data );       

//         return true;
//     }

//     public function delete() { }

//     public function get_fees() {
//         return $this->fees;
//     }

//     public function get_categories() {
//         return array_keys( $this->fees );
//     }

//     public function is_published() {
//         return 'publish' == $this->post_status;
//     }

//     public function get_total_cost() {
//         global $wpdb;
//         $cost = floatval( $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d", $this->id ) ) );
//         return round( $cost, 2 );
//     }


// }
