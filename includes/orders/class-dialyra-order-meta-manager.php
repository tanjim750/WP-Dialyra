<?php

/**
 * Dialyra order meta helper.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/orders
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Order_Meta_Manager {

	/**
	 * Mirror minimal latest Dialyra call state to WooCommerce order meta.
	 *
	 * @since    1.0.0
	 * @param    WC_Order    $order    WooCommerce order.
	 * @param    array       $event    Normalized event.
	 */
	public function update_latest_call_meta( $order, $event ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) ) {
			return;
		}

		if ( ! empty( $event['call_session_id'] ) ) {
			$order->update_meta_data( '_dialyra_last_call_session_id', absint( $event['call_session_id'] ) );
		}

		if ( ! empty( $event['call_log_id'] ) ) {
			$order->update_meta_data( '_dialyra_last_call_log_id', absint( $event['call_log_id'] ) );
		}

		if ( ! empty( $event['call_status'] ) ) {
			$order->update_meta_data( '_dialyra_last_call_status', sanitize_text_field( $event['call_status'] ) );
		}

		if ( ! empty( $event['order_action'] ) ) {
			$order->update_meta_data( '_dialyra_last_order_action', sanitize_key( $event['order_action'] ) );
		}

		foreach ( $this->get_text_meta_fields() as $event_key => $meta_key ) {
			if ( isset( $event[ $event_key ] ) && null !== $event[ $event_key ] && '' !== $event[ $event_key ] ) {
				$order->update_meta_data( $meta_key, sanitize_text_field( $event[ $event_key ] ) );
			}
		}

		foreach ( $this->get_integer_meta_fields() as $event_key => $meta_key ) {
			if ( isset( $event[ $event_key ] ) && null !== $event[ $event_key ] && '' !== $event[ $event_key ] ) {
				$order->update_meta_data( $meta_key, absint( $event[ $event_key ] ) );
			}
		}

		if ( ! empty( $event['dtmf_sequence'] ) && is_array( $event['dtmf_sequence'] ) ) {
			$order->update_meta_data( '_dialyra_last_call_dtmf', implode( ', ', array_map( 'sanitize_text_field', $event['dtmf_sequence'] ) ) );
		} elseif ( ! empty( $event['dtmf_value'] ) ) {
			$order->update_meta_data( '_dialyra_last_call_dtmf', sanitize_text_field( $event['dtmf_value'] ) );
		}

		if ( ! empty( $event['ended_at'] ) || ! empty( $event['occurred_at'] ) ) {
			$call_time = ! empty( $event['ended_at'] ) ? $event['ended_at'] : $event['occurred_at'];
			$order->update_meta_data( '_dialyra_last_call_at', sanitize_text_field( $call_time ) );
		}
	}

	/**
	 * Get normalized text fields mirrored from webhook event to Woo order meta.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function get_text_meta_fields() {
		return array(
			'dialed_number'  => '_dialyra_last_call_number',
			'from_number'    => '_dialyra_last_call_from_number',
			'billing_status' => '_dialyra_last_call_billing_status',
			'billing_amount' => '_dialyra_last_call_cost',
			'hangup_cause'   => '_dialyra_last_call_hangup_cause',
		);
	}

	/**
	 * Get normalized integer fields mirrored from webhook event to Woo order meta.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function get_integer_meta_fields() {
		return array(
			'flow_id'           => '_dialyra_last_call_flow_id',
			'sip_trunk_id'      => '_dialyra_last_call_sip_trunk_id',
			'duration_seconds'  => '_dialyra_last_call_duration_seconds',
			'bill_seconds'      => '_dialyra_last_call_bill_seconds',
		);
	}
}
