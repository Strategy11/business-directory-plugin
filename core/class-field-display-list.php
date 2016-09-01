<?php
/**
 * @since 4.0
 */
class WPBDP_Field_Display_List implements IteratorAggregate {

    private $display = '';
    private $listing_id = '';
    private $frozen = false;

    private $items = array();
    private $displayed_fields = array();
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
            $this->items[ $f->id ] = $f;
            $this->names_to_ids[ $f->field->get_short_name() ] = $f->id;
            $this->names_to_ids[ 't_' . $f->field->get_tag() ] = $f->id;

            if ( $f->field->display_in( $this->display ) )
                $this->displayed_fields[] = $f->id;

            return;
        }

        $field_id = $f->get_id();

        if ( isset( $this->items[ $field_id ] ) )
            return;

        if ( ! $f->display_in( $this->display ) )
            return;

        // if( $f->display_in( $this->display ) )
        //     $this->displayed_fields[] = $field_id;

        $this->displayed_fields[] = $field_id;
        $this->items[ $field_id ] = new _WPBDP_Lightweight_Field_Display_Item( $f, $this->listing_id, $this->display );
        $this->names_to_ids[ $f->get_short_name() ] = $field_id;
        $this->names_to_ids[ 't_' . $f->get_tag() ] = $field_id;
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

    public function exclude( $fields_ ) {
        $exclude = is_array( $fields_ ) ? $fields_ : explode( ',', $fields_ );
        $filtered = array();

        if ( ! $exclude )
            return $this;

        foreach ( $this->items as $f ) {
            if ( in_array( 'id_' . $f->id, $exclude ) || in_array( 't_' . $f->field->get_tag(), $exclude, true ) || in_array( $f->field->get_short_name(), $exclude, true ) )
                continue;

            $filtered[] = $f;
        }

        $res = new self( $this->listing_id, $this->display, $filtered );
        $res->freeze();
        return $res;
    }

    public function getIterator() {
        return new ArrayIterator( $this->items_for_display() );
    }

    public function items_for_display() {
        $valid_ids = $this->displayed_fields;
        $fields = array();

        if ( ! $valid_ids )
            return array();

        foreach ( $this->items as $i ) {
            if ( ! in_array( $i->id, $valid_ids ) )
                continue;

            $fields[] = $i;
        }

        return $fields;
    }

    public function __get( $key ) {
        $field_id = 0;

        if ( 'html' == $key ) {
            $html  = '';
            $html .= implode( '', wp_list_pluck( $this->items_for_display(), 'html' ) );

            // FIXME: move this to a compat layer.
            if ( 'listing' == $this->display ) {
                $html = apply_filters( 'wpbdp_single_listing_fields', $html, $this->listing_id );
            }

            return $html;
        }

        if ( '_h_' == substr( $key, 0, 3 ) )
            return method_exists( $this, 'helper__' . substr( $key, 3 ) ) ? call_user_func( array( $this, 'helper__' . substr( $key, 3 ) ) ) : '';

        if ( 'id' == substr( $key, 0, 2 ) )
            $field_id = absint( substr( $key, 2 ) );

        if ( ! $field_id )
            $field_id = isset( $this->names_to_ids[ $key ] ) ? $this->names_to_ids[ $key ] : 0;

        if ( $field_id && isset( $this->items[ $field_id ] ) )
            return $this->items[ $field_id ];

        wpbdp_debug( 'Invalid field key: ' . $key );
        return new WPBDP_NoopObject(); // FIXME: templates shouldn't rely on a field existing.
        return false;
    }

    //
    // Helpers. {{
    //

    public function helper__address() {
        $address = trim( $this->t_address->value );
        $city = trim( $this->t_city->value );
        $state = trim( $this->t_state->value );
        $zip = trim( $this->t_zip->value );

        $html  = '';
        $html .= $address;
        $html .= ( $city || $state || $zip ) ? '<br />' : '';
        $html .= $city;
        $html .= ( $city && $state ) ? ', ' . $state : $state;
        $html .= $zip ? ' ' . $zip : '';

        return $html;
    }

    public function helper__address_nobr() {
        return str_replace( '<br />', ', ', $this->helper__address() );
    }

    //
    // }}
    //
}

/**
 * @since 4.0
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
            case 'tag':
                return $this->field->get_tag();
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


