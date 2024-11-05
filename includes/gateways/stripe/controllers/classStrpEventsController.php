<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class WPBDPStrpEventsController {

	/**
	 * @var string
	 */
	public static $events_to_skip_option_name = 'wpbdp_strp_events_to_skip';

	private $event;
	private $invoice;
	private $charge;
	private $status;

	/**
	 * Tell Stripe Connect API that the request came through by flushing early before processing.
	 * Flushing early allows the API to end the request earlier.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	private function flush_response() {
		ob_start();

		// Get the size of the output.
		$size = ob_get_length();

		// Disable compression (in case content length is compressed).
		header( 'Content-Encoding: none' );

		// Set the content length of the response.
		header( 'Content-Length: ' . $size );

		// Close the connection.
		header( 'Connection: close' );

		// Flush all output.
		ob_end_flush();
		@ob_flush();
		flush();
	}

	/**
	 * @return void
	 */
	public function process_connect_events() {
		$this->flush_response();

		$unprocessed_event_ids = WPBDPStrpConnectHelper::get_unprocessed_event_ids();
		if ( $unprocessed_event_ids ) {
			$this->process_event_ids( $unprocessed_event_ids );
		}
		wp_send_json_success();
	}

	/**
	 * @since x.x
	 *
	 * @param array<string> $event_ids
	 * @return void
	 */
	private function process_event_ids( $event_ids ) {
		foreach ( $event_ids as $event_id ) {
			if ( $this->should_skip_event( $event_id ) ) {
				continue;
			}

			set_transient( 'wpbdp_last_process_' . $event_id, time(), 60 );

			$this->event = WPBDPStrpConnectHelper::get_event( $event_id );
			if ( is_object( $this->event ) ) {
				$this->handle_event();
				$this->track_handled_event( $event_id );
				WPBDPStrpConnectHelper::process_event( $event_id );
			} else {
				$this->count_failed_event( $event_id );
			}
		}
	}

	/**
	 * @since x.x
	 *
	 * @param string $event_id
	 * @return bool True if the event should be skipped.
	 */
	private function should_skip_event( $event_id ) {
		if ( $this->last_attempt_to_process_event_is_too_recent( $event_id ) ) {
			return true;
		}

		$option = get_option( self::$events_to_skip_option_name );
		if ( ! is_array( $option ) ) {
			return false;
		}

		return in_array( $event_id, $option, true );
	}

	/**
	 * @param string $event_id
	 * @return bool
	 */
	private function last_attempt_to_process_event_is_too_recent( $event_id ) {
		$last_process_attempt = get_transient( 'wpbdp_last_process_' . $event_id );
		return is_numeric( $last_process_attempt ) && $last_process_attempt > ( time() - 60 );
	}

	/**
	 * @since x.x
	 *
	 * @param string $event_id
	 * @return void
	 */
	private function count_failed_event( $event_id ) {
		$transient_name = 'wpbdp_failed_event_' . $event_id;
		$transient      = get_transient( $transient_name );
		if ( is_int( $transient ) ) {
			$failed_count = $transient + 1;
		} else {
			$failed_count = 1;
		}

		$maximum_retries = 3;
		if ( $failed_count >= $maximum_retries ) {
			$this->track_handled_event( $event_id );
		} else {
			set_transient( $transient_name, $failed_count );
		}
	}

	/**
	 * Track an event to no longer process.
	 * This is called for successful events, and also for failed events after a number of retries.
	 *
	 * @since x.x
	 *
	 * @param string $event_id
	 * @return void
	 */
	private function track_handled_event( $event_id ) {
		$option = get_option( self::$events_to_skip_option_name );

		if ( is_array( $option ) ) {
			if ( count( $option ) > 1000 ) {
				// Prevent the option from getting too big by removing the front item before adding the next.
				array_shift( $option );
			}
		} else {
			$option = array();
		}

		$option[] = $event_id;
		update_option( self::$events_to_skip_option_name, $option, false );
	}

	/**
	 * @return void
	 */
	private function handle_event() {
		$this->invoice = $this->event->data->object;

		try {
			$subscription   = new WPBDP__Listing_Subscription( 0, isset( $this->invoice->subscription ) ? $this->invoice->subscription : 0 );
			$parent_payment = $subscription->get_parent_payment();
		} catch ( Exception $e ) {
			$subscription   = null;
			$parent_payment = null;
		}

		switch ( $this->event->type ) {
			case 'invoice.payment_failed':
				if ( $parent_payment && 'stripe' === $parent_payment->gateway ) {

					$cancel = WPBDPStrpApiHelper::cancel_subscription( $subscription->get_subscription_id() );
					if ( $cancel ) {
						// Mark as canceled in BD.
						$subscription->cancel();
					}
				}
				break;
			case 'invoice.payment_succeeded':
				if ( ! $subscription ) {
					$subscription = $this->maybe_create_listing_subscription();

					if ( $subscription ) {
						$parent_payment = $subscription->get_parent_payment();
					}
				}

				$this->process_payment_succeeded( $subscription, $parent_payment, $invoice );

				break;
			case 'payment_intent.succeeded':
				$this->process_payment_intent();
				break;
		}
	}

	private function maybe_create_listing_subscription() {
		foreach ( $this->invoice->lines->data as $invoice_item ) {
			if ( 'subscription' === $invoice_item->type ) {
				$payment = wpbdp_get_payment( $invoice_item->metadata->wpbdp_payment_id );
				break;
			}
		}

		if ( ! $payment ) {
			return null;
		}

		if ( $this->invoice->charge ) {
			$charge = WPBDPStrpConnectHelper::get_charge( $this->invoice->charge );
			if ( is_object( $charge ) ) {
				$this->save_payer_address( $payment, $charge->billing_details );
			}
		}

		$payment->gateway       = 'stripe';
		$payment->gateway_tx_id = $invoice->id;
		$payment->status        = 'completed';
		$payment->save();

		$this->set_listing_stripe_customer( $payment->listing_id, $this->invoice->customer );
		$subscription = $payment->get_listing()->get_subscription();
		if ( ! $subscription ) {
			return null;
		}

		$subscription->set_subscription_id( $this->invoice->subscription );
		$subscription->record_payment( $payment );

		return $subscription;
	}

	private function set_listing_stripe_customer( $listing_id, $customer_id ) {
		if ( $listing_id && ! empty( $customer_id ) ) {
			update_post_meta( $listing_id, $this->customer_meta_name(), $customer_id );
		}
	}

	/**
	 * The name of the post or user meta, depending on test or live mode.
	 *
	 * @since x.x
	 *
	 * @return string
	 */
	private function customer_meta_name() {
		$test_mode = wpbdp_get_option( 'payments-test-mode' );
		return '_wpbdp_stripe_customer_id' . ( $test_mode ? '_test' : '' );
	}

	private function process_payment_intent() {
		$event = $this->event->data;

		if ( empty( $event->object->id ) || 'manual' === $event->object->confirmation_method ) {
			return;
		}

		// TODO: The function verify_transaction does not appear to exist.
		$checkout = $this->verify_transaction( $event->object );

		if ( ! $checkout ) {
			return;
		}

		$checkout = array_shift( $checkout );
		$payment  = wpbdp_get_payment( $checkout->data->object->client_reference_id );

		if ( ! $payment || 'completed' == $payment->status ) {
			return;
		}

		$payment->gateway = 'stripe';
		$payment->status  = 'completed';

		if ( ! empty( $event->object->charges ) && ! empty( $event->object->charges->data[0] ) ) {
			$charge = $event->object->charges->data[0];
			$this->save_payer_address( $payment, $charge->billing_details );

			$payment->gateway_tx_id = $charge->id;
		} elseif ( ! empty( $event->object->latest_charge ) ) {
			// Fallback to get the charge id from the invoice.
			$payment->gateway_tx_id = $event->object->latest_charge;
		}

		$payment->save();
	}

	private function save_payer_address( $payment, $billing_details ) {
		$payment->payer_first_name      = $billing_details->name;
		$payment->payer_email           = $billing_details->email;
		$payment->payer_data['address'] = $billing_details->address->line1 . ( $billing_details->address->line2 ? ', ' . $billing_details->address->line2 : '' );
		$payment->payer_data['state']   = $billing_details->address->state;
		$payment->payer_data['city']    = $billing_details->address->city;
		$payment->payer_data['country'] = $billing_details->address->country;
		$payment->payer_data['zip']     = $billing_details->address->postal_code;
	}
}
