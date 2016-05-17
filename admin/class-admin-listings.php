<?php

class WPBDP_Admin_Listings {

    function __construct() {
        add_action('admin_init', array($this, 'add_metaboxes'));
        add_action( 'wpbdp_admin_notices', array( $this, 'no_category_edit_notice' ) );

        add_action( 'manage_' . WPBDP_POST_TYPE . '_posts_columns', array( &$this, 'add_columns' ) );
        add_action( 'manage_' . WPBDP_POST_TYPE . '_posts_custom_column', array( &$this, 'listing_column' ), 10, 2 );

        add_filter( 'views_edit-' . WPBDP_POST_TYPE, array( &$this, 'listing_views' ) );
        add_filter( 'posts_clauses', array( &$this, 'listings_admin_filters' ) );

        add_filter( 'post_row_actions', array( &$this, 'row_actions' ), 10, 2 );

        add_action( 'save_post', array( &$this, 'save_post' ) );

        add_action('admin_footer', array($this, '_add_bulk_actions'));
        add_action('admin_footer', array($this, '_fix_new_links'));

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

    function no_category_edit_notice() {
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

        if( ! $listing->get_categories( 'all' ) )
            wpbdp_admin_message( _x( 'This listing doesn\'t have any category assigned. At least one category (and its respective fee) is required in order to determine the features available to this listing, as well as handling renewals.', 'admin listings', 'WPBDM' ), 'error' );
    }

    function add_metaboxes() {
        add_meta_box( 'BusinessDirectory_listinginfo',
                      __( 'Listing Information', 'WPBDM' ),
                      array( 'WPBDP_Admin_Listing_Metabox', 'metabox_callback' ),
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

    // {{{ Custom columns.

    function add_columns( $columns_ ) {
        $custom_columns = array();
        $custom_columns['category'] = _x( 'Categories', 'admin', 'WPBDM' );
        $custom_columns['payment_status'] = __( 'Payment Status', 'WPBDM' );
        $custom_columns['sticky_status'] = __( 'Featured (Sticky) Status', 'WPBDM' );

        $columns = array();

        foreach ( $columns_ as $k => $v ) {
            $columns[ $k ] = $v;

            if ( 'title' == $k )
                $columns = array_merge( $columns, $custom_columns );
        }

        return apply_filters( 'wpbdp_admin_directory_columns', $columns );
    }


    function listing_column( $column, $post_id ) {
        if ( ! method_exists( $this, 'listing_column_' . $column ) )
            return do_action( 'wpbdp_admin_directory_column_' . $column, $post_id );

        call_user_func( array( &$this, 'listing_column_' . $column ), $post_id );
    }

    function listing_column_category( $post_id ) {
        $listing = WPBDP_Listing::get( $post_id );
        $categories = $listing->get_categories( 'all' );

        $i = 0;
        foreach ( $categories as &$category ) {
            print $category->expired ? '<s>' : '';
            printf( '<a href="%s" title="%s">%s</a>',
                    get_term_link( $category->id, WPBDP_CATEGORY_TAX ),
                    $category->expired ? _x( '(Listing expired in this category)', 'admin', 'WPBDM' ) : '',
                    $category->name );
            print $category->expired ? '</s>' : '';
            print ( ( $i + 1 ) != count( $categories ) ? ', ' : '' );

            $i++;
        }
    }

    function listing_column_payment_status( $post_id ) {
        $listing = WPBDP_Listing::get( $post_id );
        $paid_status = $listing->get_payment_status();

        $status_links = '';

        if ( $paid_status != 'ok' )
            $status_links .= sprintf('<span><a href="%s">%s</a></span>',
                                    esc_url( add_query_arg( array( 'wpbdmaction' => 'setaspaid', 'post' => $post_id ) ) ),
                                    __('Paid', 'WPBDM'));

        printf( '<span class="tag paymentstatus %s">%s</span>', $paid_status, strtoupper( $paid_status ) );

        if ( $status_links && current_user_can( 'administrator' ) )
            printf( '<div class="row-actions"><b>%s:</b> %s</div>', __( 'Mark as', 'WPBDM' ), $status_links );
    }

    function listing_column_sticky_status( $post_id ) {
        $upgrades_api = wpbdp_listing_upgrades_api();
        $sticky_info = $upgrades_api->get_info( $post_id );

        echo sprintf('<span class="tag status %s">%s</span><br />',
                    str_replace(' ', '', $sticky_info->status),
                    $sticky_info->pending ? __('Pending Upgrade', 'WPBDM') : esc_attr($sticky_info->level->name) );

        echo '<div class="row-actions">';

        if ( current_user_can('administrator') ) {
            if ( $sticky_info->upgradeable ) {
                echo sprintf('<span><a href="%s">%s</a></span>',
                             esc_url( add_query_arg(array('wpbdmaction' => 'changesticky', 'u' => $sticky_info->upgrade->id, 'post' => $post_id)) ),
                             '<b>↑</b> ' . sprintf(__('Upgrade to %s', 'WPBDM'), esc_attr($sticky_info->upgrade->name)) );
                echo '<br />';
            }

            if ( $sticky_info->downgradeable ) {
                echo sprintf('<span><a href="%s">%s</a></span>',
                             esc_url( add_query_arg(array('wpbdmaction' => 'changesticky', 'u' => $sticky_info->downgrade->id, 'post' => $post_id)) ),
                             '<b>↓</b> ' . sprintf(__('Downgrade to %s', 'WPBDM'), esc_attr($sticky_info->downgrade->name)) );
            }
        } elseif ( current_user_can('contributor') && wpbdp_user_can( 'upgrade-to-sticky', $post_id ) ) {
                echo sprintf('<span><a href="%s"><b>↑</b> %s</a></span>', wpbdp_get_page_link('upgradetostickylisting', $post_id), _x('Upgrade to Featured', 'admin actions', 'WPBDM'));
        }

        echo '</div>';

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
        $expired = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->prefix}wpbdp_listing_fees lf ON lf.listing_id = p.ID WHERE lf.expires_on < %s",
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
                $pieces['join'] = " LEFT JOIN {$wpdb->prefix}wpbdp_listing_fees ON {$wpdb->prefix}wpbdp_listing_fees.listing_id = {$wpdb->posts}.ID ";
                $pieces['where'] = $wpdb->prepare( " AND {$wpdb->prefix}wpbdp_listing_fees.expires_on IS NOT NULL AND {$wpdb->prefix}wpbdp_listing_fees.expires_on < %s ", current_time( 'mysql' ) );
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
        if ($post->post_type == WPBDP_POST_TYPE && current_user_can('contributor') && ! current_user_can( 'administrator' ) ) {
            if (wpbdp_user_can('edit', $post->ID))
                $actions['edit'] = sprintf('<a href="%s">%s</a>',
                                            wpbdp_get_page_link('editlisting', $post->ID),
                                            _x('Edit Listing', 'admin actions', 'WPBDM'));

            if (wpbdp_user_can('delete', $listing_id))
                $actions['delete'] = sprintf('<a href="%s">%s</a>', wpbdp_get_page_link('deletelisting', $listing_id), _x('Delete Listing', 'admin actions', 'WPBDM'));
        }

        return $actions;
    }

    public function save_post($post_id) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;

        // Handle listings saved admin-side.
        if ( is_admin() && isset( $_POST['post_type'] ) && $_POST['post_type'] == WPBDP_POST_TYPE ) {
            $listing = WPBDP_Listing::get( $post_id );

            if ( ! $listing )
                return;

            $listing->fix_categories( true  );

            // Save custom fields.
            //if ( isset( $_POST['wpbdp-listing-fields-nonce'] ) && wp_verify_nonce( $_POST['wpbdp-listing-fields-nonce'], plugin_basename( __FILE__ ) ) )
            if ( isset( $_POST['wpbdp-listing-fields-nonce'] ) ) {
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
                                          'setaspaid' => _x('Mark as Paid', 'admin actions', 'WPBDM'),
                                          'sep3' => '--',
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
