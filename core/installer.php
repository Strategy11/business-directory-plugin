<?php

class WPBDP_Installer {

    const DB_VERSION = '13';

    private $installed_version = null;


    public function __construct() {
        $this->installed_version = get_option( 'wpbdp-db-version', get_option( 'wpbusdirman_db_version', null ) );

        add_action( 'split_shared_term', array( &$this, 'handle_term_split' ), 10, 4 );
    }

    public function install() {
        // schedule expiration hook if needed
        if (!wp_next_scheduled('wpbdp_listings_expiration_check')) {
            wpbdp_log('Expiration check was not in schedule. Scheduling.');
            wp_schedule_event(current_time('timestamp'), 'hourly', 'wpbdp_listings_expiration_check');
        } else {
            wpbdp_log('Expiration check was in schedule. Nothing to do.');
        }

        if ( false === get_option( 'wpbdp-db-migrations', false ) )
            update_option( 'wpbdp-db-migrations', array(), false );

        if ( self::DB_VERSION == $this->installed_version )
            return;

        $this->update_database_schema();

        if ( $this->installed_version ) {
            wpbdp_log('WPBDP is already installed.');
            $this->_update();
        } else {
            wpbdp_log('New installation. Creating default form fields.');
            global $wpbdp;

            // Create default category.
            wp_insert_term( _x( 'General', 'default category name', 'WPBDM' ), WPBDP_CATEGORY_TAX );

            $wpbdp->formfields->create_default_fields();

            add_option( 'wpbdp-show-drip-pointer', 1 );
            add_option( 'wpbdp-show-tracking-pointer', 1 );

            // Create default paid fee.
            $fee = new WPBDP_Fee_Plan( array( 'label' => _x( 'Default Fee', 'installer', 'WPBDM' ),
                                              'amount' => 1.0,
                                              'days' => 365,
                                              'images' => 1,
                                              'categories' => array( 'all' => true, 'categories' => array() ),
                                              'enabled' => 1 ) );
            $fee->save();
        }

        delete_option('wpbusdirman_db_version');
        update_option('wpbdp-db-version', self::DB_VERSION);
    }

    /**
     * Builds the SQL queries (without running them) used to create all of the required database tables for BD.
     * Calls the `wpbdp_database_schema` filter that allows plugins to modify the schema.
     * @return array An associative array of (non prefixed)table => SQL items.
     * @since 3.3
     */
    public function get_database_schema() {
        global $wpdb;

        $schema = array();

        $schema['form_fields'] = "CREATE TABLE {$wpdb->prefix}wpbdp_form_fields (
            id bigint(20) PRIMARY KEY  AUTO_INCREMENT,
            label varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            description varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            field_type varchar(100) NOT NULL,
            association varchar(100) NOT NULL,
            validators text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            weight int(5) NOT NULL DEFAULT 0,
            display_flags text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            field_data blob NULL,
            shortname varchar(255) NOT NULL DEFAULT '',
            tag varchar(255) NOT NULL DEFAULT '',
            KEY field_type (field_type)
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

        $schema['fees'] = "CREATE TABLE {$wpdb->prefix}wpbdp_fees (
            id bigint(20) PRIMARY KEY  AUTO_INCREMENT,
            label varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            days smallint unsigned NOT NULL DEFAULT 0,
            images smallint unsigned NOT NULL DEFAULT 0,
            categories blob NOT NULL,
            extra_data blob NULL,
            weight int(5) NOT NULL DEFAULT 0,
            sticky tinyint(1) NOT NULL DEFAULT 0,
            enabled tinyint(1) NOT NULL DEFAULT 1,
            tag varchar(255) NOT NULL DEFAULT ''
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

        $schema['payments'] = "CREATE TABLE {$wpdb->prefix}wpbdp_payments (
            id bigint(20) PRIMARY KEY  AUTO_INCREMENT,
            listing_id bigint(20) NOT NULL DEFAULT 0,
            gateway varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            currency_code varchar(3) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'USD',
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            status varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            created_on timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_on timestamp NULL DEFAULT NULL,
            processed_by varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            payerinfo blob NULL,
            extra_data longblob NULL,
            notes longblob NULL,
            tag varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            KEY listing_id (listing_id),
            KEY status (status)
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

        $schema['payments_items'] = "CREATE TABLE {$wpdb->prefix}wpbdp_payments_items (
            id bigint(20) PRIMARY KEY  AUTO_INCREMENT,
            payment_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            item_type varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'charge',
            description varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'Charge',
            rel_id_1 bigint(20) NULL,
            rel_id_2 bigint(20) NULL,
            data longblob NULL
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

        $schema['listing_fees'] = "CREATE TABLE {$wpdb->prefix}wpbdp_listing_fees (
            id bigint(20) PRIMARY KEY  AUTO_INCREMENT,
            listing_id bigint(20) NOT NULL,
            category_id bigint(20) NOT NULL,
            fee_id bigint(20) NULL,
            fee_days smallint unsigned NOT NULL,
            fee_images smallint unsigned NOT NULL DEFAULT 0,
            expires_on timestamp NULL DEFAULT NULL,
            email_sent tinyint(1) NOT NULL DEFAULT 0,
            recurring tinyint(1) NOT NULL DEFAULT 0,
            recurring_id varchar(255) NULL,
            recurring_data blob NULL,
            sticky tinyint(1) NOT NULL DEFAULT 0,
            KEY listing_cat (listing_id,category_id),
            KEY expires_and_email (expires_on,email_sent)
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

        $schema['submit_state'] = "CREATE TABLE {$wpdb->prefix}wpbdp_submit_state (
            id varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci PRIMARY KEY,
            state longblob NOT NULL,
            updated_on datetime NOT NULL
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

        return apply_filters( 'wpbdp_database_schema', $schema );
    }

    public function update_database_schema() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        wpbdp_log( 'Running dbDelta.' ); 

        $schema = $this->get_database_schema();

        foreach ( $schema as $table_sql )
            dbDelta( $table_sql );
    }

    public function _update() {
        global $wpbdp;

        if ( get_option( 'wpbdp-manual-upgrade-pending', false ) )
            return;

        $upgrade_routines = array( '2.0', '2.1', '2.2', '2.3', '2.4', '2.5', '3.1', '3.2', '3.4', '3.5', '3.6', '3.7', '3.9', '4.0', '5', '6', '7', '8', '11', '12', '13' );

        foreach ( $upgrade_routines as $v ) {
            if ( version_compare( $this->installed_version, $v ) < 0 ) {
                wpbdp_log( sprintf( 'Running upgrade routine for version %s', $v ) );
                $_v = str_replace( '.', '_', $v );
                call_user_func( array( $this, 'upgrade_to_' . $_v ) );
                update_option('wpbdp-db-version', $v);

                if ( get_option( 'wpbdp-manual-upgrade-pending', false ) )
                    break;
            }
        }

        $wpbdp->formfields->maybe_correct_tags();
    }

    public function request_manual_upgrade( $callback ) {
        update_option( 'wpbdp-manual-upgrade-pending', $callback );
    }

    public function setup_manual_upgrade() {
        $manual_upgrade = get_option( 'wpbdp-manual-upgrade-pending', false );

        if ( ! $manual_upgrade )
            return;

        new WPBDP_Installer_Manual_Upgrade( $this, $manual_upgrade );
    }


    /*
     * Upgrade routines.
     */

    public function upgrade_to_2_0() {
        global $wpdb;
        global $wpbdp;

        $wpbdp->settings->upgrade_options();
        wpbdp_log('WPBDP settings updated to 2.0-style');

        // make directory-related metadata hidden
        $old_meta_keys = array(
            'termlength', 'image', 'listingfeeid', 'sticky', 'thumbnail', 'paymentstatus', 'buyerfirstname', 'buyerlastname',
            'paymentflag', 'payeremail', 'paymentgateway', 'totalallowedimages', 'costoflisting'
        );

        foreach ($old_meta_keys as $meta_key) {
            $query = $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s AND {$wpdb->postmeta}.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                                    '_wpbdp_' . $meta_key, $meta_key, 'wpbdm-directory');
            $wpdb->query($query);
        }

        wpbdp_log('Made WPBDP directory metadata hidden attributes');
    }

    public function upgrade_to_2_1() {
        global $wpdb;

        /* This is only to make this routine work for BD 3.0. It's not necessary in other versions. */
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields ADD COLUMN validator VARCHAR(255) NULL;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields ADD COLUMN display_options BLOB NULL;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields ADD COLUMN is_required TINYINT(1) NOT NULL DEFAULT 0;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields ADD COLUMN type VARCHAR(255) NOT NULL;" );

        static $pre_2_1_types = array(null, 'textfield', 'select', 'textarea', 'radio', 'multiselect', 'checkbox');
        static $pre_2_1_validators = array(
            'email' => 'EmailValidator',
            'url' => 'URLValidator',
            'missing' => null, /* not really used */
            'numericwhole' => 'IntegerNumberValidator',
            'numericdeci' => 'DecimalNumberValidator',
            'date' => 'DateValidator'
        );
        static $pre_2_1_associations = array(
            'title' => 'title',
            'description' => 'content',
            'category' => 'category',
            'excerpt' => 'excerpt',
            'meta' => 'meta',
            'tags' => 'tags'
        );

        $field_count = $wpdb->get_var(
            sprintf("SELECT COUNT(*) FROM {$wpdb->prefix}options WHERE option_name LIKE '%%%s%%'", 'wpbusdirman_postform_field_label'));

        for ($i = 1; $i <= $field_count; $i++) {
            $label = get_option('wpbusdirman_postform_field_label_' . $i);
            $type = get_option('wpbusdirman_postform_field_type_'. $i);
            $validation = get_option('wpbusdirman_postform_field_validation_'. $i);
            $association = get_option('wpbusdirman_postform_field_association_'. $i);
            $required = strtolower(get_option('wpbusdirman_postform_field_required_'. $i));
            $show_in_excerpt = strtolower(get_option('wpbusdirman_postform_field_showinexcerpt_'. $i));
            $hide_field = strtolower(get_option('wpbusdirman_postform_field_hide_'. $i));
            $options = get_option('wpbusdirman_postform_field_options_'. $i);

            $newfield = array();
            $newfield['label'] = $label;
            $newfield['type'] = wpbdp_getv($pre_2_1_types, intval($type), 'textfield');
            $newfield['validator'] = wpbdp_getv($pre_2_1_validators, $validation, null);
            $newfield['association'] = wpbdp_getv($pre_2_1_associations, $association, 'meta');
            $newfield['is_required'] = $required == 'yes' ? true : false;
            $newfield['display_options'] = serialize(
                array('show_in_excerpt' => $show_in_excerpt == 'yes' ? true : false,
                      'hide_field' => $hide_field == 'yes' ? true : false)
            );
            $newfield['field_data'] = $options ? serialize(array('options' => explode(',', $options))) : null;

            if ($wpdb->insert($wpdb->prefix . 'wpbdp_form_fields', $newfield)) {
                delete_option('wpbusdirman_postform_field_label_' . $i);
                delete_option('wpbusdirman_postform_field_type_' . $i);
                delete_option('wpbusdirman_postform_field_validation_' . $i);
                delete_option('wpbusdirman_postform_field_association_' . $i);
                delete_option('wpbusdirman_postform_field_required_' . $i);
                delete_option('wpbusdirman_postform_field_showinexcerpt_' . $i);
                delete_option('wpbusdirman_postform_field_hide_' . $i);
                delete_option('wpbusdirman_postform_field_options_' . $i);
                delete_option('wpbusdirman_postform_field_order_' . $i);
            }

        }
    }

    public function upgrade_to_2_2() {
        global $wpdb;
        $wpdb->query("ALTER TABLE {$wpdb->prefix}wpbdp_form_fields CHARACTER SET utf8 COLLATE utf8_general_ci");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}wpbdp_form_fields CHANGE `label` `label` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}wpbdp_form_fields CHANGE `description` `description` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
    }

    public function upgrade_to_2_3() {
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
                                         $new_id, '_wpbdp_listingfeeid', $i, 'wpbdm-directory');
                $wpdb->query($query);

                foreach (array('label', 'amount', 'increment', 'images', 'categories') as $k) {
                    delete_option('wpbusdirman_settings_fees_' . $k . '_' . $i);
                    delete_option('_settings_fees_' . $k . '_' . $i);
                }
            }

        }
    }

    public function upgrade_to_2_4() {
        global $wpdb;
        global $wpbdp;

        $fields = $wpbdp->formfields->get_fields();

        foreach ($fields as &$field) {
            $query = $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s AND {$wpdb->postmeta}.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                                    '_wpbdp[fields][' . $field->get_id() . ']', $field->get_label(), 'wpbdm-directory');
            $wpdb->query($query);
        }
    }

    public function upgrade_to_2_5() {
        global $wpdb;

        wpbdp_log('Updating payment/sticky status values.');
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s", '_wpbdp[sticky]', '_wpbdp_sticky'));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s", 'sticky', '_wpbdp[sticky]', 'approved'));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value != %s", 'pending', '_wpbdp[sticky]', 'approved'));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s", '_wpbdp[payment_status]', '_wpbdp_paymentstatus'));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value != %s", 'not-paid', '_wpbdp[payment_status]', 'paid'));

        // Misc updates
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_totalallowedimages'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_termlength'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_costoflisting'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_listingfeeid'));

        wpbdp_log('Updating listing images to new framework.');

        $old_images = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_image'));
        foreach ($old_images as $old_image) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $filename = ABSPATH . 'wp-content/uploads/wpbdm/' . $old_image->meta_value;

            $wp_filetype = wp_check_filetype(basename($filename), null);
            
            $attachment_id = wp_insert_attachment(array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_content' => '',
                'post_status' => 'inherit'
            ), $filename, $old_image->post_id);
            $attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
            wp_update_attachment_metadata( $attachment_id, $attach_data );
        }
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_image'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_thumbnail'));        
    }

    public function upgrade_to_3_1() {
        global $wpdb;

        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s", WPBDP_POST_TYPE, 'wpbdm-directory'));

        if (function_exists('flush_rewrite_rules'))
            flush_rewrite_rules(false);
    }

    /*
     * This update converts all form fields to a new, more flexible format that uses a new API introduced in BD 2.3.
     */
    public function upgrade_to_3_2() {
        global $wpdb;

        $validators_trans = array(
            'EmailValidator' => 'email',
            'URLValidator' => 'url',
            'IntegerNumberValidator' => 'integer_number',
            'DecimalNumberValidator' => 'decimal_number',
            'DateValidator' => 'date_'
        );

        $old_fields = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpbdp_form_fields" );

        foreach ( $old_fields as &$f ) {
            $newfield = array();
            $newfield['field_type'] = strtolower( $f->type );

            if ( empty( $newfield['field_type'] ) )
                $newfield['field_type'] = 'textfield';

            $newfield['display_flags'] = array();
            $newfield['field_data'] = array();
            $newfield['validators'] = array();

            // display options
            $f_display_options = array_merge(array('show_in_excerpt' => true, 'show_in_listing' => true, 'show_in_search' => true), $f->display_options ? (array) unserialize($f->display_options) : array());
            if ( isset( $f_display_options['hide_field'] ) && $f_display_options['hide_field'] ) {
                // do nothing
            } else {
                if ( $f_display_options['show_in_excerpt'] ) $newfield['display_flags'][] = 'excerpt';
                if ( $f_display_options['show_in_listing'] ) $newfield['display_flags'][] = 'listing';
                if ( $f_display_options['show_in_search'] ) $newfield['display_flags'][] = 'search';
            }

            // validators
            if ( $f->validator && isset( $validators_trans[ $f->validator ] ) ) $newfield['validators'] = array( $validators_trans[ $f->validator ] );
            if ( $f->is_required ) $newfield['validators'][] = 'required';

            // options for multivalued fields
            $f_data = $f->field_data ? unserialize( $f->field_data ) : null;
            $f_data = is_array( $f_data ) ? $f_data : array();

            if ( isset( $f_data['options'] ) && is_array( $f_data['options'] ) ) $newfield['field_data']['options'] = $f_data['options'];
            if ( isset( $f_data['open_in_new_window'] ) && $f_data['open_in_new_window'] ) $newfield['field_data']['open_in_new_window'] = true;

            if ( $newfield['field_type'] == 'textfield' && in_array( 'url', $newfield['validators']) )
                $newfield['field_type'] = 'url';

            $newfield['display_flags'] = implode( ',', $newfield['display_flags'] );
            $newfield['validators'] = implode( ',', $newfield['validators'] );
            $newfield['field_data'] = serialize( $newfield['field_data'] );

            $wpdb->update( "{$wpdb->prefix}wpbdp_form_fields", $newfield, array( 'id' => $f->id ) );
        }

        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields DROP COLUMN validator;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields DROP COLUMN display_options;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields DROP COLUMN is_required;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields DROP COLUMN type;" );

        add_action( 'admin_notices', array( $this, 'disable_regions_in_3_2_upgrade' )  );
    }

    public function disable_regions_in_3_2_upgrade() {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        if ( class_exists( 'WPBDP_RegionsPlugin' ) && version_compare( WPBDP_RegionsPlugin::VERSION, '1.1', '<' ) ) {
            deactivate_plugins( 'business-directory-regions/business-directory-regions.php', true );
            echo sprintf( '<div class="error"><p>%s</p></div>',
                          _x( '<b>Business Directory Plugin - Regions Module</b> was disabled because it is incompatible with the current version of Business Directory. Please update the Regions module.', 'installer', 'WPBDM' )
                        );
        }        
    }

    public function upgrade_to_3_4() {
        global $wpdb;
        
        $query = $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listing_fees SET email_sent = %d WHERE email_sent = %d", 2, 1 );
        $wpdb->query( $query );
    }

    public function upgrade_to_3_5() {
        global $wpdb;
        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE taxonomy = %s", WPBDP_CATEGORY_TAX, 'wpbdm-category' ) );
        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE taxonomy = %s", WPBDP_TAGS_TAX, 'wpbdm-tags' ) );
    }

    public function upgrade_to_3_6() {
        global $wpdb;

        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields MODIFY id bigint(20) AUTO_INCREMENT" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_fees MODIFY id bigint(20) AUTO_INCREMENT" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_payments MODIFY id bigint(20) AUTO_INCREMENT" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_listing_fees MODIFY id bigint(20) AUTO_INCREMENT" );

        update_option(WPBDP_Settings::PREFIX . "listings-per-page", get_option("posts_per_page"));
    }

    public function upgrade_to_3_7() {
        global $wpdb;

        // Try to disable incompatible modules.
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        if ( is_plugin_active( 'business-directory-regions/business-directory-regions.php' ) ) {
            deactivate_plugins( 'business-directory-regions/business-directory-regions.php' );
        }

        // Remove invalid listing fees (quick).
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id NOT IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)", WPBDP_POST_TYPE ) );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE category_id NOT IN (SELECT term_id FROM {$wpdb->terms})" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_listing_fees DROP charged" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_listing_fees DROP updated_on" );

        // Update notify-admin email option.
        if ( get_option( WPBDP_Settings::PREFIX . 'notify-admin', false ) )
            update_option( WPBDP_Settings::PREFIX . 'admin-notifications', array( 'new-listing') );

        $this->request_manual_upgrade( 'upgrade_to_3_7_migrate_payments' );
    }

    public function upgrade_to_3_7_migrate_payments() {
        global $wpdb;

        $status_msg = '';

        // Remove/update listing fees.
        if ( ! $wpdb->get_col( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->prefix}wpbdp_listing_fees LIKE %s", 'migrated' ) ) )
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_listing_fees ADD migrated tinyint(1) DEFAULT 0" );

        $n_fees = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_listing_fees" ) );
        $n_fees_migrated = intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_listing_fees WHERE migrated = %d", 1 ) ) );
        $fees_done = ( $n_fees_migrated == $n_fees ) ? true : false;

        if ( ! $fees_done ) {
            $status_msg = sprintf( _x( 'Cleaning up listing fees information... %d/%d', 'installer', 'WPBDM' ), $n_fees_migrated, $n_fees );

            $fees = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE migrated = %d ORDER BY id ASC LIMIT 50", 0 ), ARRAY_A );

            foreach ( $fees as &$f ) {
                // Delete fee if category does not exist.
                if ( ! term_exists( intval( $f['category_id'] ), WPBDP_CATEGORY_TAX ) ) {
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d", $f['id'] ) );
                } else {
                    // Delete duplicated listing fees.
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id < %d AND category_id = %d AND listing_id = %d",
                                                  $f['id'],
                                                  $f['category_id'],
                                                  $f['listing_id'] ) );

                    $f['fee'] = (array) unserialize( $f['fee'] );
                    $f['fee_days'] = abs( intval( $f['fee']['days'] ) );
                    $f['fee_images'] = abs( intval( $f['fee']['images'] ) );
                    $f['fee_id'] = intval( $f['fee']['id'] );
                    $f['fee'] = '';
                    $f['migrated'] = 1;

                    unset( $f['fee'] );

                    if ( ! $f['expires_on'] )
                        unset( $f['expires_on'] );

                    $wpdb->update( $wpdb->prefix . 'wpbdp_listing_fees', $f, array( 'id' => $f['id'] ) );
                }
            }
        }

        // Migrate transactions.
        $transactions_done = false;

        if ( $fees_done ) {
            if ( ! $wpdb->get_col( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->prefix}wpbdp_payments LIKE %s", 'migrated' ) ) )
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_payments ADD migrated tinyint(1) DEFAULT 0" );

            $n_transactions = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_payments" ) );
            $n_transactions_migrated = intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_payments WHERE migrated = %d", 1 ) ) );
            $transactions_done = ( $n_transactions_migrated == $n_transactions ) ? true : false;

            if ( $transactions_done ) {
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_payments DROP payment_type" );
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_payments DROP migrated" );
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_listing_fees DROP fee" );                
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_listing_fees DROP migrated" );
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp[payment_status]' ) );
            } else {
                $status_msg = sprintf( _x( 'Migrating previous transactions to new Payments API... %d/%d', 'installer', 'WPBDM' ), $n_transactions_migrated, $n_transactions );

                $transactions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_payments WHERE migrated = %d ORDER BY id ASC LIMIT 50", 0 ), ARRAY_A );
                
                foreach ( $transactions as &$t ) {
                    $t['status'] = 'approved' == $t['status'] ? 'completed' : ( 'pending' == $t['status'] ? 'pending' : 'rejected' );
                    $t['currency_code'] = get_option( 'wpbdp-currency' );
                    $t['migrated'] = 1;

                    if ( ! isset( $t['processed_on'] ) || empty( $t['processed_on'] ) )
                        unset( $t['processed_on'] );

                    if ( ! isset( $t['created_on'] ) || empty( $t['created_on'] ) )
                        unset( $t['created_on'] );

                    if ( ! isset( $t['listing_id'] ) || empty( $t['listing_id'] ) )
                        $t['listing_id'] = 0;

                    if ( ! isset( $t['amount'] ) || empty( $t['amount'] ) )
                        $t['amount'] = '0.0';

                    // TODO: delete duplicated pending transactions (i.e. two renewals for the same category & listing ID that are 'pending').

                    switch ( $t['payment_type'] ) {
                        case 'initial':
                            $wpdb->insert( $wpdb->prefix . 'wpbdp_payments_items',
                                           array( 'payment_id' => $t['id'],
                                                  'amount' => $t['amount'],
                                                  'item_type' => 'charge',
                                                  'description' => _x( 'Initial listing payment (BD < 3.4)', 'installer', 'WPBDM' )
                                                ) );
                            $wpdb->update( $wpdb->prefix . 'wpbdp_payments', $t, array( 'id' => $t['id'] ) );

                            break;

                        case 'edit':
                            $wpdb->insert( $wpdb->prefix . 'wpbdp_payments_items',
                                           array( 'payment_id' => $t['id'],
                                                  'amount' => $t['amount'],
                                                  'item_type' => 'charge',
                                                  'description' => _x( 'Listing edit payment (BD < 3.4)', 'installer', 'WPBDM' )
                                                ) );
                            $wpdb->update( $wpdb->prefix . 'wpbdp_payments', $t, array( 'id' => $t['id'] ) );

                            break;

                        case 'renewal':
                            $data = unserialize( $t['extra_data'] );
                            $fee_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d", $data['renewal_id'] ) );

                            if ( ! $fee_info || ! term_exists( intval( $fee_info->category_id ), WPBDP_CATEGORY_TAX ) ) {
                                $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE id = %d", $t['id'] ) );
                                continue;
                            }

                            $fee_info->fee = unserialize( $fee_info->fee );

                            $item = array();
                            $item['payment_id'] = $t['id'];
                            $item['amount'] = $t['amount'];
                            $item['item_type'] = 'fee';
                            $item['description'] = sprintf( _x( 'Renewal fee "%s" for category "%s"', 'installer', 'WPBDM' ),
                                                            $fee_info->fee['label'],
                                                            wpbdp_get_term_name( $fee_info->category_id ) );
                            $item['data'] = serialize( array( 'fee' => $fee_info->fee ) );
                            $item['rel_id_1'] = $fee_info->category_id;
                            $item['rel_id_2'] = $fee_info->fee['id'];
     
                            $wpdb->insert( $wpdb->prefix . 'wpbdp_payments_items', $item );
                            $wpdb->update( $wpdb->prefix . 'wpbdp_payments', $t, array( 'id' => $t['id'] ) );

                            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d", $data['renewal_id'] ) );

                            break;

                        case 'upgrade-to-sticky':
                            $wpdb->insert( $wpdb->prefix . 'wpbdp_payments_items',
                                           array( 'payment_id' => $t['id'],
                                                  'amount' => $t['amount'],
                                                  'item_type' => 'upgrade',
                                                  'description' => _x( 'Listing upgrade to featured', 'installer', 'WPBDM' )
                                                ) );
                            $wpdb->update( $wpdb->prefix . 'wpbdp_payments', $t, array( 'id' => $t['id'] ) );

                            break;

                        default:
                            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE id = %d", $t['id'] ) );
                            break;
                    }

                }
            }
        }

        $res = array( 'ok' => true,
                      'done' => $transactions_done,
                      'status' => $status_msg );

        return $res;
    }

    public function upgrade_to_3_9() {
        // TODO: make sure this works when passing through manual 3.7 upgrade.
        global $wpdb;

        if ( $wpdb->get_col( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->prefix}wpbdp_submit_state LIKE %s", 'created' ) ) )
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_submit_state DROP COLUMN created" );

        if ( $wpdb->get_col( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->prefix}wpbdp_submit_state LIKE %s", 'updated' ) ) ) {
            $wpdb->query( "UPDATE {$wpdb->prefix}wpbdp_submit_state SET updated_on = updated" );
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_submit_state DROP COLUMN updated" );
        }
    }

    public function upgrade_to_4_0() {
        $o = (bool) get_option( WPBDP_Settings::PREFIX . 'send-email-confirmation', false );

        if ( ! $o ) {
            update_option( WPBDP_Settings::PREFIX . 'user-notifications', array( 'listing-published' ) );
        }
        delete_option( WPBDP_Settings::PREFIX . 'send-email-confirmation' );
    }

    /**
     * This upgrade routine takes care of the term splitting feature that is going to be introduced in WP 4.2.
     * @since 3.6.4
     */
    public function upgrade_to_5() {
        global $wp_version;

        if ( ! function_exists( 'wp_get_split_term' ) )
            return;

        $terms = $this->gather_pre_split_term_ids();
        foreach ( $terms as $term_id )
            $this->process_term_split( $term_id );
    }

    /**
     * @since 3.6.4
     */
    private function gather_pre_split_term_ids() {
        global $wpdb;

        $res = array();

        // Fees.
        $fees = $wpdb->get_col( "SELECT categories FROM {$wpdb->prefix}wpbdp_fees" );
        foreach ( $fees as $f ) {
            $data = unserialize( $f );

            if ( isset( $data['all'] ) && $data['all'] )
                continue;

            if ( ! empty( $data['categories'] ) )
                $res = array_merge( $res, $data['categories'] );

        }

        // Listing fees.
        if ( $fee_ids = $wpdb->get_col( "SELECT DISTINCT category_id FROM {$wpdb->prefix}wpbdp_listing_fees" ) ) {
            $res = array_merge( $res, $fee_ids );
        }

        // Payments.
        $payments_terms = $wpdb->get_col(
                $wpdb->prepare( "SELECT DISTINCT rel_id_1 FROM {$wpdb->prefix}wpbdp_payments_items WHERE ( item_type = %s OR item_type = %s )",
                                'fee',
                                'recurring_fee' )
        );
        $res = array_merge( $res, $payments_terms );

        // Category images.
        $imgs = get_option( 'wpbdp[category_images]', false );
        if ( $imgs && is_array( $imgs ) ) {
            if ( !empty ( $imgs['images'] ) )
                $res = array_merge( $res, array_keys( $imgs['images'] ) );

            if ( ! empty( $imgs['temp'] ) )
                $res = array_merge( $res, array_keys( $imgs['temp'] ) );
        }

        return array_map( 'intval', array_unique( $res ) );
    }

    /**
     * Use this function to update BD references of a pre-split term ID to use the new term ID.
     * @since 3.6.4
     */
    public function process_term_split( $old_id = 0 ) {
        global $wpdb;

        if ( ! $old_id )
            return;

        $new_id = wp_get_split_term( $old_id, WPBDP_CATEGORY_TAX );
        if ( ! $new_id )
            return;

        // Fees.
        $fees = $wpdb->get_results( "SELECT id, categories FROM {$wpdb->prefix}wpbdp_fees" );
        foreach ( $fees as &$f ) {
            $categories = unserialize( $f->categories );

            if ( ( isset( $categories['all'] ) && $categories['all'] ) || empty( $categories['categories'] ) )
                continue;

            $index = array_search( $old_id, $categories['categories'] );

            if ( $index === false )
                continue;

            $categories['categories'][ $index ] = $new_id;
            $wpdb->update( $wpdb->prefix . 'wpbdp_fees',
                           array( 'categories' => serialize( $categories ) ),
                           array( 'id' => $f->id ) );
        }

        // Listing fees.
        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listing_fees SET category_id = %d WHERE category_id = %d",
                                      $new_id,
                                      $old_id ) );

        // Payments.
        $wpdb->query(
            $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_payments_items SET rel_id_1 = %d WHERE ( rel_id_1 = %d AND ( item_type = %s OR item_type = %s ) )",
                            $new_id,
                            $old_id,
                            'fee',
                            'recurring_fee' )
        );

        // Category images.
        $imgs = get_option( 'wpbdp[category_images]', false );
        if ( empty( $imgs ) || ! is_array( $imgs ) )
            return;

        if ( ! empty( $imgs['images'] ) && isset( $imgs['images'][ $old_id ] ) ) {
            $imgs['images'][ $new_id ] = $imgs['images'][ $old_id ];
            unset( $imgs['images'][ $old_id ] );
        }

        if ( ! empty( $imgs['temp'] ) && isset( $imgs['temp'][ $old_id ] ) ) {
            $imgs['temp'][ $new_id ] = $imgs['temp'][ $old_id ];
            unset( $imgs['temp'][ $old_id ] );
        }

        update_option( 'wpbdp[category_images]', $imgs );
    }

    public function handle_term_split( $old_id, $new_id, $tt_id, $tax ) {
        if ( WPBDP_CATEGORY_TAX != $tax )
            return;

        $this->process_term_split( $old_id );
    }

    public function upgrade_to_6() {
        global $wpdb;

        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_payments MODIFY created_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" );
        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_payments SET processed_on = NULL WHERE processed_on = %s", '0000-00-00 00:00:00' ) );
    }

    public function upgrade_to_7() {
        global $wpdb;

        $fields = $wpdb->get_results( $wpdb->prepare( "SELECT id, field_type FROM {$wpdb->prefix}wpbdp_form_fields WHERE field_type IN (%s, %s, %s, %s) AND association = %s",
                                                      'select', 'multiselect', 'checkbox', 'radio', 'meta' ) );

        foreach ( $fields as $f ) {
            $listing_values = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
                                                                  '_wpbdp[fields][' . $f->id . ']' ) );

            foreach ( $listing_values as $lv ) {
                $v = maybe_unserialize( $lv->meta_value );

                if ( in_array( $f->field_type, array( 'select', 'radio' ), true ) ) {
                    if ( is_array( $v ) )
                        $v = array_pop( $v );
                } else {
                    if ( is_array( $v ) )
                        $v = implode( "\t", $v );
                }

                $wpdb->update( $wpdb->postmeta, array( 'meta_value' => $v ), array( 'meta_id' => $lv->meta_id ) );
            }
        }
    }

    public function upgrade_to_8() {
        if ( get_option( WPBDP_Settings::PREFIX . 'show-search-form-in-results', false ) )
            update_option( WPBDP_Settings::PREFIX . 'search-form-in-results', 'above' );
        delete_option( WPBDP_Settings::PREFIX . 'show-search-form-in-results' );
    }

    public function upgrade_to_11() {
        // Users upgrading from < 4.x get the pre-4.0 theme.
        update_option( 'wpbdp-active-theme', 'no_theme' );
    }

    public function upgrade_to_12() {
        delete_transient( 'wpbdp-themes-updates' );
    }

    public function upgrade_to_13() {
        // Make sure no field shortnames conflict.
         $fields = wpbdp_get_form_fields();

         foreach ( $fields as $f )
             $f->save();
    }

}


class WPBDP_Installer_Manual_Upgrade {

    private $installer;
    private $callback;

    public function __construct( &$installer, $callback ) {
        add_action( 'admin_notices', array( &$this, 'upgrade_required_notice' ) );
        add_action( 'admin_menu', array( &$this, 'add_upgrade_page' ) );
        add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_wpbdp-manual-upgrade', array( &$this, 'handle_ajax' ) );

        $this->installer = $installer;
        $this->callback = $callback;
    }

    public function upgrade_required_notice() {
        global $pagenow;

        if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'wpbdp-upgrade-page' == $_GET['page'] )
            return;

        print '<div class="error"><p>';
        print '<strong>' . __( 'Business Directory - Manual Upgrade Required', 'WPBDM' ) . '</strong>';
        print '<br />';
        _e( 'Business Directory features are currently disabled because the plugin needs to perform a manual upgrade before continuing.', 'WPBDM' );
        print '<br /><br />';
        printf( '<a class="button button-primary" href="%s">%s</a>', admin_url( 'admin.php?page=wpbdp-upgrade-page' ), __( 'Perform Manual Upgrade', 'WPBDM' ) );
        print '</p></div>';
    }

    public function add_upgrade_page() {
        global $submenu;

        // Make "Directory" menu items point to upgrade page.
        $menu_id = 'edit.php?post_type=' . WPBDP_POST_TYPE;
        if ( isset( $submenu[ $menu_id ] ) ) {
            foreach ( $submenu[ $menu_id ] as &$item ) {
                $item[2] = admin_url( 'admin.php?page=wpbdp-upgrade-page' );
            }
        }

        add_submenu_page( 'options.php',
                          __( 'Business Directory - Manual Upgrade', 'WPBDM' ),
                          __( 'Business Directory - Manual Upgrade', 'WPBDM' ),
                          'administrator',
                          'wpbdp-upgrade-page',
                          array( &$this, 'upgrade_page' ) );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'wpbdp-manual-upgrade' , WPBDP_URL . 'admin/resources/manual-upgrade.js' );
    }

    public function upgrade_page() {
        echo wpbdp_admin_header( __( 'Business Directory - Manual Upgrade', 'WPBDM' ), 'manual-upgrade', null, false );

        echo '<div class="step-upgrade">';
        echo '<p>';
        _e( 'Business Directory features are currently disabled because the plugin needs to perform a manual upgrade before it can be used.', 'WPBDM' );
        echo '<br />';
        _e( 'Click "Start Upgrade" and wait until the process finishes.', 'WPBDM' );
        echo '</p>';
        echo '<p>';
        echo '<a href="#" class="start-upgrade button button-primary">' . _x( 'Start Upgrade', 'manual-upgrade', 'WPBDM' ) . '</a>';
        echo ' ';
        echo '<a href="#" class="pause-upgrade button">' . _x( 'Pause Upgrade', 'manual-upgrade', 'WPBDM' ) . '</a>';
        echo '</p>';
        echo '<textarea id="manual-upgrade-progress" rows="20" style="width: 90%; font-family: courier, monospaced; font-size: 12px;" readonly="readonly"></textarea>';
        echo '</div>';

        echo '<div class="step-done" style="display: none;">';
        echo '<p>' . _x( 'The upgrade was sucessfully performed. Business Directory Plugin is now available.', 'manual-upgrade', 'WPBDM' ) . '</p>';
        printf ( '<a href="%s" class="button button-primary">%s</a>',
                 admin_url( 'admin.php?page=wpbdp_admin' ),
                 _x( 'Go to "Directory Admin"', 'manual-upgrade', 'WPBDM' ) );
        echo '</div>';

        echo wpbdp_admin_footer();
    }

    public function handle_ajax() {
        // if ( ! current_user_can( 'administrator' ) || ! isset( $_POST['action'] ) )
        //     return;

        $response = call_user_func( array( $this->installer, $this->callback ) );

        print json_encode( $response );

        if ( $response['done'] )
            delete_option( 'wpbdp-manual-upgrade-pending' );

        exit();
    }

}
