<?php
/**
 * Class Fee Plan Creates, Updates and Deletes Directory Plans
 *
 * @package BDP/Includes
 */

// phpcs:disable

/**
 * @since 5.0
 *
 * @SuppressWarnings(PHPMD)
 */
final class WPBDP__Fee_Plan {

    private $id = 0;

    private $label       = '';
    private $description = '';
    private $amount      = 0.0;
    private $days        = 0;
    private $images      = 0;
    private $enabled     = true;

    private $sticky    = false;
    private $recurring = false;

    private $pricing_model   = 'flat';
    private $pricing_details = array();

    private $supported_categories = 'all';

    private $weight     = 0;
    private $tag        = '';
    private $extra_data = array();


    public function __construct( $data = array() ) {
        if ( $data ) {
            $this->setup_plan( $data );
        }
    }

    public function &__get( $key ) {
        if ( method_exists( $this, 'get_' . $key ) ) {
            $value = call_user_func( array( $this, 'get_' . $key ) );
        } else {
            $value = &$this->{$key};
        }

        return $value;
    }

    public function __set( $key, $value ) {
        $this->{$key} = $value;
    }

    public function __isset( $key ) {
        if ( property_exists( $this, $key ) ) {
            return false === empty( $this->{$key} );
        } else {
            return null;
        }
    }

    public function exists() {
        return ! empty( $this->id );
    }

    public function save( $fire_hooks = true ) {
        global $wpdb;

        // Validate.
        $validation_errors = $this->validate();

        if ( ! empty( $validation_errors ) ) {
            $error = new WP_Error();

            foreach ( $validation_errors as $col => $msg ) {
                $error->add( 'validation_error', $msg, array( 'field' => $col ) );
            }

            return $error;
        }

        if ( $fire_hooks ) {
            do_action_ref_array( 'wpbdp_fee_before_save', array( $this ) );
        }

        $row = array();
        foreach ( get_object_vars( $this ) as $key => $value ) {
            $row[ $key ] = $value;
        }

        if ( ! $this->exists() ) {
            unset( $row['id'] );
        }

        $row['pricing_details'] = serialize( $row['pricing_details'] );

        if ( 'all' !== $row['supported_categories'] ) {
            $row['supported_categories'] = implode( ',', $row['supported_categories'] );
        }

        if ( empty( $row['extra_data'] ) ) {
            unset( $row['extra_data'] );
        } else {
            $row['extra_data'] = serialize( $row['extra_data'] );
        }

        $saved  = false;
        $update = $this->exists();
        if ( $update ) {
            $saved = $wpdb->update( $wpdb->prefix . 'wpbdp_plans', $row, array( 'id' => $this->id ) );
        } else {
            $saved = $wpdb->insert( $wpdb->prefix . 'wpbdp_plans', $row );

            if ( $saved ) {
                $this->id = $wpdb->insert_id;
            }
        }

        if ( $saved ) {
            if ( $fire_hooks ) {
                do_action( 'wpbdp_fee_save', $this, $update );
            }

            $wpdb->update(
                $wpdb->prefix . 'wpbdp_listings',
                array( 'is_sticky' => $this->sticky ? 1 : 0 ),
                array(
                    'fee_id' => $this->id,
                )
            );
        }

        return $saved;
    }

    public function update( $data ) {
        unset( $data['id'] );
        $this->setup_plan( $data );
        return $this->save();
    }

    public function delete() {
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . 'wpbdp_plans', array( 'id' => $this->id ) );
    }

    public function supports_category( $category_id ) {
        return $this->supports_category_selection( array( $category_id ) );
    }

    /**
     * @since 5.0
     */
    public function get_feature_list() {
        $items = array();

        if ( wpbdp_get_option( 'allow-images' ) ) {
            if ( ! $this->images ) {
                $items['images'] = _x( 'No images allowed.', 'fee plan', 'WPBDM' );
            } else {
				$items['images'] = sprintf( _nx( '%d image allowed.', '%d images allowed.', $this->images, 'fee plan', 'WPBDM' ), $this->images );
            }
        }

        $items = apply_filters( 'wpbdp_plan_feature_list', $items, $this );
        return $items;
    }

    /**
     * @since 5.0
     */
    public function calculate_amount( $categories = array() ) {
        $amount       = 0.0;
        $pricing_info = $this->pricing_details;

        switch ( $this->pricing_model ) {
			case 'variable':
				$amount = array_sum( wp_array_slice_assoc( $pricing_info, $categories ) );
                break;
			case 'extra':
				$amount = $this->amount + ( $pricing_info['extra'] * count( $categories ) );
                break;
			case 'flat':
			default:
				$amount = $this->amount;
                break;
        }

        return $amount;
    }

    /**
     * @since 5.0
     */
    public function supports_category_selection( $categories = array() ) {
        if ( ! $categories ) {
            return true;
        }

        if ( is_string( $this->supported_categories ) && 'all' === $this->supported_categories ) {
            return true;
        }

        if ( array_diff( $categories, $this->supported_categories ) ) {
            return false;
        }

        return true;
    }

    public static function get_instance( $fee_id ) {
        global $wpdb;

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_plans WHERE id = %d", $fee_id ), ARRAY_A );

        if ( ! $row ) {
            return false;
        }

        if ( 'all' !== $row['supported_categories'] ) {
            $row['supported_categories'] = array_map( 'absint', explode( ',', $row['supported_categories'] ) );
        }

        $row['pricing_details'] = maybe_unserialize( $row['pricing_details'] );
        $row['extra_data']      = maybe_unserialize( $row['extra_data'] );

        $instance = new self( $row );
        return $instance;
    }

    /**
     * @since 5.0
     */
    public function calculate_expiration_time( $base_time = null ) {
        if ( ! $base_time ) {
            $base_time = current_time( 'timestamp' );
        }

        if ( 0 === $this->days ) {
            return null;
        }

        $expire_time = strtotime( sprintf( '+%d days', $this->days ), $base_time );
        return date( 'Y-m-d H:i:s', $expire_time );
    }

    private function setup_plan( $data ) {
        if ( is_object( $data ) ) {
            $data = get_object_vars( $data );
        }

        foreach ( $data as $key => $value ) {
            $this->{$key} = $value;
        }

        $this->sanitize();
    }

    private function sanitize() {
        $this->label         = trim( $this->label );
        $this->amount        = floatval( trim( $this->amount ) );
        $this->days          = absint( $this->days );
        $this->images        = absint( $this->images );
        $this->sticky        = (bool) $this->sticky;
        $this->recurring     = (bool) $this->recurring;
        $this->pricing_model = empty( $this->pricing_model ) ? 'flat' : $this->pricing_model;
        $this->tag           = strtolower( trim( $this->tag ) );

        if ( 'all' !== $this->supported_categories ) {
            $this->supported_categories = array_filter( array_map( 'absint', (array) $this->supported_categories ), array( $this, 'sanitize_category' ) );
        }

        if ( empty( $this->supported_categories ) ) {
            $this->supported_categories = 'all';
        }

        if ( 'extra' === $this->pricing_model ) {
            $this->pricing_details = array(
                'extra' => floatval( $this->pricing_details['extra'] ),
            );
        } else {
            unset( $this->pricing_details['extra'] );
        }

        // Unset details for categories that are not supported.
        if ( 'variable' === $this->pricing_model ) {
            $this->amount = 0.0;

            if ( 'all' !== $this->supported_categories ) {
                $this->pricing_details = wp_array_slice_assoc( $this->pricing_details, $this->supported_categories );
            }
        }

        if ( 'flat' === $this->pricing_model ) {
            $this->pricing_details = array();
        }

        // Free plan is special.
        if ( 'free' === $this->tag ) {
            $this->pricing_model        = 'flat';
            $this->amount               = 0.0;
            $this->sticky               = false;
            $this->recurring            = false;
            $this->supported_categories = 'all';
            $this->enabled              = true;
        }
    }

    private function validate() {
        $this->sanitize();

        $errors = array();

        if ( ! $this->label ) {
            $errors['label'] = _x( 'Fee label is required.', 'fees-api', 'WPBDM' );
        }

        // limit 'duration' because of TIMESTAMP limited range (issue #157).
        // FIXME: this is not a long-term fix. we should move to DATETIME to avoid this entirely.
        if ( $this->days > 3650 ) {
            $errors['days'] = _x( 'Fee listing duration must be a number less than 10 years (3650 days).', 'fees-api', 'WPBDM' );
        }

        if ( 1 == $this->recurring ) {
            if ( 0 === $this->days ) {
                $errors[] = str_replace( '<a>', '<a href="#wpbdp-fee-form-days">', _x( 'To set this fee as "Recurring" you must have a time for the listing to renew (e.g. 30 days). To avoid issues with the listing, please edit the <a>fee plan</a> appropriately.', 'fees-api', 'WPBDM' ) );
            }

            $error_message = _x( 'To set this fee as "Recurring" you must set a price for your fee plan. To avoid issues with the listing, please edit the <a>fee plan</a> appropriately.', 'fees-api', 'WPBDM' );

            if ( 'flat' === $this->pricing_model && 0 == $this->amount ) {
                $errors[] = str_replace( '<a>', '<a href="#wpbdp-fee-form-fee-price">', $error_message );
            }

            if ( 'variable' === $this->pricing_model && 0 === array_sum( $this->pricing_details ) ) {
                $errors[] = str_replace( '<a>', '<a href="#wpbdp-fee-form-fee-category">', $error_message );
            }

            if ( 'extra' === $this->pricing_model && 0 === $this->amount + $this->pricing_details['extra'] ) {
                $errors[] = str_replace( '<a>', '<a href="#wpbdp-fee-form-fee-price">', $error_message );
            }
        }

        return $errors;
    }

    private function sanitize_category( $category_id ) {
        $category = get_term( absint( $category_id ), WPBDP_CATEGORY_TAX );
        return $category && ! is_wp_error( $category );

    }
}

require_once WPBDP_INC . 'compatibility/deprecated/class-fee-plan.php';
