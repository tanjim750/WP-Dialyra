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
}
