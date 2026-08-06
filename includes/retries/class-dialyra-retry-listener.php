<?php

/**
 * Dialyra retry hook dispatcher.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/retries
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Retry_Listener {

	/**
	 * Dispatch retry request hook for failed call events.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Normalized Dialyra event.
	 */
	public function handle_call_event( $event ) {
		$event = is_array( $event ) ? $event : array();

		if ( 'call.failed' !== ( $event['event_type'] ?? '' ) ) {
			return;
		}

		do_action(
			Dialyra_Hook_Names::get_or_default( 'call', 'call_retry_requested', 'dialyra_call_retry_requested' ),
			absint( $event['order_id'] ?? 0 ),
			absint( $event['call_session_id'] ?? 0 ),
			$event
		);
	}

	/**
	 * Dispatch retry request hook for temporary originate failures.
	 *
	 * @since    1.0.0
	 * @param    int                     $order_id    WooCommerce order ID.
	 * @param    Dialyra_API_Response    $response    API response.
	 */
	public function handle_originate_failure( $order_id, $response ) {
		if ( ! $response instanceof Dialyra_API_Response ) {
			return;
		}

		if ( 402 === absint( $response->get_status_code() ) ) {
			return;
		}

		do_action(
			Dialyra_Hook_Names::get_or_default( 'call', 'call_retry_requested', 'dialyra_call_retry_requested' ),
			absint( $order_id ),
			0,
			array(
				'event_type'   => 'call.originate_failed',
				'order_id'     => absint( $order_id ),
				'status_code'  => absint( $response->get_status_code() ),
				'error_type'   => sanitize_key( $response->get_error_type() ),
				'occurred_at'  => current_time( 'mysql' ),
			)
		);
	}
}
