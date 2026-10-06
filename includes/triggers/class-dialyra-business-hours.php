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
		$now      = new DateTimeImmutable( 'now', $this->get_timezone( $settings ) );

		return 'open' === $this->get_availability_reason( $settings, $now );
	}

	/**
	 * Get the next valid automatic call time.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function get_next_valid_call_time() {
		$settings = $this->get_settings();

		if ( $this->is_always_active_mode( sanitize_key( $settings['availability_mode'] ?? 'always_active' ) ) ) {
			return current_time( 'mysql' );
		}

		$timezone  = $this->get_timezone( $settings );
		$now       = new DateTimeImmutable( 'now', $timezone );

		if ( 'open' === $this->get_availability_reason( $settings, $now ) ) {
			return current_time( 'mysql' );
		}

		for ( $offset = 0; $offset <= 14; $offset++ ) {
			$candidate = $now->modify( '+' . $offset . ' days' );
			if ( ! $this->is_selected_day( $settings, $candidate ) ) {
				continue;
			}

			$window    = $this->get_window( $settings, $candidate );
			$call_time = $window ? $window['open'] : null;

			if ( $call_time && $call_time > $now ) {
				return $call_time->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
			}
		}

		return $now->modify( '+1 day' )->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Get a lightweight snapshot for audit logs.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function get_audit_context() {
		$settings = $this->get_settings();
		$timezone = $this->get_timezone( $settings );
		$now      = new DateTimeImmutable( 'now', $timezone );
		$mode     = sanitize_key( $settings['availability_mode'] ?? 'always_active' );

		return array(
			'is_calling_allowed' => 'open' === $this->get_availability_reason( $settings, $now ),
			'availability_reason' => $this->get_availability_reason( $settings, $now ),
			'availability_mode' => $mode,
			'is_always_active'  => $this->is_always_active_mode( $mode ),
			'timezone'          => $timezone->getName(),
			'current_time'      => $now->format( 'Y-m-d H:i:s' ),
			'days'              => ! empty( $settings['days'] ) && is_array( $settings['days'] ) ? array_values( array_map( 'sanitize_key', $settings['days'] ) ) : array( 'all' ),
			'open_time'         => sanitize_text_field( $settings['open_time'] ?? '09:00' ),
			'close_time'        => sanitize_text_field( $settings['close_time'] ?? '18:00' ),
			'next_valid_time'   => $this->get_next_valid_call_time(),
		);
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

		return array_replace( $defaults, is_array( $settings ) ? $settings : array() );
	}

	private function is_selected_day( $settings, DateTimeImmutable $date ) {
		$days = ! empty( $settings['days'] ) && is_array( $settings['days'] ) ? array_map( 'sanitize_key', $settings['days'] ) : array( 'all' );

		return in_array( 'all', $days, true ) || in_array( strtolower( $date->format( 'D' ) ), $days, true );
	}

	private function get_window( $settings, DateTimeImmutable $date ) {
		$times = array(
			'open'  => sanitize_text_field( $settings['open_time'] ?? '09:00' ),
			'close' => sanitize_text_field( $settings['close_time'] ?? '18:00' ),
		);
		$window = array();

		foreach ( $times as $key => $time ) {
			if ( ! preg_match( '/^([01][0-9]|2[0-3]):([0-5][0-9])(?::([0-5][0-9]))?$/', $time, $parts ) ) {
				return null;
			}

			$window[ $key ] = $date->setTime( (int) $parts[1], (int) $parts[2], (int) ( $parts[3] ?? 0 ) );
		}

		if ( $window['close'] == $window['open'] ) {
			return null;
		}

		if ( $window['close'] < $window['open'] ) {
			$window['close'] = $window['close']->modify( '+1 day' );
		}

		return $window;
	}

	private function get_availability_reason( $settings, DateTimeImmutable $now ) {
		$mode = sanitize_key( $settings['availability_mode'] ?? 'always_active' );

		if ( $this->is_always_active_mode( $mode ) ) {
			return 'open';
		}

		if ( 'scheduled' !== $mode ) {
			return 'invalid_availability_mode';
		}

		if ( ! $this->get_window( $settings, $now ) ) {
			return 'invalid_time_window';
		}

		foreach ( array( $now, $now->modify( '-1 day' ) ) as $date ) {
			$window = $this->get_window( $settings, $date );

			if ( $this->is_selected_day( $settings, $date ) && $now >= $window['open'] && $now < $window['close'] ) {
				return 'open';
			}
		}

		return $this->is_selected_day( $settings, $now ) ? 'outside_operating_hours' : 'day_not_selected';
	}

	/**
	 * Check whether a business-hours mode means calls are always allowed.
	 *
	 * @since    1.0.0
	 * @param    string    $mode    Availability mode.
	 * @return   bool
	 */
	private function is_always_active_mode( $mode ) {
		return in_array( sanitize_key( $mode ), array( '', 'always_active', 'always_open', 'open', 'all_day', '24_7' ), true );
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
