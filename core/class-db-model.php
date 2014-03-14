<?php
abstract class WPBDP_DB_Model {
    
    protected $id = null;

    public function set( $data = array() ) {
        throw new Exception( 'Not implemented yet!' );
    }

    abstract public function save();
    abstract public function delete();

    public function validates() {
        return true;
    }
    
    public function get_id() {
        return $this->id;
    }

    protected function fill_from_data( &$data = array(), $defaults = array() ) {
        $data = (array) $data;
        $keys = array_unique( array_merge( array_keys( $data ), array_keys( $defaults ) ) );
        
        foreach ( $keys as $k ) {
            $v = isset( $data[ $k ] ) ? maybe_unserialize( $data[ $k ] ) : null;
            
            if ( $v )
                $this->{$k} = $v;
            elseif( isset( $defaults [ $k ] ) )
                $this->{$k} = $defaults[ $k ];
        }
    }

    /**
     * Convenience method to obtain a record from the database by ID.
     * Subclasses should override this method because we have to support PHP 5.2 where late static binding is not available.
     * @param int $id The row ID.
     * @return object
     */
    public static function get( $id ) {
        throw new Exception('get() method not implemented.');
    }

    /**
     * Convenience method to search records in a database table.
     * Subclasses should override this method because we have to support PHP 5.2 where late static binding is not available.
     * @return array
     */
    public static function find( $args = array(), $lightweight = false ) {
        throw new Exception('find() method not implemented.');
    }

    protected static function _find( $args = array(), $lightweight = false, $table, $classname = '' ) {
        if ( ! $table || ! $classname || ! class_exists( $classname ) )
            throw new Exception( 'Please provide a table and class name.' );

        global $wpdb;
        
        $query = "SELECT * FROM {$table} WHERE 1=1";
        $single = isset( $args['_single'] ) && true == $args['_single'];
        $order = isset( $args['_order'] ) && !empty( $args['_order'] ) ? trim( $args['_order'] ) : null;
        $limit = isset( $args['_limit'] ) && !empty( $args['_limit'] ) ? intval( $args['_limit'] ) : null;
        
        foreach ( $args as $arg => $value ) {
            if ( is_null( $value ) || in_array( $arg, array( '_single', '_order', '_limit' ), true ) )
                continue;
            
            $query .= $wpdb->prepare( " AND {$arg}=" . ( is_int( $value ) ? '%d' : '%s' ), $value );
        }

        if ( $single )
            $limit = 1;

        if ( $order ) {
            $order_field = wpbdp_starts_with( $order, '-' ) ? substr( $order, 1 ) : $order;
            $order_dir = wpbdp_starts_with( $order, '-' ) ? 'DESC' : 'ASC';

            $query .= " ORDER BY {$order_field} {$order_dir}";
        }

        if ( $limit > 0 )
            $query .= " LIMIT {$limit}";

        $results = $wpdb->get_results( $query, ARRAY_A );
        
        if ( ! $lightweight ) {
            foreach ( $results as &$r ) {
                $r = new $classname( $r );
            }
        }
        
        return $single ? ( $results ? $results[0] : null ) : $results;
    }

    protected static function _get( $id, $table, $classname = '' ) {
        return self::_find( array( 'id' => $id, '_single' => true ), false, $table, $classname );
    }

}


class WPBDP_DB_Model2 {

    public $errors = array();

    protected $table = '';
    protected $serialized = array();

    private $attrs = array();


    public function __construct( $data = array() ) {
        $this->fill( $data );
    }

    public function fill( $data = array() ) {
        foreach ( $data as $k => $v ) {
            $this->attrs[ $k ] = ( in_array( $k, $this->serialized, true) && $v ) ? maybe_unserialize( $v ) : $v;
        }
    }

    public function save( $validate = true ) {
        global $wpdb;

        if ( isset( $this->attrs['id'] ) && $this->attrs['id'] )
            return $this->update( $validate );
        else
            return $this->insert( $validate );
    }
    
    private function validate() {
        $this->errors = $this->_validate();
        return empty( $this->errors ) ? true : false;
    }
    
    protected function _validate() {
        return array();
    }
    
    public function is_valid() {
        return $this->validate();
    }
    
    public function is_invalid() {
        return ! $this->is_valid();
    }

    private function insert( $validate = true ) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpbdp_' . $this->table;
        
        if ( $validate && ! $this->validate() )
            return false;

        $row = array();
        foreach ( $this->attrs as $k => $v )
            $row[ $k ] = in_array( $k, $this->serialized, true ) ? ( $v ? serialize( $v ) : '' ): $v;

        if ( false !== $wpdb->insert( $table, $row ) ) {
            $this->attrs['id'] = intval( $wpdb->insert_id );
            return true;
        }

        return false;
    }

    private function update( $validate = true ) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpbdp_' . $this->table;

        $row = array();
        foreach ( $this->attrs as $k => $v )
            $row[ $k ] = in_array( $k, $this->serialized, true ) ? ( $v ? serialize( $v ) : '' ): $v;

        // if ( $validate )
        //     $this->validate();

        return false !== $wpdb->update( $table, $row, array( 'id' => $this->id ) );
    }

    public function &get_attr( $k ) {
        if ( array_key_exists( $k, $this->attrs ) ) {
            $value = $this->attrs[ $k ];
        } else {
            $value = null;
        }

        return $value;
    }

    public function set_attr( $k, $v ) {
        $this->attrs[ $k ] = $v;
    }

    public function &__get( $k ) {
        if ( method_exists( $this, "get_$k" ) ) {
            $v = call_user_func( array( &$this, "get_$k" ) );
            return $v;
        }

        return $this->get_attr( $k );
    }

    public function __set( $k, $v ) {
        if ( method_exists( $this, "set_$k" ) ) {
            return call_user_func( array( &$this, "set_$k" ), $v );
        }

        // if ( array_key_exists( $k, $this->attrs ) )
        return $this->set_attr( $k, $v );

        // throw new Exception( 'Undefined Property: ' . $k );
    }

    /**
     * Convenience method to search records in a database table.
     * Subclasses should override this method because we have to support PHP 5.2 where late static binding is not available.
     * @return array
     */
    public static function find( $id, $args = array() ) {
        throw new Exception('find() method not implemented.');
    }

    protected static function _find( $id, $args = array(), $table = '', $classname = '' ) {
        if ( ! $table || ! $classname || ! class_exists( $classname ) )
            throw new Exception( 'Please provide a table and class name.' );

        global $wpdb;
        
        $single = false;

        $query = "SELECT * FROM {$table} WHERE 1=1";

        switch ( $id ) {
            case 'first':
                $args['_limit'] = 1;
                $args['_order'] = 'id';
                $single = true;

                break;
            case 'last':
                $args['_limit'] = 1;
                $args['_order'] = '-id';
                $single = true;

                break;
            case 'all':
                break;
            default:
                $args['id'] = intval( $id );
                $args['_limit'] = 1;
                $single = true;

                break;
        }

        $single = (  ! $single && isset( $args['_single'] ) && true == $args['_single'] ) ? true : $single;
        $order = isset( $args['_order'] ) ? $args['_order'] : '';
        $limit = isset( $args['_limit'] ) ? $args['_limit'] : '';

        foreach ( $args as $arg => $value ) {
            if ( is_null( $value ) || in_array( $arg, array( '_single', '_order', '_limit' ), true ) )
                continue;

            if ( is_array( $value ) ) {
                $value_str = implode( ',', $value );
                $query .= " AND {$arg} IN ({$value_str})";
            } elseif ( $value[0] == '>' ) {
                $query .= " AND {$arg} {$value}";
            } else {
                $query .= $wpdb->prepare( " AND {$arg}=" . ( is_int( $value ) ? '%d' : '%s' ), $value );    
            }
        }

        if ( $order ) {
            $order_field = wpbdp_starts_with( $order, '-' ) ? substr( $order, 1 ) : $order;
            $order_dir = wpbdp_starts_with( $order, '-' ) ? 'DESC' : 'ASC';

            $query .= " ORDER BY {$order_field} {$order_dir}";
        }

        if ( $limit > 0 )
            $query .= " LIMIT {$limit}";

        if ( $single ) {
            if ( $row = $wpdb->get_row( $query, ARRAY_A ) )
                return new $classname( $row );
            else
                return null;
        }

        return array_map( create_function( '$x', 'return new ' . $classname . '( $x );' ), $wpdb->get_results( $query, ARRAY_A ) );
    }    

}