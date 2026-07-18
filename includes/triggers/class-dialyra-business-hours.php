<?php

/**
 * Dialyra business-hours evaluator.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/triggers
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Business_Hours {

	/**
	 * Check whether automatic calling is allowed now.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function is_calling_allowed_now() {
		$settings = $this->get_settings();
		$mode     = sanitize_key( $settings['availability_mode'] ?? 'always_active' );

		if ( 'always_active' === $mode ) {
			return true;
		}

		if ( 'scheduled' !== $mode ) {
			return false;
		}

		$now       = new DateTimeImmutable( 'now', $this->get_timezone( $settings ) );
		$day_key   = strtolower( $now->format( 'D' ) );
		$day_key   = 'thu' === $day_key ? 'thu' : substr( $day_key, 0, 3 );
		$days      = ! empty( $settings['days'] ) && is_array( $settings['days'] ) ? array_map( 'sanitize_key', $settings['days'] ) : array( 'all' );
		$open_time = sanitize_text_field( $settings['open_time'] ?? '09:00' );
		$close_time = sanitize_text_field( $settings['close_time'] ?? '18:00' );

		if ( ! in_array( 'all', $days, true ) && ! in_array( $day_key, $days, true ) ) {
			return false;
		}

		$open  = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $now->format( 'Y-m-d' ) . ' ' . $open_time, $this->get_timezone( $settings ) );
		$close = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $now->format( 'Y-m-d' ) . ' ' . $close_time, $this->get_timezone( $settings ) );

		return $open && $close && $now >= $open && $now <= $close;
	}

	/**
	 * Get the next valid automatic call time.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function get_next_valid_call_time() {
		$settings = $this->get_settings();

		if ( 'always_active' === sanitize_key( $settings['availability_mode'] ?? 'always_active' ) ) {
			return current_time( 'mysql' );
		}

		$timezone  = $this->get_timezone( $settings );
		$now       = new DateTimeImmutable( 'now', $timezone );
		$days      = ! empty( $settings['days'] ) && is_array( $settings['days'] ) ? array_map( 'sanitize_key', $settings['days'] ) : array( 'all' );
		$open_time = sanitize_text_field( $settings['open_time'] ?? '09:00' );

		for ( $offset = 0; $offset <= 14; $offset++ ) {
			$candidate = $now->modify( '+' . $offset . ' days' );
			$day_key   = strtolower( $candidate->format( 'D' ) );
			$day_key   = 'thu' === $day_key ? 'thu' : substr( $day_key, 0, 3 );

			if ( ! in_array( 'all', $days, true ) && ! in_array( $day_key, $days, true ) ) {
				continue;
			}

			$call_time = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $candidate->format( 'Y-m-d' ) . ' ' . $open_time, $timezone );

			if ( $call_time && $call_time > $now ) {
				return $call_time->format( 'Y-m-d H:i:s' );
			}
		}

		return $now->modify( '+1 day' )->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Get business-hours settings.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function get_settings() {
		$defaults = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_business_hours_defaults() : array();
		$settings = defined( 'WP_DIALYRA_OPTION_BUSINESS_HOURS' ) ? get_option( WP_DIALYRA_OPTION_BUSINESS_HOURS, array() ) : array();

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			$setup = defined( 'WP_DIALYRA_OPTION_SETUP_SETTINGS' ) ? get_option( WP_DIALYRA_OPTION_SETUP_SETTINGS, array() ) : array();
			$settings = is_array( $setup ) && isset( $setup['business_hours'] ) && is_array( $setup['business_hours'] ) ? $setup['business_hours'] : array();
		}

		return array_replace_recursive( $defaults, is_array( $settings ) ? $settings : array() );
	}

	/**
	 * Get configured timezone.
	 *
	 * @since    1.0.0
	 * @param    array    $settings    Business-hours settings.
	 * @return   DateTimeZone
	 */
	private function get_timezone( $settings ) {
		$timezone = ! empty( $settings['timezone'] ) ? sanitize_text_field( $settings['timezone'] ) : wp_timezone_string();

		try {
			return new DateTimeZone( $timezone );
		} catch ( Exception $exception ) {
			return wp_timezone();
		}
	}
}
