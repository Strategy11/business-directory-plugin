<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Handles shared Stripe subscription logic between WPBDPStrpActionsController and WPBDPStrpLinkController.
 *
 * @since x.x
 */
class WPBDPStrpSubscriptionHelper {

	/**
	 * Prepare a charge object for a Stripe subscription.
	 *
	 * @since x.x
	 * @todo I removed the $charge_object->paid = false; line from here is it isn't required for Stripe link.
	 *       Make sure that if/when we re-use this in Stripe that we still include that.
	 *
	 * @param object $subscription A Stripe Subscription object.
	 * @param string $amount
	 * @return stdClass
	 */
	public static function prepare_charge_object_for_subscription( $subscription, $amount ) {
		$charge_object                       = new stdClass();
		$charge_object->sub_id               = $subscription->id;
		$charge_object->id                   = null;
		$charge_object->amount               = $amount;
		$charge_object->current_period_start = $subscription->current_period_start;
		$charge_object->current_period_end   = $subscription->current_period_end;
		return $charge_object;
	}

	/**
	 * Create a subscription object with the nested payments submodule.
	 *
	 * @since x.x
	 *
	 * @param array $atts
	 * @return string|int $sub_id
	 */
	public static function create_new_subscription( $atts ) {
		$atts['charge'] = (object) $atts['charge'];

		$new_values = array(
			'amount'         => WPBDPStrpAppHelper::get_formatted_amount_for_currency( $atts['charge']->amount, $atts['action'] ),
			'paysys'         => 'stripe',
			'item_id'        => $atts['entry']->id,
			'action_id'      => $atts['action']->ID,
			'sub_id'         => isset( $atts['charge']->sub_id ) ? $atts['charge']->sub_id : '',
			'interval_count' => $atts['action']->post_content['interval_count'],
			'time_interval'  => $atts['action']->post_content['interval'],
			'status'         => 'active',
			'next_bill_date' => gmdate( 'Y-m-d' ),
		);

		$wpbdp_sub = new WPBDPStrpSubscription();
		$sub_id  = $wpbdp_sub->create( $new_values );
		return $sub_id;
	}

	/**
	 * Get a plan for Stripe subscription.
	 *
	 * @since x.x
	 *
	 * @param array $atts {
	 *    @type WP_Post $action
	 *    @type string  $amount
	 * }
	 * @return string Plan id.
	 */
	public static function get_plan_from_atts( $atts ) {
		$action                         = $atts['action'];
		$action->post_content['amount'] = $atts['amount'];
		return self::get_plan_for_action( $action );
	}

	/**
	 * @since x.x
	 *
	 * @param WP_Post $action
	 * @return string|false
	 */
	private static function get_plan_for_action( $action ) {
		$plan_id = $action->post_content['plan_id'];
		if ( ! $plan_id ) {
			// the amount has already been formatted, so add the decimal back in
			$amount                         = $action->post_content['amount'];
			$action->post_content['amount'] = number_format( ( $amount / 100 ), 2, '.', '' );
			$plan_opts                      = self::prepare_plan_options( $action->post_content );
			$plan_id                        = WPBDPStrpConnectHelper::maybe_create_plan( $plan_opts );
		}
		return $plan_id;
	}

	/**
	 * @since x.x
	 *
	 * @param array $settings
	 * @return array
	 */
	public static function prepare_plan_options( $settings ) {
		$amount              = self::prepare_amount( $settings['amount'], $settings );
		$default_description = number_format( ( $amount / 100 ), 2 ) . '/' . $settings['interval'];
		$plan_opts           = array(
			'amount'         => $amount,
			'interval'       => $settings['interval'],
			'interval_count' => $settings['interval_count'],
			'currency'       => $settings['currency'],
			'name'           => empty( $settings['description'] ) ? $default_description : $settings['description'],
		);

		if ( ! empty( $settings['trial_interval_count'] ) ) {
			$plan_opts['trial_period_days'] = self::get_trial_with_default( $settings['trial_interval_count'] );
		}

		$plan_opts['id'] = self::create_plan_id( $settings );

		return $plan_opts;
	}

	/**
	 * Include settings in the plan description in order to make sure the correct plan is used.
	 *
	 * @param array $settings
	 * @return string
	 */
	public static function create_plan_id( $settings ) {
		$amount = self::prepare_amount( $settings['amount'], $settings );
		$id     = sanitize_title_with_dashes( $settings['description'] ) . '_' . $amount . '_' . $settings['interval_count'] . $settings['interval'] . '_' . $settings['currency'];
		return $id;
	}

	/**
	 * Convert the amount from 10.00 to 1000.
	 *
	 * @param mixed $amount
	 * @param array $atts
	 * @return string
	 */
	public static function prepare_amount( $amount, $atts = array() ) {
		return number_format( $amount, 2, '', '' );
	}

	/**
	 * Since the trial period can come from an entry, use a default value
	 * when creating the plan. This is overridden when the subscription
	 * is created.
	 *
	 * @since x.x
	 *
	 * @param mixed $trial
	 * @return int
	 */
	private static function get_trial_with_default( $trial ) {
		if ( ! is_numeric( $trial ) ) {
			$trial = 1;
		}
		return absint( $trial );
	}

	/**
	 * If a subscription fails because the plan does not exist, create the plan and try again.
	 *
	 * @since x.x
	 *
	 * @param object|string|false $subscription
	 * @param array               $charge_data
	 * @param WP_Post             $action
	 * @param int                 $amount
	 * @return object|string|false
	 */
	public static function maybe_create_missing_plan_and_create_subscription( $subscription, $charge_data, $action, $amount ) {
		if ( ! is_string( $subscription ) || 0 !== strpos( $subscription, 'No such plan: ' ) ) {
			// Only retry when there is a No such plan string error.
			return $subscription;
		}

		// The full error message looks like "No such plan: '_399_1month_usd".
		$action->post_content['plan_id'] = '';
		$charge_data['plan']             = self::get_plan_from_atts( compact( 'action', 'amount' ) );
		$subscription                    = WPBDPStrpApiHelper::create_subscription( $charge_data );
		return $subscription;
	}
}
