<?php
/**
 * @since next-release
 */
class WPBDP__DB__Query_Set implements IteratorAggregate {

    private $db;

    private $model;
    private $is_manager = false;

    private $query = array();
    private $sql_query = '';
    private $executed = false;

    private $rows = array();


    public function __construct( $model, $query = null, $is_manager = false ) {
        global $wpdb;

        $this->db = $wpdb;
        $this->model = is_array( $model ) ? $model : WPBDP__DB__Model::get_model_info( $model );

        if ( $query )
            $this->query = $query;
        else
            $this->query = array( 'where' => '',
                                  'join' => '',
                                  'groupby' => '',
                                  'orderby' => '',
                                  'distinct' => '',
                                  'limits' => '',
                                  'fields' => '' );
        $this->is_manager = $is_manager;
    }

    public function get( $args ) {
        if ( is_scalar( $args ) )
            $args = array( 'pk' => $args );

        $where = implode( ' AND ', $this->filter_args( $args ) );

        $q = $this->query;
        $q['where'] = ! empty( $q['where'] ) ? $q['where'] . " AND ($where)" : $where;
        $q['limit'] = 'LIMIT 1';

        $qs = new self( $this->model, $q );
        $qs->maybe_execute_query();

        $res = $qs->to_array();

        if ( ! $res )
            throw new Exception('No row found!');

        return $res[0];
    }

    public function filter( $args, $negate = false ) {
        if ( ! $args )
            return $this;

        $where = $this->filter_args( $args );
        $where = implode( ' AND ', $where );

        if ( $negate )
            $where = " NOT ($where) ";

        $q = $this->query;
        $q['where'] = ! empty( $q['where'] ) ? $q['where'] . " AND ($where)" : $where;

        return new self( $this->model, $q );
    }

    public function exclude( $args ) {
        if ( ! $args )
            return $this;

        return $this->filter( $args, true );
    }

    public function all() {
        return new self( $this->model, $this->query );
    }

    public function count() {
        $sql = $this->build_sql_query();
        $sql = str_replace( '*', 'COUNT(*)', $sql );

        return absint( $this->db->get_var( $sql ) );
    }

    public function exists() {
        return $this->count() > 0;
    }

    /**
     * @implements
     */
    public function getIterator() {
        return new ArrayIterator( $this->to_array() );
    }

    public function to_array() {
        $this->maybe_execute_query();

        $res = array();

        foreach ( $this->rows as $r ) {
            $res[] = WPBDP__DB__Model::from_db( $r, $this->model['class'] );
        }

        return $res;
    }

    private function maybe_execute_query() {
        if ( $this->executed )
            return;

        $sql = $this->build_sql_query();
        $this->rows = $this->db->get_results( $sql, ARRAY_A );
    }

    private function filter_args( $args ) {
        $args = wp_parse_args( $args );
        // null is NULL
        // _exact 
        // _iexact ILIKE
        // contains LIKE %x%
        // icontains ILIKE %x%
        // __in 
        // >
        // <
        // <=
        // >=
        // startswith
        // istartswith
        // endswith
        // iendswith
        // range BETWEEN x AND y
        // __isnull
        $filters = array();

        foreach ( $args as $f => $v ) {
            if ( 'pk' == $f )
                $f = $this->model['primary_key'];

            if ( is_array( $v ) )
                $filters[] = "$f IN ('" . implode( '\',\'', $v ) . "')";
            else
                $filters[] = $this->db->prepare( "$f = %s", $v );
        }

        return $filters;
    }

    public function build_sql_query() {
        extract( $this->query );

        $table = $this->model['table']['name'];

        if ( ! $fields )
            $fields = '*';

        if ( ! empty( $groupby ) )
            $groupby = 'GROUP BY ' . $groupby;

        if ( ! empty( $orderby ) )
            $orderby = 'ORDER BY ' . $orderby;

        if ( ! empty( $where ) )
            $where = "WHERE $where";

        $query = "SELECT $distinct $fields FROM $table $join $where $groupby $orderby $limits";
        return $query;
    }

}
