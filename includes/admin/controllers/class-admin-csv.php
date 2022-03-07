<?php

/**
 * Admin CSV import and export controller.
 */
class WPBDP__Admin__Csv extends WPBDP__Admin__Controller {

	public function __construct() {
		parent::__construct();

		require_once WPBDP_INC . 'admin/csv-import.php';
		$this->csv_import = new WPBDP_CSVImportAdmin();

		require_once WPBDP_INC . 'admin/csv-export.php';
		$this->csv_export = new WPBDP_Admin_CSVExport();
	}

	public function _dispatch() {
		$tabs = array( 'csv_import', 'csv_export' );

		$current_tab = wpbdp_get_var( array( 'param' => 'tab' ) );
		if ( empty( $current_tab ) ) {
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

        <?php if ( 'csv_import' == $current_tab ) : ?>
        <div class="wpbdp-csv-import-top-buttons">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp_admin_csv&action=example-csv' ) ); ?>" class="button"><?php _ex( 'See an example CSV import file', 'admin csv-import', 'business-directory-plugin' ); ?></a>
			<a href="#help" class="button"><?php esc_html_e( 'Help', 'business-directory-plugin' ); ?></a>
        </div>
        <?php endif; ?>


        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo 'csv_import' == $current_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp_admin_csv&tab=csv_import' ) ); ?>"><span class="dashicons dashicons-download"></span> <?php _ex( 'Import', 'admin csv', 'business-directory-plugin' ); ?></a>
            <a class="nav-tab <?php echo 'csv_export' == $current_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp_admin_csv&tab=csv_export' ) ); ?>"><span class="dashicons dashicons-upload"></span> <?php _ex( 'Export', 'admin csv', 'business-directory-plugin' ); ?></a>
        </h2>
		<?php
		echo $output;
		echo wpbdp_admin_footer();
	}

}

