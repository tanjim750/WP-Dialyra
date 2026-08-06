<?php

/**
 * Dialyra call sync hook dispatcher.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/calls
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Call_Sync_Listener {

	/**
	 * API endpoints.
	 *
	 * @var Dialyra_API_Endpoints|null
	 */
	private $api_endpoints;

	/**
	 * Call log repository.
	 *
	 * @var Dialyra_Call_Log_Repository|null
	 */
	private $call_log_repository;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Endpoints|null       $api_endpoints          API endpoints.
	 * @param    Dialyra_Call_Log_Repository|null $call_log_repository    Call log repository.
	 */
	public function __construct( $api_endpoints = null, $call_log_repository = null ) {
		$this->api_endpoints       = $api_endpoints instanceof Dialyra_API_Endpoints ? $api_endpoints : null;
		$this->call_log_repository = $call_log_repository instanceof Dialyra_Call_Log_Repository ? $call_log_repository : null;
	}

	/**
	 * Dispatch async-friendly call sync request hook.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Normalized Dialyra event.
	 */
	public function handle_call_event( $event ) {
		$event = is_array( $event ) ? $event : array();
		$type  = sanitize_text_field( $event['event_type'] ?? '' );

		if ( ! in_array( $type, array( 'call.completed', 'call.failed' ), true ) ) {
			return;
		}

		do_action(
			Dialyra_Hook_Names::get_or_default( 'call', 'call_sync_requested', 'dialyra_call_sync_requested' ),
			absint( $event['call_session_id'] ?? 0 ),
			absint( $event['order_id'] ?? 0 ),
			$event
		);
	}

	/**
	 * Fetch Dialyra history and sync the local call log row.
	 *
	 * @since    1.0.0
	 * @param    int      $call_session_id    Call session ID.
	 * @param    int      $order_id           WooCommerce order ID.
	 * @param    array    $event              Normalized event.
	 * @return   bool
	 */
	public function handle_sync_requested( $call_session_id, $order_id, $event ) {
		$event = is_array( $event ) ? $event : array();

		if ( ! $this->api_endpoints || ! $this->call_log_repository ) {
			return false;
		}

		$query   = array();
		$path_id = 0;

		if ( ! empty( $event['action_id'] ) ) {
			$query['action_id'] = sanitize_text_field( $event['action_id'] );
		} elseif ( $call_session_id ) {
			$query['call_session_id'] = absint( $call_session_id );
		} elseif ( ! empty( $event['call_log_id'] ) ) {
			$path_id = absint( $event['call_log_id'] );
		}

		if ( empty( $query ) && ! $path_id ) {
			return false;
		}

		$response = $this->api_endpoints->get_call_history( $path_id, $query );

		if ( ! $response || ! $response->is_successful() ) {
			return false;
		}

		$history = $this->extract_response_data( $response );

		if ( empty( $history ) ) {
			return false;
		}

		$local_log_id = $this->call_log_repository->find_log_id_for_event( $event );

		if ( ! $local_log_id ) {
			$local_log_id = $this->call_log_repository->log_webhook_event( $event );
		}

		return $local_log_id ? $this->call_log_repository->sync_from_history_response( $local_log_id, $history ) : false;
	}

	/**
	 * Extract normalized API response data.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   array
	 */
	private function extract_response_data( Dialyra_API_Response $response ) {
		$data = $response->get_data();
		$data = is_array( $data ) ? $data : array();

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		return $data;
	}
}
