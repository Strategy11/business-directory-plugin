<?php

class WPBDP_Settings {

	const PREFIX = 'wpbdp-';

	public function __construct() {
		$this->groups = array();
		$this->settings = array();

		add_action('init', array($this, '_register_settings'));
	}


	public function _register_settings() {
		/* General settings */
		$g = $this->add_group('general', __('General'));
		$s = $this->add_section($g, 'permalink', __('Permalink Settings'));
		$this->add_setting($s, 'permalinks-directory-slug', __('Directory Listings Slug'), 'text', WPBDP_Plugin::POST_TYPE);
		$this->add_setting($s, 'permalinks-category-slug', __('Categories Slug'), 'text', WPBDP_Plugin::POST_TYPE_CATEGORY);
		$this->add_setting($s, 'permalinks-tags-slug', __('Tags Slug'), 'text', WPBDP_Plugin::POST_TYPE_TAGS);

		$s = $this->add_section($g, 'recaptcha', __('ReCaptcha Settings'));
		$this->add_setting($s, 'recaptcha-on', __('Turn on reCAPTCHA?'), 'boolean', true);
		$this->add_setting($s, 'recaptcha-public-key', __('reCAPTCHA Public Key'));
		$this->add_setting($s, 'recaptcha-private-key', __('reCAPTCHA Private Key'));

		$s = $this->add_section($g, 'misc', __('Miscellaneous Settings'));
		$this->add_setting($s, 'hide-buy-module-buttons', __('Hide all buy plugin module buttons?'), 'boolean', false);
		$this->add_setting($s, 'hide-tips', __('Hide tips for use and other information?'), 'boolean', false);
		$this->add_setting($s, 'credit-author', __('Give credit to plugin author?'), 'boolean', true);

		/* Listings settings */
		$g = $this->add_group('listings', __('Listings'));
		$s = $this->add_section($g, 'general', __('General Settings'));
		$this->add_setting($s, 'listing-duration', __('Listing duration for no-free sites (in days)'), 'text', '365');
		$this->add_setting($s, 'show-contact-form', __('Include listing contact form on listing pages?'), 'boolean', true);
		$this->add_setting($s, 'show-comment-form', __('Include comment form on listing pages?'), 'boolean', false);
		$this->add_setting($s, 'listing-renewal', __('Turn on listing renewal option?'), 'boolean', true);
		$this->add_setting($s, 'use-default-picture', __('Use default picture for listings with no picture?'), 'boolean', true);
		$this->add_setting($s, 'show-listings-under-categories', __('Show listings under categories on main page?'), 'boolean', false);
		$this->add_setting($s, 'override-email-blocking', __('Override email Blocking?'), 'boolean', false);
		$this->add_setting($s, 'status-on-uninstall', __('Status of listings upon uninstalling plugin'), 'choice', 'draft', '',
						   array('choices' => array('draft', 'trash')));
		$this->add_setting($s, 'deleted-status', __('Status of deleted listings'), 'choice', 'draft', '',
						   array('choices' => array('draft', 'trash')));

		$s = $this->add_section($g, 'post/category', __('Post/Category Settings'));
		$this->add_setting($s, 'new-post-status', __('Default new post status'), 'choice', 'pending', '',
						   array('choices' => array('publish', 'pending'))
						   );
		$this->add_setting($s, 'edit-post-status', __('Edit post status'), 'choice', 'publish', '',
						   array('choices' => array('publish', 'pending')));
		$this->add_setting( $s, 'categories-order-by', __('Order categories list by'), 'choice', 'name', '',
						   array('choices' => array('name', 'ID', 'slug', 'count', 'term_group')));
		$this->add_setting( $s, 'categories-sort', __('Sort order for categories'), 'choice', 'ASC', '',
						   array('choices' => array(array('ASC', __('Ascending')), array('DESC', __('Descending')))));
		$this->add_setting($s, 'show-category-post-count', __('Show category post count?'), 'boolean', true);
		$this->add_setting($s, 'hide-empty-categories', __('Hide empty categories?'), 'boolean', true);
		$this->add_setting($s, 'show-only-parent-categories', __('Show only parent categories in category list?'), 'boolean', false);
		$this->add_setting($s, 'listings-order-by', __('Order directory listings by'), 'choice', 'title', '',
						  array('choices' => array('date', 'title', 'id', 'author', 'modified')));
		$this->add_setting( $s, 'listings-sort', __('Sort directory listings by'), 'choice', 'ASC',
						   __('Ascending for ascending order A-Z, Descending for descending order Z-A'),
						   array('choices' => array(array('ASC', __('Ascending')), array('DESC', __('Descending')))));

		$s = $this->add_section($g, 'featured', __('Featured (Sticky) listing settings'));
		$this->add_setting($s, 'featured-on', __('Offer sticky listings?'), 'boolean', true);
		$this->add_setting($s, 'featured-price', __('Sticky listing price'), 'text', '39.99');
		$this->add_setting($s, 'featured-description', __('Sticky listing page description text'), 'text',
						   __('You can upgrade your listing to featured status. Featured listings will always appear on top of regular listings.'));

		/* Payment settings */
		$g = $this->add_group('payment', __('Payment'));
		$s = $this->add_section($g, 'general', __('Payment Settings'));
		$this->add_setting($s, 'payments-on', __('Turn On payments?'), 'boolean', false);
		$this->add_setting($s, 'payments-test-mode', __('Put payment gateways in test mode?'), 'boolean', true);

		// PayPal currency codes from https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_currency_codes
		$this->add_setting($s, 'currency', __('Currency Code'), 'choice', 'USD', '',
							array('choices' => array(
								array('AUD', __('Australian Dollar (AUD)')),
								array('CAD', __('Canadian Dollar (CAD)')),
								array('CZK', __('Czech Koruna (CZK)')),
								array('DKK', __('Danish Krone (DKK)')),
								array('Euro', __('Euro (EUR)')),
								array('HKD', __('Hong Kong Dollar (HKD)')),
								array('HUF', __('Hungarian Forint (HUF)')),
								array('ILS', __('Israeli New Shequel (ILS)')),
								array('JPY', __('Japanese Yen (JPY)')),
								array('MXN', __('Mexican Peso (MXN)')),
								array('NOK', __('Norwegian Krone (NOK)')),
								array('NZD', __('New Zelland Dollar (NZD)')),
								array('PHP', __('Philippine Peso (PHP)')),
								array('PLN', __('Polish Zloty (PLN)')),
								array('GBP', __('Pound Sterling (GBP)')),
								array('SGD', __('Singapore Dollar (SGD)')),
								array('SEK', __('Swedish Krona (SEK)')),
								array('CHF', __('Swiss Franc (CHF)')),
								array('TWD', __('Taiwan Dollar (TWD)')),
								array('THB', __('Thai Baht (THB)')),
								array('USD', __('U.S. Dollar')),
							)));
		$this->add_setting($s, 'currency-symbol', __('Currency Symbol'), 'text', '$');
		$this->add_setting($s, 'payment-message', __('Thank you for payment message'), 'text',
						__('Thank you for your payment. Your payment is being verified and your listing reviewed. The verification and review process could take up to 48 hours.'));

		$s = $this->add_section($g, 'googlecheckout', __('Google Checkout Settings'));
		$this->add_setting($s, 'googlecheckout', __('Activate Google Checkout?'), 'boolean', false);
		$this->add_setting($s, 'googlecheckout-merchant', __('Google Checkout Merchant ID'));
		$this->add_setting($s, 'googlecheckout-seller', __('Google Checkout Seller ID'));

		$s = $this->add_section($g, 'paypal', __('PayPal Gateway Settings'));
		$this->add_setting($s, 'paypal', __('Activate Paypal?'), 'boolean', false,
						   __('Will only work when the PayPal module is installed'));
		$this->add_setting($s, 'paypal-business-email', __('PayPal Business Email'));

		$s = $this->add_section($g, '2checkout', __('2Checkout Gateway Settings'));
		$this->add_setting($s, '2checkout', __('Activate 2Checkout?'), 'boolean', false,
						   __('Will only work when the 2checkout module is installed'));
		$this->add_setting($s, '2checkout-seller', __('2Checkout seller/vendor ID'));

		/* Registration settings */
		$g = $this->add_group('registration', __('Registration'));
		$s = $this->add_section($g, 'registration', __('Registration Settings'));
		$this->add_setting($s, 'require-login', __('Require login?'), 'boolean', true);
		$this->add_setting($s, 'login-url', __('Login URL'), 'text', wp_login_url());
		$this->add_setting($s, 'registration-url', __('Registration URL'), 'text', wp_login_url());

		/* Image settings */
		$g = $this->add_group('image', __('Image'));
		$s = $this->add_section($g, 'image', __('Image Settings'));
		$this->add_setting($s, 'allow-images', __('Allow images?'), 'boolean', true);
		$this->add_setting($s, 'free-images', __('Number of free images'), 'text', '2');
		$this->add_setting($s, 'show-thumbnail', __('Show Thumbnail on main listings page?'), 'boolean', true);
		$this->add_setting($s, 'image-max-filesize', __('Max Image File Size'), 'text', '100000');
		$this->add_setting($s, 'image-min-filesize', __('Minimum Image File Size'), 'text', '300');
		$this->add_setting($s, 'image-max-width', __('Max image width'), 'text', '500');
		$this->add_setting($s, 'image-max-height', __('Max image height'), 'text', '500');
		$this->add_setting($s, 'thumbnail-width', __('Thumbnail width'), 'text', '120');

	}

	public function add_group($slug, $name) {
		$group = new StdClass();
		$group->wpslug = self::PREFIX . $slug;
		$group->slug = $slug;
		$group->name = $name;
		$group->sections = array();

		$this->groups[$slug] = $group;

		return $slug;
	}

	public function add_section($group_slug, $slug, $name) {
		$section = new StdClass();
		$section->name = $name;
		$section->slug = $slug;
		$section->settings = array();

		$this->groups[$group_slug]->sections[$slug] = $section;

		return "$group_slug:$slug";
	}

	public function add_setting($section_key, $name, $label, $type='text', $default=null, $help_text='', $args=array()) {
		list($group, $section) = explode(':', $section_key);

		if (!$group || !$section)
			return false;

		if ( isset($this->groups[$group]) && isset($this->groups[$group]->sections[$section]) ) {
			$_default = $default;
			if (is_null($_default)) {
				switch ($type) {
					case 'text':
					case 'choices':
						$_default = '';
						break;
					case 'boolean':
						$_default = false;
						break;
					default:
						$_default = null;
						break;
				}
			}

			$setting = new StdClass();
			$setting->name = $name;
			$setting->label = $label;
			$setting->help_text = $help_text;
			$setting->default = $_default;
			$setting->type = $type;
			$setting->args = $args;

			$this->groups[$group]->sections[$section]->settings[$name] = $setting;
		}

		if (!isset($this->settings[$name])) {
			$this->settings[$name] = $setting;
		}

		return true;
	}

	public function get($name, $ifempty=null) {
		$value =  get_option(self::PREFIX . $name, null);

		if (is_null($value)) {
			$default_value = isset($this->settings[$name]) ? $this->settings[$name]->default : null;			
			return $default_value;
		}

		if (!is_null($ifempty) && empty($value))
			return $ifempty;

		return $value;
	}

	public function set($name, $value, $onlyknown=true) {
		$name = strtolower($name);

		if ($onlynown && !isset($this->settings[$name]))
			return false;

		if (isset($this->settings[$name]) && $this->settings[$name]->type == 'boolean') {
			$value = (boolean) intval($value);
		}

		// wpbdp_debug("Setting $name = $value");
		update_option(self::PREFIX . $name, $value);

		return true;
	}

	/* emulates get_wpbusdirman_config_options() in version 2.0 until
	 * all deprecated code has been ported. */
	public function pre_2_0_compat_get_config_options() {
		$legacy_options = array();

		foreach ($this->pre_2_0_options() as $old_key => $new_key) {
			$setting_value = $this->get($new_key);

			if ($new_key == 'googlecheckout' || $new_key == 'paypal' || $new_key == '2checkout')
				$setting_value = !$setting_value;

			if ($this->settings[$new_key]->type == 'boolean') {
				$setting_value = $setting_value == true ? 'yes' : 'no';
			}

			$legacy_options[$old_key] = $setting_value;
		}

		return $legacy_options;
	}



	public function reset_defaults() {
		foreach ($this->settings as $setting) {
			delete_option(self::PREFIX . $setting->name);
		}
	}

	/*
	 * admin
	 */
	public function _setting_text($args) {
		$setting = $args['setting'];
		$value = $this->get($setting->name);

		if (isset($args['use_textarea']) || strlen($value) > 50) {
			$html  = '<textarea id="' . $setting->name . '" name="' . self::PREFIX . $setting->name . '" cols="50" rows="2">';
			$html .= esc_attr($value);
			$html .= '</textarea>';
		} else {
			$html = '<input type="text" id="' . $setting->name . '" name="' . self::PREFIX . $setting->name . '" value="' . $value . '" />';
		}

		$html .= '<span class="description">' . $setting->help_text . '</span>';

		echo $html;
	}

	public function _setting_boolean($args) {
		$setting = $args['setting'];

		$value = (boolean) $this->get($setting->name);

		$html  = '<label for="' . $setting->name . '">';
		$html .= '<input type="checkbox" id="' .$setting->name . '" name="' . self::PREFIX . $setting->name . '" value="1" '
				  . ($value ? 'checked="checked"' : '') . '/>';
		$html .= '<span class="description">' . $setting->help_text . '</span>';
		$html .= '</label>';

		echo $html;
	}

	public function _setting_choice($args) {
		$setting = $args['setting'];
		$choices = $args['choices'];

		$value = $this->get($setting->name);

		$html = '<select id="' . $setting->name . '" name="' . self::PREFIX . $setting->name . '">';
		
		foreach ($choices as $ch) {
			$opt_label = is_array($ch) ? $ch[1] : $ch;
			$opt_value = is_array($ch) ? $ch[0] : $ch;

			$html .= '<option value="' . $opt_value . '"' . ($value == $opt_value ? ' selected="selected"' : '') . '>'
					 		. $opt_label . '</option>';
		}

		$html .= '</select>';
		$html .= '<span class="description">' . $setting->help_text . '</span>';

		echo $html;
	}

	public function register_in_admin() {
		foreach ($this->groups as $group) {
			foreach ($group->sections as $section) {
				add_settings_section($section->slug, $section->name, create_function('', ';'), $group->wpslug);

				foreach ($section->settings as $setting) {
					register_setting($group->wpslug, self::PREFIX . $setting->name);
					add_settings_field(self::PREFIX . $setting->name, $setting->label,
									   array($this, '_setting_' . $setting->type),
									   $group->wpslug,
									   $section->slug,
									   array_merge($setting->args, array('label_for' => $setting->name, 'setting' => $setting))
									   );
				}
			}
		}
	}

	/* upgrade from old-style settings to new options */
	public function pre_2_0_options() {
		static $option_translations = array(
			'wpbusdirman_settings_config_18' => 'listing-duration',
			'wpbusdirman_settings_config_25' => 'hide-buy-module-buttons',
			'wpbusdirman_settings_config_26' => 'hide-tips',
			'wpbusdirman_settings_config_27' => 'show-contact-form',
			'wpbusdirman_settings_config_36' => 'show-comment-form',
			'wpbusdirman_settings_config_34' => 'credit-author',
			'wpbusdirman_settings_config_38' => 'listing-renewal',
			'wpbusdirman_settings_config_39' => 'use-default-picture',
			'wpbusdirman_settings_config_44' => 'show-listings-under-categories',
			'wpbusdirman_settings_config_45' => 'override-email-blocking',
			'wpbusdirman_settings_config_46' => 'status-on-uninstall',
			'wpbusdirman_settings_config_47' => 'deleted-status',
			'wpbusdirman_settings_config_3' => 'require-login',
			'wpbusdirman_settings_config_4' => 'login-url',
			'wpbusdirman_settings_config_5' => 'registration-url',
			'wpbusdirman_settings_config_1' => 'new-post-status',
			'wpbusdirman_settings_config_19' => 'edit-post-status',
			'wpbusdirman_settings_config_7' => 'categories-order-by',
			'wpbusdirman_settings_config_8' => 'categories-sort',
			'wpbusdirman_settings_config_9' => 'show-category-post-count',
			'wpbusdirman_settings_config_10' => 'hide-empty-categories',
			'wpbusdirman_settings_config_48' => 'show-only-parent-categories',
			'wpbusdirman_settings_config_52' => 'listings-order-by',
			'wpbusdirman_settings_config_53' => 'listings-sort',
			'wpbusdirman_settings_config_6' => 'allow-images',
			'wpbusdirman_settings_config_2' => 'free-images',
			'wpbusdirman_settings_config_11' => 'show-thumbnail',
			'wpbusdirman_settings_config_13' => 'image-max-filesize',
			'wpbusdirman_settings_config_14' => 'image-min-filesize',
			'wpbusdirman_settings_config_15' => 'image-max-width',
			'wpbusdirman_settings_config_16' => 'image-max-height',
			'wpbusdirman_settings_config_17' => 'thumbnail-width',
			'wpbusdirman_settings_config_20' => 'currency',
			'wpbusdirman_settings_config_12' => 'currency-symbol',
			'wpbusdirman_settings_config_21' => 'payments-on',
			'wpbusdirman_settings_config_22' => 'payments-test-mode',
			'wpbusdirman_settings_config_37' => 'payment-message',
			'wpbusdirman_settings_config_23' => 'googlecheckout-merchant',
			'wpbusdirman_settings_config_24' => 'googlecheckout-seller',
			'wpbusdirman_settings_config_40' => 'googlecheckout',
			'wpbusdirman_settings_config_35' => 'paypal-business-email',
			'wpbusdirman_settings_config_41' => 'paypal',
			'wpbusdirman_settings_config_42' => '2checkout-seller',
			'wpbusdirman_settings_config_43' => '2checkout',
			'wpbusdirman_settings_config_31' => 'featured-on',
			'wpbusdirman_settings_config_32' => 'featured-price',
			'wpbusdirman_settings_config_33' => 'featured-description',
			'wpbusdirman_settings_config_28' => 'recaptcha-public-key',
			'wpbusdirman_settings_config_29' => 'recaptcha-private-key',
			'wpbusdirman_settings_config_30' => 'recaptcha-on',
			'wpbusdirman_settings_config_49' => 'permalinks-directory-slug',
			'wpbusdirman_settings_config_50' => 'permalinks-category-slug',
			'wpbusdirman_settings_config_51' => 'permalinks-tags-slug'
		);
		return $option_translations;
	}

	public function upgrade_options() {
		if (!$this->settings)
			$this->_register_settings();

		$translations = $this->pre_2_0_options();

		if ($old_options = get_option('wpbusdirman_settings_config')) {
			foreach ($old_options as $option) {
				$id = strtolower($option['id']);
				$type = strtolower($option['type']);
				$value = $option['std'];

				if ($type == 'titles' || empty($value))
					continue;

				if ($id == 'wpbusdirman_settings_config_40') {
					$this->set('googlecheckout', $value == 'yes' ? false : true);
				} elseif ($id == 'wpbusdirman_settings_config_41') {
					$this->set('paypal', $value == 'yes' ? false : true);
				} elseif ($id == 'wpbusdirman_settings_config_43') {
					$this->set('2checkout', $value == 'yes' ? false : true);
				} else {
					$newsetting = $this->settings[$translations[$id]];

					switch ($newsetting->type) {
						case 'boolean':
							$this->set($newsetting->name, $value == 'yes' ? true : false);
							break;
						case 'choice':
						case 'text':
						default:
							$this->set($newsetting->name, $value);
							break;
					}
				}

			}

			delete_option('wpbusdirman_settings_config');
		}
	}


	
}