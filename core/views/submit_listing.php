<?php
require_once( WPBDP_PATH . 'core/helpers/class-authenticated-listing-view.php' );


class WPBDP__Views__Submit_Listing extends WPBDP__Authenticated_Listing_View {

    protected $listing = null;
    protected $sections = array();

    protected $prevent_save = false;
    protected $editing = false;
    protected $data = array();
    protected $messages = array( 'general' => array() );


    public function get_title() {
        return _x( 'Submit A Listing', 'views', 'WPBDM' );
    }

    public function enqueue_resources() {
        wp_enqueue_style( 'dashicons' );

        wp_enqueue_script( 'wpbdp-submit-listing', WPBDP_URL . 'core/js/submit-listing.min.js', array( 'jquery-ui-sortable' ) );

        wp_localize_script( 'wpbdp-submit-listing', 'wpbdpSubmitListingL10n', array(
            'categoriesPlaceholderTxt' => _x( 'Click this field to add categories', 'submit listing', 'WPBDM' ),
            'completeListingTxt' => _x( 'Complete Listing', 'submit listing', 'WPBDM' ),
            'continueToPaymentTxt' => _x( 'Continue to Payment', 'submit listing', 'WPBDM' ),
            'isAdmin' => current_user_can( 'administrator' )
        ) );
    }

    public function dispatch() {
        $msg = '';
        if ( ! $this->can_submit( $msg ) )
            return wpbdp_render_msg( $msg );

        $this->listing = $this->find_or_create_listing();

        // Perform auth.
        $this->_auth_required();

        // Handle "Clear Form" request.
        if ( ! empty( $_POST ) && ! empty( $_POST['reset'] ) && 'reset' == $_POST['reset'] ) {
            wp_delete_post( $this->listing->get_id(), true );
            return $this->_redirect( wpbdp_url( 'submit_listing' ) );
        }

        if ( ! $this->editing && 'auto-draft' != get_post_status( $this->listing->get_id() ) ) {
            $possible_payment = WPBDP_Payment::objects()->filter( array( 'listing_id' => $this->listing->get_id(), 'payment_type' => 'initial', 'status' => 'pending' ) )->get();

            if ( $possible_payment )
                return $this->_redirect( $possible_payment->get_checkout_url() );
            else
                return $this->done();
        }

        $this->sections = $this->submit_sections();
        $this->prepare_sections();

        if ( ! empty( $_POST['save_listing'] ) && 1 == $_POST['save_listing'] && ! $this->prevent_save ) {
            $res = $this->save_listing();

            if ( is_wp_error( $res ) ) {
                $errors = $res->get_error_messages();

                foreach ( $errors as $e )
                    $this->messages( $e, 'error', 'general' );
            } else {
                return $res;
            }
        }

        if ( current_user_can( 'administrator' ) )
            $this->messages( _x( 'You\'re logged in as admin, payment will be skipped.', 'submit listing', 'WPBDM' ), 'notice', 'general' );


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
                                     'messages' => $messages,
                                     'is_admin' => current_user_can( 'administrator' ) ),
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

        // Store previous values before clearing.
        $plan = $this->listing->get_fee_plan();
        $this->data['previous_plan'] = $plan ? $plan->fee_id : 0;
        $this->data['previous_categories'] = wp_get_post_terms( $this->listing->get_id(), WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) );

        // Clear plan and categories.
        $this->listing->set_fee_plan( null );
        wp_set_post_terms( $this->listing->get_id(), array(), WPBDP_CATEGORY_TAX, false );

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

    public function preview_listing_fields_form( $preview_config = array() ) {
        $preview_config = wp_parse_args( $preview_config,
                                         array( 'fee' => 0,
                                                'level' => 'normal' ) );
        $preview_config = apply_filters( 'wpbdp_view_submit_listing_preview_config', $preview_config );

        $this->state->categories = array( $preview_config['fee'] );

        return $this->listing_fields();
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
            $listing_id = absint( $_REQUEST['listing_id'] );
            $listing = wpbdp_get_listing( $listing_id );
        } else {
            $listing_id = wp_insert_post( array( 'post_author' => 0, 'post_type' => WPBDP_POST_TYPE, 'post_status' => 'auto-draft', 'post_title' => 'Incomplete Listing' ) );

            $listing = wpbdp_get_listing( $listing_id );
            $listing->set_fee_plan( null );
            $listing->set_status( 'incomplete' );
        }

        if ( ! $listing_id )
            die();

        return $listing;
    }

    private function submit_sections() {
        $sections = array();

        if ( ! $this->editing ) {
            $sections['plan_selection'] = array(
                'title' => _x( 'Category & plan selection', 'submit listing', 'WPBDM' )
            );
        }

        $sections['listing_fields'] = array(
            'title' => _x( 'Listing Information', 'submit listing', 'WPBDM' ) );
        $sections['listing_images'] = array(
            'title' => _x( 'Listing Images', 'submit listing', 'WPBDM' ) );

        if ( ! $this->editing && ! wpbdp_get_option( 'require-login' ) && wpbdp_get_option( 'allow-user-creation' ) && ! is_user_logged_in() ) {
            $sections['account_creation'] = array(
                'title' => _x( 'Account Creation', 'submit listing', 'WPBDM' )
            );
        }

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
                $section['html'] = _x( '(Please choose a fee plan above)', 'submit listing', 'WPBDM' );
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
        $plans = wpbdp_get_fee_plans();

        if ( ! $plans )
            wp_die( _x( 'Can not submit a listing at this moment. Please try again later.', 'submit listing', 'WPBDM' ) );

        $categories = $category_field->value_from_POST();
        $plan_id = ! empty( $_POST['listing_plan'] ) ? absint( $_POST['listing_plan'] ) : 0;

        $errors = array();
        if ( $categories && ! $category_field->validate( $categories, $errors ) ) {
            $this->messages = array_merge( $this->messages, $errors );
            $this->prevent_save = true;
        } elseif ( $categories && $plan_id ) {
            $plan = wpbdp_get_fee_plan( $plan_id );

            if ( ! $plan || ! $plan->enabled || ! $plan->supports_category_selection( $categories ) ) {
                $this->messages[] = array( _x( 'Please choose a valid fee plan for your category selection.', 'submit listing', 'WPBDM' ), 'error' );
                $this->prevent_save = true;
            } else {
                // Set new fee plan.
                // $auto_renew = $allow_recurring && ( wpbdp_get_option( 'listing-renewal-auto-dontask' ) || ( ! empty( $_POST['autorenew_fees'] ) && 'autorenew' == $_POST['autorenew_fees'] ) );

                // Set categories.
                wp_set_post_terms( $this->listing->get_id(), $categories, WPBDP_CATEGORY_TAX, false );

                // Set fee plan.
                $this->listing->set_fee_plan( $plan );
            }
        }

        if ( $this->listing->get_fee_plan() )
            return $this->section_render( 'submit-listing-plan-selection-complete' );
        else
            $this->prevent_save = true;

        $selected_plan = ! empty( $this->data['previous_plan'] ) ? $this->data['previous_plan'] : 0;
        $selected_categories = ! empty( $this->data['previous_categories'] ) ? $this->data['previous_categories'] : array();
        return $this->section_render( 'submit-listing-plan-selection', compact( 'category_field', 'plans', 'allow_recurring', 'selected_categories', 'selected_plan' ) );
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

    private function account_creation() {
        $form_create = empty( $_POST['create-account'] ) ? false : ( $_POST['create-account'] == 'create-account' );
        $form_username = ! empty( $_POST['user_username'] ) ? trim( $_POST['user_username'] ) : '';
        $form_email = ! empty( $_POST['user_email'] ) ? trim( $_POST['user_email'] ) : '';
        $form_password = ! empty( $_POST['user_password'] ) ? $_POST['user_password'] : '';

        if ( $form_create ) {
            $error = false;

            if ( ! $form_username ) {
                $this->messages( _x( 'Please enter your desired username.', 'submit listing', 'WPBDM' ), 'error', 'account_creation' );
                $error = true;
            }

            if ( ! $error && ! $form_email ) {
                $this->messages( _x( 'Please enter the e-mail for your new account.', 'submit listing', 'WPBDM' ), 'error', 'account_creation' );
                $error = true;
            }

            if ( ! $error && ! $form_password ) {
                $this->messages( _x( 'Please enter the password for your new account.', 'submit listing', 'WPBDM' ), 'error', 'account_creation' );
                $error = true;
            }

            if ( ! $error && $form_username && username_exists( $form_username ) ) {
                $this->messages( _x( 'The username you chose is already in use. Please use a different one.', 'submit listing', 'WPBDM' ), 'error', 'account_creation' );
                $error = true;
            }

            if ( ! $error && $form_email && email_exists( $form_email ) ) {
                $this->messages( _x( 'The e-mail address you chose for your account is already in use.', 'submit listing', 'WPBDM' ), 'error', 'account_creation' );
                $error = true;
            }

            if ( $error ) {
                $this->prevent_save = true;
            } else {
                $this->data['account_details'] = array( 'username' => $form_username, 'email' => $form_email, 'password' => $form_password );
            }
        }

        $html  = '';

        $html .= '<input id="wpbdp-submit-listing-create_account" type="checkbox" name="create-account" value="create-account" ' . checked( true, $form_create, false ) . '/>';
        $html .= '<label for="wpbdp-submit-listing-create_account">' . _x( 'Create a user account on this site', 'submit listing', 'WPBDM' ) . '</label>';

        $html .= '<div id="wpbdp-submit-listing-account-details" class="' . ( ! $form_create ? 'wpbdp-hidden' : '' ) . '">';
        $html .= '<label for="user_username">' . _x( 'Username:', 'submit listing', 'WPBDM' ) . '</label>';
        $html .= '<input id="wpbdp-submit-listing-user_username" type="text" name="user_username" value="' . esc_attr( $form_username ) .'" />';

        $html .= '<label for="user_email">' . _x( 'Email:', 'submit listing', 'WPBDM' ) . '</label>';
        $html .= '<input id="wpbdp-submit-listing-user_email" type="text" name="user_email" value="' . esc_attr( $form_email ) . '" />';

        $html .= '<label for="wpbdp-submit-listing-user_password">' . _x( 'Password:', 'submit listing', 'WPBDM' ) . '</label>';
        $html .= '<input id="wpbdp-submit-listing-user_password" type="password" name="user_password" value="" />';
        $html .= '</div>';

        // $user_id = username_exists( $user_name );
        // if ( !$user_id and email_exists($user_email) == false ) {
        // 	$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
        // 	$user_id = wp_create_user( $user_name, $random_password, $user_email );
        // } else {
        // 	$random_password = __('User already exists.  Password inherited.');
        // }

        return $html;
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
        if ( ! $this->editing ) {
            if ( ! empty( $this->data['account_details'] ) ) {
                $user_id = wp_create_user( $this->data['account_details']['username'], $this->data['account_details']['password'], $this->data['account_details']['email'] );

                if ( is_wp_error( $user_id ) )
                    return $user_id;

                wp_update_post( array( 'ID' => $this->listing->get_id(), 'post_author' => $user_id ) );
            }

            // XXX: what to do with this?
            // $extra = wpbdp_capture_action_array( 'wpbdp_listing_form_extra_sections', array( &$this->state ) );
            // return $this->render( 'extra-sections', array( 'output' => $extra ) );
            // do_action_ref_array( 'wpbdp_listing_form_extra_sections_save', array( &$this->state ) );
            $this->listing->set_status( 'pending_payment' );
            $payment = $this->listing->generate_or_retrieve_payment();
            $payment->context = is_admin() ? 'admin-submit' : 'submit';
            $payment->save();

            if ( current_user_can( 'administrator' ) ) {
                $payment->process_as_admin();
                $this->listing->set_flag( 'admin-posted' );
            }

            if ( ! $payment )
                die();
        }

        $this->listing->set_post_status( $this->editing ? wpbdp_get_option( 'edit-post-status' ) : wpbdp_get_option( 'new-post-status' ) );
        $this->listing->_after_save( 'submit-' . ( $this->editing ? 'edit' : 'new' ) );

        if ( $payment && 'completed' != $payment->status ) {
            $checkout_url = $payment->get_checkout_url();
            return $this->_redirect( $checkout_url );
        }

        return $this->done();
    }

    private function done() {
        return wpbdp_render( 'submit-listing-done', array( 'listing' => $this->listing, 'editing' => $this->editing ) );
    }

}
