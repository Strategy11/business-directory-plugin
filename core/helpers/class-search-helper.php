<?php

/**
 * @since next-release
 */
class WPBDP__Search_Helper {

    public $mode = '';
    public $location = '';
    public $keywords = array();
    public $fields = array();
    public $plan = array();

    public $aliases = array();
    public $query_pieces;

    public $resultset = array();


    public function __construct( $args ) {
        $defaults = array( 'query' => '',
                           'location' => '',
                           'fields' => array(),
                           'mode' => 'advanced' );
        $args = wp_parse_args( $args, $defaults );
        $args = apply_filters( 'wpbdp_search_args', $args );

        $this->mode = $args['mode'];
        $this->location = $args['location'];

        if ( 'advanced' == $this->mode ) {
            foreach ( $args['query'] as $field_id => $keywords ) {
                $f = WPBDP_Form_Field::get( $field_id );

                if ( ! $f )
                    continue;

                $this->keywords[ $field_id ] = wpbdp_get_option( 'quick-search-enable-performance-tricks' ) ? array( $keywords ) : array_map( 'trim', explode( ' ', $keywords ) );
                $this->fields[ $field_id ] = $f;
            }
        } elseif ( 'quick-search' == $this->mode ) {
            $this->keywords = wpbdp_get_option( 'quick-search-enable-performance-tricks' ) ? array( $args['query'] ) : array_map( 'trim', explode( ' ', $args['query'] ) );

            foreach ( $args['fields'] as $f ) {
                if ( is_object( $f ) ) {
                    $this->fields[ $f->get_id() ] = $f;
                } else {
                    if ( $field = WPBDP_Form_Field::get( $f ) )
                        $this->fields[ $field->get_id() ] = $field;
                }
            }
        }

        do_action_ref_array( 'wpbdp_search_after_init', array( $this ) );

    }

    public function execute() {
        global $wpdb;

        if ( $this->resultset )
            return;

        $this->plan = $this->build_plan();
        $this->plan = apply_filters( 'wpbdp_search_plan', $this->plan, $this );

        $execution = array();

        if ( 'quick-search' == $this->mode ) {
            foreach ( $this->plan as $keyword => $fields ) {
                $execution[ $keyword ] = array();

                foreach ( $fields as $field_query ) {
                    $f = $this->fields[ $field_query['field'] ];
                    $execution[ $keyword ][] = $f->configure_search( $keyword, $this );
                }
            }
        }

        $query_pieces = array( 'where' => ' ',
                               'join' => '',
                               'orderby' => '',
                               'distinct' => '',
                               'fields' => "{$wpdb->posts}.ID",
                               'limits' => '' );

        if ( 'quick-search' == $this->mode ) {
            $query_pieces['where'] .= '1=1 ';

            foreach ( $execution as $keyword => $execdetails ) {
                $query_pieces['where'] .= ' AND ( 1=0 ';

                foreach ( $execdetails as $r ) {
                    foreach ( array_keys( $query_pieces ) as $i ) {
                        if ( ! isset( $r[ $i ] ) )
                            continue;

                        if ( 'where' == $i )
                            $r[ $i ] = ' OR ' . $r[ $i ];

                        $query_pieces[ $i ] .= ' ' . $r[ $i ] . ' ';
                    }
                }

                $query_pieces['where'] .= ') ';
            }
        }

        $query = sprintf( "SELECT %s %s FROM {$wpdb->posts} %s WHERE ({$wpdb->posts}.post_type = '%s' AND {$wpdb->posts}.post_status = '%s') AND %s GROUP BY {$wpdb->posts}.ID %s %s",
                          $query_pieces['distinct'],
                          $query_pieces['fields'],
                          $query_pieces['join'],
                          WPBDP_POST_TYPE,
                          'publish',
                          $query_pieces['where'],
                          $query_pieces['orderby'],
                          $query_pieces['limits'] );
        $this->resultset = $wpdb->get_col( $query );
    }

    public function get_posts() {
        $this->execute();
        return $this->resultset;
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

    private function build_plan() {
        $plan = array();

        if ( 'quick-search' == $this->mode ) {
            foreach ( $this->keywords as $k ) {
                $plan[ $k ] = array();

                foreach ( $this->fields as $f ) {
                    $plan[ $k ][] = array( 'field' => $f->get_id(), 'keyword' => $k );
                }
            }
        } elseif ( 'advanced' == $this->mode ) {
        }

        return $plan;
    }

}
