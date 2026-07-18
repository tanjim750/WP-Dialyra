<?php

/**
 * Dialyra webhook event normalizer.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/webhooks
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Webhook_Event_Normalizer {

	/**
	 * Normalize a Dialyra webhook payload for internal hooks.
	 *
	 * @since    1.0.0
	 * @param    array     $payload       Decoded payload.
	 * @param    string    $event_id      Validated event ID.
	 * @param    string    $event_type    Event type.
	 * @return   array
	 */
	public function normalize( $payload, $event_id, $event_type ) {
		$payload         = is_array( $payload ) ? $payload : array();
		$event           = $this->array_value( $payload['event'] ?? array() );
		$call            = $this->array_value( $payload['call'] ?? array() );
		$template_values = $this->array_value( $payload['template_values'] ?? ( $payload['webhook_variables'] ?? array() ) );
		$timeline        = $this->array_value( $call['timeline'] ?? ( $payload['timeline'] ?? array() ) );
		$dtmf_history    = $this->array_value( $timeline['dtmf_events'] ?? ( $payload['dtmf_history'] ?? array() ) );

		return array(
			'event_id'         => sanitize_text_field( $event_id ),
			'event_type'       => sanitize_text_field( $event_type ),
			'occurred_at'      => $this->text_value( $event['occurred_at'] ?? ( $event['created_at'] ?? ( $payload['occurred_at'] ?? '' ) ) ),
			'business_id'      => $this->nullable_absint( $event['business_id'] ?? ( $call['business_id'] ?? ( $payload['business_id'] ?? null ) ) ),
			'call_session_id'  => $this->nullable_absint( $call['call_session_id'] ?? ( $call['id'] ?? ( $payload['call_session_id'] ?? null ) ) ),
			'call_log_id'      => $this->nullable_absint( $call['call_log_id'] ?? ( $payload['call_log_id'] ?? null ) ),
			'call_status'      => $this->text_value( $call['call_status'] ?? ( $call['status'] ?? ( $payload['call_status'] ?? '' ) ) ),
			'order_id'         => $this->nullable_absint( $template_values['order_id'] ?? ( $call['order_id'] ?? ( $payload['order_id'] ?? null ) ) ),
			'order_action'     => $this->order_action_value( $template_values['order_action'] ?? ( $call['order_action'] ?? ( $payload['order_action'] ?? 'none' ) ) ),
			'dialed_number'    => $this->text_value( $call['dialed_number'] ?? ( $call['to_number'] ?? ( $payload['dialed_number'] ?? '' ) ) ),
			'from_number'      => $this->text_value( $call['from_number'] ?? ( $payload['from_number'] ?? '' ) ),
			'flow_id'          => $this->nullable_absint( $call['flow_id'] ?? ( $payload['flow_id'] ?? null ) ),
			'flow_version_id'  => $this->nullable_absint( $call['flow_version_id'] ?? ( $payload['flow_version_id'] ?? null ) ),
			'sip_trunk_id'     => $this->nullable_absint( $call['sip_trunk_id'] ?? ( $payload['sip_trunk_id'] ?? null ) ),
			'started_at'       => $this->text_value( $call['started_at'] ?? ( $payload['started_at'] ?? '' ) ),
			'answered_at'      => $this->text_value( $call['answered_at'] ?? ( $payload['answered_at'] ?? '' ) ),
			'ended_at'         => $this->text_value( $call['ended_at'] ?? ( $payload['ended_at'] ?? '' ) ),
			'duration_seconds' => $this->nullable_absint( $call['duration_seconds'] ?? ( $call['duration_sec'] ?? ( $payload['duration_seconds'] ?? null ) ) ),
			'bill_seconds'     => $this->nullable_absint( $call['bill_seconds'] ?? ( $call['billsec'] ?? ( $payload['bill_seconds'] ?? null ) ) ),
			'billing_status'   => $this->text_value( $call['billing_status'] ?? ( $call['billing_clear_reason'] ?? ( $payload['billing_status'] ?? '' ) ) ),
			'billing_amount'   => $this->text_value( $call['billing_charged_amount'] ?? ( $call['billing_reserved_amount'] ?? ( $payload['billing_charged_amount'] ?? '' ) ) ),
			'hangup_cause'     => $this->text_value( $call['hangup_cause_text'] ?? ( $call['hangup_cause'] ?? ( $payload['hangup_cause'] ?? '' ) ) ),
			'dtmf_value'       => $this->text_value( $payload['dtmf_value'] ?? ( $payload['digits'] ?? '' ) ),
			'dtmf_sequence'    => $this->dtmf_sequence( $dtmf_history ),
			'dtmf_history'     => $dtmf_history,
			'raw_payload'      => $payload,
		);
	}

	/**
	 * Get array value.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
	 * @return   array
	 */
	private function array_value( $value ) {
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Sanitize text value.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
	 * @return   string|null
	 */
	private function text_value( $value ) {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Normalize nullable integer.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
	 * @return   int|null
	 */
	private function nullable_absint( $value ) {
		$value = absint( $value );

		return $value ? $value : null;
	}

	/**
	 * Normalize order action.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw action.
	 * @return   string
	 */
	private function order_action_value( $value ) {
		$value = sanitize_key( $value );

		return in_array( $value, array( 'none', 'confirmed', 'cancelled' ), true ) ? $value : 'none';
	}

	/**
	 * Extract DTMF sequence.
	 *
	 * @since    1.0.0
	 * @param    array    $history    DTMF events.
	 * @return   array
	 */
	private function dtmf_sequence( $history ) {
		$sequence = array();

		foreach ( $history as $event ) {
			if ( is_array( $event ) && isset( $event['digits'] ) ) {
				$sequence[] = sanitize_text_field( $event['digits'] );
			}
		}

		return $sequence;
	}
}
