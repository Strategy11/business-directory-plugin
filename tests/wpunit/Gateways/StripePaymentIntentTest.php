<?php
/**
 * Tests for completing payments from Stripe payment_intent.succeeded events.
 */

namespace Gateways;

use ReflectionMethod;
use ReflectionProperty;
use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Payment;
use WPBDPStrpEventsController;

/**
 * Tests for the amount paid on Stripe when completing a payment.
 */
class StripePaymentIntentTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * Checkout session events the fake Stripe Connect server returns.
	 *
	 * @var array
	 */
	private $checkout_events = array();

	/**
	 * @since x.x
	 */
	public function setUp(): void {
		parent::setUp();
		$this->checkout_events = array();
		add_filter( 'pre_http_request', array( $this, 'fake_connect_server' ), 10, 3 );
	}

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'fake_connect_server' ), 10 );
		parent::tearDown();
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider payment_intent_provider
	 *
	 * @param array  $payment_args Payment amount, currency and discount.
	 * @param array  $intent       Payment intent amount_received and currency.
	 * @param array  $session      Checkout session fields, or empty to use the intent metadata.
	 * @param string $status       Expected payment status.
	 */
	public function testPaymentStatusFollowsAmountPaid( $payment_args, $intent, $session, $status ) {
		$payment = $this->create_payment( $payment_args );

		$intent_object = (object) array(
			'id'                  => 'pi_' . $payment->id,
			'object'              => 'payment_intent',
			'confirmation_method' => 'automatic',
			'latest_charge'       => 'ch_' . $payment->id,
			'amount_received'     => $intent['amount'],
			'currency'            => $intent['currency'],
			'metadata'            => (object) array( 'wpbdp_payment_id' => (string) $payment->id ),
		);

		if ( $session ) {
			$this->checkout_events[] = array(
				'type' => 'checkout.session.completed',
				'data' => array(
					'object' => array_merge(
						array(
							'payment_intent'      => $intent_object->id,
							'client_reference_id' => (string) $payment->id,
						),
						$session
					),
				),
			);
		}

		$this->process_payment_intent( $intent_object );

		$this->assertSame( $status, wpbdp_get_payment( $payment->id )->status );
	}

	/**
	 * @since x.x
	 *
	 * @return array
	 */
	public function payment_intent_provider() {
		$usd_100 = array(
			'amount'   => 100,
			'currency' => 'USD',
		);

		return array(
			'intent for the payment total'           => array( $usd_100, $this->intent( 10000 ), array(), 'completed' ),
			'intent for less than the payment total' => array( $usd_100, $this->intent( 100 ), array(), 'pending' ),
			'intent in another currency'             => array( $usd_100, $this->intent( 10000, 'eur' ), array(), 'pending' ),
			'intent for more than the payment total' => array( $usd_100, $this->intent( 20000 ), array(), 'completed' ),
			'session for the payment total'          => array( $usd_100, $this->intent( 10000 ), $this->session( 10000 ), 'completed' ),
			'session for less than the payment total' => array( $usd_100, $this->intent( 100 ), $this->session( 100 ), 'pending' ),
			'session with a currency conversion'     => array(
				$usd_100,
				$this->intent( 9200, 'eur' ),
				array_merge(
					$this->session( 9200, 'eur' ),
					array(
						'currency_conversion' => array(
							'amount_total'    => 10000,
							'source_currency' => 'usd',
						),
					)
				),
				'completed',
			),
			'discounted payment'                     => array(
				array(
					'amount'   => 100,
					'currency' => 'USD',
					'discount' => -20,
				),
				$this->intent( 8000 ),
				$this->session( 8000 ),
				'completed',
			),
			'cents rounding'                         => array(
				array(
					'amount'   => 19.99,
					'currency' => 'USD',
				),
				$this->intent( 1999 ),
				$this->session( 1999 ),
				'completed',
			),
			'zero-decimal currency'                  => array(
				array(
					'amount'   => 1000,
					'currency' => 'JPY',
				),
				$this->intent( 1000, 'jpy' ),
				$this->session( 1000, 'jpy' ),
				'completed',
			),
		);
	}

	/**
	 * @since x.x
	 *
	 * @param int    $amount   Amount in the smallest currency unit.
	 * @param string $currency Currency code.
	 *
	 * @return array
	 */
	private function intent( $amount, $currency = 'usd' ) {
		return compact( 'amount', 'currency' );
	}

	/**
	 * @since x.x
	 *
	 * @param int    $amount_total Amount in the smallest currency unit.
	 * @param string $currency     Currency code.
	 *
	 * @return array
	 */
	private function session( $amount_total, $currency = 'usd' ) {
		return compact( 'amount_total', 'currency' );
	}

	/**
	 * @since x.x
	 *
	 * @param array $args Payment amount, currency and optional discount.
	 *
	 * @return WPBDP_Payment
	 */
	private function create_payment( $args ) {
		$items = array(
			array(
				'type'        => 'charge',
				'description' => 'Plan',
				'amount'      => $args['amount'],
			),
		);

		if ( ! empty( $args['discount'] ) ) {
			$items[] = array(
				'type'        => 'discount_code',
				'description' => 'Discount',
				'amount'      => $args['discount'],
			);
		}

		$payment = new WPBDP_Payment(
			array(
				'listing_id'    => 0,
				'payment_type'  => 'renewal',
				'currency_code' => $args['currency'],
				'payment_items' => $items,
			)
		);
		$payment->save();

		return $payment;
	}

	/**
	 * Run the payment_intent.succeeded handler for a payment intent.
	 *
	 * @since x.x
	 *
	 * @param object $intent Payment intent object.
	 *
	 * @return void
	 */
	private function process_payment_intent( $intent ) {
		$controller = new WPBDPStrpEventsController();

		$property = new ReflectionProperty( $controller, 'invoice' );
		$property->setAccessible( true );
		$property->setValue( $controller, $intent );

		$method = new ReflectionMethod( $controller, 'process_payment_intent' );
		$method->setAccessible( true );
		$method->invoke( $controller );
	}

	/**
	 * Answer Stripe Connect requests locally.
	 *
	 * @since x.x
	 *
	 * @param array|false $response Short-circuit response.
	 * @param array       $args     Request arguments.
	 * @param string      $url      Request URL.
	 *
	 * @return array|false
	 */
	public function fake_connect_server( $response, $args, $url ) {
		if ( 0 !== strpos( $url, 'https://api.businessdirectoryplugin.com/' ) ) {
			return $response;
		}

		$data = new \stdClass();
		if ( 'get_events' === ( $args['body']['wpbdp_strp_connect_action'] ?? '' ) ) {
			$data->events = $this->checkout_events;
		}

		$body = wp_json_encode(
			array(
				'success' => true,
				'data'    => $data,
			)
		);

		return array(
			'body'          => $body,
			'response'      => array( 'code' => 200 ),
			'http_response' => new class( $body ) {
				/**
				 * @var object
				 */
				private $response;

				/**
				 * @param string $body Response body.
				 */
				public function __construct( $body ) {
					$this->response = (object) array( 'body' => $body );
				}

				/**
				 * @return object
				 */
				public function get_response_object() {
					return $this->response;
				}
			},
		);
	}
}
