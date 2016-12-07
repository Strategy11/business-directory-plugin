<?php
/**
 * @since next-release
 */
class WPBDP__Listing_Search {

    private $tree = array();
    private $parts = array();
    public $aliases = array();
    private $query_template = '';
    private $query = '';
    private $results = null;


    public function __construct( $tree ) {
        $this->tree = $tree;

        // If the tree has no head, assume 'and'.
        if ( ! isset( $this->tree[0] ) || ! is_string( $this->tree[0] ) )
            array_unshift( $this->tree, 'and' );
    }

    public function terms_for_field( $field ) {
        $field = is_object( $field ) ? $field->get_id() : absint( $field );
        $result = array();

        foreach ( $this->parts as $p ) {
            if ( $field == $p[0] )
                $result[] = $p[1];
        }

        return $result;
    }

    public function get_tree() {
        return $this->tree;
    }

    public function get_results() {
        if ( ! is_array( $this->results ) ) {
            $this->execute();
        }

        return $this->results;
    }

    public function execute() {
        global $wpdb;

        $this->tree = self::tree_simplify( $this->tree );

        // Prepare query template.
        $this->query_template = $this->_traverse_tree( $this->tree );

        // Build query.
        $query_pieces = array( 'where' => $this->query_template,
                               'join' => '',
                               'orderby' => '',
                               'distinct' => '',
                               'fields' => "{$wpdb->posts}.ID",
                               'limits' => '' );

        foreach ( $this->parts as $key => $data )  {
            $field = wpbdp_get_form_field( $data[0] );
            $res = $field->configure_search( $data[1], $this );

            if ( ! empty( $res['where'] ) ) {
                $query_pieces['where'] = str_replace( '%' . $key . '%', $res['where'], $query_pieces['where'] );
            } else {
                // This prevents incorrect queries from being created.
                $query_pieces['where'] = str_replace( 'AND %' . $key . '%', '', $query_pieces['where'] );
                $query_pieces['where'] = str_replace( 'OR %' . $key . '%', '', $query_pieces['where'] );
            }

            foreach ( $res as $k => $v ) {
                if ( 'where' == $k )
                    continue;

                $query_pieces[ $k ] .= ' ' . $v . ' ';
            }
        }

        $query_pieces['where'] = str_replace( 'AND  AND', 'AND', $query_pieces['where'] );
        $query_pieces['where'] = str_replace( 'OR  OR', 'OR', $query_pieces['where'] );
        $query_pieces['where'] = str_replace( 'AND )', ')', $query_pieces['where'] );
        $query_pieces['where'] = str_replace( 'OR )', ')', $query_pieces['where'] );

        $query_pieces = apply_filters_ref_array( 'wpbdp_search_query_pieces', array( $query_pieces, $this ) );

        $this->query = sprintf( "SELECT %s %s FROM {$wpdb->posts} %s WHERE ({$wpdb->posts}.post_type = '%s' AND {$wpdb->posts}.post_status = '%s') AND %s GROUP BY {$wpdb->posts}.ID %s %s",
                                $query_pieces['distinct'],
                                $query_pieces['fields'],
                                $query_pieces['join'],
                                WPBDP_POST_TYPE,
                                'publish',
                                $query_pieces['where'],
                                $query_pieces['orderby'],
                                $query_pieces['limits'] );
        // wpbdp_debug_e($this->query);
        $this->results = $wpdb->get_col( $this->query );
    }

    private function _traverse_tree( $tree ) {
        if ( is_array( $tree ) && 2 == count( $tree ) && is_numeric( $tree[0] ) ) {
            $key = md5( serialize( $tree ) );

            if ( ! isset( $this->parts[ $key ] ) )
                $this->parts[ $key ] = $tree;

            return '%' . $key . '%';
        }

        $res = '';
        $head = $tree[0];
        $args = array_slice( $tree, 1 );

        $res .= '(';
        $res .= ( 'and' == $head ? '1=1' : '1=0' );

        foreach ( $args as $x ) {
            $res .= ' ' . strtoupper( $head ) . ' ';
            $res .= $this->_traverse_tree( $x );
        }

        $res .= ')';

        return $res;
    }

    public function join_alias( $table, $reuse = false ) {
        if ( ! isset( $this->aliases[ $table ] ) )
            $this->aliases[ $table ] = array();

        $i = count( $this->aliases[ $table ] );
        $alias = '';
        $reused = false;

        if ( $reuse && $i > 0 ) {
            $alias = $this->aliases[ $table ][ $i - 1 ];
            $reused = true;
        } else {
            $alias = $i > 0 ? 't_' . $i : $table;
        }

        $this->aliases[ $table ][] = $alias;

        return array( $alias, $reused );
    }

    public static function from_request( $request = array() ) {
        return new self( self::parse_request( $request ) );
    }

    public static function parse_request( $request = array() ) {
        $res = array();

        // Quick search.
        if ( ! empty( $request['kw'] ) ) {
            $keywords = wpbdp_get_option( 'quick-search-enable-performance-tricks' ) ? array( $request['kw'] ) : explode( ' ', $request['kw'] );

            $fields_ids = wpbdp_get_option( 'quick-search-fields' );
            $fields_ids = $fields_ids ? $fields_ids : wpbdp_get_form_fields( 'association=title,excerpt,content&output=ids' );

            $fields = array();

            foreach ( $fields_ids as $field_id ) {
                $field = wpbdp_get_form_field( $field_id );

                if ( $field ) {
                    $fields[] = $field;
                }
            }

            $res[] = 'and';

            foreach ( $keywords as $k ) {
                $subq = array( 'or' );

                foreach ( $fields as $field ) {
                    $subq[] = array( $field->get_id(), $k );
                }

                $res[] = $subq;
            }
        } elseif ( ! empty( $request['listingfields'] ) ) {
            // Regular search.
            $res[] = 'and';

            foreach ( $request['listingfields'] as $field_id => $term ) {
                if ( ! $term )
                    continue;

                $res[] = array( $field_id, $term );
            }
        }

        $res = apply_filters( 'wpbdp_listing_search_parse_request', $res, $request );
        // wpbdp_debug_e($res);
        return $res;
    }

    public static function tree_remove_field( $tree, $field ) {
        $field = is_object( $field ) ? $field->get_id() : absint( $field );
        $result = array();

        foreach ( $tree as $t ) {
            if ( is_array( $t ) && 2 == count( $t ) && isset( $t[0] ) && $field == $t[0] )
                continue;
            elseif ( is_array( $t ) )
                $t = self::tree_remove_field( $t, $field );

            $result[] = $t;
        }

        return $result;
    }

    public static function tree_simplify( $tree ) {
        return $tree;
    }

}

