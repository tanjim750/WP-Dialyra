<?php

/**
 * Shared Dialyra utility defaults.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Dialyra_Utils {

	public static function get_initiated_calls( $limit = 100 ) {
		global $wpdb;

		$limit      = max( 1, (int) $limit );
		$table_name = $wpdb->prefix . 'dialyra_call_logs';
		$rows       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name}
				WHERE status = %s AND order_id > 0
				AND (remote_call_log_id > 0 OR call_session_id > 0 OR (action_id IS NOT NULL AND TRIM(action_id) <> ''))
				ORDER BY created_at ASC, id ASC LIMIT %d",
				'initiated',
				$limit
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	public static function get_updated_call_state( $local_log_id ) {
		$local_log_id = (int) $local_log_id;

		if ( $local_log_id < 1 ) {
			return new WP_Error( 'dialyra_invalid_call_id', __( 'A valid local call log ID is required.', 'wp-dialyra' ) );
		}

		$plugin = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;

		if ( ! $plugin || ! $plugin->get_api_endpoints() || ! $plugin->get_call_log_repository() ) {
			return new WP_Error( 'dialyra_call_service_unavailable', __( 'Dialyra call service is not available.', 'wp-dialyra' ) );
		}

		$call = $plugin->get_call_log_repository()->get_log( $local_log_id );

		if ( empty( $call ) ) {
			return new WP_Error( 'dialyra_call_not_found', __( 'Local call log was not found.', 'wp-dialyra' ) );
		}

		$query   = array();
		$call_id = null;

		if ( ! empty( $call['action_id'] ) ) {
			$query['action_id'] = sanitize_text_field( $call['action_id'] );
		} elseif ( ! empty( $call['call_session_id'] ) ) {
			$query['call_session_id'] = absint( $call['call_session_id'] );
		} elseif ( ! empty( $call['remote_call_log_id'] ) ) {
			$call_id = absint( $call['remote_call_log_id'] );
		} else {
			return new WP_Error( 'dialyra_call_identifier_missing', __( 'This call has no remote identifier to fetch its current state.', 'wp-dialyra' ) );
		}

		$response = $plugin->get_api_endpoints()->get_call_history( $call_id, $query );

		if ( ! $response->is_successful() ) {
			return new WP_Error( 'dialyra_call_state_fetch_failed', $response->get_message(), array( 'status' => $response->get_status_code() ) );
		}

		return $response->get_data();
	}

	public static function update_local_call_record( $local_log_id, $call_state ) {
		$local_log_id = (int) $local_log_id;

		if ( $local_log_id < 1 ) {
			return new WP_Error( 'dialyra_invalid_call_id', __( 'A valid local call log ID is required.', 'wp-dialyra' ) );
		}

		if ( is_wp_error( $call_state ) ) {
			return $call_state;
		}

		if ( ! is_array( $call_state ) || empty( $call_state['status'] ) || ! is_string( $call_state['status'] ) ) {
			return new WP_Error( 'dialyra_invalid_call_state', __( 'Call history data with a valid status is required.', 'wp-dialyra' ) );
		}

		$plugin     = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
		$repository = $plugin ? $plugin->get_call_log_repository() : null;

		if ( ! $repository ) {
			return new WP_Error( 'dialyra_call_service_unavailable', __( 'Dialyra call service is not available.', 'wp-dialyra' ) );
		}

		if ( empty( $repository->get_log( $local_log_id ) ) ) {
			return new WP_Error( 'dialyra_call_not_found', __( 'Local call log was not found.', 'wp-dialyra' ) );
		}

		if ( ! $repository->sync_from_history_response( $local_log_id, $call_state ) ) {
			return new WP_Error( 'dialyra_call_update_failed', __( 'The local call log could not be updated.', 'wp-dialyra' ) );
		}

		return true;
	}

	/**
	 * Get the default setup values used by the plugin.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_setup_defaults() {
		return array(
			'access_token_mode'    => 'auto',
			'business_hours'      => self::get_business_hours_defaults(),
			'call_capacity'       => self::get_call_capacity_defaults(),
			'call_trigger'        => self::get_call_trigger_defaults(),
			'order_status_map'    => self::get_order_status_mapping_defaults(),
			'retry_policy'        => self::get_retry_policy_defaults(),
			'webhook_secret_mode' => 'auto',
		);
	}

	/**
	 * Get default site access token values.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_site_access_token_defaults() {
		return array(
			'expires_days' => 365,
			'scopes'       => array(
				'calls:originate',
				'calls:read',
			),
		);
	}

	/**
	 * Get default call trigger settings.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_call_trigger_defaults() {
		return array(
			'mode'          => defined( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_MODE' ) ? WP_DIALYRA_DEFAULT_CALL_TRIGGER_MODE : 'instant',
			'order_status'  => defined( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_ORDER_STATUS' ) ? WP_DIALYRA_DEFAULT_CALL_TRIGGER_ORDER_STATUS : 'processing',
			'delay_minutes' => defined( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_DELAY_MINUTES' ) ? WP_DIALYRA_DEFAULT_CALL_TRIGGER_DELAY_MINUTES : 5,
		);
	}

	/**
	 * Get default retry policy settings.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_retry_policy_defaults() {
		return array(
			'max_attempts'               => defined( 'WP_DIALYRA_DEFAULT_RETRY_MAX_ATTEMPTS' ) ? WP_DIALYRA_DEFAULT_RETRY_MAX_ATTEMPTS : 2,
			'delay_minutes'              => defined( 'WP_DIALYRA_DEFAULT_RETRY_DELAY_MINUTES' ) ? WP_DIALYRA_DEFAULT_RETRY_DELAY_MINUTES : 15,
			'only_during_business_hours' => defined( 'WP_DIALYRA_DEFAULT_RETRY_ONLY_DURING_BUSINESS_HOURS' ) ? WP_DIALYRA_DEFAULT_RETRY_ONLY_DURING_BUSINESS_HOURS : true,
		);
	}

	/**
	 * Get default business hours settings.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_business_hours_defaults() {
		return array(
			'availability_mode' => defined( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_MODE' ) ? WP_DIALYRA_DEFAULT_BUSINESS_HOURS_MODE : 'always_active',
			'days'              => defined( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_DAYS' ) && is_array( WP_DIALYRA_DEFAULT_BUSINESS_HOURS_DAYS ) ? WP_DIALYRA_DEFAULT_BUSINESS_HOURS_DAYS : array( 'all' ),
			'open_time'         => defined( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_OPEN_TIME' ) ? WP_DIALYRA_DEFAULT_BUSINESS_HOURS_OPEN_TIME : '09:00',
			'close_time'        => defined( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_CLOSE_TIME' ) ? WP_DIALYRA_DEFAULT_BUSINESS_HOURS_CLOSE_TIME : '18:00',
			'timezone'          => self::get_default_timezone(),
		);
	}

	/**
	 * Get default call capacity settings.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_call_capacity_defaults() {
		return array(
			'max_concurrent_calls' => defined( 'WP_DIALYRA_DEFAULT_MAX_CONCURRENT_CALLS' ) ? WP_DIALYRA_DEFAULT_MAX_CONCURRENT_CALLS : 1,
		);
	}

	/**
	 * Get default order status mapping settings.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_order_status_mapping_defaults() {
		return array(
			'confirmed_status'   => defined( 'WP_DIALYRA_DEFAULT_ORDER_CONFIRMED_STATUS' ) ? WP_DIALYRA_DEFAULT_ORDER_CONFIRMED_STATUS : 'processing',
			'cancelled_status'   => defined( 'WP_DIALYRA_DEFAULT_ORDER_CANCELLED_STATUS' ) ? WP_DIALYRA_DEFAULT_ORDER_CANCELLED_STATUS : 'cancelled',
			'no_answer_status'   => defined( 'WP_DIALYRA_DEFAULT_CALL_NO_ANSWER_STATUS' ) ? WP_DIALYRA_DEFAULT_CALL_NO_ANSWER_STATUS : 'no_change',
			'busy_status'        => defined( 'WP_DIALYRA_DEFAULT_CALL_BUSY_STATUS' ) ? WP_DIALYRA_DEFAULT_CALL_BUSY_STATUS : 'no_change',
			'failed_status'      => defined( 'WP_DIALYRA_DEFAULT_CALL_FAILED_STATUS' ) ? WP_DIALYRA_DEFAULT_CALL_FAILED_STATUS : 'no_change',
			'confirmed_note'     => defined( 'WP_DIALYRA_DEFAULT_ORDER_CONFIRMED_NOTE' ) ? WP_DIALYRA_DEFAULT_ORDER_CONFIRMED_NOTE : 'Dialyra call confirmed the order.',
			'cancelled_note'     => defined( 'WP_DIALYRA_DEFAULT_ORDER_CANCELLED_NOTE' ) ? WP_DIALYRA_DEFAULT_ORDER_CANCELLED_NOTE : 'Dialyra call cancelled the order.',
			'no_answer_note'     => defined( 'WP_DIALYRA_DEFAULT_CALL_NO_ANSWER_NOTE' ) ? WP_DIALYRA_DEFAULT_CALL_NO_ANSWER_NOTE : 'Dialyra call ended with no answer.',
			'busy_note'          => defined( 'WP_DIALYRA_DEFAULT_CALL_BUSY_NOTE' ) ? WP_DIALYRA_DEFAULT_CALL_BUSY_NOTE : 'Dialyra call reached a busy line.',
			'failed_note'        => defined( 'WP_DIALYRA_DEFAULT_CALL_FAILED_NOTE' ) ? WP_DIALYRA_DEFAULT_CALL_FAILED_NOTE : 'Dialyra call failed.',
			'skip_call_statuses' => defined( 'WP_DIALYRA_DEFAULT_SKIP_CALL_STATUSES' ) && is_array( WP_DIALYRA_DEFAULT_SKIP_CALL_STATUSES ) ? WP_DIALYRA_DEFAULT_SKIP_CALL_STATUSES : array( 'completed', 'cancelled', 'draft', 'refunded' ),
		);
	}

	/**
	 * Get default selectable business-hour days.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_business_hour_days() {
		return array(
			'all' => __( 'All', 'wp-dialyra' ),
			'mon' => __( 'Mon', 'wp-dialyra' ),
			'tue' => __( 'Tue', 'wp-dialyra' ),
			'wed' => __( 'Wed', 'wp-dialyra' ),
			'thu' => __( 'Thu', 'wp-dialyra' ),
			'fri' => __( 'Fri', 'wp-dialyra' ),
			'sat' => __( 'Sat', 'wp-dialyra' ),
			'sun' => __( 'Sun', 'wp-dialyra' ),
		);
	}

	/**
	 * Get default WooCommerce status choices used by setup placeholders.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_default_order_statuses() {
		return array(
			'processing' => __( 'Processing', 'wp-dialyra' ),
			'pending'    => __( 'Pending payment', 'wp-dialyra' ),
			'on-hold'    => __( 'On hold', 'wp-dialyra' ),
			'completed'  => __( 'Completed', 'wp-dialyra' ),
			'cancelled'  => __( 'Cancelled', 'wp-dialyra' ),
			'no_change'  => __( 'Keep current status', 'wp-dialyra' ),
		);
	}

	/**
	 * Get default API configuration values.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_api_defaults() {
		return array(
			'base_url' => defined( 'DIALYRA_API_BASE_URL' ) ? DIALYRA_API_BASE_URL : 'https://api.dialyra.it.com/api',
			'version'  => 'v3',
			'timeout'  => 30,
		);
	}

	/**
	 * Get the site timezone with a safe fallback.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public static function get_default_timezone() {
		$timezone = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : '';

		if ( $timezone && in_array( $timezone, timezone_identifiers_list(), true ) ) {
			return $timezone;
		}

		if ( '+06:00' === $timezone ) {
			return 'Asia/Dhaka';
		}

		return 'UTC';
	}
}
