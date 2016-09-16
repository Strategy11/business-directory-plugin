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

        // TODO: featured_price and featured_level are temporary while we decide what to do with featured levels.
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
            status varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'ok',
            featured_price decimal(10,2) NOT NULL DEFAULT 0.00,
            featured_level VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
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

        new WPBDP_Installer_Manual_Upgrade( $this, $manual_upgrade );
    }

    public function handle_term_split( $old_id, $new_id, $tt_id, $tax ) {
        if ( WPBDP_CATEGORY_TAX != $tax )
            return;

        require_once ( WPBDP_PATH . 'migrations/migration-5_0.php' );
        $m = new WPBDP__Migrations__5_0();
        $m->process_term_split( $old_id );
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

        // Try to load class.
        $this->installer = $installer;
        $this->callback = $callback;
    }

    public function upgrade_required_notice() {
        global $pagenow;

        if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'wpbdp-upgrade-page' == $_GET['page'] )
            return;

        if ( ! current_user_can( 'administrator' ) )
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
        if ( ! current_user_can( 'administrator' ) )
            return;

        if ( is_array( $this->callback ) ) {
            $classname = $this->callback[0];
            $method = $this->callback[1];

            $file = WPBDP_PATH . 'core/migrations/migration-' . str_replace( 'WPBDP__Migrations__', '', $classname ) . '.php';
            require_once( $file );
            $m = new $classname;

            $response = call_user_func( array( $m, $method ) );
        } else {
            $response = call_user_func( $this->callback );
        }

        print json_encode( $response );

        if ( $response['done'] )
            delete_option( 'wpbdp-manual-upgrade-pending' );

        exit();
    }

}
