<?php

/**
 * Dialyra internal event hook names.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/events
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Events {

	const ORDER_CREATED         = 'dialyra_order_created';
	const ORDER_STATUS_CHANGED  = 'dialyra_order_status_changed';
	const BUSINESS_CHANGED      = 'dialyra_business_changed';
	const CALL_EVENT_RECEIVED      = 'dialyra_call_event_received';
	const ORDER_ACTION_RECEIVED   = 'dialyra_order_action_received';
	const ORDER_ACTION_PROCESSED  = 'dialyra_order_action_processed';
	const ORDER_CONFIRMED         = 'dialyra_order_confirmed';
	const ORDER_CANCELLED         = 'dialyra_order_cancelled';
	const CALL_NO_ANSWER          = 'dialyra_call_no_answer';
	const CALL_BUSY               = 'dialyra_call_busy';
	const CALL_FAILED             = 'dialyra_call_failed';
	const CALL_ORIGINATED         = 'dialyra_call_originated';
	const CALL_ORIGINATE_FAILED   = 'dialyra_call_originate_failed';
	const CALL_UNAUTHORIZED       = 'dialyra_call_unauthorized';
	const CALL_BILLING_BLOCKED    = 'dialyra_call_billing_blocked';
	const CALL_INVALID_FLOW       = 'dialyra_call_invalid_flow';
	const CALL_ORIGINATE_ERROR    = 'dialyra_call_originate_error';
	const CALL_RETRY_REQUESTED    = 'dialyra_call_retry_requested';
	const RETRY_REGISTERED        = 'dialyra_retry_registered';
	const RETRY_REGISTRATION_SKIPPED = 'dialyra_retry_registration_skipped';
	const CALL_SYNC_REQUESTED     = 'dialyra_call_sync_requested';
	const PROCESS_CALL_QUEUE      = 'dialyra_process_call_queue';
	const PROCESS_RETRY_QUEUE     = 'dialyra_process_retry_queue';
}
