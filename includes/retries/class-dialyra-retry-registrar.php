<?php

/**
 * Dialyra retry queue registrar.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/retries
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Retry_Registrar {

	/**
	 * Retry repository.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Retry_Repository
	 */
	private $retry_repository;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_Retry_Repository    $retry_repository    Retry repository.
	 */
	public function __construct( $retry_repository ) {
		$this->retry_repository = $retry_repository;
	}

	/**
	 * Register a retry candidate for a failed order call.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    array    $context     Retry context.
	 * @return   int
	 */
	public function register( $order_id, array $context ) {
		$order_id = absint( $order_id );
		$context  = $this->normalize_context( $context );

		if ( ! $order_id || ! $this->order_exists( $order_id ) || empty( $context['business_id'] ) ) {
			return 0;
		}

		if ( ! $this->is_retry_candidate( $context ) ) {
			return 0;
		}

		$existing = $this->retry_repository->find_active_for_order( $context['business_id'], $order_id );

		if ( $existing && ! empty( $existing['id'] ) ) {
			$retry_id = absint( $existing['id'] );
			$this->retry_repository->update_failure_context( $retry_id, $context );

			do_action(
				Dialyra_Hook_Names::get_or_default( 'call', 'retry_registration_skipped', 'dialyra_retry_registration_skipped' ),
				$retry_id,
				$order_id,
				$context
			);

			return $retry_id;
		}

		$retry_id = $this->retry_repository->insert_pending( $context['business_id'], $order_id, $context );

		if ( $retry_id ) {
			do_action(
				Dialyra_Hook_Names::get_or_default( 'call', 'retry_registered', 'dialyra_retry_registered' ),
				$retry_id,
				$order_id,
				$context
			);
		}

		return $retry_id;
	}

	/**
	 * Handle Dialyra originate failure hook.
	 *
	 * @since    1.0.0
	 * @param    int                     $order_id    WooCommerce order ID.
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   int
	 */
	public function handle_originate_failure( $order_id, $response ) {
		if ( ! $response instanceof Dialyra_API_Response ) {
			return 0;
		}

		$context = array(
			'business_id'    => class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0,
			'call_session_id' => $this->extract_response_call_session_id( $response ),
			'failure_source' => 'originate',
			'failure_code'   => $this->extract_response_failure_code( $response ),
			'failure_reason' => $response->get_message(),
			'status_code'    => $response->get_status_code(),
			'error_type'     => $response->get_error_type(),
		);

		return $this->register( $order_id, $context );
	}

	/**
	 * Handle normalized Dialyra call events.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Normalized event.
	 * @return   int
	 */
	public function handle_call_event( $event ) {
		$event = is_array( $event ) ? $event : array();

		if ( 'call.failed' !== ( $event['event_type'] ?? '' ) ) {
			return 0;
		}

		$order_id = absint( $event['order_id'] ?? 0 );
		$context  = array(
			'business_id'     => absint( $event['business_id'] ?? 0 ),
			'call_session_id' => absint( $event['call_session_id'] ?? 0 ),
			'failure_source'  => 'runtime',
			'failure_code'    => $this->extract_event_failure_code( $event ),
			'failure_reason'  => $this->extract_event_failure_reason( $event ),
			'event_type'      => sanitize_text_field( $event['event_type'] ?? '' ),
		);

		if ( empty( $context['business_id'] ) && class_exists( 'Dialyra_Auth_Manager' ) ) {
			$context['business_id'] = absint( Dialyra_Auth_Manager::get_business_id() );
		}

		return $this->register( $order_id, $context );
	}

	/**
	 * Handle normalized failed/busy/no-answer call status hooks.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id        WooCommerce order ID.
	 * @param    array     $event           Normalized event.
	 * @param    string    $failure_code    Failure code.
	 * @return   int
	 */
	public function handle_call_status_failure( $order_id, $event, $failure_code ) {
		$event        = is_array( $event ) ? $event : array();
		$failure_code = sanitize_key( $failure_code );

		$context = array(
			'business_id'     => absint( $event['business_id'] ?? 0 ),
			'call_session_id' => absint( $event['call_session_id'] ?? 0 ),
			'failure_source'  => 'runtime',
			'failure_code'    => $failure_code ? $failure_code : $this->extract_event_failure_code( $event ),
			'failure_reason'  => $this->extract_event_failure_reason( $event ),
			'event_type'      => sanitize_text_field( $event['event_type'] ?? '' ),
		);

		if ( empty( $context['business_id'] ) && class_exists( 'Dialyra_Auth_Manager' ) ) {
			$context['business_id'] = absint( Dialyra_Auth_Manager::get_business_id() );
		}

		return $this->register( $order_id, $context );
	}

	/**
	 * Handle no-answer call hook.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    array    $event       Normalized event.
	 * @return   int
	 */
	public function handle_no_answer_call( $order_id, $event ) {
		return $this->handle_call_status_failure( $order_id, $event, 'no_answer' );
	}

	/**
	 * Handle busy call hook.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    array    $event       Normalized event.
	 * @return   int
	 */
	public function handle_busy_call( $order_id, $event ) {
		return $this->handle_call_status_failure( $order_id, $event, 'busy' );
	}

	/**
	 * Handle failed call hook.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    array    $event       Normalized event.
	 * @return   int
	 */
	public function handle_failed_call( $order_id, $event ) {
		return $this->handle_call_status_failure( $order_id, $event, 'call_failed' );
	}

	/**
	 * Determine whether a failure should become a retry candidate.
	 *
	 * @since    1.0.0
	 * @param    array    $context    Retry context.
	 * @return   bool
	 */
	public function is_retry_candidate( $context ) {
		$context        = is_array( $context ) ? $context : array();
		$failure_source = sanitize_key( $context['failure_source'] ?? '' );
		$failure_code   = sanitize_key( $context['failure_code'] ?? '' );
		$error_type     = sanitize_key( $context['error_type'] ?? '' );
		$event_type     = sanitize_text_field( $context['event_type'] ?? '' );
		$status_code    = isset( $context['status_code'] ) ? absint( $context['status_code'] ) : 0;

		$is_retry_candidate = false;

		if ( 'runtime' === $failure_source && ( 'call.failed' === $event_type || in_array( $failure_code, array( 'call_failed', 'failed', 'no_answer', 'busy' ), true ) ) ) {
			$is_retry_candidate = true;
		}

		if ( 'originate' === $failure_source ) {
			$retryable_codes = array(
				'no_sip_available',
				'trunk_not_ready',
				'ami_connection_failure',
				'ami_connectivity_failure',
				'temporary_server_failure',
				'server_error',
				'network_error',
				'rate_limited',
			);
			$non_retryable_codes = array(
				'insufficient_credit',
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
			);

			if ( 402 === $status_code || in_array( $failure_code, $non_retryable_codes, true ) ) {
				$is_retry_candidate = false;
			} elseif ( in_array( $failure_code, $retryable_codes, true ) || in_array( $error_type, $retryable_codes, true ) || $status_code >= 500 || 0 === $status_code || 429 === $status_code ) {
				$is_retry_candidate = true;
			} elseif ( in_array( $error_type, $non_retryable_codes, true ) ) {
				$is_retry_candidate = false;
			}
		}

		return (bool) apply_filters( 'dialyra_is_retry_candidate', $is_retry_candidate, $context );
	}

	/**
	 * Normalize retry context values.
	 *
	 * @since    1.0.0
	 * @param    array    $context    Raw context.
	 * @return   array
	 */
	private function normalize_context( $context ) {
		$failure_source = sanitize_key( $context['failure_source'] ?? '' );

		return array(
			'business_id'     => absint( $context['business_id'] ?? 0 ),
			'call_session_id' => absint( $context['call_session_id'] ?? 0 ),
			'failure_source'  => in_array( $failure_source, array( 'originate', 'runtime' ), true ) ? $failure_source : 'runtime',
			'failure_code'    => sanitize_key( $context['failure_code'] ?? '' ),
			'failure_reason'  => sanitize_textarea_field( $context['failure_reason'] ?? '' ),
			'status_code'     => isset( $context['status_code'] ) ? absint( $context['status_code'] ) : 0,
			'error_type'      => sanitize_key( $context['error_type'] ?? '' ),
			'event_type'      => sanitize_text_field( $context['event_type'] ?? '' ),
		);
	}

	/**
	 * Check whether the WooCommerce order exists.
	 *
	 * @since    1.0.0
	 * @param    int    $order_id    WooCommerce order ID.
	 * @return   bool
	 */
	private function order_exists( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return false;
		}

		return (bool) wc_get_order( $order_id );
	}

	/**
	 * Extract failure code from API response.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   string
	 */
	private function extract_response_failure_code( Dialyra_API_Response $response ) {
		$data = $response->get_data();
		$data = is_array( $data ) ? $data : array();

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		$code = $data['failure_code'] ?? ( $data['code'] ?? ( $data['error_code'] ?? $response->get_error_type() ) );

		return sanitize_key( $code );
	}

	/**
	 * Extract call session ID from API response when available.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   int
	 */
	private function extract_response_call_session_id( Dialyra_API_Response $response ) {
		$data = $response->get_data();
		$data = is_array( $data ) ? $data : array();

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		return absint( $data['call_session_id'] ?? ( $data['id'] ?? 0 ) );
	}

	/**
	 * Extract failure code from a normalized runtime event.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Normalized event.
	 * @return   string
	 */
	private function extract_event_failure_code( $event ) {
		$raw_payload = isset( $event['raw_payload'] ) && is_array( $event['raw_payload'] ) ? $event['raw_payload'] : array();
		$call        = isset( $raw_payload['call'] ) && is_array( $raw_payload['call'] ) ? $raw_payload['call'] : array();
		$code        = $event['failure_code'] ?? ( $event['reason_code'] ?? ( $call['failure_code'] ?? ( $call['hangup_cause'] ?? 'call_failed' ) ) );

		return sanitize_key( $code ? $code : 'call_failed' );
	}

	/**
	 * Extract failure reason from a normalized runtime event.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Normalized event.
	 * @return   string
	 */
	private function extract_event_failure_reason( $event ) {
		$raw_payload = isset( $event['raw_payload'] ) && is_array( $event['raw_payload'] ) ? $event['raw_payload'] : array();
		$call        = isset( $raw_payload['call'] ) && is_array( $raw_payload['call'] ) ? $raw_payload['call'] : array();
		$reason      = $event['failure_reason'] ?? ( $event['reason'] ?? ( $call['failure_reason'] ?? ( $call['hangup_cause_text'] ?? '' ) ) );

		return $reason ? sanitize_textarea_field( $reason ) : __( 'Call failed before completion.', 'wp-dialyra' );
	}
}
