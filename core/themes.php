<?php
/**
 * @since 4.0
 */
class WPBDP_Themes {

    private $themes = array();
    private $template_dirs = array();
    private $cache = array( 'templates' => array(),
                            'rendered' => array() );


    function __construct() {
        if ( is_admin() ) {
            require_once( WPBDP_PATH . 'admin/class-themes-admin.php' );
            $this->admin = new WPBDP_Themes_Admin( $this );
        }

        // Theme template dir is priority 1.
        $theme = $this->get_active_theme_data();
        $this->template_dirs[] = $theme->path . 'templates/';

        // Core templates are last priority.
        $this->template_dirs[] = trailingslashit( WPBDP_PATH . 'core/templates' );

        // Load special theme .php file.
        $this->call_theme_function('');
        $this->call_theme_function('init');

        add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_theme_scripts' ), 20 );

        add_shortcode( 'businessdirectory-theme-test', array( &$this, 'theme_test' ) );
    }

    function call_theme_function( $fname, $args = array() ) {
        $theme = $this->get_active_theme_data();

        // If no function name is provided, just load the file.
        if ( ! $fname && file_exists( $theme->path . 'theme.php' ) )
            include_once( $theme->path . 'theme.php' );

        $theme_name = str_replace( array( '-' ), array( '_' ), $theme->id );

        if ( function_exists( $theme_name . '_' . $fname ) )
            call_user_func_array( $theme_name . '_' . $fname, $args );
    }

    function enqueue_theme_scripts() {
        $theme = $this->get_active_theme_data();
        $css = $theme->assets->css;
        $js = $theme->assets->js;

        foreach ( $css as $c ) {
            wp_enqueue_style( $theme->id . '-' . $this->_normalize_asset_name( $c ),
                              $theme->url . 'assets/' . $c );
        }

        foreach ( $js as $j ) {
            wp_enqueue_script( $theme->id . '-' . $this->_normalize_asset_name( $j ),
                               $theme->url . 'assets/' . $j );
        }

        $this->call_theme_function( 'enqueue_scripts' );
    }

    function _normalize_asset_name( $a ) {
        $a = strtolower( $a );
        $a = str_replace( ' ', '_', $a );
        $a = str_replace( '.css', '', $a );
        return $a;
    }

    function get_themes_directories() {
        $res = array();

        $res[ WPBDP_PATH . 'themes/' ] = WPBDP_URL . 'themes/';
        $res[ WP_CONTENT_DIR . '/businessdirectory-themes/' ] = WP_CONTENT_URL . '/businessdirectory-themes/';

        return $res;
    }

    /**
     * Scans all theme directories to find themes and returns information about them.
     * Subsequent calls to this function use an internal cache to avoid unnecessary I/O.
     * @return array An array of theme objects.
     */
    function get_installed_themes() {
        // Use cached info if available.
        if ( ! empty( $this->themes ) )
            return $this->themes;

        $themes = array();

        foreach ( $this->get_themes_directories() as $path => $url ) {
            $dirs = WPBDP_FS::ls( $path, 'filter=dir' );

            if ( ! $dirs )
                continue;

            foreach ( $dirs as $d ) {
                $info = $this->_get_theme_info( $d );

                if ( ! $info )
                    continue;

                $themes[ $info->id ] = $info;
            }
        }

        $this->themes = $themes;
        return $themes;
    }

    /**
     * Changes the active theme.
     * @param string $theme_id
     * @return boolean True if theme was changed successfully, False otherwise.
     */
    function set_active_theme( $theme_id = '' ) {
        if ( ! $theme_id )
            return false;

        $themes = $this->get_installed_themes();
        if ( ! isset( $themes[ $theme_id ] ) )
            return false;

        if ( $theme_id == $this->get_active_theme() )
            return true;

        return update_option( 'wpbdp-active-theme', $theme_id );
    }

    /**
     * Retrieves the ID for the current active theme.
     * @return string
     */
    function get_active_theme() { 
        $active = get_option( 'wpbdp-active-theme', 'default' );
        $themes = $this->get_installed_themes();

        if ( ! isset( $themes[ $active ] ) )
            return 'default';

        return $active;
    }

    /**
     * Retrieves theme information for the current active theme.
     * @return object
     */
    function get_active_theme_data( $key = null ) {
        $active = $this->get_active_theme();
        $data = $this->themes[ $active ];

        if ( ! is_null( $key ) )
            return isset( $data->{$key} ) ? $data->{$key} : false;

        return $data;
    }

    /**
     * @since next-release
     */
    function missing_suggested_fields( $key = '' ) {
        global $wpbdp;
        global $wpdb;

        $key = ( ! $key ) ? 'tag' : $key;

        $missing = array();
        $suggested_fields = $this->get_active_theme_data( 'suggested_fields' );
        $current_fields_tags = $wpdb->get_col( "SELECT tag FROM {$wpdb->prefix}wpbdp_form_fields" );

        $missing_tags = array_diff( $suggested_fields, $current_fields_tags );

        foreach ( $missing_tags as $mt ) {
            $info = $wpbdp->formfields->get_default_fields( $mt );

            if ( ! $info )
                continue;

            $missing[] = $info[ $key ];
        }

        return $missing;
    }

    function _get_theme_info( $d ) {
        $d = trailingslashit( $d );

        $manifest_file = $d . 'theme.json';

        if ( ! is_readable( $manifest_file ) )
            return false;

        $manifest = (array) json_decode( file_get_contents( $manifest_file ) );
        if ( ! $manifest )
            return false;

        $theme_keys = array(
            array( 'id', 'string', basename( $d ) ),
            array( 'name', 'string', basename( $d ) ),
            array( 'description', 'string', '' ),
            array( 'version', 'int', 0 ),
            array( 'author', 'string', '' ),
            array( 'author_email', 'email', '' ),
            array( 'author_url', 'url', '' ),
            array( 'requires', 'string', '4.0dev' ),
            array( 'assets', 'array', array( 'css' => null, 'js' => null ), array( 'allow_other_keys' => false ) ),
            array( 'template_variables', 'array', array() ),
            array( 'suggested_fields', 'array', array() )
/*            array( 'assets/css', 'array/string', array() ),
            array( 'assets/js', 'array/string', array() )*/
        );

        $info = new StdClass();

        foreach ( $theme_keys as $i ) {
            list( $k, $type, $default ) = $i;
            $value = isset( $manifest[ $k ] ) ? $manifest[ $k ] : $default;

            switch ( $type ) {
                case 'string':
                case 'email':
                case 'url':
                    $value = is_string( $value ) ? $value : null;
                    break;

                case 'int':
                    $value = is_numeric( $value ) ? intval( $value ) : null;
                    break;

                case 'array':
                    break;

                default:
                    $value = null;
                    break;
            }

            if ( is_null( $value ) )
                continue;

            $info->{$k} = $value;
        }

        $info->path = $d;

        if ( ! $this->_guess_theme_path_info( $info ) )
            return false;

        return $info;
    }

    function _guess_theme_path_info( &$theme ) {
        $valid_parents = $this->get_themes_directories();
        $url = '';

        foreach ( $valid_parents as $p => $u ) {
            if ( false === stripos( $theme->path, $p ) )
                continue;

            $url = str_replace( $p, $u, $theme->path );
        }

        $theme->url = $url;
        $theme->thumbnail = is_readable( $theme->path . 'thumbnail.png' ) ? $theme->url . 'thumbnail.png' : '';

        return ! empty( $url );
    }

    function add_template_dir( $dir_or_file ) {
        if ( ! is_dir( $dir_or_file ) )
            return false;

        $path = trailingslashit( $dir_or_file );

        if ( in_array( $path, $this->template_dirs, true ) )
            return true;

        $last = array_pop( $this->template_dirs );
        $this->template_dirs[] = $path;
        $this->template_dirs[] = $last;

        return true;
    }

    function render( $id_or_file, $vars = array() ) {
        if ( file_exists( $id_or_file ) )
            return 'RENDER AS FILE';

        $path = $this->locate_template( $id_or_file );
        if ( ! $path )
            throw new Exception( 'Invalid template id or file: "' . $id_or_file . '"' );

        if ( ! isset( $vars['_in_wrapper'] ) ) {
            // Setup default and hook-added variables.
            $this->_configure_template_vars( $id_or_file, $path, $vars );

            // Process variables using templates or callbacks.
            $this->_process_template_vars( $vars );

            // Configure blocks depending on theme overrides.
            $this->_configure_template_blocks( $vars );
        }

        extract( $vars );

        ob_start();
        include( $path );
        $html = ob_get_contents();
        ob_end_clean();

        $template_meta = ( isset( $__template__ ) && is_array( $__template__ ) ) ? $__template__ : array();
        $template_blocks = ! empty( $template_meta['blocks'] ) ? $template_meta['blocks'] : array();

        // Check for wrapper template.
        $wrapper_name = '';

        if ( isset( $vars['_wrapper'] ) && false === $vars['_wrapper'] ) {
            // Do not use a wrapper.
        } else {
            if ( isset( $vars['_wrapper'] ) )
                $wrapper_name = $vars['_wrapper'];

            if ( ! $wrapper_name && ! empty( $template_meta['wrapper'] ) )
                $wrapper_name = $template_meta['wrapper'];

            if ( ! $wrapper_name )
                $wrapper_name = $id_or_file . '_wrapper';
        }

        $wrapper = $wrapper_name ? $this->locate_template( $wrapper_name ) : false;

        // Add before/after to the HTML directly.
        $html = ( in_array( 'before', $template_blocks, true ) ? '' : ( ! empty( $vars['blocks']['before'] ) ? $vars['blocks']['before'] : '' ) ) .
                ( in_array( 'before_inner', $template_blocks, true ) ? '' : ( ! empty( $vars['blocks']['before_inner'] ) ? $vars['blocks']['before_inner'] : '' ) ) .
                $html .
                ( in_array( 'after_inner', $template_blocks, true ) ? '' : ( ! empty( $vars['blocks']['after_inner'] ) ? $vars['blocks']['after_inner'] : '' ) ) .
                ( in_array( 'after', $template_blocks, true ) ? '' : ( ! empty( $vars['blocks']['after'] ) ? $vars['blocks']['after'] : '' ) );

        if ( $wrapper ) {
            $vars['_wrapper'] = false; // Stop recursion.
            $vars['_in_wrapper'] = true;

            $wrapper_vars = array_merge( $vars, array( 'content' => $html ) );
            unset( $wrapper_vars['blocks'] );

            $html = $this->render( $wrapper_name,
                                   $wrapper_vars );
        } else {
        }

        return $html;
    }

    function _configure_template_vars ( $id_or_file, $path, &$vars ) {
        $defaults = array(
            '_id' => str_replace( array( '.tpl.php', ' ', '-page', 'page-', 'page' ),
                                  array( '', '-', '', '', '' ),
                                  $id_or_file ),
            '_template' => $id_or_file,
            '_path' => $path,
            '_view' => null,
            '_full' => false,
            '_bar' => false,
            '_bar_items' => array( 'links', 'search' ),
            '_class' => '',
            '_inner_class' => ''
        );

        if ( isset( $vars['_full'] ) && $vars['_full'] && ! array_key_exists( '_bar', $vars ) )
            $defaults['_bar'] = true;

        $vars = array_merge( $defaults, $vars );
        $vars = apply_filters( 'wpbdp_template_variables', $vars, $id_or_file );
        $vars = apply_filters( 'wpbdp_template_variables__' . $id_or_file, $vars, $path );
    }

    function _process_template_vars( &$vars ) {
        foreach ( $vars as $k => $v ) {
            if ( '#' != $k[0] )
                continue;

            $k_ = substr( $k, 1 );

            if ( ! is_array( $v ) || ! array_key_exists( 'position', $v ) ) {
                $vars[ $k_ ] = $v;
                unset( $vars[ $k ] );
            }

            $vars[ $k ]['weight'] = isset( $v['weight'] ) ? intval( $v['weight'] ) : 10;

            if ( array_key_exists( 'value', $v ) )
                continue;

            if ( array_key_exists( 'callback', $v ) ) {
                $vars[ $k ]['value'] = call_user_func_array( $v['callback'], $vars ); // TODO: support 'echo'ed output too. 
                unset( $vars[ $k ]['callback'] );
            }
        }
    }

    function _configure_template_blocks( &$vars ) {
        $template_id = $vars['_template'];

        // FUTURE: Maybe support new blocks per template?
        $blocks = array( 'after' => array(), 'before' => array(), 'before_inner' => array(), 'after_inner' => array() );
        $vars['blocks'] = array();

        // Current theme info.
        $current_theme = $this->get_active_theme_data();
        $theme_vars = ( isset ( $current_theme->template_variables->{$template_id} ) ) ? $current_theme->template_variables->{$template_id} : array();

        foreach ( $vars as $var => $content ) {
            if ( '#' != $var[0] )
                continue;

            $new_key = substr( $var, 1 );
            $var_position = $content['position'];
            $var_value = $content['value'];
            $var_weight = $content['weight'];

            if ( ! in_array( $new_key, $theme_vars, true ) ) {
                if ( isset( $blocks[ $var_position ] ) ) {
                    if ( ! isset( $blocks[ $var_position ][ $var_weight ] ) )
                        $blocks[ $var_position ][ $var_weight ] = array();

                    $blocks[ $var_position ][ $var_weight ][ $new_key ] = $var_value;
                } else {
                    $vars[ $new_key ] = $var_value;
                }
            } else {
                $vars[ $new_key ] = $var_value;
            }

            unset( $vars[ $var ] );
        }

        // Sort blocks.
        foreach ( $blocks as $block_id => &$block_content ) {
            $vars['blocks'][ $block_id ] = '';

            if ( ! $block_content )
                continue;

            ksort( $block_content, SORT_NUMERIC );

            foreach ( $block_content as $prio => $c )
                $vars['blocks'][ $block_id ] .= implode( '', $c );
        }
    }

    function locate_template( $id ) {
        $id = str_replace( '.tpl.php', '', $id );

        if ( isset( $this->cache['templates'][ $id ] ) )
            return $this->cache['templates'][ $id ];

        $filename = str_replace( ' ', '-', $id ) . '.tpl.php';
        $path = false;

        // Find the template.
        foreach ( $this->template_dirs as $p ) {
            if ( file_exists( $p . $filename ) ) {
                $path = $p . $filename;
                break;
            }
        }

        if ( $path )
            $this->cache['templates'][ $id ] = $path;

        return $path;
    }

    function install_theme( $file ) {
        $themes_dir = wp_normalize_path( WP_CONTENT_DIR . '/businessdirectory-themes/' ); // TODO: do not hardcode this directory.
        list( $temp_dir, $unzipped_dir, $package_name ) = WPBDP_FS::unzip_to_temp_dir( $file );

        if ( ! file_exists( WPBDP_FS::join( $unzipped_dir, 'theme.json' ) ) ) {
            WPBDP_FS::rmdir( $temp_dir );
            return new WP_Error( 'no-theme-file',
                                 _x( 'ZIP file is not a valid BD theme file.', 'themes', 'WPBDM' ) );
        }

        if ( ! WPBDP_FS::mkdir( $themes_dir ) ) {
            WPBDP_FS::rmdir( $temp_dir );
            return new WP_Error( 'no-themes-directory',
                                 _x( 'Could not create themes directory.', 'themes', 'WPBDM' ) );
        }

        $dest_dir = $themes_dir . $package_name;

        if ( ! WPBDP_FS::rmdir( $dest_dir ) ) {
            WPBDP_FS::rmdir( $temp_dir );
            return new WP_Error( 'old-theme-not-removed',
                                 sprintf( _x( 'Could not remove previous theme directory "%s".', 'themes', 'WPBDM' ), 
                                          $dest_dir ) );
        }

        if ( ! WPBDP_FS::movedir( $unzipped_dir, $themes_dir ) ) {
            WPBDP_FS::rmdir( $temp_dir );
            return new WP_Error( 'theme-not-copied', _x( 'Could not move new theme into theme directory.', 'themes', 'WPBDM' ) );
        }

        WPBDP_FS::rmdir( $temp_dir );

        return $dest_dir;
    }

    function theme_test_template_vars( $vars ) {
        $vars['module_added_var'] = 2;
        $vars['#constant_before'] = array( 'position' => 'before', 'value' => '!BEFORE!', 'weight' => 11 );
        $vars['#constant_before2'] = array( 'position' => 'before', 'value' => '!BEFORE BUT FIRST!', 'weight' => 9 );
        $vars['#googlemaps'] = array( 'position' => 'after', 'callback' => array( &$this, 'theme_test_googlemap' ) );

        return $vars;
    }

    function theme_test_googlemap( $vars ) {
        return '!MY GOOGLE MAP!';
    }

    function theme_test() {
        add_filter( 'wpbdp_template_variables__theme_test', array( &$this, 'theme_test_template_vars' ) );
        return wpbdp_x_render( 'theme_test', array( 'my_template_var' => 1, 'my_other_template_var' => 0 ) );
    }

}

function wpbdp_x_render( $id_or_file, $vars = array() ) {
    global $wpbdp;
    return $wpbdp->themes->render( $id_or_file, $vars );
}

function wpbdp_add_template_dir( $dir_or_file ) {
    global $wpbdp;
    return $wpbdp->themes->add_template_dir( $dir_or_file );
}

