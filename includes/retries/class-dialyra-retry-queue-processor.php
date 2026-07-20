<?php

/**
 * Dialyra retry queue processor.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/retries
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Retry_Queue_Processor {

	/**
	 * Retry repository.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Retry_Repository
	 */
	private $retry_repository;

	/**
	 * Retry policy.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Retry_Policy
	 */
	private $retry_policy;

	/**
	 * Call eligibility service.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Call_Eligibility
	 */
	private $eligibility;

	/**
	 * Business hours service.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Business_Hours
	 */
	private $business_hours;

	/**
	 * Call originate service.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Call_Originate_Service
	 */
	private $call_originate_service;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_Retry_Repository        $retry_repository         Retry repository.
	 * @param    Dialyra_Retry_Policy            $retry_policy             Retry policy.
	 * @param    Dialyra_Call_Eligibility        $eligibility              Eligibility service.
	 * @param    Dialyra_Business_Hours          $business_hours           Business hours service.
	 * @param    Dialyra_Call_Originate_Service  $call_originate_service   Call originate service.
	 */
	public function __construct( $retry_repository, $retry_policy, $eligibility, $business_hours, $call_originate_service ) {
		$this->retry_repository       = $retry_repository;
		$this->retry_policy           = $retry_policy;
		$this->eligibility            = $eligibility;
		$this->business_hours         = $business_hours;
		$this->call_originate_service = $call_originate_service;
	}

	/**
	 * Process due retry queue records.
	 *
	 * @since    1.0.0
	 * @param    int    $limit    Maximum records to process.
	 * @return   array
	 */
	public function process_due_queue( $limit = 10 ) {
		$results = array(
			'processed' => 0,
			'completed' => 0,
			'cancelled' => 0,
			'scheduled' => 0,
			'exhausted' => 0,
			'failed'    => 0,
		);

		$delay_minutes = $this->retry_policy->is_enabled() ? $this->retry_policy->get_delay_minutes() : 0;
		$rows          = $this->retry_repository->get_due_items( $delay_minutes, $limit );

		foreach ( $rows as $row ) {
			$retry_id = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
			$order_id = isset( $row['order_id'] ) ? absint( $row['order_id'] ) : 0;

			if ( ! $retry_id || ! $order_id || ! $this->retry_repository->claim( $retry_id ) ) {
				continue;
			}

			$results['processed']++;

			$outcome = $this->process_claimed_row( $retry_id, $order_id, $row );

			if ( isset( $results[ $outcome ] ) ) {
				$results[ $outcome ]++;
			}
		}

		return $results;
	}

	/**
	 * Process the next batch of due retry items.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function process_next() {
		return $this->process_due_queue( 10 );
	}

	/**
	 * Process one retry queue record by ID.
	 *
	 * @since    1.0.0
	 * @param    int    $retry_id    Retry queue ID.
	 * @return   string
	 */
	public function process_retry_item( $retry_id ) {
		$retry_id = absint( $retry_id );
		$row      = method_exists( $this->retry_repository, 'get_by_id' ) ? $this->retry_repository->get_by_id( $retry_id ) : array();
		$status   = sanitize_key( $row['status'] ?? '' );

		if ( empty( $row ) || ! in_array( $status, array( 'pending', 'scheduled' ), true ) ) {
			return 'not_found';
		}

		$order_id = absint( $row['order_id'] ?? 0 );

		if ( ! $order_id || ! $this->retry_repository->claim( $retry_id ) ) {
			return 'not_claimed';
		}

		return $this->process_claimed_row( $retry_id, $order_id, $row );
	}

	/**
	 * Process one claimed retry row.
	 *
	 * @since    1.0.0
	 * @param    int      $retry_id    Retry queue ID.
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    array    $row         Retry row.
	 * @return   string
	 */
	private function process_claimed_row( $retry_id, $order_id, $row ) {
		if ( ! $this->retry_policy->is_enabled() ) {
			$this->retry_repository->mark_cancelled( $retry_id );
			return 'cancelled';
		}

		$max_attempts  = $this->retry_policy->get_max_attempts();
		$attempt_count = isset( $row['attempt_count'] ) ? absint( $row['attempt_count'] ) : 0;

		if ( $attempt_count >= $max_attempts ) {
			$this->retry_repository->mark_exhausted( $retry_id );
			return 'exhausted';
		}

		$eligibility = $this->eligibility->can_call_order( $order_id );

		if ( empty( $eligibility['eligible'] ) ) {
			if ( 'active_call_exists' === ( $eligibility['reason'] ?? '' ) ) {
				$this->retry_repository->schedule_next( $retry_id, $this->get_delay_retry_time() );
				return 'scheduled';
			}

			$this->retry_repository->mark_cancelled( $retry_id );
			return 'cancelled';
		}

		if ( $this->retry_policy->only_during_business_hours() && ! $this->business_hours->is_calling_allowed_now() ) {
			$this->retry_repository->schedule_next( $retry_id, $this->business_hours->get_next_valid_call_time() );
			return 'scheduled';
		}

		if ( ! $this->eligibility->has_concurrency_capacity() ) {
			$this->retry_repository->schedule_next( $retry_id, $this->get_delay_retry_time() );
			return 'scheduled';
		}

		$current_attempt = $this->retry_repository->increment_attempt( $retry_id );
		$response        = $this->call_originate_service->originate_for_order(
			$order_id,
			array(
				'source'                        => 'retry_queue',
				'suppress_originate_error_hook' => true,
				'retry_id'                      => $retry_id,
				'retry_attempt'                 => $current_attempt,
				'source_call_session_id'        => isset( $row['source_call_session_id'] ) ? absint( $row['source_call_session_id'] ) : 0,
				'queue_call_session_id'         => isset( $row['call_session_id'] ) ? absint( $row['call_session_id'] ) : 0,
			)
		);

		if ( $response instanceof Dialyra_API_Response && $response->is_successful() ) {
			$this->retry_repository->mark_completed( $retry_id );
			return 'completed';
		}

		if ( $this->is_terminal_response_failure( $response ) ) {
			$this->retry_repository->mark_cancelled( $retry_id );
			return 'cancelled';
		}

		if ( $current_attempt >= $max_attempts ) {
			$this->retry_repository->mark_exhausted( $retry_id );
			return 'exhausted';
		}

		$this->retry_repository->schedule_next( $retry_id, $this->get_delay_retry_time() );

		return 'failed';
	}

	/**
	 * Get next retry time using the configured delay.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_delay_retry_time() {
		return date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( $this->retry_policy->get_delay_minutes() * MINUTE_IN_SECONDS ) );
	}

	/**
	 * Determine whether a failed retry response should stop retry processing.
	 *
	 * @since    1.0.0
	 * @param    mixed    $response    Originate response.
	 * @return   bool
	 */
	private function is_terminal_response_failure( $response ) {
		if ( ! $response instanceof Dialyra_API_Response ) {
			return false;
		}

		$error_type = sanitize_key( $response->get_error_type() );
		$status_code = absint( $response->get_status_code() );

		return 402 === $status_code || in_array(
			$error_type,
			array(
				'invalid_phone',
				'flow_not_configured',
				'missing_flow',
				'unauthenticated',
				'forbidden',
				'bad_request',
				'validation_error',
				'missing_scope',
				'business_not_connected',
				'order_not_found',
				'woocommerce_unavailable',
				'invalid_request_payload',
			),
			true
		);
	}
}
