<?php
require_once ( WPBDP_PATH . 'core/class-db-query-set.php' );
require_once ( WPBDP_PATH . 'core/class-db-entity.php' );

class WPBDP__DB__Model {

    protected $_adding = true;
    protected $_attrs = array();
    protected $_dirty = array();


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

        if ( isset( $this->_attrs[ $name ] ) && $value == $this->_attrs[ $name ] )
            return;

        $this->_attrs[ $name ] = $value;

        if ( ! in_array( $name, $this->_dirty, true ) )
            $this->_dirty[] = $name;
    }

    protected function prepare_row() {
        $row = array();

        $model = self::get_model_info( $this );
        $cols = $model['table']['columns'];
        $pk = $model['primary_key'];
        $dirty = $this->_dirty;

        if ( ! $this->_adding )
            $row[ $pk ] = $this->_attrs[ $pk ];

        foreach ( $dirty as $col_name ) {
            if ( ! isset( $cols[ $col_name ] ) )
                continue;

            $col_value = $this->_attrs[ $col_name ];

            if ( $cols[ $col_name ]['serialized'] )
                $col_value = maybe_serialize( $col_value );

            $row[ $col_name ] = $col_value;
        }

        // Update timestamps.
        $time = current_time( 'mysql' );

        if ( isset( $cols['updated_at'] ) )
            $row['updated_at'] = $time;

        if ( $this->_adding && isset( $cols['created_at'] ) )
            $row['created_at'] = $time;

        return $row;
    }

    public function clean( &$errors ) {
    }

    public function &__get( $name ) {
        if ( ! $this->is_valid_attr( $name ) )
            throw new Exception( 'Invalid attribute: ' . $name );

        if ( method_exists( $this, 'get_' . $name ) ) {
            $v = call_user_func( array( $this, 'get_' . $name ) );
            return $v;
        }

        if ( ! isset( $this->_attrs[ $name ] ) ) {
            $v = null;
            return null;
        }

        $value = &$this->_attrs[ $name ];
        return $value;
    }

    public function __set( $name, $value ) {
        if ( ! $this->is_valid_attr( $name ) )
            throw new Exception( 'Invalid attribute: ' . $name );

        if ( method_exists( $this, 'set_' . $name ) )
            return call_user_func( array( $this, 'set_' . $name ) );

        $this->_attrs[ $name ] = $value;
    }


    public function save( $validate = true ) {
        global $wpdb;

        $errors = array();

        if ( $validate )
            $this->clean( $errors );

        if ( $errors )
            throw new Exception('Invalid model instance!');

        $model = self::get_model_info( $this );
        $pk = $model['primary_key'];
        $row = $this->prepare_row();

        if ( $this->_adding )
            $res = $wpdb->insert( $model['table']['name'], $row );
        else
            $res = $wpdb->update( $model['table']['name'], $row, array( $pk => $this->_attrs[ $pk ] ) );

        if ( $this->_adding && $res ) {
            $this->_attrs[ $pk ] = $wpdb->insert_id;
            $this->_adding = false;
        }

        return false !== $res;
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
        $obj->_adding = false;

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


