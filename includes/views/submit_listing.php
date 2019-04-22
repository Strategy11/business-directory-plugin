<?php
/**
 * Submit Listing View
 *
 * @package WPBDP\Views
 */

// phpcs:disable
require_once WPBDP_PATH . 'includes/helpers/class-authenticated-listing-view.php';
/**
 * @SuppressWarnings(PHPMD)
 */
class WPBDP__Views__Submit_Listing extends WPBDP__Authenticated_Listing_View {

    protected $listing  = null;
    protected $sections = array();

    protected $prevent_save = false;
    protected $editing      = false;
    protected $data         = array();
    protected $messages     = array( 'general' => array() );

    private $available_plans         = array();
    public $skip_plan_selection      = false;
    public $skip_plan_payment        = false;
    public $category_specific_fields = false;
    public $fixed_plan_id            = 0;


    public function get_title() {
        return _x( 'Submit A Listing', 'views', 'WPBDM' );
    }

    public function enqueue_resources() {
        wp_enqueue_style( 'dashicons' );

        wp_enqueue_script(
            'wpbdp-submit-listing',
            WPBDP_URL . 'assets/js/submit-listing.min.js',
            array(),
            WPBDP_VERSION
        );

        wp_enqueue_script( 'wpbdp-checkout' );

        // Required for the date picker.
        wpbdp_enqueue_jquery_ui_style();
        wp_enqueue_script( 'jquery-ui-datepicker', false, false, false, true );

        // Required for textareas with HTML support via the WP Editor.
        // XXX: wp_enqueue_editor was added in WordPress 4.8.0.
        if ( function_exists( 'wp_enqueue_editor' ) ) {
            wp_enqueue_editor();
        }

        // Required for account creation (if enabled).
        if ( 'disabled' !== wpbdp_get_option( 'create-account-during-submit-mode' ) ) {
            wp_enqueue_script( 'password-strength-meter' );
        }

        wp_localize_script(
            'wpbdp-submit-listing', 'wpbdpSubmitListingL10n', array(
				'categoriesPlaceholderTxt' => _x( 'Click this field to add categories', 'submit listing', 'WPBDM' ),
				'completeListingTxt'       => _x( 'Complete Listing', 'submit listing', 'WPBDM' ),
				'continueToPaymentTxt'     => _x( 'Continue to Payment', 'submit listing', 'WPBDM' ),
				'isAdmin'                  => current_user_can( 'administrator' ),
				'waitAMoment'              => _x( 'Please wait a moment!', 'submit listing', 'WPBDM' ),
				'somethingWentWrong'       => _x( 'Something went wrong!', 'submit listing', 'WPBDM' ),
            )
        );

        do_action( 'wpbdp_submit_listing_enqueue_resources' );
    }

    public function saving() {
        return ( ! empty( $_POST['save_listing'] ) && '1' == $_POST['save_listing'] );
    }

    public function dispatch() {
        $msg = '';
        if ( ! $this->can_submit( $msg ) ) {
            return wpbdp_render_msg( $msg );
        }

        if ( $this->editing ) {
            $message = '';

            if ( empty( $_REQUEST['listing_id'] ) ) {
                $message = _x( 'No listing ID was specified.', 'submit listing', 'WPBDM' );
            } elseif ( ! wpbdp_user_can( 'edit', $_GET['listing_id'] ) ) {
                $message = _x( "You can't edit this listing.", 'submit listing', 'WPBDM' );
            }

            if ( $message ) {
                return wpbdp_render_msg( $message );
            }
        }

        $this->listing = $this->find_or_create_listing();

        // Perform auth.
        $this->_auth_required( array( 'wpbdp_view' => 'submit_listing' ) );

        // Handle "Clear Form" request.
        if ( ! empty( $_POST ) && ! empty( $_POST['reset'] ) && 'reset' === $_POST['reset'] ) {
            if ( ! $this->editing ) {
                wp_delete_post( $this->listing->get_id(), true );
                return $this->_redirect( wpbdp_url( 'submit_listing' ) );
            }

            return $this->_redirect( wpbdp_url( 'edit_listing', $this->listing->get_id() ) );
        }

        if ( ! $this->editing && 'auto-draft' !== get_post_status( $this->listing->get_id() ) ) {
            $possible_payment = WPBDP_Payment::objects()->filter(
                array(
					'listing_id'   => $this->listing->get_id(),
					'payment_type' => 'initial',
					'status'       => 'pending',
                )
            )->get();

            if ( $possible_payment ) {
                return $this->_redirect( $possible_payment->get_checkout_url() );
            } else {
				return $this->done();
            }
        }

        if ( $this->editing && ! $this->listing->has_fee_plan() ) {
            if ( current_user_can( 'administrator' ) ) {
                return wpbdp_render_msg(
                    str_replace(
                        '<a>',
                        '<a href="' . esc_url( $this->listing->get_admin_edit_link() ) . '">',
                        _x( 'This listing can\'t be edited at this time because it has no fee plan associated. Please <a>edit the listing</a> on the backend and associate it to a fee plan.', 'submit listing', 'WPBDM' )
                    ),
                    'error'
                );
            } else {
                return wpbdp_render_msg( _x( 'This listing can\'t be edited at this time. Please try again later or contact the admin if the problem persists.', 'submit listing', 'WPBDM' ), 'error' );
            }
        }

        $this->configure();
        $this->sections = $this->submit_sections();
        $this->prepare_sections();

        if ( ! empty( $_POST['save_listing'] ) && '1' === $_POST['save_listing'] && ! $this->prevent_save ) {
            $res = $this->save_listing();

            if ( is_wp_error( $res ) ) {
                $errors = $res->get_error_messages();

                foreach ( $errors as $e ) {
                    $this->messages( $e, 'error', 'general' );
                }
            } else {
                return $res;
            }
        }

        if ( current_user_can( 'administrator' ) ) {
            $this->messages( _x( 'You\'re logged in as admin, payment will be skipped.', 'submit listing', 'WPBDM' ), 'notice', 'general' );
        }

        $instructions = trim( wpbdp_get_option( 'submit-instructions' ) );
        if ( $instructions ) {
            $this->messages( $instructions, 'tip', 'general' );
        }

        // Prepare $messages for template.
        $messages = array();
        foreach ( $this->messages as $context => $items ) {
            $messages[ $context ] = '';

            foreach ( $items as $i ) {
                $messages[ $context ] .= sprintf( '<div class="wpbdp-msg %s">%s</div>', $i[1], $i[0] );
            }
        }

        $html = wpbdp_render(
            'submit-listing',
            array(
                'listing'  => $this->listing,
                'sections' => $this->sections,
                'messages' => $messages,
                'is_admin' => current_user_can( 'administrator' ),
                'editing'  => $this->editing,
                'submit'   => $this,
            ),
            false
        );
        return $html;
    }

    public function ajax_reset_plan() {
        $res = new WPBDP_Ajax_Response();

        if ( ! $this->can_submit( $msg ) || empty( $_POST['listing_id'] ) ) {
            wp_die();
        }

        $this->listing = $this->find_or_create_listing();

        if ( ! $this->listing->has_fee_plan() ) {
            wp_die();
        }

        if ( ! $this->editing ) {
            // Store previous values before clearing.
            $plan                              = $this->listing->get_fee_plan();
            $this->data['previous_plan']       = $plan ? $plan->fee_id : 0;
            $this->data['previous_categories'] = wp_get_post_terms( $this->listing->get_id(), WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) );

            // Clear plan and categories.
            $this->listing->set_fee_plan( null );
        }

        if ( ! empty( $_POST['listingfields'] ) ) {
            update_post_meta( $this->listing->get_id(), '_wpbdp_temp_listingfields', $_POST['listingfields'] );
        }

        wp_set_post_terms( $this->listing->get_id(), array(), WPBDP_CATEGORY_TAX, false );

        $this->ajax_sections();
    }

    /**
     * Additional configuration for the submit process, prior to the sections being called.
     *
     * @since 5.1.2
     */
    private function configure() {
        // Show "Complete Listing" instead of "Continue to Payment" if all fees are free.
        $this->skip_plan_payment = true;
        foreach ( wpbdp_get_fee_plans() as $plan ) {
            if ( 'flat' !== $plan->pricing_model || 0.0 !== $plan->amount ) {
                $this->skip_plan_payment = false;
            }

            $this->available_plans[] = $plan;
        }

        $this->category_specific_fields = $this->category_specific_fields();

        // Maybe skip plan selection?
        if ( $this->skip_plan_payment ) {
            $this->skip_plan_selection = ( 1 === count( $this->get_available_plans() ) );

            if ( $this->skip_plan_selection ) {
                $plan                = $this->available_plans[0];
                $this->fixed_plan_id = $plan->id;
            }
        }
    }

    public function ajax_sections() {
        $res = new WPBDP_Ajax_Response();

        if ( ! $this->can_submit( $msg ) || empty( $_POST['listing_id'] ) ) {
            $res->send_error( $msg );
        }

        $this->listing = $this->find_or_create_listing();

        // Ignore 'save_listing' for AJAX requests in order to leave it as the final POST with all the data.
        if ( $this->saving() ) {
            unset( $_POST['save_listing'] );
        }

        $this->configure();
        $this->sections = $this->submit_sections();
        $this->prepare_sections();

        $sections = array();
        foreach ( $this->sections as $section ) {
            $messages = ( ! empty( $this->messages[ $section['id'] ] ) ) ? $this->messages[ $section['id'] ] : array();

            $messages_html = '';
            foreach ( $messages as $i ) {
                $messages_html .= sprintf( '<div class="wpbdp-msg %s">%s</div>', $i[1], $i[0] );
            }

            $sections[ $section['id'] ]         = $section;
            $sections[ $section['id'] ]['html'] = wpbdp_render(
                'submit-listing-section', array(
                    'section'  => $section,
                    'messages' => $messages_html,
                )
            );
        }

        $res->add( 'listing_id', $this->listing->get_id() );
        $res->add( 'messages', $this->messages );
        $res->add( 'sections', $sections );
        $res->send();
    }

    public function messages( $msg, $type = 'notice', $context = 'general' ) {
        if ( ! isset( $this->messages[ $context ] ) ) {
            $this->messages[ $context ] = array();
        }

        foreach ( (array) $msg as $msg_ ) {
            $this->messages[ $context ][] = array( $msg_, $type );
        }

        if ( isset( $this->sections[ $context ] ) ) {
            $this->sections[ $context ]['flags'][] = 'has-message';

            if ( 'error' === $type ) {
                $this->sections[ $context ]['flags'][] = 'has-error';
            }
        }
    }

    private function can_submit( &$msg = null ) {
        // TODO: Can we use get_post()?
        $post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;

        if ( is_object( $post ) ) {
            // Submit shortcode is exempt from restrictions.
            $submit_shortcodes = array( 'businessdirectory-submit-listing', 'businessdirectory-submitlisting', 'business-directory-submitlisting', 'business-directory-submit-listing', 'WPBUSDIRMANADDLISTING' );

            foreach ( $submit_shortcodes as $test_shortcode ) {
                if ( has_shortcode( $post->post_content, $test_shortcode ) ) {
                    return true;
                }
            }
        }

        if ( 'submit_listing' === wpbdp_current_view() && wpbdp_get_option( 'disable-submit-listing' ) ) {
            if ( current_user_can( 'administrator' ) ) {
                $msg = _x( '<b>View not available</b>. Do you have the "Disable Frontend Listing Submission?" setting checked?', 'templates', 'WPBDM' );
            } else {
                $msg = _x( 'Listing submission has been disabled. Contact the administrator for details.', 'templates', 'WPBDM' );
            }

            return false;
        }

        return true;
    }

    private function find_or_create_listing() {
        $listing_id = 0;

        if ( ! empty( $_REQUEST['listing_id'] ) && false != get_post_status( $_REQUEST['listing_id'] ) ) {
            $listing_id = absint( $_REQUEST['listing_id'] );
            $listing    = wpbdp_get_listing( $listing_id );
        } else {
            $listing_id = wp_insert_post(
                array(
                    'post_author' => get_current_user_id(),
                    'post_type'   => WPBDP_POST_TYPE,
                    'post_status' => 'auto-draft',
                    'post_title'  => '(no title)',
                )
            );

            $listing = wpbdp_get_listing( $listing_id );
            $listing->set_fee_plan( null );
        }

        if ( ! $listing_id ) {
            die();
        }

        $this->editing = $this->editing || ( (bool) absint( ! empty( $_POST['editing'] ) ? $_POST['editing'] : 0 ) );

        return $listing;
    }

    public function get_listing() {
        return $this->listing;
    }

    public function prevent_save() {
        $this->prevent_save = true;
    }

    private function submit_sections() {
        $sections = array();

        if ( $this->can_edit_plan_or_categories() ) {
            $sections['plan_selection'] = array(
                'title' => $this->skip_plan_selection ? _x( 'Category selection', 'submit listing', 'WPBDM' ) : _x( 'Category & plan selection', 'submit listing', 'WPBDM' ),
            );
        }

        $sections['listing_fields'] = array(
            'title' => _x( 'Listing Information', 'submit listing', 'WPBDM' ),
		);

        if ( wpbdp_get_option( 'allow-images' ) ) {
            $sections['listing_images'] = array(
                'title' => _x( 'Listing Images', 'submit listing', 'WPBDM' ),
            );
        }

        $sections = apply_filters( 'wpbdp_submit_sections', $sections, $this );

        if ( ! $this->editing && ! wpbdp_get_option( 'require-login' ) && 'disabled' !== wpbdp_get_option( 'create-account-during-submit-mode' ) && ! is_user_logged_in() ) {
            $sections['account_creation'] = array(
                'title' => _x( 'Account Creation', 'submit listing', 'WPBDM' ),
            );
        }

        if ( ! $this->editing && wpbdp_get_option( 'display-terms-and-conditions' ) ) {
            $sections['terms_and_conditions'] = array(
                'title' => _x( 'Terms and Conditions', 'submit listing', 'WPBDM' ),
            );
        }

        foreach ( $sections as $section_id => &$s ) {
            $s['id']    = $section_id;
            $s['html']  = '';
            $s['flags'] = array();
        }

        return $sections;
    }

    private function can_edit_plan_or_categories() {
        if ( ! $this->editing || ! $this->listing->has_fee_plan() ) {
            return true;
        }

        $plan = $this->listing->get_fee_plan();
        if ( ! $plan->fee ) {
            return false;
        }

        if ( 'flat' === $plan->fee->pricing_model ) {
            return true;
        }

        return false;
    }

    private function prepare_sections() {
        foreach ( $this->sections as &$section ) {
            $callback = WPBDP_Utils::normalize( $section['id'] );

            if ( ! $this->listing->has_fee_plan() && 'plan_selection' !== $section['id'] ) {
                $section['flags'][] = 'collapsed';
                $section['flags'][] = 'disabled';
                $section['html']    = _x( '(Please choose a fee plan above)', 'submit listing', 'WPBDM' );
                $section['state']   = 'disabled';
                continue;
            }

            if ( method_exists( $this, $callback ) ) {
                $res     = call_user_func( array( $this, $callback ) );
                $html    = '';
                $enabled = false;

                if ( is_array( $res ) ) {
                    $enabled = $res[0];
                    $html    = $res[1];
                } elseif ( is_string( $res ) && ! empty( $res ) ) {
                    $enabled = true;
                    $html    = $res;
                } elseif ( false === $res ) {
                    $section['flags'][] = 'hidden';
                }

                $section['state'] = $enabled ? 'enabled' : 'disabled';
                $section['html']  = $html;
            } else {
                $section['state'] = 'disabled';
                $section['html']  = '';
            }

            $section = apply_filters( 'wpbdp_submit_section_' . $section['id'], $section, $this );

            $section['flags'][] = $section['state'];
        }

        $this->sections = apply_filters( 'wpbdp_submit_prepare_sections', $this->sections, $this );
    }

    private function section_render( $template, $vars = array(), $result = true ) {
        $vars['listing'] = $this->listing;
        $vars['editing'] = $this->editing;
        $vars['_submit'] = $this;
        $output          = wpbdp_render( $template, $vars, false );

        return array( $result, $output );
    }

    private function plan_selection() {
        global $wpbdp;

        $plans = $this->available_plans;

        if ( ! $plans ) {
            wp_die( _x( 'Can not submit a listing at this moment. Please try again later.', 'submit listing', 'WPBDM' ) );
        }

        $msg = _x( 'Listing submission is not available at the moment. Contact the administrator for details.', 'templates', 'WPBDM' );

        if ( current_user_can( 'administrator' ) ) {
            $msg = _x( '<b>View not available</b>, there is no "Category" association field. %s and create a new field with this association, or assign this association to an existing field', 'templates', 'WPBDM' );

            $msg = sprintf(
                $msg,
                sprintf(
                    '<a href="%s">%s</a>',
                    admin_url( 'admin.php?page=wpbdp_admin_formfields' ),
                    _x( 'Go to "Manage Form Fields"', 'admin', 'WPBDM' )
                )
            );
        }

        $category_field = wpbdp_get_form_fields( 'association=category&unique=1' ) or wp_die( $msg );

        if ( $this->editing ) {
            $this->data['previous_categories'] = $this->listing->get_categories( 'ids' );

            $plan_id = $this->listing->get_fee_plan()->fee_id;

            $categories = $category_field->value_from_POST();
            if ( ! $categories && ! empty( $_POST ) ) {
                $this->data['previous_categories'] = array();
                $this->messages( _x( 'Please select a category.', 'submit listing', 'WPBDM' ), 'error', 'plan_selection' );
            }
        } else {
            $categories = $category_field->value_from_POST();

            if ( $this->skip_plan_selection && ! $this->category_specific_fields ) {
                $plan_id = $this->fixed_plan_id;

                if ( ! $this->listing->get_fee_plan() ) {
                    $this->listing->set_fee_plan( $plan_id );
                }

                if ( $this->saving() && ! $categories ) {
                    $this->messages( _x( 'Please select a category for your listing.', 'submit listing', 'WPBDM' ), 'error', 'plan_selection' );
                    $this->prevent_save = true;
                }
            } else {
                $plan_id = ! empty( $_POST['listing_plan'] ) ? absint( $_POST['listing_plan'] ) : 0;
            }
        }

        $errors = array();
        if ( $categories && ! $category_field->validate( $categories, $errors ) ) {
            foreach ( $errors as $e ) {
                $this->messages( $e, 'error', 'plan_selection' );
            }

            $this->prevent_save = true;
        } elseif ( $categories && $plan_id ) {
            $plan = wpbdp_get_fee_plan( $plan_id );

            if ( ! $plan || ! $plan->enabled || ! $plan->supports_category_selection( $categories ) ) {
                if ( $this->editing ) {
                    $this->messages( _x( 'Please choose a valid category for your plan.', 'submit listing', 'WPBDM' ), 'error', 'plan_selection' );
                } else {
                    $this->messages( _x( 'Please choose a valid fee plan for your category selection.', 'submit listing', 'WPBDM' ), 'error', 'plan_selection' );
                }

                $this->prevent_save = true;
            } else {
                // Set categories.
                wp_set_post_terms( $this->listing->get_id(), $categories, WPBDP_CATEGORY_TAX, false );

                if ( ! $this->editing ) {
                    // Set fee plan.
                    $this->listing->set_fee_plan( $plan );
                }
            }
        } elseif ( ! $categories && $this->skip_plan_selection ) {
            $current_categories = $this->listing->get_categories( 'ids' );

            wp_set_post_terms( $this->listing->get_id(), $current_categories, WPBDP_CATEGORY_TAX, false );
        }

        if ( $this->editing ) {
            if ( ! $categories ) {
                $this->prevent_save = true;
            }
        } else {

            if ( $this->skip_plan_selection && ! $this->category_specific_fields ) {
                $this->data['previous_categories'] = $this->listing->get_categories( 'ids' );
            } else {
                if ( $this->listing->get_fee_plan() ) {
                    return $this->section_render( 'submit-listing-plan-selection-complete' );
                } else {
                    $this->prevent_save = true;
                }
            }
        }

        if ( ! $this->editing ) {
            $selected_plan = ! empty( $this->data['previous_plan'] ) ? $this->data['previous_plan'] : 0;

            if ( $this->skip_plan_selection ) {
                $selected_plan = $plan_id;
            }
        } else {
            $selected_plan = $plan_id;
        }

        $selected_categories = ! empty( $this->data['previous_categories'] ) ? $this->data['previous_categories'] : array();
        return $this->section_render( 'submit-listing-plan-selection', compact( 'category_field', 'plans', 'selected_categories', 'selected_plan' ) );
    }

    /**
     * Called dynamically from prepare_sections when the section id is set to
     * 'listing_fields'.
     */
    private function listing_fields( $preview = false ) {
        $form_fields         = wpbdp_get_form_fields( array( 'association' => '-category' ) );
        $form_fields         = apply_filters_ref_array( 'wpbdp_listing_submit_fields', array( &$form_fields, &$this->listing ) );
        $saved_listingfields = get_post_meta( $this->listing->get_id(), '_wpbdp_temp_listingfields', true );
        $field_values        = ! empty( $saved_listingfields ) ? $saved_listingfields : array();

        $validation_errors = array();
        $fields            = array();

        foreach ( $form_fields as $field ) {
            if ( ! $preview && ! $field->validate_categories( $this->listing->get_categories( 'ids' ) ) ) {
                continue;
            }

            $value = ! empty( $field_values[ $field->get_id() ] ) ? $field_values[ $field->get_id() ] : $field->value( $this->listing->get_id() );

            if ( 'title' === $field->get_association() && '(no title)' === $value ) {
                $value = '';
            }

            $posted_value = $field->value_from_POST();

            if ( null !== $posted_value ) {
                $value = $posted_value;
            }

            $field_values[ $field->get_id() ] = $value;

            if ( ! empty( $_POST['save_listing'] ) ) {
                $field_errors = null;
                $validate_res = apply_filters_ref_array(
                    'wpbdp_listing_submit_validate_field', array(
                        $field->validate( $value, $field_errors ),
                        &$field_errors,
                        &$field,
                        $value,
                        &$this->listing,
                    )
                );

                if ( ! $validate_res ) {
                    $validation_errors[ $field->get_id() ] = $field_errors;
                } else {
                    $field->store_value( $this->listing->get_id(), $value );
                }
            }

            $fields[] = $field;
        }

        // FIXME: fake this (for compatibility with modules) until we move everything to wpbdp_save_listing() and
        // friends. See #2945.
        do_action_ref_array( 'WPBDP_Listing::set_field_values', array( &$this->listing, $field_values ) );

        if ( $validation_errors ) {
            $this->messages( _x( 'Something went wrong. Please check the form for errors, correct them and submit again.', 'listing submit', 'WPBDM' ), 'error', 'listing_fields' );
            $this->prevent_save = true;
        }

        return $this->section_render( 'submit-listing-fields', compact( 'fields', 'field_values', 'validation_errors' ) );
    }

    // phpcs:enable

    /**
     * @param array $images_  An array of images.
     * @param array $meta     An of metadata for images.
     */
    private function sort_images( $images_, $meta ) {
        // Sort inside $meta first.
        WPBDP__Utils::sort_by_property( $meta, 'order' );
        $meta = array_reverse( $meta, true );

        // Sort $images_ considering $meta.
        $images = array();

        foreach ( array_keys( $meta ) as $img_id ) {
            if ( in_array( $img_id, $images_, true ) ) {
                $images[] = $img_id;
            }
        }

        foreach ( $images_ as $img_id ) {
            if ( in_array( $img_id, $images, true ) ) {
                continue;
            }

            $images[] = $img_id;
        }

        return $images;
    }

    // phpcs:disable

    private function listing_images() {
        if ( ! wpbdp_get_option( 'allow-images' ) ) {
            return false;
        }

        $listing = $this->listing;
        $plan = $listing->get_fee_plan();
        $image_slots = absint( $plan->fee_images );

        if ( ! $image_slots ) {
            return false;
        }

        $validation_error = '';

        if ( ! empty( $_POST['thumbnail_id'] ) ) {
            $listing->set_thumbnail_id( $_POST['thumbnail_id'] );
        }

        $images = $this->listing->get_images( 'ids' );

        // foreach ( $images as $img_id ) {
        //     $updated_meta = ( ! empty( $_POST['images_meta'][ $img_id ] ) ) ? (array) $_POST['images_meta'][ $img_id ] : array();
        //
        //     update_post_meta( $img_id, '_wpbdp_image_weight', ! empty( $updated_meta['order'] ) ? intval( $updated_meta['order'] ) : 0 );
        //     update_post_meta( $img_id, '_wpbdp_image_caption', ! empty( $updated_meta['caption'] ) ? trim( $updated_meta['caption'] ) : '' );
        // }

        $images_meta = $this->listing->get_images_meta();

        // Maybe update meta.
        if ( ! empty( $_POST['images_meta'] ) ) {
            foreach ( $images as $img_id ) {
                $updated_meta = ( ! empty( $_POST['images_meta'][ $img_id ] ) ) ? (array) $_POST['images_meta'][ $img_id ] : array();

                update_post_meta( $img_id, '_wpbdp_image_weight', ! empty( $updated_meta['order'] ) ? intval( $updated_meta['order'] ) : 0 );
                update_post_meta( $img_id, '_wpbdp_image_caption', ! empty( $updated_meta['caption'] ) ? trim( $updated_meta['caption'] ) : '' );

                $images_meta[ $img_id ] = $updated_meta;
            }
        }

        if ( ! empty( $_POST['save_listing'] ) && ! count( $images_meta ) && wpbdp_get_option( 'enforce-image-upload' ) ) {
            $this->prevent_save = true;
            $this->messages( _x( 'Image upload is required, please provide at least one image and submit again.', 'listing submit', 'WPBDM' ), 'error', 'listing_images' );
        }

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
        $mode = wpbdp_get_option( 'create-account-during-submit-mode' );
        $form_create = empty( $_POST['create-account'] ) ? false : ( $_POST['create-account'] == 'create-account' );
        $form_username = ! empty( $_POST['user_username'] ) ? trim( $_POST['user_username'] ) : '';
        $form_email = ! empty( $_POST['user_email'] ) ? trim( $_POST['user_email'] ) : '';
        $form_password = ! empty( $_POST['user_password'] ) ? $_POST['user_password'] : '';
        // $form_password_retype = ! empty( $_POST['user_password_retype'] ) ? $_POST['user_password_retype'] : '';

        if ( ( $this->saving() && 'required' == $mode ) || $form_create ) {
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

            // if ( ! $error && ( ! $form_password_retype  || ( $form_password != $form_password_retype ) ) ) {
            //     $this->messages( _x( 'Passwords entered do not match.', 'submit listing', 'WPBDM' ), 'error', 'account_creation' );
            //     $error = true;
            // }

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

        if ( 'optional' == $mode ) {
            $html .= '<input id="wpbdp-submit-listing-create_account" type="checkbox" name="create-account" value="create-account" ' . checked( true, $form_create, false ) . '/>';
            $html .= '<label for="wpbdp-submit-listing-create_account">' . _x( 'Create a user account on this site', 'submit listing', 'WPBDM' ) . '</label>';
        }

        $html .= '<div id="wpbdp-submit-listing-account-details" class="' . ( ( 'optional' == $mode && ! $form_create ) ? 'wpbdp-hidden' : '' ) . '">';

        if ( 'required' == $mode ) {
            $html .= '<p>';
            $html .= _x( 'You need to create an account on the site. Please fill out the form below.', 'submit listing', 'WPBDM' );
            $html .= '</p>';
        }

        $html .= '<div class="wpbdp-form-field wpbdp-form-field-type-textfield">';
        $html .= '<div class="wpbdp-form-field-label">';
        $html .= '<label for="user_username">' . _x( 'Username:', 'submit listing', 'WPBDM' ) . '</label>';
        $html .= '</div>';
        $html .= '<div class="wpbdp-form-field-inner">';
        $html .= '<input id="wpbdp-submit-listing-user_username" type="text" name="user_username" value="' . esc_attr( $form_username ) .'" />';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="wpbdp-form-field wpbdp-form-field-type-textfield">';
        $html .= '<div class="wpbdp-form-field-label">';
        $html .= '<label for="user_email">' . _x( 'Email:', 'submit listing', 'WPBDM' ) . '</label>';
        $html .= '</div>';
        $html .= '<div class="wpbdp-form-field-inner">';
        $html .= '<input id="wpbdp-submit-listing-user_email" type="text" name="user_email" value="' . esc_attr( $form_email ) . '" />';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="wpbdp-form-field wpbdp-form-field-type-password">';
        $html .= '<div class="wpbdp-form-field-label">';
        $html .= '<label for="wpbdp-submit-listing-user_password">' . _x( 'Password:', 'submit listing', 'WPBDM' ) . '</label>';
        $html .= '</div>';
        $html .= '<div class="wpbdp-form-field-inner">';
        $html .= '<input id="wpbdp-submit-listing-user_password" type="password" name="user_password" value="" />';
        $html .= '<span class="wpbdp-password-strength-meter"></span>';
        $html .= '</div>';
        $html .= '</div>';

        // $html .= '<div class="wpbdp-form-field wpbdp-form-field-type-password">';
        // $html .= '<div class="wpbdp-form-field-label">';
        // $html .= '<label for="wpbdp-submit-listing-user_password_retype">' . _x( 'Password (type again):', 'submit listing', 'WPBDM' ) . '</label>';
        // $html .= '</div>';
        // $html .= '<div class="wpbdp-form-field-inner">';
        // $html .= '<input id="wpbdp-submit-listing-user_password_retype" type="password" name="user_password_retype" value="" />';
        // $html .= '</div>';
        // $html .= '</div>';

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

        if ( $this->saving() && ! $accepted ) {
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
        $html .= '<input type="checkbox" name="terms-and-conditions-agreement" value="1" ' . ( $accepted ? 'checked="checked"' : '' ) . ' />';

        $label = _x( 'I agree to the <a>Terms and Conditions</a>', 'templates', 'WPBDM' );
        if ( $is_url )
            $label = str_replace( '<a>', '<a href="' . esc_url( $tos ) . '" target="_blank" rel="noopener">', $label );
        else
            $label = str_replace( array( '<a>', '</a>' ), '', $label );

        $html .= $label;
        $html .= '</label>';

        return array( true, $html );
    }

    private function save_listing() {
        if ( ! $this->editing ) {
            $this->listing->set_status( 'incomplete' );

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

            if ( ! $payment )
                die();

            $payment->context = is_admin() ? 'admin-submit' : 'submit';
            $payment->save();
            if ( current_user_can( 'administrator' ) ) {
                $payment->process_as_admin();
                $this->listing->set_flag( 'admin-posted' );
            }
        }

        $listing_status = get_post_status( $this->listing->get_id() );
        $this->listing->set_post_status( $this->editing ? ( 'publish' !== $listing_status ? $listing_status : wpbdp_get_option( 'edit-post-status' ) ) : wpbdp_get_option( 'new-post-status' ) );
        $this->listing->_after_save( 'submit-' . ( $this->editing ? 'edit' : 'new' ) );

        if ( ! $this->editing && 'completed' != $payment->status ) {
            $checkout_url = $payment->get_checkout_url();
            return $this->_redirect( $checkout_url );
        }

        delete_post_meta( $this->listing->get_id(), '_wpbdp_temp_listingfields' );

        return $this->done();
    }

    private function done() {
        $params = array(
            'listing' => $this->listing,
            'editing' => $this->editing,
            'payment' => $this->editing ? false : $this->listing->generate_or_retrieve_payment(),
        );

        return wpbdp_render( 'submit-listing-done', $params );
    }

    public static function preview_form( $listing ) {
        $view = new self;
        $view->listing = $listing;

        // $view->enqueue_resources();
        list( $success, $html ) = $view->listing_fields( true );

        return $html;
    }

    public function category_specific_fields () {
        $form_fields = wpbdp_get_form_fields( array( 'association' => '-category' ) );
        $form_fields = apply_filters_ref_array( 'wpbdp_listing_submit_fields', array( &$form_fields, &$this->listing ) );

        foreach ( $form_fields as $field ) {
            $field_allowed_categories = $field->data( 'supported_categories', 'all' );
            if ( 'all' !== $field_allowed_categories ) {
                return  true;
            }
        }
        return false;
    }

    public function get_available_plans() {
        return $this->available_plans;
    }

}

// phpcs:enable
