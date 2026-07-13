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
	 * Get default call trigger settings.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_call_trigger_defaults() {
		return array(
			'mode'          => 'instant',
			'order_status'  => 'processing',
			'delay_minutes' => 5,
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
			'max_attempts'               => 2,
			'delay_minutes'              => 15,
			'only_during_business_hours' => true,
			'stop_on_confirmed'          => true,
			'stop_on_cancelled'          => true,
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
			'availability_mode' => 'always_active',
			'days'              => array( 'all' ),
			'open_time'         => '09:00',
			'close_time'        => '18:00',
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
			'max_concurrent_calls' => 1,
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
			'confirmed_status' => 'processing',
			'cancelled_status' => 'cancelled',
			'no_answer_status' => 'no_change',
			'busy_status'      => 'no_change',
			'failed_status'    => 'no_change',
			'order_note'       => __( 'Updated by Dialyra call result.', 'wp-dialyra' ),
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

		return $timezone ? $timezone : 'UTC';
	}
}
