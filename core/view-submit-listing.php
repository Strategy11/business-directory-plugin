<?php
require_once( WPBDP_PATH . 'core/class-view.php' );

/**
 * Submit/edit listing process page.
 * @since 3.3
 */
class WPBDP_Submit_Listing_Page extends WPBDP_View {

    private $state = null;
    private $messages = array();
    private $errors = array();

    public function __construct( $listing_id = 0, $preview = false ) {
        $this->state = isset( $_REQUEST['_state'] ) && $_REQUEST['_state'] ? WPBDP_Listing_Submit_State::get( $_REQUEST['_state'] ) : new WPBDP_Listing_Submit_State( $listing_id );

        if ( ! $preview )
            $this->state->save();
    }

    public function get_page_name() {
        return 'submitlisting';
    }

    public function dispatch() {
        // Check there are categories available
        if ( count( get_terms(WPBDP_CATEGORY_TAX, array( 'hide_empty' => false) ) ) == 0 ) {
            if ( current_user_can( 'administrator' ) ) {
                return wpbdp_render_msg( _x( 'There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that they cannot add their listing at this time. Listings cannot be added until you assign categories to the business directory.', 'templates', 'WPBDM' ), 'error' );
            } else {
                return wpbdp_render_msg( _x( 'Your listing cannot be added at this time. Please try again later. If this is not the first time you see this warning, please ask the site administrator to set up one or more categories inside the Directory.', 'templates', 'WPBDM' ), 'error' );
            }
        }

        // Login required?
        if ( wpbdp_get_option( 'require-login' ) && !is_user_logged_in() )
            return wpbdp_render( 'parts/login-required', array(), false );

        if ( $this->state->editing )  {
            $current_user = wp_get_current_user();

            if ( ( get_post( $this->state->listing_id )->post_author != $current_user->ID ) && ( !current_user_can( 'administrator' ) ) )
                return wpbdp_render_msg( _x( 'You are not authorized to edit this listing.', 'templates', 'WPBDM' ), 'error' );
        }

        $callback = 'step_' . $this->state->step;

        if ( method_exists( $this, $callback ) )
            return call_user_func( array( &$this, $callback) );
        else
            return 'STEP NOT IMPLEMENTED YET: ' . $this->state->get_step();
    }

    protected function render( $template, $args = array(), $skipouter = false, $is_html = false ) {
        $html = '';
        $html .= sprintf( '<div id="wpbdp-submit-page" class="wpbdp-submit-page businessdirectory-submit businessdirectory wpbdp-page step-%s">',
                          str_replace( '_', '-', $this->state->step ) );
        $html .= sprintf( '<h2>%s</h2>', $this->state->editing ? _x( 'Edit Your Listing', 'templates', 'WPBDM' ) : _x( 'Submit A Listing', 'templates', 'WPBDM' ) );

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

        $listing_instructions = wpbdp_get_option( 'submit-instructions' );
        if( $template == 'category-selection' && !empty( $listing_instructions ) ){
            $html .= wpbdp_render_msg( $listing_instructions );
        }

        if ( ! $is_html )
            $content = wpbdp_render( 'submit-listing/' . $template,
                                     array_merge( array( '_state' => $this->state ), $args ),
                                     false );
        else
            $content = $template;

        $html .= $content;
        $html .= '</div>';

        return apply_filters_ref_array( 'wpbdp_view_submit_listing', array( $html, &$this->state ) );
    }

    protected function step_category_selection() {
        if ( $this->state->editing ) {
            $this->state->advance( false );
            return $this->dispatch();
        }

        $category_field = wpbdp_get_form_fields( 'association=category&unique=1' ) or die( '' );

        $post_value = isset( $_POST['listingfields'][ $category_field->get_id() ] ) ?
                      $category_field->convert_input( $_POST['listingfields'][ $category_field->get_id() ] ) :
                      array();
        if ( $post_value && ! is_array( $post_value ) )
            $post_value = array( $post_value );

        if ( $post_value ) {
            $errors = null;

            if ( ! $category_field->validate( $post_value, $errors ) ) {
                $this->errors = array_merge( $this->errors, $errors );
            } else {
                $this->state->categories = $post_value;
                // $categories = array();
                //
                // foreach ( $post_value as $category_id )
                //     $categories[ $category_id ] = isset( $this->state->categories[ $category_id ] ) ? $this->state->categories[ $category_id ] : null;

                $this->state->advance();
                return $this->dispatch();
            }

        }

        $plans = WPBDP_Fee_Plan::find( 'all' );
        unset($plans[1]);
        return $this->render( 'category-selection', array( 'category_field' => $category_field, 'plans' => $plans ) );
    }

    private function skip_fee_selection( &$fee_selection ) {
        if ( $this->state->editing )
            return true;

        $skip = true;
        foreach ( $fee_selection as $fs ) {
            if ( ! $fs['options'] || count( $fs['options'] ) > 1 ) {
                $skip = false;
                break;
            }
        }

        if ( wpbdp_get_option( 'featured-on' ) && wpbdp_get_option( 'featured-offer-in-submit' ) )
            $skip = false;

        if ( wpbdp_get_option( 'listing-renewal-auto' ) && ! wpbdp_get_option( 'listing-renewal-auto-dontask' ) )
            $skip = false;

        return $skip;
    }

    protected function step_fee_selection() {
        global $wpbdp;

        // FIXME: change this to the correct selection before next-release.
        $plans = WPBDP_Fee_Plan::find( 'all' );

        if ( isset( $_POST['listing_plan'] ) ) {
            $fee = WPBDP_Fee_Plan::find( absint( $_POST['listing_plan'] ) );

            if ( ! $fee ) {
                $this->errors[] = _x( 'Please select a fee plan.', 'templates', 'WPBDM' );
            } else {
                $this->state->fee_id = $fee->id;
                $this->state->advance( false );

                if ( wpbdp_get_option( 'listing-renewal-auto' ) )
                    $this->state->autorenew_fees = ( wpbdp_get_option( 'listing-renewal-auto-dontask' ) || ( ! empty( $_POST['autorenew_fees'] ) && 'autorenew' == $_POST['autorenew_fees'] ) );

                return $this->dispatch();
            }
        }

        // FIXME(next-release): not sure what we're going to do with this since featured levels are to be removed...
        // $upgrade_option = false;
        // if ( ! $this->state->editing && wpbdp_get_option( 'featured-on' ) && wpbdp_get_option( 'featured-offer-in-submit' ) ) {
        //     $upgrade_option = wpbdp_listing_upgrades_api()->get( 'sticky' );
        // }

        return $this->render( 'fee-selection',
                              array( 'plans' => $plans,
                                     'allow_recurring' => wpbdp_get_option( 'listing-renewal-auto' ) && $wpbdp->payments->check_capability( 'recurring' ) ) );
    }

    public function preview_listing_fields_form( $preview_config = array() ) {
        $preview_config = wp_parse_args( $preview_config,
                                         array( 'fee' => 0,
                                                'level' => 'normal' ) );
        $preview_config = apply_filters( 'wpbdp_view_submit_listing_preview_config', $preview_config );

        $this->state->step = 'listing_fields';
        $this->state->step_number = 3;
        $this->state->categories = array( $preview_config['fee'] );
        $this->state->featured_level  = $preview_config['level'];

        return $this->step_listing_fields();
    }

    protected function step_listing_fields() {
        $fields = wpbdp_get_form_fields( array( 'association' => '-category' ) );
        $fields = apply_filters_ref_array( 'wpbdp_listing_submit_fields', array( &$fields, &$this->state ) );

        $validation_errors = array();
        if ( isset( $_POST['listingfields'] ) && isset( $_POST['step'] ) && 'listing_fields' == $_POST['step']  ) {
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

            if ( ! $this->state->editing && !current_user_can( 'administrator' ) && wpbdp_get_option( 'display-terms-and-conditions' ) ) {
                $tos = trim( wpbdp_get_option( 'terms-and-conditions' ) );

                if ( $tos && ( !isset( $_POST['terms-and-conditions-agreement'] ) || $_POST['terms-and-conditions-agreement'] != 1 ) ) {
                    $validation_errors[] = _x( 'Please agree to the Terms and Conditions.', 'templates', 'WPBDM' );
                }
            }

            if ( wpbdp_get_option( 'recaptcha-for-submits' ) ) {
                if ( ! wpbdp_recaptcha_check_answer() )
                    $validation_errors[] = _x( "The reCAPTCHA wasn't entered correctly.", 'templates', 'WPBDM' );
            }

            if ( !$validation_errors ) {
                $this->state->advance();
                return $this->dispatch();
            }
        }

        $terms_field = '';
        if ( ! $this->state->editing /*&& !current_user_can( 'administrator' ) */&& wpbdp_get_option( 'display-terms-and-conditions' ) ) {
            $tos = trim( wpbdp_get_option( 'terms-and-conditions' ) );

            if ( $tos ) {
                if ( wpbdp_starts_with( $tos, 'http://', false ) || wpbdp_starts_with( $tos, 'https://', false ) ) {
                    $terms_field .= sprintf( '<a href="%s" target="_blank">%s</a>',
                                             esc_url( $tos ),
                                             _x( 'Read our Terms and Conditions', 'templates', 'WPBDM' )
                                           );
                } else {
                    $terms_field .= '<div class="wpbdp-form-field-label">';
                    $terms_field .= '<label>';
                    $terms_field .= _x( 'Terms and Conditions:', 'templates', 'WPBDM' );
                    $terms_field .= '</label>';
                    $terms_field .= '</div>';
                    $terms_field .= '<div class="wpbdp-form-field-html wpbdp-form-field-inner">';
                    $terms_field .= sprintf( '<textarea readonly="readonly" rows="5" cols="50">%s</textarea>',
                                             esc_textarea( $tos ) );
                    $terms_field .= '</div>';
                }

                $terms_field .= '<label>';
                $terms_field .= '<input type="checkbox" name="terms-and-conditions-agreement" value="1" />';
                $terms_field .= _x( 'I agree to the Terms and Conditions', 'templates', 'WPBDM' );
                $terms_field .= '</label>';
            }
        }

        $recaptcha = '';
        if ( wpbdp_get_option('recaptcha-for-submits') ) {
            $recaptcha = wpbdp_recaptcha();
        }

        return $this->render( 'listing-fields',
                              array(
                                    'fields' => $fields,
                                    'validation_errors' => $validation_errors,
                                    'recaptcha' => $recaptcha,
                                    'terms_and_conditions' => $terms_field
                                   )
                            );
    }

    private function sort_images( $images_, $meta ) {
        // Sort inside $meta first.
        uasort( $meta, create_function( '$x, $y', "return \$y['order'] - \$x['order'];" ) );

        // Sort $images_ considering $meta.
        $images = array();

        foreach ( array_keys( $meta ) as $img_id ) {
            if ( in_array( $img_id, $images_, true ) )
                $images[] = $img_id;
        }

        foreach ( $images_ as $img_id ) {
            if ( in_array( $img_id, $images, true ) )
                continue;

            $images[] = $img_id;
        }

        return $images;
    }

    protected function step_images() {
        // Calculate image slots this listing can use.
        $image_slots = 0;

        if ( wpbdp_get_option( 'allow-images' ) ) {
            $fee = wpbdp_get_fee( $this->state->fee_id );
            $image_slots = $fee->images;
        }

        // Move on if there are no slots.
        if ( 0 == $image_slots ) {
            $this->state->advance( false );
            return $this->dispatch();
        }

        $images_meta = $this->state->images_meta;
        $images = $this->sort_images( $this->state->images, $images_meta );

        $thumbnail_id = $this->state->thumbnail_id;
        $image_slots_remaining = $image_slots - count( $images );

        $image_min_file_size = intval( wpbdp_get_option( 'image-min-filesize' ) );
        $image_min_file_size = $image_min_file_size ? size_format( $image_min_file_size * 1024 ) : '0';

        $image_max_file_size = intval( wpbdp_get_option( 'image-max-filesize' ) );
        $image_max_file_size = $image_max_file_size ? size_format( $image_max_file_size * 1024 ) : '0';

        $image_min_width = intval( wpbdp_get_option( 'image-min-width' ) );
        $image_max_width = intval( wpbdp_get_option( 'image-max-width' ) );
        $image_min_height = intval( wpbdp_get_option( 'image-min-height' ) );
        $image_max_height = intval( wpbdp_get_option( 'image-max-height' ) );

        // Set thumbnail.
        $thumbnail_id = isset( $_POST['thumbnail_id'] ) ? intval( $_POST['thumbnail_id'] ) : $this->state->thumbnail_id;
        $this->state->thumbnail_id = $thumbnail_id;

        if ( isset( $_POST['finish'] ) ) {
            foreach ( $this->state->images as $img_id ) {
                $img_meta = isset( $_POST['images_meta'][ $img_id ] ) ? (array) $_POST['images_meta'][ $img_id ] : array();

                $this->state->images_meta[ $img_id ]['order'] = ( ! empty( $img_meta['order'] ) ) ? intval( $img_meta['order'] ) : 0;
                $this->state->images_meta[ $img_id ]['caption'] = ( ! empty( $img_meta['caption'] ) ) ? strval( $img_meta['caption'] ) : '';
            }

            $this->state->advance();
            return $this->dispatch();
        }

        return $this->render( 'images',
                              compact( 'image_max_file_size',
                                       'image_min_file_size',
                                       'image_min_width',
                                       'image_max_width',
                                       'image_min_height',
                                       'image_max_height',
                                       'images',
                                       'images_meta',
                                       'image_slots',
                                       'image_slots_remaining' )
                            );
    }

    protected function step_before_save() {
        if ( isset( $_POST['continue-with-save'] ) ) {
            $this->state->advance();
            return $this->dispatch();
        }

        $extra = wpbdp_capture_action_array( 'wpbdp_listing_form_extra_sections', array( &$this->state ) );
        $this->state->save(); // Save state in case extra sections modified it.

        if ( !$extra ) {
            $this->state->advance( false );
            return $this->dispatch();
        }

        return $this->render( 'extra-sections', array( 'output' => $extra ) );
    }

    protected function step_save() {
        $listing = $this->state->editing ? WPBDP_Listing::get( $this->state->listing_id ) : WPBDP_Listing::create( $this->state );
        $listing->set_field_values( $this->state->fields );
        $listing->set_images( $this->state->images );
        $listing->set_thumbnail_id( $this->state->thumbnail_id );

        foreach ( $this->state->images_meta as $img_id => $img_meta ) {
            update_post_meta( $img_id, '_wpbdp_image_weight', $img_meta[ 'order' ] );
            update_post_meta( $img_id, '_wpbdp_image_caption', $img_meta[ 'caption' ] );
        }

        // Save categories.
        wp_set_post_terms( $listing->get_id(), $this->state->categories, WPBDP_CATEGORY_TAX, false );

        // Assign fee plan.
        $fee = wpbdp_get_fee( $this->state->fee_id );
        if ( ! $fee )
            $fee = WPBDP_Fee_Plan::get_free_plan();

        if ( ! $this->state->editing ) {
            $payment = $listing->set_fee_with_payment( $fee, ( ! current_user_can( 'administrator' ) && $this->state->autorenew_fees ) );
            $payment->tag( 'initial' );
            $payment->set_submit_state_id( $this->state->id );

            if ( current_user_can( 'administrator' ) )
                $payment->set_status( WPBDP_Payment::STATUS_COMPLETED );

            $payment->save();

            $this->state->listing_id = $listing->get_id();
            $this->state->payment_id = $payment->get_id();
        }

        // if ( $this->state->upgrade_to_sticky )
        //     $payment->add_item( 'upgrade',
        //                         wpbdp_get_option( 'featured-price' ),
        //                         _x( 'Listing upgrade to featured', 'submit', 'WPBDM' ) );
        // }

        do_action_ref_array( 'wpbdp_listing_form_extra_sections_save', array( &$this->state ) );

        $listing->save();
        $listing->set_post_status( $this->state->editing ? wpbdp_get_option( 'edit-post-status' ) : wpbdp_get_option( 'new-post-status' ) );

        $this->state->advance( false ); // This step is 'invisible'.
        return $this->dispatch();
    }

    protected function step_checkout() {
        global $wpbdp;

        if ( $this->state->editing ) {
            $this->state->advance( false );
            return $this->dispatch();
        }

        $payment = WPBDP_Payment::get( $this->state->payment_id );

        if ( ! $payment )
            return wpbdp_render_msg( _x( 'Invalid submit state.', 'submit_state', 'WPBDM' ), 'error' );

        if ( $payment->is_completed() ) {
            $this->state->advance( false );
            return $this->dispatch();
        }

        $checkout = wpbdp_load_view( 'checkout', $payment );
        return $checkout->dispatch();
    }

    protected function step_confirmation() {
        $listing = WPBDP_Listing::get( $this->state->listing_id );
        $listing->notify( $this->state->editing ? 'edit' : 'new', $this->state );
        return $this->render( 'done' );
    }

}

/**
 * Lightweight object used to store the submit process advance.
 * @since 3.3
 */
class WPBDP_Listing_Submit_State {

    public static $STEPS = array( 'category_selection',
                                  'fee_selection',
                                  'listing_fields',
                                  'images',
                                  'before_save',
                                  'save',
                                  'checkout',
                                  'confirmation' );

    public $id = '';
    public $listing_id = 0;

    public $step_number = 1;
    public $step = 'category_selection';

    public $fields = array();
    public $categories = array();
    public $fee_id = 0;
    public $autorenew_fees = false;
    public $upgrade_to_sticky = false;
    public $extra = array();
    public $editing = false;

    public $images = array();
    public $images_meta = array();
    public $thumbnail_id = 0;


    public function __construct( $listing_id = 0 ) {
        $this->editing = $listing_id > 0 ? true : false;

        if ( $listing_id > 0 ) {
            $listing = WPBDP_Listing::get( $listing_id );

            if ( ! $listing || ! $listing->is_published() )
                throw new Exception( 'You can not edit this listing.' );

            $this->listing_id = $listing_id;

            $categories = $listing->get_categories( 'all' );
            foreach ( $categories as &$category )
                $this->categories[ $category->id ] = $category->fee_id;

            // Image information.
            $this->images = $listing->get_images( 'ids' );
            $this->images_meta = $listing->get_images_meta();
            $this->thumbnail_id = $listing->get_thumbnail_id();

            // Fields.
            $fields = wpbdp_get_form_fields( array( 'association' => '-category' ) );
            foreach ( $fields as &$f ) {
                $this->fields[ $f->get_id() ] = $f->value( $this->listing_id );
            }

            // Recover additional information.
            do_action_ref_array( 'wpbdp_submit_state_init', array( &$this ) );
        }
    }

    public static function &get( $id ) {
        global $wpdb;

        $state = wp_cache_get( $id, 'wpbdp submit state' );

        if ( ! $state ) {
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_submit_state WHERE id = %s", $id ) );

            if ( ! $row )
                return null;

            $state = unserialize( $row->state );

            $obj = new self;
            foreach ( $state as $k => &$v )
                $obj->{$k} = $v;

            $state = $obj;
            wp_cache_set( $id, $state, 'wpbdp submit state' );
        }

        return $state;
    }

    public function save() {
        global $wpdb;

        $this->id = $this->id ? $this->id : md5( microtime() . rand() . wp_salt() );
        $data = array( 'id' => $this->id,
                       'state' => serialize( (array) $this ),
                       'updated_on' => current_time( 'mysql' ) );
        $wpdb->replace( $wpdb->prefix . 'wpbdp_submit_state', $data );
    }

    public function advance( $increase_step_number = true ) {
        $current_step = $this->step;

        if ( 'confirmation' == $current_step )
            return;

        $current_index = array_search( $this->step, self::$STEPS );
        $this->step = self::$STEPS[ ++$current_index ];

        if ( $increase_step_number )
            $this->step_number++;

        $this->save();
    }
}
