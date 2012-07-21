<?php
/*
 * Fees/Payment API
 */

if (!class_exists('WPBDP_PaymentsAPI')) {

class WPBDP_FeesAPI {

    public function __construct() { }

    public static function get_free_fee() {
        $fee = new StdClass();
        $fee->id = 0;
        $fee->label = _x('Free Listing', 'fees-api', 'WPBDM');
        $fee->amount = 0.0;
        $fee->images = intval(wpbdp_get_option('free-images'));
        $fee->days = intval(wpbdp_get_option('listing-duration'));
        $fee->categories = array('all' => true, 'categories' => array());
        $fee->extra_data = null;

        return $fee;
    }

    private function normalize(&$fee) {
        $fee->categories = unserialize($fee->categories);
    }

    public function get_fees_for_category($catid) {
        $fees = array();

        if (wpbdp_payments_api()->payments_possible()) {
            $parent_categories = wpbdp_get_parent_categories($catid);
            array_walk($parent_categories, create_function('&$x', '$x = intval($x->term_id);'));

            foreach ($this->get_fees() as $fee) {
                if ($fee->categories['all']) {
                    $fees[] = $fee;
                } else {
                    foreach ($fee->categories['categories'] as $fee_catid) {
                        if (in_array($fee_catid, $parent_categories)) {
                            $fees[] = $fee;
                            break;
                        }
                    }
                }
            }
        }

        if (!$fees)
            $fees[] = $this->get_free_fee();

        return $fees;
    }

    public function get_fees($categories=null) {
        global $wpdb;
        
        if (isset($categories)) {
            $fees = array();

            foreach ($categories as $catid) {
                $category_fees = $this->get_fees_for_category($catid);
                $fees[$catid] = $category_fees;
            }

            return $fees;
        } else {
            $fees = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpbdp_fees");
            
            foreach ($fees as &$fee)
                $this->normalize($fee);

            return $fees;
        }
    }

    public function get_fee_by_id($id) {
        global $wpdb;

        if ($id == 0)
            return $this->get_free_fee();

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

        if (!isset($fee['days']) || !is_int($fee['days']) || intval($fee['days']) < 0) {
            $errors[] = _x('Fee listing run must be a non-negative integer.', 'fees-api', 'WPBDM');
        } else {
            // limit 'duration' because of TIMESTAMP limited range (issue #157).
            // FIXME: this is not a long-term fix. we should move to DATETIME to avoid this entirely.
            if ($fee['days'] > 3650) {
                $errors[] = _x('Fee listing duration must be a number less than 10 years (3650 days).', 'fees-api', 'WPBDM');
            }
        }

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

        if (!$fee['categories']['categories'])
            $fee['categories']['all'] = true;

        // TODO delete unnecessary categories: if a parent of a category is in the list, remove the category

        $fee['categories'] = serialize($fee['categories']);

        if ($this->is_valid_fee($fee, $errors)) {
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

class WPBDP_PaymentsAPI {

    public function __construct() {
        $this->gateways = array();
    }

    public function register_gateway($id, $options=array()) {
        $default_options = array('name' => $id,
                                 'html_callback' => null,
                                 'process_callback' => null);
        $options = array_merge($default_options, $options);

        if (array_key_exists($id, $this->gateways))
            return false;

        $gateway = new StdClass();
        $gateway->id = $id;
        $gateway->name = $options['name'];
        $gateway->html_callback = $options['html_callback'];
        $gateway->process_callback = $options['process_callback'];

        $this->gateways[$gateway->id] = $gateway;
    }

    public function get_available_methods() {
        $gateways = array();

        if (wpbdp_get_option('payments-on')) {
            foreach ($this->gateways as &$gateway) {
                if (wpbdp_get_option($gateway->id))
                    $gateways[] = $gateway;
            }
        }

        return $gateways;
    }

    public function payments_possible() {
        return count($this->get_available_methods()) > 0;
    }

    public function get_registered_methods() {
        return $this->gateways;
    }

    public function has_gateway($gateway) {
        return array_key_exists($gateway, $this->gateways);
    }

    public function render_payment_page($options_) {
        $options = array_merge(array(
            'title' => _x('Checkout', 'payments-api', 'WPBDM'),
            'item_text' => _x('Pay %1$s through %2$s', 'payments-api', 'WPBDM')
        ), $options_);

        $transaction = $this->get_transaction($options['transaction_id']);

        return wpbdp_render('payment-page', array(
            'title' => $options['title'],
            'item_text' => $options['item_text'],
            'transaction' => $transaction,
            'payment_methods' => $this->get_available_methods()
            ));
    }

    public function get_uri_id_for_transaction($transaction) {
        return urlencode(base64_encode(sprintf('%d.%s', $transaction->id, strtotime($transaction->created_on))));
    }

    public function get_transaction_from_uri_id() {
        if (!isset($_GET['tid']))
            return null;

        $uri_id_plain = explode('.', urldecode(base64_decode($_GET['tid'])));
        $transaction_id = $uri_id_plain[0];
        $transaction_date = $uri_id_plain[1];

        // check transaction date is valid
        if ($transaction = $this->get_transaction($transaction_id)) {
            if (strtotime($transaction->created_on) == $transaction_date)
                return $transaction;
        }

        return null;
    }

    public function get_processing_url($gateway, $transaction=null) {
        $args = array('action' => 'payment-process', 'gateway' => $gateway);

        if ($transaction)
            $args['tid'] = $this->get_uri_id_for_transaction($transaction);

        return add_query_arg($args, wpbdp_get_page_link('main'));
    }

    public function in_test_mode() {
        return wpbdp_get_option('payments-test-mode');
    }

    private function act_on_transaction_save($transaction) {
        global $wpdb;

        if ($transaction->id == $this->get_last_transaction($transaction->listing_id)->id) {
            update_post_meta($transaction->listing_id, '_wpbdp[payment_status]', $transaction->status == 'approved' ? 'paid' : 'not-paid');
        }

        if ($transaction->status == 'approved') {
            if ($transaction->payment_type == 'upgrade-to-sticky') {
                update_post_meta($transaction->listing_id, '_wpbdp[sticky]', 'sticky');
            } elseif ($transaction->payment_type == 'renewal') {
                $listingsapi = wpbdp_listings_api();

                $extradata = unserialize($transaction->extra_data);
                $renewalinfo = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d", $extradata['renewal_id']));
                
                $listingsapi->assign_fee($transaction->listing_id, $renewalinfo->category_id, $extradata['fee'], true);

                wp_update_post(array('post_status' => 'publish', 'ID' => $transaction->listing_id));
            }
        } elseif ($transaction->status == 'rejected') {
            if ($transaction->payment_type == 'upgrade-to-sticky') {
                delete_post_meta($transaction->listing_id, '_wpbdp[sticky]');
            }
        }
    }

    public function save_transaction($trans_) {
        global $wpdb;

        $trans = is_object($trans_) ? (array) $trans_ : $trans_;

        if (isset($trans['payerinfo']))
            $trans['payerinfo'] = serialize($trans['payerinfo']);

        if (isset($trans['extra_data']))
            $trans['extra_data'] = serialize($trans['extra_data']);

        if (!isset($trans['id'])) {
            $current_date = date('Y-m-d H:i:s', time());
            $trans['amount'] = floatval($trans['amount']);
            
            if (!isset($trans['amount']))
                $trans['amount'] = 0.0;

            if (!isset($trans['status']))
                $trans['status'] = $trans['amount'] > 0.0 ? 'pending' : 'approved';

            if (!isset($trans['created_on']))
                $trans['created_on'] = $current_date;

            if (!isset($trans['processed_on']) && $trans['amount'] == 0.0)
                $trans['processed_on'] = $current_date;

            if (!isset($trans['processed_by']) && $trans['amount'] == 0.0)
                $trans['processed_by'] = 'system';

            if ($wpdb->insert("{$wpdb->prefix}wpbdp_payments", $trans)) {
                $trans_id = $wpdb->insert_id;
                $this->act_on_transaction_save($this->get_transaction($trans_id));
                return $trans_id;
            }
        } else {
            $wpdb->update("{$wpdb->prefix}wpbdp_payments", $trans, array('id' => $trans['id']));
            $this->act_on_transaction_save($this->get_transaction($trans['id']));

            return $trans['id'];
        }

        return 0;
    }

    public function get_transaction($transaction_id) {
        global $wpdb;

        if ($trans = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_payments WHERE id = %d", $transaction_id))) {
            if ($trans->payerinfo) {
                $trans->payerinfo = unserialize($trans->payerinfo);
            } else {
                $trans->payerinfo = array('name' => '',
                                          'email' => '');
            }

            if ($trans->extra_data) {
                $trans->extra_data = unserialize($trans->extra_data);
            } else {
                $trans->extra_data = array();
            }

            return $trans;            
        }

        return null;
    }

    public function get_transactions($listing_id) {
        global $wpdb;

        $transactions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d", $listing_id));

        foreach ($transactions as &$trans) {
            $trans->payerinfo = unserialize($trans->payerinfo);
            $trans->extra_data = unserialize($trans->extra_data);

            if (!$trans->payerinfo)
                $trans->payerinfo = array('name' => '', 'email' => '');
        }

        return $transactions;
    }

    public function get_last_transaction($listing_id) {
        global $wpdb;

        $transaction = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d ORDER BY id DESC LIMIT 1", $listing_id));

        if ($transaction) {
            $transaction->payerinfo = unserialize($transaction->payerinfo);
            $transaction->extra_data = unserialize($transaction->extra_data);            

            if (!$transaction->payerinfo)
                $transaction->payerinfo = array('name' => '', 'email' => '');            

            return $transaction;
        }

        return null;
    }

    public function process_payment($gateway_id) {
        if (!array_key_exists($gateway_id, $this->gateways))
            return;

        $getvars = $_GET;
        unset($getvars['action']);
        unset($getvars['page_id']);

        return call_user_func($this->gateways[$gateway_id]->process_callback, array_merge($_POST, $getvars));
    }

}

}