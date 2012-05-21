<?php
/*
 * Fees/Payment API
 */

if (!class_exists('WPBDP_PaymentAPI')) {

class WPBDP_FeesAPI {

    public function __construct() { }

    private function normalize(&$fee) {
        $fee->categories = unserialize($fee->categories);
    }

    public function get_fees() {
        global $wpdb;
        
        $fees = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpbdp_fees");
        
        foreach ($fees as &$fee)
            $this->normalize($fee);

        return $fees;
    }

    public function get_fee_by_id($id) {
        global $wpdb;

        if ($fee = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_fees WHERE id = %d", $id))) {
            $this->normalize($fee);
            return $fee;
        }

        return null;
    }

    public function is_valid_fee($fee=array(), &$errors=null) {
        if (!is_array($errors)) $errors = array();

        if (!isset($fee['label']) || trim($fee['label']) == '')
            $errors[] = _x('Fee label is required.', 'fees-api', 'WPBDM');

        if (!isset($fee['amount']) || trim($fee['amount']) == '' || !is_numeric($fee['amount']) || floatval($fee['amount']) < 0.0)
            $errors[] = _x('Fee amount must be a non-negative decimal number.', 'fees-api', 'WPBDM');

        if (!isset($fee['categories']))
            $errors[] = _x('Fee must apply to at least one category.', 'fees-api', 'WPBDM');

        if (isset($fee['categories']) && !isset($fee['categories']['all']) && !isset($fee['categories']['categories']))
            $errors[] = _x('Fee must apply to at least one category.', 'fees-api', 'WPBDM');

        if (!isset($fee['images']) || !is_int($fee['images']) || intval($fee['images']) < 0)
            $errors[] = _x('Fee allowed images must be a non-negative integer.', 'fees-api', 'WPBDM');

        if (!isset($fee['days']) || !is_int($fee['days']) || intval($fee['days']) < 0)
            $errors[] = _x('Fee listing run must be a non-negative integer.', 'fees-api', 'WPBDM');        

        if ($errors)
            return false;

        return true;
    }

    public function add_or_update_fee($fee_=array(), &$errors = null) {
        global $wpdb;

        $errors = array();

        $fee = $fee_;

        $fee['images'] = intval($fee['images']);
        $fee['days'] = intval($fee['days']);
        $fee['categories'] = array();
        $fee['categories']['all'] = intval(wpbdp_getv($fee_['categories'], 'all', false));
        $fee['categories']['categories'] = array_map('intval', wpbdp_getv($fee_['categories'], 'categories', array()));

        if (in_array(0, $fee['categories']['categories']))
            $fee['categories']['all'] = true;

        if ($fee['categories']['all'])
            $fee['categories']['categories'] = array();

        // TODO delete unnecessary categories: if a parent of a category is in the list, remove the category

        $fee['categories'] = serialize($fee['categories']);

        if ($this->is_valid_fee($fee, &$errors)) {
            if (isset($fee['id'])) {
                return $wpdb->update("{$wpdb->prefix}wpbdp_fees", $fee, array('id' => $fee['id'])) !== false;
            } else {
                return $wpdb->insert("{$wpdb->prefix}wpbdp_fees", $fee);
            }
        }

        return false;
    }

    public function delete_fee($id) {
        if (is_object($id)) return $this->delete_fee((array) $id);
        if (is_array($id)) return $this->delete_fee($id['id']);

        global $wpdb;

        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wpbdp_fees WHERE id = %d", $id));

        return true;
    }    

    public function _update_to_2_3() {
        global $wpdb;

        $count = $wpdb->get_var(
            sprintf("SELECT COUNT(*) FROM {$wpdb->prefix}options WHERE option_name LIKE '%%%s%%'", 'wpbusdirman_settings_fees_label_'));

        for ($i = 1; $i <= $count; $i++) {
            $label = get_option('_settings_fees_label_' . $i, get_option('wpbusdirman_settings_fees_label_' . $i));
            $amount = get_option('_settings_fees_amount' . $i, get_option('wpbusdirman_settings_fees_amount_' . $i, '0.00'));
            $days = intval( get_option('_settings_fees_increment_' . $i, get_option('wpbusdirman_settings_fees_increment_' . $i, 0)) );
            $images = intval( get_option('_settings_fees_images_' . $i, get_option('wpbusdirman_settings_fees_images_' . $i, 0)) );
            $categories = get_option('_settings_fees_categories_' . $i, get_option('wpbusdirman_settings_fees_categories_' . $i, ''));

            $newfee = array();
            $newfee['label'] = $label;
            $newfee['amount'] = $amount;
            $newfee['days'] = $days;
            $newfee['images'] = $images;

            $category_data = array('all' => false, 'categories' => array());
            if ($categories == '0') {
                $category_data['all'] = true;
            } else {
                foreach (explode(',', $categories) as $category_id) {
                    $category_data['categories'][] = intval($category_id);
                }
            }
            
            $newfee['categories'] = serialize($category_data);

            if ($wpdb->insert($wpdb->prefix . 'wpbdp_fees', $newfee)) {
                $new_id = $wpdb->insert_id;

                $query = $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s AND {$wpdb->postmeta}.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                                         $new_id, '_wpbdp_listingfeeid', $i, wpbdp_post_type());
                $wpdb->query($query);

                foreach (array('label', 'amount', 'increment', 'images', 'categories') as $k) {
                    delete_option('wpbusdirman_settings_fees_' . $k . '_' . $i);
                    delete_option('_settings_fees_' . $k . '_' . $i);
                }
            }

        }
    }

}

class WPBDP_PaymentAPI {


}

}