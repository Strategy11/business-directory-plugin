<?php

// Custom category walker (used when rendering category fields using radios or checkboxes)
class WPBDP_CategoryFormInputWalker extends Walker {
    var $tree_type = 'category';
    var $db_fields = array( 'parent' => 'parent', 'id' => 'term_id' );

    private $input_type;
    private $selected;
    private $field;

    public function __construct( $input_type='radio', $selected=null, &$field=null ) {
        $this->input_type = $input_type;
        $this->selected = $selected;
        $this->field = $field;
    }

    public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
        switch ( $this->input_type ) {
            case 'checkbox':
                $output .= '<div class="wpbdmcheckboxclass">';
                $output .= sprintf( '<input type="checkbox" class="%s" name="%s" value="%s" %s style="margin-left: %dpx;" />%s',
                                    $this->field->is_required() ? 'required' : '',
                                    'listingfields[' . $this->field->get_id() . '][]',
                                    $category->term_id,
                                    in_array( $category->term_id, is_array( $this->selected ) ? $this->selected : array( $this->selected ) ) ? 'checked="checked"' : '',
                                    $depth * 10,
                                    esc_attr( $category->name )
                                  );
                $output .= '</div>';
                break;
            case 'radio':
            default:
                $output .= sprintf( '<input type="radio" name="%s" class="%s" value="%s" %s style="margin-left: %dpx;"> %s<br />',
                                    'listingfields[' . $this->field->get_id() . ']',
                                    $this->field->is_required() ? 'inradio required' : 'inradio',
                                    $category->term_id,
                                    $this->selected == $category->term_id ? 'checked="checked"' : '',
                                    $depth * 10,
                                    esc_attr( $category->name )
                                  );
                break;
        }

    }
}
