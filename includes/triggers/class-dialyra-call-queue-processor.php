<?php

/**
 * Dialyra initial call queue processor.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/triggers
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Call_Queue_Processor {

	/**
	 * Initial call queue repository.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Call_Queue_Repository
	 */
	private $queue_repository;

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
	 * @param    Dialyra_Call_Queue_Repository    $queue_repository         Queue repository.
	 * @param    Dialyra_Call_Eligibility         $eligibility              Eligibility service.
	 * @param    Dialyra_Business_Hours           $business_hours           Business hours service.
	 * @param    Dialyra_Call_Originate_Service   $call_originate_service   Call originate service.
	 */
	public function __construct( $queue_repository, $eligibility, $business_hours, $call_originate_service ) {
		$this->queue_repository       = $queue_repository;
		$this->eligibility            = $eligibility;
		$this->business_hours         = $business_hours;
		$this->call_originate_service = $call_originate_service;
	}

	/**
	 * Process due pending initial/deferred call queue records.
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
			'deferred'  => 0,
			'failed'    => 0,
		);

		$rows = $this->queue_repository->get_due_pending( $limit );

		foreach ( $rows as $row ) {
			$queue_id = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
			$order_id = isset( $row['order_id'] ) ? absint( $row['order_id'] ) : 0;

			if ( ! $queue_id || ! $order_id || ! $this->queue_repository->claim( $queue_id ) ) {
				continue;
			}

			$results['processed']++;

			$outcome = $this->process_claimed_row( $queue_id, $order_id );

			if ( isset( $results[ $outcome ] ) ) {
				$results[ $outcome ]++;
			}
		}

		return $results;
	}

	/**
	 * Process one pending queue record by ID.
	 *
	 * @since    1.0.0
	 * @param    int    $queue_id    Queue ID.
	 * @return   string
	 */
	public function process_queue_item( $queue_id ) {
		$queue_id = absint( $queue_id );
		$row      = method_exists( $this->queue_repository, 'get_by_id' ) ? $this->queue_repository->get_by_id( $queue_id ) : array();

		if ( empty( $row ) || 'pending' !== sanitize_key( $row['status'] ?? '' ) ) {
			return 'not_found';
		}

		$order_id = absint( $row['order_id'] ?? 0 );

		if ( ! $order_id || ! $this->queue_repository->claim( $queue_id ) ) {
			return 'not_claimed';
		}

		return $this->process_claimed_row( $queue_id, $order_id );
	}

	/**
	 * Process one claimed queue record.
	 *
	 * @since    1.0.0
	 * @param    int    $queue_id    Queue ID.
	 * @param    int    $order_id    WooCommerce order ID.
	 * @return   string
	 */
	private function process_claimed_row( $queue_id, $order_id ) {
		$eligibility = $this->eligibility->can_call_order( $order_id );

		if ( empty( $eligibility['eligible'] ) ) {
			if ( 'active_call_exists' === ( $eligibility['reason'] ?? '' ) ) {
				$this->queue_repository->defer( $queue_id, 'concurrency', $this->get_concurrency_retry_time() );
				return 'deferred';
			}

			$this->queue_repository->mark_cancelled( $queue_id );
			return 'cancelled';
		}

		if ( ! $this->business_hours->is_calling_allowed_now() ) {
			$this->queue_repository->defer( $queue_id, 'business_hours', $this->business_hours->get_next_valid_call_time() );
			return 'deferred';
		}

		if ( ! $this->eligibility->has_concurrency_capacity() ) {
			$this->queue_repository->defer( $queue_id, 'concurrency', $this->get_concurrency_retry_time() );
			return 'deferred';
		}

		$response = $this->call_originate_service->originate_for_order( $order_id );

		if ( $response instanceof Dialyra_API_Response && $response->is_successful() ) {
			$this->queue_repository->mark_completed( $queue_id );
			return 'completed';
		}

		if ( $response instanceof Dialyra_API_Response && $this->is_local_terminal_error( $response ) ) {
			$this->queue_repository->mark_cancelled( $queue_id );
			return 'failed';
		}

		if ( $response instanceof Dialyra_API_Response && absint( $response->get_status_code() ) > 0 ) {
			$this->queue_repository->defer( $queue_id, 'originate_error', $this->get_concurrency_retry_time() );
			return 'deferred';
		}

		$this->queue_repository->mark_cancelled( $queue_id );
		return 'failed';
	}

	/**
	 * Get a short retry time for temporary concurrency deferrals.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_concurrency_retry_time() {
		return date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( 5 * MINUTE_IN_SECONDS ) );
	}

	/**
	 * Check whether a failed originate response came from local plugin validation.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   bool
	 */
	private function is_local_terminal_error( Dialyra_API_Response $response ) {
		$data = $response->get_data();
		$data = is_array( $data ) ? $data : array();

		return ! empty( $data['error_type'] );
	}
}
