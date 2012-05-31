<?php
if (!class_exists('WPBDP_ListingsAPI')) {

class WPBDP_ListingsAPI {

	public function __construct() {	}

	public function get_stickies() {
		global $wpdb;

		$stickies = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
									  		 '_wpbdp[sticky]',
									  		 'sticky'));

		return $stickies;
	}

	public function get_sticky_status($listing_id) {
		if ($sticky_status = get_post_meta($listing_id, '_wpbdp[sticky]', true)) {
			return $sticky_status;
		}

		return 'normal';
	}

	public function get_thumbnail_id($listing_id) {
		return intval(get_post_meta($listing_id, '_wpbdp[thumbnail_id]', true));
	}

	public function get_images($listing_id) {
		$attachments = get_posts(array(
			'numberposts' => -1,
			'post_type' => 'attachment',
			'post_parent' => $listing_id
		));

		$result = array();

		foreach ($attachments as $attachment) {
			if (wp_attachment_is_image($attachment->ID))
				$result[] = $attachment;
		}

		return $result;
	}

	public function get_last_transaction($listing_id) {
		global $wpdb;
		
		if ($transaction_id = $wpdb->get_var(
				$wpdb->prepare("SELECT id FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %s ORDER BY id DESC LIMIT 1", $listing_id)
			)) {
			$payments_api = wpbdp_payments_api();
			return $payments_api->get_transaction($transaction_id);
		}

		return null;
	}

	public function get_payment_status($listing_id) {
		if ($payment_status = get_post_meta($listing_id, '_wpbdp[payment_status]', true))
			return $payment_status;

		return 'not-paid';
	}

	public function get_payment_info($listing_id) {
		if ($payment_info = get_post_meta($listing_id, '_wpbdp[payment_info]', true))
			return $payment_info;

		return array();
	}

	// effective_cost means do not include already paid fees
	public function cost_of_listing($listing_id, $effective_cost=false) {
		if (is_object($listing_id)) return $this->cost_of_listing($listing_id->ID);

		$fees = get_post_meta($listing_id, '_wpbdp[fees]', true);
		$cost = 0.0;

		foreach ($fees as $fee) {
			if (!isset($fee['_nocharge']))
				$cost += floatval($fee['amount']);
		}

		return round($cost, 2);
	}

	public function is_free_listing($listing_id) {
		return $this->cost_of_listing($listing_id) == 0.0;
	}

	public function get_expiration_time($listing_id, $fee) {
		if ($fee->days == 0)
			return 0;

		$start_time = get_post_time('U', false, $listing_id);
		$expire_time = strtotime(sprintf('+%d days', $fee->days), $start_time);
		return $expire_time;
	}

	public function get_listing_fee_for_category($listing_id, $catid) {
		$fees = get_post_meta($listing_id, '_wpbdp[fees]', true);

		foreach ($fees as $fee) {
			if ($fee['category_id'] == $catid) {
				unset($fee['category_id']);
				return ((object) $fee);
			}
		}
	}

	// TODO: create user when user's not logged in and anonymous submits are allowed
	// if (!(is_user_logged_in()) ) {
	// 	if ($email_field = $formfields_api->getFieldsByValidator('EmailValidator', true)) {
	// 		if ($email = $formfields_api->extract($listingfields, $email_field)) {
	// 			if (email_exists($email)) {
	// 				$wpbusdirman_UID = get_user_by_email($email)->ID;
	// 			} else {
	// 				$randvalue = wpbusdirman_generatePassword(5,2);
	// 				$wpbusdirman_UID = wp_insert_user(array(
	// 					'display_name' => 'Guest ' . $randvalue,
	// 					'user_login'=> 'guest_' . $randvalue,
	// 					'user_email'=> $email,
	// 					'user_pass'=> wpbusdirman_generatePassword(7,2)));
	// 			}
	// 		}
	// 	}
	// } elseif(is_user_logged_in()) {
	// 	global $current_user;
	// 	get_currentuserinfo();
	// 	$wpbusdirman_UID=$current_user->ID;
	// }
	public function request_listing_upgrade($listing_id, &$transaction_id) {
		$sticky_status = $this->get_sticky_status($listing_id);

		if ($sticky_status == 'normal') {
			$payments_api = wpbdp_payments_api();
			$transaction_id = $payments_api->save_transaction(array(
				'payment_type' => 'upgrade-to-sticky',
				'listing_id' => $listing_id,
				'amount' => wpbdp_get_option('featured-price')
			));

			update_post_meta($listing_id, '_wpbdp[sticky]', 'pending');
		}

		$transaction_id = 0;
		return false;
	}

	public function add_listing($data_, &$transaction_id=null) {
		$data = is_object($data_) ? (array) $data_ : $data_;

		$editing = isset($data['listing_id']) && $data['listing_id'];

		$listingfields = $data['fields'];

		$formfields_api = wpbdp_formfields_api();

		$post_title = trim(strip_tags($formfields_api->extract($listingfields, 'title')));
		$post_excerpt = trim(strip_tags($formfields_api->extract($listingfields, 'excerpt')));
		$post_content = trim(strip_tags($formfields_api->extract($listingfields, 'content')));

		$post_categories = $formfields_api->extract($listingfields, 'category');
		if (!is_array($post_categories))
			$post_categories = array($post_categories);

		$post_tags = $formfields_api->extract($listingfields, 'tags');
		if ($post_tags && !is_array($post_tags))
			$post_tags = explode(',', $post_tags);

		$post_status = $data['listing_id'] ? wpbdp_get_option('edit-post-status') : wpbdp_get_option('new-post-status');

		$listing_id = wp_insert_post(array(
			'post_title' => $post_title,
			'post_content' => $post_content,
			'post_excerpt' => $post_excerpt,
			'post_status' => $post_status,
			'post_type' => wpbdp_post_type(),
			'ID' => isset($data['listing_id']) ? intval($data['listing_id']) : null

		));

		wp_set_post_terms($listing_id, $post_categories, wpbdp_categories_taxonomy(), false);
		wp_set_post_terms($listing_id, $post_tags, wpbdp_tags_taxonomy(), false);

		// register field values
		foreach ($formfields_api->getFieldsByAssociation('meta') as $field) {
			if (isset($listingfields[$field->id])) {
				if ($value = $formfields_api->extract($listingfields, $field)) {
					if (in_array($field->type, array('multiselect', 'checkbox'))) {
						$value = implode("\t", $value);
					}

					update_post_meta($listing_id, '_wpbdp[fields][' . $field->id . ']', $value);
				}
			}
		}

		// attach images
		if (isset($data['images']) && $data['images']) {
			foreach ($data['images'] as $image_id) {
				wp_update_post(array('ID' => $image_id,
									 'post_parent' => $listing_id));
			}

			if (isset($data['thumbnail_id']) && $data['thumbnail_id']) {
				update_post_meta($listing_id, '_wpbdp[thumbnail_id]', $data['thumbnail_id']);
			} else {
				update_post_meta($listing_id, '_wpbdp[thumbnail_id]', $data['images'][0]);
			}
		}

		// register fee information
		if (!isset($data['fees'])) $data['fees'] = array();

		$fee_information = array();
		foreach ($post_categories as $catid) {
			$fee = (array) (isset($data['fees'][$catid]) ? $data['fees'][$catid] : wpbdp_fees_api()->get_free_fee());
			$fee['category_id'] = $catid;
			unset($fee['categories'], $fee['extra_data']);

			$fee_information[] = $fee;

			if (!isset($fee['_nocharge']))
				add_post_meta($listing_id, '_wpbdp[expired][' . $catid . ']', 0);
		}

		update_post_meta($listing_id, '_wpbdp[fees]', $fee_information);

		// register payment info
		$cost = $this->cost_of_listing($listing_id, true);
		$payment_api = wpbdp_payments_api();
		$transaction_id = $payment_api->save_transaction(array(
			'amount' => $cost,
			'payment_type' => !$editing ? 'initial' : 'edit',
			'listing_id' => $listing_id
		));
		update_post_meta($listing_id, '_wpbdp[payment_status]', $cost > 0.0 ? 'not-paid' : 'paid');

		return $listing_id;
	}

}

}