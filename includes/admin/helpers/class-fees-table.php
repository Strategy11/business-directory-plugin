<?php
/**
 * Class fees table
 *
 * @package Includes/Admin/Helpers
 */

// phpcs:disable

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class WPBDP__Admin__Fees_Table
 *
 * @SuppressWarnings(PHPMD)
 */
class WPBDP__Admin__Fees_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(
            array(
				'singular' => _x( 'fee', 'fees admin', 'WPBDM' ),
				'plural'   => _x( 'fees', 'fees admin', 'WPBDM' ),
				'ajax'     => false,
            )
        );
    }

    public function no_items() {
        if ( 'all' === $this->get_current_view() ) {
            echo str_replace(
                '<a>',
                '<a href="' . admin_url( 'admin.php?page=wpbdp-admin-fees&wpbdp-view=add-fee' ) . '">',
                _x( 'There are no fees right now. You can <a>create one</a>, if you want.', 'fees admin', 'WPBDM' )
            );
            return;
        }

        switch ( $this->get_current_view() ) {
            case 'active':
                $view_name = _x( 'Active', 'fees admin', 'WPBDM' );
                break;
            case 'unavailable':
                $view_name = _x( 'Not Available', 'fees admin', 'WPBDM' );
                break;
            case 'disabled':
                $view_name = _x( 'Disabled', 'fees admin', 'WPBDM' );
                break;
            default:
                $view_name = '';
                break;
        }
        printf(
            str_replace(
                '<a>',
                '<a href="' . admin_url( 'admin.php?page=wpbdp-admin-fees&wpbdp-view=add-fee' ) . '">',
                _x( 'There are no "%s" fees right now. You can <a>create one</a>, if you want.', 'fees admin', 'WPBDM' )
            ),
            $view_name
        );
    }

    public function get_current_view() {
        return wpbdp_getv( $_GET, 'fee_status', 'active' );
    }

    public function get_views() {
        global $wpdb;

        $views = array();

        $all      = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans" ) );
        $non_free = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans WHERE enabled = %d AND tag != %s", 1, 'free' ) ) );
        $disabled = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans WHERE enabled = %d", 0 ) ) );

        $views['all'] = sprintf(
            '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'fee_status', 'all' ) ),
            'all' === $this->get_current_view() ? 'current' : '',
            _x( 'All', 'admin fees table', 'WPBDM' ),
            number_format_i18n( $all )
        );

        if ( ! wpbdp_payments_possible() ) {
            $active = $all - $non_free - $disabled;
        } else {
            $active = $non_free;
        }

        $views['active'] = sprintf(
            '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'fee_status', 'active' ) ),
            'active' === $this->get_current_view() ? 'current' : '',
            _x( 'Active', 'admin fees table', 'WPBDM' ),
            number_format_i18n( $active )
        );

        $unavailable = $all - $active - $disabled;

        $views['unavailable'] = sprintf(
            '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'fee_status', 'unavailable' ) ),
            'unavailable' === $this->get_current_view() ? 'current' : '',
            _x( 'Not Available', 'admin fees table', 'WPBDM' ),
            number_format_i18n( $unavailable )
        );

        $views['disabled'] = sprintf(
            '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'fee_status', 'disabled' ) ),
            'disabled' === $this->get_current_view() ? 'current' : '',
            _x( 'Disabled', 'admin fees table', 'WPBDM' ),
            number_format_i18n( $disabled )
        );

        return $views;
    }

    public function get_columns() {
        $cols = array(
            'label'      => _x( 'Label', 'fees admin', 'WPBDM' ),
            'amount'     => _x( 'Amount', 'fees admin', 'WPBDM' ),
            'duration'   => _x( 'Duration', 'fees admin', 'WPBDM' ),
            'images'     => _x( 'Images', 'fees admin', 'WPBDM' ),
            'attributes' => _x( 'Attributes', 'fees admin', 'WPBDM' ),
        );

        return $cols;
    }

    public function prepare_items() {
        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

        $args = array();

        switch ( $this->get_current_view() ) {
			case 'active':
				$args['enabled']      = 1;
				$args['include_free'] = ! wpbdp_payments_possible();
                break;
			case 'disabled':
				$args['enabled'] = 0;
				$args['tag']     = ''; // FIXME: Without tag = '', you only get disabled free fees.

                break;
			case 'unavailable':
				if ( wpbdp_payments_possible() ) {
					$args['enabled'] = 'all';
					$args['tag']     = 'free';
				} else {
					$args['enabled']      = 1;
					$args['include_free'] = false;
					$args['tag']          = ''; // FIXME: Without tag = '', include_free is ignored.
				}

                break;
			case 'all':
			default:
				$args['enabled']      = 'all';
				$args['include_free'] = true;
				$args['tag']          = ''; // FIXME: Without tag = '', you get only free fees.
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
            echo '<div>';
            _ex(
                'This is the default free plan for your directory.  You can\'t delete it and it\'s always free, but you can edit the name and other settings. It\'s only available when the directory is in Free mode.  You can always create other fee plans, including ones for 0.00 (free) if you wish.',
                'fees admin',
                'WPBDM'
            );
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }

		// if ( $free_mode && $item->amount > 0.0 ) {
		// echo '<tr></tr>';
		// echo '<tr class="wpbdp-item-message-tr">';
		// echo '<td colspan="' . count( $this->get_columns() ) . '">';
		// echo '<div>';
		// _ex( 'Fee plan disabled because directory is in free mode.', 'fees admin', 'WPBDM' );
		// echo '</div>';
		// echo '</td>';
		// echo '</tr>';
		// }
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
                    )
                )
            ),
            esc_url(
                add_query_arg(
                    array(
						'action' => 'feedown',
						'id'     => $fee->id,
                    )
                )
            )
        );
    }

    public function column_label( $fee ) {
        $actions         = array();
        $actions['edit'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(
                add_query_arg(
                    array(
						'wpbdp-view' => 'edit-fee',
						'id'         => $fee->id,
                    )
                )
            ),
            _x( 'Edit', 'fees admin', 'WPBDM' )
        );

        if ( 'free' === $fee->tag ) {
			// $actions['delete'] = sprintf('<a href="%s">%s</a>',
			// esc_url(add_query_arg(array('action' => 'deletefee', 'id' => $fee->id))),
			// _x('Disable', 'fees admin', 'WPBDM'));
        } else {
            if ( $fee->enabled ) {
                $actions['disable'] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url(
                        add_query_arg(
                            array(
								'wpbdp-view' => 'toggle-fee',
								'id'         => $fee->id,
                            )
                        )
                    ),
                    _x( 'Disable', 'fees admin', 'WPBDM' )
                );
            } else {
				$actions['enable'] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url(
                        add_query_arg(
                            array(
								'wpbdp-view' => 'toggle-fee',
								'id'         => $fee->id,
                            )
                        )
                    ),
                    _x( 'Enable', 'fees admin', 'WPBDM' )
				);
            }

            $actions['delete'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(
                    add_query_arg(
                        array(
							'wpbdp-view' => 'delete-fee',
							'id'         => $fee->id,
                        )
                    )
                ),
                _x( 'Delete', 'fees admin', 'WPBDM' )
            );
        }

        $html  = '';
        $html .= sprintf(
            '<span class="wpbdp-drag-handle" data-fee-id="%s"></span></a>',
            $fee->id
        );

        $fee_id_string = _x( '<strong>Fee ID:</strong> <fee-id>', 'fees admin', 'WPBDM' );
        $fee_id_string = str_replace( '<fee-id>', $fee->id, $fee_id_string );

        $html .= sprintf(
            '<strong><a href="%s">%s</a></strong><br/>%s',
            esc_url(
                add_query_arg(
                    array(
						'wpbdp-view' => 'edit-fee',
						'id'         => $fee->id,
                    )
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
            return _x( 'Variable', 'fees admin', 'WPBDM' );
        } elseif ( 'extra' === $fee->pricing_model ) {
            $amount = wpbdp_currency_format( $fee->amount );
            $extra  = wpbdp_currency_format( $fee->pricing_details['extra'] );

            return sprintf( _x( '%1$s + %2$s per category', 'fees admin', 'WPBDM' ), $amount, $extra );
        }

        return wpbdp_currency_format( $fee->amount );
    }

    public function column_duration( $fee ) {
        if ( $fee->days === 0 ) {
            return _x( 'Forever', 'fees admin', 'WPBDM' );
        }
        return sprintf( _nx( '%d day', '%d days', $fee->days, 'fees admin', 'WPBDM' ), $fee->days );
    }

    public function column_images( $fee ) {
        return sprintf( _nx( '%d image', '%d images', $fee->images, 'fees admin', 'WPBDM' ), $fee->images );
    }

    public function column_categories( $fee ) {
        if ( $fee->categories['all'] ) {
            return _x( 'All categories', 'fees admin', 'WPBDM' );
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
                $html .= _x( 'Disabled', 'fees admin', 'WPBDM' );
            } elseif ( ( ! wpbdp_payments_possible() && 'free' !== $fee->tag ) || ( wpbdp_payments_possible() && 'free' === $fee->tag ) ) {
                $html .= _x( 'Unavailable', 'fees admin', 'WPBDM' );
            } else {
                $html .= _x( 'Active', 'fees admin', 'WPBDM' );
            }

            $html .= '</span>';
        }

        if ( $fee->sticky ) {
            $html .= '<span class="wpbdp-tag">' . _x( 'Sticky', 'fees admin', 'WPBDM' ) . '</span>';
        }

        if ( $fee->recurring ) {
            $html .= '<span class="wpbdp-tag">' . _x( 'Recurring', 'fees admin', 'WPBDM' ) . '</span>';
        }

        if ( ! empty( $fee->extra_data['private'] ) ) {
            $html .= '<span class="wpbdp-tag">' . _x( 'Private', 'fees admin', 'WPBDM' ) . '</span>';
        }

        return $html;
    }

}
