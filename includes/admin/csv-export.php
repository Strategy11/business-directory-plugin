<?php

require_once WPBDP_INC . 'admin/helpers/csv/class-csv-exporter.php';

/**
 * CSV Export admin pages.
 *
 * @since 3.2
 */
class WPBDP_Admin_CSVExport {

	public function __construct() {
		add_action( 'wpbdp_enqueue_admin_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_wpbdp-csv-export', array( &$this, 'ajax_csv_export' ) );
		add_action( 'wp_ajax_wpbdp-csv-download', array( &$this, 'ajax_csv_download' ) );
	}

	public function enqueue_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'wpbdp-admin-export-js',
			WPBDP_ASSETS_URL . 'js/admin-export' . $min . '.js',
			array( 'wpbdp-admin-js' ),
			WPBDP_VERSION,
			true
		);

		wp_enqueue_style(
			'wpbdp-admin-export-css',
			WPBDP_ASSETS_URL . 'css/admin-export.min.css',
			array(),
			WPBDP_VERSION
		);
	}

	public function dispatch() {
		wpbdp_render_page( WPBDP_PATH . 'templates/admin/csv-export.tpl.php', array(), true );
	}

	public function ajax_csv_export() {
		WPBDP_App_Helper::permission_check( 'manage_options' );
		check_ajax_referer( 'wpbdp_ajax', 'nonce' );

		$error = '';

		try {
			if ( ! isset( $_REQUEST['state'] ) ) {
				$export = new WPBDP_CSVExporter( array_merge( wpbdp_get_var( array( 'param' => 'settings' ), 'request' ), array() ) );
			} else {
				$state = json_decode( base64_decode( wpbdp_get_var( array( 'param' => 'state' ), 'request' ) ), true );
				if ( ! $state || ! is_array( $state ) || empty( $state['workingdir'] ) ) {
					$error = _x( 'Could not decode export state information.', 'admin csv-export', 'business-directory-plugin' );
				}

				$export = WPBDP_CSVExporter::from_state( $state );

				if ( 1 === intval( wpbdp_get_var( array( 'param' => 'cleanup' ), 'request' ) ) ) {
					$export->cleanup();
				} else {
					$export->advance();
				}
			}
		} catch ( Exception $e ) {
			$error = $e->getMessage();
		}

		$state = ! $error ? $export->get_state() : null;

		$response                 = array();
		$response['error']        = $error;
		$response['state']        = $state ? base64_encode( json_encode( $state ) ) : null;
		$response['count']        = $state ? count( $state['listings'] ) : 0;
		$response['exported']     = $state ? $state['exported'] : 0;
		$response['filesize']     = $state ? size_format( $state['filesize'] ) : 0;
		$response['isDone']       = $state ? $state['done'] : false;
		$response['fileurl']      = $state ? ( $state['done'] ? $export->get_file_url() : '' ) : '';
		$response['filename']     = $state ? ( $state['done'] ? basename( $export->get_file_url() ) : '' ) : '';
		$response['download_url'] = $state ? ( $state['done'] ? $this->get_download_url( $state ) : '' ) : '';

		echo json_encode( $response );

		die();
	}

	/**
	 * Handle CSV file download with proper headers to force download.
	 *
	 * @since x.x
	 * 
	 * @return void
	 */
	public function ajax_csv_download() {
		WPBDP_App_Helper::permission_check( 'manage_options' );
		check_ajax_referer( 'wpbdp_ajax', 'nonce' );

		$state_param = wpbdp_get_var( array( 'param' => 'state' ), 'request' );
		
		if ( ! $state_param ) {
			wp_die( esc_html__( 'Invalid download request.', 'business-directory-plugin' ) );
		}

		$state = json_decode( base64_decode( $state_param ), true );
		
		if ( ! $state || ! is_array( $state ) || empty( $state['workingdir'] ) ) {
			wp_die( esc_html__( 'Invalid export state.', 'business-directory-plugin' ) );
		}

		try {
			$export    = WPBDP_CSVExporter::from_state( $state );
			$file_path = $export->get_file_path();
			$file_url  = $export->get_file_url();
			
			if ( ! file_exists( $file_path ) ) {
				throw new Exception( esc_html__( 'Export file not found.', 'business-directory-plugin' ) );
			}

			$filename = basename( $file_url );
			$filesize = filesize( $file_path );

			if ( false !== $filesize ) {
				header( 'Content-Length: ' . $filesize );
			}

			// We set the content type to application/octet-stream to overwrite the text/csv headers added by some hosting providers.
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Cache-Control: no-cache, must-revalidate' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			while ( ob_get_level() ) {
				ob_end_clean();
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
			readfile( $file_path );
			exit;

		} catch ( Exception $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Get the download URL for the export file.
	 *
	 * @since x.x
	 * 
	 * @param array $state The export state.
	 * 
	 * @return string The download URL.
	 */
	private function get_download_url( $state ) {
		return add_query_arg(
			array(
				'action' => 'wpbdp-csv-download',
				'state'  => base64_encode( json_encode( $state ) ),
				'nonce'  => wp_create_nonce( 'wpbdp_ajax' ),
			),
			admin_url( 'admin-ajax.php' )
		);
	}
}
