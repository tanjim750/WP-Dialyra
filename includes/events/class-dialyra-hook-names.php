<?php

/**
 * Dialyra hook name registry.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/events
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Hook_Names {

	/**
	 * Get all custom Dialyra hook names grouped by purpose.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_all() {
		return array(
			'webhook' => array(
				'call_event_received' => self::hook( 'CALL_EVENT_RECEIVED', 'dialyra_call_event_received' ),
			),
			'order'   => array(
				'order_created'          => self::hook( 'ORDER_CREATED', 'dialyra_order_created' ),
				'order_status_changed'   => self::hook( 'ORDER_STATUS_CHANGED', 'dialyra_order_status_changed' ),
				'order_action_received'  => self::hook( 'ORDER_ACTION_RECEIVED', 'dialyra_order_action_received' ),
				'order_confirmed'        => self::hook( 'ORDER_CONFIRMED', 'dialyra_order_confirmed' ),
				'order_cancelled'        => self::hook( 'ORDER_CANCELLED', 'dialyra_order_cancelled' ),
				'order_action_processed' => self::hook( 'ORDER_ACTION_PROCESSED', 'dialyra_order_action_processed' ),
			),
			'business' => array(
				'business_changed' => self::hook( 'BUSINESS_CHANGED', 'dialyra_business_changed' ),
			),
			'call'    => array(
				'call_no_answer'        => self::hook( 'CALL_NO_ANSWER', 'dialyra_call_no_answer' ),
				'call_busy'             => self::hook( 'CALL_BUSY', 'dialyra_call_busy' ),
				'call_failed'           => self::hook( 'CALL_FAILED', 'dialyra_call_failed' ),
				'call_originated'       => self::hook( 'CALL_ORIGINATED', 'dialyra_call_originated' ),
				'call_originate_failed' => self::hook( 'CALL_ORIGINATE_FAILED', 'dialyra_call_originate_failed' ),
				'call_unauthorized'     => self::hook( 'CALL_UNAUTHORIZED', 'dialyra_call_unauthorized' ),
				'call_billing_blocked'  => self::hook( 'CALL_BILLING_BLOCKED', 'dialyra_call_billing_blocked' ),
				'call_invalid_flow'     => self::hook( 'CALL_INVALID_FLOW', 'dialyra_call_invalid_flow' ),
				'call_originate_error'  => self::hook( 'CALL_ORIGINATE_ERROR', 'dialyra_call_originate_error' ),
				'call_retry_requested'  => self::hook( 'CALL_RETRY_REQUESTED', 'dialyra_call_retry_requested' ),
				'retry_registered'      => self::hook( 'RETRY_REGISTERED', 'dialyra_retry_registered' ),
				'retry_registration_skipped' => self::hook( 'RETRY_REGISTRATION_SKIPPED', 'dialyra_retry_registration_skipped' ),
				'call_sync_requested'   => self::hook( 'CALL_SYNC_REQUESTED', 'dialyra_call_sync_requested' ),
			),
			'scheduler' => array(
				'process_call_queue'  => self::hook( 'PROCESS_CALL_QUEUE', 'dialyra_process_call_queue' ),
				'process_retry_queue' => self::hook( 'PROCESS_RETRY_QUEUE', 'dialyra_process_retry_queue' ),
			),
		);
	}

	/**
	 * Get a single custom Dialyra hook name.
	 *
	 * @since    1.0.0
	 * @param    string    $group    Hook group.
	 * @param    string    $name     Hook key.
	 * @return   string
	 */
	public static function get( $group, $name ) {
		$hooks = self::get_all();
		$group = sanitize_key( $group );
		$name  = sanitize_key( $name );

		return isset( $hooks[ $group ][ $name ] ) ? $hooks[ $group ][ $name ] : '';
	}

	/**
	 * Get a single custom Dialyra hook name with a fallback.
	 *
	 * @since    1.0.0
	 * @param    string    $group       Hook group.
	 * @param    string    $name        Hook key.
	 * @param    string    $fallback    Fallback hook name.
	 * @return   string
	 */
	public static function get_or_default( $group, $name, $fallback ) {
		$hook_name = self::get( $group, $name );

		return $hook_name ? $hook_name : $fallback;
	}

	/**
	 * Get a flattened list of all custom Dialyra hook names.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_names() {
		$names = array();

		foreach ( self::get_all() as $group ) {
			$names = array_merge( $names, array_values( $group ) );
		}

		return $names;
	}

	/**
	 * Resolve a hook from Dialyra_Events constants with a fallback.
	 *
	 * @since    1.0.0
	 * @param    string    $constant_name    Dialyra_Events constant name.
	 * @param    string    $fallback         Fallback hook name.
	 * @return   string
	 */
	private static function hook( $constant_name, $fallback ) {
		if ( class_exists( 'Dialyra_Events' ) && defined( 'Dialyra_Events::' . $constant_name ) ) {
			return constant( 'Dialyra_Events::' . $constant_name );
		}

		return $fallback;
	}
}
