<?php
/**
 * CSV Import admin pages.
 *
 * @package BDP/Includes/Admin
 */

require_once WPBDP_INC . 'admin/helpers/csv/class-csv-import.php';
/**
 * CSV Import admin pages.
 *
 * @since 2.1
 */
class WPBDP_CSVImportAdmin {

	private $files = array(
		'images' => '',
		'csv'    => '',
	);

    function __construct() {
        global $wpbdp;

        add_action( 'wpbdp_enqueue_admin_scripts', array( &$this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_wpbdp-csv-import', array( &$this, 'ajax_csv_import' ) );
        add_action( 'wp_ajax_wpbdp-autocomplete-user', array( &$this, 'ajax_autocomplete_user' ) );
    }

    function enqueue_scripts() {
        wp_enqueue_script(
            'wpbdp-admin-import-js',
            WPBDP_ASSETS_URL . 'js/admin-csv-import.min.js',
            array( 'wpbdp-admin-js', 'jquery-ui-autocomplete' ),
            WPBDP_VERSION,
			true
        );

        wp_enqueue_style(
            'wpbdp-admin-import-css',
            WPBDP_ASSETS_URL . 'css/admin-csv-import.min.css',
            array(),
            WPBDP_VERSION
        );
    }

    function ajax_csv_import() {
        global $wpbdp;

        if ( ! current_user_can( 'administrator' ) ) {
            die();
        }

        $import_id = wpbdp_get_var( array( 'param' => 'import_id', 'default' => 0 ), 'post' );

        if ( ! $import_id ) {
            die();
        }

        $res = new WPBDP_Ajax_Response();

        try {
            $import = new WPBDP_CSV_Import( $import_id );
        } catch ( Exception $e ) {
            if ( isset( $import ) && $import ) {
                $import->cleanup();
            }
            $res->send_error( $e->getMessage() );
        }

        if ( ! empty( $_POST['cleanup'] ) ) {
            $import->cleanup();
            $res->send();
        }

        $wpbdp->_importing_csv          = true;
        $wpbdp->_importing_csv_no_email = (bool) $import->get_setting( 'disable-email-notifications' );

        wp_defer_term_counting( true );
        $import->do_work();
        wp_defer_term_counting( false );

        unset( $wpbdp->_importing_csv );
		unset( $wpbdp->_importing_csv_no_email );

        $res->add( 'done', $import->done() );
        $res->add( 'progress', $import->get_progress( 'n' ) );
        $res->add( 'total', $import->get_import_rows_count() );
        $res->add( 'imported', $import->get_imported_rows_count() );
        $res->add( 'rejected', $import->get_rejected_rows_count() );

        if ( $import->done() ) {
            $res->add( 'warnings', $import->get_errors() );
            $import->cleanup();
        }

        $res->send();
    }

    public function ajax_autocomplete_user() {
		$term  = wpbdp_get_var( array( 'param' => 'term' ), 'request' );
        $users = get_users( array( 'search' => "*{$term}*" ) );

        foreach ( $users as $user ) {
            $return[] = array(
                'label' => "{$user->display_name} ({$user->user_login})",
                'value' => $user->ID,
            );
        }

        wp_die( wp_json_encode( $return ) );
    }

    function dispatch() {
        $action = wpbdp_get_var( array( 'param' => 'action' ), 'request' );

        switch ( $action ) {
            case 'example-csv':
                $this->example_csv();
                break;
            case 'do-import':
                $this->import();
                break;
            default:
                $this->import_settings();
                break;
        }
    }

    private function example_data_for_field( $field = null, $shortname = null ) {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ( $field ) {
            if ( $field->get_association() == 'title' ) {
				/* translators: %s: Sample business name */
                return sprintf( esc_html__( 'Business %s', 'business-directory-plugin' ), $letters[ rand( 0, strlen( $letters ) - 1 ) ] );
            }

			if ( $field->get_association() === 'category' ) {
                if ( $terms = get_terms( WPBDP_CATEGORY_TAX, 'number=5&hide_empty=0' ) ) {
                    return $terms[ array_rand( $terms ) ]->name;
                }
				return '';
            }

			if ( $field->get_association() === 'tags' ) {
                if ( $terms = get_terms( WPBDP_TAGS_TAX, 'number=5&hide_empty=0' ) ) {
                    return $terms[ array_rand( $terms ) ]->name;
                }
				return '';
            }

			if ( $field->has_validator( 'url' ) ) {
                return get_site_url();
            } elseif ( $field->has_validator( 'email' ) ) {
                return get_option( 'admin_email' );
            } elseif ( $field->has_validator( 'integer_number' ) ) {
                return rand( 0, 100 );
            } elseif ( $field->has_validator( 'decimal_number' ) ) {
                return rand( 0, 100 ) / 100.0;
            } elseif ( $field->has_validator( 'date_' ) ) {
                return date( 'd/m/Y' );
            } elseif ( $field->get_field_type()->get_id() == 'multiselect' || $field->get_field_type()->get_id() == 'checkbox' ) {
                if ( $field->data( 'options' ) ) {
                    $options = $field->data( 'options' );
                    return $options[ array_rand( $options ) ];
                }

                return '';
            }
        }

        if ( $shortname == 'user' ) {
            $users = get_users();
            return $users[ array_rand( $users ) ]->user_login;
        }

        return _x( 'Whatever', 'admin csv-import', 'business-directory-plugin' );
    }

    private function example_csv() {
		wpbdp_admin_header(
			array(
				'title'   => __( 'Example CSV Import File', 'business-directory-plugin' ),
				'buttons' => array(
					'return' => array(
						'label' => __( 'Go Back', 'business-directory-plugin' ),
						'url'   => remove_query_arg( 'action' ),
					)
				),
				'echo'    => true,
				'sidebar' => false,
			)
		);

        $posts = get_posts(
            array(
				'post_type'        => WPBDP_POST_TYPE,
				'post_status'      => 'publish',
				'numberposts'      => 10,
				'suppress_filters' => false,
            )
        );

        // echo sprintf('<input type="button" value="%s" />', _x('Copy CSV', 'admin csv-import', 'business-directory-plugin'));
        echo '<textarea class="wpbdp-csv-import-example" rows="30">';

        $fields = wpbdp_get_form_fields( array( 'field_type' => '-ratings' ) );

        foreach ( $fields as $f ) {
            echo $f->get_short_name() . ',';
        }
        echo 'username,fee_id';
        echo "\n";

        if ( count( $posts ) >= 5 ) {
            foreach ( $posts as $post ) {
                foreach ( $fields as $f ) {
                    $value = $f->plain_value( $post->ID );

                    echo str_replace( ',', ';', $value );
                    echo ',';
                }
                echo get_the_author_meta( 'user_login', $post->post_author );
                $fee = wpbdp_get_listing( $post->ID )->get_fee_plan();
                echo ',';
                echo $fee ? $fee->fee_id : '';

                echo "\n";
            }
        } else {
            for ( $i = 0; $i < 5; $i++ ) {
                foreach ( $fields as $f ) {
                    echo sprintf( '"%s"', $this->example_data_for_field( $f, $f->get_short_name() ) );
                    echo ',';
                }

                echo sprintf( '"%s"', $this->example_data_for_field( null, 'user' ) );
                echo "\n";
            }
		}

        echo '</textarea>';

        echo wpbdp_admin_footer();
    }

    private function get_imports_dir() {
        $upload_dir = wp_upload_dir();

        if ( $upload_dir['error'] ) {
            return false;
        }

        $imports_dir = rtrim( $upload_dir['basedir'], DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'wpbdp-csv-imports';
        return $imports_dir;
    }

    private function find_uploaded_files() {
        $base_dir = $this->get_imports_dir();

        $res = array(
			'images' => array(),
			'csv'    => array(),
		);

        if ( is_dir( $base_dir ) ) {
            $files = wpbdp_scandir( $base_dir );

            foreach ( $files as $f_ ) {
                $f = $base_dir . DIRECTORY_SEPARATOR . $f_;

                if ( ! is_file( $f ) || ! is_readable( $f ) ) {
                    continue;
                }

                switch ( strtolower( substr( $f, -4 ) ) ) {
                    case '.csv':
                        $res['csv'][] = $f;
                        break;
                    case '.zip':
                        $res['images'][] = $f;
                        break;
                    default:
                        break;
                }
            }
        }

        return $res;
    }

    private function import_settings() {
        $import_dir = $this->get_imports_dir();

        if ( $import_dir && ! is_dir( $import_dir ) ) {
            @mkdir( $import_dir, 0777 );
        }

        $files = array();

        if ( ! $import_dir || ! is_dir( $import_dir ) || ! is_writable( $import_dir ) ) {
            wpbdp_admin_message(
                sprintf(
                    _x(
                        'A valid temporary directory with write permissions is required for CSV imports to function properly. Your server is using "%s" but this path does not seem to be writable. Please consult with your host.',
                        'csv import',
                        'business-directory-plugin'
                    ),
                    $import_dir
                )
            );
        }

        $files = $this->find_uploaded_files();

        // Retrieve last used settings to use as defaults.
        $defaults = get_user_option( 'wpbdp-csv-import-settings' );
        if ( ! $defaults || ! is_array( $defaults ) ) {
            $defaults = array();
        }

        echo wpbdp_render_page(
            WPBDP_PATH . 'templates/admin/csv-import.tpl.php',
            array(
				'files'    => $files,
				'defaults' => $defaults,
            )
        );
    }

    private function import() {
		$nonce = wpbdp_get_var( array( 'param' => '_wpnonce' ), 'post' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'do-import' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'business-directory-plugin' ) );
		}

		$sources = array();
		$error   = '';

        // CSV file.
		$error = $this->add_file_to_sources( 'csv', $sources );
		if ( $error ) {
			$this->show_error( $error );
			return;
		}

		if ( ! $this->files['csv'] ) {
			$this->show_error( _x( 'Please upload or select a CSV file.', 'admin csv-import', 'business-directory-plugin' ) );
			return;
		}

        // Images file.
		$error = $this->add_file_to_sources( 'images', $sources );

		if ( $error ) {
			$this->show_error( $error );
			return;
		}

        // Store settings to use as defaults next time.
		$settings = wpbdp_get_var( array( 'param' => 'settings' ), 'post' );
		update_user_option( get_current_user_id(), 'wpbdp-csv-import-settings', $settings, false );

        $import = null;
        try {
            $import = new WPBDP_CSV_Import(
                '',
				$this->files['csv'],
				$this->files['images'],
				array_merge( $settings, array( 'test-import' => ! empty( $_POST['test-import'] ) ) )
            );
        } catch ( Exception $e ) {
            if ( $import ) {
                $import->cleanup();
            }

            $error  = _x( 'An error was detected while validating the CSV file for import. Please fix this before proceeding.', 'admin csv-import', 'business-directory-plugin' );
            $error .= '<br />';
			$error .= '<b>' . esc_html( $e->getMessage() ) . '</b>';

			$this->show_error( $error );
			return;
        }

        if ( $import->in_test_mode() ) {
            wpbdp_admin_message( _x( 'Import is in "test mode". Nothing will be inserted into the database.', 'admin csv-import', 'business-directory-plugin' ) );
        }

        echo wpbdp_render_page(
            WPBDP_PATH . 'templates/admin/csv-import-progress.tpl.php',
            array(
				'import'  => $import,
				'sources' => $sources,
            )
        );
    }

	/**
	 * @since 5.11
	 */
	private function show_error( $error ) {
		wpbdp_admin_message( $error, 'error' );
		$this->import_settings();
	}

	/**
	 * @param $type    string - 'csv' or 'image'
	 * @param $sources array
	 *
	 * @since 5.11
	 */
	private function add_file_to_sources( $type, &$sources ) {
		$file = wpbdp_get_var( array( 'param' => $type . '-file-local' ), 'post' );

		if ( $file && $this->is_correct_type( $allowed_type[ $type ], $file ) ) {
			$this->files[ $type ] = $this->get_imports_dir() . DIRECTORY_SEPARATOR . basename( $file );

			$sources[] = basename( $this->files[ $type ] );
			return;
		}

		$file = $this->get_file_name( $type . '-file', 'tmp' );
		if ( empty( $_FILES[ $type . '-file' ] ) || empty( $file ) ) {
			return;
		}

		$this->files[ $type ] = $file;

		$no_file  = UPLOAD_ERR_NO_FILE == $_FILES[ $type . '-file' ]['error'] || ! is_uploaded_file( $this->files[ $type ] );
		if ( $no_file ) {
			return __( 'There was an error uploading the file:', 'business-directory-plugin' ) . ' ' . $type;
		}

		$filename = $this->get_file_name( $type . '-file' );
		if ( ! $this->is_correct_type( $type, $filename ) ) {
			return __( 'Please upload the correct file type.', 'business-directory-plugin' );
		}

		$sources[] = $filename;
	}

	/**
	 * @since 5.11
	 */
	private function is_correct_type( $type, $filename ) {
		$allowed_type = array(
			'images' => 'zip',
			'csv'    => 'csv',
		);

		$uploaded_type = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		return $uploaded_type === $allowed_type[ $type ];
	}

	/**
	 * Unslashing causes issues in Windows.
	 *
	 * @since 5.11
	 */
	private function get_file_name( $name, $temp = false ) {
		$value = $temp ? 'tmp_name' : 'name';

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		return isset( $_FILES[ $name ][ $value ] ) ? sanitize_option( 'upload_path', $_FILES[ $name ][ $value ] ) : '';
	}
}
