<?php

/**
 * Dialyra retry policy reader.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/retries
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Retry_Policy {

	/**
	 * Check whether retry processing is enabled.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function is_enabled() {
		return $this->get_max_attempts() > 0;
	}

	/**
	 * Get maximum retry attempts.
	 *
	 * @since    1.0.0
	 * @return   int
	 */
	public function get_max_attempts() {
		$settings = $this->get_settings();

		return isset( $settings['max_attempts'] ) ? max( 0, absint( $settings['max_attempts'] ) ) : 0;
	}

	/**
	 * Get retry delay in minutes.
	 *
	 * @since    1.0.0
	 * @return   int
	 */
	public function get_delay_minutes() {
		$settings = $this->get_settings();

		return isset( $settings['delay_minutes'] ) ? max( 1, absint( $settings['delay_minutes'] ) ) : 15;
	}

	/**
	 * Check whether retries are limited to business hours.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function only_during_business_hours() {
		$settings = $this->get_settings();

		return ! empty( $settings['only_during_business_hours'] );
	}

	/**
	 * Get retry policy settings.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function get_settings() {
		$defaults = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_retry_policy_defaults() : array();
		$settings = defined( 'WP_DIALYRA_OPTION_RETRY_POLICY' ) ? get_option( WP_DIALYRA_OPTION_RETRY_POLICY, array() ) : array();

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			$setup = defined( 'WP_DIALYRA_OPTION_SETUP_SETTINGS' ) ? get_option( WP_DIALYRA_OPTION_SETUP_SETTINGS, array() ) : array();
			$settings = is_array( $setup ) && isset( $setup['retry_policy'] ) && is_array( $setup['retry_policy'] ) ? $setup['retry_policy'] : array();
		}

		return array_replace_recursive( $defaults, is_array( $settings ) ? $settings : array() );
	}
}
