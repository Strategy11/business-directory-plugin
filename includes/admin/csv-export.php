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

					$existing_token = wpbdp_get_var( array( 'param' => 'existing_token' ), 'request' );
					if ( $existing_token ) {
						delete_transient( 'wpbdp_export_' . $existing_token );
					}
				} else {
					$export->advance();
				}
			}
		} catch ( Exception $e ) {
			$error = $e->getMessage();
		}

		$state = ! $error ? $export->get_state() : null;

		if ( $state && ! isset( $state['token'] ) ) {
			$state['token'] = wp_generate_password( 32, false );
		}

		$this->send_export_response( $export, $error, $state );
	}

	/**
	 * Send the response to the browser.
	 *
	 * @since 6.4.20
	 * 
	 * @param WPBDP_CSVExporter $export The export object.
	 * @param string $error The error message.
	 * @param array $state The export state.
	 */
	private function send_export_response( $export, $error, $state ) {
		$response = array(
			'error'        => $error,
			'state'        => null,
			'count'        => 0,
			'exported'     => 0,
			'filesize'     => 0,
			'isDone'       => false,
			'fileurl'      => '',
			'filename'     => '',
			'download_url' => '',
			'token'        => '',
		);

		if ( ! $state ) {
			wp_send_json( $response );
		}

		$response['state']    = base64_encode( wp_json_encode( $state ) );
		$response['count']    = count( $state['listings'] );
		$response['exported'] = $state['exported'];
		$response['filesize'] = size_format( $state['filesize'] );
		$response['isDone']   = $state['done'];

		if ( ! $state['done'] ) {
			wp_send_json( $response );
		}

		$response['fileurl']      = $export->get_file_url();
		$response['filename']     = basename( $export->get_file_url() );
		$response['download_url'] = $this->get_download_url( $state );
		$response['token']        = $state['token'];

		wp_send_json( $response );
	}

	/**
	 * Handle CSV file download with proper headers to force download.
	 *
	 * @since 6.4.18
	 * 
	 * @return void
	 */
	public function ajax_csv_download() {
		WPBDP_App_Helper::permission_check( 'manage_options' );
		check_ajax_referer( 'wpbdp_ajax', 'nonce' );

		$token       = wpbdp_get_var( array( 'param' => 'token' ), 'request' );
		$state_param = wpbdp_get_var( array( 'param' => 'state' ), 'request' );

		if ( ! $token && ! $state_param ) {
			wp_die( esc_html__( 'Invalid download request.', 'business-directory-plugin' ) );
		}

		$state = $token ? get_transient( 'wpbdp_export_' . $token ) : json_decode( base64_decode( $state_param ), true );
		
		if ( ! $state || ! is_array( $state ) || empty( $state['workingdir'] ) ) {
			wp_die( esc_html__( 'Invalid export state or token expired.', 'business-directory-plugin' ) );
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

			// We ignore the PHPCS warning because we expect to have large export files and they need to be read in chunks.
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
	 * @since 6.4.18
	 * 
	 * @param array $state The export state.
	 * 
	 * @return string The download URL.
	 */
	private function get_download_url( $state ) {
		$token = $state['token'];

		set_transient( 'wpbdp_export_' . $token, $state, HOUR_IN_SECONDS );
		
		return add_query_arg(
			array(
				'action' => 'wpbdp-csv-download',
				'token'  => $token,
				'nonce'  => wp_create_nonce( 'wpbdp_ajax' ),
			),
			admin_url( 'admin-ajax.php' )
		);
	}
}
