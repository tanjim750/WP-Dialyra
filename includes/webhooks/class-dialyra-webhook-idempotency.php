<?php

/**
 * Dialyra webhook idempotency storage.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/webhooks
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Webhook_Idempotency {

	const TABLE_VERSION = '1.0.0';

	/**
	 * Reserve an event ID for processing.
	 *
	 * @since    1.0.0
	 * @param    string    $event_id      Event ID.
	 * @param    string    $event_type    Event type.
	 * @return   bool
	 */
	public function reserve_event( $event_id, $event_type = '' ) {
		global $wpdb;

		$event_id = sanitize_text_field( $event_id );

		if ( '' === $event_id ) {
			return false;
		}

		$this->maybe_install_table();

		$inserted = $wpdb->insert(
			$this->get_table_name(),
			array(
				'event_id'   => $event_id,
				'event_type' => sanitize_text_field( $event_type ),
				'status'     => 'received',
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		return (bool) $inserted;
	}

	/**
	 * Mark event processed after dispatch.
	 *
	 * @since    1.0.0
	 * @param    string    $event_id    Event ID.
	 */
	public function mark_processed( $event_id ) {
		global $wpdb;

		$this->maybe_install_table();

		$wpdb->update(
			$this->get_table_name(),
			array(
				'status'       => 'processed',
				'processed_at' => current_time( 'mysql' ),
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'event_id' => sanitize_text_field( $event_id ) ),
			array( '%s', '%s', '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Get webhook events table name.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'dialyra_webhook_events';
	}

	/**
	 * Install webhook events table.
	 *
	 * @since    1.0.0
	 */
	public static function install_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'dialyra_webhook_events';
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id VARCHAR(191) NOT NULL,
			event_type VARCHAR(100) NOT NULL DEFAULT '',
			status VARCHAR(30) NOT NULL DEFAULT 'received',
			created_at DATETIME NOT NULL,
			processed_at DATETIME NULL DEFAULT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_id (event_id),
			KEY event_type (event_type),
			KEY status (status)
		) {$charset_collate};";

		dbDelta( $sql );

		if ( defined( 'WP_DIALYRA_OPTION_WEBHOOK_EVENTS_TABLE_VERSION' ) ) {
			update_option( WP_DIALYRA_OPTION_WEBHOOK_EVENTS_TABLE_VERSION, self::TABLE_VERSION, false );
		}
	}

	/**
	 * Install table for existing plugin installs when needed.
	 *
	 * @since    1.0.0
	 */
	private function maybe_install_table() {
		$version = defined( 'WP_DIALYRA_OPTION_WEBHOOK_EVENTS_TABLE_VERSION' ) ? get_option( WP_DIALYRA_OPTION_WEBHOOK_EVENTS_TABLE_VERSION, '' ) : '';

		if ( self::TABLE_VERSION !== $version ) {
			self::install_table();
		}
	}
}
