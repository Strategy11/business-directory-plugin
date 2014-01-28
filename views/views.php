<?php
/*
 * General directory views
 */

if (!class_exists('WPBDP_DirectoryController')) {

class WPBDP_DirectoryController {

    public $action = null;

    public function __construct() {
        add_action( 'wp', array( $this, '_handle_action'), 10, 1 );
        $this->_extra_sections = array();
    }

    public function init() {
        $this->listings = wpbdp_listings_api();
    }

    public function check_main_page(&$msg) {
        $msg = '';

        $wpbdp = wpbdp();
        if (!$wpbdp->_config['main_page']) {
            if (current_user_can('administrator') || current_user_can('activate_plugins'))
                $msg = __('You need to create a page with the [businessdirectory] shortcode for the Business Directory plugin to work correctly.', 'WPBDM');
            else
                $msg = __('The directory is temporarily disabled.', 'WPBDM');
            return false;
        }

        return true;
    }

    public function _handle_action(&$wp) {
        if ( is_page() && get_the_ID() == wpbdp_get_page_id( 'main' ) ) {
            $action = get_query_var('action') ? get_query_var('action') : ( isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '' );

            if (get_query_var('category_id') || get_query_var('category')) $action = 'browsecategory';
            if (get_query_var('tag')) $action = 'browsetag';
            if (get_query_var('id') || get_query_var('listing')) $action = 'showlisting';

            if (!$action) $action = 'main';

            $this->action = $action;
        } else {
            $this->action = null;
        }
    }

    public function get_current_action() {
        return $this->action;
    }

    public function dispatch() {
        switch ($this->action) {
            case 'showlisting':
                return $this->show_listing();
                break;
            case 'browsecategory':
                return $this->browse_category();
                break;
            case 'browsetag':
                return $this->browse_tag();
                break;
            case 'editlisting':
            case 'submitlisting':
                return $this->submit_listing();
                break;
            case 'sendcontactmessage':
                return $this->send_contact_message();
                break;
            case 'deletelisting':
                return $this->delete_listing();
                break;
            case 'upgradetostickylisting':
                return $this->upgrade_to_sticky();
                break;
            case 'viewlistings':
                return $this->view_listings(true);
                break;
            case 'renewlisting':
                require_once( WPBDP_PATH . 'views/renew-listing.php' );
                $renew_page = new WPBDP_RenewListingPage();
                return $renew_page->dispatch();
                
                break;
            case 'payment-process':
                return $this->process_payment();
                break;
            case 'search':
                return $this->search();
                break;
            default:
                return $this->main_page();
                break;
        }
    }

    /* Show listing. */
    public function show_listing() {
        if (!$this->check_main_page($msg)) return $msg;

        if (get_query_var('listing') || isset($_GET['listing'])) {
            if ($posts = get_posts(array('post_status' => 'publish', 'numberposts' => 1, 'post_type' => WPBDP_POST_TYPE, 'name' => get_query_var('listing') ? get_query_var('listing') : wpbdp_getv($_GET, 'listing', null) ) )) {
                $listing_id = $posts[0]->ID;
            } else {
                $listing_id = null;
            }
        } else {
            $listing_id = get_query_var('id') ? get_query_var('id') : wpbdp_getv($_GET, 'id', null);
        }

        if ( !$listing_id )
            return;

        $html  = '';

        if ( isset($_GET['preview']) )
            $html .= wpbdp_render_msg( _x('This is just a preview. The listing has not been published yet.', 'preview', 'WPBDM') );

        $html .= wpbdp_render_listing($listing_id, 'single', false, true);

        return $html;
    }

    /* Display category. */
    public function browse_category($category_id=null) {
        if (!$this->check_main_page($msg)) return $msg;

        if (get_query_var('category')) {
            if ($term = get_term_by('slug', get_query_var('category'), WPBDP_CATEGORY_TAX)) {
                $category_id = $term->term_id;
            } else {
                $category_id = intval(get_query_var('category'));
            }
        }

        $category_id = $category_id ? $category_id : intval(get_query_var('category_id'));

        $listings_api = wpbdp_listings_api();

        query_posts(array(
            'post_type' => WPBDP_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC'),
            'tax_query' => array(
                array('taxonomy' => WPBDP_CATEGORY_TAX,
                      'field' => 'id',
                      'terms' => $category_id)
            )
        ));

        $html = wpbdp_render( 'category',
                             array(
                                'category' => get_term( $category_id, WPBDP_CATEGORY_TAX ),
                                'is_tag' => false
                                ),
                             false );

        wp_reset_query();

        return $html;
    }

    /* Display category. */
    public function browse_tag() {
        if (!$this->check_main_page($msg)) return $msg;

        $tag = get_term_by('slug', get_query_var('tag'), WPBDP_TAGS_TAX);
        $tag_id = $tag->term_id;

        $listings_api = wpbdp_listings_api();

        query_posts(array(
            'post_type' => WPBDP_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC'),
            'tax_query' => array(
                array('taxonomy' => WPBDP_TAGS_TAX,
                      'field' => 'id',
                      'terms' => $tag_id)
            )
        ));

        $html = wpbdp_render( 'category',
                             array(
                                'category' => get_term( $tag_id, WPBDP_TAGS_TAX ),
                                'is_tag' => true
                                ),
                             false );        

        wp_reset_query();

        return $html;
    }    

    /* display listings */
    public function view_listings($include_buttons=false) {
        $paged = 1;

        if (get_query_var('page'))
            $paged = get_query_var('page');
        elseif (get_query_var('paged'))
            $paged = get_query_var('paged');

        query_posts(array(
            'post_type' => WPBDP_POST_TYPE,
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'post_status' => 'publish',
            'paged' => intval($paged),
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC')
        ));

        $html = wpbdp_capture_action( 'wpbdp_before_viewlistings_page' );
        $html .= wpbdp_render('businessdirectory-listings', array(
                'excludebuttons' => !$include_buttons
            ), true);
        $html .= wpbdp_capture_action( 'wpbdp_after_viewlistings_page' );

        wp_reset_query();

        return $html;
    }

    public function submit_listing() {
        require_once( WPBDP_PATH . 'views/submit-listing.php' );
        $submit_page = new WPBDP_SubmitListingPage( isset( $_REQUEST['listing_id'] ) ? $_REQUEST['listing_id'] : 0 );
        return $submit_page->dispatch();
    }

    /*
     * Send contact message to listing owner.
     */
    public function send_contact_message() {
        if ($listing_id = wpbdp_getv($_REQUEST, 'listing_id', 0)) {
            $current_user = is_user_logged_in() ? wp_get_current_user() : null;

            $author_name = htmlspecialchars(trim(wpbdp_getv($_POST, 'commentauthorname', $current_user ? $current_user->data->user_login : '')));
            $author_email = trim(wpbdp_getv($_POST, 'commentauthoremail', $current_user ? $current_user->data->user_email : ''));
            $message = trim(wp_kses(stripslashes(wpbdp_getv($_POST, 'commentauthormessage', '')), array()));

            $validation_errors = array();

            if (!$author_name)
                $validation_errors[] = _x("Please enter your name.", 'contact-message', "WPBDM");

            if ( !wpbdp_validate_value( $author_email, 'email' ) )
                $validation_errors[] = _x("Please enter a valid email.", 'contact-message', "WPBDM");

            if (!$message)
                $validation_errors[] = _x('You did not enter a message.', 'contact-message', 'WPBDM');

            if (wpbdp_get_option('recaptcha-on')) {
                if ($private_key = wpbdp_get_option('recaptcha-private-key')) {
                    if ( !function_exists( 'recaptcha_get_html' ) )
                        require_once(WPBDP_PATH . 'libs/recaptcha/recaptchalib.php');

                    $resp = recaptcha_check_answer($private_key, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
                    if (!$resp->is_valid)
                        $validation_errors[] = _x("The reCAPTCHA wasn't entered correctly.", 'contact-message', 'WPBDM');
                }
            }

            if (!$validation_errors) {
                $headers =  "MIME-Version: 1.0\n" .
                        "From: $author_name <$author_email>\n" .
                        "Reply-To: $author_email\n" .
                        "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

                $subject = "[" . get_option( 'blogname' ) . "] " . sprintf(_x('Contact via "%s"', 'contact email', 'WPBDM'), wp_kses( get_the_title($listing_id), array() ));
                $wpbdmsendtoemail=wpbusdirman_get_the_business_email($listing_id);
                $time = date_i18n( __('l F j, Y \a\t g:i a'), current_time( 'timestamp' ) );

                $body = wpbdp_render_page(WPBDP_PATH . 'templates/email/contact.tpl.php', array(
                    'listing_url' => get_permalink($listing_id),
                    'name' => $author_name,
                    'email' => $author_email,
                    'message' => $message,
                    'time' => $time
                ), false);

                $html = '';

                // TODO: should use WPBDP_Email instead
                if(wp_mail( $wpbdmsendtoemail, $subject, $body, $headers )) {
                    $html .= "<p>" . _x("Your message has been sent.", 'contact-message', "WPBDM") . "</p>";
                } else {
                    $html .= "<p>" . _x("There was a problem encountered. Your message has not been sent", 'contact-message', "WPBDM") . "</p>";
                }

                $html .= sprintf('<p><a href="%s">%s</a></p>', get_permalink($listing_id), _x('Return to listing.', 'contact-message', "WPBDM"));

                return $html;
            } else {
                return wpbdp_listing_contact_form( $listing_id, $validation_errors );
            }
        }
    }

    /*
     * Directory views/actions
     */
    public function main_page() {
        $html = '';

        if ( count(get_terms(WPBDP_CATEGORY_TAX, array('hide_empty' => 0))) == 0 ) {
            if (is_user_logged_in() && current_user_can('install_plugins')) {
                $html .= wpbdp_render_msg( _x('There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that there are currently no listings in the directory. Listings cannot be added until you assign categories to the business directory.', 'templates', 'WPBDM'), 'error' );
            } else {
                $html .= "<p>" . _x('There are currently no listings in the directory.', 'templates', 'WPBDM') . "</p>";
            }
        }

        if (current_user_can('administrator')) {
            if ($errors = wpbdp_payments_api()->check_config()) {
                foreach ($errors as $error) {
                    $html .= wpbdp_render_msg($error, 'error');
                }
            }
        }        

        $listings = '';
        if (wpbdp_get_option('show-listings-under-categories'))
            $listings = $this->view_listings(false);

        $html .= wpbdp_render(array('businessdirectory-main-page', 'wpbusdirman-index-categories'),
                               array(
                                'submit_listing_button' => wpbusdirman_post_menu_button_submitlisting(),
                                'view_listings_button' => wpbusdirman_post_menu_button_viewlistings(),
                                'action_links' => wpbusdirman_post_menu_button_submitlisting() . wpbusdirman_post_menu_button_viewlistings(),
                                'search_form' => wpbdp_get_option('show-search-listings') ? wpbdp_search_form() : '',
                                'listings' => $listings
                               ));

        return $html;
    }


    /*
     * Submit listing process.
     */

    /*
     * @since 2.1.6
     */
    public function register_listing_form_section($id, $section=array()) {
        $section = array_merge( array(
            'title' => '',
            'display' => null,
            'process' => null,
            'save' => null
        ), $section);

        if (!$section['display'] && !$section['process'])
            return false;

        $section['id'] = $id;
        $this->_extra_sections[$id] = (object) $section;

        return true;
    }

    /* Manage Listings */
    public function manage_listings() {
        if (!$this->check_main_page($msg)) return $msg;

        $current_user = is_user_logged_in() ? wp_get_current_user() : null;
        $listings = array();

        if ($current_user) {
            query_posts(array(
                'author' => $current_user->ID,
                'post_type' => WPBDP_POST_TYPE,
                'post_status' => 'publish',
                'paged' => get_query_var('paged') ? get_query_var('paged') : 1
            ));
        }

        $html = wpbdp_render('manage-listings', array(
            'current_user' => $current_user
            ), false);

        if ($current_user)
            wp_reset_query();

        return $html;
    }

    public function delete_listing() {
        if ($listing_id = wpbdp_getv($_REQUEST, 'listing_id')) {
            if ( (wp_get_current_user()->ID == get_post($listing_id)->post_author) || (current_user_can('administrator')) ) {
                $post_update = array('ID' => $listing_id,
                                     'post_type' => WPBDP_POST_TYPE,
                                     'post_status' => wpbdp_get_option('deleted-status'));
                
                wp_update_post($post_update);

                return wpbdp_render_msg(_x('The listing has been deleted.', 'templates', 'WPBDM'))
                      . $this->main_page();
            }
        }
    }

    /* Upgrade to sticky. */
    public function upgrade_to_sticky() {
        $listing_id = wpbdp_getv($_REQUEST, 'listing_id');

        if ( !$listing_id || !wpbdp_user_can('upgrade-to-sticky', $listing_id) )
            return '';

        $upgrades_api = wpbdp_listing_upgrades_api();
        $listings_api = wpbdp_listings_api();

        if ($listings_api->get_payment_status($listing_id) != 'paid' && !current_user_can('administrator')) {
            $html  = '';
            $html .= wpbdp_render_msg(_x('You can not upgrade your listing until its payment has been cleared.', 'templates', 'WPBDM'));
            $html .= sprintf('<a href="%s">%s</a>', get_permalink($listing_id), _x('Return to listing.', 'templates', 'WPBDM'));
            return $html;
        }

        $sticky_info = $upgrades_api->get_info($listing_id);

        if ($sticky_info->pending) {
            $html  = '';
            $html .= wpbdp_render_msg(_x('Your listing is already pending approval for "featured" status.', 'templates', 'WPBDM'));
            $html .= sprintf('<a href="%s">%s</a>', get_permalink($listing_id), _x('Return to listing.', 'templates', 'WPBDM'));
            return $html;
        }

        $action = isset($_POST['do_upgrade']) ? 'do_upgrade' : 'pre_upgrade';

        switch ($action) {
            case 'do_upgrade':
                $payments_api = wpbdp_payments_api();
                
                $transaction_id = $upgrades_api->request_upgrade($listing_id);

                if ($transaction_id && current_user_can('administrator')) {
                    // auto-approve transaction if we are admins
                    $transaction = $payments_api->get_transaction($transaction_id);
                    $transaction->status = 'approved';
                    $transaction->processed_by = 'admin';
                    $transaction->processed_on = date('Y-m-d H:i:s', time());
                    $payments_api->save_transaction($transaction);

                    $html  = '';
                    $html .= wpbdp_render_msg(_x('Listing has been upgraded.', 'templates', 'WPBDM'));
                    $html .= sprintf('<a href="%s">%s</a>', get_permalink($listing_id), _x('Return to listing.', 'templates', 'WPBDM'));
                    return $html;
                }

                return $payments_api->render_payment_page(array(
                    'title' => _x('Upgrade listing', 'templates', 'WPBDM'),
                    'transaction_id' => $transaction_id,
                    'item_text' => _x('Pay %s upgrade fee via %s', 'templates', 'WPBDM'),
                    'return_link' => array(
                        get_permalink($listing_id),
                        _x('Return to listing.', 'templates', 'WPBDM')
                    )
                ));

                break;
            default:
                $html  = '';

                return wpbdp_render('listing-upgradetosticky', array(
                    'listing' => get_post($listing_id),
                    'featured_level' => $sticky_info->upgrade
                ), false);

                return $html;
                break;
        }
    }

    /* payment processing */
    public function process_payment() {
        $html = '';
        $api = wpbdp_payments_api();

        if ($transaction_id = $api->process_payment($_REQUEST['gateway'], $error_message)) {
            if ( $error_message ) {
                return wpbdp_render_msg($error_message, $type='error');
            }

            $transaction = $api->get_transaction($transaction_id);

            if ($transaction->payment_type == 'upgrade-to-sticky') {
                $html .= sprintf('<h2>%s</h2>', _x('Listing Upgrade Payment Status', 'templates', 'WPBDM'));
            } elseif ($transaction->payment_type == 'initial') {
                $html .= sprintf('<h2>%s</h2>', _x('Listing Submitted', 'templates', 'WPBDM'));
            } else {
                $html .= sprintf('<h2>%s</h2>', _x('Listing Payment Confirmation', 'templates', 'WPBDM'));
            }

            if (wpbdp_get_option('send-email-confirmation')) {
                $listing_id = $transaction->listing_id;
                $message = wpbdp_get_option('payment-message');
                $message = str_replace("[listing]", get_the_title($listing_id), $message);

                $email = new WPBDP_Email();
                $email->subject = "[" . get_option( 'blogname' ) . "] " . wp_kses( get_the_title($listing_id), array() );
                $email->to[] = wpbusdirman_get_the_business_email($listing_id);
                $email->body = $message;
                $email->send();
            }

            $html .= sprintf('<p>%s</p>', wpbdp_get_option('payment-message'));
        }

        return $html;
    }

    /*
     * Search functionality
     */
    public function search() {
        $results = array();

        if ( isset( $_GET['dosrch'] ) ) {
            $search_args = array();
            $search_args['q'] = wpbdp_getv($_GET, 'q', null);
            $search_args['fields'] = array(); // standard search fields
            $search_args['extra'] = array(); // search fields added by plugins

            foreach ( wpbdp_getv( $_GET, 'listingfields', array() ) as $field_id => $field_search )
                $search_args['fields'][] = array( 'field_id' => $field_id, 'q' => $field_search );

            foreach ( wpbdp_getv( $_GET, '_x', array() ) as $label => $field )
                $search_args['extra'][ $label ] = $field;

            $listings_api = wpbdp_listings_api();
            $results = $listings_api->search( $search_args );
        }

        $form_fields = wpbdp_get_form_fields( array( 'display_flags' => 'search', 'validators' => '-email' ) );
        $fields = '';
        foreach ( $form_fields as &$field ) {
            $field_value = isset( $_REQUEST['listingfields'] ) && isset( $_REQUEST['listingfields'][ $field->get_id() ] ) ? $field->convert_input( $_REQUEST['listingfields'][ $field->get_id() ] ) : $field->convert_input( null );
            $fields .= $field->render( $field_value, 'search' );
        }

        query_posts( array(
            'post_type' => WPBDP_POST_TYPE,
            'posts_per_page' => wpbdp_get_option( 'listings-per-page' ) > 0 ? wpbdp_get_option( 'listings-per-page' ) : -1,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'post__in' => $results ? $results : array(0),
            'orderby' => wpbdp_get_option( 'listings-order-by', 'date' ),
            'order' => wpbdp_get_option( 'listings-sort', 'ASC' )
        ) );

        $html = wpbdp_render( 'search',
                               array( 
                                      'fields' => $fields,
                                      'searching' => isset( $_GET['dosrch'] ) ? true : false,
                                      'show_form' => !isset( $_GET['dosrch'] ) || wpbdp_get_option( 'show-search-form-in-results' )
                                    ),
                              false );
        wp_reset_query();

        return $html;
    }

}

}