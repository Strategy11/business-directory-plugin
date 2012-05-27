<?php
if (!class_exists('WPBDP_ListingsAPI')) {

class WPBDP_ListingsAPI {

	public function __construct() { }

	public function cost_of_listing($listing_id) {
		if (is_object($listing_id)) return $this->cost_of_listing($listing_id->ID);

		$fees = get_post_meta($listing_id, '_wpbdp[fees]', true);
		$cost = 0.0;

		foreach ($fees as $fee)
			$cost += floatval($fee['amount']);

		return round($cost, 2);
	}

	public function add_listing($data_) {
		$data = is_object($data_) ? (array) $data_ : $data_;

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

		$post_status = isset($data['id']) ? wpbdp_get_option('edit-post-status') : wpbdp_get_option('new-post-status');

		$listing_id = wp_insert_post(array(
			'post_title' => $post_title,
			'post_content' => $post_content,
			'post_excerpt' => $post_excerpt,
			'post_status' => $post_status,
			'post_type' => wpbdp_post_type(),
			'ID' => isset($data['id']) ? intval($data['id']) : null

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

			add_post_meta($listing_id, '_wpbdp[expired][' . $catid . ']', 0);
		}

		add_post_meta($listing_id, '_wpbdp[fees]', $fee_information, true);

		// register initial payment info
		$cost = $this->cost_of_listing($listing_id);
		add_post_meta($listing_id, '_wpbdp[payment]', array('status' => $cost > 0.0 ? 'not-paid' : 'paid',
															'details' => array()));

		return $listing_id;
	}

}

}