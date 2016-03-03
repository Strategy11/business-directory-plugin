<?php

/**
 * @since next-release
 */
class WPBDP_Router {

    private $dirs;
    private $rules;


    public function __construct() {
        $this->dirs = array();
        $this->rules = array();
    }

    public function add_view_path( $path ) {
        if ( is_dir( $path ) )
            $this->dirs[] = $path;
    }

    public function add( $regex, $view, $args = array(), $name = '' ) {
        $regex = '/' . trim( $regex, '/' );

        $this->rules[] = array( 'regex' => $regex,
                                'view' => $view, 'args' => $args, 'name' => $name );
    }

    public function resolve( $url = null ) {
        $url = ! $url ? $_SERVER['REQUEST_URI'] : $url;

        $url_parts = wp_parse_url( $url );
        $query_args = array();

        if ( ! empty( $url_parts['query'] ) )
            parse_str( $url_parts['query'], $query_args );

        $candidates = array( 'rule' => array(),
                             'name' => array() );

        foreach ( $this->rules as $rule ) {
            if ( preg_match( "#^" . $rule['regex'] . "$#", $url, $matches ) )
                $candidates[ 'rule' ][] = $rule;

            if ( $rule['name'] && ! empty( $query_args['v'] ) && $query_args['v'] == $rule['name'] )
                $candidates[ 'name' ][] = $rule;
        }

        if ( ! $candidates[ 'rule' ] && ! $candidates['name'] )
            return false;

        $match = false;

        if ( $candidates['rule'] )
            $match = reset( $candidates['rule'] );
        else if ( $candidates['name'] )
            $match = reset( $candidates['name'] );

        return $match;
    }

    public function route( $url = null ) {
        $view_data = $this->resolve( $url );

        if ( ! $view_data )
            return false;

        $view = $view_data[ 'view' ];

        if ( class_exists( $view ) )
            return $view;

        if ( function_exists( $view ) )
            return new WPBDP_Callback_View( $view );

        // Try to find the class.
        $filename = '';
        $classname = '';

        if ( is_array( $view ) && isset( $view[ 'file' ] ) )
            $filename = $view['file'];
        else
            $filename = strtolower( str_replace( array( 'WPBDP_', '_View' ), array( '', '' ), $view ) ) . '.php';

        if ( is_array( $view ) && isset( $view['class'] ) )
            $classname = $view['class'];
        else
            $classname = $view;

        foreach ( $this->dirs as $d ) {
            $path = WPBDP_FS::join( $d, $filename );

            if ( ! file_exists( $path ) )
                continue;

            include_once( $path );

            if ( ! class_exists( $classname ) )
                continue;

            return new $classname( $this );
        }

        return false;
    }


}

// if ( preg_match("#^$match#", $request_match, $matches) ||
//                     preg_match("#^$match#", urldecode($request_match), $matches) ) {
//  
//                     if ( $wp_rewrite->use_verbose_page_rules && preg_match( '/pagename=\$matches\[([0-9]+)\]/', $query, $varmatch ) ) {
//                         // This is a verbose page match, let's check to be sure about it.
