<?php

class WPBDP_Admin_Listings {

    function __construct() {
        add_action('admin_init', array($this, 'add_metaboxes'));
        add_action( 'wpbdp_admin_notices', array( $this, 'no_plan_edit_notice' ) );

        add_action( 'manage_' . WPBDP_POST_TYPE . '_posts_columns', array( &$this, 'add_columns' ) );
        add_action( 'manage_' . WPBDP_POST_TYPE . '_posts_custom_column', array( &$this, 'listing_column' ), 10, 2 );

        add_filter( 'views_edit-' . WPBDP_POST_TYPE, array( &$this, 'listing_views' ) );
        add_filter( 'posts_clauses', array( &$this, 'listings_admin_filters' ) );

        add_filter( 'post_row_actions', array( &$this, 'row_actions' ), 10, 2 );

        add_action('admin_footer', array($this, '_add_bulk_actions'));
        add_action('admin_footer', array($this, '_fix_new_links'));

        add_action( 'wpbdp_save_listing', array( $this, 'save_fields_and_fee' ) );

        // Filter by category.
        add_action( 'restrict_manage_posts', array( &$this, '_add_category_filter' ) );
        add_action( 'parse_query', array( &$this, '_apply_category_filter' ) );

        // Augment search with username search.
        add_filter( 'posts_clauses', array( &$this, '_username_search_support' ) );
    }

    // Category filter. {{

    function _add_category_filter() {
        global $typenow;
        global $wp_query;

        if ( WPBDP_POST_TYPE != $typenow )
            return;

        wp_dropdown_categories( array(
            'show_option_all' => _x( 'All categories',  'admin category filter', 'WPBDM' ),
            'taxonomy' => WPBDP_CATEGORY_TAX,
            'name' => WPBDP_CATEGORY_TAX,
            'orderby' => 'name',
            'selected' => ( ! empty ( $wp_query->query[ WPBDP_CATEGORY_TAX ] ) ? $wp_query->query[ WPBDP_CATEGORY_TAX ] : 0 ),
            'hierarchical' => true,
            'hide_empty' => false,
            'depth' => 4
        ) );
    }

    function _apply_category_filter( $query ) {
        if ( ! is_admin() )
            return;

        if ( ! function_exists( 'get_current_screen' ) )
            return;

        $screen = get_current_screen();

        if ( ! $screen )
            return;

        if ( 'edit' != $screen->base ||  WPBDP_POST_TYPE != $screen->post_type )
            return;

        if ( empty( $query->query_vars[ WPBDP_CATEGORY_TAX ] ) || ! is_numeric( $query->query_vars[ WPBDP_CATEGORY_TAX ] ) )
            return;

        $term = get_term_by( 'id', $query->query_vars[ WPBDP_CATEGORY_TAX ], WPBDP_CATEGORY_TAX );

        if ( ! $term )
            return;

        $query->query_vars[ WPBDP_CATEGORY_TAX ] = $term->slug;
    }

    // }}

    function _username_search_support( $pieces ) {
        global $wp_query, $wpdb;

        if ( ! function_exists( 'get_current_screen' ) || ! is_admin() )
            return $pieces;

        $screen = get_current_screen();

        if ( ! $screen
            || 'edit' != $screen->base || WPBDP_POST_TYPE != $screen->post_type
            || ! isset( $_GET['s'] ) || ! $wp_query->is_search )
            return $pieces;

        $orig_s = urldecode( $_GET['s'] );
        $orig_s = '%' . $wpdb->esc_like( $orig_s ) . '%';

        $s = str_replace( '*', '%', $wpdb->esc_like( urldecode( $_GET['s'] ) ) );

        if ( false !== strstr( $s, '%' ) ) {
            $where = "$wpdb->users.user_login LIKE '$s' OR $wpdb->users.display_name LIKE '$s'";
        } else {
            $where = $wpdb->prepare( "$wpdb->users.user_login = %s OR $wpdb->users.display_name = %s",
                                     $s,
                                     $s );
        }

        $regex = "/($wpdb->posts.post_title LIKE '" . preg_quote( $orig_s ) . "')/i";

        $pieces['join'] .= " LEFT JOIN $wpdb->users ON $wpdb->posts.post_author = $wpdb->users.ID ";
        $pieces['where'] = preg_replace( $regex, " $0 OR " . $where, $pieces['where'] );

        return $pieces;
    }

    function no_plan_edit_notice() {
        if ( ! function_exists( 'get_current_screen' ) )
            return;

        $screen = get_current_screen();

        if ( ! $screen || WPBDP_POST_TYPE != $screen->id || 'add' == $screen->action  )
            return;

        global $post;

        if ( ! $post )
            return;

        $listing = WPBDP_Listing::get( $post->ID );

        if ( ! $listing )
            return;

        if( ! $listing->has_fee_plan() )
            wpbdp_admin_message( _x( 'This listing doesn\'t have a fee plan assigned. This is required in order to determine the features available to this listing, as well as handling renewals.', 'admin listings', 'WPBDM' ), 'error' );
    }

    function add_metaboxes() {
        add_meta_box( 'wpbdp-listing-plan',
                      __( 'Listing Information', 'WPBDM' ),
                      array( $this, '_metabox_listing_info' ),
                      WPBDP_POST_TYPE,
                      'side',
                      'core' );
        add_meta_box( 'wpbdp-listing-timeline',
                      __( 'Listing Timeline', 'WPBDM' ),
                      array( $this, '_metabox_listing_timeline' ),
                      WPBDP_POST_TYPE,
                      'side',
                      'core' );
        add_meta_box( 'wpbdp-listing-fields',
                      _x( 'Listing Fields / Images', 'admin', 'WPBDM' ),
                      array( 'WPBDP_Admin_Listing_Fields_Metabox', 'metabox_callback' ),
                      WPBDP_POST_TYPE,
                      'normal',
                      'core' );
    }

    public function _metabox_listing_info( $post ) {
        require_once( WPBDP_PATH . 'admin/helpers/class-listing-information-metabox.php' );
        $metabox = new WPBDP__Admin__Metaboxes__Listing_Information( $post->ID );
        $metabox->render();
    }

    public function _metabox_listing_timeline( $post ) {
        require_once( WPBDP_PATH . 'admin/helpers/class-listing-timeline.php' );
        $timeline = new WPBDP__Listing_Timeline( $post->ID );

        echo $timeline->render();
    }

    // {{{ Custom columns.

    function add_columns( $columns_ ) {
        $custom_columns = array();
        $custom_columns['category'] = _x( 'Categories', 'admin', 'WPBDM' );
        $custom_columns['status'] = __( 'Listing Status', 'WPBDM' );
        $custom_columns['expiration_date'] = __( 'Expires on', 'WPBDM' );

        // Do not show comments column.
        unset( $columns_['comments'] );

        $columns = array();

        foreach ( $columns_ as $k => $v ) {
            $columns[ $k ] = $v;

            if ( 'title' == $k )
                $columns = array_merge( $columns, $custom_columns );
        }

        $columns['attributes'] = __( 'Attributes', 'WPBDM' );

        return apply_filters( 'wpbdp_admin_directory_columns', $columns );
    }


    function listing_column( $column, $post_id ) {
        if ( ! method_exists( $this, 'listing_column_' . $column ) )
            return do_action( 'wpbdp_admin_directory_column_' . $column, $post_id );

        call_user_func( array( &$this, 'listing_column_' . $column ), $post_id );
    }

    function listing_column_category( $post_id ) {
        $terms = wp_get_post_terms( $post_id, WPBDP_CATEGORY_TAX );
        foreach ( $terms as $i => $term ) {
            printf( '<a href="%s">%s</a>', get_term_link( $term->term_id, WPBDP_CATEGORY_TAX ), $term->name );

            if ( ( $i + 1 ) != count( $terms ) )
                echo ', ';
        }
    }

    function listing_column_status( $post_id ) {
        $listing = WPBDP_Listing::get( $post_id );
        $status = apply_filters( 'wpbdp_admin_listing_display_status', array( $listing->get_status(), $listing->get_status_label() ), $listing );
        echo $status[1];
    }

    public function listing_column_expiration_date( $post_id ) {
        $listing = WPBDP_Listing::get( $post_id );
        $exp_date = $listing->get_expiration_date();

        if ( $exp_date )
            echo date_i18n( get_option( 'date_format' ), strtotime( $exp_date ) );
        else
            echo _x( 'Never', 'admin listings', 'WPBDM' );
    }

    public function listing_column_attributes( $post_id ) {
        $listing = WPBDP_Listing::get( $post_id );
        $plan = $listing->get_fee_plan();

        $attributes = array();

        if ( $plan->is_sticky )
            $attributes['featured'] = '<span class="tag">' . _x( 'Featured', 'admin listings', 'WPBDM' ) . '</span>';

        if ( $plan->is_recurring )
            $attributes['recurring'] = '<span class="tag">' . _x( 'Recurring', 'admin listings', 'WPBDM' ) . '</span>';

        if ( 0.0 == $plan->fee_price )
            $attributes['free'] = '<span class="tag">' . _x( 'Free', 'admin listings', 'WPBDM' ) . '</span>';
        else
            $attributes['paid'] = '<span class="tag">' . _x( 'Paid', 'admin listings', 'WPBDM' ) . '</span>';

        $attributes = apply_filters( 'wpbdp_admin_directory_listing_attributes', $attributes, $listing );

        foreach ( $attributes as $attr )
            echo $attr;
    }


    // }}}

    // {{{ List views.

    function listing_views( $views ) {
        global $wpdb;

        if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'editor' ) ) {
            if ( current_user_can( 'contributor' ) && isset( $views['mine'] ) )
                return array( $views['mine'] );

            return array();
        }

        $post_statuses = '\'' . join('\',\'', isset($_GET['post_status']) ? array($_GET['post_status']) : array('publish', 'draft', 'pending')) . '\'';

//        $unpaid = $wpdb->get_var( $wpdb->prepare(
//            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->prefix}wpbdp_payments ps ON p.ID = ps.listing_id
//             WHERE p.post_type = %s AND p.post_status IN ({$post_statuses}) AND ps.status = %s",
//             WPBDP_POST_TYPE,
//             'pending'
//        ) );
        $unpaid = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->prefix}wpbdp_payments ps
             ON (ps.listing_id = p.ID AND ps.status = %s) WHERE p.post_type = %s
             AND p.post_status IN ({$post_statuses}) AND ps.status IS NOT NULL",
             'pending',
             WPBDP_POST_TYPE ) );

        $paid = intval( $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p WHERE p.post_type = %s AND p.post_status IN ({$post_statuses})",
            WPBDP_POST_TYPE ) ) ) - $unpaid;

        $pending_upgrade = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND p.post_status IN ({$post_statuses}) AND ( (pm.meta_key = %s AND pm.meta_value = %s) )",
                                                           WPBDP_POST_TYPE,
                                                           '_wpbdp[sticky]',
                                                           'pending') );
        $expired = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_listings WHERE expiration_date IS NOT NULL AND expiration_date < %s",
                                                   current_time( 'mysql' ) ) );

        $views['paid'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                 esc_url( add_query_arg('wpbdmfilter', 'paid', remove_query_arg('post')) ),
                                 wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'paid' ? 'current' : '',
                                 __('Paid', 'WPBDM'),
                                 number_format_i18n($paid));
        $views['unpaid'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                   esc_url( add_query_arg('wpbdmfilter', 'unpaid', remove_query_arg('post')) ),
                                   wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'unpaid' ? 'current' : '',
                                   __('Unpaid', 'WPBDM'),
                                   number_format_i18n($unpaid));
        $views['featured'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                   esc_url( add_query_arg('wpbdmfilter', 'pendingupgrade', remove_query_arg('post')) ),
                                   wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'pendingupgrade' ? 'current' : '',
                                   __('Pending Upgrade', 'WPBDM'),
                                   number_format_i18n($pending_upgrade));
        $views['expired'] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                     esc_url( add_query_arg( 'wpbdmfilter', 'expired', remove_query_arg( 'post' ) ) ),
                                     wpbdp_getv( $_REQUEST, 'wpbdmfilter' ) == 'expired' ? 'current' : '' ,
                                     _x( 'Expired', 'admin', 'WPBDM' ),
                                     number_format_i18n( $expired )
                                    );

        $views = apply_filters( 'wpbdp_admin_directory_views', $views, $post_statuses );

        return $views;
    }

    function listings_admin_filters( $pieces  ) {
        global $wpdb;

        if ( ! is_admin() || ! isset( $_REQUEST['wpbdmfilter'] ) || ! function_exists( 'get_current_screen' ) || ! get_current_screen() || 'edit-' . WPBDP_POST_TYPE != get_current_screen()->id )
            return $pieces;

        switch ( $_REQUEST['wpbdmfilter'] ) {
            case 'expired':
                $pieces['join'] = " LEFT JOIN {$wpdb->prefix}wpbdp_listings lp ON lp.listing_id = {$wpdb->posts}.ID ";
                $pieces['where'] = $wpdb->prepare( " AND lp.expiration_date IS NOT NULL AND lp.expiration_date < %s ", current_time( 'mysql' ) );
                $pieces['groupby'] = " {$wpdb->posts}.ID ";
                break;
            case 'pendingupgrade':
                $pieces['join'] = " LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = {$wpdb->posts}.ID ";
                $pieces['where'] = $wpdb->prepare( " AND pm.meta_key = %s AND pm.meta_value = %s ", '_wpbdp[sticky]', 'pending' );
                break;
            case 'paid':
                $pieces['where'] .= $wpdb->prepare( " AND NOT EXISTS ( SELECT 1 FROM {$wpdb->prefix}wpbdp_payments WHERE {$wpdb->posts}.ID = {$wpdb->prefix}wpbdp_payments.listing_id AND ( {$wpdb->prefix}wpbdp_payments.status IS NULL OR {$wpdb->prefix}wpbdp_payments.status != %s ) )", 'completed' );
                break;
            case 'unpaid':
                $pieces['join'] .= " LEFT JOIN {$wpdb->prefix}wpbdp_payments ON {$wpdb->posts}.ID = {$wpdb->prefix}wpbdp_payments.listing_id ";
                $pieces['where'] .= $wpdb->prepare( " AND {$wpdb->prefix}wpbdp_payments.status = %s ", 'pending' );
                $pieces['groupby'] .= " {$wpdb->posts}.ID ";
                break;
            default:
                $pieces = apply_filters( 'wpbdp_admin_directory_filter', $pieces, $_REQUEST['wpbdmfilter'] );
                break;
        }

        return $pieces;
    }

    // }}}


    public function row_actions($actions, $post) {
        if ( WPBDP_POST_TYPE != $post->post_type )
            return $actions;

        if (current_user_can('contributor') && ! current_user_can( 'administrator' ) ) {
            if (wpbdp_user_can('edit', $post->ID))
                $actions['edit'] = sprintf('<a href="%s">%s</a>',
                                            wpbdp_url( 'edit_listing', $post->ID ),
                                            _x('Edit Listing', 'admin actions', 'WPBDM'));

            if ( wpbdp_user_can( 'delete', $post->ID ) )
                $actions['delete'] = sprintf( '<a href="%s">%s</a>', wpbdp_url( 'delete_listing', $post->ID ), _x( 'Delete Listing', 'admin actions', 'WPBDM' ) );
        }

        if ( ! current_user_can( 'administrator' ) )
            return $actions;

        $actions['view-payments'] = '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_admin_payments&listing=' . $post->ID ) ) . '">' . _x( 'View Payments', 'admin actions', 'WPBDM' ) . '</a>';

        $listing = wpbdp_get_listing( $post->ID );
        $actions = apply_filters( 'wpbdp_admin_directory_row_actions', $actions, $listing );

        return $actions;
    }

    public function save_fields_and_fee( $post_id ) {
        global $wpdb;

        if ( ! is_admin() || ! isset( $_POST['post_type'] ) || WPBDP_POST_TYPE != $_POST['post_type'] )
            return;

        $listing = WPBDP_Listing::get( $post_id );

        // Set listing plan (if needed).
        $new_plan = $_POST['listing_plan'];
        $current_plan = $listing->get_fee_plan();

        if ( ! $current_plan || (int) $current_plan->fee_id != (int) $new_plan['fee_id'] ) {
            $payment = $listing->set_fee_plan_with_payment( $new_plan['fee_id'] );

            if ( $payment )
                $payment->process_as_admin();
        }

        // Update plan attributes.
        $row = array();
        $row['expiration_date'] = '' == $new_plan['expiration_date'] ? null : $new_plan['expiration_date'];
        $row['fee_images'] = absint( $new_plan['fee_images'] );
        $row['is_sticky'] = ! empty( $new_plan['is_sticky'] ) ? 1 : 0;

        $wpdb->update( $wpdb->prefix . 'wpbdp_listings', $row, array( 'listing_id' => $post_id ) );

        // Update fields.
        $formfields_api = wpbdp_formfields_api();
        $listingfields = wpbdp_getv( $_POST, 'listingfields', array() );

        foreach ( $formfields_api->find_fields( array( 'association' => 'meta' ) ) as $field ) {
            if ( isset( $listingfields[ $field->get_id() ] ) ) {
                $value = $field->convert_input( $listingfields[ $field->get_id() ] );
                $field->store_value( $listing->get_id(), $value );
            } else {
                $field->store_value( $listing->get_id(), $field->convert_input( null ) );
            }
        }

        // Update image information.
        if ( isset( $_POST['thumbnail_id'] ) )
            $listing->set_thumbnail_id( $_POST['thumbnail_id'] );

        // Images info.
        if ( isset( $_POST['images_meta'] ) ) {
            $meta = $_POST['images_meta'];

            foreach ( $meta as $img_id => $img_meta ) {
                update_post_meta( $img_id, '_wpbdp_image_weight', absint( $img_meta[ 'order' ] ) );
                update_post_meta( $img_id, '_wpbdp_image_caption', strval( $img_meta[ 'caption' ] ) );
            }
        }
    }

    public function _add_bulk_actions() {
        if (!current_user_can('administrator'))
            return;

        if ($screen = get_current_screen()) {
            if ($screen->id == 'edit-' . WPBDP_POST_TYPE) {
                if (isset($_GET['post_type']) && $_GET['post_type'] == WPBDP_POST_TYPE) {
                    $bulk_actions = array('sep0' => '--',
                                          'publish' => _x('Publish Listing', 'admin actions', 'WPBDM'),
                                          'sep1' => '--',
                                          'upgradefeatured' => _x('Upgrade to Featured', 'admin actions', 'WPBDM'),
                                          'cancelfeatured' => _x('Downgrade to Normal', 'admin actions', 'WPBDM'),
                                          'sep2' => '--',
                                          'renewlisting' => _x( 'Renew Listing', 'admin actions', 'WPBDM' )
                                         );
                    $bulk_actions = apply_filters( 'wpbdp_admin_directory_bulk_actions', $bulk_actions );

                    // the 'bulk_actions' filter doesn't really work for this until this bug is fixed: http://core.trac.wordpress.org/ticket/16031
                    echo '<script type="text/javascript">';

                    foreach ($bulk_actions as $action => $text) {
                        echo sprintf('jQuery(\'select[name="%s"]\').append(\'<option value="%s" data-uri="%s">%s</option>\');',
                                    'action', 'listing-' . $action, esc_url( add_query_arg('wpbdmaction', $action) ), $text);
                        echo sprintf('jQuery(\'select[name="%s"]\').append(\'<option value="%s" data-uri="%s">%s</option>\');',
                                    'action2', 'listing-' . $action, esc_url( add_query_arg('wpbdmaction', $action) ), $text);
                    }

                    echo '</script>';
                }
            }
        }
    }

    public function _fix_new_links() {
        // 'contributors' should still use the frontend to add listings (editors, authors and admins are allowed to add things directly)
        // XXX: this is kind of hacky but is the best we can do atm, there aren't hooks to change add links
        if (current_user_can('contributor') && isset($_GET['post_type']) && $_GET['post_type'] == WPBDP_POST_TYPE) {
            echo '<script type="text/javascript">';
            echo sprintf('jQuery(\'a.add-new-h2\').attr(\'href\', \'%s\');', wpbdp_get_page_link('add-listing'));
            echo '</script>';
        }
    }

}
