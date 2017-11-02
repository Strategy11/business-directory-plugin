<?php

class WPBDP__Admin__Csv extends WPBDP__Admin__Controller {

    public function __construct() {
        parent::__construct();

        require_once( WPBDP_INC . 'admin/csv-import.php' );
        $this->csv_import = new WPBDP_CSVImportAdmin();

        require_once( WPBDP_INC . 'admin/csv-export.php' );
        $this->csv_export = new WPBDP_Admin_CSVExport();
    }

    public function _dispatch() {
        $tabs = array( 'csv_import', 'csv_export' );

        if ( ! empty( $_GET['tab'] ) ) {
            $current_tab = $_GET['tab'];
        } else {
            $current_tab = 'csv_import';
        }

        if ( ! in_array( $current_tab, $tabs ) ) {
            wp_die();
        }

        ob_start();
        call_user_func( array( $this->{$current_tab}, 'dispatch' ) );
        $output = ob_get_clean();

        echo wpbdp_admin_header();
        echo wpbdp_admin_notices();
?>

        <?php if ( 'csv_import' == $current_tab ): ?>
        <div class="wpbdp-csv-import-top-buttons">
            <a href="<?php echo esc_url(add_query_arg('action', 'example-csv')); ?>" class="button"><?php _ex('See an example CSV import file', 'admin csv-import', 'WPBDM'); ?></a>
            <a href="#help" class="button"><?php _ex('Help', 'admin csv-import', 'WPBDM'); ?></a>
        </div>
        <?php endif; ?>


        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo 'csv_import' == $current_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'csv_import' ) ); ?>"><span class="dashicons dashicons-download"></span> <?php _ex( 'Import', 'admin csv', 'WPBDM' ); ?></a>
            <a class="nav-tab <?php echo 'csv_export' == $current_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'csv_export' ) ); ?>"><span class="dashicons dashicons-upload"></span> <?php _ex( 'Export', 'admin csv', 'WPBDM' ); ?></a>
        </h2>
<?php
        echo $output;
        echo wpbdp_admin_footer();
    }

}

