<?php

class WPBDP__Views__Submit_Listing extends WPBDP_NView {

    private $listing = null;
    private $sections = array();

    private $prevent_save = false;
    private $editing = false;
    private $messages = array( 'general' => array() );


    public function get_title() {
        return _x( 'Submit A Listing', 'views', 'WPBDM' );
    }

    public function enqueue_resources() {
        wp_enqueue_style( 'dashicons' );

        wp_enqueue_style( 'select2-css', WPBDP_URL . 'vendors/select2-4.0.3/css/select2.min.css' );
        wp_register_script( 'select2', WPBDP_URL . 'vendors/select2-4.0.3/js/select2.full.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'wpbdp-submit-listing', WPBDP_URL . 'core/js/submit-listing.min.js', array( 'jquery-ui-sortable', 'select2' ) );
    }

    public function dispatch() {
        $msg = '';
        if ( ! $this->can_submit( $msg ) )
            return wpbdp_render_msg( $msg );

        $this->listing = $this->find_or_create_listing();

        if ( ! $this->editing && 'auto-draft' != get_post_status( $this->listing->get_id() ) ) {
            $possible_payment = WPBDP_Payment::objects()->filter( array( 'listing_id' => $this->listing->get_id(), 'payment_type' => 'initial', 'status' => 'pending' ) )->get();

            if ( $possible_payment )
                return $this->_redirect( $possible_payment->get_checkout_url() );
            else
                return $this->done();
        }

        $this->sections = $this->submit_sections();
        $this->prepare_sections();

        if ( ! empty( $_POST['save_listing'] ) && 1 == $_POST['save_listing'] && ! $this->prevent_save )
            return $this->save_listing();

        // Prepare $messages for template.
        $messages = array();
        foreach ( $this->messages as $context => $items ) {
            $messages[ $context ] = '';

            foreach ( $items as $i )
                $messages[ $context ] .= sprintf( '<div class="wpbdp-msg %s">%s</div>', $i[1], $i[0] );
        }

        $html = wpbdp_render( 'submit-listing',
                              array( 'listing' => $this->listing,
                                     'sections' => $this->sections,
                                     'messages' => $messages ),
                              false );
        return $html;
    }

    public function ajax_reset_plan() {
        $res = new WPBDP_Ajax_Response();

        if ( ! $this->can_submit( $msg ) || empty( $_POST['listing_id'] ) )
            wp_die();

        $this->listing = $this->find_or_create_listing();

        if ( ! $this->listing->has_fee_plan() )
            wp_die();

        $this->listing->clear_fee_plan();
        $this->ajax_sections();
    }

    public function ajax_sections() {
        $res = new WPBDP_Ajax_Response();

        if ( ! $this->can_submit( $msg ) || empty( $_POST['listing_id'] ) )
            $res->send_error( $msg );

        $this->listing = $this->find_or_create_listing();
        $this->sections = $this->submit_sections();
        $this->prepare_sections();

        $sections = array();
        foreach ( $this->sections as $section ) {
            $sections[ $section['id'] ] = $section;
            $sections[ $section['id'] ]['html'] = wpbdp_render( 'submit-listing-section', array( 'section' => $section, 'messages' => $messages ) );
        }

        $res->add( 'listing_id', $this->listing->get_id() );
        $res->add( 'messages', $this->messages );
        $res->add( 'sections', $sections );
        $res->send();
    }

    private function messages( $msg, $type = 'notice', $context = 'general' ) {
        if ( ! isset( $this->messages[ $context ] ) )
            $this->messages[ $context ] = array();

        foreach ( (array) $msg as $msg_ ) {
            $this->messages[ $context ][] = array( $msg_, $type );
        }

        if ( isset( $this->sections[ $context ] ) ) {
            $this->sections[ $context ]['flags'][] = 'has-message';

            if ( 'error' == $type )
                $this->sections[ $context ]['flags'][] = 'has-error';
        }
    }

    private function can_submit( &$msg = null ) {
        if ( 'submit_listing' == wpbdp_current_view() && wpbdp_get_option( 'disable-submit-listing' ) ) {
            if ( current_user_can( 'administrator' ) )
                $msg = _x( '<b>View not available</b>. Do you have the "Disable Frontend Listing Submission?" setting checked?', 'templates', 'WPBDM' );
            else
                $msg = _x( 'View not available.', 'templates', 'WPBDM' );

            return false;
        }

        return true;
    }

    private function find_or_create_listing() {
        $listing_id = 0;

        if ( ! empty( $_REQUEST['listing_id'] ) && false != get_post_status( $_REQUEST['listing_id'] ) ) {
            $listing_id = absint( $_POST['listing_id'] );
        } else {
            $listing_id = wp_insert_post( array( 'post_type' => WPBDP_POST_TYPE, 'post_status' => 'auto-draft', 'post_title' => 'Incomplete Listing' ) );
        }

        if ( ! $listing_id )
            die();

        $listing = WPBDP_Listing::get( $listing_id );
        return $listing;
    }

    private function submit_sections() {
        $sections = array();
        $sections['plan_selection'] = array(
            'title' => _x( 'Category & plan selection', 'submit listing', 'WPBDM' )
        );
        $sections['listing_fields'] = array(
            'title' => _x( 'Listing Information', 'submit listing', 'WPBDM' ) );
        $sections['listing_images'] = array(
            'title' => _x( 'Listing Images', 'submit listing', 'WPBDM' ) );

        if ( wpbdp_get_option( 'display-terms-and-conditions' ) ) {
            $sections['terms_and_conditions'] = array(
                'title' => _x( 'Terms and Conditions', 'submit listing', 'WPBDM' )
            );
        }

        foreach ( $sections as $section_id => &$s ) {
            $s['id'] = $section_id;
            $s['html'] = '';
            $s['flags'] = array( 'enabled' );
        }

        return $sections;
    }

    private function prepare_sections() {
        foreach ( $this->sections as &$section ) {
            $callback = WPBDP_Utils::normalize( $section['id'] );

            if ( ! $this->listing->has_fee_plan() && 'plan_selection' != $section['id'] ) {
                $section['flags'][] = 'collapsed';
                $section['flags'][] = 'disabled';
                $section['html'] = '(Choose a plan)';
                $section['state'] = 'disabled';
                continue;
            }

            if ( method_exists( $this, $callback ) ) {
                $res = call_user_func( array( $this, $callback ) );
                $html = ''; 
                $enabled = false;

                if ( is_array( $res ) ) {
                    $enabled = $res[0];
                    $html = $res[1];
                } elseif ( is_string( $res ) && ! empty( $res ) ) {
                    $enabled = true;
                    $html = $res;
                }

                $section['state'] = $enabled ? 'enabled' : 'disabled';
                $section['html'] = $html;
            } else {
                $section['state'] = 'disabled';
                $section['html'] = '';
            }
        }
    }

    private function section_render( $template, $vars = array(), $result = true ) {
        $vars['listing'] = $this->listing;
        $output = wpbdp_render( $template, $vars, false );

        return array( $result, $output );
    }

    private function plan_selection() {
        global $wpbdp;

        $allow_recurring = wpbdp_get_option( 'listing-renewal-auto' ) && $wpbdp->payments->check_capability( 'recurring' );
        $category_field = wpbdp_get_form_fields( 'association=category&unique=1' ) or die( '' );
        $plans = WPBDP_Fee_Plan::find( 'all' ); unset($plans[0]);

        $categories = $category_field->value_from_POST();
        $plan_id = ! empty( $_POST['listing_plan'] ) ? absint( $_POST['listing_plan'] ) : 0;

        $errors = array();
        if ( $categories && ! $category_field->validate( $categories, $errors ) ) {
            $this->messages = array_merge( $this->messages, $errors );
            $this->prevent_save = true;
        } elseif ( $categories && $plan_id ) {
            $plan = WPBDP_Fee_Plan::find( $plan_id );

            if ( ! $plan || ! $plan->supports_category_selection( $categories ) ) {
                $this->messages[] = array( _x( 'Please choose a valid fee plan for your category selection.', 'submit listing', 'WPBDM' ), 'error' );
                $this->prevent_save = true;
            } else {
                // Set new fee plan.
                $auto_renew = $allow_recurring && ( wpbdp_get_option( 'listing-renewal-auto-dontask' ) || ( ! empty( $_POST['autorenew_fees'] ) && 'autorenew' == $_POST['autorenew_fees'] ) );
 
                wp_set_post_terms( $this->listing->get_id(), $categories, WPBDP_CATEGORY_TAX, false );
                $this->listing->set_fee_plan( $plan, $auto_renew, 'pending' );
            }
        }

        if ( $this->listing->get_fee_plan() )
            return $this->section_render( 'submit-listing-plan-selection-complete' );

        return $this->section_render( 'submit-listing-plan-selection', compact( 'category_field', 'plans', 'allow_recurring' ) );
    }

    private function listing_fields() {
        $fields = wpbdp_get_form_fields( array( 'association' => '-category' ) );
        $fields = apply_filters_ref_array( 'wpbdp_listing_submit_fields', array( &$fields, &$this->listing ) );
        $field_values = array();

        $validation_errors = array();

        foreach ( $fields as $field ) {
            $value = $field->value( $this->listing->get_id() );
            $posted_value = $field->value_from_POST();

            if ( null !== $posted_value )
                $value = $posted_value;

            $field_values[ $field->get_id() ] = $value;

            if ( null !== $posted_value ) {
                $field_errors = null;
                $validate_res = apply_filters_ref_array( 'wpbdp_listing_submit_validate_field', array(
                                                            $field->validate( $value, $field_errors ),
                                                            &$field_errors,
                                                            &$field,
                                                            $value,
                                                            &$this->listing
                                                        ) );

                if ( ! $validate_res ) {
                    $validation_errors[ $field->get_id() ] = $field_errors;
                } else {
                    $field->store_value( $this->listing->get_id(), $value );
                }
            }
        }

        if ( $validation_errors ) {
            $this->messages( _x( 'Something went wrong. Please check the form for errors, correct them and submit again.', 'listing submit', 'WPBDM' ), 'error', 'listing_fields' );
            $this->prevent_save = true;
        }

        return $this->section_render( 'submit-listing-fields', compact( 'fields', 'field_values', 'validation_errors' ) );
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

    private function listing_images() {
        if ( ! wpbdp_get_option( 'allow-images' ) )
            return false;

        $listing = $this->listing;
        $plan = $listing->get_fee_plan();
        $image_slots = absint( $plan->fee_images );

        if ( ! empty( $_POST['thumbnail_id'] ) )
            $listing->set_thumbnail_id( $_POST['thumbnail_id'] );

        $images = $this->listing->get_images( 'ids' );

        foreach ( $images as $img_id ) {
            $updated_meta = ( ! empty( $_POST['images_meta'][ $img_id ] ) ) ? (array) $_POST['images_meta'][ $img_id ] : array();

            update_post_meta( $img_id, '_wpbdp_image_weight', ! empty( $updated_meta['order'] ) ? intval( $updated_meta['order'] ) : 0 );
            update_post_meta( $img_id, '_wpbdp_image_caption', ! empty( $updated_meta['caption'] ) ? trim( $updated_meta['caption'] ) : '' );
        }

        $images_meta = $this->listing->get_images_meta();

        $thumbnail_id = $this->listing->get_thumbnail_id();

        // TODO: replace this with calls to utility functions.
        $images = $this->sort_images( $images, $images_meta );
        $image_slots_remaining = $image_slots - count( $images );

        $image_min_file_size = intval( wpbdp_get_option( 'image-min-filesize' ) );
        $image_min_file_size = $image_min_file_size ? size_format( $image_min_file_size * 1024 ) : '0';

        $image_max_file_size = intval( wpbdp_get_option( 'image-max-filesize' ) );
        $image_max_file_size = $image_max_file_size ? size_format( $image_max_file_size * 1024 ) : '0';

        $image_min_width = intval( wpbdp_get_option( 'image-min-width' ) );
        $image_max_width = intval( wpbdp_get_option( 'image-max-width' ) );
        $image_min_height = intval( wpbdp_get_option( 'image-min-height' ) );
        $image_max_height = intval( wpbdp_get_option( 'image-max-height' ) );


        return $this->section_render( 'submit-listing-images',
                                      compact( 'image_max_file_size',
                                               'image_min_file_size',
                                               'image_min_width',
                                               'image_max_width',
                                               'image_min_height',
                                               'image_max_height',
                                               'images',
                                               'images_meta',
                                               'image_slots',
                                               'image_slots_remaining',
                                               'thumbnail_id',
                                               'listing' ) );
    }

    private function terms_and_conditions() {
        $tos = trim( wpbdp_get_option( 'terms-and-conditions' ) );

        if ( ! $tos )
            return false;

        $is_url = wpbdp_starts_with( $tos, 'http://', false ) || wpbdp_starts_with( $tos, 'https://', false );
        $accepted = ! empty( $_POST['terms-and-conditions-agreement'] ) && 1 == $_POST['terms-and-conditions-agreement'];

        if ( ! empty( $_POST ) && ! $accepted ) {
            $this->messages( _x( 'Please agree to the Terms and Conditions.', 'templates', 'WPBDM' ), 'error', 'terms_and_conditions' );
            $this->prevent_save = true;
        }

        $html = '';

        if ( ! $is_url ) {
            $html .= '<label>';
            $html .= _x( 'Terms and Conditions:', 'templates', 'WPBDM' );
            $html .= '</label><br />';
            $html .= sprintf( '<textarea readonly="readonly" class="wpbdp-submit-listing-tos">%s</textarea>', esc_textarea( $tos ) );
            $html .= '<br />';
        }

        $html .= '<label>';
        $html .= '<input type="checkbox" name="terms-and-conditions-agreement" value="1" />';

        $label = _x( 'I agree to the <a>Terms and Conditions</a>', 'templates', 'WPBDM' );
        if ( $is_url )
            $label = str_replace( '<a>', '<a href="' . esc_url( $tos ) . '" target="_blank">', $label );
        else
            $label = str_replace( array( '<a>', '</a>' ), '', $label );

        $html .= $label;
        $html .= '</label>';

        return array( true, $html );
    }

    private function save_listing() {
        // XXX: what to do with this?
        // $extra = wpbdp_capture_action_array( 'wpbdp_listing_form_extra_sections', array( &$this->state ) ); 
        // return $this->render( 'extra-sections', array( 'output' => $extra ) );
        // do_action_ref_array( 'wpbdp_listing_form_extra_sections_save', array( &$this->state ) );
        $payment = $this->listing->generate_or_retrieve_payment();

        if ( current_user_can( 'administrator' ) ) {
            $payment->payment_items[0]['description'] .= ' ' . _x( '(admin, no charge)', 'submit listing', 'WPBDM' );
            $payment->payment_items[0]['amount'] = 0.0;
            $payment->status = 'completed';

            $payment->add_note( _x( 'Admin submit. Payment skipped.', 'submit listing', 'WPBDM' ) );

            $payment->save();
        }

        if ( ! $payment )
            die();

        $this->listing->set_post_status( $this->editing ? wpbdp_get_option( 'edit-post-status' ) : wpbdp_get_option( 'new-post-status' ) );
        $this->listing->notify( $this->editing ? 'edit' : 'new' );

        if ( 'completed' == $payment->status )
            return $this->done();

        $checkout_url = $payment->get_checkout_url();
        return $this->_redirect( $checkout_url );
    }

    private function done() {
        return wpbdp_render( 'submit-listing-done', array( 'listing' => $this->listing, 'editing' => $this->editing ) );
    }

}
