<?php
require_once ( WPBDP_PATH . 'core/helpers/class-wp-taxonomy-term-list.php'  );

/**
 * @since next-release
 */
class WPBDP__Admin__Variable_Pricing_Configurator extends WPBDP__WP_Taxonomy_Term_List {

    private $prices = array();


    function __construct( $args ) {
        parent::__construct( $args );

        if ( ! isset( $args['fee'] ) )
            return;

        $fee = $args['fee'];

        if ( 'variable' != $fee->pricing_model )
            return;

        $this->prices = $fee->pricing_details;
    }

    protected function element_before( $term, $depth ) {
        return '<div class="wpbdp-variable-pricing-configurator-row ' . ( ! isset( $this->prices[ $term->term_id ] ) ? 'hidden' : '' ) . '" data-term-id="' . $term->term_id . '">';
    }

    protected function element( $term, $depth ) {
        $res = parent::element( $term, $depth );
        return str_replace( array( '<br>', '<br />' ), '', $res );
    }

    protected function element_after( $term, $depth  ) {
        $res  = '';
        $res .= sprintf( '<input type="text" name="fee[pricing_details][%d]" class="category-price" value="%s" />',
                         $term->term_id,
                         isset( $this->prices[ $term->term_id ] ) ? $this->prices[ $term->term_id ] : '0.0' );
        $res .= '</div>';

        return $res;
    }

}
