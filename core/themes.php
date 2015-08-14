<?php
/**
 * @since 4.0
 */
class WPBDP_Themes {

    private $themes = array();
    private $template_dirs = array();
    private $cache = array( 'templates' => array(),
                            'rendered' => array() );
    private $template_areas = array();


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
            $dirs = wpbdp_scandir( $path, array( 'filter' => 'dir' ) );

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
    function get_active_theme_data() {
        $active = $this->get_active_theme();
        return $this->themes[ $active ];
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
            array( 'sections', 'array', array() )
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

        $defaults = array(
            '_id' => str_replace( array( '.tpl.php', ' ', '-page', 'page-', 'page' ),
                                  array( '', '-', '', '', '' ),
                                  $id_or_file ),
            '_template' => $id_or_file,
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
        $vars = apply_filters( 'wpbdp_render_vars', $vars, $id_or_file, $path );
        $vars = apply_filters( 'wpbdp_render_' . $id_or_file . '_vars', $vars, $path );
        $vars['_vars'] = $vars;

        $current_theme = $this->get_active_theme_data();
        $theme_handled_items = ( isset ( $current_theme->sections->{$id_or_file} ) ) ? $current_theme->sections->{$id_or_file} : array();
        $areas = $this->_template_areas( $id_or_file, $vars );

        foreach ( $areas as $a => $items ) {
            $vars[ 'area_' . $a ] = '';

            foreach ( $items as $section_id => $item ) { 
                // Theme-handled items are removed from the area because they are manually shown somewhere by the theme.
                // Instead, they are added as regular variables.
                if ( in_array( $section_id, $theme_handled_items, true ) ) {
                    $vars[ $section_id ] = $item;
                    continue;
                }

                $vars[ 'area_' . $a ] .= $item;
            }
        }

        if ( $vars )
            extract( $vars );

        ob_start();
        include( $path );
        $html = ob_get_contents();
        ob_end_clean();

        if ( $vars['_full'] ) {
            $vars['_full'] = false; // Stop recursion.
            $html = $this->render( 'page',
                                   array_merge( $vars, array( 'page' => $html ) ) );
        } else {
            // Add before/after to the HTML directly.
            $html = $vars['area_before'] .
                    $vars['area_before_inner'] .
                    $html .
                    $vars['area_after_inner'] .
                    $vars['area_after'];
        }

        return $html;
    }

    function _template_areas( $id_or_file, $vars ) {
        $registered = isset( $this->template_areas[ $id_or_file ] ) ? $this->template_areas[ $id_or_file ] : array();
        $areas = array_unique( array_merge( array_keys( $registered ),
                               array( 'before', 'before_inner', 'after_inner', 'after' ) ) );

        $items = array();
        foreach ( $areas as $a ) {
            $area_content = array();

            if ( ! empty( $registered[ $a ] ) ) {
                // Sort registered areas/sections by priority.
                ksort( $registered[ $a ], SORT_NUMERIC );

                foreach ( $registered[ $a ] as $prio => $sections ) {
                    foreach ( $sections as $section_id => $callback_or_template ) {
                        ob_start();

                        if ( 'single' == $id_or_file ) {
                            call_user_func( $callback_or_template, $vars['listing_id'], $vars );
                        } else {
                            call_user_func( $callback_or_template, $vars );
                        }
                        $output = ob_get_clean();

                        $area_content = array_merge( $area_content, array( $section_id => $output ) );
                    }
                }
           }

            $area_content['inline']  = '';
            $area_content['inline'] .= wpbdp_capture_action( 'wpbdp_template_' . $a, $vars['_id'], $vars['_template'], $vars );
            $area_content['inline'] .= wpbdp_capture_action( 'wpbdp_template_' . $vars['_id'] . '_' . $a, $vars );

            $items[ $a ] = $area_content;
        }

        return $items;
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

    function register_template_section( $template_id, $template_area, $section_id, $callback, $priority = 10 ) {
        $priority = absint( $priority );

        if ( ! isset( $this->template_areas[ $template_id ] ) )
            $this->template_areas[ $template_id ] = array();

        if ( ! isset( $this->template_areas[ $template_id ][ $template_area ] ) )
            $this->template_areas[ $template_id ][ $template_area ] = array();

        if ( ! isset( $this->template_areas[ $template_id ][ $template_area ][ $priority ] ) )
            $this->template_areas[ $template_id ][ $template_area ][ $priority ] = array();

        $this->template_areas[ $template_id ][ $template_area ][ $priority ][ $section_id ] = $callback;
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

}

function wpbdp_x_render( $id_or_file, $vars = array() ) {
    global $wpbdp;
    return $wpbdp->themes->render( $id_or_file, $vars );
}

function wpbdp_add_template_dir( $dir_or_file ) {
    global $wpbdp;
    return $wpbdp->themes->add_template_dir( $dir_or_file );
}


function wpbdp_register_template_section( $template_id, $template_area, $section_id, $callback, $priority = 10 ) {
    global $wpbdp;
    return $wpbdp->themes->register_template_section( $template_id, $template_area, $section_id, $callback, $priority );
}
