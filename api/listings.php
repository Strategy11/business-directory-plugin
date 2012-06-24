<?php
if (!class_exists('WPBDP_ListingsAPI')) {

class WPBDP_ListingsAPI {

	public function __construct() {	}

	// sets the default settings to listings created through the admin site
	public function set_default_listing_settings($post_id) {
		$fees_api = wpbdp_fees_api();		
		$payments_api = wpbdp_payments_api();

		// if has not initial transaction, create one
		if (!$payments_api->get_transactions($post_id)) {
			$payments_api->save_transaction(array(
				'amount' => 0.0,
				'payment_type' => 'initial',
				'listing_id' => $post_id,
				'processed_by' => 'admin'
			));
		}

		// assign a fee to all categories
		$post_categories = wp_get_post_terms($post_id, wpbdp_categories_taxonomy());

		foreach ($post_categories as $category) {
			if ($fee = $this->get_listing_fee_for_category($post_id, $category->term_id)) {
				// do nothing
			} else {
				// assign a fee
				$choices = $fees_api->get_fees_for_category($category->term_id);
				$this->assign_fee($post_id, $category->term_id, $choices[0], $false);
			}
		}

	}

	public function get_stickies() {
		global $wpdb;

		$stickies = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
									  		 '_wpbdp[sticky]',
									  		 'sticky'));

		return $stickies;
	}

	public function assign_fee($listing_id, $category_id, $fee_id, $charged=false) {
		global $wpdb;

		if (!has_term($category_id, wpbdp_categories_taxonomy(), $listing_id))
			return false;

		$fee = is_object($fee_id) ? $fee_id : wpbdp_fees_api()->get_fee_by_id($fee_id);
		if ($fee) {
			if ($fee->categories['all'] || count(array_intersect(wpbdp_get_parent_catids($category_id), $fee->categories['categories'])) > 0) {
				$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d", $listing_id, $category_id));

				$feerow = array(
					'listing_id' => $listing_id,
					'category_id' => $category_id,
					'fee' => serialize((array) $fee),
					'charged' => $charged ? 1 : 0
				);

				$expiration_date = $this->calculate_expiration_date(time(), $fee);
				if ($expiration_date != null)
					$feerow['expires_on'] = $expiration_date;

				$wpdb->insert($wpdb->prefix . 'wpbdp_listing_fees', $feerow);

				return true;
			}
		}

		return false;
	}

	public function get_sticky_status($listing_id) {
		if ($sticky_status = get_post_meta($listing_id, '_wpbdp[sticky]', true)) {
			return $sticky_status;
		}

		return 'normal';
	}

	public function get_thumbnail_id($listing_id) {
		if ($thumbnail_id = get_post_meta($listing_id, '_wpbdp[thumbnail_id]', true)) {
			return intval($thumbnail_id);
		} else {
			if ($images = $this->get_images($listing_id)) {
				update_post_meta($listing_id, '_wpbdp[thumbnail_id]', $images[0]->ID);
				return $images[0]->ID;
			}
		}
		
		return 0;
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

	public function get_allowed_images($listing_id) {
		$images = 0;
		
		foreach ($this->get_listing_fees($listing_id) as $fee) {
			$fee_ = unserialize($fee->fee);
			$images += intval($fee_['images']);
		}

		return $images;
	}

	public function get_payment_status($listing_id) {
		if ($payment_status = get_post_meta($listing_id, '_wpbdp[payment_status]', true))
			return $payment_status;

		return 'not-paid';
	}

	public function set_payment_status($listing_id, $status='not-paid') {
		// global $wpdb;

		// if ($last_transaction = wpbdp_payments_api()->get_last_transaction($listing_id)) {
		// 	$last_transaction->processed_on = current_time('mysql');
		// 	$last_transaction->processed_by = 'admin';
		// 	$last_transaction->status = ($status == 'paid') ? 'approved' : 'rejected';
		// 	wpbdp_payments_api()->save_transaction($last_transaction);
		// 	return true;
		// }

		// return false;

		update_post_meta($listing_id, '_wpbdp[payment_status]', $status);
		return true;
	}

	public function get_listing_fees($listing_id) {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d", $listing_id));
	}

	// effective_cost means do not include already paid fees
	public function cost_of_listing($listing_id, $effective_cost=false) {
		if (is_object($listing_id)) return $this->cost_of_listing($listing_id->ID);

		global $wpdb;

		$cost = 0.0;

		if ($fees = $wpdb->get_col($wpdb->prepare("SELECT fee FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d" . ($effective_cost ? ' AND charged = 1' : ''), $listing_id))) {
			foreach ($fees as &$fee) {
				$fee = unserialize($fee);
				$cost += floatval($fee['amount']);
			}
		}

		return round($cost, 2);
	}

	// TODO revisar que tampoco hayan transacciones pendientes
	public function is_free_listing($listing_id) {
		return $this->cost_of_listing($listing_id) == 0.0;
	}

	public function get_expiration_time($listing_id, $fee) {
		if (is_array($fee)) return $this->get_expiration_time($listing_id, (object) $fee);

		if ($fee->days == 0)
			return null;

		$start_time = get_post_time('U', false, $listing_id);
		$expire_time = strtotime(sprintf('+%d days', $fee->days), $start_time);
		return $expire_time;
	}

	public function get_listing_fee_for_category($listing_id, $catid) {
		global $wpdb;

		$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d", $listing_id, $catid));

		if ($row != null) {
			$fee = unserialize($row->fee);
			$fee['expires_on'] = $row->expires_on;
			return (object) $fee;
		}

		return null;
	}

	public function request_listing_upgrade($listing_id, &$transaction_id) {
		$sticky_status = $this->get_sticky_status($listing_id);

		if ($sticky_status == 'normal') {
			$payments_api = wpbdp_payments_api();

			if ($payments_api->payments_possible()) {
				$transaction_id = $payments_api->save_transaction(array(
					'payment_type' => 'upgrade-to-sticky',
					'listing_id' => $listing_id,
					'amount' => wpbdp_get_option('featured-price')
				));

				update_post_meta($listing_id, '_wpbdp[sticky]', 'pending');

				return true;
			}
		}

		$transaction_id = 0;
		return false;
	}

	public function calculate_expiration_time($time, $fee) {
		if ($fee->days == 0)
			return null;

		$expire_time = strtotime(sprintf('+%d days', $fee->days), $time);
		return $expire_time;
	}
			// $start_time = get_post_time('U', false, $listing_id);

	public function calculate_expiration_date($time, $fee) {
		if ($expire_time = $this->calculate_expiration_time($time, $fee))
			return date('Y-m-d H:i:s', $expire_time);
		
		return null;
	}

	public function renew_listing($renewal_id, $fee) {
		global $wpdb;

		if ($renewal = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d AND expires_on IS NOT NULL AND expires_on < %s", $renewal_id, current_time('mysql')))) {
			if (has_term($renewal->category_id, wpbdp_categories_taxonomy(), $renewal->listing_id) && ($fee->categories['all'] || in_array($renewal->category_id, $fee->categories['categories']))) {
				// register the new transaction
				$transaction_id = wpbdp_payments_api()->save_transaction(array(
					'listing_id' => $renewal->listing_id,
					'amount' => $fee->amount,
					'payment_type' => 'renewal',
					'extra_data' => serialize(array('renewal_id' => $renewal_id, 'fee' => $fee))
				));

				// set payment status to not-paid
				update_post_meta($renewal->listing_id, '_wpbdp[payment_status]', 'not-paid');
				return $transaction_id;
			}
		}

		return 0;
	}

	public function add_listing($data_, &$transaction_id=null) {
		global $wpdb;

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

		$insert_args = array(
			'post_title' => $post_title,
			'post_content' => $post_content,
			'post_excerpt' => $post_excerpt,
			'post_status' => $post_status,
			'post_type' => wpbdp_post_type(),
			'ID' => isset($data['listing_id']) ? intval($data['listing_id']) : null
		);

		if (!$editing) {
			$current_user = wp_get_current_user();

			if ($current_user->ID == 0) {
				if (wpbdp_get_option('require-login')) {
					exit;
				}
				// create user
				if ($email_field = $formfields_api->getFieldsByValidator('EmailValidator', true)) {
					$email = $formfields_api->extract($listingfields, $email_field);
					
					if (email_exists($email)) {
						$insert_args['post_author'] = get_user_by_email($email)->ID;
					} else {
						$randvalue = wpbusdirman_generatePassword(5, 2);
						$insert_args['post_author'] = wp_insert_user(array(
							'display_name' => 'Guest ' . $randvalue,
							'user_login' => 'guest_' . $randvalue,
							'user_email' => $email,
							'user_pass' => wpbusdirman_generatePassword(7, 2)
						));
					}
				} else {
					exit;
				}
			}
		}

		$listing_id = wp_insert_post($insert_args);

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

		foreach ($post_categories as $catid) {
			$fee = (array) (isset($data['fees'][$catid]) ? $data['fees'][$catid] : wpbdp_fees_api()->get_free_fee());
			$fee['category_id'] = $catid;
			unset($fee['categories'], $fee['extra_data']);

			if (isset($fee['_nocharge']) && $fee['_nocharge'] == true) {
				$wpdb->update($wpdb->prefix . 'wpbdp_listing_fees', array('charged' => 0), array('listing_id' => $listing_id,
																								 'category_id' => $catid));
			} else {
				$this->assign_fee($listing_id, $catid, $fee['id'], true);
			}
		}
		if ($post_categories)
			$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id NOT IN (" . join(',', $post_categories) . ")", $listing_id) );

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