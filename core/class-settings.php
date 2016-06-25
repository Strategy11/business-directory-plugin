<?php
class WPBDP_Settings {

    const PREFIX = 'wpbdp-';

    const _EMAIL_RENEWAL_MESSAGE = "Your listing \"[listing]\" in category [category] expired on [expiration]. To renew your listing click the link below.\n[link]";
    const _EMAIL_AUTORENEWAL_MESSAGE = "Hey [author],\n\nThanks for your payment. We just renewed your listing [listing] on [date] for another period.\n\nIf you have any questions, contact us at [site].";
    const _EMAIL_AUTORENEWAL_PENDING_MESSAGE = "Hey [author],\n\nThis is just to remind you that your listing [listing] is going to be renewed on [date] for another period.\nIf you want to review or cancel your subscriptions please visit [link].\n\nIf you have any questions, contact us at [site].";
    const _EMAIL_PENDING_RENEWAL_MESSAGE = 'Your listing "[listing]" is about to expire at [site]. You can renew it here: [link].';

    private $deps = array();


    public function __construct() {
        $this->groups = array();
        $this->settings = array();

        add_action( 'wp_ajax_wpbdp-admin-settings-email-preview', array( &$this, '_ajax_email_preview' ) );
        add_filter( 'wpbdp_settings_render', array( &$this, 'after_render' ), 0, 3 );
    }

    public function register_settings() {
        /* General settings */
        $g = $this->add_group('general', _x('General', 'admin settings', 'WPBDM'));

        $s = $this->add_section( $g, 'tracking', _x( 'Data Collection', 'admin settings', 'WPBDM' ) );
        $this->add_setting( $s,
                            'tracking-on',
                            _x( 'Allow BD to anonymously collect information about your installed plugins, themes and WP version?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            str_replace( '<a>',
                                         '<a href="http://businessdirectoryplugin.com/what-we-track/" target="_blank">',
                                         _x( '<a>Learn more</a> about what BD does and does NOT track.', 'admin settings', 'WPBDM' ) )
                          );

        $s = $this->add_section($g, 'permalink', _x('Permalink Settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'permalinks-directory-slug', _x('Directory Listings Slug', 'admin settings', 'WPBDM'), 'text', WPBDP_POST_TYPE, null, null, array($this, '_validate_listings_permalink'));
        $this->add_setting($s, 'permalinks-category-slug', _x('Categories Slug', 'admin settings', 'WPBDM'), 'text', WPBDP_CATEGORY_TAX, _x('The slug can\'t be in use by another term. Avoid "category", for instance.', 'admin settings', 'WPBDM'), null, array($this, '_validate_term_permalink'));
        $this->add_setting($s, 'permalinks-tags-slug', _x('Tags Slug', 'admin settings', 'WPBDM'), 'text', WPBDP_TAGS_TAX, _x('The slug can\'t be in use by another term. Avoid "tag", for instance.', 'admin settings', 'WPBDM'), null, array($this, '_validate_term_permalink'));
        $this->add_setting( $s,
                            'permalinks-no-id',
                            _x( 'Remove listing ID from directory URLs?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            _x( 'Prior to 3.5.1, we included the ID in the listing URL, like "/business-directory/1809/listing-title". Check this setting to remove the ID for better SEO.', 'admin settings', 'WPBDM' ) );

        $s = $this->add_section( $g,
                                 'recaptcha',
                                 _x( 'reCAPTCHA Settings', 'admin settings', 'WPBDM' ),
                                 str_replace( '<a>',
                                              '<a href="http://www.recaptcha.com" target="_blank">',
                                              _x( 'Need API keys for reCAPTCHA? Get them <a>here</a>.', 'admin settings', 'WPBDM' ) )
                                );
        $this->add_setting($s, 'recaptcha-on', _x('Use reCAPTCHA for contact forms', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'hide-recaptcha-loggedin', _x('Turn off reCAPTCHA for logged in users?', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'recaptcha-for-submits', _x('Use reCAPTCHA for listing submits', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting( $s,
                            'recaptcha-for-comments',
                            _x( 'Use reCAPTCHA for listing comments?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false );
        $this->add_setting($s, 'recaptcha-public-key', _x('reCAPTCHA Public Key', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'recaptcha-private-key', _x('reCAPTCHA Private Key', 'admin settings', 'WPBDM'));
        

       // {{ Registration settings.
//        $s = $this->add_group( 'registration',
//                               _x('Registration', 'admin settings', 'WPBDM' ) );
        $msg = _x( "We expect that a membership plugin supports the 'redirect_to' parameter for the URLs below to work. If the plugin does not support them, these settings will not function as expected. Please contact the membership plugin and ask them to support the WP standard 'redirect_to' query parameter.",
                   'admin settings',
                   'WPBDM' );
        $s = $this->add_section( $g, 'registration', _x( 'Registration Settings', 'admin settings', 'WPBDM' ), $msg );
        $this->add_setting($s, 'require-login', _x('Require login to post listings?', 'admin settings', 'WPBDM'), 'boolean', true);

        // deprecated as of 2.1, added again for 3.6.10
        $this->add_setting( $s,
                            'login-url',
                            _x( 'Login URL', 'admin settings', 'WPBDM'),
                            'text',
                            '',
                            _x( 'URL of your membership plugin\'s login page.  Only enter this if using a membership plugin or custom login page.', 'admin settings', 'WPBDM' ) );

        // deprecated as of 2.1, added again for 3.4
        $this->add_setting( $s,
                            'registration-url',
                            _x( 'Registration URL', 'admin settings', 'WPBDM' ),
                            'text',
                            '',
                            _x( 'URL of your membership plugin\'s registration page.  Only enter this if using a membership plugin or custom registration page.', 'admin settings', 'WPBDM' ) );
        // }}

        $s = $this->add_section( $g,
                                 'terms-and-conditions',
                                 _x( 'Terms and Conditions', 'admin settings', 'WPBDM' ) );
        $this->add_setting( $s,
                            'display-terms-and-conditions',
                            _x( 'Display and require user agreement to Terms and Conditions', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false
                          );
        $this->add_setting( $s,
                            'terms-and-conditions',
                            _x( 'Terms and Conditions', 'admin settings', 'WPBDM' ),
                            'text',
                            _x( "Terms and Conditions text goes here...\n\n", 'admin settings', 'WPBDM' ),
                            _x( 'Enter text or a URL starting with http. If you use a URL, the Terms and Conditions text will be replaced by a link to the appropiate page.', 'admin settings', 'WPBDM' ),
                            array( 'use_textarea' => true )
                            );

        $s = $this->add_section($g, 'displayoptions', _x('Directory Display Options', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'show-submit-listing', _x('Show the "Submit listing" button.', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'show-search-listings', _x('Show "Search listings".', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'show-view-listings', _x('Show the "View Listings" button.', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'show-directory-button', _x('Show the "Directory" button.', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting( $s, 'disable-cpt', _x( 'Disable advanced CPT integration.', 'admin settings', 'WPBDM' ), 'boolean', false );

        // {{ Directory search.
        $s = $this->add_section( $g,
                                 'search',
                                 _x( 'Directory Search', 'admin settings', 'WPBDM' ) );
//        $this->add_setting( $s,
//                            'show-search-form-in-results',
//                            _x( 'Display search form when displaying search results?', 'admin settings', 'WPBDM' ),
//                            'boolean',
//                            true );
        $this->add_setting( $s,
                            'search-form-in-results',
                            _x( 'Search form display', 'admin settings', 'WPBDM' ),
                            'choice',
                            'above',
                            '',
                            array( 'choices' => array( array( 'above', _x( 'Above results', 'admin settings', 'WPBDM' ) ),
                                                       array( 'below', _x( 'Below results', 'admin settings', 'WPBDM' ) ),
                                                       array( 'none', _x( 'Don\'t show with results', 'admin settings', 'WPBDM' ) ) ) ) );

        // Quick search fields.
        $desc  = '';
        $desc .= '<span class="text-fields-warning wpbdp-note" style="display: none;">';
        $desc .= _x( 'You have selected a textarea field to be included in quick searches. Searches involving those fields are very expensive and could result in timeouts and/or general slowness.', 'admin settings', 'WPBDM' );
        $desc .= '</span>';
        $desc .= _x( 'Use Ctrl-Click to include multiple fields in the search. Choosing too many fields for inclusion into Quick Search can result in very slow search performance.', 'admin settings', 'WPBDM' );
        $this->add_setting( $s,
                            'quick-search-fields',
                            _x( 'Quick search fields', 'admin settings', 'WPBDM' ),
                            'choice',
                            array(),
                            $desc,
                            array( 'choices' => array( &$this, 'quicksearch_fields_cb' ), 'use_checkboxes' => false, 'multiple' => true )
                         );
        $this->add_setting( $s,
                            'quick-search-enable-performance-tricks',
                            _x( 'Enable high performance searches?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            _x( 'Enabling this makes BD sacrifice result quality to improve speed. This is helpful if you\'re on shared hosting plans, where database performance is an issue.', 'admin settings', 'WPBDM' ) );
        // }}

        // Misc. settings.

        $s = $this->add_section($g, 'misc', _x('Miscellaneous Settings', 'admin settings', 'WPBDM'));
        // $this->add_setting($s, 'hide-tips', _x('Hide tips for use and other information?', 'admin settings', 'WPBDM'), 'boolean', false);

        $desc  = '';
        $desc .= _x( 'Check this if you are having trouble with BD, particularly when importing or exporting CSV files.', 'admin settings', 'WPBDM' );
        $desc .=str_replace( '<a>',
                             '<a href="http://businessdirectoryplugin.com/support-forum/faq/how-to-check-for-plugin-and-theme-conflicts-with-bd/" target="_blank">',
                             _x( 'If this compatibility mode doesn\'t solve your issue, you may be experiencing a more serious conflict. <a>Here is an article</a> about how to test for theme and plugin conflicts with Business Directory.', 'admin settings', 'WPBDM' ) );
        $this->add_setting( $s,
                            'ajax-compat-mode',
                            _x( 'Enable AJAX compatibility mode?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            $desc,
                            null,
                            array( &$this, 'setup_ajax_compat_mode' ) );

        /* Listings settings */
        $g = $this->add_group('listings', _x('Listings', 'admin settings', 'WPBDM'));
        $s = $this->add_section($g, 'general', _x('General Settings', 'admin settings', 'WPBDM'));

        $this->add_setting($s, 'listings-per-page', _x('Listings per page', 'admin settings', 'WPBDM'), 'text', '10',
                           _x('Number of listings to show per page. Use a value of "0" to show all listings.', 'admin settings', 'WPBDM'));

        $this->add_setting($s, 'listing-duration', _x('Listing duration for no-fee sites (in days)', 'admin settings', 'WPBDM'), 'text', '365',
                           _x('Use a value of "0" to keep a listing alive indefinitely or enter a number less than 10 years (3650 days).', 'admin settings', 'WPBDM'),
                           null,
                           array($this, '_validate_listing_duration'));

        $this->add_setting( $s,
                            'show-contact-form',
                            _x( 'Include listing contact form on listing pages?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            true,
                            _x( 'Allows visitors to contact listing authors privately. Authors will receive the messages via email.', 'admin settings', 'WPBDM' ) );
        $this->add_setting( $s,
                            'contact-form-require-login',
                            _x( 'Require login for using the contact form?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false );
        $this->register_dep( 'contact-form-require-login', 'requires-true', 'show-contact-form' );
        $this->add_setting( $s,
                            'contact-form-daily-limit',
                            _x( 'Maximum number of contact form submits per day', 'admin settings', 'WPBDM' ),
                            'text',
                            '0',
                            _x( 'Use this to prevent spamming of listing owners. 0 means unlimited submits per day.',
                                'admin settings',
                                'WPBDM') );
        $this->register_dep( 'contact-form-daily-limit', 'requires-true', 'show-contact-form' );
        $this->add_setting( $s,
                            'show-comment-form',
                            _x( 'Include comment form on listing pages?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            _x( 'Allow visitors to discuss listings using the standard WordPress comment form. Comments are public.', 'admin settings', 'WPBDM' ) );
        $this->add_setting($s, 'show-listings-under-categories', _x('Show listings under categories on main page?', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'status-on-uninstall', _x('Status of listings upon uninstalling plugin', 'admin settings', 'WPBDM'), 'choice', 'trash', '',
                           array('choices' => array( array( 'draft', _x( 'Draft', 'post status' ) ), array( 'trash', _x( 'Trash', 'post status' ) ) )));
        $this->add_setting($s, 'deleted-status', _x('Status of deleted listings', 'admin settings', 'WPBDM'), 'choice', 'trash', '',
                           array('choices' => array( array( 'draft', _x( 'Draft', 'post status' ) ), array( 'trash', _x( 'Trash', 'post status' ) ) )));
        $this->add_setting( $s, 'submit-instructions', _x( 'Submit Listing instructions message', 'admin settings', 'WPBDM' ), 'text','', _x( 'This text is displayed at the first page of the Submit Listing process for Business Directory. You can use it for instructions about filling out the form or anything you want to tell users before they get started.', 'admin settings', 'WPBDM' ), array( 'use_textarea' => true ) );

        $s = $this->add_section($g, 'listings/renewals', _x('Listing Renewal', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'listing-renewal', _x('Turn on listing renewal option?', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting( $s,
                            'listing-renewal-auto',
                            _x( 'Allow recurring renewal payments?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            _x( 'Allow users to opt in for automatic renewal of their listings. The fee is charged at the time the listing expires without user intervention.', 'admin settings', 'WPBDM' )
                          );
        $this->add_setting( $s,
                            'listing-renewal-auto-dontask',
                            _x( 'Use recurring payments as the default payment method?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            _x( 'Enable automatic renewal without having users opt in during the submit process.', 'admin settings', 'WPBDM' ) );
        $this->register_dep( 'listing-renewal-auto-dontask', 'requires-true', 'listing-renewal-auto' );

        $this->add_setting( $s,
                            'renewal-email-threshold',
                            _x( 'Listing renewal e-mail threshold (in days)', 'admin settings', 'WPBDM' ),
                            'text',
                            '5',
                            _x( 'Configure how many days before listing expiration is the renewal e-mail sent.', 'admin settings', 'WPBDM' )
                            );
        $this->add_setting( $s,
                            'send-autorenewal-expiration-notice',
                            _x( 'Send expiration notices including a cancel links to auto-renewed listings?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false );

        // Renewal Reminders
        $this->add_setting( $s,
                            'renewal-reminder',
                            _x( 'Remind listing owners of expired listings (past due)?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false );
        $this->add_setting( $s,
                            'renewal-reminder-threshold',
                            _x( 'Listing renewal reminder e-mail threshold (in days)', 'admin settings', 'WPBDM' ),
                            'text',
                            '10',
                            _x( 'Configure how many days after the expiration of a listing an e-mail reminder should be sent to the owner.', 'admin settings', 'WPBDM' )
                          );

        $s = $this->add_section($g, 'post/category', _x('Post/Category Settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'new-post-status', _x('Default new post status', 'admin settings', 'WPBDM'), 'choice', 'pending', '',
                           array('choices' => array( array( 'publish', _x( 'Published', 'post status' ) ), array( 'pending', _x( 'Pending', 'post status' ) ) ))
                           );
        $this->add_setting($s, 'edit-post-status', _x('Edit post status', 'admin settings', 'WPBDM'), 'choice', 'publish', '',
                           array('choices' => array( array( 'publish', _x( 'Published', 'post status' ) ), array( 'pending', _x( 'Pending', 'post status' ) ) ) ) );
        $this->add_setting( $s, 'categories-order-by', _x('Order categories list by', 'admin settings', 'WPBDM'), 'choice', 'name', '',
                           array('choices' => array(
                            array( 'name', _x( 'Name', 'admin settings', 'WPBDM' ) ),
                            array( 'slug', _x( 'Slug', 'admin settings', 'WPBDM' ) ),
                            array( 'count', _x( 'Listing Count', 'admin settings', 'WPBDM' ) )
                           )) );
        $this->add_setting( $s, 'categories-sort', _x('Sort order for categories', 'admin settings', 'WPBDM'), 'choice', 'ASC', '',
                           array('choices' => array(array('ASC', _x('Ascending', 'admin settings', 'WPBDM')), array('DESC', _x('Descending', 'admin settings', 'WPBDM')))));
        $this->add_setting($s, 'show-category-post-count', _x('Show category post count?', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'hide-empty-categories', _x('Hide empty categories?', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'show-only-parent-categories', _x('Show only parent categories in category list?', 'admin settings', 'WPBDM'), 'boolean', false);

        $s = $this->add_section( $g, 'post/sorting', _x( 'Listings Sorting', 'admin settings', 'WPBDM' ) );
        $this->add_setting($s, 'listings-order-by', _x('Order directory listings by', 'admin settings', 'WPBDM'), 'choice', 'title', '',
                          array('choices' => array(
                            array( 'title', _x( 'Title', 'admin settings', 'WPBDM' ) ),
                            array( 'author', _x( 'Author', 'admin settings', 'WPBDM' ) ),
                            array( 'date', _x( 'Date posted', 'admin settings', 'WPBDM' ) ),
                            array( 'modified', _x( 'Date last modified', 'admin settings', 'WPBDM' ) ),
                            array( 'rand', _x( 'Random', 'admin settings', 'WPBDM' ) ),
                            array( 'paid', _x( 'Paid first then free. Inside each group by date.', 'admin settings', 'WPBDM' ) ),
                            array( 'paid-title', _x( 'Paid first then free. Inside each group by title.', 'admin settings', 'WPBDM' ) )
                          )));
        $this->add_setting( $s, 'listings-sort', _x('Sort directory listings by', 'admin settings', 'WPBDM'), 'choice', 'ASC',
                           _x('Ascending for ascending order A-Z, Descending for descending order Z-A', 'admin settings', 'WPBDM'),
                           array('choices' => array(array('ASC', _x('Ascending', 'admin settings', 'WPBDM')), array('DESC', _x('Descending', 'admin settings', 'WPBDM')))));

        $this->add_setting( $s,
                            'listings-sortbar-enabled',
                            _x( 'Enable sort bar?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false );
        $this->add_setting( $s,
                            'listings-sortbar-fields',
                            _x( 'Sortbar Fields', 'admin settings', 'WPBDM' ),
                            'choice',
                            array(),
                            '',
                            array( 'choices' => array( &$this, 'sortbar_fields_cb' ),
                                   'use_checkboxes' => true,
                                   'multiple' =>true ) );
        $this->register_dep( 'listings-sortbar-fields', 'requires-true', 'listings-sortbar-enabled' );

        $s = $this->add_section($g, 'featured', _x('Featured (Sticky) listing settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'featured-on', _x('Offer sticky listings?', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'featured-offer-in-submit', _x('Offer upgrades during submit process?', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'featured-price', _x('Sticky listing price', 'admin settings', 'WPBDM'), 'text', '39.99');
        $this->add_setting($s, 'featured-description', _x('Sticky listing page description text', 'admin settings', 'WPBDM'), 'text',
                           _x('You can upgrade your listing to featured status. Featured listings will always appear on top of regular listings.', 'admin settings', 'WPBDM'));

        /*
         * E-Mail settings.
         */
        $g = $this->add_group( 'email', _x( 'E-Mail', 'admin settings', 'WPBDM' ) );
        $s = $this->add_section( $g, 'email-general', _x( 'General Settings', 'admin settings', 'WPBDM' ) );
        $this->add_setting( $s,
                            'override-email-blocking',
                            _x( 'Display email address fields publicly?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                           _x('Shows the email address of the listing owner to all web users. NOT RECOMMENDED as this increases spam to the address and allows spam bots to harvest it for future use.', 'admin settings', 'WPBDM') );
        $this->add_setting( $s,
                            'listing-email-mode',
                            _x( 'How to determine the listing\'s email address?', 'admin settings', 'WPBDM' ),
                            'choice',
                            'field',
                            _x( 'This affects emails sent to listing owners via contact forms or when their listings expire.', 'admin settings', 'WPBDM' ),
                            array( 'choices' => array(
                                array( 'field', _x( 'Try listing\'s email field first, then author\'s email.', 'admin settings', 'WPBDM' ) ),
                                array( 'user',  _x( 'Try author\'s email first and then listing\'s email field.', 'admin settings', 'WPBDM' ) )

                            ) ) );

        $s = $this->add_section( $g, 'email-notifications', _x( 'E-Mail Notifications', 'admin settings', 'WPBDM' ) );
        $this->add_setting( $s,
                            'admin-notifications',
                            _x( 'Notify admin via e-mail when...', 'admin settings', 'WPBDM' ),
                            'choice',
                            array(),
                            '',
                            array( 'choices' => array( 'new-listing' => _x( 'A new listing is submitted.', 'admin settings', 'WPBDM' ),
                                                       'listing-edit' => _x( 'A listing is edited.', 'admin settings', 'WPBDM' ),
                                                       'renewal' => _x( 'A listing expires.', 'admin settings', 'WPBDM' ),
                                                       'listing-contact' => _x( 'A contact message is sent to a listing\'s owner.', 'admin settings', 'WPBDM' ) ),
                                   'use_checkboxes' => true,
                                   'multiple' => true )
                          );
        $this->add_setting( $s,
                            'admin-notifications-cc',
                            _x( 'CC this e-mail address too', 'admin settings', 'WPBDM' ),
                            'text',
                            '' );

        $this->add_setting( $s,
                            'user-notifications',
                            _x( 'Notify users via e-mail when...', 'admin settings', 'WPBDM' ),
                            'choice',
                            array( 'new-listing', 'listing-published'/*, 'payment-status-change'*/ ),
                            _x( 'You can modify the text template used for most of these e-mails below.', 'admin settings', 'WPBDM' ),
                            array( 'choices' => array( 'new-listing' => _x( 'Their listing is submitted.', 'admin settings', 'WPBDM' ),
                                                       'listing-published' => _x( 'Their listing is approved/published.', 'admin settings', 'WPBDM' )/*,
                                                       'payment-status-change' => _x( 'A payment status changes (sends a receipt).', 'admin settings', 'WPBDM' ),*/
                                                        ),
                                   'use_checkboxes' => true,
                                   'multiple' => true )
                          );

        // Listing contact.
        $email_contact_template  = '';
        $email_contact_template .= sprintf( _x( 'You have received a reply from your listing at %s.', 'contact email', 'WPBDM' ), '[listing-url]' ) . "\n\n";
        $email_contact_template .= sprintf( _x( 'Name: %s', 'contact email', 'WPBDM' ), '[name]' ) . "\n";
        $email_contact_template .= sprintf( _x( 'E-Mail: %s', 'contact email', 'WPBDM' ), '[email]' ) . "\n";
        $email_contact_template .= _x( 'Message:', 'contact email', 'WPBDM' ) . "\n";
        $email_contact_template .= '[message]' . "\n\n";
        $email_contact_template .= sprintf( _x( 'Time: %s', 'contact email', 'WPBDM' ), '[date]' );

        $s = $this->add_section( $g, 'email/templates', _x( 'E-Mail Templates', 'admin settings', 'WPBDM' ) );

        $this->add_setting( $s,
                            'email-confirmation-message', _x( 'Email confirmation message', 'admin settings', 'WPBDM' ),
                            'email_template',
                            array( 'subject' => '[[site-title]] Listing "[listing]" received',
                                   'body' => 'Your submission \'[listing]\' has been received and it\'s pending review. This review process could take up to 48 hours.' ),
                            _x( 'Sent after a listing has been submitted.', 'admin settings', 'WPBDM' ),
                            array( 'placeholders' => array( 'listing' => array( _x( 'Listing\'s title', 'admin settings', 'WPBDM' ) ) ) )
                          );
        $this->add_setting( $s,
                            'email-templates-listing-published', _x( 'Listing published message', 'admin settings', 'WPBDM' ),
                            'email_template',
                            array( 'subject' => '[[site-title]] Listing "[listing]" published',
                                   'body' => _x( 'Your listing "[listing]" is now available at [listing-url] and can be viewed by the public.', 'admin settings', 'WPBDM' ) ),
                            _x( 'Sent when the listing has been published or approved by an admin.', 'admin settings', 'WPBDM' ),
                            array( 'placeholders' => array( 'listing' => _x( 'Listing\'s title', 'admin settings', 'WPBDM' ),
                                                            'listing-url' => _x( 'Listing\'s URL', 'admin settings', 'WPBDM' ) ) )
                          );
        $this->add_setting( $s,
                            'email-templates-contact',
                            _x( 'Listing Contact Message', 'admin settings', 'WPBDM' ),
                            'email_template',
                            array( 'subject' => '[[site-title]] Contact via "[listing]"',
                                   'body'    => $email_contact_template ),
                            _x( 'Sent to listing owners when someone uses the contact form on their listing pages.', 'admin settings', 'WPBDM' ),
                            array( 'placeholders' => array( 'listing-url' => 'Listing\'s URL',
                                                            'listing' => 'Listing\'s title',
                                                            'name' => 'Sender\'s name',
                                                            'email' => 'Sender\'s e-mail address',
                                                            'message' => 'Contact message',
                                                            'date' => 'Date and time the message was sent' ) ) );

        $s = $this->add_section( $g,
                                 'email-payments',
                                 _x( 'Payment related', 'admin settings', 'WPBDM' ) );
        $body_template = <<<EOF
Hi there,

We noticed that you tried submitting a listing on [site-link] but didn't finish
the process.  If you want to complete the payment and get your listing
included, just click here to continue:

[link]

If you have any issues, please contact us directly by hitting reply to this
email!

Thanks,
- The Administrator of [site-title]
EOF;
        $this->add_setting( $s,
                            'email-templates-payment-abandoned', _x( 'Payment abandoned reminder message', 'admin settings', 'WPBDM' ),
                            'email_template',
                            array( 'subject' => '[[site-title]] Pending payment for "[listing]"',
                                   'body' => $body_template ),
                            _x( 'Sent some time after a pending payment is abandoned by users.', 'admin settings', 'WPBDM' ),
                            array( 'placeholders' => array( 'listing' => _x( 'Listing\'s title', 'admin settings', 'WPBDM' ),
                                                            'link' => _x( 'Checkout URL link', 'admin settings', 'WPBDM' ) ) )
                          );

        $url = admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=listings' ) . '#listings/renewals';
        $s = $this->add_section( $g,
                                 'email-renewal-reminders',
                                 _x( 'Renewal Reminders', 'admin settings', 'WPBDM' ),
                                 str_replace( '<a>',
                                              '<a href="' . esc_url( $url ) . '">',
                                              _x( 'This section refers only to the text of the renewal/expiration notices. You can also <a>configure when the e-mails are sent</a>.', 'admin settings', 'WPBDM' ) ) );

        $this->add_setting( $s,
                'renewal-pending-message',
                _x( 'Pending expiration e-mail message', 'admin settings', 'WPBDM' ),
                'email_template',
                array( 'subject' => '[[site-title]] [listing] - Expiration notice',
                       'body' => self::_EMAIL_PENDING_RENEWAL_MESSAGE ),
                _x( 'Sent some time before the listing expires. Applies to non-recurring renewals only.', 'settings', 'WPBDM' ),
                array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
                                                'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
                                                'expiration' => _x( 'Expiration date', 'settings', 'WPBDM' ),
                                                'category' => _x( 'Category that is going to expire', 'settings', 'WPBDM' ),
                                                'link' => _x( 'Link to renewal page', 'settings', 'WPBDM' ),
                                                'site' => _x( 'Link to your site', 'settings', 'WPBDM' )  ) )
                );
        $this->add_setting( $s,
                            'listing-renewal-message', _x('Listing Renewal e-mail message', 'admin settings', 'WPBDM'),
                            'email_template',
                            array( 'subject' => '[[site-title]] [listing] - Expiration notice',
                                   'body' => self::_EMAIL_RENEWAL_MESSAGE ),
                            _x( 'Sent at the time of listing expiration. Applies to non-recurring renewals only.', 'settings', 'WPBDM' ),
                            array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
                                                            'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
                                                            'expiration' => _x( 'Expiration date', 'settings', 'WPBDM' ),
                                                            'category' => _x( 'Category that expired', 'settings', 'WPBDM' ),
                                                            'link' => _x( 'Link to renewal page', 'settings', 'WPBDM' ),
                                                            'site' => _x( 'Link to your site', 'settings', 'WPBDM' )  ) )
                          );
        $this->add_setting( $s,
                            'listing-autorenewal-notice', _x( 'Listing auto-renewal reminder (recurring payments)', 'admin settings', 'WPBDM'),
                            'email_template',
                            array( 'subject' => '[[site-title]] [listing] - Renewal reminder',
                                   'body' => self::_EMAIL_AUTORENEWAL_PENDING_MESSAGE ),
                            _x( 'Sent some time before the listing is auto-renewed. Applies to recurring renewals only.', 'settings', 'WPBDM' ),
                            array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
                                                            'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
                                                            'date' => _x( 'Renewal date', 'settings', 'WPBDM' ),
                                                            'category' => _x( 'Category that is going to be renewed', 'settings', 'WPBDM' ),
                                                            'site' => _x( 'Link to your site', 'settings', 'WPBDM' ),
                                                            'link' => _x( 'Link to manage subscriptions', 'settings', 'WPBDM' ) ) )
                          );
        $this->add_setting( $s,
                            'listing-autorenewal-message', _x('Listing Renewal e-mail message (recurring payments)', 'admin settings', 'WPBDM'),
                            'email_template',
                            array( 'subject' => '[[site-title]] [listing] renewed',
                                   'body' => self::_EMAIL_AUTORENEWAL_MESSAGE ),
                            _x( 'Sent after the listing is auto-renewed. Applies to recurring renewals only.', 'settings', 'WPBDM' ),
                            array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
                                                            'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
                                                            'category' => _x( 'Renewed category', 'settings', 'WPBDM' ),
                                                            'date' => _x( 'Renewal date', 'settings', 'WPBDM' ),
                                                            'site' => _x( 'Link to your site', 'settings', 'WPBDM' ) ) )
                          );
        $this->add_setting( $s,
                            'renewal-reminder-message',
                            _x( 'Renewal reminder e-mail message', 'admin settings', 'WPBDM' ),
                            'email_template',
                            array( 'subject' => '[[site-title]] [listing] - Expiration reminder',
                                   'body' => "Dear Customer\nWe've noticed that you haven't renewed your listing \"[listing]\" for category [category] at [site] and just wanted to remind you that it expired on [expiration]. Please remember you can still renew it here: [link]." ),
                            _x( 'Sent some time after listing expiration and when no renewal has occurred. Applies to both recurring and non-recurring renewals.', 'settings', 'WPBDM' ),
                            array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
                                                            'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
                                                            'expiration' => _x( 'Expiration date', 'settings', 'WPBDM' ),
                                                            'category' => _x( 'Category that expired', 'settings', 'WPBDM' ),
                                                            'link' => _x( 'Link to renewal page', 'settings', 'WPBDM' ),
                                                            'site' => _x( 'Link to your site', 'settings', 'WPBDM' )  ) )
                          );

        /* Payment settings */
        $g = $this->add_group('payment', _x('Payment', 'admin settings', 'WPBDM'));
        $s = $this->add_section($g, 'general', _x('Payment Settings', 'admin settings', 'WPBDM'));

        $this->add_setting( $s, 'fee-order', 'Fee Order', 'core', array( 'method' => 'label', 'order' => 'asc' ) );
        $this->add_setting($s, 'payments-on', _x('Turn On payments?', 'admin settings', 'WPBDM'), 'boolean', false);

        $this->add_setting($s, 'payments-test-mode', _x('Put payment gateways in test mode?', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->register_dep( 'payments-test-mode', 'requires-true', 'payments-on' );

        $this->add_setting( $s,
                            'payments-use-https',
                            _x( 'Perform checkouts on the secure (HTTPS) version of your site?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            _x( 'Recommended for added security. For this to work you need to enable HTTPS on your server and <a>obtain an SSL certificate</a>.', 'admin settings', 'WPBDM' ) );
        $this->register_dep( 'payments-use-https', 'requires-true', 'payments-on' );

        // PayPal currency codes from https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_currency_codes
        $this->add_setting($s, 'currency', _x('Currency Code', 'admin settings', 'WPBDM'), 'choice', 'USD', '',
                            array('choices' => array(
                                array('AUD', _x('Australian Dollar (AUD)', 'admin settings', 'WPBDM')),
                                array('BRL', _x('Brazilian Real (BRL)', 'admin settings', 'WPBDM')),
                                array('CAD', _x('Canadian Dollar (CAD)', 'admin settings', 'WPBDM')),
                                array('CZK', _x('Czech Koruna (CZK)', 'admin settings', 'WPBDM')),
                                array('DKK', _x('Danish Krone (DKK)', 'admin settings', 'WPBDM')),
                                array('EUR', _x('Euro (EUR)', 'admin settings', 'WPBDM')),
                                array('HKD', _x('Hong Kong Dollar (HKD)', 'admin settings', 'WPBDM')),
                                array('HUF', _x('Hungarian Forint (HUF)', 'admin settings', 'WPBDM')),
                                array('ILS', _x('Israeli New Shequel (ILS)', 'admin settings', 'WPBDM')),
                                array('JPY', _x('Japanese Yen (JPY)', 'admin settings', 'WPBDM')),
                                array('MYR', _x('Malasian Ringgit (MYR)', 'admin settings', 'WPBDM')),
                                array('MXN', _x('Mexican Peso (MXN)', 'admin settings', 'WPBDM')),
                                array('NOK', _x('Norwegian Krone (NOK)', 'admin settings', 'WPBDM')),
                                array('NZD', _x('New Zealand Dollar (NZD)', 'admin settings', 'WPBDM')),
                                array('PHP', _x('Philippine Peso (PHP)', 'admin settings', 'WPBDM')),
                                array('PLN', _x('Polish Zloty (PLN)', 'admin settings', 'WPBDM')),
                                array('GBP', _x('Pound Sterling (GBP)', 'admin settings', 'WPBDM')),
                                array('SGD', _x('Singapore Dollar (SGD)', 'admin settings', 'WPBDM')),
                                array('SEK', _x('Swedish Krona (SEK)', 'admin settings', 'WPBDM')),
                                array('CHF', _x('Swiss Franc (CHF)', 'admin settings', 'WPBDM')),
                                array('TWD', _x('Taiwan Dollar (TWD)', 'admin settings', 'WPBDM')),
                                array('THB', _x('Thai Baht (THB)', 'admin settings', 'WPBDM')),
                                array('TRY', _x('Turkish Lira (TRY)', 'admin settings', 'WPBDM')),
                                array('USD', _x('U.S. Dollar (USD)', 'admin settings', 'WPBDM')),
                            )));
        $this->register_dep( 'currency', 'requires-true', 'payments-on' );

        $this->add_setting($s, 'currency-symbol', _x('Currency Symbol', 'admin settings', 'WPBDM'), 'text', '$');
        $this->register_dep( 'currency-symbol', 'requires-true', 'payments-on' );

        $this->add_setting( $s,
                            'currency-symbol-position',
                            _x( 'Currency symbol display', 'admin settings', 'WPBDM' ),
                            'choice',
                            'left',
                            '',
                            array( 'choices' => array( array( 'left', _x( 'Show currency symbol on the left', 'admin settings', 'WPBDM' ) ),
                                                       array( 'right', _x( 'Show currency symbol on the right', 'admin settings', 'WPBDM' ) ),
                                                       array( 'none', _x( 'Do not show currency symbol', 'admin settings', 'WPBDM' ) ) ) ) );

        $this->add_setting($s, 'payment-message', _x('Thank you for payment message', 'admin settings', 'WPBDM'), 'text',
                        _x('Thank you for your payment. Your payment is being verified and your listing reviewed. The verification and review process could take up to 48 hours.', 'admin settings', 'WPBDM'));
        $this->register_dep( 'payment-message', 'requires-true', 'payments-on' );

        $this->add_setting( $s,
                            'payment-abandonment',
                            _x( 'Ask users to come back for abandoned payments?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            _x( 'An abandoned payment is when a user attempts to place a listing and gets to the end, but fails to complete their payment for the listing. This results in listings that look like they failed, when the user simply didn\'t complete the transaction.  BD can remind them to come back and continue.', 'admin settings', 'WPBDM' )
                );

        $this->register_dep( 'payment-abandonment', 'requires-true', 'payments-on' );
        $this->add_setting( $s,
                            'payment-abandonment-threshold',
                            _x( 'Listing abandonment threshold (hours)', 'admin settings', 'WPBDM' ),
                            'text',
                            '24',
                            str_replace( '<a>',
                                         '<a href="' . admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=email' ) . '#email-templates-payment-abandoned">',
                                         _x( 'Listings with pending payments are marked as abandoned after this time. You can also <a>customize the e-mail</a> users receive.', 'admin settings', 'WPBDM' )
                                        ) );
        $this->register_dep( 'payment-abandonment-threshold', 'requires-true', 'payment-abandonment' );

        // TODO: we probably should merge this and 'Images' into an 'Appearance' tab. {
        $g = $this->add_group( 'themes', _x( 'Themes', 'admin settings', 'WPBDM' ) );

        $msg = str_replace( '<a>', '<a href="' . admin_url( 'admin.php?page=wpbdp-themes' ) . '">', _x( 'You can manage your themes on <a>Directory Themes</a>.', 'admin settings', 'WPBDM' ) );
        $s = $this->add_section( $g, 'general', _x( 'General Settings', 'admin settings', 'WPBDM' ), $msg );

        $this->add_setting( $s,
                            'themes-button-style',
                            _x( 'Theme button style', 'admin settings', 'WPBDM' ),
                            'choice',
                            'theme',
                            '',
                            array( 'choices' => array( array( 'theme', _x( 'Use the BD theme style for BD buttons', 'admin settings', 'WPBDM' ) ),
                                                       array( 'none', _x( 'Use the WP theme style for BD buttons', 'admin settings', 'WPBDM' ) )  ),
                            'use_checkboxes' => false ) );
        // }


        /* Image settings */
        $g = $this->add_group( 'image',
                               _x( 'Image', 'admin settings', 'WPBDM' ),
                               _x( 'Any changes to these settings will affect new listings only.  Existing listings will not be affected.  If you wish to change existing listings, you will need to re-upload the image(s) on that listing after changing things here.', 'admin settings', 'WPBDM' ) );
        $s = $this->add_section($g, 'image', _x('Image Settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'allow-images', _x('Allow images?', 'admin settings', 'WPBDM'), 'boolean', true);

        $this->add_setting($s, 'image-min-filesize', _x('Min Image File Size (KB)', 'admin settings', 'WPBDM'), 'text', '0' );
        $this->add_setting($s, 'image-max-filesize', _x('Max Image File Size (KB)', 'admin settings', 'WPBDM'), 'text', '10000');

        $this->add_setting($s, 'image-min-width', _x( 'Min image width (px)', 'admin settings', 'WPBDM'), 'text', '0' );
        $this->add_setting($s, 'image-min-height', _x( 'Min image height (px)', 'admin settings', 'WPBDM'), 'text', '0' );

        $this->add_setting($s, 'image-max-width', _x('Max image width (px)', 'admin settings', 'WPBDM'), 'text', '500');
        $this->add_setting($s, 'image-max-height', _x('Max image height (px)', 'admin settings', 'WPBDM'), 'text', '500');

        $this->add_setting( $s, 'use-thickbox', _x( 'Turn on thickbox/lightbox?', 'admin settings', 'WPBDM' ), 'boolean', false, _x( 'Uncheck if it conflicts with other elements or plugins installed on your site', 'admin settings', 'WPBDM' ) );

        $s = $this->add_section( $g, 'image/thumbnails', _x( 'Thumbnails', 'admin settings', 'WPBDM' ) );
        $this->add_setting($s, 'thumbnail-width', _x('Thumbnail width (px)', 'admin settings', 'WPBDM'), 'text', '150');
        $this->add_setting($s, 'thumbnail-height', _x('Thumbnail height (px)', 'admin settings', 'WPBDM'), 'text', '150');
        $this->add_setting( $s,
                            'thumbnail-crop',
                            _x( 'Crop thumbnails to exact dimensions?', 'admin settings', 'WPBDM'),
                            'boolean',
                            false,
                            _x( 'When enabled images will match exactly the dimensions above but part of the image may be cropped out. If disabled, image thumbnails will be resized to match the specified width and their height will be adjusted proportionally. Depending on the uploaded images, thumbnails may have different heights.', 'admin settings', 'WPBDM' )
                        );

        $s = $this->add_section($g, 'listings', _x('Listings', 'admin settings', 'WPBDM'));
        $this->add_setting( $s,
                            'free-images',
                            _x( 'Number of free images', 'admin settings', 'WPBDM' ),
                            'text',
                            '2',
                           str_replace( '<a>',
                                        '<a href="' . admin_url( 'admin.php?page=wpbdp_admin_fees' ) . '">',
                                        _x( 'For paid listing images, configure that by adding or editing a <a>Fee Plan</a> instead of this setting, which is ignored for paid listings.', 'admin settings', 'WPBDM' ) ),
                           null,
                           array( &$this, '_validate_free_images' ) );
        $this->add_setting($s, 'use-default-picture', _x('Use default picture for listings with no picture?', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'show-thumbnail', _x('Show Thumbnail on main listings page?', 'admin settings', 'WPBDM'), 'boolean', true);
    }

    public function quicksearch_fields_cb() {
        $fields = array();

        foreach ( wpbdp_get_form_fields( 'association=-custom' ) as $field ) {
            $is_text_field = false;

            if ( in_array( $field->get_association(), array( 'excerpt', 'content' ) ) || 'textarea' == $field->get_field_type_id() )
                $is_text_field = true;

            $fields[] = array( $field->get_id(), $field->get_label(), $is_text_field ? 'textfield' : '' );
        }

        return $fields;
    }

    public function sortbar_fields_cb() {
        $fields = array();

        foreach (  wpbdp_get_form_fields() as $f ) {
            if ( in_array( $f->get_field_type_id(), array( 'textarea', 'select', 'checkbox', 'url' ), true ) ||
                 in_array( $f->get_association(), array( 'category', 'tags' ), true ) )
                continue;

            $fields[ $f->get_id() ] = $f->get_label();
        }

        $fields['user_login'] = _x( 'User', 'admin settings', 'WPBDM' );
        $fields['user_registered'] = _x( 'User registration date', 'admin settings', 'WPBDM' );
        $fields['date'] = _x( 'Date posted', 'admin settings', 'WPBDM' );
        $fields['modified'] = _x( 'Date last modified', 'admin settings', 'WPBDM' );

        return $fields;
    }

    public function _validate_listings_permalink($setting, $newvalue, $oldvalue=null) {
        return trim(str_replace(' ', '', $newvalue));
    }

    public function setup_ajax_compat_mode( $setting, $newvalue, $oldvalue = null ) {
        if ( $newvalue == $oldvalue )
            return;

        $mu_dir = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
        $source = WPBDP_PATH . 'core/compatibility/wpbdp-ajax-compat-mu.php';
        $dest   = trailingslashit( $mu_dir ) . basename( $source );

        $message = false;
        $install = (bool) $newvalue;

        if ( $install ) {
            // Install plugin.
            if ( wp_mkdir_p( $mu_dir ) ) {
                if ( ! copy( $source, $dest ) ) {
                    $message = array( sprintf( _x( 'Could not copy the AJAX compatibility plugin "%s". Compatibility mode was not activated.', 'admin settings', 'WPBDM' ),
                                               $dest ),
                                      'error' );
                    $newvalue = $oldvalue;
                }/* else {
                    $message = _x( 'AJAX compatibility mode activated. "Business Directory Plugin - AJAX Compatibility Module" was installed.', 'admin settings', 'WPBDM' );
                }*/
            } else {
                $message = array( sprintf( _x( 'Could not activate AJAX Compatibility mode: the directory "%s" could not be created.', 'admin settings', 'WPBDM' ),
                                           $mu_dir ),
                                  'error' );
                $newvalue = $oldvalue;
            }
        } else {
            // Uninstall.
            if ( file_exists( $dest ) && ! unlink( $dest ) ) {
                $message = array(
                    sprintf( _x( 'Could not remove the "Business Directory Plugin - AJAX Compatibility Module". Please remove the file "%s" manually or deactivate the plugin.',
                                 'admin settings',
                                 'WPBDM' ),
                             $dest ),
                    'error'
                );

                $newvalue = $oldvalue;
            }
        }

        if ( $message )
            update_option( 'wpbdp-ajax-compat-mode-notice', $message );

        return $newvalue;
    }

    public function _validate_term_permalink($setting, $newvalue, $oldvalue=null) {
        $bd_taxonomy = $setting->name == 'permalinks-category-slug' ? WPBDP_CATEGORY_TAX : WPBDP_TAGS_TAX;
        foreach (get_taxonomies(null, 'objects') as $taxonomy) {
            if ($taxonomy->rewrite && $taxonomy->rewrite['slug'] == $newvalue && $taxonomy->name != $bd_taxonomy) {
                return $oldvalue;
            }
        }

        return trim(str_replace(' ', '', $newvalue));
    }

    public function _validate_free_images( $setting, $newvalue, $oldvalue = null ) {
        $v = absint( $newvalue );

        global $_wpbdp_fee_plan_recursion_guard;
        if ( ! isset( $_wpbdp_fee_plan_recursion_guard ) || ! $_wpbdp_fee_plan_recursion_guard ) {
            $freeplan = WPBDP_Fee_Plan::get_free_plan();
            $freeplan->update( array( 'images' => $v ) );
        }

        return $v;
    }

    public function _validate_listing_duration($setting, $newvalue, $oldvalue=null) {
        // limit 'duration' because of TIMESTAMP limited range (issue #157).
        // FIXME: this is not a long-term fix. we should move to DATETIME to avoid this entirely.
        $v = min(max(intval($newvalue), 0), 3650);

        global $_wpbdp_fee_plan_recursion_guard;
        if ( ! isset( $_wpbdp_fee_plan_recursion_guard ) || ! $_wpbdp_fee_plan_recursion_guard ) {
            $freeplan = WPBDP_Fee_Plan::get_free_plan();
            $freeplan->update( array( 'days' => $v ) );
        }

        return $v;
    }

    public function add_group($slug, $name, $help_text='') {
        $group = new StdClass();
        $group->wpslug = self::PREFIX . $slug;
        $group->slug = $slug;
        $group->name = esc_attr( $name );
        $group->help_text = $help_text;
        $group->sections = array();

        $this->groups[$slug] = $group;

        return $slug;
    }

    public function add_section($group_slug, $slug, $name, $help_text='') {
        $section = new StdClass();
        $section->name = esc_attr( $name );
        $section->slug = $slug;
        $section->help_text = $help_text;
        $section->settings = array();

        $this->groups[$group_slug]->sections[$slug] = $section;

        return "$group_slug:$slug";
    }

    public function add_core_setting( $name, $default=null ) {
        $setting = new StdClass();
        $setting->name = $name;
        $setting->label = '';
        $setting->help_text = '';
        $setting->default = $default;
        $setting->type = 'core';
        $setting->args = array();
        $setting->validator = '';

        if ( !isset( $this->settings[ $name ] ) ) {
            $this->settings[ $name ] = $setting;
        }

        return true;
    }

    public function add_setting( $section_key, $name, $label, $type = 'text', $default = null, $help_text = '', $args = array(),
                                 $validator = null, $callback = null ) {

        if ( $type == 'core' )
            return $this->add_core_setting( $name, $default );

        list($group, $section) = explode(':', $section_key);
        $args = !$args ? array() : $args;

        if (!$group || !$section)
            return false;

        if ( isset($this->groups[$group]) && isset($this->groups[$group]->sections[$section]) ) {
            $_default = $default;
            if (is_null($_default)) {
                switch ($type) {
                    case 'text':
                    case 'choice':
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
            $setting->name = esc_attr( $name );
            $setting->label = $label;
            $setting->help_text = $help_text;
            $setting->default = $_default;
            $setting->type = $type;
            $setting->args = $args;
            $setting->validator = $validator;
            $setting->callback = $callback;

            $setup_cb = '_setting_' . $setting->type . '_setup';
            if ( is_callable( array( $this, $setup_cb ) ) ) {
                call_user_func_array( array( $this, $setup_cb ), array( &$setting ) );
            }

            $this->groups[$group]->sections[$section]->settings[$name] = $setting;
        }

        if (!isset($this->settings[$name])) {
            $this->settings[$name] = $setting;
        }

        return $name;
    }

    public function register_dep( $setting, $dep, $arg = null ) {
        if ( ! isset( $this->deps[ $setting ] ) )
            $this->deps[ $setting ] = array();

        $this->deps[ $setting ][ $dep ] = $arg;
    }

    public function get_dependencies( $args = array() ) {
        $args = wp_parse_args( $args, array(
            'setting' => null,
            'type' => null
        ) );
        extract( $args );

        if ( $setting )
            return isset( $this->deps[ $setting ] ) ? $this->deps[ $setting ] : array();

        if ( $type ) {
            $res = array();

            foreach ( $this->deps as $s => $deps ) {
                foreach ( $deps as $d => $a ) {
                    if ( $type == $d )
                        $res[ $s ] = $a;
                }
            }
        }

        return $this->deps;
    }

    function get_setting( $name ) {
        if ( isset( $this->settings[ $name ] ) )
            return $this->settings[ $name ];

        return false;
    }

    public function get($name, $ifempty=null) {
        $value =  get_option(self::PREFIX . $name, null);

        if (is_null($value)) {
            $default_value = isset($this->settings[$name]) ? $this->settings[$name]->default : null;

            if (is_null($default_value))
                return $ifempty;

            return $default_value;
        }

        if (!is_null($ifempty) && empty($value))
            $value = $ifempty;

        if ( ! isset( $this->settings[ $name ] ) )
            return false;

        if ($this->settings[$name]->type == 'boolean') {
            return (boolean) intval($value);
        } elseif ( 'choice' == $this->settings[$name]->type && isset( $this->settings[$name]->args['multiple'] ) && $this->settings[$name]->args['multiple'] ) {
            if ( ! $value )
                return array();
        }

        return $value;
    }

    public function set($name, $value, $onlyknown=true) {
        $name = strtolower($name);

        if ($onlyknown && !isset($this->settings[$name]))
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
    public function after_render( $html, $setting, $args = array() ) {
        $html = '<a name="' . $setting->name . '"></a>' . $html;
        return $html;
    }

    public function _setting_custom($args) {
        $setting = $args['setting'];
        $value = $this->get( $setting->name );

        $html = '';

        ob_start();
        call_user_func( $setting->callback, $setting, $value );
        $custom_content = ob_get_contents();
        ob_end_clean();

        $html .= $custom_content;

        echo apply_filters( 'wpbdp_settings_render', $html, $setting, $args );
    }

    public function _setting_text($args) {
        $setting = $args['setting'];
        $value = $this->get($setting->name);

        if (isset($args['use_textarea']) || strlen($value) > 100) {
            $html  = '<textarea id="' . $setting->name . '" name="' . self::PREFIX . $setting->name . '" rows="' . ( isset( $args['textarea_rows'] ) ? $args['textarea_rows'] : 4 ) . '">';
            $html .= esc_textarea($value);
            $html .= '</textarea><br />';
        } else {
            $html = '<input type="text" id="' . $setting->name . '" name="' . self::PREFIX . $setting->name . '" value="' . esc_attr( $value ) . '" size="' . (strlen($value) > 0 ? strlen($value) : 20). '" />';
        }

        $html .= '<span class="description">' . $setting->help_text . '</span>';

        echo apply_filters( 'wpbdp_settings_render', $html, $setting, $args );
    }

    public function _setting_license_key($args) {
        $setting = $args['setting'];
        $value = trim( $this->get( $setting->name ) );

        $module_id = str_replace( 'license-key-', '', $setting->name );
        $license_status = get_option( 'wpbdp-license-status-' . $module_id, false );

        $html  = '';
        $html .= '<input type="text"
                         id="' . $setting->name . '"
                         name="' . self::PREFIX . $setting->name . '"
                         value="' . esc_attr( $value ) . '"
                         size="25"
                         ' . ( 'valid' == $license_status ? 'readonly="readonly"' : '' ) . '/>';

        $html .= '<span class="license-activation" data-module-id="' . esc_attr( $module_id ) . '">';
        $html .= wp_nonce_field( 'license activation', 'nonce', false, false );
        $html .= '<input type="button"
                         value="' . _x( 'Deactivate License', 'settings', 'WPBDM' ) . '"
                         class="button-secondary license-deactivate"
                         data-L10n="' . esc_attr( _x( 'Deactivating license...', 'settings', 'WPBDM' ) ) . '"
                         style="' . ( 'valid' == $license_status ? '' : 'display: none;' ) . '" />';
        $html .= '<input type="button"
                         value="' . _x( 'Activate License', 'settings', 'WPBDM' ) . '"
                         class="button-secondary license-activate"
                         data-L10n="' . esc_attr( _x( 'Activating license...', 'settings', 'WPBDM' ) ) . '"
                         style="' . ( 'valid' == $license_status ? 'display: none;' : '' ) . '" />';
        $html .= '<br />';
        $html .= '<span class="status-message"></span>';
        $html .= '</span>';

        echo apply_filters( 'wpbdp_settings_render', $html, $setting, $args );
    }

    public function _setting_text_template( $args ) {
        $setting = $args['setting'];
        $help_text_original = $setting->help_text;

        $placeholders = isset( $args['placeholders'] ) ? $args['placeholders'] : array();

        if ( $placeholders ) {
            $placeholders_text = '';

            foreach ( $placeholders as $pholder => $desc ) {
                $placeholders_text .= sprintf( '%s - %s, ', '[' . $pholder . ']', $desc );
            }
            $placeholders_text = substr( $placeholders_text, 0, -2 ) . '.';

            $setting->help_text = ( $help_text_original ? $help_text_original . '<br  />' : '' ) . sprintf( _x( 'Valid placeholders: %s', 'admin settings', 'WPBDM' ),
                                           $placeholders_text );
        }

        $args['use_textarea'] = true;

        // TODO: this is a proxy for _setting_text (for now).
        ob_start();
        $this->_setting_text( $args );
        $html = ob_get_contents();
        ob_end_clean();

        $setting->help_text = $help_text_original;

        echo $html;
    }

    function _setting_email_template( $args ) {
        $setting = $args['setting'];
        $value = $this->get( $setting->name );

        if ( ! is_array( $value ) ) {
            $body = $value;

            $value = array();
            $value['subject'] = $setting->default['subject'];
            $value['body'] = $body;
        }

        $html  = '';
        $html .= '<span class="description">' . $setting->help_text . '</span>';
        $html .= sprintf( '<div class="wpbdp-settings-email" data-setting="%s">',
                          $setting->name );

        $html .= '<div class="short-preview" title="' . _x( 'Click to edit e-mail', 'settings email', 'WPBDM' ) . '">';
        $html .= '<span class="edit-toggle tag">' . _x( 'Click to edit', 'settings email', 'WPBDM' ) . '</span>';
        $html .= '<h4>';
        $html .= $value['subject'];
        $html .= '</h4>';
        $html .= $value['body'];
        $html .=  '...';
        $html .= '</div>';

        $html .= sprintf( '<div class="editor" style="display: none;" data-preview-nonce="%s">', wp_create_nonce( 'preview email ' . $setting->name ) );
        $html .= '<table class="form-table"><tbody>';
        $html .= '<tr>';
        $html .= sprintf( '<th scope="row"><label for="%s-subject">%s</label</th>',
                          $setting->name,
                          _x( 'E-Mail Subject', 'settings email', 'WPBDM' ) );
        $html .= '<td>';
        $html .= sprintf( '<input type="text" name="%s" value="%s" id="%s" class="subject-text">',
                          self::PREFIX . $setting->name . '[subject]',
                          esc_attr( $value['subject'] ),
                          $setting->name . '-subject' );
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= sprintf( '<th scope="row"><label for="%s-body">%s</label</th>',
                          $setting->name,
                          _x( 'E-Mail Body', 'settings email', 'WPBDM' ) );
        $html .= '<td>';
        $html .= sprintf( '<textarea id="%s" name="%s" class="body-text">%s</textarea>',
                          $setting->name . '-body',
                          self::PREFIX . $setting->name . '[body]',
                          esc_textarea( $value['body'] ) );

        $placeholders = isset( $args['placeholders'] ) ? $args['placeholders'] : array();

        if ( $placeholders ) {
            $html .= '<div class="placeholders">';
            $html .= _x( 'You can use the following placeholders:', 'settings email', 'WPBDM' );
            $html .= '<br /><br />';

            $added_sep = false;

            foreach ( $placeholders as $placeholder => $placeholder_data ) {
                $description = is_array( $placeholder_data ) ? $placeholder_data[0] : $placeholder_data;
                $is_core_placeholder = is_array( $placeholder_data ) && isset( $placeholder_data[2] ) && $placeholder_data[2];

                if ( $is_core_placeholder && ! $added_sep ) {
                    $html .= '<div class="placeholder-separator"></div>';
                    $added_sep = true;
                }

                $html .= sprintf( '<div class="placeholder" data-placeholder="%s"><span class="placeholder-code">[%s]</span> - <span class="placeholder-description">%s</span></div>',
                                  esc_attr( $placeholder ),
                                  $placeholder,
                                  $description );
            }
            $html .= '</div>';
        }

        $html .= '<div class="buttons">';
        $html .= '<a href="#" class="button preview-email">' . _x( 'Preview e-mail', 'settings email', 'WPBDM' ) . '</a> ';
        $html .= '<a href="#" class="button cancel">' . _x( 'Cancel', 'settings email', 'WPBDM' ) . '</a> ';
        $html .= '<a href="#" class="button button-primary save">' . _x( 'Save Changes', 'settings email', 'WPBDM' ) . '</a> ';
        $html .= '</div>';

        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</tbody></table>';
        $html .= '</div>';

        $html .= '</div>';

        echo apply_filters( 'wpbdp_settings_render', $html, $setting, $args );
    }

    function _setting_email_template_setup( &$setting ) {
        if ( ! isset( $setting->args['placeholders'] ) || ! is_array( $setting->args['placeholders'] ) )
            $setting->args['placeholders'] = array();

        // Add default placeholders.
        $setting->args['placeholders'] = array_merge( $setting->args['placeholders'], array(
            'site-title'    => array( _x( 'Site title', 'settings email', 'WPBDM' ),
                                      get_bloginfo( 'name' ),
                                      'core' ),
            'site-link'    => array( _x( 'Site title (with link)', 'settings email', 'WPBDM' ),
                                      sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'name' ) ),
                                      'core' ),
            'site-url'      => array( _x( 'Site address (with link)', 'settings email', 'WPBDM' ),
                                      sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'url' ) ),
                                      'core' ),
            'directory-url' => array( _x( 'Directory URL (with link)', 'settings email', 'WPBDM' ),
                                      sprintf( '<a href="%1$s">%1$s</a>', wpbdp_get_page_link( 'main' ) ),
                                      'core' ),
            'today'         => array( _x( 'Current date', 'settings email', 'WPBDM' ),
                                      date_i18n( get_option( 'date_format' ) ),
                                      'core' ),
            'now'           => array( _x( 'Current time', 'settings email', 'WPBDM' ),
                                      date_i18n( get_option( 'time_format' ) ),
                                      'core' )
        ) );
    }

    function _ajax_email_preview() {
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        $setting = $this->get_setting( isset( $_POST['setting'] ) ? $_POST['setting'] : '' );

        if ( ! $setting || 'email_template' != $setting->type || ! wp_verify_nonce( $nonce, 'preview email ' . $setting->name ) )
            die();

        $placeholders = isset( $setting->args['placeholders'] ) ? $setting->args['placeholders'] : array();

        $subject = stripslashes( isset( $_POST['subject'] ) ? trim( $_POST['subject'] ) : '' );
        $body = stripslashes( isset( $_POST['body'] ) ? trim( $_POST['body'] ) : '' );

        $res = new WPBDP_Ajax_Response();

        foreach ( $placeholders as $pholder => $pdata ) {
            $repl = ( is_array( $pdata ) && count( $pdata ) >= 2 && $pdata[1] ) ? $pdata[1] : '[' . $pholder . ']';

            $subject = str_replace( '[' . $pholder . ']', $repl, $subject );
            $body = str_replace( '[' . $pholder . ']', $repl, $body );
        }

        $html  = '';
        $html .= '<div class="wpbdp-settings-email-preview">';
        $html .= '<h4>' . $subject . '</h4>';
        $html .= nl2br( $body );
        $html .= '</div>';

        $res->add( 'subject', $subject );
        $res->add( 'body', $body );
        $res->add( 'html', $html );
        $res->send();
    }

    public function _setting_boolean($args) {
        $setting = $args['setting'];

        $value = (boolean) $this->get($setting->name);

        $html  = '<label for="' . $setting->name . '">';
        $html .= '<input type="checkbox" id="' .$setting->name . '" name="' . self::PREFIX . $setting->name . '" value="1" '
                  . ($value ? 'checked="checked"' : '') . '/>';
        $html .= '&nbsp;<span class="description">' . $setting->help_text . '</span>';
        $html .= '</label>';

        echo apply_filters( 'wpbdp_settings_render', $html, $setting, $args );
    }

    public function _setting_choice($args) {
        $setting = $args['setting'];
        $choices = is_callable( $args['choices'] ) ? call_user_func( $args['choices'] ) : $args['choices'];

        $value = $this->get($setting->name);

        $multiple = isset( $args['multiple'] ) && $args['multiple'] ? true : false;
        $widget = $multiple ? ( isset( $args['use_checkboxes'] ) && $args['use_checkboxes'] ? 'checkbox' : 'multiselect' ) : 'select'; // TODO: Add support for radios.

        if ( 'multiselect' == $widget )
            $multiple = true;

        $html = '';

        if ( $widget == 'select' || $widget == 'multiselect' ) {
            $html .= '<select id="' . $setting->name . '" name="' . self::PREFIX . $setting->name . ( $multiple ? '[]' : '' ) . '" ' . ( $multiple ? 'multiple="multiple"' : '' ) . '>';

            $value = is_array( $value ) ? $value : array( $value );

            foreach ($choices as $ch) {
                $opt_label = is_array($ch) ? $ch[1] : $ch;
                $opt_value = is_array($ch) ? $ch[0] : $ch;
                $opt_class = ( is_array( $ch ) && isset( $ch[2] ) ) ? $ch[2] : '';

                $html .= '<option value="' . $opt_value . '"' . ( $value && in_array( $opt_value, $value ) ? ' selected="selected"' : '') . ' class="' . $opt_class . '">'
                          . $opt_label . '</option>';
            }

            $html .= '</select>';
        } elseif ( $widget == 'checkbox' ) {
            foreach ( $choices as $k => $v ) {
                $html .= sprintf( '<label><input type="checkbox" name="%s[]" value="%s" %s />%s</label><br />',
                                  self::PREFIX . $setting->name,
                                  $k,
                                  ( $value && in_array( $k, $value ) ) ? 'checked="checked"' : '',
                                  $v );
            }
        }

        $html .= '<span class="description">' . $setting->help_text . '</span>';

        echo apply_filters( 'wpbdp_settings_render', $html, $setting, $args );
    }

    public function register_in_admin() {
        foreach ($this->groups as $group) {
            foreach ($group->sections as $section) {
                $callback = create_function( '', 'WPBDP_Settings::_section_cb("' . $group->slug . '", "' . $section->slug . '");' );
                add_settings_section($section->slug, $section->name, $callback, $group->wpslug);

                foreach ($section->settings as $setting) {
                    register_setting($group->wpslug, self::PREFIX . $setting->name/*, array( &$this, 'filter_x' ) */);
                    add_settings_field(self::PREFIX . $setting->name, $setting->label,
                                       array($this, '_setting_' . $setting->type),
                                       $group->wpslug,
                                       $section->slug,
                                       array_merge($setting->args, array('label_for' => $setting->name, 'setting' => $setting))
                                       );

                    if ( $setting->validator || ( $setting->type == 'choice' && isset( $setting->args['multiple'] ) && $setting->args['multiple'] ) ) {
                        add_filter('pre_update_option_' . self::PREFIX . $setting->name, create_function('$n, $o=null', 'return WPBDP_Settings::_validate_setting("' . $setting->name . '", $n, $o);'), 10, 2);
                    }
                }
            }
        }
    }

    public static function _section_cb( $g, $s ) {
        $api = wpbdp_settings_api();

        $group = $api->groups[ $g ];
        $section = $group->sections[ $s ];

        echo '<a name="' . $section->slug . '"></a>';

        if ( $section->help_text ) {
            echo '<p class="description">';
            echo stripslashes( $section->help_text );
            echo '</p>';
        }
    }

    public static function _validate_setting($name, $newvalue=null, $oldvalue=null) {
        $api = wpbdp_settings_api();
        $setting = $api->settings[$name];

        if ( $setting->type == 'choice' && isset( $setting->args['multiple'] ) && $setting->args['multiple'] ) {
            if ( isset( $_POST[ self::PREFIX . $name ] ) ) {
                $newvalue = $_POST[ self::PREFIX . $name ];
                $newvalue = is_array( $newvalue ) ? $newvalue : array( $newvalue );

                if ( $setting->validator )
                    $newvalue = call_user_func( $setting->validator, $setting, $newvalue, $api->get( $setting->name ) );
            }

            return $newvalue;
        }

        return call_user_func($setting->validator, $setting, $newvalue, $api->get($setting->name));
    }

    /* upgrade from old-style settings to new options */
    public function pre_2_0_options() {
        static $option_translations = array(
            'wpbusdirman_settings_config_18' => 'listing-duration',
            /* 'wpbusdirman_settings_config_25' => 'hide-buy-module-buttons',*/  /* removed in 2.0 */
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

                if ($type == 'titles' || $id == 'wpbusdirman_settings_config_25' || empty($value))
                    continue;

                if ($id == 'wpbusdirman_settings_config_40') {
                    $this->set('googlecheckout', $value == 'yes' ? false : true);
                } elseif ($id == 'wpbusdirman_settings_config_41') {
                    $this->set('paypal', $value == 'yes' ? false : true);
                } elseif ($id == 'wpbusdirman_settings_config_43') {
                    $this->set('2checkout', $value == 'yes' ? false : true);
                } else {
                    if (!isset($this->settings[$translations[$id]]))
                        continue;

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

