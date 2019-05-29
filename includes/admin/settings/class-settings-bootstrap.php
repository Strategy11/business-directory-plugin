<?php
/**
 * @package WPBDP\Admin\Settings
 */

// phpcs:disable Generic.Formatting
// phpcs:disable PEAR.NamingConventions
// phpcs:disable PEAR.Functions
// phpcs:disable Squiz.Commenting
// phpcs:disable Squiz.PHP
// phpcs:disable Squiz.WhiteSpace
// phpcs:disable WordPress.Arrays
// phpcs:disable WordPress.PHP
// phpcs:disable WordPress.VIP
// phpcs:disable WordPress.WhiteSpace
// phpcs:disable WordPress.WP

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
final class WPBDP__Settings__Bootstrap {

    public static function register_initial_groups() {
        wpbdp_register_settings_group( 'general', _x( 'General', 'settings', 'WPBDM' ) );

        wpbdp_register_settings_group( 'listings', _x( 'Listings', 'settings', 'WPBDM' ) );
        wpbdp_register_settings_group( 'listings/main', _x( 'General Settings', 'settings', 'WPBDM' ), 'listings' );

        wpbdp_register_settings_group( 'email', _x( 'E-Mail', 'settings', 'WPBDM' ) );
        wpbdp_register_settings_group( 'email/main', _x( 'General Settings', 'settings', 'WPBDM' ), 'email' );

        wpbdp_register_settings_group( 'payment', _x( 'Payment', 'settings', 'WPBDM' ) );
        wpbdp_register_settings_group( 'payment/main', _x( 'General Settings', 'settings', 'WPBDM' ), 'payment' );

        wpbdp_register_settings_group( 'appearance', _x( 'Appearance', 'settings', 'WPBDM' ) );
        wpbdp_register_settings_group( 'appearance/main', _x( 'General Settings', 'settings', 'WPBDM' ), 'appearance' );

        // wpbdp_register_settings_group( 'licenses', _x( 'Licenses', 'settings', 'WPBDM' ) );
        wpbdp_register_settings_group( 'modules', _x( 'Premium Modules', 'settings', 'WPBDM' ) );
    }

    public static function register_initial_settings() {
        self::settings_general();
        self::settings_listings();
        self::settings_email();
        self::settings_payment();
        self::settings_appearance();
    }

    private static function settings_general() {
        wpbdp_register_settings_group( 'general/main', _x( 'General Settings', 'settings', 'WPBDM' ), 'general' );

        // Permalinks.
        wpbdp_register_settings_group( 'permalink_settings', _x( 'Permalink Settings', 'settings', 'WPBDM' ), 'general/main' );
        wpbdp_register_setting(
            array(
				'id'        => 'permalinks-directory-slug',
				'type'      => 'text',
				'name'      => _x( 'Directory Listings Slug', 'settings', 'WPBDM' ),
				'default'   => 'wpbdp_listing',
				'group'     => 'permalink_settings',
				'validator' => 'no-spaces,trim,required',
            )
        );
        wpbdp_register_setting(
            array(
				'id'        => 'permalinks-category-slug',
				'type'      => 'text',
				'name'      => _x( 'Categories Slug', 'settings', 'WPBDM' ),
				'desc'      => _x( 'The slug can\'t be in use by another term. Avoid "category", for instance.', 'settings', 'WPBDM' ),
				'default'   => 'wpbdp_category',
				'group'     => 'permalink_settings',
				'taxonomy'  => WPBDP_CATEGORY_TAX,
				'validator' => 'taxonomy_slug',
            )
        );
        wpbdp_register_setting(
            array(
				'id'        => 'permalinks-tags-slug',
				'type'      => 'text',
				'name'      => _x( 'Tags Slug', 'settings', 'WPBDM' ),
				'desc'      => _x( 'The slug can\'t be in use by another term. Avoid "tag", for instance.', 'settings', 'WPBDM' ),
				'default'   => 'wpbdp_tag',
				'group'     => 'permalink_settings',
				'taxonomy'  => WPBDP_TAGS_TAX,
				'validator' => 'taxonomy_slug',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'permalinks-no-id',
				'type'    => 'checkbox',
				'name'    => _x( 'Remove listing ID from directory URLs?', 'settings', 'WPBDM' ),
				'desc'    => _x( 'Check this setting to remove the ID for better SEO.', 'settings', 'WPBDM' ),
				'tooltip' => _x( 'Prior to 3.5.1, we included the ID in the listing URL, like "/business-directory/1809/listing-title".', 'settings', 'WPBDM' ) . _x( '<strong>IMPORTANT:</strong> subpages of the main directory page cannot be accesed while this settings is checked.', 'admin settings', 'WPBDM' ),
				'group'   => 'permalink_settings',
            )
        );

        // reCAPTCHA.
        wpbdp_register_settings_group(
            'recaptcha',
            _x( 'reCAPTCHA', 'settings', 'WPBDM' ),
            'general',
            array(
                'desc' => str_replace( '<a>', '<a href="http://www.google.com/recaptcha" target="_blank" rel="noopener">', _x( 'Need API keys for reCAPTCHA? Get them <a>here</a>.', 'settings', 'WPBDM' ) ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'    => 'recaptcha-on',
				'type'  => 'checkbox',
				'name'  => _x( 'Use reCAPTCHA for contact forms', 'settings', 'WPBDM' ),
				'group' => 'recaptcha',
            )
        );
        wpbdp_register_setting(
            array(
				'id'    => 'hide-recaptcha-loggedin',
				'type'  => 'checkbox',
				'name'  => _x( 'Turn off reCAPTCHA for logged in users?', 'settings', 'WPBDM' ),
				'group' => 'recaptcha',
            )
        );
        wpbdp_register_setting(
            array(
				'id'    => 'recaptcha-for-submits',
				'type'  => 'checkbox',
				'name'  => _x( 'Use reCAPTCHA for listing submits', 'settings', 'WPBDM' ),
				'group' => 'recaptcha',
            )
        );
        wpbdp_register_setting(
            array(
				'id'    => 'recaptcha-for-flagging',
				'type'  => 'checkbox',
				'name'  => _x( 'Use reCAPTCHA for report listings?', 'settings', 'WPBDM' ),
				'group' => 'recaptcha',
            )
        );
        wpbdp_register_setting(
            array(
				'id'    => 'recaptcha-for-comments',
				'type'  => 'checkbox',
				'name'  => _x( 'Use reCAPTCHA for listing comments?', 'settings', 'WPBDM' ),
				'group' => 'recaptcha',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'recaptcha-public-key',
				'type'    => 'text',
				'name'    => _x( 'reCAPTCHA Public Key', 'settings', 'WPBDM' ),
				'default' => '',
				'group'   => 'recaptcha',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'recaptcha-private-key',
				'type'    => 'text',
				'name'    => _x( 'reCAPTCHA Private Key', 'settings', 'WPBDM' ),
				'default' => '',
				'group'   => 'recaptcha',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'recaptcha-version',
				'type'    => 'select',
				'name'    => _x( 'reCAPTCHA version', 'settings', 'WPBDM' ),
                'default' => 'v2',
                'options' => array(
                    'v2' => 'V2',
                    'v3' => 'V3',
                ),
				'group'   => 'recaptcha',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'recaptcha-threshold',
				'type'    => 'number',
				'name'    => _x( 'reCAPTCHA V3 threshold score', 'settings', 'WPBDM' ),
                'default' => 0.5,
                'min'     => 0,
                'step'    => 0.1,
				'max'     => 1,
				'desc'    => _x( 'reCAPTCHA v3 returns a score (1.0 is very likely a good interaction, 0.0 is very likely a bot). Based on the score, you can take variable action in the context of your site. You can set here the score threshold, scores under this value will result in reCAPTCHA validation error.', 'settings', 'WPBDM'),
				'group'   => 'recaptcha',
            )
        );

        wpbdp_register_settings_group( 'registration', _x( 'Registration', 'settings', 'WPBDM' ), 'general', array( 'desc' => _x( "We expect that a membership plugin supports the 'redirect_to' parameter for the URLs below to work. If the plugin does not support them, these settings will not function as expected. Please contact the membership plugin and ask them to support the WP standard 'redirect_to' query parameter.", 'settings', 'WPBDM' ) ) );
        wpbdp_register_setting(
            array(
				'id'      => 'require-login',
				'type'    => 'checkbox',
				'name'    => _x( 'Require login to post listings?', 'settings', 'WPBDM' ),
				'default' => 1,
				'group'   => 'registration',
            )
        );
        wpbdp_register_setting(
            array(
				'id'    => 'enable-key-access',
				'type'  => 'checkbox',
				'name'  => _x( 'Allow anonymous users to edit/manage listings with an access key?', 'settings', 'WPBDM' ),
				'group' => 'registration',
            )
        );
        wpbdp_register_setting(
            array(
				'id'          => 'login-url',
				'type'        => 'text',
				'name'        => _x( 'Login URL', 'settings', 'WPBDM' ),
				'desc'        => _x( 'Only enter this if using a membership plugin or custom login page.', 'settings', 'WPBDM' ),
				'placeholder' => _x( 'URL of your membership plugin\'s login page.', 'settings', 'WPBDM' ),
				'default'     => '',
				'group'       => 'registration',
            )
        );
        wpbdp_register_setting(
            array(
				'id'          => 'registration-url',
				'type'        => 'text',
				'name'        => _x( 'Registration URL', 'settings', 'WPBDM' ),
				'desc'        => _x( 'Only enter this if using a membership plugin or custom registration page.', 'settings', 'WPBDM' ),
				'placeholder' => _x( 'URL of your membership plugin\'s registration page.', 'settings', 'WPBDM' ),
				'default'     => '',
				'group'       => 'registration',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'create-account-during-submit-mode',
				'type'    => 'radio',
				'name'    => _x( 'Allow users to create accounts during listing submit?', 'settings', 'WPBDM' ),
				'default' => 'required',
				'options' => array(
					'disabled' => _x( 'No', 'settings', 'WPBDM' ),
					'optional' => _x( 'Yes, and make it optional', 'settings', 'WPBDM' ),
					'required' => _x( 'Yes, and make it required', 'settings', 'WPBDM' ),
				),
				'group'   => 'registration',
            )
        );

        // Terms & Conditions.
        wpbdp_register_settings_group( 'tos_settings', _x( 'Terms and Conditions', 'settings', 'WPBDM' ), 'general/main' );
        wpbdp_register_setting(
            array(
				'id'    => 'display-terms-and-conditions',
				'type'  => 'checkbox',
				'name'  => _x( 'Display and require user agreement to Terms and Conditions', 'settings', 'WPBDM' ),
				'group' => 'tos_settings',
            )
        );
        wpbdp_register_setting(
            array(
				'id'          => 'terms-and-conditions',
				'type'        => 'textarea',
				'name'        => _x( 'Terms and Conditions', 'settings', 'WPBDM' ),
				'desc'        => _x( 'Enter text or a URL starting with http. If you use a URL, the Terms and Conditions text will be replaced by a link to the appropiate page.', 'settings', 'WPBDM' ),
				'default'     => '',
				'placeholder' => _x( 'Terms and Conditions text goes here.', 'settings', 'WPBDM' ),
				'group'       => 'tos_settings',
            )
        );

        // Tracking.
        wpbdp_register_settings_group( 'tracking_settings', _x( 'Data Collection', 'settings', 'WPBDM' ), 'general/main' );
        wpbdp_register_setting(
            array(
				'id'    => 'tracking-on',
				'type'  => 'checkbox',
				'name'  => _x( 'Allow BD to anonymously collect information about your installed plugins, themes and WP version?', 'settings', 'WPBDM' ),
				'desc'  => str_replace( '<a>', '<a href="http://businessdirectoryplugin.com/what-we-track/" target="_blank" rel="noopener">', _x( '<a>Learn more</a> about what BD does and does NOT track.', 'admin settings', 'WPBDM' ) ),
				'group' => 'tracking_settings',
            )
        );

        // Search.
        wpbdp_register_settings_group( 'search_settings', _x( 'Directory Search', 'settings', 'WPBDM' ), 'general/main' );
        wpbdp_register_setting(
            array(
				'id'      => 'search-form-in-results',
				'type'    => 'radio',
				'name'    => _x( 'Search form display', 'settings', 'WPBDM' ),
				'default' => 'above',
				'options' => array(
					'above' => _x( 'Above results', 'admin settings', 'WPBDM' ),
					'below' => _x( 'Below results', 'admin settings', 'WPBDM' ),
					'none'  => _x( 'Don\'t show with results', 'admin settings', 'WPBDM' ),
				),
				'group'   => 'search_settings',
            )
        );

        $too_many_fields  = '<span class="text-fields-warning wpbdp-note" style="display: none;">';
        $too_many_fields .= _x( 'You have selected a textarea field to be included in quick searches. Searches involving those fields are very expensive and could result in timeouts and/or general slowness.', 'admin settings', 'WPBDM' );
        $too_many_fields .= '</span>';

        list( $fields, $text_fields, $default_fields ) = self::get_quicksearch_fields();
        $no_fields                                     = '<p><strong>' . _x( 'If no fields are selected, the following fields will be used in Quick Searches:', 'admin settings', 'WPBDM' ) . ' ' . implode( ', ', $default_fields ) . '.</strong></p>';

        wpbdp_register_setting(
            array(
				'id'       => 'quick-search-fields',
				'type'     => 'multicheck',
				'name'     => _x( 'Quick search fields', 'settings', 'WPBDM' ),
				'desc'     => _x( 'Choosing too many fields for inclusion into Quick Search can result in very slow search performance.', 'settings', 'WPBDM' ) . $no_fields . $too_many_fields,
				'default'  => array(),
				'multiple' => true,
				'options'  => $fields,
				'group'    => 'search_settings',
				'attrs'    => array(
					'data-text-fields' => wp_json_encode( $text_fields ),
				),
            )
        );
        wpbdp_register_setting(
            array(
				'id'    => 'quick-search-enable-performance-tricks',
				'type'  => 'checkbox',
				'name'  => _x( 'Enable high performance searches?', 'settings', 'WPBDM' ),
				'desc'  => _x( 'Enabling this makes BD sacrifice result quality to improve speed. This is helpful if you\'re on shared hosting plans, where database performance is an issue.', 'settings', 'WPBDM' ),
				'group' => 'search_settings',
            )
        );

        // Advanced settings.
        wpbdp_register_settings_group( 'general/advanced', _x( 'Advanced', 'settings', 'WPBDM' ), 'general' );

        wpbdp_register_setting(
            array(
				'id'    => 'disable-cpt',
				'type'  => 'checkbox',
				'name'  => _x( 'Disable advanced CPT integration?', 'settings', 'WPBDM' ),
				'group' => 'general/advanced',
            )
        );
        wpbdp_register_setting(
            array(
				'id'        => 'ajax-compat-mode',
				'type'      => 'checkbox',
				'name'      => _x( 'Enable AJAX compatibility mode?', 'settings', 'WPBDM' ),
				'desc'      => _x( 'Check this if you are having trouble with BD, particularly when importing or exporting CSV files.', 'admin settings', 'WPBDM' )
					. ' ' . str_replace( '<a>', '<a href="http://businessdirectoryplugin.com/support-forum/faq/how-to-check-for-plugin-and-theme-conflicts-with-bd/" target="_blank" rel="noopener">', _x( 'If this compatibility mode doesn\'t solve your issue, you may be experiencing a more serious conflict. <a>Here is an article</a> about how to test for theme and plugin conflicts with Business Directory.', 'settings', 'WPBDM' ) ),
				'group'     => 'general/advanced',
				'on_update' => array( __CLASS__, 'setup_ajax_compat_mode' ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'    => 'disable-submit-listing',
				'type'  => 'checkbox',
				'name'  => _x( 'Disable Frontend Listing Submission?', 'settings', 'WPBDM' ),
				'desc'  => _x( 'Prevents the Submit Listing button from showing on the main UI, but allows a shortcode for submit listing to function on other pages.', 'settings', 'WPBDM'),
				'group' => 'general/advanced',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'enqueue-fontawesome-styles',
				'type'    => 'checkbox',
				'name'    => _x( 'Enqueue Business Directory\'s FontAwesome styles?', 'settings', 'WPBDM' ),
				'desc'    => _x( 'This helps to prevent conflicts with other plugins that already do this. Disable this only if you\'re having an issue with FontAwesome icons and have performed a conflict test to validate this is a multiple styles enqueueing issue.', 'settings', 'WPBDM'),
				'default' => true,
				'group'   => 'general/advanced',
            )
        );
    }

    /**
     * Find fields that can be used in Quick Search.
     */
    private static function get_quicksearch_fields() {
        $fields         = array();
        $text_fields    = array();
        $default_fields = array();

        foreach ( wpbdp_get_form_fields( 'association=-custom' ) as $field ) {
            if ( in_array( $field->get_association(), array( 'title', 'excerpt', 'content' ), true ) ) {
                $default_fields[] = $field->get_label();
            }

            if ( in_array( $field->get_association(), array( 'excerpt', 'content' ), true ) || 'textarea' === $field->get_field_type_id() ) {
                $text_fields[] = $field->get_id();
            }

            $fields[ $field->get_id() ] = $field->get_label();
        }

        return array( $fields, $text_fields, $default_fields );
    }

    private static function settings_listings() {
        wpbdp_register_setting(
            array(
				'id'      => 'listings-per-page',
				'type'    => 'number',
				'name'    => _x( 'Listings per page', 'settings', 'WPBDM' ),
				'desc'    => _x( 'Number of listings to show per page. Use a value of "0" to show all listings.', 'settings', 'WPBDM' ),
				'default' => '10',
				'min'     => 0,
				'step'    => 1,
				'group'   => 'listings/main',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'listing-duration',
				'type'    => 'number',
				'name'    => _x( 'Listing duration for no-fee sites (in days)', 'settings', 'WPBDM' ),
				'desc'    => _x( 'Use a value of "0" to keep a listing alive indefinitely or enter a number less than 10 years (3650 days).', 'settings', 'WPBDM' ),
				'default' => '365',
				'min'     => 0,
				'step'    => 1,
				'max'     => 3650,
				'group'   => 'listings/main',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'listing-renewal',
				'type'    => 'checkbox',
				'name'    => _x( 'Turn on listing renewal option?', 'settings', 'WPBDM' ),
				'default' => true,
				'group'   => 'listings/main',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'listing-link-in-new-tab',
				'type'    => 'checkbox',
				'name'    => _x( 'Open detailed view of listing in new tab?', 'settings', 'WPBDM' ),
				'default' => false,
				'group'   => 'listings/main',
            )
        );

        wpbdp_register_settings_group( 'listings/report', _x( 'Report Listings', 'settings', 'WPBDM' ), 'listings/main' );
        wpbdp_register_setting(
            array(
				'id'      => 'enable-listing-flagging',
				'type'    => 'checkbox',
				'name'    => _x( 'Include button to report listings?', 'settings', 'WPBDM' ),
				'default' => false,
				'group'   => 'listings/report',
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'listing-flagging-register-users',
				'type'         => 'checkbox',
				'name'         => _x( 'Enable report listing for registered users only', 'settings', 'WPBDM' ),
				'default'      => true,
				'group'        => 'listings/report',
				'requirements' => array( 'enable-listing-flagging' ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'listing-flagging-options',
				'type'         => 'textarea',
				'name'         => _x( 'Report listing option list', 'settings', 'WPBDM' ),
				'desc'         => _x( 'Form option list to report a listing as inappropriate. One option per line.', 'settings', 'WPBDM' ),
				'default'      => false,
				'group'        => 'listings/report',
				'requirements' => array( 'enable-listing-flagging' ),
            )
        );

        wpbdp_register_settings_group( 'listings/contact', _x( 'Contact Form', 'settings', 'WPBDM' ), 'listings/main' );
        wpbdp_register_setting(
            array(
				'id'      => 'show-contact-form',
				'type'    => 'checkbox',
				'name'    => _x( 'Include listing contact form on listing pages?', 'settings', 'WPBDM' ),
				'desc'    => _x( 'Allows visitors to contact listing authors privately. Authors will receive the messages via email.', 'settings', 'WPBDM' ),
				'default' => true,
				'group'   => 'listings/contact',
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'contact-form-require-login',
				'type'         => 'checkbox',
				'name'         => _x( 'Require login for using the contact form?', 'settings', 'WPBDM' ),
				'default'      => false,
				'group'        => 'listings/contact',
				'requirements' => array( 'show-contact-form' ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'contact-form-daily-limit',
				'type'         => 'number',
				'name'         => _x( 'Maximum number of contact form submits per day', 'settings', 'WPBDM' ),
				'desc'         => _x( 'Use this to prevent spamming of listing owners. 0 means unlimited submits per day.', 'settings', 'WPBDM' ),
				'default'      => '0',
				'group'        => 'listings/contact',
				'requirements' => array( 'show-contact-form' ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'allow-comments-in-listings',
				'type'    => 'radio',
				'name'    => _x( 'Include comment form on listing pages?', 'settings', 'WPBDM' ),
				'desc'    => _x( 'BD uses the standard comment inclusion from WordPress, but most themes only allow for comments on posts, not pages. Some themes handle both. BD is displayed on a page, so we need a theme that can handle both to show comments. Use the 2nd option if you want to allow comments on listings first, and if that doesn\'t work, try the 3rd option instead.', 'settings', 'WPBDM' ),
				'default' => get_option( 'wpbdp-show-comment-form', false ) ? 'allow-comments-and-insert-template' : 'do-not-allow-comments',
				'options' => array(
					'do-not-allow-comments'              => _x( 'Do not include comments in listings', 'admin settings', 'WPBDM' ),
					'allow-comments'                     => _x( 'Include comment form, theme invoked (standard option)', 'admin settings', 'WPBDM' ),
					'allow-comments-and-insert-template' => _x( "Include comment form, BD invoked (use only if 2nd option doesn't work)", 'admin settings', 'WPBDM' ),
				),
				'group'   => 'listings/main',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'show-listings-under-categories',
				'type'    => 'checkbox',
				'name'    => _x( 'Show listings under categories on main page?', 'settings', 'WPBDM' ),
				'default' => false,
				'group'   => 'listings/main',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'prevent-sticky-on-directory-view',
				'type'    => 'multicheck',
				'name'    => _x( 'Prevent featured (sticky) status on BD pages?', 'settings', 'WPBDM' ),
				'desc'    => _x( 'Prevents featured listings from floating to the top of the selected page.', 'settings', 'WPBDM' ),
				'default' => array(),
				'options' => array(
					'main'          => _x( 'Directory view.', 'admin settings', 'WPBDM' ),
					'all_listings'  => _x( 'All Listings view.', 'admin settings', 'WPBDM' ),
					'show_category' => _x( 'Category view.', 'admin settings', 'WPBDM' ),
					'search'        => _x( 'Search view.', 'admin settings', 'WPBDM' ),
				),
				'group'   => 'listings/main',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'status-on-uninstall',
				'type'    => 'radio',
				'name'    => _x( 'Status of listings upon uninstalling plugin', 'settings', 'WPBDM' ),
				'default' => 'trash',
				'options' => array(
					'draft' => _x( 'Draft', 'post status' ),
					'trash' => _x( 'Trash', 'post status' ),
				),
				'group'   => 'listings/main',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'deleted-status',
				'type'    => 'radio',
				'name'    => _x( 'Status of deleted listings', 'settings', 'WPBDM' ),
				'default' => 'trash',
				'options' => array(
					'draft' => _x( 'Draft', 'post status' ),
					'trash' => _x( 'Trash', 'post status' ),
				),
				'group'   => 'listings/main',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'submit-instructions',
				'type'    => 'textarea',
				'name'    => _x( 'Submit Listing instructions message', 'settings', 'WPBDM' ),
				'desc'    => _x( 'This text is displayed at the first page of the Submit Listing process for Business Directory. You can use it for instructions about filling out the form or anything you want to tell users before they get started.', 'settings', 'WPBDM' ),
				'default' => '',
				'group'   => 'listings/main',
            )
        );

        wpbdp_register_settings_group( 'listings/post_category', _x( 'Post/Category Settings', 'settings', 'WPBDM' ), 'listings/main' );
        wpbdp_register_setting(
            array(
				'id'      => 'new-post-status',
				'type'    => 'radio',
				'name'    => _x( 'Default new post status', 'settings', 'WPBDM' ),
				'default' => 'pending',
				'options' => array(
					'publish' => _x( 'Published', 'post status' ),
					'pending' => _x( 'Pending', 'post status' ),
				),
				'group'   => 'listings/post_category',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'edit-post-status',
				'type'    => 'radio',
				'name'    => _x( 'Edit post status', 'settings', 'WPBDM' ),
				'default' => 'publish',
				'options' => array(
					'publish' => _x( 'Published', 'post status' ),
					'pending' => _x( 'Pending', 'post status' ),
				),
				'group'   => 'listings/post_category',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'categories-order-by',
				'type'    => 'radio',
				'name'    => _x( 'Order categories list by', 'settings', 'WPBDM' ),
				'default' => 'name',
				'options' => array(
					'name'  => _x( 'Name', 'admin settings', 'WPBDM' ),
					'slug'  => _x( 'Slug', 'admin settings', 'WPBDM' ),
					'count' => _x( 'Listing Count', 'admin settings', 'WPBDM' ),
				),
				'group'   => 'listings/post_category',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'categories-sort',
				'type'    => 'radio',
				'name'    => _x( 'Sort order for categories', 'settings', 'WPBDM' ),
				'default' => 'ASC',
				'options' => array(
					'ASC'  => _x( 'Ascending', 'admin settings', 'WPBDM' ),
					'DESC' => _x( 'Descending', 'admin settings', 'WPBDM' ),
				),
				'group'   => 'listings/post_category',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'show-category-post-count',
				'type'    => 'checkbox',
				'name'    => _x( 'Show category post count?', 'settings', 'WPBDM' ),
				'default' => true,
				'group'   => 'listings/post_category',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'hide-empty-categories',
				'type'    => 'checkbox',
				'name'    => _x( 'Hide empty categories?', 'settings', 'WPBDM' ),
				'default' => false,
				'group'   => 'listings/post_category',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'show-only-parent-categories',
				'type'    => 'checkbox',
				'name'    => _x( 'Show only parent categories in category list?', 'settings', 'WPBDM' ),
				'default' => false,
				'group'   => 'listings/post_category',
            )
        );

        wpbdp_register_settings_group( 'listings/sorting', _x( 'Listings Sorting', 'settings', 'WPBDM' ), 'listings/main' );

        $msg = _x( 'Fee Plan Custom Order can be changed under <a>Manage Fees</a>', 'admin settings', 'WPBDM' );
        $msg = str_replace( '<a>', '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp-admin-fees' ) ) . '">', $msg );
        wpbdp_register_setting(
            array(
				'id'      => 'listings-order-by',
				'type'    => 'select',
				'name'    => _x( 'Order directory listings by', 'settings', 'WPBDM' ),
				'desc'    => $msg,
				'default' => 'title',
				'options' => apply_filters( 'wpbdp_sort_options',
				    array(
                        'title'            => _x( 'Title', 'admin settings', 'WPBDM' ),
                        'author'           => _x( 'Author', 'admin settings', 'WPBDM' ),
                        'date'             => _x( 'Date posted', 'admin settings', 'WPBDM' ),
                        'modified'         => _x( 'Date last modified', 'admin settings', 'WPBDM' ),
                        'rand'             => _x( 'Random', 'admin settings', 'WPBDM' ),
                        'paid'             => _x( 'Paid first then free. Inside each group by date.', 'admin settings', 'WPBDM' ),
                        'paid-title'       => _x( 'Paid first then free. Inside each group by title.', 'admin settings', 'WPBDM' ),
                        'plan-order-date'  => _x( 'Fee Plan Custom Order, then Date', 'admin settings', 'WPBDM' ),
                        'plan-order-title' => _x( 'Fee Plan Custom Order, then Title', 'admin settings', 'WPBDM' ),
                    )
				),
				'group'   => 'listings/sorting',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'listings-sort',
				'type'    => 'radio',
				'name'    => _x( 'Sort directory listings by', 'settings', 'WPBDM' ),
				'desc'    => _x( 'Ascending for ascending order A-Z, Descending for descending order Z-A', 'settings', 'WPBDM' ),
				'default' => 'ASC',
				'options' => array(
					'ASC'  => _x( 'Ascending', 'admin settings', 'WPBDM' ),
					'DESC' => _x( 'Descending', 'admin settings', 'WPBDM' ),
				),
				'group'   => 'listings/sorting',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'listings-sortbar-enabled',
				'type'    => 'checkbox',
				'name'    => _x( 'Enable sort bar?', 'settings', 'WPBDM' ),
				'default' => false,
				'group'   => 'listings/sorting',
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'listings-sortbar-fields',
				'type'         => 'multicheck',
				'name'         => _x( 'Sortbar Fields', 'settings', 'WPBDM' ),
				'default'      => array(),
				'options'      => wpbdp_sortbar_get_field_options(),
				'group'        => 'listings/sorting',
				'requirements' => array( 'listings-sortbar-enabled' ),
            )
        );
    }

    private static function settings_appearance() {
        // Display Options.
        wpbdp_register_settings_group( 'display_options', _x( 'Directory Display Options', 'settings', 'WPBDM' ), 'appearance/main' );
        wpbdp_register_setting(
            array(
				'id'           => 'show-submit-listing',
				'type'         => 'checkbox',
				'name'         => _x( 'Show the "Submit listing" button.', 'settings', 'WPBDM' ),
				'desc'         => _x( 'Hides the button used by the main UI to allow listing submission, but does not shut off the use of the link for submitting listings (allows you to customize the submit listing button on your own)', 'settings', 'WPBDM'),
				'default'      => true,
				'group'        => 'display_options',
				'requirements' => array( '!disable-submit-listing' ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'show-search-listings',
				'type'    => 'checkbox',
				'name'    => _x( 'Show "Search listings".', 'settings', 'WPBDM' ),
				'default' => true,
				'group'   => 'display_options',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'show-view-listings',
				'type'    => 'checkbox',
				'name'    => _x( 'Show the "View Listings" button.', 'settings', 'WPBDM' ),
				'default' => true,
				'group'   => 'display_options',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'show-directory-button',
				'type'    => 'checkbox',
				'name'    => _x( 'Show the "Directory" button.', 'settings', 'WPBDM' ),
				'default' => true,
				'group'   => 'display_options',
            )
        );

        // Themes.
        wpbdp_register_settings_group( 'themes', _x( 'Theme Settings', 'settings', 'WPBDM' ), 'appearance', array( 'desc' => str_replace( '<a>', '<a href="' . admin_url( 'admin.php?page=wpbdp-themes' ) . '">', _x( 'You can manage your themes on <a>Directory Themes</a>.', 'admin settings', 'WPBDM' ) ) ) );

        wpbdp_register_setting(
            array(
				'id'      => 'themes-button-style',
				'type'    => 'radio',
				'name'    => _x( 'Theme button style', 'settings', 'WPBDM' ),
				'default' => 'theme',
				'options' => array(
					'theme' => _x( 'Use the BD theme style for BD buttons', 'admin settings', 'WPBDM' ),
					'none'  => _x( 'Use the WP theme style for BD buttons', 'admin settings', 'WPBDM' ),
				),
				'group'   => 'themes',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'include-button-styles',
				'type'    => 'checkbox',
				'name'    => _x( 'Include CSS rules to give their own style to View, Edit and Delete buttons?', 'settings', 'WPBDM' ),
				'default' => 1,
				'group'   => 'themes',
            )
        );

        // Image.
        wpbdp_register_settings_group( 'appearance/image', _x( 'Image', 'settings', 'WPBDM' ), 'appearance' );
        wpbdp_register_settings_group( 'images/general', _x( 'Image Settings', 'settings', 'WPBDM' ), 'appearance/image', array( 'desc' => 'Any changes to these settings will affect new listings only.  Existing listings will not be affected.  If you wish to change existing listings, you will need to re-upload the image(s) on that listing after changing things here.' ) );
        wpbdp_register_setting(
            array(
				'id'      => 'allow-images',
				'type'    => 'checkbox',
				'name'    => _x( 'Allow images?', 'settings', 'WPBDM' ),
				'default' => true,
				'group'   => 'images/general',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'image-min-filesize',
				'type'    => 'number',
				'min'     => 0,
				'step'    => 1,
				'name'    => _x( 'Min Image File Size (KB)', 'settings', 'WPBDM' ),
				'default' => '0',
				'group'   => 'images/general',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'image-max-filesize',
				'type'    => 'number',
				'min'     => 0,
				'step'    => 1,
				'name'    => _x( 'Max Image File Size (KB)', 'settings', 'WPBDM' ),
				'default' => '10000',
				'group'   => 'images/general',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'image-min-width',
				'type'    => 'number',
				'min'     => 0,
				'step'    => 1,
				'name'    => _x( 'Min image width (px)', 'settings', 'WPBDM' ),
				'default' => '0',
				'group'   => 'images/general',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'image-min-height',
				'type'    => 'number',
				'name'    => _x( 'Min image height (px)', 'settings', 'WPBDM' ),
				'default' => '0',
				'min'     => 0,
				'step'    => 1,
				'group'   => 'images/general',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'image-max-width',
				'type'    => 'number',
				'min'     => 0,
				'step'    => 1,
				'name'    => _x( 'Max image width (px)', 'settings', 'WPBDM' ),
				'default' => '500',
				'group'   => 'images/general',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'image-max-height',
				'type'    => 'number',
				'min'     => 0,
				'step'    => 1,
				'name'    => _x( 'Max image height (px)', 'settings', 'WPBDM' ),
				'default' => '500',
				'group'   => 'images/general',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'use-thickbox',
				'type'    => 'checkbox',
				'min'     => 0,
				'step'    => 1,
				'name'    => _x( 'Turn on thickbox/lightbox?', 'settings', 'WPBDM' ),
				'desc'    => _x( 'Uncheck if it conflicts with other elements or plugins installed on your site', 'settings', 'WPBDM' ),
				'default' => false,
				'group'   => 'images/general',
            )
        );

        wpbdp_register_settings_group( 'image/thumbnails', _x( 'Thumbnails', 'settings', 'WPBDM' ), 'appearance/image' );
        wpbdp_register_setting(
            array(
				'id'      => 'thumbnail-width',
				'type'    => 'number',
				'min'     => 0,
				'step'    => 1,
				'name'    => _x( 'Thumbnail width (px)', 'settings', 'WPBDM' ),
				'default' => '150',
				'group'   => 'image/thumbnails',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'thumbnail-height',
				'type'    => 'number',
				'min'     => 0,
				'step'    => 1,
				'name'    => _x( 'Thumbnail height (px)', 'settings', 'WPBDM' ),
				'default' => '150',
				'group'   => 'image/thumbnails',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'thumbnail-crop',
				'type'    => 'checkbox',
				'name'    => _x( 'Crop thumbnails to exact dimensions?', 'settings', 'WPBDM' ),
				'desc'    => _x( 'When enabled images will match exactly the dimensions above but part of the image may be cropped out. If disabled, image thumbnails will be resized to match the specified width and their height will be adjusted proportionally. Depending on the uploaded images, thumbnails may have different heights.', 'settings', 'WPBDM' ),
				'default' => false,
				'group'   => 'image/thumbnails',
            )
        );

        wpbdp_register_settings_group( 'image/listings', _x( 'Listings', 'settings', 'WPBDM' ), 'appearance/image' );
        wpbdp_register_setting(
            array(
				'id'      => 'enforce-image-upload',
				'type'    => 'checkbox',
				'name'    => _x( 'Enforce image upload on submit/edit?', 'settings', 'WPBDM' ),
				'default' => false,
				'group'   => 'image/listings',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'free-images',
				'type'    => 'number',
				'name'    => _x( 'Number of free images', 'settings', 'WPBDM' ),
				'default' => '2',
				'min'     => 0,
				'step'    => 1,
				'desc'    => str_replace( '<a>', '<a href="' . admin_url( 'admin.php?page=wpbdp-admin-fees' ) . '">', _x( 'For paid listing images, configure that by adding or editing a <a>Fee Plan</a> instead of this setting, which is ignored for paid listings.', 'admin settings', 'WPBDM' ) ),
				'group'   => 'image/listings',
            )
        );
        wpbdp_register_setting(
            array(
                'id'      => 'use-default-picture',
                'type'    => 'multicheck',
                'name'    => _x( 'Use "Coming Soon" photo for listings without any (primary) images?', 'settings', 'WPBDM' ),
                'default' => array(),
                'options' => array(
                    'excerpt' => _x( 'Excerpt view.', 'admin settings', 'WPBDM' ),
                    'listing' => _x( 'Detail view.', 'admin settings', 'WPBDM' ),
                ),
                'group'   => 'image/listings',
            )
        );
        wpbdp_register_setting(
            array(
                'id'      => 'listing-main-image-default-size',
                'type'    => 'select',
                'name'    => _x( 'Default thumbnail image size', 'settings', 'WPBDM' ),
                'default' => 'wpbdp-thumb',
                'options' => self::get_registered_image_sizes(),
                'desc'    => _x( 'This indicates the size of the thumbnail to be used both in excerpt and detail views. For CROPPED image size values, we use the EXACT size defined. For all other values, we preserve the aspect ratio of the image and use the width as the starting point.', 'settings', 'WPBDM' ),
                'group'   => 'image/listings',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'show-thumbnail',
				'type'    => 'checkbox',
				'name'    => _x( 'Show Thumbnail on main listings page?', 'settings', 'WPBDM' ),
				'default' => true,
				'group'   => 'image/listings',
            )
        );
        wpbdp_register_setting(
            array(
                'id'           => 'listings-sticky-image',
                'type'         => 'file',
                'name'         => _x( 'Featured Badge image', 'settings', 'WPBDM' ),
                'default'      => '',
                'group'        => 'image/listings',
            )
        );
        wpbdp_register_setting(
            array(
                'id'          => 'sticky-image-link-to',
                'type'        => 'url',
                'name'        => _x( 'Featured Badge URL', 'settings', 'WPBDM' ),
                'desc'        => _x( 'Use this to set Featured Badge image as a link to a defined URL.', 'settings', 'WPBDM' ),
                'placeholder' => _x( 'URL', 'settings', 'WPBDM' ),
                'default'     => '',
                'group'       => 'image/listings',
            )
        );
        wpbdp_register_setting(
            array(
                'id'      => 'display-sticky-badge',
                'type'    => 'multicheck',
                'name'    => _x( 'Display featured (sticky) badge on listing:', 'settings', 'WPBDM' ),
                'desc'    => _x( '', 'settings', 'WPBDM' ),
                'default' => array( 'single' ),
                'options' => array(
                    'excerpt' => _x( 'Excerpt view.', 'admin settings', 'WPBDM' ),
                    'single'  => _x( 'Detail view.', 'admin settings', 'WPBDM' ),
                ),
                'group'   => 'image/listings',
            )
        );
    }

    private static function settings_payment() {
        wpbdp_register_setting(
            array(
				'id'      => 'fee-order',
				'type'    => 'silent',
				'name'    => _x( 'Fee Order', 'settings', 'WPBDM' ),
				'default' => array(
					'method' => 'label',
					'order'  => 'asc',
				),
				'group'   => 'payment/main',
            )
        );

        wpbdp_register_setting(
            array(
				'id'      => 'payments-on',
				'type'    => 'checkbox',
				'name'    => _x( 'Turn On payments?', 'settings', 'WPBDM' ),
				'default' => false,
				'group'   => 'payment/main',
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'payments-test-mode',
				'type'         => 'checkbox',
				'name'         => _x( 'Put payment gateways in test mode?', 'settings', 'WPBDM' ),
				'default'      => true,
				'group'        => 'payment/main',
				'requirements' => array( 'payments-on' ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'payments-use-https',
				'type'         => 'checkbox',
				'name'         => _x( 'Perform checkouts on the secure (HTTPS) version of your site?', 'settings', 'WPBDM' ),
				'desc'         => _x( 'Recommended for added security. For this to work you need to enable HTTPS on your server and obtain an SSL certificate.', 'settings', 'WPBDM' ),
				'default'      => false,
				'group'        => 'payment/main',
				'requirements' => array( 'payments-on' ),
            )
        );

        $aed_usupported_gateways = apply_filters( 'wpbdp_aed_not_supported', wpbdp_get_option( 'authorize-net', false ) ? array( 'Authorize.net' ) : array() );
        $desc = '';

        if ( $aed_usupported_gateways ) {
            $desc = sprintf(
                _x( 'AED currency is not supported by %s. %s', 'admin settings', 'WPBDM' ),
                '<b>' . implode( ' or ', $aed_usupported_gateways ) . '</b>',
                _n( 'If you are using this gateway, we recommend you disable it if you wish to collect payments in this currency.', 'If you are using these gateways, we recommend you disable them if you wish to collect payments in this currency.', count( $aed_usupported_gateways ) )
            );
        }

        wpbdp_register_setting(
            array(
				'id'           => 'currency',
				'type'         => 'select',
				'name'         => _x( 'Currency Code', 'settings', 'WPBDM' ),
				'default'      => 'USD',
				'options'      => array(
					'AUD' => _x( 'Australian Dollar (AUD)', 'admin settings', 'WPBDM' ),
					'BRL' => _x( 'Brazilian Real (BRL)', 'admin settings', 'WPBDM' ),
					'CAD' => _x( 'Canadian Dollar (CAD)', 'admin settings', 'WPBDM' ),
					'CZK' => _x( 'Czech Koruna (CZK)', 'admin settings', 'WPBDM' ),
					'DKK' => _x( 'Danish Krone (DKK)', 'admin settings', 'WPBDM' ),
					'AED' => _x( 'United Arab Emirates Dirham (AED)', 'admin settings', 'WPBDM' ),
					'EUR' => _x( 'Euro (EUR)', 'admin settings', 'WPBDM' ),
					'HKD' => _x( 'Hong Kong Dollar (HKD)', 'admin settings', 'WPBDM' ),
					'HUF' => _x( 'Hungarian Forint (HUF)', 'admin settings', 'WPBDM' ),
					'ILS' => _x( 'Israeli New Shequel (ILS)', 'admin settings', 'WPBDM' ),
					'JPY' => _x( 'Japanese Yen (JPY)', 'admin settings', 'WPBDM' ),
					'MAD' => _x( 'Moroccan Dirham (MAD)', 'admin settings', 'WPBDM' ),
					'MYR' => _x( 'Malasian Ringgit (MYR)', 'admin settings', 'WPBDM' ),
					'MXN' => _x( 'Mexican Peso (MXN)', 'admin settings', 'WPBDM' ),
					'NOK' => _x( 'Norwegian Krone (NOK)', 'admin settings', 'WPBDM' ),
					'NZD' => _x( 'New Zealand Dollar (NZD)', 'admin settings', 'WPBDM' ),
					'PHP' => _x( 'Philippine Peso (PHP)', 'admin settings', 'WPBDM' ),
					'PLN' => _x( 'Polish Zloty (PLN)', 'admin settings', 'WPBDM' ),
					'GBP' => _x( 'Pound Sterling (GBP)', 'admin settings', 'WPBDM' ),
					'SGD' => _x( 'Singapore Dollar (SGD)', 'admin settings', 'WPBDM' ),
					'SEK' => _x( 'Swedish Krona (SEK)', 'admin settings', 'WPBDM' ),
					'CHF' => _x( 'Swiss Franc (CHF)', 'admin settings', 'WPBDM' ),
					'TWD' => _x( 'Taiwan Dollar (TWD)', 'admin settings', 'WPBDM' ),
					'THB' => _x( 'Thai Baht (THB)', 'admin settings', 'WPBDM' ),
					'TRY' => _x( 'Turkish Lira (TRY)', 'admin settings', 'WPBDM' ),
					'USD' => _x( 'U.S. Dollar (USD)', 'admin settings', 'WPBDM' ),
				),
				'desc'         => $desc,
				'group'        => 'payment/main',
				'requirements' => array( 'payments-on' ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'currency-symbol',
				'type'         => 'text',
				'name'         => _x( 'Currency Symbol', 'settings', 'WPBDM' ),
				'default'      => '$',
				'group'        => 'payment/main',
				'requirements' => array( 'payments-on' ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'currency-symbol-position',
				'type'         => 'radio',
				'name'         => _x( 'Currency symbol display', 'settings', 'WPBDM' ),
				'default'      => 'left',
				'options'      => array(
					'left'  => _x( 'Show currency symbol on the left', 'admin settings', 'WPBDM' ),
					'right' => _x( 'Show currency symbol on the right', 'admin settings', 'WPBDM' ),
					'none'  => _x( 'Do not show currency symbol', 'admin settings', 'WPBDM' ),
				),
				'group'        => 'payment/main',
				'requirements' => array( 'payments-on' ),
            )
        );
        wpbdp_register_setting(
            array(
                'id'           => 'include-fee-description',
                'type'         => 'checkbox',
                'name'         => _x( 'Include fee description in receipt?', 'settings', 'WPBDM' ),
                'default'      => false,
                'group'        => 'payment/main',
                'requirements' => array( 'payments-on' ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'payment-message',
				'type'         => 'textarea',
				'name'         => _x( 'Thank you for payment message', 'settings', 'WPBDM' ),
				'default'      => _x( 'Thank you for your payment. Your payment is being verified and your listing reviewed. The verification and review process could take up to 48 hours.', 'admin settings', 'WPBDM' ),
				'group'        => 'payment/main',
				'requirements' => array( 'payments-on' ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'payment-abandonment',
				'type'         => 'checkbox',
				'name'         => _x( 'Ask users to come back for abandoned payments?', 'settings', 'WPBDM' ),
				'desc'         => _x( 'An abandoned payment is when a user attempts to place a listing and gets to the end, but fails to complete their payment for the listing. This results in listings that look like they failed, when the user simply didn\'t complete the transaction.  BD can remind them to come back and continue.', 'settings', 'WPBDM' ),
				'default'      => false,
				'group'        => 'payment/main',
				'requirements' => array( 'payments-on' ),
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'payment-abandonment-threshold',
				'type'         => 'number',
				'name'         => _x( 'Listing abandonment threshold (hours)', 'settings', 'WPBDM' ),
				'desc'         => str_replace( '<a>', '<a href="' . admin_url( 'admin.php?page=wpbdp_settings&tab=email' ) . '#email-templates-payment-abandoned">', _x( 'Listings with pending payments are marked as abandoned after this time. You can also <a>customize the e-mail</a> users receive.', 'admin settings', 'WPBDM' ) ),
				'default'      => '24',
				'min'          => 0,
				'step'         => 1,
				'group'        => 'payment/main',
				'requirements' => array( 'payment-abandonment' ),
            )
        );
    }

    private static function settings_email() {
        wpbdp_register_settings_group( 'email/main/general', _x( 'General Settings', 'settings', 'WPBDM' ), 'email/main' );
        wpbdp_register_setting(
            array(
				'id'      => 'override-email-blocking',
				'type'    => 'checkbox',
				'name'    => _x( 'Display email address fields publicly?', 'settings', 'WPBDM' ),
				'desc'    => _x( 'Shows the email address of the listing owner to all web users. NOT RECOMMENDED as this increases spam to the address and allows spam bots to harvest it for future use.', 'settings', 'WPBDM' ),
				'default' => false,
				'group'   => 'email/main/general',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'listing-email-mode',
				'type'    => 'radio',
				'name'    => _x( 'How to determine the listing\'s email address?', 'settings', 'WPBDM' ),
				'desc'    => _x( 'This affects emails sent to listing owners via contact forms or when their listings expire.', 'settings', 'WPBDM' ),
				'default' => 'field',
				'options' => array(
					'field' => _x( 'Try listing\'s email field first, then author\'s email.', 'admin settings', 'WPBDM' ),
					'user'  => _x( 'Try author\'s email first and then listing\'s email field.', 'admin settings', 'WPBDM' ),
				),
				'group'   => 'email/main/general',
            )
        );
        wpbdp_register_setting(
            array(
				'id'      => 'listing-email-content-type',
				'type'    => 'radio',
				'name'    => _x( 'Email Content-Type header', 'settings', 'WPBDM' ),
				'desc'    => _x( 'Use this setting to control the format of the emails explicitly. Some plugins for email do not correctly support Content Type unless explicitly set, you can do that here. If you\'re unsure, try "HTML", "Plain" and then "Both".', 'settings', 'WPBDM' ),
				'default' => 'html',
				'options' => array(
					'plain' => _x( 'Plain (text/plain)', 'admin settings', 'WPBDM' ),
					'html'  => _x( 'HTML (text/html)', 'admin settings', 'WPBDM' ),
					'both'  => _x( 'Both (multipart/alternative)', 'admin settings', 'WPBDM' ),
				),
				'group'   => 'email/main/general',
            )
        );

        wpbdp_register_settings_group( 'email_notifications', _x( 'E-Mail Notifications', 'settings', 'WPBDM' ), 'email/main' );
        wpbdp_register_setting(
            array(
				'id'      => 'admin-notifications',
				'type'    => 'multicheck',
				'name'    => _x( 'Notify admin via e-mail when...', 'settings', 'WPBDM' ),
				'default' => array(),
				'options' => array(
					'new-listing'      => _x( 'A new listing is submitted.', 'admin settings', 'WPBDM' ),
					'listing-edit'     => _x( 'A listing is edited.', 'admin settings', 'WPBDM' ),
					'renewal'          => _x( 'A listing expires.', 'admin settings', 'WPBDM' ),
					'after_renewal'    => _x( 'A listing is renewed.', 'admin settings', 'WPBDM' ),
					'flagging_listing' => _x( 'A listing has been reported as inappropriate.', 'admin settings', 'WPBDM' ),
					'listing-contact'  => _x( 'A contact message is sent to a listing\'s owner.', 'admin settings', 'WPBDM' ),
				),
				'group'   => 'email_notifications',
            )
        );
        wpbdp_register_setting(
            array(
				'id'    => 'admin-notifications-cc',
				'type'  => 'text',
				'name'  => _x( 'CC this e-mail address too', 'settings', 'WPBDM' ),
				'group' => 'email_notifications',
            )
        );

        $settings_url = admin_url( 'admin.php?page=wpbdp_settings&tab=email&subtab=email_templates' );
        $description  = _x( 'You can modify the text template used for most of these e-mails in the <templates-link>Templates</templates-link> tab.', 'settings', 'WPBDM' );
        $description  = str_replace( '<templates-link>', '<a href="' . $settings_url . '">', $description );
        $description  = str_replace( '</templates-link>', '</a>', $description );

        wpbdp_register_setting( array(
            'id'      => 'user-notifications',
            'type'    => 'multicheck',
            'name'    => _x( 'Notify users via e-mail when...', 'settings', 'WPBDM' ),
            'desc'    => $description,
            'default' => array( 'new-listing', 'listing-published', 'listing-expires' ),
            'options' => array(
                'new-listing'       => _x( 'Their listing is submitted.', 'admin settings', 'WPBDM' ),
                'listing-published' => _x( 'Their listing is approved/published.', 'admin settings', 'WPBDM' ),
                'listing-expires'   => _x( 'Their listing expired or is about to expire.', 'admin settings', 'WPBDM' ),
            ),
            'group' => 'email_notifications'
        ) );

        wpbdp_register_settings_group( 'email_templates', _x( 'Templates', 'settings', 'WPBDM' ), 'email' );
        wpbdp_register_setting(
            array(
				'id'           => 'email-confirmation-message',
				'type'         => 'email_template',
				'name'         => _x( 'Email confirmation message', 'settings', 'WPBDM' ),
				'desc'         => _x( 'Sent after a listing has been submitted.', 'settings', 'WPBDM' ),
				'default'      => array(
					'subject' => '[[site-title]] Listing "[listing]" received',
					'body'    => 'Your submission \'[listing]\' has been received and it\'s pending review. This review process could take up to 48 hours.',
				),
				'placeholders' => array(
					'listing' => array( _x( 'Listing\'s title', 'admin settings', 'WPBDM' ) ),
				),
				'group'        => 'email_templates',
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'email-templates-listing-published',
				'type'         => 'email_template',
				'name'         => _x( 'Listing published message', 'settings', 'WPBDM' ),
				'desc'         => _x( 'Sent when the listing has been published or approved by an admin.', 'settings', 'WPBDM' ),
				'default'      => array(
					'subject' => '[[site-title]] Listing "[listing]" published',
					'body'    => _x( 'Your listing "[listing]" is now available at [listing-url] and can be viewed by the public.', 'admin settings', 'WPBDM' ),
				),
				'placeholders' => array(
					'listing'     => _x( 'Listing\'s title', 'admin settings', 'WPBDM' ),
					'listing-url' => _x( 'Listing\'s URL', 'admin settings', 'WPBDM' ),
					'access_key'  => _x( 'Listing\'s Access Key', 'admin settings', 'WPBDM' ),
				),
				'group'        => 'email_templates',
            )
        );
        wpbdp_register_setting(
            array(
				'id'           => 'email-templates-contact',
				'type'         => 'email_template',
				'name'         => _x( 'Listing Contact Message', 'settings', 'WPBDM' ),
				'desc'         => _x( 'Sent to listing owners when someone uses the contact form on their listing pages.', 'settings', 'WPBDM' ),
				'default'      => array(
					'subject' => '[[site-title]] Contact via "[listing]"',
					'body'    => '' .
								 sprintf( _x( 'You have received a reply from your listing at %s.', 'contact email', 'WPBDM' ), '[listing-url]' ) . "\n\n" .
								 sprintf( _x( 'Name: %s', 'contact email', 'WPBDM' ), '[name]' ) . "\n" .
								 sprintf( _x( 'E-Mail: %s', 'contact email', 'WPBDM' ), '[email]' ) . "\n" .
								 _x( 'Message:', 'contact email', 'WPBDM' ) . "\n" .
								 '[message]' . "\n\n" .
								 sprintf( _x( 'Time: %s', 'contact email', 'WPBDM' ), '[date]' ),
				),
				'placeholders' => array(
					'listing-url' => _x( 'Listing\'s URL', 'admin settings', 'WPBDM' ),
					'listing'     => _x( 'Listing\'s title', 'admin settings', 'WPBDM' ),
					'name'        => _x( 'Sender\'s name', 'admin settings', 'WPBDM' ),
					'email'       => _x( 'Sender\'s e-mail address', 'admin settings', 'WPBDM' ),
					'message'     => _x( 'Contact message', 'admin settings', 'WPBDM' ),
					'date'        => _x( 'Date and time the message was sent', 'admin settings', 'WPBDM' ),
					'access_key'  => _x( 'Listing\'s Access Key', 'admin settings', 'WPBDM' ),
				),
				'group'        => 'email_templates',
            )
        );

        wpbdp_register_setting(
            array(
				'id'           => 'email-templates-payment-abandoned',
				'type'         => 'email_template',
				'name'         => _x( 'Payment abandoned reminder message', 'settings', 'WPBDM' ),
				'desc'         => _x( 'Sent some time after a pending payment is abandoned by users.', 'settings', 'WPBDM' ),
				'default'      => array(
					'subject' => '[[site-title]] Pending payment for "[listing]"',
					'body'    => '
        Hi there,

        We noticed that you tried submitting a listing on [site-link] but didn\'t finish
        the process.  If you want to complete the payment and get your listing
        included, just click here to continue:

        [link]

        If you have any issues, please contact us directly by hitting reply to this
        email!

        Thanks,
        - The Administrator of [site-title]',
				),
				'placeholders' => array(
					'listing' => _x( 'Listing\'s title', 'admin settings', 'WPBDM' ),
					'link'    => _x( 'Checkout URL link', 'admin settings', 'WPBDM' ),
				),
				'group'        => 'email_templates',
            )
        );

        // wpbdp_register_setting( array(
        // 'id'   => 'email-renewal-reminders_settings',
        // 'type' => 'section',
        // 'name' => _x( 'Expiration/Renewal Notices', 'settings', 'WPBDM' ),
        // 'desc' =>  _x( 'You can configure here the text for the expiration/renewal emails and also how long before/after expiration/renewal they are sent.', 'settings', 'WPBDM' ),
        // 'tab' => 'email'
        // ) );
        wpbdp_register_setting(
            array(
				'id'        => 'expiration-notices',
				'type'      => 'expiration_notices',
				'name'      => _x( 'E-Mail Notices', 'settings', 'WPBDM' ),
				'default'   => self::get_default_expiration_notices(),
				'group'     => 'email_templates',
				'validator' => array( __class__, 'validate_expiration_notices' ),
            )
        );
    }

    public static function get_default_expiration_notices() {
        $notices = array();

        /* renewal-pending-message, non-recurring only */
        $notices[] = array(
            'event'         => 'expiration',
            'relative_time' => '+5 days', /* renewal-email-threshold, def: 5 days */
            'listings'      => 'non-recurring',
            'subject'       => '[[site-title]] [listing] - Your listing is about to expire',
            'body'          => 'Your listing "[listing]" is about to expire at [site]. You can renew it here: [link].',
        );
        // array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
        // 'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
        // 'expiration' => _x( 'Expiration date', 'settings', 'WPBDM' ),
        // 'category' => _x( 'Category that is going to expire', 'settings', 'WPBDM' ),
        // 'link' => _x( 'Link to renewal page', 'settings', 'WPBDM' ),
        // 'site' => _x( 'Link to your site', 'settings', 'WPBDM' )  ) )
        /* listing-renewal-message, non-recurring only */
        $notices[] = array(
            'event'         => 'expiration',
            'relative_time' => '0 days', /* at time of expiration */
            'listings'      => 'non-recurring',
            'subject'       => 'Your listing on [site-title] expired',
            'body'          => "Your listing \"[listing]\" in category [category] expired on [expiration]. To renew your listing click the link below.\n[link]",
        );
        // array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
        // 'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
        // 'expiration' => _x( 'Expiration date', 'settings', 'WPBDM' ),
        // 'category' => _x( 'Category that expired', 'settings', 'WPBDM' ),
        // 'link' => _x( 'Link to renewal page', 'settings', 'WPBDM' ),
        // 'site' => _x( 'Link to your site', 'settings', 'WPBDM' )  ) )
        /* renewal-reminder-message, both recurring and non-recurring */
        $notices[] = array(
            'event'         => 'expiration',
            'relative_time' => '-5 days', /* renewal-reminder-threshold */
            'listings'      => 'both',
            'subject'       => '[[site-title]] [listing] - Expiration reminder',
            'body'          => "Dear Customer\nWe've noticed that you haven't renewed your listing \"[listing]\" for category [category] at [site] and just wanted to remind you that it expired on [expiration]. Please remember you can still renew it here: [link].",
        );
        // array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
        // 'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
        // 'expiration' => _x( 'Expiration date', 'settings', 'WPBDM' ),
        // 'category' => _x( 'Category that expired', 'settings', 'WPBDM' ),
        // 'link' => _x( 'Link to renewal page', 'settings', 'WPBDM' ),
        // 'site' => _x( 'Link to your site', 'settings', 'WPBDM' )  ) )
        /* listing-autorenewal-notice, recurring only, controlled by the send-autorenewal-expiration-notice setting */
        $notices[] = array(
            'event'         => 'expiration',
            'relative_time' => '+5 days', /*  renewal-email-threshold, def: 5 days */
            'listings'      => 'recurring',
            'subject'       => '[[site-title]] [listing] - Renewal reminder',
            'body'          => "Hey [author],\n\nThis is just to remind you that your listing [listing] is going to be renewed on [expiration] for another period.\nIf you want to review or cancel your subscriptions please visit [link].\n\nIf you have any questions, contact us at [site].",
        );
        // array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
        // 'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
        // 'date' => _x( 'Renewal date', 'settings', 'WPBDM' ),
        // 'category' => _x( 'Category that is going to be renewed', 'settings', 'WPBDM' ),
        // 'site' => _x( 'Link to your site', 'settings', 'WPBDM' ),
        // 'link' => _x( 'Link to manage subscriptions', 'settings', 'WPBDM' ) ) )
        /* listing-autorenewal-message, after IPN notification of renewal of recurring */
        $notices[] = array(
            'event'         => 'renewal',
            'relative_time' => '0 days',
            'listings'      => 'recurring',
            'subject'       => '[[site-title]] [listing] renewed',
            'body'          => "Hey [author],\n\nThanks for your payment. We just renewed your listing [listing] on [payment_date] for another period.\n\nIf you have any questions, contact us at [site].",
        );
        // $replacements['listing'] = sprintf( '<a href="%s">%s</a>',
        // get_permalink( $payment->get_listing_id() ),
        // get_the_title( $payment->get_listing_id() ) );
        // $replacements['author'] = get_the_author_meta( 'display_name', get_post( $payment->get_listing_id() )->post_author );
        // $replacements['category'] = wpbdp_get_term_name( $recurring_item->rel_id_1 );
        // $replacements['date'] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
        // strtotime( $payment->get_processed_on() ) );
        // $replacements['site'] = sprintf( '<a href="%s">%s</a>',
        // get_bloginfo( 'url' ),
        // get_bloginfo( 'name' ) );
        //
        return $notices;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function validate_expiration_notices( $value ) {
        // We remove notices with no subject and no content.
        foreach ( array_keys( $value ) as $notice_id ) {
            $value[ $notice_id ] = array_map( 'trim', $value[ $notice_id ] );

            if ( empty( $value[ $notice_id ]['subject'] ) && empty( $value[ $notice_id ]['content'] ) ) {
                unset( $value[ $notice_id ] );
            }
        }

        // Remove enforce that there's always one notice applying to the expiration time of non-recurring listings. (#3795)

        return $value;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function setup_ajax_compat_mode( $setting, $value ) {
        $mu_dir = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
        $source = WPBDP_INC . '/compatibility/wpbdp-ajax-compat-mu.php';
        $dest   = trailingslashit( $mu_dir ) . basename( $source );

        if ( 0 == $value && file_exists( $dest ) ) {
            if ( ! unlink( $dest ) ) {
                $message = array(
                    sprintf(
                        _x(
                            'Could not remove the "Business Directory Plugin - AJAX Compatibility Module". Please remove the file "%s" manually or deactivate the plugin.',
                            'admin settings',
                            'WPBDM'
                        ),
                        $dest
                    ),
                    'error',
                );
                update_option( 'wpbdp-ajax-compat-mode-notice', $message );
            }
        } elseif ( 1 == $value && ! file_exists( $dest ) ) {
            // Install plugin.
            $success = true;

            if ( ! wp_mkdir_p( $mu_dir ) ) {
                $message = array( sprintf( _x( 'Could not activate AJAX Compatibility mode: the directory "%s" could not be created.', 'admin settings', 'WPBDM' ), $mu_dir ), 'error' );
                $success = false;
            }

            if ( $success && ! copy( $source, $dest ) ) {
                $message = array( sprintf( _x( 'Could not copy the AJAX compatibility plugin "%s". Compatibility mode was not activated.', 'admin settings', 'WPBDM' ), $dest ), 'error' );
                $success = false;
            }

            if ( ! $success ) {
                update_option( 'wpbdp-ajax-compat-mode-notice', $message );
                wpbdp_set_option( $setting['id'], 0 );
            }
        }
    }

    private static function register_image_sizes() {
        $thumbnail_width  = absint( wpbdp_get_option( 'thumbnail-width' ) );
        $thumbnail_height = absint( wpbdp_get_option( 'thumbnail-height' ) );

        $max_width  = absint( wpbdp_get_option( 'image-max-width' ) );
        $max_height = absint( wpbdp_get_option( 'image-max-height' ) );

        $crop = (bool) wpbdp_get_option( 'thumbnail-crop' );

        add_image_size( 'wpbdp-mini', 50, 50, true ); // Used for the submit process.
        add_image_size( 'wpbdp-thumb', $thumbnail_width, $crop ? $thumbnail_height : 9999, $crop ); // Thumbnail size.
        add_image_size( 'wpbdp-large', $max_width, $max_height, false ); // Large size.
    }

    private static function get_registered_image_sizes() {
        self::register_image_sizes();

        global $_wp_additional_image_sizes;

        $sizes = array( 'uploaded' => _x( 'Uploaded Image (no resize)', 'admin settings', 'WPBDM' ) );

        foreach ( get_intermediate_image_sizes() as $_size ) {
            if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
                $name   = 'WP ' . ucwords( str_replace( '_', ' ', $_size ) );
                $width  = get_option( "{$_size}_size_w" );
                $height = get_option( "{$_size}_size_h" );
                $crop   = (bool) get_option( "{$_size}_crop" );
            } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
                $name   = ucwords( str_replace( 'wpbdp', 'Directory', str_replace( array( '_', '-' ), ' ', $_size ) ) );
                $name   = str_replace( 'Directory Thumb', 'Directory Thumbnail', $name );
                $width  = $_wp_additional_image_sizes[ $_size ]['width'];
                $height = $_wp_additional_image_sizes[ $_size ]['height'];
                $crop   = (bool) $_wp_additional_image_sizes[ $_size ]['crop'];
            }

            $sizes[ $_size ] = sprintf(
                '%s (%s x %s px %s) ',
                $name,
                $width,
                $height == 9999 ? '*' : $height,
                $crop ? _x( 'Cropped', 'settings', 'WPBDM' ) : ''
            );
        }

        return $sizes;
    }
}

// phpcs:enable
