<?php
class WPBDP__Manual_Upgrade_Helper {

    private $installer;
    private $data = array();
    private $callback;
    private $config_callback = null;


    public function __construct( &$installer, $upgrade_config ) {
        // Try to load class.
        $this->installer = $installer;
        $this->data = $upgrade_config;

        $callback_definition = isset( $this->data['callback'] ) ? $this->data['callback'] : $this->data;
        $config_callback_definition = isset( $this->data['config_callback'] ) ? $this->data['config_callback'] : null;

        $this->callback = $this->get_callback( $callback_definition );

        if ( $config_callback_definition ) {
            $this->config_callback = $this->get_configuration_callback( $config_callback_definition );
        } else {
            $this->config_callback = null;
        }

        add_action( 'admin_notices', array( &$this, 'upgrade_required_notice' ) );
        add_action( 'admin_menu', array( &$this, 'add_upgrade_page' ) );
        add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_wpbdp-manual-upgrade', array( &$this, 'handle_ajax' ) );
    }

    private function get_callback( $params ) {
        $callback = $this->get_migration_callback( $params );

        if ( ! is_callable( $callback ) ) {
            throw new Exception( 'Invalid upgrade callback provided.' );
        }

        return $callback;
    }

    private function get_migration_callback( $params ) {
        if ( is_array( $params ) ) {
            $classname = $params[0];
            $method = $params[1];

            $migration = $this->installer->load_migration_class( $classname );

            $callback = array( $migration, $method );
        } else {
            $callback = $params;
        }

        return $callback;
    }

    private function get_configuration_callback( $params ) {
        $callback = $this->get_migration_callback( $params );

        if ( ! is_callable( $callback ) ) {
            throw new Exception( 'Invalid upgrade config callback provided.' );
        }

        return $callback;
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
        wp_enqueue_style( 'wpbdp-admin', WPBDP_URL . 'assets/css/admin.min.css' );
        wp_enqueue_style( 'wpbdp-manual-upgrade-css', WPBDP_URL . 'assets/css/admin-manual-upgrade.min.css' );
        wp_enqueue_script( 'wpbdp-manual-upgrade' , WPBDP_URL . 'assets/js/admin-manual-upgrade.min.js' );
    }

    private function is_configured() {
        if ( ! $this->config_callback )
            return true;

        $latest_data = (array) get_option( 'wpbdp-manual-upgrade-pending', array() );
        return ! empty( $latest_data['configured'] );
    }

    public function upgrade_page() {
        echo wpbdp_admin_header( __( 'Business Directory - Manual Upgrade', 'WPBDM' ), 'manual-upgrade', null, false );
        echo '<div class="wpbdp-manual-upgrade-wrapper">';

        if ( ! $this->is_configured() ) {
            ob_start();
            call_user_func( $this->config_callback );
            $output = ob_get_contents();
            ob_end_clean();

            if ( ! $this->is_configured() ) {
                echo '<form action="" method="post">';
                echo '<div class="wpbdp-manual-upgrade-configuration">';
                echo $output;
                echo '<div class="cf"><input type="submit" class="right button button-primary" value="' . _x( 'Continue', 'manual-upgrade', 'WPBDM' ) . '"/></div>';
                echo '</div>';
                echo '</form>';
            }
        }

        if ( $this->is_configured() ) {
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
            echo '<p>' . _x( 'The upgrade was successfully performed. Business Directory Plugin is now available.', 'manual-upgrade', 'WPBDM' ) . '</p>';
            printf ( '<a href="%s" class="button button-primary">%s</a>',
                     admin_url( 'admin.php?page=wpbdp_admin' ),
                     _x( 'Go to "Directory Admin"', 'manual-upgrade', 'WPBDM' ) );
            echo '</div>';
        }

        echo '</div>';
        echo wpbdp_admin_footer();
    }

    public function handle_ajax() {
        if ( ! current_user_can( 'administrator' ) )
            return;

        $response = call_user_func( $this->callback );

        print json_encode( $response );

        if ( $response['done'] )
            delete_option( 'wpbdp-manual-upgrade-pending' );

        exit();
    }

}

