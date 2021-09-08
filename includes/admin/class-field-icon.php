<?php

require_once WPBDP_INC . 'helpers/class-fontawesome.php';

/**
 * @since 5.6.3
 */
class WPBDP_Admin_Field_Icon {

    private $fontawesome = null;
    private $icons = array();


    public function __construct() {
        $this->fontawesome  = new WPBDP_FontAwesome();
        $this->icons        = $this->fontawesome->icons_drop_down();
    }


    public function dropdown_fonts( $selected = '' ) {
  
        $params = array(
            'default'   => __( 'Please select an icon', 'business-directory-plugin' ),
            'id'        => 'field[icon]',
            'name'      => 'field[icon]',
            'class'     => array( 'wpbdp-icon-selector' ),
            'mode'      => 'inline',
            'selected'  => $selected
        );

        return $this->render( $params );
    }


    /**
     * @param array $params     An array of configuration parameters.
     * @since 5.6.3
     */
    public function render( $params ) {
        return wpbdp_render_page(
            WPBDP_PATH . 'templates/admin/form-fields-label-icon.tpl.php',
            $this->prepare_paramaters( $params )
        );
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 5.6.3
     */
    private function prepare_paramaters( $params ) {
        $params = wp_parse_args(
            $params,
            array(
                'label'       => false,
                'label_class' => false,
                'required'    => false,
                'selected'    => null,
                'mode'        => null,
                'icons'       => array(),
                'nonce'       => '',
            )
        );
        return $this->prepare_mode_parameters( $params );
    }


    /**
     * @param array $params     An array of configuration parameters.
     * @since 5.6.3
     */
    private function prepare_mode_parameters( $params ) {
        $params['configuration'] = $this->get_mode_configuration( $params );
        if ( $params['mode'] !== 'ajax' ) {
            $params['icons'] = $this->icons;
        }
        return $params;
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 5.6.3
     */
    private function get_mode_configuration( $params ) {
        $configuration = $this->get_common_configuration( $params );

        if ( $params['mode'] === 'ajax' ) {
            $configuration['select2'] = array(
                'ajax' => array(
                    'url'      => add_query_arg( 'action', 'wpbdp-autocomplete-icons', admin_url( 'admin-ajax.php' ) ),
                    'dataType' => 'json',
                ),
            );

            $configuration['security']   = wp_create_nonce( 'ajax_autocomplete_icons' );
        } else {
            $configuration['select2'] = array();
        }

        return $configuration;
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 5.6.3
     */
    private function get_common_configuration( $params ) {
		$icons = $this->icons;
        return array(
            'selected' => ! empty( $params['selected'] ) ? array(
                'id'   => $params['selected'],
                'text' => $icons[ $params['selected'] ],
            ) : '',
            'mode'     => $params['mode'],
        );
    }



    public function ajax_autocomplete_users() {
        global $wpdb;

        $request = wp_unslash( $_REQUEST );

        if ( ! ( isset( $request['security'] ) && wp_verify_nonce( $request['security'], 'ajax_autocomplete_icons') ) ) {
            wp_send_json(
                array(
                    'status' => 'fail',
                )
            );
        }

        $response = $this->fontawesome->search_icons( ! empty( $request['term'] ) ? $request['term'] : '' );


        wp_send_json(
            array(
                'status' => 'ok',
                'items'  => $response,
            )
        );
    }
}
