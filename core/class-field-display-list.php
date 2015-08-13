<?php
/**
 * @since themes-release
 */
class WPBDP_Field_Display_List implements IteratorAggregate {

    private $display = '';
    private $listing_id = '';
    private $frozen = false;

    private $items = array();
    private $names_to_ids = array();


    public function __construct( $listing_id, $display, $fields = array() ) {
        $this->listing_id = $listing_id;
        $this->display = $display;

        foreach ( $fields as &$f ) {
            $this->append( $f );
        }
    }

    public function append( &$f ) {
        if ( $this->frozen )
            return;

        if ( $f instanceof _WPBDP_Lightweight_Field_Display_Item ) {
            if ( $f->field->display_in( $this->display ) )
                $this->items[ $f->id ] = $f;
            return;
        }

        $field_id = $f->get_id();

        if ( ! $f->display_in( $this->display) || isset( $this->items[ $field_id ] ) )
            return;

        $this->items[ $field_id ] = new _WPBDP_Lightweight_Field_Display_Item( $f, $this->listing_id, $this->display );
        $this->names_to_ids[ $f->get_short_name() ] = $field_id;
    }

    public function freeze() {
        $this->frozen = true;
    }

    public function not( $filter ) {
        return $this->filter( '-' . $filter );
    }

    public function filter( $filter ) {
        $neg = ( '-' == substr( $filter, 0, 1 ) );
        $filter = ( $neg ? substr( $filter, 1 ) : $filter );

        $filtered = array();

        $type_filter = '';
        $association_filter = '';
        $social_filter = ( $filter == 'social' );

        foreach ( $this->items as &$f ) {
            if ( $social_filter && $neg && ! $f->field->display_in( 'social' ) )
                $filtered[] = $f;
            elseif ( $social_filter && ! $neg && $f->field->display_in( 'social' ) )
                $filtered[] = $f;
        }

        $res = new self( $this->listing_id, $this->display, $filtered );
        $res->freeze();

        return $res;
    }

    public function getIterator() {
        return new ArrayIterator( $this->items );
    }

    public function __get( $key ) {
        $field_id = 0;

        if ( 'html' == $key ) {
            return implode( '', wp_list_pluck( $this->items, 'html' ) );
        }

        if ( 'id' == substr( $key, 0, 2 ) )
            $field_id = absint( substr( $key, 2 ) );

        if ( ! $field_id )
            $field_id = isset( $this->names_to_ids[ $key ] ) ? $this->names_to_ids[ $key ] : 0;

        if ( $field_id && isset( $this->items[ $field_id ] ) )
            return $this->items[ $field_id ];

        throw new Exception('Invalid key: ' . $key);
    }

}

/**
 * @since themes-release
 */
class _WPBDP_Lightweight_Field_Display_Item {

    private $field = null;
    private $listing_id = 0;
    private $display = '';

    private $html_ = null;
    private $value_ = null;
    private $raw_ = null;

    public function __construct( &$field, $listing_id, $display ) {
        $this->field = $field;
        $this->listing_id = $listing_id;
        $this->display = $display;
    }

    public function __get( $key ) {
        $k = "${key}_";

        if ( isset( $this->{$k} ) )
            return $this->{$k};

        $v = null;

        switch ( $key ) {
            case 'html':
                $v = $this->field->display( $this->listing_id, $this->display );
                break;
            case 'value':
                $v = $this->field->html_value( $this->listing_id );
                break;
            case 'raw':
                $v = $this->field->value( $this->listing_id );
                break;
            case 'id':
                return $this->field->get_id();
                break;
            case 'label':
                return $this->field->get_label();
                break;
            case 'field':
                return $this->field;
            default:
                break;
        }

        $this->{$k} = $v;
        return $v;
    }

}


