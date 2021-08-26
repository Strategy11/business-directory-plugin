<?php

// Custom category walker (used when rendering category fields using radios or checkboxes)
class WPBDP_CategoryFormInputWalker extends Walker {
    var $tree_type = 'category';
    var $db_fields = array( 'parent' => 'parent', 'id' => 'term_id' );

    private $input_type;
    private $selected;
    private $field;

	public function __construct( $input_type = 'radio', $selected = null, &$field = null ) {
        $this->input_type = $input_type;
        $this->selected = $selected;
        $this->field = $field;
    }

    public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
        switch ( $this->input_type ) {
            case 'checkbox':
                $output .= '<div class="wpbdp-form-field-checkbox-item wpbdmcheckboxclass">';
                $output .= sprintf( '<label for="wpbdp-field-%7$d-%6$s"><input id="wpbdp-field-%7$d-%6$s" type="checkbox" class="%s" name="%s" value="%s" %s style="margin-left: %dpx;" /> %s</label>',
                                    $this->field->is_required() ? 'required' : '',
                                    'listingfields[' . $this->field->get_id() . '][]',
                                    $category->term_id,
                                    in_array( $category->term_id, is_array( $this->selected ) ? $this->selected : array( $this->selected ) ) ? 'checked="checked"' : '',
                                    $depth * 10,
                                    esc_attr( $category->name ),
                                    $this->field->get_id()
                                  );
                $output .= '</div>';
                break;
            case 'radio':
            default:
                $output .= sprintf( '<div class="wpbdm-form-field-radio-item"><label for="wpbdp-field-%6$d-%5$s"><input id="wpbdp-field-%6$d-%5$s" type="radio" name="%s" class="%s" value="%s" %s style="margin-left: %dpx;"> %s</label></div>',
                                    'listingfields[' . $this->field->get_id() . ']',
                                    $this->field->is_required() ? 'inradio required' : 'inradio',
                                    $category->term_id,
                                    $this->selected == $category->term_id ? 'checked="checked"' : '',
                                    $depth * 10,
                                    esc_attr( $category->name ),
                                    $this->field->get_id()
                                  );
                break;
        }

    }
}
