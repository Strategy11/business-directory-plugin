<?php
require_once ( WPBDP_PATH . 'core/class-migration.php' );


class WPBDP_Installer {

    const DB_VERSION = '15';

    private $installed_version = null;


    public function __construct() {
        $this->installed_version = get_option( 'wpbdp-db-version', get_option( 'wpbusdirman_db_version', null ) );

        add_action( 'split_shared_term', array( &$this, 'handle_term_split' ), 10, 4 );
    }

    public function install() {
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
            description TEXT DEFAULT '',
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            days smallint unsigned NOT NULL DEFAULT 0,
            images smallint unsigned NOT NULL DEFAULT 0,
            extra_data blob NULL,
            weight int(5) NOT NULL DEFAULT 0,
            sticky tinyint(1) NOT NULL DEFAULT 0,
            enabled tinyint(1) NOT NULL DEFAULT 1,
            tag varchar(255) NOT NULL DEFAULT '',
            pricing_model varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'flat',
            pricing_details blob NULL,
            supported_categories text NOT NULL DEFAULT ''
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

        $schema['payments'] = "CREATE TABLE {$wpdb->prefix}wpbdp_payments (
            id bigint(20) PRIMARY KEY  AUTO_INCREMENT,
            listing_id bigint(20) NOT NULL DEFAULT 0,
            parent_id bigint(20) NOT NULL DEFAULT 0,
            payment_key varchar(255) NULL DEFAULT '',
            payment_notes longblob NULL,
            payment_type varchar(255) NULL DEFAULT '',
            payment_items blob NULL,
            payer_email varchar(255) NULL DEFAULT '',
            payer_first_name varchar(255) NULL DEFAULT '',
            payer_last_name varchar(255) NULL DEFAULT '',
            payer_data blob NULL,
            gateway varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            gateway_tx_id varchar(255) NULL DEFAULT '',
            gateway_extra_data longblob NULL,
            currency_code varchar(3) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'USD',
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            status varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            created_on timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_on timestamp NULL DEFAULT NULL,
            processed_by varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
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

        $schema['listings_plans'] = "CREATE TABLE {$wpdb->prefix}wpbdp_listings_plans (
            listing_id bigint(20) PRIMARY KEY,
            fee_id bigint(20) NOT NULL,
            fee_price decimal(10,2) NOT NULL DEFAULT 0.00,
            fee_days smallint unsigned NOT NULL,
            fee_images smallint unsigned NOT NULL DEFAULT 0,
            expiration_date timestamp NULL DEFAULT NULL,
            is_recurring tinyint(1) NOT NULL DEFAULT 0,
            is_sticky tinyint(1) NOT NULL DEFAULT 0,
            subscription_id bigint(20) NULL,
            status varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'ok'
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

        $migrations = $this->get_pending_migrations();

        foreach ( $migrations as $version ) {
            wpbdp_log( sprintf( 'Running upgrade routine for version %s', $version ) );
            $migration_file = WPBDP_PATH . 'core/migrations/migration-' . str_replace( '.', '_', $version ) . '.php';

            if ( ! file_exists( $migration_file ) )
                continue;

            $classname = 'WPBDP__Migrations__' . str_replace( '.', '_', $version );

            require_once( $migration_file );
            $m = new $classname;
            $m->migrate();

            update_option('wpbdp-db-version', $version );

            if ( get_option( 'wpbdp-manual-upgrade-pending', false ) )
                break;
        }
        // $wpbdp->formfields->maybe_correct_tags();
    }

    public function get_pending_migrations() {
        $current_version = strval( $this->installed_version );
        $current_version = ( false === strpos( $current_version, '.' ) ) ? $current_version . '.0' : $current_version;

        $latest_version = strval( self::DB_VERSION );
        $latest_version = ( false === strpos( $latest_version, '.' ) ) ? $latest_version . '.0' : $latest_version;

        $migrations = array();

        foreach ( WPBDP_FS::ls( WPBDP_PATH . 'core/migrations/' ) as $_ ) {
            $version = str_replace( array( 'migration-', '.php', '_' ),
                                    array( '', '', '.' ),
                                    basename( $_ ) );

            if ( version_compare( $version, $current_version, '<' ) )
                continue;

            if ( version_compare( $version, $latest_version, '>' ) )
                continue;

            $migrations[] = $version;
        }

        sort( $migrations, SORT_NUMERIC );
        return $migrations;
    }

    public function setup_manual_upgrade() {
        $manual_upgrade = get_option( 'wpbdp-manual-upgrade-pending', false );

        if ( ! $manual_upgrade )
            return;

        require_once( WPBDP_PATH . 'core/helpers/class-manual-upgrade-helper.php' );

        $helper = new WPBDP__Manual_Upgrade_Helper( $this, $manual_upgrade );
    }

    public function handle_term_split( $old_id, $new_id, $tt_id, $tax ) {
        if ( WPBDP_CATEGORY_TAX != $tax )
            return;

        require_once ( WPBDP_PATH . 'migrations/migration-5_0.php' );
        $m = new WPBDP__Migrations__5_0();
        $m->process_term_split( $old_id );
    }

}

