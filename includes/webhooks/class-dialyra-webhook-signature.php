<?php

/**
 * Dialyra webhook signature verification.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/webhooks
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Webhook_Signature {

	const DEFAULT_TOLERANCE_SECONDS = 300;

	/**
	 * Validate timestamp and HMAC signature headers.
	 *
	 * @since    1.0.0
	 * @param    string    $raw_body     Raw JSON request body.
	 * @param    string    $timestamp    X-Dialyra-Timestamp value.
	 * @param    string    $signature    X-Dialyra-Signature value.
	 * @param    string    $secret       Webhook secret.
	 * @return   true|WP_Error
	 */
	public function verify( $raw_body, $timestamp, $signature, $secret ) {
		$raw_body  = is_string( $raw_body ) ? $raw_body : '';
		$timestamp = sanitize_text_field( $timestamp );
		$signature = sanitize_text_field( $signature );
		$secret    = is_string( $secret ) ? $secret : '';

		if ( '' === $secret ) {
			return new WP_Error( 'dialyra_webhook_secret_missing', __( 'Dialyra webhook secret is not configured.', 'wp-dialyra' ), array( 'status' => 401 ) );
		}

		if ( '' === $timestamp || ! $this->is_timestamp_valid( $timestamp ) ) {
			return new WP_Error( 'dialyra_webhook_timestamp_invalid', __( 'Dialyra webhook timestamp is invalid or expired.', 'wp-dialyra' ), array( 'status' => 401 ) );
		}

		if ( ! preg_match( '/^sha256=[a-f0-9]{64}$/i', $signature ) ) {
			return new WP_Error( 'dialyra_webhook_signature_invalid', __( 'Dialyra webhook signature format is invalid.', 'wp-dialyra' ), array( 'status' => 401 ) );
		}

		$expected = 'sha256=' . hash_hmac( 'sha256', $timestamp . '.' . $raw_body, $secret );

		if ( ! hash_equals( $expected, strtolower( $signature ) ) ) {
			return new WP_Error( 'dialyra_webhook_signature_invalid', __( 'Dialyra webhook signature is invalid.', 'wp-dialyra' ), array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Validate timestamp tolerance.
	 *
	 * @since    1.0.0
	 * @param    string    $timestamp    Header timestamp.
	 * @return   bool
	 */
	private function is_timestamp_valid( $timestamp ) {
		if ( is_numeric( $timestamp ) ) {
			$event_time = (int) $timestamp;

			if ( $event_time > 9999999999 ) {
				$event_time = (int) floor( $event_time / 1000 );
			}
		} else {
			$event_time = strtotime( $timestamp );
		}

		if ( ! $event_time ) {
			return false;
		}

		return abs( time() - $event_time ) <= self::DEFAULT_TOLERANCE_SECONDS;
	}
}
