<?php
/**
 * @package WPBDP
 */

require_once WPBDP_INC . 'helpers/functions/debug.php';
require_once WPBDP_INC . 'helpers/class-database-helper.php';
require_once WPBDP_INC . 'helpers/class-email.php';
require_once WPBDP_INC . 'compatibility/class-ajax-response.php';
require_once WPBDP_INC . 'helpers/class-fs.php';

class WPBDP_Utils {

	/**
	 * @since 5.2.1
	 */
	public static $property = null;

	/**
	 * @since 3.6.10
	 */
	public static function normalize( $val = '', $opts = array() ) {
		$res = strtolower( $val );
		$res = remove_accents( $res );
		$res = preg_replace( '/\s+/', '_', $res );
		$res = preg_replace( '/[^a-zA-Z0-9_-]+/', '', $res );

		return $res;
	}

	/**
	 * @since 5.0
	 */
	public static function sort_by_property( &$array, $prop ) {
		self::$property = $prop;

		uasort( $array, array( 'WPBDP_Utils', 'sort_by_property_callback' ) );

		self::$property = null;
	}

	/**
	 * @param array $left   Entry to compare.
	 * @param array $right  Entry to compare.
	 * @since 5.2.1
	 */
	public static function sort_by_property_callback( $left, $right ) {
		self::get_sort_value( $left );
		self::get_sort_value( $right );

		return $left - $right;
	}

	private static function get_sort_value( &$side ) {
		$side = (array) $side;
		$side = isset( $side[ self::$property ] ) ? $side[ self::$property ] : 0;
	}

	/**
	 * @since 5.0
	 */
	public static function table_exists( $table ) {
		global $wpdb;

		$res = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		return count( $res ) > 0;
	}

	/**
	 * @since 5.0
	 */
	public static function table_has_col( $table, $col ) {
		if ( ! self::table_exists( $table ) ) {
			return false;
		}

		global $wpdb;
		$columns = wp_filter_object_list( $wpdb->get_results( "DESCRIBE {$table}" ), array(), 'and', 'Field' );
		return in_array( $col, $columns, true );
	}

	/**
	 * @since 5.0
	 */
	public static function table_drop_col( $table, $col ) {
		if ( ! self::table_has_col( $table, $col ) ) {
			return false;
		}

		global $wpdb;
		$wpdb->query( "ALTER TABLE {$table} DROP COLUMN {$col}" );
	}

	/**
	 * Check cache before fetching values and saving to cache
	 *
	 * @since v5.9
	 *
	 * @param array  $args {
	 *     @type string $cache_key The unique name for this cache
	 *     @type string $group The name of the cache group
	 *     @type string $query If blank, don't run a db call
	 *     @type string $type The wpdb function to use with this query
	 *     @type int    $time When the cahce should expire
	 * }
	 *
	 * @return mixed $results The cache or query results
	 */
	public static function check_cache( $args ) {
		$defaults = array(
			'cache_key' => '',
			'group'     => '',
			'query'     => '',
			'type'      => 'get_var',
			'time'      => 300,
			'return'    => 'object',
		);
		$args     = array_merge( $defaults, $args );

		$type  = $args['type'];
		$query = $args['query'];

		$results = wp_cache_get( $args['cache_key'], $args['group'] );
		if ( $results !== false || empty( $query ) ) {
			return $results;
		}

		if ( 'get_posts' === $type ) {
			$results = get_posts( $query );
		} elseif ( 'get_post' === $type ) {
			$results = get_post( $query );
		} elseif ( 'all' === $type ) {
			$results = self::get_all_ids( $args );
		} elseif ( 'get_associative_results' === $type ) {
			global $wpdb;
			$results = $wpdb->get_results( $query, OBJECT_K ); // WPCS: unprepared SQL ok.
		} else {
			global $wpdb;
			if ( $args['return'] === 'array' ) {
				$results = $wpdb->{$type}( $query, ARRAY_A );
			} else {
				$results = $wpdb->{$type}( $query );
			}
		}

		self::set_cache( $args['cache_key'], $results, $args['group'], $args['time'] );

		return $results;
	}

	/**
	 * Reduce database calls by getting all rows at once.
	 *
	 * @since 5.11
	 */
	private static function get_all_ids( $args ) {
		global $wpdb;

		$table = $args['group'];
		$keys  = $args['query'];
		if ( empty( $keys ) ) {
			$keys = array( 'id' );
		}

		$query = 'SELECT * FROM ' . $wpdb->prefix . $table;
		if ( $args['return'] === 'array' ) {
			$results = $wpdb->get_results( $query, ARRAY_A );
		} else {
			$results = $wpdb->get_results( $query );
		}

		if ( ! $results ) {
			return array();
		}

		$all_rows = array();
		foreach ( $results as $row ) {
			foreach ( $keys as $key ) {
				$all_rows[ $row->$key ] = $row;
			}
		}

		return $all_rows;
	}

	/**
	 * @since v5.9
	 */
	public static function set_cache( $cache_key, $results, $group = '', $time = 300 ) {
		self::add_key_to_group_cache( $cache_key, $group );
		wp_cache_set( $cache_key, $results, $group, $time );
	}

	/**
	 * Keep track of the keys cached in each group so they can be deleted
	 * in Redis and Memcache
	 *
	 * @since v5.9
	 */
	public static function add_key_to_group_cache( $key, $group ) {
		$cached         = self::get_group_cached_keys( $group );
		$cached[ $key ] = $key;
		wp_cache_set( 'cached_keys', $cached, $group, 300 );
	}

	/**
	 * @since v5.9
	 */
	public static function get_group_cached_keys( $group ) {
		$cached = wp_cache_get( 'cached_keys', $group );
		if ( ! $cached || ! is_array( $cached ) ) {
			$cached = array();
		}

		return $cached;
	}

	/**
	 * Delete all caching in a single group
	 *
	 * @since v5.9
	 *
	 * @param string $group The name of the cache group
	 */
	public static function cache_delete_group( $group ) {
		$cached_keys = self::get_group_cached_keys( $group );

		if ( ! empty( $cached_keys ) ) {
			foreach ( $cached_keys as $key ) {
				wp_cache_delete( $key, $group );
			}

			wp_cache_delete( 'cached_keys', $group );
		}
	}

	/**
	 * Check if value contains blank value or empty array
	 *
	 * @since 5.9
	 *
	 * @param mixed  $value The value to check
	 * @param string $empty
	 *
	 * @return boolean
	 */
	public static function is_empty_value( $value, $empty = '' ) {
		return ( is_array( $value ) && empty( $value ) ) || $value === $empty;
	}

	public static function media_upload( $file_, $use_media_library = true, $check_image = false, $constraints = array(), &$error_msg = null, $sideload = false ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$sideload = is_string( $file_ ) && file_exists( $file_ );

		if ( $sideload ) {
			$mime_type = self::get_mimetype( $file_ );

			$file = array(
				'name'     => basename( $file_ ),
				'tmp_name' => $file_,
				'type'     => $mime_type,
				'error'    => 0,
				'size'     => filesize( $file_ ),
			);
		} else {
			$file = $file_;
		}

		if ( ! self::is_valid_upload( $file, $constraints, $error_msg ) ) {
			return false;
		}

		if ( ! $use_media_library ) {
			$upload = $sideload ? wp_handle_sideload( $file, array( 'test_form' => false ) ) : wp_handle_upload( $file, array( 'test_form' => false ) );

			if ( ! $upload || ! is_array( $upload ) || isset( $upload['error'] ) ) {
				$error_msg = isset( $upload['error'] ) ? $upload['error'] : _x( 'Unkown error while uploading file.', 'utils', 'business-directory-plugin' );
				return false;
			}

			return $upload;
		}

		$file_id = self::get_file_id( $file_ );
		if ( ! $sideload && ! empty( $_FILES[ $file_id ]['name'] ) && is_array( $_FILES[ $file_id ]['name'] ) ) {
			// Force an array of files to a single file.
			$file_id            = substr( sha1( (string) rand() ), 0, 5 );
			$_FILES[ $file_id ] = $file;
		}

		$attachment_id = $sideload ? media_handle_sideload( $file, 0 ) : media_handle_upload( $file_id, 0 );

		if ( is_wp_error( $attachment_id ) ) {
			$error_msg = $attachment_id->get_error_message();
			return false;
		}

		if ( ! is_numeric( $attachment_id ) ) {
			$error_msg = $attachment_id;
			return false;
		}

		return $attachment_id;
	}

	/**
	 * Attach an image to a media library after upload from `wp_handle_upload` or `wp_handle_sideload`.
	 * This is used to include an image into the media library and does not resize the image after import.
	 *
	 * @param array $file_data (
	 *     @type string $file Filename of the newly-uploaded file.
	 *     @type string $url  URL of the newly-uploaded file.
	 *     @type string $type Mime type of the newly-uploaded file.
	 * )
	 * @param int $post_id The optional post id to attatch the image to
	 *
	 * @since 5.18
	 *
	 * @return int|false The attachement id
	 */
	public static function attach_image_to_media_library( $file_data, $post_id = 0 ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$file       = $file_data['file'];
		$ext        = pathinfo( $file, PATHINFO_EXTENSION );
		$name       = wp_basename( $file, ".$ext" );
		$title      = sanitize_file_name( $name );
		$excerpt    = '';
		$image_meta = wp_read_image_metadata( $file );
		if ( $image_meta ) {
			$image_title = sanitize_title( $image_meta['title'] );
			if ( ! is_numeric( $image_title ) ) {
				$title = $image_meta['title'];
			}

			if ( trim( $image_meta['caption'] ) ) {
				$excerpt = $image_meta['caption'];
			}
		}
		$attachment    = array(
			'post_mime_type' => $file_data['type'],
			'guid'           => $file_data['url'],
			'post_parent'    => $post_id,
			'post_title'     => $title,
			'post_excerpt'   => $excerpt,
		);
		$attachment_id = wp_insert_attachment( $attachment, $file, $post_id, true );
		if ( is_wp_error( $attachment_id ) ) {
			return false;
		}
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file ) );
		return $attachment_id;
	}

	/**
	 * Attempts to get the mimetype of a file.
	 *
	 * @param string $file The path to a file.
	 *
	 * @since 5.16
	 */
	public static function get_mimetype( $file ) {
		$mime_type = null;

		if ( function_exists( 'mime_content_type' ) ) {
			$mime_type = mime_content_type( $file );
		}

		if ( null === $mime_type ) {
			$type_info = wp_check_filetype( $file, wp_get_mime_types() );
			$mime_type = $type_info['type'];
		}

		wpbdp_sanitize_value( 'sanitize_text_field', $mime_type );
		return $mime_type;
	}

	/**
	 * @since 5.16
	 */
	private static function is_valid_upload( $file, $constraints, &$error_msg ) {
		self::get_file_contrstraints( $constraints );

		if ( $file['error'] !== 0 ) {
			$error_msg = _x( 'Error while uploading file', 'utils', 'business-directory-plugin' );
			return false;
		}

		if ( $constraints['max-size'] > 0 && $file['size'] > $constraints['max-size'] ) {
			$error_msg = sprintf(
				__( 'File size (%1$s) exceeds maximum file size of %2$s', 'business-directory-plugin' ),
				size_format( $file['size'], 2 ),
				size_format( $constraints['max-size'], 2 )
			);
			return false;
		}

		if ( $constraints['min-size'] > 0 && $file['size'] < $constraints['min-size'] ) {
			$error_msg = sprintf(
				__( 'File size (%1$s) is smaller than the minimum file size of %2$s', 'business-directory-plugin' ),
				size_format( $file['size'], 2 ),
				size_format( $constraints['min-size'], 2 )
			);
			return false;
		}

		if ( is_array( $constraints['mimetypes'] ) && ! self::is_valid_file_type( $file, $constraints['mimetypes'] ) ) {
			$error_msg = sprintf( _x( 'File type "%s" is not allowed', 'utils', 'business-directory-plugin' ), $file['type'] );
			return false;
		}

		// We do not accept TIFF format. Compatibility issues.
		if ( in_array( strtolower( $file['type'] ), array( 'image/tiff' ) ) ) {
			$error_msg = sprintf( _x( 'File type "%s" is not allowed', 'utils', 'business-directory-plugin' ), $file['type'] );
			return false;
		}

		return true;
	}

	/**
	 * @since 5.16
	 */
	private static function get_file_contrstraints( &$constraints ) {
		$constraints = array_merge(
			array(
				'image'      => false,
				'min-size'   => 0,
				'max-size'   => 0,
				'min-width'  => 0,
				'min-height' => 0,
				'max-width'  => 0,
				'max-height' => 0,
				'mimetypes'  => null,
			),
			$constraints
		);

		foreach ( array( 'min-size', 'max-size', 'min-width', 'min-height', 'max-width', 'max-height' ) as $k ) {
			$constraints[ $k ] = absint( $constraints[ $k ] );
		}
	}

	/**
	 * Check the file type and extension.
	 *
	 * @param array $file
	 * @param array $mimetypes
	 *
	 * @since 6.0
	 *
	 * @return bool
	 */
	private static function is_valid_file_type( $file, $mimetypes ) {
		// If this is a multidimensional array, flatten it.
		if ( is_array( reset( $mimetypes ) ) ) {
			$mimetypes = array_values( $mimetypes );
			$mimetypes = call_user_func_array( 'array_merge', $mimetypes );
		}

		$mime_allowed = in_array( strtolower( $file['type'] ), $mimetypes, true );
		if ( ! $mime_allowed ) {
			return false;
		}

		// If the keys are numeric, we don't have the extensions to check.
		$check_extension = array_filter( array_keys( $mimetypes ), 'is_string' );
		if ( empty( $check_extension ) ) {
			return true;
		}

		$filename = sanitize_file_name( (string) wp_unslash( $file['name'] ) );
		$matches  = wp_check_filetype( $filename, $mimetypes );
		return ! empty( $matches['ext'] );
	}

	/**
	 * We have the file info, but WP needs the file id.
	 *
	 * @since 5.16
	 */
	private static function get_file_id( $_file ) {
		if ( empty( $_FILES ) ) {
			return '';
		}
		foreach ( $_FILES as $id => $file ) {
			if ( $file === $_file ) {
				return esc_attr( $id );
			}
		}
		reset( $_FILES );
		return esc_attr( key( $_FILES ) );
	}
}

/**
 * @deprecated Use {@link WPBDP_Utils} instead.
 */
// phpcs:ignore
class WPBDP__Utils extends WPBDP_Utils {
	public function __construct() {
		_deprecated_constructor( __CLASS__, '', 'WPBDP_Utils' );
	}
}

/**
 * No op object used to prevent modules from breaking a site while performing a manual upgrade
 * or something similar.
 * Instances of this class allow accessing any property or calling any function without side effects (errors).
 *
 * @since 3.4dev
 */
/* This class is not used i am not sure if it is used by other modules. */
// phpcs:ignore
class WPBDP_NoopObject {

	public function __construct() {
	}

	public function __set( $k, $v ) { }

	public function __get( $k ) {
		return null;
	}

	public function __isset( $k ) {
		return false;
	}

	public function __unset( $k ) { }

	public function __call( $name, $args = array() ) {
		return false;
	}
}
