<?php
/*
 * General directory views
 */

if (!class_exists('WPBDP_DirectoryController')) {

class WPBDP_DirectoryController {

	public function __construct() {	}

	public function init() {
		$this->listings = wpbdp_listings_api();

		/* shortcodes */
		add_shortcode('WPBUSDIRMANUI', array($this, 'dispatch'));
		add_shortcode('business-directory', array($this, 'dispatch'));
	}

	public function dispatch() {
    	$action = wpbdp_getv($_REQUEST, 'action');

	   	switch ($action) {
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
    			return $this->view_listings();
    			break;
    		case 'renewlisting':
    			return $this->renew_listing();
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

	/* display listings */
	public function view_listings($excludebuttons=true) {
		wpbusdirman_sticky_loop();

		$paged = 1;

		if (get_query_var('page'))
			$paged = get_query_var('page');
		elseif (get_query_var('paged'))
			$paged = get_query_var('paged');

		query_posts(array(
			'post_type' => wpbdp_post_type(),
			'posts_per_page' => 0,
			'post_status' => 'publish',
			'paged' => intval($paged),
			'orderby' => wpbdp_get_option('listings-order-by', 'date'),
			'order' => wpbdp_get_option('listings-sort', 'ASC'),
			'post__not_in' => wpbdp_listings_api()->get_stickies()
		));

		$html = wpbdp_render('businessdirectory-listings', array(
				'excludebuttons' => $excludebuttons
			), true);

		wp_reset_query();

		return $html;
	}

	/*
	 * Send contact message to listing owner.
	 */
	public function send_contact_message() {
		if ($listing_id = wpbdp_getv($_POST, 'listing_id', 0)) {
			$current_user = is_user_logged_in() ? wp_get_current_user() : null;

			$author_name = htmlspecialchars(trim(wpbdp_getv($_POST, 'commentauthorname', $current_user ? $current_user->data->user_login : '')));
			$author_email = trim(wpbdp_getv($_POST, 'commentauthoremail', $current_user ? $current_user->data->user_email : ''));
			$author_website = trim(wpbdp_getv($_POST, 'commentauthorwebsite', $current_user ? $current_user->data->user_url : ''));
			$message = trim(wp_kses(stripslashes(wpbdp_getv($_POST, 'commentauthormessage', '')), array()));

			$validation_errors = array();

			if (!$author_name)
				$validation_errors[] = _x("Please enter your name.", 'contact-message', "WPBDM");

			if (!wpbusdirman_isValidEmailAddress($author_email))
				$validation_errors[] = _x("Please enter a valid email.", 'contact-message', "WPBDM");

			if ($author_website && !(wpbdp_validate_value('URLValidator', $author_website)))
				$validation_errors[] = _x("Please enter a valid URL.", 'contact-message', "WPBDM");

			if (!$message)
				$validation_errors[] = _x('You did not enter a message.', 'contact-message', 'WPBDM');

			if (wpbdp_get_option('recaptcha-on')) {
				if ($private_key = wpbdp_get_option('recaptcha-private-key')) {
					require_once(WPBDP_PATH . 'recaptcha/recaptchalib.php');

					$resp = recaptcha_check_answer($private_key, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
					if (!$resp->is_valid)
						$validation_errors[] = sprintf(_x("The reCAPTCHA wasn't entered correctly: %s", 'contact-message', 'WPBDM'), $resp->error);
				}
			}

			if (!$validation_errors) {
				$headers =	"MIME-Version: 1.0\n" .
						"From: $author_name <$author_email>\n" .
						"Reply-To: $author_email\n" .
						"Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
				$subject = "[" . get_option( 'blogname' ) . "] " . wp_kses( get_the_title($listing_id), array() );
				$wpbdmsendtoemail=wpbusdirman_get_the_business_email($listing_id);
				$time = date_i18n( __('l F j, Y \a\t g:i a'), current_time( 'timestamp' ) );
				$message = "Name: $author_name
				Email: $author_email
				Website: $author_website

				$message

				Time: $time

				";

				$html = '';

				if(wp_mail( $wpbdmsendtoemail, $subject, $message, $headers )) {
					$html .= "<p>" . _x("Your message has been sent", 'contact-message', "WPBDM") . "</p>";
				} else {
					$html .= "<p>" . _x("There was a problem encountered. Your message has not been sent", 'contact-message', "WPBDM") . "</p>";
				}

				$html .= sprintf('<p><a href="%s">%s</a></p>', get_permalink($listing_id), _x('Return to listing.', 'contact-message', "WPBDM"));

				return $html;
			} else {
				return wpbusdirman_contactform(null, $listing_id, $author_name, $author_email, $author_website, $message, $validation_errors);
			}
		}
	}

	/*
	 * Directory views/actions
	 */
	public function main_page() {
		$html = '';

		if ( count(get_terms(wpbdp_categories_taxonomy(), array('hide_empty' => 0))) == 0 ) {
			if (is_user_logged_in() && current_user_can('install_plugins')) {
				$html .= "<p>" . _x('There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that there are currently no listings in the directory. Listings cannot be added until you assign categories to the business directory.', 'templates', 'WPBDM') . "</p>";
			} else {
				$html .= "<p>" . _x('There are currently no listings in the directory.', 'templates', 'WPBDM') . "</p>";
			}
		}

		$html .= wpbdp_render(array('businessdirectory-main-page-categories', 'wpbusdirman-index-categories'),
							   array(
							   	'submit_listing_button' => wpbusdirman_post_menu_button_submitlisting(),
							   	'view_listings_button' => wpbusdirman_post_menu_button_viewlistings()
							   ));

		if (wpbdp_get_option('show-listings-under-categories')) {
			$html .= $this->view_listings(true);
		}

		return $html;
	}


	/*
	 * Submit listing process.
	 */
	// TODO login is required for edits
	public function submit_listing($listing_id=null) {
		$no_categories_msg = false;

		if (count(get_terms(wpbdp_categories_taxonomy(), array('hide_empty' => false))) == 0) {
			if (is_user_logged_in() && current_user_can('install_plugins')) {
				return wpbdp_render_msg(_x('There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that they cannot add their listing at this time. Listings cannot be added until you assign categories to the business directory.', 'templates', 'WPBDM'), 'error');
			} else {
				return wpbdp_render_msg(_x('Your listing cannot be added at this time. Please try again later.', 'templates', 'WPBDM'), 'error');
			}
		}

		if (wpbdp_get_option('require-login') && !is_user_logged_in())
			return wpbdp_render('parts/login-required', array(), false);

		$step = wpbdp_getv($_POST, '_step', 'fields');
		$this->_listing_data = array('listing_id' => 0,
									 'fields' => array(),
									 'fees' => array(),
									 'images' => array(),
									 'thumbnail_id' => 0);

		if (isset($_POST['listing_data'])) {
			$this->_listing_data = unserialize(base64_decode($_POST['listing_data']));
		} else {
			if (isset($_POST['listingfields']))
				$this->_listing_data['fields'] = $_POST['listingfields'];

			if (isset($_POST['listing_id']))
				$this->_listing_data['listing_id'] = intval($_POST['listing_id']);
		}

		if ($listing_id = $this->_listing_data['listing_id']) {
			$current_user = wp_get_current_user();
			if ( (get_post($listing_id)->post_author != $current_user->ID) && (!current_user_can('administrator')) )
				return wpbdp_render_msg(_x('You are not authorized to edit this listing.', 'templates', 'WPBDM'), 'error');

			if (wpbdp_payment_status($listing_id) != 'paid')
				return wpbdp_render_msg(_x('You can not edit your listing until its payment has been cleared.', 'templates', 'WPBDM'), 'error');
		}

		$html = '';
		$html .= call_user_func(array($this, 'submit_listing_' . $step), $listing_id);
		$html .= apply_filters('wpbdp_listing_form', '', $this->_listing_data['listing_id']);

		return $html;
	}

	public function submit_listing_fields() {
		$formfields_api = wpbdp_formfields_api();

		$post_values = isset($_POST['listingfields']) ? $_POST['listingfields'] : array();
		$validation_errors = array();

		$fields = array();
		foreach ($formfields_api->getFields() as $field) {
			$default_value = '';

			if ($listing_id = $this->_listing_data['listing_id']) {
				switch ($field->association) {
					case 'category':
						$default_value = array();

						foreach (wpbdp_get_listing_field_value($listing_id, $field) as $listing_category) {
							$default_value[] = $listing_category->term_id;
						}

						break;
					case 'tags':
						$tags = wpbdp_get_listing_field_value($listing_id, $field);
						if (is_array($tags))
							array_walk($tags, create_function('&$x', '$x = $x->name;'));
						$default_value = implode(',', $tags ? $tags : array());
						break;
					default:
						$default_value = wpbdp_get_listing_field_value($listing_id, $field);
						break;
				}
			}

			$field_value = wpbdp_getv($post_values, $field->id, $default_value);

			if ($post_values) {
				if (!$formfields_api->validate($field, $field_value, $field_errors))
					$validation_errors = array_merge($validation_errors, $field_errors);
			}

			$fields[] = array('field' => $field,
							  'value' => $field_value,
							  'html' => $formfields_api->render($field, $field_value));
		}

		// if there are values POSTed and everything validates, move on
		if ($post_values && !$validation_errors) {
			return $this->submit_listing_payment();
		}
		
		return wpbdp_render('listing-form-fields', array(
							'validation_errors' => $validation_errors,
							'listing_id' => $this->_listing_data['listing_id'],
							'fields' => $fields,
							), false);		
	}

	private function edit_listing_payment() {
		$listing_id = $this->_listing_data['listing_id'];

		$formfields_api = wpbdp_formfields_api();

		$post_categories = $formfields_api->extract($this->_listing_data['fields'], 'category');
		if (!is_array($post_categories)) $post_categories = array($post_categories);

		// if categories are the same, move on
		$previous_categories = wp_get_post_terms($listing_id, wpbdp_categories_taxonomy());
		array_walk($previous_categories, create_function('&$x', '$x = $x->term_id;'));

		$new_categories = array();
		foreach ($post_categories as $catid) {
			if (!in_array($catid, $previous_categories)) {
				$new_categories[] = $catid;
			} else {
				$fee = wpbdp_listings_api()->get_listing_fee_for_category($listing_id, $catid);
				$fee->_nocharge = true;
				$this->_listing_data['fees'][$catid] = $fee;
			}
		}

		// wpbdp_debug_e($this->_listing_data['fees']);

		if (!$new_categories)
			return $this->submit_listing_images();

		// there are new categories, show the fee options
		$available_fees = wpbdp_fees_api()->get_fees($new_categories);
		$fees = array();

		foreach ($available_fees as $catid => $fee_options) {
			$fees[] = array('category' => get_term($catid, wpbdp_categories_taxonomy()),
							'fees' => $fee_options);
		}

		$validation_errors = array();

		// check every category has a fee selected
		if ($_POST['_step'] == 'payment') {
			$post_fees = wpbdp_getv($_POST, 'fees', array());

			foreach ($new_categories as $catid) {
				$selected_fee_option = wpbdp_getv($post_fees, $catid, null);

				// TODO: check fee is a valid fee for the given category (check $available_fees[$catid] for the id)
				if ($selected_fee_option == null || !isset($available_fees[$catid])) {
					$validation_errors[] = sprintf(_x('Please select a fee option for the "%s" category.', 'templates', 'WPBDM'), get_term($catid, wpbdp_categories_taxonomy())->name);
				}
			}

			if (!$validation_errors) {
				foreach ($new_categories as $catid) {
					$this->_listing_data['fees'][$catid] = wpbdp_fees_api()->get_fee_by_id(wpbdp_getv($post_fees, $catid));
				}

				return $this->submit_listing_images();
			}
		}

		return wpbdp_render('listing-form-fees', array(
							'validation_errors' => $validation_errors,
							'listing_id' => $this->_listing_data['listing_id'],
							'listing_data' => $this->_listing_data,
							'fee_options' => $fees,
							), false);
	}

	public function submit_listing_payment() {
		if ($this->_listing_data['listing_id'])
			return $this->edit_listing_payment();

		$formfields_api = wpbdp_formfields_api();

		$post_categories = $formfields_api->extract($this->_listing_data['fields'], 'category');
		if (!is_array($post_categories)) $post_categories = array($post_categories);

		$available_fees = wpbdp_fees_api()->get_fees($post_categories);
		$fees = array();

		foreach ($available_fees as $catid => $fee_options) {
			$fees[] = array('category' => get_term($catid, wpbdp_categories_taxonomy()),
							'fees' => $fee_options);
		}

		$validation_errors = array();

		// check every category has a fee selected
		if ($_POST['_step'] == 'payment') {
			$post_fees = wpbdp_getv($_POST, 'fees', array());

			foreach ($post_categories as $catid) {
				$selected_fee_option = wpbdp_getv($post_fees, $catid, null);

				// TODO: check fee is a valid fee for the given category (check $available_fees[$catid] for the id)
				if ($selected_fee_option == null || !isset($available_fees[$catid])) {
					$validation_errors[] = sprintf(_x('Please select a fee option for the "%s" category.', 'templates', 'WPBDM'), get_term($catid, wpbdp_categories_taxonomy())->name);
				}
			}

			if (!$validation_errors) {
				foreach ($post_categories as $catid) {
					$this->_listing_data['fees'][$catid] = wpbdp_fees_api()->get_fee_by_id(wpbdp_getv($post_fees, $catid));
				}

				return $this->submit_listing_images();
			}
		}

		return wpbdp_render('listing-form-fees', array(
							'validation_errors' => $validation_errors,
							'listing_id' => $this->_listing_data['listing_id'],
							'listing_data' => $this->_listing_data,
							'fee_options' => $fees,
							), false);
	}

	// todo - avoid duplicate uploads (unset something?)
	public function submit_listing_images() {
		$action = '';
		if (isset($_POST['_step']) && $_POST['_step'] == 'images') {
			if (isset($_POST['upload_image']))
				$action = 'upload';
			if (isset($_POST['delete_image']) && intval($_POST['delete_image']) > 0)
				$action = 'delete';
			if (isset($_POST['submit']))
				$action = 'submit';
		}

		$images_allowed = 0;
		foreach ($this->_listing_data['fees'] as $fee)
			$images_allowed += $fee->images;

		if (!wpbdp_get_option('allow-images') || $images_allowed == 0)
			return $this->submit_listing_save();

		if ($this->_listing_data['listing_id'] && !$this->_listing_data['images']) {
			foreach (wpbdp_listings_api()->get_images($this->_listing_data['listing_id']) as $image) {
				$this->_listing_data['images'][] = $image->ID;
			}
		}

		$images = $this->_listing_data['images'];
		// sanitize images (maybe someone got deleted while we were here?)
		$images = array_filter($images, create_function('$x', 'return get_post($x) !== null;'));
		$this->_listing_data['images'] = $images;

		switch ($action) {
			case 'upload':
				if (($images_allowed - count($images) - 1) >= 0) {
					require_once(ABSPATH . 'wp-admin/includes/file.php');
					require_once(ABSPATH . 'wp-admin/includes/image.php');

					if ($image_file = $_FILES['image']) {
						if ($image_file['error'] == 0) {
							$wp_image_ = wp_handle_upload($image_file, array('test_form' => FALSE));

							if (!isset($wp_image_['error'])) {
								if ($attachment_id = wp_insert_attachment(array(
																'post_mime_type' => $wp_image_['type'],
																'post_title' => preg_replace('/\.[^.]+$/', '', basename($wp_image_['file'])),
																'post_content' => '',
																'post_status' => 'inherit'
																), $wp_image_['file'])) {

								 	$attach_data = wp_generate_attachment_metadata($attachment_id, $wp_image_['file']);
								 	wp_update_attachment_metadata($attachment_id, $attach_data);

								 	if (wp_attachment_is_image($attachment_id)) {
										$this->_listing_data['images'][] = $attachment_id;
									} else {
										wp_delete_attachment($attachment_id, true);
									}

								}
							} else {
								print 'image error';
							}
						} else {
							print 'image error';
						}
					}
				}
				break;
			case 'delete':
				$attachment_id = intval($_POST['delete_image']);

				$key = array_search($attachment_id, $this->_listing_data['images']);
				if ($key !== FALSE) {
					wp_delete_attachment($attachment_id, true);
					unset($this->_listing_data['images'][$key]);
				}
					
				break;
			case 'submit':
				return $this->submit_listing_save();
				break;
			default:
				break;
		}

		$images = $this->_listing_data['images'];

		if (isset($_POST['thumbnail_id']) && in_array($_POST['thumbnail_id'], $images)) {
			$this->_listing_data['thumbnail_id'] = $_POST['thumbnail_id'];
		} elseif ($this->_listing_data['listing_id']) {
			$this->_listing_data['thumbnail_id'] = wpbdp_listings_api()->get_thumbnail_id($this->_listing_data['listing_id']);
		}

		return wpbdp_render('listing-form-images', array(
							'validation_errors' => null,
							'listing' => null,
							'listing_data' => $this->_listing_data,
							'can_upload_images' => (($images_allowed - count($images))> 0),
							'images_left' => ($images_allowed - count($images)),
							'images_allowed' => $images_allowed,
							'images' => $images,
							'thumbnail_id' => $this->_listing_data['thumbnail_id']
							), false);
	}

	public function submit_listing_save() {
		if (isset($_POST['thumbnail_id']))
			$this->_listing_data['thumbnail_id'] = intval($_POST['thumbnail_id']);

		$data = $this->_listing_data;

		$transaction_id = null;
		if ($listing_id = $this->listings->add_listing($data, $transaction_id)) {
			$cost = $this->listings->cost_of_listing($listing_id, true);

			if ($cost > 0.0) {
				$payments_api = wpbdp_payments_api();
				$payment_page = $payments_api->render_payment_page(array(
					'title' => _x('Step 4 - Checkout', 'templates', 'WPBDM'),
					'transaction_id' => $transaction_id,
					'item_text' => _x('Pay %1$s listing fee via %2$s', 'templates', 'WPBDM')
				));

				return wpbdp_render('listing-form-checkout', array(
					'listing_data' => $this->_listing_data,
					'listing' => get_post($listing_id),
					'payment_page' => $payment_page
				), false);
			}

			if (wpbdp_get_option('send-email-confirmation')) {
				$subject = "[" . get_option( 'blogname' ) . "] " . wp_kses( get_the_title($listing_id), array() );
				$message = wpbdp_get_option('email-confirmation-message');
				$message = str_replace("[listing]", get_the_title($listing_id), $message);
				@wp_mail(wpbusdirman_get_the_business_email($listing_id), $subject, $message);
			}

			return wpbdp_render('listing-form-done', array(
							'listing_data' => $this->_listing_data,
							'listing' => get_post($listing_id)
						), false);
		} else {
			return wpbdp_render(_x('An error occurred while saving your listing. Please try again later.', 'templates', 'WPBDM'), 'error');
		}
	}

	/* Manage Listings */
	public function manage_listings() {
		$current_user = is_user_logged_in() ? wp_get_current_user() : null;
		$listings = array();

		if ($current_user)
			query_posts('author=' . $current_user->ID . '&post_type=' . wpbdp_post_type());

		$html = wpbdp_render('manage-listings', array(
			'current_user' => $current_user
			), false);

		if ($current_user)
			wp_reset_query();

		return $html;
	}

	public function delete_listing() {
		if ($listing_id = wpbdp_getv($_POST, 'listing_id')) {
			if ( (wp_get_current_user()->ID == get_post($listing_id)->post_author) || (current_user_can('administrator')) ) {
				$post_update = array('ID' => $listing_id,
									 'post_type' => wpbdp_post_type(),
									 'post_status' => wpbdp_get_option('deleted-status'));
				
				wp_update_post($post_update);

				return wpbdp_render_msg(_x('The listing has been deleted.', 'templates', 'WPBDM'))
					  . $this->manage_listings();
			}
		}
	}

	/* Upgrade to sticky. */
	public function upgrade_to_sticky() {
		if (!wpbdp_get_option('featured-on'))
			return;

		if ($listing_id = wpbdp_getv($_POST, 'listing_id')) {
			if (get_post($listing_id)->post_author != wp_get_current_user()->id)
				return '';

			$listings_api = wpbdp_listings_api();
			
			if ($listings_api->get_payment_status($listing_id) != 'paid')
				return wpbdp_render_msg(_x('You can not upgrade your listing until its payment has been cleared.', 'templates', 'WPBDM'));

			$action = '';
			if (isset($_POST['do_upgrade']))
				$action = 'do_upgrade';

			switch ($action) {
				case 'do_upgrade':
					$listings_api = wpbdp_listings_api();
					$payments_api = wpbdp_payments_api();

					$transaction_id = null;
					$listings_api->request_listing_upgrade($listing_id, $transaction_id);

					return $payments_api->render_payment_page(array(
						'title' => _x('Upgrade listing', 'templates', 'WPBDM'),
						'transaction_id' => $transaction_id,
						'item_text' => _x('Pay %s upgrade fee via %s', 'templates', 'WPBDM')
					));

					break;
				default:
					$sticky_status = $listings_api->get_sticky_status($listing_id);

					if ($sticky_status == 'sticky')
						return wpbdp_render_msg(_x('Your listing is already featured.', 'templates', 'WPBDM'));	
					elseif ($sticky_status == 'pending')
						return wpbdp_render_msg(_x('Your listing is already pending approval for "featured" status.', 'templates', 'WPBDM'));
					else
						return wpbdp_render('listing-upgradetosticky', array(
							'listing' => get_post($listing_id),
						), false);						

					break;
			}
			
			$sticky_status = $listings_api->get_sticky_status($listing_id);

			switch ($sticky_status) {
				case 'sticky':
					return wpbdp_render_msg(_x('Your listing is already featured.', 'templates', 'WPBDM'));
					break;
				case 'pending':
					return wpbdp_render_msg('listing is pending approval');
					break;
				default:
					return wpbdp_render('listing-upgradetosticky', array(
						'listing' => get_post($listing_id)
					), false);
					break;
			}
		}
	}

	/* listing renewal */
	public function renew_listing() {
		global $wpdb;

		$current_date = current_time('mysql');

		if (!wpbdp_get_option('listing-renewal'))
			return '';

		if ($fee_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d AND expires_on IS NOT NULL AND expires_on < %s", intval($_GET['renewal_id']), $current_date))) {
			if ($post = get_post($fee_info->listing_id)) {
				if ($post->post_author != wp_get_current_user()->ID || !has_term($fee_info->category_id, wpbdp_categories_taxonomy(), $post->ID))
					return '';

				$listingsapi = wpbdp_listings_api();
				$feesapi = wpbdp_fees_api();
				$paymentsapi = wpbdp_payments_api();

				$available_fees = $feesapi->get_fees_for_category($fee_info->category_id);

				if (isset($_POST['fee_id'])) {
					if ($fee = $feesapi->get_fee_by_id($_POST['fee_id'])) {
						if ($transaction_id = $listingsapi->renew_listing($_GET['renewal_id'], $fee)) {
							return $paymentsapi->render_payment_page(array(
								'title' => _x('Renew Listing', 'templates', 'WPBDM'),
								'item_text' => _x('Pay %1$s renewal fee via %2$s.', 'templates', 'WPBDM'),
								'transaction_id' => $transaction_id,
							));
						}
					}
				}

				return wpbdp_render('renewlisting-fees', array(
					'fee_options' => $available_fees,
					'category' => get_term($fee_info->category_id, wpbdp_categories_taxonomy()),
					'listing' => $post
				), false);
			}
		}

		return '';
	}

	/* payment processing */
	public function process_payment() {
		$html = '';
		$api = wpbdp_payments_api();

		if ($transaction_id = $api->process_payment($_REQUEST['gateway'])) {
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
				$subject = "[" . get_option( 'blogname' ) . "] " . wp_kses( get_the_title($listing_id), array() );
				$message = wpbdp_get_option('payment-message');
				$message = str_replace("[listing]", get_the_title($listing_id), $message);
				@wp_mail(wpbusdirman_get_the_business_email($listing_id), $subject, $message);
			}

			$html .= sprintf('<p>%s</p>', wpbdp_get_option('payment-message'));
		}

		return $html;
	}

	/*
	 * Search functionality
	 */
	public function search() {
		$fields_api = wpbdp_formfields_api();
		$listings_api = wpbdp_listings_api();

		$results = array();
		if ($_POST) {
			$search_args = array();
			$search_args['q'] = wpbdp_getv($_POST, 'q', null);
			$search_args['meta'] = array();

			foreach (wpbdp_getv($_POST, 'meta', array()) as $field_id => $field_search) {
				$search_args['meta'][] = array('field_id' => $field_id,
											   'q' => wpbdp_getv($field_search, 'q', null),
											   'options' => wpbdp_getv($field_search, 'options', array())
											   );
			}

			$results = $listings_api->search($search_args);
		}

		$fields = array();
		foreach ($fields_api->getFieldsByAssociation('meta') as $field) {
			if (!$field->display_options['hide_field']) $fields[] = $field;
		}

		query_posts(array('post_type' => wpbdp_post_type(),
						  'posts_per_page' => -1,
						  'post__in' => $results ? $results : array(0)));
		$html = wpbdp_render('search', array('fields' => $fields, 'searching' => $_POST ? true : false), false);
		wp_reset_query();

		return $html;
	}

}

}