<?php
require_once( WPBDP_PATH . 'core/debugging.php' );
require_once( WPBDP_PATH . 'core/class-email.php' );
require_once( WPBDP_PATH . 'core/class-ajax-response.php' );
require_once( WPBDP_PATH . 'core/helpers/class-fs.php' );
require_once( WPBDP_PATH . 'core/class-utils.php' );


/**
 * Restructures multidimensional $_FILES arrays into one key-based array per file.
 * Single-file arrays are returned as an array of one item for consistency.
 *
 * @since 3.4
 *
 * @param array $_FILES array
 * @return array
 */
function wpbdp_flatten_files_array( $files = array() ) {
    if ( ! isset( $files['tmp_name'] ) )
        return $files;

    if ( ! is_array( $files['tmp_name'] ) )
        return array( $files );

    $res = array();
    foreach ( $files as $k1 => $v1 ) {
        foreach ( $v1 as $k2 => $v2 ) {
            $res[ $k2 ][ $k1 ] = $v2;
        }
    }

    return $res;
}


/**
 * Returns properties and array values from objects or arrays, resp.
 *
 * @param array|object $dict
 * @param string $key Property name or array key.
 * @param mixed $default Optional. Defaults to `false`.
 *
 */
function wpbdp_getv($dict, $key, $default=false) {
    $_dict = is_object($dict) ? (array) $dict : $dict;

    if (is_array($_dict) && isset($_dict[$key]))
        return $_dict[$key];

    return $default;
}

function wpbdp_capture_action($hook) {
    $output = '';

    $args = func_get_args();
    if (count($args) > 1) {
        $args = array_slice($args,  1);
    } else {
        $args = array();
    }

    ob_start();
    do_action_ref_array($hook, $args);
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
}

function wpbdp_capture_action_array($hook, $args=array()) {
    $output = '';

    ob_start();
    do_action_ref_array($hook, $args);
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
}

function wpbdp_php_ini_size_to_bytes( $val ) {
    $val = trim( $val );
    $size = intval( $val );
    $unit = strtoupper( $val[strlen($val) - 1] );

    switch ( $unit ) {
        case 'G':
            $size *= 1024;
        case 'M':
            $size *= 1024;
        case 'K':
            $size *= 1024;
    }

    return $size;
}

function wpbdp_media_upload_check_env( &$error ) {
    if ( empty( $_FILES ) && empty( $_POST ) && isset( $_SERVER['REQUEST_METHOD'] ) &&
         strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post' ) {
        $post_max = wpbdp_php_ini_size_to_bytes( ini_get( 'post_max_size' ) );
        $posted_size = intval( $_SERVER['CONTENT_LENGTH'] );

        if ( $posted_size > $post_max ) {
            $error = _x( 'POSTed data exceeds PHP config. maximum. See "post_max_size" directive.', 'utils', 'WPBDM' );
            return false;
        }
    }

    return true;
}

/**
 * @since 2.1.6
 */
function wpbdp_media_upload($file_, $use_media_library=true, $check_image=false, $constraints=array(), &$error_msg=null, $sideload=false) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $sideload = ( is_string( $file_ ) && file_exists( $file_ ) ) ? true : false;

    if ( $sideload ) {
        $mime_type = '';

        if ( function_exists( 'finfo_open' ) ) {
            if ( $finfo = finfo_open( FILEINFO_MIME ) ) {
                $mime_type = explode( ';', finfo_file( $finfo, $file_ ) );
                $mime_type = trim( $mime_type[0] );
                finfo_close( $finfo );
            }
        } else {
            $type_info = wp_check_filetype( $file_, wp_get_mime_types() );
            $mime_type = $type_info['type'];
        }

        $file = array(
            'name' => basename( $file_ ),
            'tmp_name' => $file_,
            'type' => $mime_type,
            'error' => 0,
            'size' => filesize( $file_ )
        );
    } else {
        $file = $file_;
    }

    $constraints = array_merge( array(
                                    'image' => false,
                                    'min-size' => 0,
                                    'max-size' => 0,
                                    'min-width' => 0,
                                    'min-height' => 0,
                                    'max-width' => 0,
                                    'max-height' => 0,
                                    'mimetypes' => null
                              ), $constraints );

    foreach ( array( 'min-size', 'max-size', 'min-width', 'min-height', 'max-width', 'max-height' ) as $k )
        $constraints[ $k ] = absint( $constraints[ $k ] );

    if ($file['error'] == 0) {
        if ($constraints['max-size'] > 0 && $file['size'] > $constraints['max-size'] ) {
            $error_msg = sprintf( _x( 'File size (%s) exceeds maximum file size of %s', 'utils', 'WPBDM' ),
                                size_format ($file['size'], 2),
                                size_format ($constraints['max-size'], 2)
                                );
            return false;
        }

        if ( $constraints['min-size'] > 0 && $file['size'] < $constraints['min-size'] ) {
            $error_msg = sprintf( _x( 'File size (%s) is inferior to the required minimum file size of %s', 'utils', 'WPBDM' ),
                                size_format( $file['size'], 2 ),
                                size_format( $constraints['min-size'], 2 )
                                );
            return false;
        }

        if ( is_array( $constraints['mimetypes'] ) ) {
            if ( !in_array( strtolower( $file['type'] ), $constraints['mimetypes'] ) ) {
                $error_msg = sprintf( _x( 'File type "%s" is not allowed', 'utils', 'WPBDM' ), $file['type'] );
                return false;
            }
        }

        // We do not accept TIFF format. Compatibility issues.
        if ( in_array( strtolower( $file['type'] ), array('image/tiff') ) ) {
            $error_msg = sprintf( _x( 'File type "%s" is not allowed', 'utils', 'WPBDM' ), $file['type'] );
            return false;
        }

        $upload = $sideload ? wp_handle_sideload( $file, array( 'test_form' => FALSE ) ) : wp_handle_upload( $file, array('test_form' => FALSE) );

        if( ! $upload || ! is_array( $upload ) || isset( $upload['error'] ) ) {
            $error_msg = isset( $upload['error'] ) ? $upload['error'] : _x( 'Unkown error while uploading file.', 'utils', 'WPBDM' );
            return false;
        }

        if ( !$use_media_library )
            return $upload;

        if ( $attachment_id = wp_insert_attachment(array(
            'post_mime_type' => $upload['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
            'post_content' => '',
            'post_status' => 'inherit'
        ), $upload['file']) ) {
            $attach_metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
            wp_update_attachment_metadata( $attachment_id, $attach_metadata );

            if ( $check_image && ! wp_attachment_is_image( $attachment_id ) ) {
                wp_delete_attachment( $attachment_id, true );

                $error_msg = _x('Uploaded file is not an image', 'utils', 'WPBDM');
                return false;
            }

            if ( wp_attachment_is_image( $attachment_id ) ) {
                $meta = wp_get_attachment_metadata( $attachment_id );
                $failed = false;

                if ( ! $failed && $meta && $constraints['min-width'] > 0 && $meta['width'] < $constraints['min-width'] ) {
                    $error_msg = sprintf( _x( 'Image width (%s px) is inferior to minimum required width of %s px.', 'utils', 'WPBDM' ),
                                          $meta['width'],
                                          $constraints['min-width'] );
                }

                if ( ! $failed && $meta && $constraints['min-height'] > 0 && $meta['height'] < $constraints['min-height'] ) {
                    $error_msg = sprintf( _x( 'Image height (%s px) is inferior to minimum required height of %s px.', 'utils', 'WPBDM' ),
                                          $meta['height'],
                                          $constraints['min-height'] );
                }

                if ( ! $failed && $meta && $constraints['max-width'] > 0 && $meta['width'] > $constraints['max-width'] ) {
                    $error_msg = sprintf( _x( 'Image width (%s px) is greater than maximum allowed width of %s px.', 'utils', 'WPBDM' ),
                                          $meta['width'],
                                          $constraints['max-width'] );
                }

                if ( ! $failed && $meta && $constraints['max-height'] > 0 && $meta['height'] > $constraints['max-height'] ) {
                    $error_msg = sprintf( _x( 'Image height (%s px) is greater than maximum required height of %s px.', 'utils', 'WPBDM' ),
                                          $meta['height'],
                                         $constraints['max-height'] );
                }

                if ( $failed ) {
                    wp_delete_attachment( $attachment_id, true );
                    return false;
                }
            }

            return $attachment_id;
        }
    } else {
        $error_msg = _x('Error while uploading file', 'utils', 'WPBDM');
    }

    return false;
}

/**
 * Returns the domain used in the current request, optionally stripping
 * the www part of the domain.
 *
 * @since 2.1.5
 * @param $www  boolean     true to include the 'www' part,
 *                          false to attempt to strip it.
 */
function wpbdp_get_current_domain($www=true, $prefix='') {
    $domain = wpbdp_getv($_SERVER, 'HTTP_HOST', '');
    if (empty($domain)) {
        $domain = wpbdp_getv($_SERVER, 'SERVER_NAME', '');
    }

    if (!$www && substr($domain, 0, 4) === 'www.') {
        $domain = $prefix . substr($domain, 4);
    }

    return $domain;
}

/**
 * Bulds WordPress ajax URL using the same domain used in the current request.
 *
 * @since 2.1.5
 */
function wpbdp_ajaxurl($overwrite=false) {
    static $ajaxurl = false;

    if ($overwrite || $ajaxurl === false) {
        $url = admin_url('admin-ajax.php');
        $parts = parse_url($url);

        $domain = wpbdp_get_current_domain();

        // Since $domain already contains the port remove it.
        if ( isset( $parts['port'] ) && $parts['port'] )
            $domain = str_replace( ':' . $parts['port'], '', $domain );

        $ajaxurl = str_replace($parts['host'], $domain, $url);
    }

    return $ajaxurl;
}

/**
 * Removes a value from an array.
 * @since 2.3
 */
function wpbdp_array_remove_value( &$array_, &$value_ ) {
    $key = array_search( $value_, $array_ );

    if ( $key !== false ) {
        unset( $array_[$key] );
    }

    return true;
}

/**
 * Checks if a given string starts with another string.
 * @param string $str the string to be searched
 * @param string $prefix the prefix to search for
 * @return TRUE if $str starts with $prefix or FALSE otherwise
 * @since 3.0.3
 */
function wpbdp_starts_with( $str, $prefix, $case_sensitive=true ) {
    if ( !$case_sensitive )
        return stripos( $str, $prefix, 0 ) === 0;

    return strpos( $str, $prefix, 0 ) === 0;
}

/**
 * @since 3.1
 */
function wpbdp_format_time( $time=null, $format='mysql', $time_is_date=false ) {
    // TODO: add more formats
    switch ( $format ) {
        case 'mysql':
            return date( 'Y-m-d H:i:s', $time );
            break;
        default:
            break;
    }

    return $time;
}

/**
 * Returns the contents of a directory (ignoring . and .. special files).
 * @param string $path a directory.
 * @return array list of files within the directory.
 * @since 3.3
 */
function wpbdp_scandir( $path, $args = array() ) {
    if ( !is_dir( $path ) )
        return array();
/*
    $defaults = array(
        'filter' => false
    );
    $args = wp_parse_args( $args, $defaults );
    extract( $args );

    $filter = is_array( $filter ) ? $filter : ( $filter ? (array) $filter : false );*/
    $res = array_diff( scandir( $path ), array( '.', '..' ) );

/*    foreach ( $res as $i => &$r ) {
        $r = untrailingslashit( $path ) . '/' . $r;

        if ( $filter && ( ( ! in_array( 'dir', $filter ) && is_dir( $r ) ) || ( ! in_array( 'file', $filter ) && is_file( $r ) ) ) )
            unset( $res[ $i ] );
    }
*/
    return $res;
//    return array_diff( scandir( $path ), array( '.', '..' ) );
}

/**
 * Recursively deletes a directory.
 * @param string $path a directory.
 * @since 3.3
 * @deprecated since 3.6.10. Use {@link WPBDP_FS::rmdir} instead.
 */
function wpbdp_rrmdir( $path ) {
    return WPBDP_FS::rmdir( $path );
}

/**
 * Returns the name of a term.
 * @param id|string $id_or_slug The term ID or slug (see `$field`).
 * @param string $taxonomy Taxonomy name. Defaults to `WPBDP_CATEGORY_TAX` (BD's category taxonomy).
 * @param string $field Field used for the term lookup. Defaults to "id".
 * @param boolean $escape Whether to escape the name before returning or not. Defaults to `True`.
 * @return string The term name (if found) or an empty string otherwise.
 * @since 3.3
 */
function wpbdp_get_term_name( $id_or_slug, $taxonomy = WPBDP_CATEGORY_TAX, $field = 'id', $escape = true ) {
    $term = get_term_by( $field,
                         'id' == $field ? intval( $id_or_slug ) : $id_or_slug,
                         $taxonomy );

    if ( ! $term )
        return '';

    return $term->name;
}

function wpbdp_has_shortcode( &$content, $shortcode ) {
    $check = has_shortcode( $content, $shortcode );

    if ( ! $check ) {
        // Sometimes has_shortcode() fails so we try another approach.
        if ( false !== stripos( $content, '[' . $shortcode . ']' ) )
            $check = true;
    }

    return $check;
}

/**
 * TODO: dodoc.
 * @since 3.4.2
 */
function wpbdp_text_from_template( $setting_name, $replacements = array() ) {
    global $wpbdp;

    $setting = $wpbdp->settings->get_setting( $setting_name );

    if ( ! $setting )
        return false;

    $text = wpbdp_get_option( $setting_name );

    if ( ! $text )
        return false;

    $placeholders = isset( $setting->args['placeholders'] ) ? array_keys( $setting->args['placeholders'] ) : array();

    foreach ( $replacements as $pholder => $repl ) {
        if ( ! in_array( $pholder, $placeholders, true ) )
            continue;

        $text = str_replace( '[' . $pholder . ']', $repl, $text );
    }

    return $text;
}

/**
 * @since 3.5.4
 */
function wpbdp_email_from_template( $setting_or_file, $replacements = array(), $args = array() ) {
    global $wpbdp;

    $setting = null;
    $file = null;

    if ( is_file( $setting_or_file ) && is_readable( $setting_or_file ) )
        $file = $setting_or_file;
    else
        $setting = $wpbdp->settings->get_setting( $setting_or_file );

    if ( ( ! $setting && ! $file ) || ( $setting && 'email_template' != $setting->type ) )
        return false;

    if ( ! class_exists( 'WPBDP_Email' ) )
        require_once( WPBDP_PATH . 'core/class-email.php' );

    $placeholders = $setting ? ( isset( $setting->args['placeholders'] ) && is_array( $setting->args['placeholders'] ) ? $setting->args['placeholders'] : array() ) : array();

    // Add core replacements.
    $replacements = array_merge( $replacements, array(
        'site-title'    => get_bloginfo( 'name' ),
        'site-link'     => sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'name' ) ),
        'site-url'      => sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'url' ) ),
        'directory-url' => sprintf( '<a href="%1$s">%1$s</a>', wpbdp_get_page_link( 'main' ) ),
        'today'         => date_i18n( get_option( 'date_format' ) ),
        'now'           => date_i18n( get_option( 'time_format' ) )
    ) );

    if ( $file ) {
        $keys = array_keys( $replacements );
        $values = array_values( $replacements );

        // Normalize keys for PHP usage.
        foreach ( $keys as &$k )
            $k = str_replace( '-', '_', $k );

        $replacements = array_combine( $keys, $values );
    }

    $subject = '';
    $body = '';

    if ( $setting ) {
        $value = wpbdp_get_option( $setting->name );

        // Support old-style settings.
        if ( ! is_array( $value ) ) {
            $subject = $setting->default['subject'];
            $body = $setting->default['body'];
        } else {
            $subject = $value['subject'];
            $body = $value['body'];
        }

        foreach ( array_keys( $placeholders ) as $placeholder ) {
            if ( ! isset( $replacements[ $placeholder ] ) )
                continue;

            $subject = str_replace( '[' . $placeholder . ']', $replacements[ $placeholder ], $subject );
            $body = str_replace( '[' . $placeholder . ']', $replacements[ $placeholder ], $body );
        }
    } elseif ( $file ) {
        $body = wpbdp_render_page( $file, $replacements );
    }

    $email = new WPBDP_Email();

    if ( $subject )
        $email->subject = $subject;

    $email->body = $body;

    return $email;
}

function wpbdp_admin_pointer( $selector, $title, $content_ = '',
                              $primary_button = false, $primary_action = '',
                              $secondary_button = false, $secondary_action = '',
                              $options = array() ) {
    if ( ! current_user_can( 'administrator' ) || ( get_bloginfo( 'version' ) < '3.3' ) )
        return;

    $content  = '';
    $content .= '<h3>' . $title . '</h3>';
    $content .= '<p>' . $content_ . '</p>';
?>
<script type="text/javascript">
//<![CDATA[
jQuery(function( $ ) {
        var wpbdp_pointer = $( '<?php echo $selector; ?>' ).pointer({
            'content': <?php echo json_encode( $content ); ?>,
            'position': { 'edge': '<?php echo isset( $options['edge'] ) ? $options['edge'] : 'top'; ?>',
                          'align': '<?php echo isset( $options['align'] ) ? $options['align'] : 'center'; ?>' },
            'buttons': function( e, t ) {
                <?php if ( ! $secondary_button ): ?>
                var b = $( '<a id="wpbdp-pointer-b1" class="button-primary">' + '<?php echo $primary_button; ?>' + '</a>' );
                <?php else: ?>
                var b = $( '<a id="wpbdp-pointer-b2" class="button-secondary" style="margin-right: 15px;">' + '<?php echo $secondary_button; ?>' + '</a>' );
                <?php endif; ?>
                return b;
            }
        }).pointer('open');

        <?php if ( $secondary_button ): ?>
        $( '#wpbdp-pointer-b2' ).before( '<a id="wpbdp-pointer-b1" class="button-primary">' + '<?php echo $primary_button; ?>' + '</a>' );
        $( '#wpbdp-pointer-b2' ).click(function(e) {
            e.preventDefault();
            <?php if ( $secondary_action ): ?>
            <?php echo $secondary_action; ?>
            <?php endif; ?>
            wpbdp_pointer.pointer( 'close' );
        });
        <?php endif; ?>

        $( '#wpbdp-pointer-b1' ).click(function(e) {
            e.preventDefault();
            <?php if ( $primary_action ): ?>
            <?php echo $primary_action; ?>
            <?php endif; ?>
            wpbdp_pointer.pointer( 'close' );
        });

});
//]]>
</script>
<?php
}

/**
 * No op object used to prevent modules from breaking a site while performing a manual upgrade
 * or something similar.
 * Instances of this class allow accessing any property or calling any function without side effects (errors).
 *
 * @since 3.4dev
 */
class WPBDP_NoopObject {

    public function __construct() {
    }

    public function __set( $k, $v ) { }
    public function __get( $k ) { return null; }
    public function __isset( $k ) { return false; }
    public function __unset( $k ) { }

    public function __call( $name, $args = array() ) {
        return false;
    }

}

// For compat with PHP < 5.3
if ( ! function_exists( 'str_getcsv' ) ) {
    function str_getcsv( $input, $delimiter = ',', $enclosure = '"', $escape = "\\" ) {
        $f = tmpfile();
        fwrite( $f, $input );
        fseek( $f, 0 );

        $res = fgetcsv( $f, 0, $delimiter, $enclosure );

        fclose( $f );

        return $res;
    }
}

/**
 * @since 4.0.5dev
 */
function wpbdp_detect_encoding( $content ) {
    static $encodings = array(
        'UTF-8', 'ASCII',
        'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5',
        'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10',
        'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16',
        'Windows-1251', 'Windows-1252', 'Windows-1254',
    );

    if ( function_exists( 'mb_detect_encoding' ) ) {
        return mb_detect_encoding( $content, $encodings, true );
    } else {
        if ( ! function_exists( 'iconv' ) )
            return 'UTF-8';
        else
            return wpbdp_mb_detect_encoding( $content, $encodings );
    }
}

/**
 * Taken from http://php.net/manual/en/function.mb-detect-encoding.php#113983
 * @since 4.0.5dev
 */
function wpbdp_mb_detect_encoding( $content, $encodings ) {
   foreach ( $encodings as $encoding ) {
        $sample = iconv( $encoding, $encoding, $string );
        if ( md5( $sample ) == md5( $string ) ) {
            return $encoding;
        }
    }

    return false;
}

function wpbdp_render_user_field( $args = array() ) {
    $args = wp_parse_args( $args, array(
        'class' => '',
        'name' => 'user',
        'value' => null,
    ) );

    $users_query = new WP_User_Query( array( 'count_total' => true, 'fields' => 'ID', 'number' => 200 ) );

    if ( $users_query->get_total() <= 200 ) {
        $output = '<select class="' . esc_attr( $args['class'] ) . '" name="' . esc_attr( $args['name'] ) . '">';

        foreach ( get_users( 'orderby=display_name' ) as $user ) {
            $selected = $args['value'] == $user->ID ? ' selected="selected"' : '';

            $output .= '<option value="' . $user->ID . '"' . $selected . '>';
            $output .= "{$user->display_name} ({$user->user_login})";
            $output .= '</option>';
        }

        $output .= '</select>';
    } else {
        if ( $args['value'] ) {
            $user = get_user_by( 'ID', $args['value'] );
            $text_value = "{$user->display_name} ({$user->user_login})";
            $hidden_value = $user->ID;
        } else {
            $text_value = '';
            $hidden_value = 0;
        }

        $hidden_field_id = 'autocomplete-value-' . uniqid();

        $output = '<input class="wpbdp-user-autocomplete ' . esc_attr( $args['class'] ) . '" type="text" value="' . esc_attr( $text_value ) . '" data-hidden-field="' . $hidden_field_id . '" />';
        $output.= '<input id="' . $hidden_field_id . '" name="' . esc_attr( $args['name'] ) . '" type="hidden" value="' . esc_attr( $hidden_value ) . '">';
    }

    return $output;
}
