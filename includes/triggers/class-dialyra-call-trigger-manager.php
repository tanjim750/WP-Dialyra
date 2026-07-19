<?php

/**
 * Dialyra automatic call trigger manager.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/triggers
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Call_Trigger_Manager {

	/**
	 * Business manager.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Business_Manager
	 */
	private $business_manager;

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
	 * Flow manager.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Flow_Manager|null
	 */
	private $flow_manager;

	/**
	 * Product assignment manager.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Flow_Product_Assignment_Manager|null
	 */
	private $product_assignment_manager;

	/**
	 * Local call log repository.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Call_Log_Repository|null
	 */
	private $call_log_repository;

	/**
	 * Audit log repository.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Audit_Log_Repository|null
	 */
	private $audit_log_repository;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_Business_Manager        $business_manager         Business manager.
	 * @param    Dialyra_Call_Queue_Repository   $queue_repository         Queue repository.
	 * @param    Dialyra_Call_Eligibility        $eligibility              Eligibility service.
	 * @param    Dialyra_Business_Hours          $business_hours           Business hours service.
	 * @param    Dialyra_Call_Originate_Service  $call_originate_service   Call originate service.
	 * @param    Dialyra_Flow_Manager|null       $flow_manager             Optional flow manager.
	 * @param    Dialyra_Flow_Product_Assignment_Manager|null $product_assignment_manager Optional product assignment manager.
	 * @param    Dialyra_Call_Log_Repository|null $call_log_repository Optional call log repository.
	 * @param    Dialyra_Audit_Log_Repository|null $audit_log_repository Optional audit log repository.
	 */
	public function __construct( $business_manager, $queue_repository, $eligibility, $business_hours, $call_originate_service, $flow_manager = null, $product_assignment_manager = null, $call_log_repository = null, $audit_log_repository = null ) {
		$this->business_manager            = $business_manager;
		$this->queue_repository            = $queue_repository;
		$this->eligibility                 = $eligibility;
		$this->business_hours              = $business_hours;
		$this->call_originate_service      = $call_originate_service;
		$this->flow_manager                = $flow_manager;
		$this->product_assignment_manager  = $product_assignment_manager;
		$this->call_log_repository         = $call_log_repository;
		$this->audit_log_repository        = $audit_log_repository;
	}

	/**
	 * Handle WooCommerce new order event.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    mixed    $order       WooCommerce order object.
	 */
	public function handle_new_order( $order_id, $order = null ) {
		$mode = $this->get_trigger_mode();
		$this->debug_log_trigger( $order_id, 'new_order_received', array( 'mode' => $mode ) );

		if ( 'instant' === $mode ) {
			$this->handle_ready_candidate( $order_id, 'instant' );
			return;
		}

		if ( 'delay' === $mode ) {
			$this->debug_log_trigger( $order_id, 'queued_delay', array( 'scheduled_at' => $this->get_delayed_call_time() ) );
			$this->queue_order( $order_id, 'delay', $this->get_delayed_call_time() );
		}
	}

	/**
	 * Handle WooCommerce order status transition.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id      WooCommerce order ID.
	 * @param    string    $old_status    Previous status.
	 * @param    string    $new_status    New status.
	 * @param    mixed     $order         WooCommerce order object.
	 */
	public function handle_order_status_changed( $order_id, $old_status, $new_status, $order = null ) {
		$mode = $this->get_trigger_mode();

		if ( 'instant' === $mode ) {
			$this->handle_ready_candidate( $order_id, 'status_fallback' );
			return;
		}

		if ( 'delay' === $mode ) {
			$this->queue_order( $order_id, 'delay', $this->get_delayed_call_time() );
			return;
		}

		if ( 'on_specific_status' === $mode && sanitize_key( $new_status ) === $this->get_target_status() ) {
			$this->handle_ready_candidate( $order_id, 'status' );
		}
	}

	/**
	 * Handle originate 401 unauthorized failure.
	 *
	 * A 401 means the saved business access token cannot be used. Generate a new
	 * token first, then queue the order so cron can safely retry the call.
	 *
	 * @since    1.0.0
	 * @param    int                     $order_id     WooCommerce order ID.
	 * @param    Dialyra_API_Response    $response     Originate API response.
	 * @return   int
	 */
	public function handle_unauthorized_call( $order_id, $response = null ) {
		$business_id = $this->business_manager->get_connected_business_id();

		if ( $business_id ) {
			$this->business_manager->clear_site_access_token();
			$this->business_manager->create_site_access_token( $business_id );
		}

		return $this->queue_order( $order_id, 'unauthorized', $this->get_short_retry_time() );
	}

	/**
	 * Handle originate 402 payment required failure.
	 *
	 * Billing failures are queued but the listener does not regenerate credentials.
	 * The queue gives the store a visible pending item after billing is fixed.
	 *
	 * @since    1.0.0
	 * @param    int                     $order_id     WooCommerce order ID.
	 * @param    Dialyra_API_Response    $response     Originate API response.
	 * @return   int
	 */
	public function handle_billing_blocked_call( $order_id, $response = null ) {
		return $this->queue_order( $order_id, 'payment_required', $this->get_billing_retry_time() );
	}

	/**
	 * Handle invalid flow originate failure.
	 *
	 * @since    1.0.0
	 * @param    int                     $order_id    WooCommerce order ID.
	 * @param    Dialyra_API_Response    $response    Originate API response.
	 * @param    array                   $context     Flow context.
	 * @return   int
	 */
	public function handle_invalid_flow_call( $order_id, $response = null, $context = array() ) {
		$this->repair_invalid_flow( is_array( $context ) ? $context : array() );

		return $this->queue_order( $order_id, 'invalid_flow', $this->get_short_retry_time() );
	}

	/**
	 * Handle any other originate API error.
	 *
	 * @since    1.0.0
	 * @param    int                     $order_id    WooCommerce order ID.
	 * @param    Dialyra_API_Response    $response    Originate API response.
	 * @param    array                   $context     Flow context.
	 * @return   int
	 */
	public function handle_originate_api_error( $order_id, $response = null, $context = array() ) {
		return $this->queue_order( $order_id, 'originate_error', $this->get_general_error_retry_time() );
	}

	/**
	 * Run eligibility and scheduling gates for an automatic call candidate.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id    WooCommerce order ID.
	 * @param    string    $source      Trigger source.
	 * @return   array
	 */
	private function handle_ready_candidate( $order_id, $source ) {
		$order_id = absint( $order_id );

		if ( ! $order_id ) {
			$this->debug_log_trigger( $order_id, 'blocked_invalid_order_id' );
			$this->log_trigger_blocked( $order_id, 'invalid_order_id', $source, 'order' );
			return array(
				'success' => false,
				'reason'  => 'invalid_order_id',
			);
		}

		$eligibility = $this->eligibility->can_call_order( $order_id );

		if ( empty( $eligibility['eligible'] ) ) {
			$this->debug_log_trigger( $order_id, 'blocked_eligibility', array( 'reason' => $eligibility['reason'] ?? '' ) );
			$this->log_trigger_blocked(
				$order_id,
				$eligibility['reason'] ?? 'not_eligible',
				$source,
				'eligibility'
			);

			if ( 'active_call_exists' === ( $eligibility['reason'] ?? '' ) ) {
				return $eligibility;
			}

			return $eligibility;
		}

		if ( ! $this->business_hours->is_calling_allowed_now() ) {
			$this->debug_log_trigger( $order_id, 'queued_business_hours' );
			$this->log_trigger_blocked( $order_id, 'outside_business_hours', $source, 'business_hours' );
			$this->queue_order( $order_id, 'business_hours', $this->business_hours->get_next_valid_call_time() );

			return array(
				'success' => false,
				'reason'  => 'outside_business_hours',
			);
		}

		if ( ! $this->eligibility->has_concurrency_capacity() ) {
			$this->debug_log_trigger( $order_id, 'queued_concurrency' );
			$this->log_trigger_blocked( $order_id, 'concurrency_limit', $source, 'concurrency' );
			$this->queue_order( $order_id, 'concurrency', $this->get_concurrency_retry_time() );

			return array(
				'success' => false,
				'reason'  => 'concurrency_limit',
			);
		}

		$this->debug_log_trigger( $order_id, 'originate_requested', array( 'source' => $source ) );

		return $this->call_originate_service->originate_for_order(
			$order_id,
			array(
				'source' => $source,
			)
		);
	}

	/**
	 * Persist a local diagnostic row when an automatic trigger stops before API.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id    WooCommerce order ID.
	 * @param    string    $reason      Block reason.
	 * @param    string    $source      Trigger source.
	 * @param    string    $gate        Gate name.
	 * @return   void
	 */
	private function log_trigger_blocked( $order_id, $reason, $source, $gate ) {
		if ( ! $this->call_log_repository || ! method_exists( $this->call_log_repository, 'log_trigger_blocked' ) ) {
			return;
		}

		$this->call_log_repository->log_trigger_blocked(
			$order_id,
			$reason,
			array(
				'business_id' => $this->business_manager && method_exists( $this->business_manager, 'get_connected_business_id' ) ? $this->business_manager->get_connected_business_id() : 0,
				'source'      => $source,
				'gate'        => $gate,
			)
		);
	}

	/**
	 * Queue an order for later initial call processing.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id        WooCommerce order ID.
	 * @param    string    $source          Queue source.
	 * @param    string    $scheduled_at    Scheduled datetime.
	 * @return   int
	 */
	private function queue_order( $order_id, $source, $scheduled_at ) {
		$business_id = $this->business_manager->get_connected_business_id();

		if ( ! $business_id ) {
			return 0;
		}

		return $this->queue_repository->upsert_pending( $business_id, $order_id, $source, $scheduled_at );
	}

	/**
	 * Get the active trigger mode.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_trigger_mode() {
		$settings = $this->get_trigger_settings();
		$mode     = sanitize_key( $settings['mode'] ?? '' );

		if ( ! $mode && defined( 'WP_DIALYRA_OPTION_CALL_TRIGGER_MODE' ) ) {
			$mode = sanitize_key( get_option( WP_DIALYRA_OPTION_CALL_TRIGGER_MODE, '' ) );
		}

		if ( 'status' === $mode ) {
			$mode = 'on_specific_status';
		}

		$allowed = array( 'instant', 'delay', 'on_specific_status' );

		return in_array( $mode, $allowed, true ) ? $mode : ( defined( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_MODE' ) ? WP_DIALYRA_DEFAULT_CALL_TRIGGER_MODE : 'instant' );
	}

	/**
	 * Get configured target status for specific-status mode.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_target_status() {
		$settings = $this->get_trigger_settings();
		$status   = sanitize_key( $settings['order_status'] ?? '' );

		return $status ? $status : ( defined( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_ORDER_STATUS' ) ? WP_DIALYRA_DEFAULT_CALL_TRIGGER_ORDER_STATUS : 'processing' );
	}

	/**
	 * Get scheduled time for delay mode.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_delayed_call_time() {
		$settings      = $this->get_trigger_settings();
		$delay_minutes = isset( $settings['delay_minutes'] ) ? absint( $settings['delay_minutes'] ) : 0;
		$delay_minutes = $delay_minutes ? $delay_minutes : ( defined( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_DELAY_MINUTES' ) ? WP_DIALYRA_DEFAULT_CALL_TRIGGER_DELAY_MINUTES : 5 );

		return date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( $delay_minutes * MINUTE_IN_SECONDS ) );
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
	 * Get short retry time for credential recovery requeue.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_short_retry_time() {
		return date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + MINUTE_IN_SECONDS );
	}

	/**
	 * Get retry time for payment-required requeue.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_billing_retry_time() {
		return date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( 15 * MINUTE_IN_SECONDS ) );
	}

	/**
	 * Get retry time for general originate API errors.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_general_error_retry_time() {
		return date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( 10 * MINUTE_IN_SECONDS ) );
	}

	/**
	 * Repair local flow state after Dialyra rejects a flow ID.
	 *
	 * @since    1.0.0
	 * @param    array    $context    Flow error context.
	 */
	private function repair_invalid_flow( $context ) {
		$context     = is_array( $context ) ? $context : array();
		$business_id = ! empty( $context['business_id'] ) ? absint( $context['business_id'] ) : $this->business_manager->get_connected_business_id();
		$flow_id     = absint( $context['flow_id'] ?? 0 );
		$flow_source = sanitize_key( $context['flow_source'] ?? '' );

		if ( ! $business_id || ! $flow_id ) {
			return;
		}

		if ( 'product' === $flow_source ) {
			$this->remove_invalid_product_flow( $business_id, $flow_id );
			return;
		}

		if ( 'default' === $flow_source ) {
			$this->replace_invalid_default_flow( $business_id, $flow_id );
		}
	}

	/**
	 * Remove product assignments that point to an invalid flow.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Business ID.
	 * @param    int    $flow_id        Flow ID.
	 */
	private function remove_invalid_product_flow( $business_id, $flow_id ) {
		if ( $this->product_assignment_manager && method_exists( $this->product_assignment_manager, 'delete_flow_assignments' ) ) {
			$this->product_assignment_manager->delete_flow_assignments( $business_id, $flow_id );
		}
	}

	/**
	 * Replace an invalid default flow with the first active published flow.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id         Business ID.
	 * @param    int    $invalid_flow_id     Invalid flow ID.
	 */
	private function replace_invalid_default_flow( $business_id, $invalid_flow_id ) {
		if ( ! $this->flow_manager || ! method_exists( $this->flow_manager, 'get_default_flow_id' ) || absint( $this->flow_manager->get_default_flow_id() ) !== absint( $invalid_flow_id ) ) {
			return;
		}

		$replacement = $this->find_first_active_published_flow( $business_id, $invalid_flow_id );

		if ( ! empty( $replacement['id'] ) ) {
			$this->flow_manager->save_default_flow_data( $replacement );
			return;
		}

		$this->flow_manager->clear_default_flow();
	}

	/**
	 * Find the first active/published flow from Dialyra.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id         Business ID.
	 * @param    int    $invalid_flow_id     Invalid flow ID to skip.
	 * @return   array
	 */
	private function find_first_active_published_flow( $business_id, $invalid_flow_id ) {
		if ( ! $this->flow_manager || ! method_exists( $this->flow_manager, 'get_flows' ) ) {
			return array();
		}

		$response = $this->flow_manager->get_flows( array( 'business_id' => absint( $business_id ) ) );

		if ( ! $response || ! $response->is_successful() ) {
			return array();
		}

		foreach ( $this->extract_flow_items( $response ) as $flow ) {
			if ( $this->is_replacement_flow( $flow, $invalid_flow_id ) ) {
				return $flow;
			}
		}

		return array();
	}

	/**
	 * Extract flow rows from common API response containers.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   array
	 */
	private function extract_flow_items( Dialyra_API_Response $response ) {
		$data = $response->get_data();
		$data = is_array( $data ) ? $data : array();

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		foreach ( array( 'items', 'flows' ) as $key ) {
			if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
				return array_values( array_filter( $data[ $key ], 'is_array' ) );
			}
		}

		return array_values( array_filter( $data, 'is_array' ) );
	}

	/**
	 * Check if a flow can replace an invalid default flow.
	 *
	 * @since    1.0.0
	 * @param    array    $flow              Flow row.
	 * @param    int      $invalid_flow_id   Invalid flow ID to skip.
	 * @return   bool
	 */
	private function is_replacement_flow( $flow, $invalid_flow_id ) {
		if ( absint( $flow['id'] ?? 0 ) === absint( $invalid_flow_id ) ) {
			return false;
		}

		$status = sanitize_key( $flow['status'] ?? '' );

		if ( in_array( $status, array( 'archived', 'draft', 'deleted' ), true ) ) {
			return false;
		}

		return in_array( $status, array( 'published', 'active' ), true ) && false !== ( $flow['is_active'] ?? true );
	}

	/**
	 * Get trigger settings from setup options.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function get_trigger_settings() {
		$defaults = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_call_trigger_defaults() : array();
		$setup    = defined( 'WP_DIALYRA_OPTION_SETUP_SETTINGS' ) ? get_option( WP_DIALYRA_OPTION_SETUP_SETTINGS, array() ) : array();
		$settings = is_array( $setup ) && isset( $setup['call_trigger'] ) && is_array( $setup['call_trigger'] ) ? $setup['call_trigger'] : array();

		if ( defined( 'WP_DIALYRA_OPTION_CALL_TRIGGER_MODE' ) ) {
			$saved_mode = sanitize_key( get_option( WP_DIALYRA_OPTION_CALL_TRIGGER_MODE, '' ) );

			if ( $saved_mode ) {
				$settings['mode'] = $saved_mode;
			}
		}

		return array_replace_recursive( $defaults, $settings );
	}

	/**
	 * Log trigger decisions into the plugin audit table.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id    WooCommerce order ID.
	 * @param    string    $event       Trigger event.
	 * @param    array     $context     Optional context.
	 */
	private function debug_log_trigger( $order_id, $event, $context = array() ) {
		if ( ! $this->audit_log_repository || ! method_exists( $this->audit_log_repository, 'log' ) ) {
			return;
		}

		$context = is_array( $context ) ? $context : array();
		$level   = false === strpos( sanitize_key( $event ), 'blocked' ) ? 'info' : 'warning';

		$context['order_id'] = absint( $order_id );

		$this->audit_log_repository->log(
			sanitize_key( $event ),
			sprintf( 'Automatic call trigger: %s', sanitize_key( $event ) ),
			$context,
			$level,
			'trigger'
		);
	}
}
