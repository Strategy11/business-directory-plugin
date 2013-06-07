<?php
/*
 * Submit/edit listing view
 */

require_once( WPBDP_PATH . 'api/views.php' );


class WPBDP_SubmitListingPage extends WPBDP_View {
    
    private $state = null;
    private $messages = array();
    private $errors = array();

    public function __construct() {
        $this->state = $this->get_current_state();
    }

    public function get_page_name() {
        return 'submitlisting';
    }

    protected function get_current_state() {
        if ( isset( $_POST['_state'] ) ) {
            $state = unserialize( base64_decode( $_POST['_state'] ) );

            if ( !$state )
                throw new Exception( 'Inconsistent state' );

            return $state;
        }

        return new WPBDP_SubmitState();
    }

    public function dispatch() {
        // Check there are categories available
        if ( count( get_terms(wpbdp_categories_taxonomy(), array( 'hide_empty' => false) ) ) == 0 ) {
            if ( current_user_can( 'administrator' ) ) {
                return wpbdp_render_msg( _x( 'There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that they cannot add their listing at this time. Listings cannot be added until you assign categories to the business directory.', 'templates', 'WPBDM' ), 'error' );
            } else {
                return wpbdp_render_msg( _x( 'Your listing cannot be added at this time. Please try again later.', 'templates', 'WPBDM' ), 'error' ); 
            }
        }

        // Login required?
        if ( wpbdp_get_option( 'require-login' ) && !is_user_logged_in() )
            return wpbdp_render( 'parts/login-required', array(), false );

        // editing && allowed to edit?
        if ( $this->state->listing_id > 0 ) {
            $current_user = wp_get_current_user();

            if ( ( get_post( $this->state->listing_id )->post_author != $current_user->ID ) && ( !current_user_can( 'administrator' ) ) )
                return wpbdp_render_msg( _x( 'You are not authorized to edit this listing.', 'templates', 'WPBDM' ), 'error' );

            if ( wpbdp_payment_status( $this->state->listing_id ) != 'paid' && !current_user_can( 'administrator' ) ) {
                $html  = '';
                $html .= wpbdp_render_msg( _x( 'You can not edit your listing until its payment has been cleared.', 'templates', 'WPBDM' ), 'error' );
                $html .= sprintf( '<a href="%s">%s</a>', get_permalink( $this->state->listing_id ), _x( 'Return to listing.', 'templates', 'WPBDM' ) );
                return $html;                
            }
        }

        $step_content = '';

        switch ( $this->state->step ) {
            case 'fee_selection':
                $step_content = $this->step_fee_selection();
                break;
            case 'listing_fields':
                $step_content = $this->step_listing_fields();
                break;
            case 'images':
                $step_content = $this->step_images();
                break;
            case 'before_save':
                $step_content = $this->step_before_save();
                break;
            case 'save':
                $step_content = $this->step_save();
                break;
            case 'checkout':
                $step_content = $this->step_checkout();
                break;
            case 'category_selection':
            default:
                $step_content = $this->step_category_selection();
                break;
        }

        $html = '';
        $html .= sprintf( '<div id="wpbdp-submit-page" class="wpbdp-submit-page businessdirectory-submit businessdirectory wpbdp-page step-%s">',
                          str_replace( '_', '-', $this->state->step ) );
        $html .= sprintf( '<h2>%s</h2>', $this->state->edit ? _x('Edit Your Listing', 'templates', 'WPBDM') : _x('Submit A Listing', 'templates', 'WPBDM') );

        if ( current_user_can( 'administrator' ) ) {
            if ( $errors = wpbdp_payments_api()->check_config() ) {
                foreach ( $errors as $error ) $html .= wpbdp_render_msg( $error, 'error' );
            }

            $html .= wpbdp_render_msg( _x( 'You are logged in as an administrator. Any payment steps will be skipped.', 'templates', 'WPBDM' ) );
        }

        if ( $this->errors ) {
            foreach ( $this->errors as &$e ) {
                $html .= wpbdp_render_msg( $e, 'error' );
            }
        }

        if ( $this->messages ) {
            foreach ( $this->messages as &$m ) {
                $html .= wpbdp_render_msg( $m );
            }
        }

        $html .= $step_content;
        $html .= '</div>';

        return apply_filters_ref_array( 'wpbdp_view_submit_listing', array( $html, &$state ) );
    }

    protected function render( $template, $args=array() ) {
        return wpbdp_render( 'submit-listing/' . $template,
                             array_merge( array( 'state' => $this->state,
                                                 '_state' => base64_encode( serialize( $this->state ) ) ), $args ),
                             false
                            );
    }

    protected function step_category_selection() {
        // TODO: unset( $_SESSION['wpbdp-submitted-listing-id'] );        
        $category_field = wpbdp_get_form_fields( 'association=category&unique=1' ) or die( '' );

        $post_value = isset( $_POST['listingfields'][ $category_field->get_id() ] ) ?
                      $category_field->convert_input( $_POST['listingfields'][ $category_field->get_id() ] ) :
                      array();

        if ( $post_value ) {
            $errors = null;

            if ( !$category_field->validate( $post_value, $errors ) ) {
                $this->errors = array_merge( $this->errors, $errors );
            } else {
                $this->state->categories = array_values( $post_value );
                return $this->step_fee_selection();
            }

        }

        return $this->render( 'category-selection', array( 'category_field' => $category_field ) );
    }

    protected function step_fee_selection() {
        $this->state->step = 'fee_selection';

        if ( !$this->state->categories )
            return;

        foreach ( $this->state->categories as $cat_id ) {
            $categories[ $cat_id ] = get_term( $cat_id, WPBDP_CATEGORY_TAX );
        }

        // available fees
        $available_fees = wpbdp_get_fees_for_category( $this->state->categories ) or die( '' );

        // TODO: if all fees are free-fees, move on

        if ( isset( $_POST['fees'] ) ) {
            $validates = true;

            foreach ( $categories as $cat_id => &$term ) {
                $selected_fee_id = wpbdp_getv( $_POST['fees'], $cat_id, null );

                if ( $selected_fee_id === null || !isset( $available_fees[ $cat_id ] ) ) {
                    $this->errors[] = sprintf( _x( 'Please select a fee option for the "%s" category.', 'templates', 'WPBDM' ), esc_html( $term->name ) );
                    $validates = false;
                } else {
                    $fee = wpbdp_get_fee( intval( $selected_fee_id ) );
                    $this->state->fees[ $cat_id ] = intval( $fee->id );
                    $this->state->allowed_images += intval( $fee->images );
                }
            }

            if ( $validates ) {
                return $this->step_listing_fields();
            }
 
        }

        return $this->render( 'fee-selection', array(
            'categories' => $this->state->categories,
            'fees' => $available_fees
        ) );
    }

    protected function step_listing_fields() {
        $this->state->step = 'listing_fields';

        $fields = wpbdp_get_form_fields( array( 'association' => '-category' ) );
        $fields = apply_filters_ref_array( 'wpbdp_listing_submit_fields', array( &$fields, &$this->state ) );

        $validation_errors = array();
        if ( isset( $_POST['listingfields'] ) ) {
            $_POST['listingfields'] = stripslashes_deep( $_POST['listingfields'] );
            
            foreach ( $fields as  &$f ) {
                $value = $f->convert_input( wpbdp_getv( $_POST['listingfields'], $f->get_id(), null ) );
                $this->state->fields[ $f->get_id() ] = $value;

                $field_errors = null;
                $validate_res = apply_filters_ref_array( 'wpbdp_listing_submit_validate_field', array(
                                                            $f->validate( $value, $field_errors ),
                                                            &$field_errors,
                                                            &$f,
                                                            $value,
                                                            &$this->state
                                                        ) );

                if ( !$validate_res )
                    $validation_errors = array_merge( $validation_errors, $field_errors );
            }

            if ( wpbdp_get_option('recaptcha-for-submits') ) {
                if ( $private_key = wpbdp_get_option( 'recaptcha-private-key' ) ) {
                    if ( isset( $_POST['recaptcha_challenge_field'] ) ) {
                        require_once( WPBDP_PATH . 'recaptcha/recaptchalib.php' );

                        $resp = recaptcha_check_answer( $private_key, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field'] );
                        if (!$resp->is_valid)
                            $validation_errors[] = _x( "The reCAPTCHA wasn't entered correctly.", 'templates', 'WPBDM' );
                    }
                }
            }

            if ( !$validation_errors ) {
                return $this->step_images();
            }
        }

        $recaptcha = '';
        if ( wpbdp_get_option('recaptcha-for-submits') ) {
            if ( $public_key = wpbdp_get_option( 'recaptcha-public-key' ) ) {
                require_once( WPBDP_PATH . 'recaptcha/recaptchalib.php' );
                $recaptcha = recaptcha_get_html( $public_key );
            }
        }
        
        return $this->render( 'listing-fields',
                              array(
                                    'fields' => $fields,
                                    'validation_errors' => $validation_errors,
                                    'recaptcha' => $recaptcha
                                   )
                            );
    }

    protected function step_images() {
        $this->state->step = 'images';

        if ( !wpbdp_get_option( 'allow-images' ) ) $this->state->allowed_images = 0;

        if ( $this->state->allowed_images == 0 )
            return $this->step_before_save();

        // sanitize $state->images just in case something disappeared (who knows)
        $this->state->images = array_filter( $this->state->images, create_function( '$x', 'return get_post($x) !== null;' ) );        

        if ( isset( $_POST['upload-image'] ) && ( ( $this->state->allowed_images - count( $this->state->images ) - 1 ) >= 0 ) ) {
            if ( $image_file = $_FILES[ 'image' ] ) {
                $image_error = '';

                if ( $attachment_id = wpbdp_media_upload( $image_file,
                                                          true,
                                                          true,
                                                          array( 'image' => true, 'max-size' => intval( wpbdp_get_option( 'image-max-filesize' ) ) * 1024 ),
                                                          $image_error ) ) {
                    $this->state->images[] = $attachment_id;
                } else {
                    $this->errors[] = $image_error;
                }
            }
        } elseif ( isset( $_POST['delete-image'] ) && intval( $_POST['delete-image-id'] ) > 0 ) {
            $attachment_id = intval( $_POST['delete-image-id'] );
            $key = array_search( $attachment_id, $this->state->images );

            if ( $key !== FALSE ) {
                wp_delete_attachment( $attachment_id, true );
                unset( $this->state->images[ $key ] );
                $this->messages[] = _x( 'Image deleted.', 'templates', 'WPBDM' );
            }
        } elseif ( isset( $_POST['finish'] ) ) {
            $thumbnail_id = isset( $_POST['thumbnail_id'] ) ? intval( $_POST['thumbnail_id'] ) : 0;
            $this->state->thumbnail_id = in_array( $thumbnail_id, $this->state->images ) ? $thumbnail_id : 0;
            return $this->step_before_save();
        }

        return $this->render( 'images' );
    }

    protected function step_before_save() {
        $this->state->step = 'before_save';
        // TODO: implement extra_sections here!
        return $this->step_save();
    }

    protected function step_save() {
        $this->state->step = 'save';

        if ( isset( $_SESSION['wpbdp-submitted-listing-id'] ) && $_SESSION['wpbdp-submitted-listing-id'] > 0 ) {
            $listing_id = $_SESSION['wpbdp-submitted-listing-id'];
            return;

            // TODO:
            // return $this->render( 'done', array(
            //     'listing_data' => $this->_listing_data,
            //     'listing' => get_post($listing_id)
            // ), false);
        }

        $res = null;
        if ( $listing_id = wpbdp_save_listing( $this->state, $res ) ) {
            // TODO:
            // $_SESSION['wpbdp-submitted-listing-id'] = $listing_id;

            // TODO: call save() on extra sections
            // if ( $this->_extra_sections ) {
            //     foreach ( $this->_extra_sections as &$section ) {
            //         if ( $section->save )
            //             call_user_func_array( $section->save, array(&$this->_listing_data['extra_sections'][$section->id], $listing_id) );
            //     }
            // }            

            $cost = floatval( $result->listing_cost );

            if ( !current_user_can( 'administrator' ) && ( $cost > 0.0 ) ) {
                $payments = wpbdp_payments_api();
                $payment_page = $payments->render_payment_page( array(
                    'title' => _x( '5 - Checkout', 'templates', 'WPBDM' ),
                    'transaction_id' => $res->transaction_id,
                    'item_text' => _x( 'Pay %1$s listing fee via %2$s', 'templates', 'WPBDM' )  
                ) );

                return $this->render( 'checkout', array(
                    'payment_page' => $payment_page
                ) );
            }
            
            if ( wpbdp_get_option( 'send-email-confirmation' ) ) {
                $message = wpbdp_get_option( 'email-confirmation-message' );
                $message = str_replace( "[listing]", get_the_title( $listing_id ), $message );
                
                $email = new WPBDP_Email();
                $email->subject = "[" . get_option( 'blogname' ) . "] " . wp_kses( get_the_title( $listing_id ), array() );
                $email->to[] = wpbusdirman_get_the_business_email( $listing_id );
                $email->body = $message;
                $email->send();
            }

            return $this->render( 'done', array(
                'listing' => get_post( $listing_id )
            ) );

        } else {
            return wpbdp_render( _x( 'An error occurred while saving your listing. Please try again later.', 'templates', 'WPBDM' ), 'error' );
        }
    }

}

// TODO: use some hashcode or nonce to validate WPBDP_SubmitState wasn't medled with or doesn't come from us
class WPBDP_SubmitState {
    public $step = 'category_selection';
    public $listing_id = 0;
    public $edit = false;

    public $categories = array();
    public $fees = array();

    public $allowed_images = 0;
    public $images = array();
    public $thumbnail = 0;

    public $fields = array();
}

 /* 
    public function submit_listing_before_save() {
        if ( isset($_POST['thumbnail_id']) )
            $this->_listing_data['thumbnail_id'] = intval( $_POST['thumbnail_id'] );

        if ( !$this->_extra_sections )
            return $this->submit_listing_save();

        if ( !isset($this->_listing_data['extra_sections']) )
            $this->_listing_data['extra_sections'] = array();

        $theres_output = false;
        $continue_to_save = true;
        if ( !isset($_POST['do_extra_sections']) )
            $continue_to_save = false;

        foreach ( $this->_extra_sections as &$section ) {
            if ( !isset($this->_listing_data['extra_sections'][$section->id]) )
                $this->_listing_data['extra_sections'][$section->id] = array();

            $process_result = false;

            if ( isset($_POST['do_extra_sections']) && $section->process ) {
                $process_result = call_user_func_array( $section->process, array(&$this->_listing_data['extra_sections'][$section->id], $this->_listing_data['listing_id']) );
                $continue_to_save = $continue_to_save && $process_result;
            }

            if ( !$process_result && $section->display ) {
                $section->_output = call_user_func_array( $section->display, array(&$this->_listing_data['extra_sections'][$section->id], $this->_listing_data['listing_id']) );

                if ( !empty( $section->_output ) && !$theres_output )
                    $theres_output = true;                
            }
        }

        if ( $continue_to_save || !$theres_output ) {
            return $this->submit_listing_save();
        }

        return wpbdp_render('listing-form-extra', array(
                            'listing_data' => $this->_listing_data,
                            'sections' => $this->_extra_sections
                            ), false);

        return $html;
    }
*/