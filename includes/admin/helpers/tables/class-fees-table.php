<?php
/**
 * Class fees table
 *
 * @package Includes/Admin/Helpers
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class WPBDP__Admin__Fees_Table
 */
class WPBDP__Admin__Fees_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(
            array(
				'singular' => _x( 'fee', 'fees admin', 'business-directory-plugin' ),
				'plural'   => _x( 'fees', 'fees admin', 'business-directory-plugin' ),
				'ajax'     => false,
            )
        );
    }

    public function no_items() {
        if ( 'all' === $this->get_current_view() ) {
            echo str_replace(
                '<a>',
                '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp-admin-fees&wpbdp-view=add-fee' ) ) . '">',
                _x( 'There are no fees right now. You can <a>create one</a>, if you want.', 'fees admin', 'business-directory-plugin' )
            );
            return;
        }

        switch ( $this->get_current_view() ) {
            case 'active':
                $view_name = _x( 'Active', 'fees admin', 'business-directory-plugin' );
                break;
            case 'disabled':
                $view_name = _x( 'Disabled', 'fees admin', 'business-directory-plugin' );
                break;
            default:
                $view_name = '';
                break;
        }
        printf(
            str_replace(
                '<a>',
                '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp-admin-fees&wpbdp-view=add-fee' ) ) . '">',
                _x( 'There are no "%s" fees right now. You can <a>create one</a>, if you want.', 'fees admin', 'business-directory-plugin' )
            ),
            $view_name
        );
    }

    public function get_current_view() {
		return wpbdp_get_var( array( 'param' => 'fee_status', 'default' => 'active' ) );
    }

    public function get_views() {
        global $wpdb;

        $admin_fees_url = admin_url( 'admin.php?page=wpbdp-admin-fees' );

        $views = array();

        $all      = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans" ) );
        $non_free = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans WHERE enabled = %d AND tag != %s", 1, 'free' ) ) );
        $disabled = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans WHERE enabled = %d", 0 ) ) );

        $views['all'] = sprintf(
            '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'fee_status', 'all', $admin_fees_url ) ),
            'all' === $this->get_current_view() ? 'current' : '',
            _x( 'All', 'admin fees table', 'business-directory-plugin' ),
            number_format_i18n( $all )
        );

        if ( ! wpbdp_payments_possible() ) {
            $active = $all - $disabled;
        } else {
            $active = $non_free;
        }

        $views['active'] = sprintf(
            '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'fee_status', 'active', $admin_fees_url ) ),
            'active' === $this->get_current_view() ? 'current' : '',
            _x( 'Active', 'admin fees table', 'business-directory-plugin' ),
            number_format_i18n( $active )
        );

        $views['disabled'] = sprintf(
            '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'fee_status', 'disabled', $admin_fees_url ) ),
            'disabled' === $this->get_current_view() ? 'current' : '',
            _x( 'Disabled', 'admin fees table', 'business-directory-plugin' ),
            number_format_i18n( $disabled )
        );

        return $views;
    }

    public function get_columns() {
        $cols = array(
            'label'      => _x( 'Label', 'fees admin', 'business-directory-plugin' ),
            'amount'     => __( 'Amount', 'business-directory-plugin' ),
            'duration'   => _x( 'Duration', 'fees admin', 'business-directory-plugin' ),
            'images'     => __( 'Images', 'business-directory-plugin' ),
            'attributes' => _x( 'Attributes', 'fees admin', 'business-directory-plugin' ),
        );

        return $cols;
    }

    public function prepare_items() {
        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

        $args = array(
			'admin_view'   => true, // Admin view shows all listings
			'enabled'      => 'all',
			'include_free' => true,
			'tag'          => '',
        );

        switch ( $this->get_current_view() ) {
			case 'active':
				$args['enabled']      = 1;
				$args['include_free'] = ! wpbdp_payments_possible();
                break;
			case 'disabled':
				$args['enabled'] = 0;
                break;
        }

        $this->items = wpbdp_get_fee_plans( $args );
    }

    /** Rows **/
    public function single_row( $item ) {
        $free_mode = ( ! wpbdp_payments_possible() );
        $classes   = '';

        if ( $free_mode && $item->amount > 0.0 ) {
            $classes .= 'disabled-fee';
        } elseif ( 'free' === $item->tag ) {
            $classes .= 'free-fee';
        }

        echo '<tr class="' . $classes . '">';
        $this->single_row_columns( $item );
        echo '</tr>';

        if ( 'free' === $item->tag ) {
            echo '<tr class="free-fee-related-tr"></tr>';
            echo '<tr class="wpbdp-item-message-tr free-fee-related-tr">';
            echo '<td colspan="' . count( $this->get_columns() ) . '">';

            echo '</td>';
            echo '</tr>';
        }
    }

    public function column_order( $fee ) {
        return sprintf(
            '<span class="wpbdp-drag-handle" data-fee-id="%s"></span> <a href="%s"><strong>↑</strong></a> | <a href="%s"><strong>↓</strong></a>',
            $fee->id,
            esc_url(
                add_query_arg(
                    array(
						'action' => 'feeup',
						'id'     => $fee->id,
                    ),
                    admin_url( 'admin.php?page=wpbdp-admin-fees' )
                )
            ),
            esc_url(
                add_query_arg(
                    array(
						'action' => 'feedown',
						'id'     => $fee->id,
                    ),
                    admin_url( 'admin.php?page=wpbdp-admin-fees' )
                )
            )
        );
    }

    public function column_label( $fee ) {
        $admin_fees_url = admin_url( 'admin.php?page=wpbdp-admin-fees' );
        $actions         = array();
        $actions['edit'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(
                add_query_arg(
                    array(
						'wpbdp-view' => 'edit-fee',
						'id'         => $fee->id,
                    ),
                    $admin_fees_url
                )
            ),
            _x( 'Edit', 'fees admin', 'business-directory-plugin' )
        );


		$toggle_url = add_query_arg(
			array(
				'wpbdp-view' => 'toggle-fee',
				'id'         => $fee->id,
			),
			$admin_fees_url
		);

		if ( $fee->enabled ) {
			$actions['disable'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $toggle_url ),
				esc_html__( 'Disable', 'business-directory-plugin' )
			);
		} else {
			$actions['enable'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $toggle_url ),
				esc_html__( 'Enable', 'business-directory-plugin' )
			);
		}

		if ( 'free' !== $fee->tag ) {
            $actions['delete'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(
                    add_query_arg(
                        array(
							'wpbdp-view' => 'delete-fee',
							'id'         => $fee->id,
                        ),
                        $admin_fees_url
                    )
                ),
                esc_html__( 'Delete', 'business-directory-plugin' )
            );
        }

        $html  = '';
        $html .= sprintf(
            '<span class="wpbdp-drag-handle" data-fee-id="%s"></span></a>',
            $fee->id
        );

        $fee_id_string = _x( '<strong>Fee ID:</strong> <fee-id>', 'fees admin', 'business-directory-plugin' );
        $fee_id_string = str_replace( '<fee-id>', $fee->id, $fee_id_string );

        $html .= sprintf(
            '<strong><a href="%s">%s</a></strong><br/>%s',
            esc_url(
                add_query_arg(
                    array(
						'wpbdp-view' => 'edit-fee',
						'id'         => $fee->id,
                    ),
                    $admin_fees_url
                )
            ),
            esc_attr( $fee->label ),
            $fee_id_string
        );
        $html .= $this->row_actions( $actions );

        return $html;
    }

    public function column_amount( $fee ) {
        if ( 'variable' === $fee->pricing_model ) {
            return _x( 'Variable', 'fees admin', 'business-directory-plugin' );
        } elseif ( 'extra' === $fee->pricing_model ) {
            $amount = wpbdp_currency_format( $fee->amount );
            $extra  = wpbdp_currency_format( $fee->pricing_details['extra'] );

            return sprintf( _x( '%1$s + %2$s per category', 'fees admin', 'business-directory-plugin' ), $amount, $extra );
        }

        return wpbdp_currency_format( $fee->amount );
    }

    public function column_duration( $fee ) {
        if ( $fee->days === 0 ) {
            return _x( 'Forever', 'fees admin', 'business-directory-plugin' );
        }
        return sprintf( _nx( '%d day', '%d days', $fee->days, 'fees admin', 'business-directory-plugin' ), $fee->days );
    }

    public function column_images( $fee ) {
        return sprintf( _nx( '%d image', '%d images', $fee->images, 'fees admin', 'business-directory-plugin' ), $fee->images );
    }

    public function column_categories( $fee ) {
        if ( $fee->categories['all'] ) {
            return _x( 'All categories', 'fees admin', 'business-directory-plugin' );
        }

        $names = array();

        foreach ( $fee->categories['categories'] as $category_id ) {
            $category = get_term( $category_id, wpbdp()->get_post_type_category() );
            if ( $category ) {
                $names[] = $category->name;
            }
        }

        return $names ? join( $names, ', ' ) : '--';
    }

    public function column_attributes( $fee ) {
        $html = '';

        if ( 'all' === $this->get_current_view() ) {
            $html .= '<span class="wpbdp-tag">';

			if ( ! $fee->enabled ) {
				$html .= __( 'Disabled', 'business-directory-plugin' );
			} elseif ( ( ! wpbdp_payments_possible() && 'free' !== $fee->tag ) || ( wpbdp_payments_possible() && 'free' === $fee->tag ) ) {
				$html .= __( 'Disabled', 'business-directory-plugin' );
			} else {
				$html .= __( 'Active', 'business-directory-plugin' );
			}

            $html .= '</span>';
        }

        if ( $fee->sticky ) {
            $html .= '<span class="wpbdp-tag">' . _x( 'Sticky', 'fees admin', 'business-directory-plugin' ) . '</span>';
        }

        if ( $fee->recurring ) {
            $html .= '<span class="wpbdp-tag">' . _x( 'Recurring', 'fees admin', 'business-directory-plugin' ) . '</span>';
        }

        if ( ! empty( $fee->extra_data['private'] ) ) {
            $html .= '<span class="wpbdp-tag">' . _x( 'Private', 'fees admin', 'business-directory-plugin' ) . '</span>';
        }

        return $html;
    }

}
