<?php
require_once ( WPBDP_PATH . 'core/class-db-query-set.php' );
require_once ( WPBDP_PATH . 'core/class-db-entity.php' );

class WPBDP__DB__Model {

    protected $adding = true;
    protected $attrs = array();
    protected $dirty = array();


    public function __construct( $fields ) {
        $model_info = self::get_model_info( $this );

        foreach ( $fields as $f => $v ) {
            if ( in_array( $f, $model_info['serialized'], true ) )
                $v = maybe_unserialize( $v );

            $this->set_attr( $f, $v );
        }
    }

    protected function is_valid_attr( $name ) {
        $info = self::get_model_info( $this );
        $db_columns = array_keys( $info['table']['columns'] );

        if ( in_array( $name, $db_columns, true ) )
            return true;

        if ( method_exists( $this, 'get_' . $name ) || method_exists( $this, 'set_' . $name ) )
            return true;

        return false;
    }

    protected function set_attr( $name, $value ) {
        if ( ! $this->is_valid_attr( $name ) )
            return false;

        if ( isset( $this->attrs[ $name ] ) && $value == $this->attrs[ $name ] )
            return;

        $this->attrs[ $name ] = $value;

        if ( ! in_array( $name, $this->dirty, true ) )
            $this->dirty[] = $name;
    }

    public function __get( $name ) {
        if ( ! $this->is_valid_attr( $name ) )
            throw new Exception( 'Invalid attribute: ' . $name );

        if ( method_exists( $this, 'get_' . $name ) )
            return call_user_func( array( $this, 'get_' . $name ) );

        if ( ! array_key_exists( $name, $this->attrs ) )
            return null;

        $value = $this->attrs[ $name ];
        return $value;
    }

    public static function objects() {
        throw new Exception('Method not overridden in subclass!');
    }

    public static function _objects( $classname ) {
        static $managers_per_class = array();

        if ( ! isset( $managers_per_class[ $classname ] ) )
            $managers_per_class[ $classname ] = new WPBDP__DB__Query_Set( $classname, false, true );

        return $managers_per_class[ $classname ];
    }

    public static function from_db( $fields, $classname ) {
        $obj = new $classname( $fields );
        $obj->adding = false;

        return $obj;
    }

    public static function get_model_info( $classname ) {
        global $wpdb;
        static $cache = array();

        if ( is_object( $classname ) )
            $classname = get_class( $classname );

        if ( isset( $cache[ $classname ] ) )
            return $cache[ $classname ];

        $cls_vars = get_class_vars( $classname );

        $info                = array();
        $info['class']       = $classname;
        $info['table']       = array( 'name' => isset( $cls_vars['table'] ) ? $wpdb->prefix . $cls_vars['table'] : $wpdb->prefix . strtolower( $classname ) . 's',
                                      'columns' => array() );
        $info['primary_key'] = isset( $cls_vars['primary_key'] ) ? $cls_vars['primary_key'] : 'id';
        $info['serialized']  = isset( $cls_vars['serialized'] ) ? $cls_vars['serialized'] : array();

        foreach ( $wpdb->get_results( "SHOW COLUMNS FROM " . $info['table']['name'], ARRAY_A ) as $col ) {
            $info['table']['columns'][ $col['Field'] ] = array( 'type'       => $col['Type'],
                                                                'nullable'   => ( 'yes' == strtolower( $col['Null'] ) ),
                                                                'default'    => $col['Default'],
                                                                'serialized' => in_array( $col['Field'], $info['serialized'], true ) );
        }

        return $info;
    }

}

// For backwards-compat.
require_once( WPBDP_PATH . 'core/compatibility/class-db-model2.php' );


