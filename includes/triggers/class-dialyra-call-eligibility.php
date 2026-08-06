<?php

/**
 * Dialyra automatic call eligibility checks.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/triggers
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Call_Eligibility {

	/**
	 * Determine whether an order can be automatically called now.
	 *
	 * @since    1.0.0
	 * @param    int    $order_id    WooCommerce order ID.
	 * @return   array
	 */
	public function can_call_order( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return $this->result( false, 'woocommerce_unavailable' );
		}

		$order = wc_get_order( absint( $order_id ) );

		if ( ! $order ) {
			return $this->result( false, 'order_not_found' );
		}

		if ( ! $this->has_valid_phone( $order ) ) {
			return $this->result( false, 'invalid_phone', $order );
		}

		if ( in_array( sanitize_key( $order->get_status() ), $this->get_skip_call_statuses(), true ) ) {
			return $this->result( false, 'skip_status', $order );
		}

		$order_action = sanitize_key( $order->get_meta( '_dialyra_last_order_action', true ) );

		if ( 'confirmed' === $order_action ) {
			return $this->result( false, 'order_already_confirmed', $order );
		}

		if ( 'cancelled' === $order_action ) {
			return $this->result( false, 'order_already_cancelled', $order );
		}

		if ( $this->is_recent_active_call( $order ) ) {
			return $this->result( false, 'active_call_exists', $order );
		}

		return $this->result( true, 'eligible', $order );
	}

	/**
	 * Check whether plugin-side concurrency capacity is available.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function has_concurrency_capacity() {
		$context = $this->get_concurrency_context();

		return absint( $context['active_calls'] ?? 0 ) < max( 1, absint( $context['max_concurrent_calls'] ?? 1 ) );
	}

	/**
	 * Get concurrency data for audit/debug visibility.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function get_concurrency_context() {
		$active_order_ids = $this->get_recent_active_call_order_ids();

		return array(
			'max_concurrent_calls' => $this->get_max_concurrent_calls(),
			'active_calls'         => count( $active_order_ids ),
			'active_order_ids'     => $active_order_ids,
			'active_statuses'      => $this->get_active_call_statuses(),
			'fresh_within_minutes' => $this->get_active_call_timeout_minutes(),
			'cutoff_time'          => $this->get_active_call_cutoff_time(),
		);
	}

	/**
	 * Check if the order has a usable phone.
	 *
	 * @since    1.0.0
	 * @param    WC_Order    $order    WooCommerce order.
	 * @return   bool
	 */
	private function has_valid_phone( $order ) {
		$phone = is_object( $order ) && method_exists( $order, 'get_billing_phone' ) ? sanitize_text_field( $order->get_billing_phone() ) : '';
		$digits = preg_replace( '/\D/', '', $phone );

		return strlen( $digits ) >= 7 && strlen( $digits ) <= 15;
	}

	/**
	 * Get skip-call statuses from dedicated option first, then setup/defaults.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function get_skip_call_statuses() {
		$statuses = defined( 'WP_DIALYRA_OPTION_SKIP_CALL_STATUSES' ) ? get_option( WP_DIALYRA_OPTION_SKIP_CALL_STATUSES, array() ) : array();

		if ( empty( $statuses ) || ! is_array( $statuses ) ) {
			$setup = defined( 'WP_DIALYRA_OPTION_SETUP_SETTINGS' ) ? get_option( WP_DIALYRA_OPTION_SETUP_SETTINGS, array() ) : array();
			$statuses = is_array( $setup ) && isset( $setup['order_status_map']['skip_call_statuses'] ) ? $setup['order_status_map']['skip_call_statuses'] : array();
		}

		if ( empty( $statuses ) && defined( 'WP_DIALYRA_DEFAULT_SKIP_CALL_STATUSES' ) && is_array( WP_DIALYRA_DEFAULT_SKIP_CALL_STATUSES ) ) {
			$statuses = WP_DIALYRA_DEFAULT_SKIP_CALL_STATUSES;
		}

		return array_values( array_filter( array_map( 'sanitize_key', is_array( $statuses ) ? $statuses : array() ) ) );
	}

	/**
	 * Count active call references from recent WooCommerce orders.
	 *
	 * @since    1.0.0
	 * @return   int
	 */
	private function get_recent_active_call_order_ids() {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'return'     => 'ids',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => '_dialyra_last_call_status',
						'value'   => $this->get_active_call_statuses(),
						'compare' => 'IN',
					),
					array(
						'key'     => '_dialyra_last_call_at',
						'value'   => $this->get_active_call_cutoff_time(),
						'compare' => '>=',
						'type'    => 'DATETIME',
					),
				),
			)
		);

		return array_values( array_map( 'absint', is_array( $orders ) ? $orders : array() ) );
	}

	/**
	 * Determine whether an order has a fresh active call marker.
	 *
	 * @since    1.0.0
	 * @param    WC_Order    $order    WooCommerce order.
	 * @return   bool
	 */
	private function is_recent_active_call( $order ) {
		$status = sanitize_key( is_object( $order ) && method_exists( $order, 'get_meta' ) ? $order->get_meta( '_dialyra_last_call_status', true ) : '' );

		if ( ! in_array( $status, $this->get_active_call_statuses(), true ) ) {
			return false;
		}

		$last_call_at = is_object( $order ) && method_exists( $order, 'get_meta' ) ? sanitize_text_field( $order->get_meta( '_dialyra_last_call_at', true ) ) : '';
		$last_call_ts = $last_call_at ? strtotime( $last_call_at ) : 0;

		return $last_call_ts && $last_call_ts >= strtotime( $this->get_active_call_cutoff_time() );
	}

	/**
	 * Get max concurrent call setting.
	 *
	 * @since    1.0.0
	 * @return   int
	 */
	private function get_max_concurrent_calls() {
		$max_concurrent_calls = defined( 'WP_DIALYRA_OPTION_MAX_CONCURRENT_CALLS' ) ? absint( get_option( WP_DIALYRA_OPTION_MAX_CONCURRENT_CALLS, 0 ) ) : 0;

		if ( ! $max_concurrent_calls ) {
			$setup = defined( 'WP_DIALYRA_OPTION_SETUP_SETTINGS' ) ? get_option( WP_DIALYRA_OPTION_SETUP_SETTINGS, array() ) : array();
			$max_concurrent_calls = isset( $setup['call_capacity']['max_concurrent_calls'] ) ? absint( $setup['call_capacity']['max_concurrent_calls'] ) : 0;
		}

		$max_concurrent_calls = $max_concurrent_calls ? $max_concurrent_calls : ( defined( 'WP_DIALYRA_DEFAULT_MAX_CONCURRENT_CALLS' ) ? WP_DIALYRA_DEFAULT_MAX_CONCURRENT_CALLS : 1 );

		return max( 1, absint( $max_concurrent_calls ) );
	}

	/**
	 * Get active call statuses used for concurrency.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function get_active_call_statuses() {
		return array( 'initiated', 'ringing', 'answered' );
	}

	/**
	 * Get active call freshness timeout.
	 *
	 * @since    1.0.0
	 * @return   int
	 */
	private function get_active_call_timeout_minutes() {
		return defined( 'WP_DIALYRA_ACTIVE_CALL_TIMEOUT_MINUTES' ) ? max( 1, absint( WP_DIALYRA_ACTIVE_CALL_TIMEOUT_MINUTES ) ) : 30;
	}

	/**
	 * Get cutoff time for active call freshness.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_active_call_cutoff_time() {
		return date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( $this->get_active_call_timeout_minutes() * MINUTE_IN_SECONDS ) );
	}

	/**
	 * Build a structured eligibility result.
	 *
	 * @since    1.0.0
	 * @param    bool      $eligible    Eligibility flag.
	 * @param    string    $reason      Reason code.
	 * @param    mixed     $order       Optional order.
	 * @return   array
	 */
	private function result( $eligible, $reason, $order = null ) {
		return array(
			'eligible' => (bool) $eligible,
			'reason'   => sanitize_key( $reason ),
			'order'    => $order,
		);
	}
}
