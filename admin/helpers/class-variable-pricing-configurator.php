<?php
require_once ( WPBDP_PATH . 'core/helpers/class-wp-taxonomy-term-list.php'  );

/**
 * @since next-release
 */
class WPBDP__Admin__Variable_Pricing_Configurator extends WPBDP__WP_Taxonomy_Term_List {

    protected function element_after( $term, $depth  ) {
        $res  = '';
        $res .= '<input type="text" value="0.0" name="fee[pricing_details][' . $term->term_id . ']" class="category-price" />';

        return $res;
    }

}
