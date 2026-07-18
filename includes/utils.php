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
			'base_url' => 'http://127.0.0.1:5001/api',
			'version'  => 'v2',
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
