<?php
if (!class_exists('WP_List_Table'))
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class WPBDP_FormFieldsTable extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => _x('form field', 'form-fields admin', 'WPBDM'),
            'plural' => _x('form fields', 'form-fields admin', 'WPBDM'),
            'ajax' => false
        ));
    }

    public function get_columns() {
        return array(
            'order' => _x('Order', 'form-fields admin', 'WPBDM'),
            'label' => _x('Label / Association', 'form-fields admin', 'WPBDM'),
            'type' => _x('Type', 'form-fields admin', 'WPBDM'),
            'validator' => _x('Validator', 'form-fields admin', 'WPBDM'),
            'tags' => _x( 'Field Attributes', 'form-fields admin', 'WPBDM' ),
        );
    }

    public function prepare_items() {
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        $formfields_api = WPBDP_FormFields::instance();
        $this->items = $formfields_api->get_fields();
    }

    /* Rows */
    public function column_order($field) {
        return sprintf( '<span class="wpbdp-drag-handle" data-field-id="%s"></span> <a href="%s"><strong>↑</strong></a> | <a href="%s"><strong>↓</strong></a>',
                        $field->get_id(),
                        esc_url( add_query_arg( array('action' => 'fieldup', 'id' => $field->get_id() ) ) ) ,
                        esc_url( add_query_arg( array('action' => 'fielddown', 'id' => $field->get_id() ) ) )
                       );
    }

    public function column_label( $field ) {
        $actions = array();
        $actions['edit'] = sprintf( '<a href="%s">%s</a>',
                                    esc_url( add_query_arg( array( 'action' => 'editfield', 'id' => $field->get_id() ) ) ),
                                    _x( 'Edit', 'form-fields admin', 'WPBDM' ) );

        if ( ! $field->has_behavior_flag( 'no-delete' ) ) {
            $actions['delete'] = sprintf( '<a href="%s">%s</a>',
                                         esc_url( add_query_arg( array( 'action' => 'deletefield', 'id' => $field->get_id() ) ) ),
                                         _x( 'Delete', 'form-fields admin', 'WPBDM') );
        }

        $html = '';
        $html .= sprintf( '<strong><a href="%s">%s</a></strong> (as <i>%s</i>)',
                          esc_url( add_query_arg( array( 'action' => 'editfield', 'id' => $field->get_id() ) ) ),
                          esc_attr( $field->get_label() ),
                          $field->get_association() );
        $html .= $this->row_actions( $actions );

        return $html;
    }

    public function column_type( $field ) {
        return esc_html( $field->get_field_type()->get_name() );
    }

    public function column_validator( $field ) {
        return esc_html( implode( ',',  $field->get_validators() ) );
    }

    public function column_tags( $field ) {
        $html = '';

        $html .= sprintf( '<span class="tag %s">%s</span>',
                          $field->is_required() ? 'required' : 'optional',
                          $field->is_required() ? _x( 'Required', 'form-fields admin', 'WPBDM' ) : _x( 'Optional', 'form-fields admin', 'WPBDM' ) );

        if ( $field->display_in( 'excerpt' ) ) {
            $html .= sprintf( '<span class="tag in-excerpt" title="%s">%s</span>',
                              _x( 'This field value is shown in the excerpt view of a listing.', 'form-fields admin', 'WPBDM' ),
                              _x( 'In Excerpt', 'form-fields admin', 'WPBDM' ) );
        }

        if ( $field->display_in( 'listing' ) ) {
            $html .= sprintf( '<span class="tag in-listing" title="%s">%s</span>',
                              _x( 'This field value is shown in the single view of a listing.', 'form-fields admin', 'WPBDM' ),
                              _x( 'In Listing', 'form-fields admin', 'WPBDM' ) );
        }        

        return $html;
    }

}

class WPBDP_FormFieldsAdmin {

    public function __construct() {
        $this->api = wpbdp_formfields_api();
        $this->admin = wpbdp()->admin;
    }

    public function dispatch() {
        $action = wpbdp_getv($_REQUEST, 'action');
        $_SERVER['REQUEST_URI'] = remove_query_arg(array('action', 'id'), $_SERVER['REQUEST_URI']);

        switch ($action) {
            case 'addfield':
            case 'editfield':
                $this->processFieldForm();
                break;
            case 'deletefield':
                $this->deleteField();
                break;
            case 'fieldup':
            case 'fielddown':
                if ( $field = $this->api->get_field( $_REQUEST['id'] ) ) {
                    $field->reorder( $action == 'fieldup' ? 1 : -1 );
                }
                $this->fieldsTable();
                break;
            case 'previewform':
                $this->previewForm();
                break;
            case 'createrequired':
                $this->createRequiredFields();
                break;
            case 'updatetags':
                $this->update_field_tags();
                break;
            default:
                $this->fieldsTable();
                break;
        }
    }

    public static function admin_menu_cb() {
        $instance = new WPBDP_FormFieldsAdmin();
        $instance->dispatch();
    }

    public static function _render_field_settings() {
        $api = wpbdp_formfields_api();

        $association = wpbdp_getv( $_REQUEST, 'association', false );
        $field_type = $api->get_field_type( wpbdp_getv( $_REQUEST, 'field_type', false ) );
        $field_id = wpbdp_getv( $_REQUEST, 'field_id', 0 );

        $response = array( 'ok' => false, 'html' => '' );

        if ( $field_type && in_array( $association, $field_type->get_supported_associations(), true ) ) {
            $field = $api->get_field( $field_id );

            $field_settings = '';
            $field_settings .= $field_type->render_field_settings( $field, $association );

            ob_start();
            do_action_ref_array( 'wpbdp_form_field_settings', array( &$field, $association ) );
            $field_settings .= ob_get_contents();
            ob_end_clean();

            $response['ok'] = true;
            $response['html'] = $field_settings;
        }

        echo json_encode( $response );
        exit;
    }

    /* preview form */
    private function previewForm() {
        require_once( WPBDP_PATH . 'core/view-submit-listing.php' );

        if ( wpbdp()->has_module( 'featuredlevels' ) )
            wpbdp_admin()->messages[] = _x( 'This is a preview of the form as it will appear during "Submit a Listing". The users may not see all fields from "Manage Form Fields" because you have "Featured Levels" active and this is showing the base level.',
                                            'formfields-preview',
                                            'WPBDM' );

        $html = '';

        $html .= wpbdp_admin_header(_x('Form Preview', 'form-fields admin', 'WPBDM'), 'formfields-preview', array(
            array(_x('← Return to "Manage Form Fields"', 'form-fields admin', 'WPBDM'), esc_url(remove_query_arg('action')))
        ));
        $html .= wpbdp_admin_notices();

        $controller = new WPBDP_Submit_Listing_Page( 0, true );
        $html .= $controller->preview_listing_fields_form();

        $html .= wpbdp_admin_footer();

        echo $html;
    }

    /* field list */
    private function fieldsTable() {
        $table = new WPBDP_FormFieldsTable();
        $table->prepare_items();

        wpbdp_render_page(WPBDP_PATH . 'admin/templates/form-fields.tpl.php',
                          array('table' => $table),
                          true);
    }

    private function processFieldForm() {
        $api = WPBDP_FormFields::instance();


        if ( isset( $_POST['field'] ) ) {
            $field = new WPBDP_FormField( stripslashes_deep( $_POST['field'] ) );
            $res = $field->save();

            if ( !is_wp_error( $res ) ) {
                $this->admin->messages[] = _x( 'Form fields updated.', 'form-fields admin', 'WPBDM' );
                return $this->fieldsTable();
            } else {
                $errmsg = '';
                
                foreach ( $res->get_error_messages() as $err ) {
                    $errmsg .= sprintf( '&#149; %s<br />', $err );
                }
                
                $this->admin->messages[] = array( $errmsg, 'error' );
            }
        } else {
            $field = isset( $_GET['id'] ) ? WPBDP_FormField::get( $_GET['id'] ) : new WPBDP_FormField( array( 'display_flags' => array( 'excerpt', 'search', 'listing' ) ) );
        }

        if ( ! wpbdp_get_option( 'override-email-blocking' ) && $field->has_validator( 'email' ) && ( $field->display_in( 'excerpt' ) || $field->display_in( 'listing' ) )  ) {
            $msg = _x( '<b>Important</b>: Since the "<a>Display email address fields publicly?</a>" setting is disabled, display settings below will not be honored and this field will not be displayed on the frontend. If you want e-mail addresses to show on the frontend, you can <a>enable public display of e-mails</a>.',
                       'form-fields admin',
                       'WPBDM' );
            $msg = str_replace( '<a>',
                                '<a href="' . admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=email' ) . '">',
                                $msg );
            wpbdp_admin_message( $msg, 'error' );
        }

        wpbdp_render_page( WPBDP_PATH . 'admin/templates/form-fields-addoredit.tpl.php',
                           array(
                            'field' => $field,
                            'field_associations' => $api->get_associations_with_flags(),
                            'field_types' => $api->get_field_types(),
                            'validators' => $api->get_validators(),
                            'association_field_types' => $api->get_association_field_types()
                           ),
                           true );
    }

    private function deleteField() {
        global $wpdb;

        $field = WPBDP_FormField::get( $_REQUEST['id'] );

        if ( !$field || $field->has_behavior_flag( 'no-delete' ) )
            return;

        if ( isset( $_POST['doit'] ) ) {
            $ret = $field->delete();

            if ( is_wp_error( $ret ) ) {
                $this->admin->messages[] = array( $ret->get_error_message(), 'error' );
            } else {
                $this->admin->messages[] = _x( 'Field deleted.', 'form-fields admin', 'WPBDM' );
            }

            return $this->fieldsTable();
        }

        wpbdp_render_page( WPBDP_PATH . 'admin/templates/form-fields-confirm-delete.tpl.php',
                           array( 'field' => $field ),
                           true );
    }

    private function createRequiredFields() {
        global $wpbdp;

        if ( $missing = $wpbdp->formfields->get_missing_required_fields() ) {
            $wpbdp->formfields->create_default_fields( $missing );
            $this->admin->messages[] = _x('Required fields created successfully.', 'form-fields admin', 'WPBDM');
        }

        return $this->fieldsTable();
    }

    private function update_field_tags() {
        global $wpbdp;

        // Before starting, check if we need to update tags.
        $wpbdp->formfields->maybe_correct_tags();

        $special_tags = array(
            'title' => _x( 'Title', 'form-fields admin', 'WPBDM' ),
            'category' => _x( 'Category', 'form-fields admin', 'WPBDM' ),
            'excerpt' => _x( 'Excerpt', 'form-fields admin', 'WPBDM' ),
            'content' => _x( 'Content', 'form-fields admin', 'WPBDM' ),
            'tags' => _x( 'Tags', 'form-fields admin', 'WPBDM' ),
            'address' => _x( 'Address', 'form-fields admin', 'WPBDM' ),
            'city' => _x( 'City', 'form-fields admin', 'WPBDM' ),
            'state' => _x( 'State', 'form-fields admin', 'WPBDM' ),
            'zip' => _x( 'ZIP Code', 'form-fields admin', 'WPBDM' ),
            'fax' => _x( 'FAX Number', 'form-fields admin', 'WPBDM' ),
            'phone' => _x( 'Phone Number', 'form-fields admin', 'WPBDM' ),
            'ratings' => _x( 'Ratings Field', 'form-fields admin', 'WPBDM' ),
            'twitter' => _x( 'Twitter', 'form-fields admin', 'WPBDM' ),
            'website' => _x( 'Website', 'form-fields admin', 'WPBDM' )
        );
        $fixed_tags = array( 'title', 'category', 'excerpt', 'content', 'tags', 'ratings' );
        $field_tags = array();

        if ( isset( $_POST['field_tags'] ) ) {
            global $wpdb;

            $posted = $_POST['field_tags'];

            foreach ( $posted as $tag => $field_id ) {
                if ( in_array( $tag, $fixed_tags, true ) )
                    continue;

                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_form_fields SET tag = %s WHERE tag = %s",
                                              '', $tag ) );
                $wpdb->query ($wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_form_fields SET tag = %s WHERE id = %d",
                                              $tag,
                                              $field_id ) );
            }

            wpbdp_admin_message( _x( 'Tags updated.', 'form-fields admin', 'WPBDM' ) );
        }

        $missing_fields = $wpbdp->themes->missing_suggested_fields( 'label' );

        foreach ( $special_tags as $t => $td ) {
            $f = WPBDP_Form_Field::find_by_tag( $t );

            $field_tags[] = array( 'tag' => $t,
                                   'description' => $td,
                                   'field_id' => ( $f ? $f->get_id() : 0 ),
                                   'fixed' => ( in_array( $t, $fixed_tags, true ) ? true : false ) );
        }

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/form-fields-tags.tpl.php',
                                array( 'field_tags' => $field_tags, 'missing_fields' => $missing_fields ) );
    }

}
