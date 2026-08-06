<?php

/**
 * Dialyra initial call queue repository.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/triggers
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Call_Queue_Repository {

	const TABLE_VERSION = '1.0.0';

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->maybe_install_table();
	}

	/**
	 * Install the initial call queue table.
	 *
	 * @since    1.0.0
	 */
	public static function install_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			business_id bigint(20) unsigned NOT NULL,
			order_id bigint(20) unsigned NOT NULL,
			source varchar(32) NOT NULL,
			status varchar(32) NOT NULL DEFAULT 'pending',
			scheduled_at datetime NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY business_order_status (business_id, order_id, status),
			KEY status_scheduled (status, scheduled_at)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( self::get_table_version_option(), self::TABLE_VERSION, false );
	}

	/**
	 * Add or update a pending queue record without creating duplicates.
	 *
	 * @since    1.0.0
	 * @param    int       $business_id     Business ID.
	 * @param    int       $order_id        Order ID.
	 * @param    string    $source          Queue source.
	 * @param    string    $scheduled_at    Scheduled datetime.
	 * @return   int
	 */
	public function upsert_pending( $business_id, $order_id, $source, $scheduled_at ) {
		global $wpdb;

		$business_id  = absint( $business_id );
		$order_id     = absint( $order_id );
		$source       = sanitize_key( $source );
		$scheduled_at = sanitize_text_field( $scheduled_at );

		if ( ! $business_id || ! $order_id || ! in_array( $source, array( 'delay', 'business_hours', 'concurrency', 'unauthorized', 'payment_required', 'invalid_flow', 'originate_error' ), true ) ) {
			return 0;
		}

		$table_name = self::get_table_name();
		$now        = current_time( 'mysql' );
		$existing_id = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table_name} WHERE business_id = %d AND order_id = %d AND status IN ('pending', 'processing') ORDER BY id ASC LIMIT 1",
					$business_id,
					$order_id
				)
			)
		);

		if ( $existing_id ) {
			$wpdb->update(
				$table_name,
				array(
					'source'       => $source,
					'status'       => 'pending',
					'scheduled_at' => $scheduled_at,
					'updated_at'   => $now,
				),
				array( 'id' => $existing_id ),
				array( '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			return $existing_id;
		}

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'business_id'  => $business_id,
				'order_id'     => $order_id,
				'source'       => $source,
				'status'       => 'pending',
				'scheduled_at' => $scheduled_at,
				'created_at'   => $now,
				'updated_at'   => $now,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return false === $inserted ? 0 : absint( $wpdb->insert_id );
	}

	/**
	 * Get due pending queue rows.
	 *
	 * @since    1.0.0
	 * @param    int    $limit    Row limit.
	 * @return   array
	 */
	public function get_due_pending( $limit = 10 ) {
		global $wpdb;

		$this->recover_stale_processing();

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::get_table_name() . ' WHERE status = %s AND scheduled_at <= %s ORDER BY scheduled_at ASC, id ASC LIMIT %d',
				'pending',
				current_time( 'mysql' ),
				max( 1, absint( $limit ) )
			),
			ARRAY_A
		);
	}

	/**
	 * Get a queue row by ID.
	 *
	 * @since    1.0.0
	 * @param    int    $queue_id    Queue row ID.
	 * @return   array
	 */
	public function get_by_id( $queue_id ) {
		global $wpdb;

		$queue_id = absint( $queue_id );

		if ( ! $queue_id ) {
			return array();
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::get_table_name() . ' WHERE id = %d LIMIT 1',
				$queue_id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : array();
	}

	/**
	 * Atomically claim a queue row for processing.
	 *
	 * @since    1.0.0
	 * @param    int    $queue_id    Queue ID.
	 * @return   bool
	 */
	public function claim( $queue_id ) {
		global $wpdb;

		$updated = $wpdb->update(
			self::get_table_name(),
			array(
				'status'     => 'processing',
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'id'     => absint( $queue_id ),
				'status' => 'pending',
			),
			array( '%s', '%s' ),
			array( '%d', '%s' )
		);

		return 1 === absint( $updated );
	}

	/**
	 * Mark a queue item completed.
	 *
	 * @since    1.0.0
	 * @param    int    $queue_id    Queue ID.
	 * @return   bool
	 */
	public function mark_completed( $queue_id ) {
		return $this->mark_status( $queue_id, 'completed' );
	}

	/**
	 * Mark a queue item cancelled.
	 *
	 * @since    1.0.0
	 * @param    int    $queue_id    Queue ID.
	 * @return   bool
	 */
	public function mark_cancelled( $queue_id ) {
		return $this->mark_status( $queue_id, 'cancelled' );
	}

	/**
	 * Defer a queue item.
	 *
	 * @since    1.0.0
	 * @param    int       $queue_id        Queue ID.
	 * @param    string    $source          Queue source.
	 * @param    string    $scheduled_at    Scheduled datetime.
	 * @return   bool
	 */
	public function defer( $queue_id, $source, $scheduled_at ) {
		global $wpdb;

		$updated = $wpdb->update(
			self::get_table_name(),
			array(
				'source'       => sanitize_key( $source ),
				'status'       => 'pending',
				'scheduled_at' => sanitize_text_field( $scheduled_at ),
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => absint( $queue_id ) ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Get table name.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'dialyra_call_queue';
	}

	/**
	 * Mark a queue status.
	 *
	 * @since    1.0.0
	 * @param    int       $queue_id    Queue ID.
	 * @param    string    $status      Status.
	 * @return   bool
	 */
	private function mark_status( $queue_id, $status ) {
		global $wpdb;

		$updated = $wpdb->update(
			self::get_table_name(),
			array(
				'status'     => sanitize_key( $status ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => absint( $queue_id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Recover stale processing rows.
	 *
	 * @since    1.0.0
	 */
	private function recover_stale_processing() {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::get_table_name() . ' SET status = %s, updated_at = %s WHERE status = %s AND updated_at < %s',
				'pending',
				current_time( 'mysql' ),
				'processing',
				date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - HOUR_IN_SECONDS )
			)
		);
	}

	/**
	 * Install table if missing or outdated.
	 *
	 * @since    1.0.0
	 */
	private function maybe_install_table() {
		$installed_version = get_option( self::get_table_version_option(), '' );

		if ( self::TABLE_VERSION !== $installed_version ) {
			self::install_table();
		}
	}

	/**
	 * Get table version option key.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private static function get_table_version_option() {
		return defined( 'WP_DIALYRA_OPTION_CALL_QUEUE_TABLE_VERSION' ) ? WP_DIALYRA_OPTION_CALL_QUEUE_TABLE_VERSION : 'dialyra_call_queue_table_version';
	}
}
