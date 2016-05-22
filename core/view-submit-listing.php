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
                $categories = array();

                foreach ( $post_value as $category_id )
                    $categories[ $category_id ] = isset( $this->state->categories[ $category_id ] ) ? $this->state->categories[ $category_id ] : null;

                $this->state->categories = $categories;

                $this->state->advance();
                return $this->dispatch();
            }

        }

        return $this->render( 'category-selection', array( 'category_field' => $category_field ) );
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

    private function setup_fee_selection() {
        $fee_selection = array();

        foreach ( $this->state->categories as $cat_id => $fee_id ) {
            if ( $this->state->editing ) {
                $fee_selection[ $cat_id ] = array( 'fee_id' => $fee_id );
            } else {
                if ( $term = get_term( $cat_id, WPBDP_CATEGORY_TAX ) ) {
                    if ( $options = wpbdp_get_fees_for_category( $cat_id ) ) {
                        if ( count( $options ) == 1 ) {
                            $fee = reset( $options );
                            $fee_id = $fee->id;
                        } else {
                            $fee_id = isset( $_POST['fees'][ $cat_id ] ) ? $_POST['fees'][ $cat_id ] : $fee_id;
                        }
                    } else {
                        $fee_id = null;
                    }

                    $fee_selection[ $cat_id ] = array( 'fee_id' => $fee_id,
                                                       'term' => $term,
                                                       'options' => $options );
                } else {
                    unset( $this->state->categories[ $cat_id ] );
                }
            }
        }


        return $fee_selection;
    }

    protected function step_fee_selection() {
        global $wpbdp;

        if ( ! $this->state->categories ) {
            die();
        }

        $fee_selection = $this->setup_fee_selection();

        if ( $this->skip_fee_selection( $fee_selection ) ) {
            foreach ( array_keys( $this->state->categories ) as $cat_id )
                $this->state->categories[ $cat_id ] = $fee_selection[ $cat_id ][ 'fee_id' ];

            $this->state->upgrade_to_sticky = false;

            // Auto-renew fees.
            if ( wpbdp_get_option( 'listing-renewal-auto' ) && wpbdp_get_option( 'listing-renewal-auto-dontask' ) && 1 == count( $this->state->categories ) ) {
                $fee = wpbdp_get_fee( end( $this->state->categories ) );

                if ( $fee->amount > 0.0 && $fee->days > 0 )
                    $this->state->autorenew_fees = true;
            }

            $this->state->advance( false );
            return $this->dispatch();
        }

        if ( isset( $_POST['fees'] ) ) {
            $validates = true;

            foreach ( array_keys( $this->state->categories ) as $cat_id) {
                $selected_fee_id = wpbdp_getv( $_POST['fees'], $cat_id, null );

                if ( null === $selected_fee_id ) {
                    $this->errors[] = sprintf( _x( 'Please select a fee option for the "%s" category.', 'templates', 'WPBDM' ), esc_html( $fee_selection[ $cat_id ]['term']->name ) );
                    $validates = false;
                } else {
                    $this->state->categories[ $cat_id ] = $selected_fee_id;
                }
            }

            if ( $validates ) {
                $this->state->upgrade_to_sticky = isset( $_POST['upgrade-listing'] ) && $_POST['upgrade-listing'] == 'upgrade' ? true : false;
                $this->state->autorenew_fees = false;

                if ( isset( $_POST['autorenew_fees'] ) && 'autorenew' == $_POST['autorenew_fees'] && 1 == count( $this->state->categories ) ) {
                    $fee = wpbdp_get_fee( end( $this->state->categories ) );

                    if ( $fee->amount > 0.0 && $fee->days > 0 )
                        $this->state->autorenew_fees = true;
                }

                $this->state->advance();
                return $this->dispatch();
            }
        }

        $upgrade_option = false;
        if ( ! $this->state->editing && wpbdp_get_option( 'featured-on' ) && wpbdp_get_option( 'featured-offer-in-submit' ) ) {
            $upgrade_option = wpbdp_listing_upgrades_api()->get( 'sticky' );
        }

        return $this->render( 'fee-selection', array(
            'fee_selection' => $fee_selection,
            'upgrade_option' => $upgrade_option,
            'allow_recurring' => count( $this->state->categories ) <= 1 && wpbdp_get_option( 'listing-renewal-auto' ) && $wpbdp->payments->check_capability( 'recurring' )
        ) );
    }

    public function preview_listing_fields_form() {
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
            foreach ( $this->state->categories as $cat_id => $fee_id )
                $image_slots += wpbdp_get_fee( $fee_id )->images; // TODO: max() instead of + probably makes more sense here.
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
        $image_min_file_size = size_format( intval( wpbdp_get_option( 'image-min-filesize' ) ) * 1024 );
        $image_max_file_size = size_format( intval( wpbdp_get_option( 'image-max-filesize' ) ) * 1024 );

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

        if ( ! $this->state->editing ) {
            // Generate payment for the listing.
            $payment = new WPBDP_Payment( array( 'listing_id' => $listing->get_id() ) );

            if ( ! $this->state->editing )
                $payment->tag( 'initial' );

            foreach ( $this->state->categories as $cat_id => $fee_id ) {
                $category_info = $listing->get_category_info( $cat_id );

                if ( ! $category_info ) {
                    $fee = wpbdp_get_fee( $fee_id );

                    if ( ! $fee )
                        continue;

                    $payment->add_item( ( ! current_user_can( 'administrator' ) && $this->state->autorenew_fees ) ? 'recurring_fee' : 'fee',
                                        $fee->amount,
                                        sprintf( _x( 'Fee "%s" for category "%s"%s', 'listings', 'WPBDM' ),
                                                 $fee->label,
                                                 wpbdp_get_term_name( $cat_id ),
                                                 $this->state->autorenew_fees ? ( ' ' . _x( '(recurring)', 'listings', 'WPBDM' ) ) : '' ),
                                        array( 'fee_id' => $fee_id, 'fee_days' => $fee->days, 'fee_images' => $fee->images ),
                                        $cat_id,
                                        $fee_id );
                }
            }

            if ( $this->state->upgrade_to_sticky )
                $payment->add_item( 'upgrade',
                                    wpbdp_get_option( 'featured-price' ),
                                    _x( 'Listing upgrade to featured', 'submit', 'WPBDM' ) );

            $payment->set_submit_state_id( $this->state->id );

            if ( current_user_can( 'administrator' ) )
                $payment->set_status( WPBDP_Payment::STATUS_COMPLETED );

            $payment->save();

            $this->state->listing_id = $listing->get_id();
            $this->state->payment_id = $payment->get_id();
        }

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
