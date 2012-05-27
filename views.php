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
    		case 'submitlisting':
    			return $this->submit_listing();
    		default:
    			return $this->main_page();
    			break;
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
			$html .= wpbdp_render(array('businessdirectory-listings', 'wpbusdirman-index-listings'),
								   array(
									'exclude_buttons' => 1,								   	
								   	'wpbdmposttype' => wpbdp_post_type(), /* deprecated */
									'excludebuttons' => 1, /* deprecated */
								   ));
		}

		return $html;
	}


	/*
	 * Submit listing process.
	 */
	public function submit_listing() {
		$no_categories_msg = false;

		if (count(get_terms(wpbdp_categories_taxonomy(), array('hide_empty' => false))) == 0) {
			if (is_user_logged_in() && current_user_can('install_plugins')) {
				$no_categories_msg = _x('There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that they cannot add their listing at this time. Listings cannot be added until you assign categories to the business directory.', 'templates', 'WPBDM');
			} else {
				$no_categories_msg = _x('Your listing cannot be added at this time. Please try again later.', 'templates', 'WPBDM');
			}

			// FIXME
			die($no_categories_msg);
			exit;
		}

		$step = wpbdp_getv($_POST, '_step', 'fields');

		$this->_listing_data = array('fields' => array(), 'fees' => array(), 'images' => array(), 'thumbnail_id' => 0);

		if (isset($_POST['listing_data'])) {
			$this->_listing_data = unserialize(base64_decode($_POST['listing_data']));
		} elseif (isset($_POST['listingfields'])) {
			$this->_listing_data['fields'] = $_POST['listingfields'];
		}

		// TODO
		// $html .= apply_filters('wpbdp_listing_form', '', $neworedit == 'new' ? false : true);
		$html = call_user_func(array($this, 'submit_listing_' . $step), $listing_id);

		wpbdp_debug($this->_listing_data);

		return $html;
	}

	public function submit_listing_fields() {
		$listing = isset($_REQUEST['listing_id']) && $_REQUEST['listing_id'] ? get_post(intval($_REQUEST['listing_id'])) : null;

		$formfields_api = wpbdp_formfields_api();

		$post_values = isset($_POST['listingfields']) ? $_POST['listingfields'] : array();
		$validation_errors = array();

		$fields = array();
		foreach ($formfields_api->getFields() as $field) {
			$field_value = wpbdp_getv($post_values, $field->id, '');


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
							'listing' => $listing,
							'fields' => $fields,
							), false);		
	}

	public function submit_listing_payment() {
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

		if (isset($_POST['thumbnail_id']) && in_array($_POST['thumbnail_id'], $images))
			$this->_listing_data['thumbnail_id'] = $_POST['thumbnail_id'];

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

	// TODO: create user when user's not logged in and anonymous submits are allowed
	public function submit_listing_save() {
		if (isset($_POST['thumbnail_id']))
			$this->_listing_data['thumbnail_id'] = intval($_POST['thumbnail_id']);

		$data = $this->_listing_data;
		
		if ($listing_id = $this->listings->add_listing($data)) {
			$cost = $this->listings->cost_of_listing($listing_id);
			if ($cost > 0.0) {
				$payments_api = wpbdp_payments_api();

				$gateways = array();

				foreach ($payments_api->get_available_methods() as $gateway) {
					$gateways[] = array('id' => $gateway->id,
									    'name' => $gateway->name,
									    'html' => $payments_api->generate_html($gateway->id, $listing_id, $cost),
										);
				}

				return wpbdp_render('listing-form-checkout', array(
					'cost' => $cost,
					'gateways' => $gateways,
					'listing' => get_post($listing_id),
				), false);
			}

			return wpbdp_render('listing-form-done', array(
							'listing' => get_post($listing_id)
						), false);
		} else {
			die('ERROR');
		}		

	}


}

}