<?php

/**
 * Dialyra retry queue repository.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/retries
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Retry_Repository {

	const TABLE_VERSION = '1.0.1';

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->maybe_install_table();
	}

	/**
	 * Install the retry queue table.
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
			call_session_id bigint(20) unsigned NULL,
			source_call_session_id bigint(20) unsigned NULL,
			failure_source varchar(50) NOT NULL,
			failure_code varchar(100) NULL,
			failure_reason text NULL,
			status varchar(30) NOT NULL DEFAULT 'pending',
			attempt_count int unsigned NOT NULL DEFAULT 0,
			last_attempt_at datetime NULL,
			scheduled_at datetime NULL,
			registered_at datetime NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY business_id (business_id),
			KEY order_id (order_id),
			KEY call_session_id (call_session_id),
			KEY status (status),
			KEY status_scheduled (status, scheduled_at),
			KEY business_order_status (business_id, order_id, status)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( self::get_table_version_option(), self::TABLE_VERSION, false );
	}

	/**
	 * Find an active retry item for a business/order pair.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Business ID.
	 * @param    int    $order_id       WooCommerce order ID.
	 * @return   array|null
	 */
	public function find_active_for_order( $business_id, $order_id ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::get_table_name() . ' WHERE business_id = %d AND order_id = %d AND status IN (%s, %s, %s) ORDER BY id ASC LIMIT 1',
				absint( $business_id ),
				absint( $order_id ),
				'pending',
				'scheduled',
				'processing'
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Insert a pending retry item.
	 *
	 * @since    1.0.0
	 * @param    int      $business_id    Business ID.
	 * @param    int      $order_id       WooCommerce order ID.
	 * @param    array    $context        Retry context.
	 * @return   int
	 */
	public function insert_pending( $business_id, $order_id, $context ) {
		global $wpdb;

		$business_id = absint( $business_id );
		$order_id    = absint( $order_id );
		$context     = is_array( $context ) ? $context : array();
		$now         = current_time( 'mysql' );

		if ( ! $business_id || ! $order_id ) {
			return 0;
		}

		$inserted = $wpdb->insert(
			self::get_table_name(),
			array(
				'business_id'            => $business_id,
				'order_id'               => $order_id,
				'call_session_id'        => ! empty( $context['call_session_id'] ) ? absint( $context['call_session_id'] ) : null,
				'source_call_session_id' => ! empty( $context['call_session_id'] ) ? absint( $context['call_session_id'] ) : null,
				'failure_source'         => sanitize_key( $context['failure_source'] ?? '' ),
				'failure_code'           => $this->nullable_text( $context['failure_code'] ?? null ),
				'failure_reason'         => $this->nullable_textarea( $context['failure_reason'] ?? null ),
				'status'                 => 'pending',
				'attempt_count'          => 0,
				'last_attempt_at'        => null,
				'scheduled_at'           => null,
				'registered_at'          => $now,
				'created_at'             => $now,
				'updated_at'             => $now,
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return false === $inserted ? 0 : absint( $wpdb->insert_id );
	}

	/**
	 * Get retry items ready for policy/processing.
	 *
	 * @since    1.0.0
	 * @param    int    $delay_minutes    Retry delay minutes.
	 * @param    int    $limit            Row limit.
	 * @return   array
	 */
	public function get_due_items( $delay_minutes, $limit = 10 ) {
		global $wpdb;

		$this->recover_stale_processing();

		$delay_seconds = max( 0, absint( $delay_minutes ) ) * MINUTE_IN_SECONDS;
		$pending_due   = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $delay_seconds );
		$now           = current_time( 'mysql' );

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::get_table_name() . ' WHERE (status = %s AND registered_at <= %s) OR (status = %s AND scheduled_at <= %s) ORDER BY COALESCE(scheduled_at, registered_at) ASC, id ASC LIMIT %d',
				'pending',
				$pending_due,
				'scheduled',
				$now,
				max( 1, absint( $limit ) )
			),
			ARRAY_A
		);
	}

	/**
	 * Atomically claim a retry item for processing.
	 *
	 * @since    1.0.0
	 * @param    int    $retry_id    Retry queue ID.
	 * @return   bool
	 */
	public function claim( $retry_id ) {
		global $wpdb;

		$updated = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::get_table_name() . ' SET status = %s, updated_at = %s WHERE id = %d AND status IN (%s, %s)',
				'processing',
				current_time( 'mysql' ),
				absint( $retry_id ),
				'pending',
				'scheduled'
			)
		);

		return 1 === absint( $updated );
	}

	/**
	 * Increment retry attempts for a claimed row.
	 *
	 * @since    1.0.0
	 * @param    int    $retry_id    Retry queue ID.
	 * @return   int
	 */
	public function increment_attempt( $retry_id ) {
		global $wpdb;

		$now = current_time( 'mysql' );

		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::get_table_name() . ' SET attempt_count = attempt_count + 1, last_attempt_at = %s, updated_at = %s WHERE id = %d',
				$now,
				$now,
				absint( $retry_id )
			)
		);

		return absint(
			$wpdb->get_var(
				$wpdb->prepare(
					'SELECT attempt_count FROM ' . self::get_table_name() . ' WHERE id = %d',
					absint( $retry_id )
				)
			)
		);
	}

	/**
	 * Schedule the next retry attempt.
	 *
	 * @since    1.0.0
	 * @param    int       $retry_id        Retry queue ID.
	 * @param    string    $scheduled_at    Scheduled datetime.
	 * @return   bool
	 */
	public function schedule_next( $retry_id, $scheduled_at ) {
		global $wpdb;

		$updated = $wpdb->update(
			self::get_table_name(),
			array(
				'status'       => 'scheduled',
				'scheduled_at' => sanitize_text_field( $scheduled_at ),
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => absint( $retry_id ) ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Mark a retry item completed.
	 *
	 * @since    1.0.0
	 * @param    int    $retry_id    Retry queue ID.
	 * @return   bool
	 */
	public function mark_completed( $retry_id ) {
		return $this->mark_status( $retry_id, 'completed' );
	}

	/**
	 * Mark a retry item cancelled.
	 *
	 * @since    1.0.0
	 * @param    int    $retry_id    Retry queue ID.
	 * @return   bool
	 */
	public function mark_cancelled( $retry_id ) {
		return $this->mark_status( $retry_id, 'cancelled' );
	}

	/**
	 * Mark a retry item exhausted.
	 *
	 * @since    1.0.0
	 * @param    int    $retry_id    Retry queue ID.
	 * @return   bool
	 */
	public function mark_exhausted( $retry_id ) {
		return $this->mark_status( $retry_id, 'exhausted' );
	}

	/**
	 * Update latest failure context on an existing active retry item.
	 *
	 * @since    1.0.0
	 * @param    int      $retry_id    Retry queue ID.
	 * @param    array    $context     Retry context.
	 * @return   bool
	 */
	public function update_failure_context( $retry_id, $context ) {
		global $wpdb;

		$retry_id = absint( $retry_id );
		$context  = is_array( $context ) ? $context : array();

		if ( ! $retry_id ) {
			return false;
		}

		$data = array(
			'failure_source' => sanitize_key( $context['failure_source'] ?? '' ),
			'failure_code'   => $this->nullable_text( $context['failure_code'] ?? null ),
			'failure_reason' => $this->nullable_textarea( $context['failure_reason'] ?? null ),
			'updated_at'     => current_time( 'mysql' ),
		);
		$formats = array( '%s', '%s', '%s', '%s' );

		if ( ! empty( $context['call_session_id'] ) ) {
			$data['call_session_id'] = absint( $context['call_session_id'] );
			$formats[] = '%d';
		}

		$updated = $wpdb->update(
			self::get_table_name(),
			$data,
			array( 'id' => $retry_id ),
			$formats,
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

		return $wpdb->prefix . 'dialyra_retry_queue';
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
	 * Recover stale processing rows.
	 *
	 * @since    1.0.0
	 */
	private function recover_stale_processing() {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::get_table_name() . ' SET status = %s, scheduled_at = %s, updated_at = %s WHERE status = %s AND updated_at < %s',
				'scheduled',
				current_time( 'mysql' ),
				current_time( 'mysql' ),
				'processing',
				date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - HOUR_IN_SECONDS )
			)
		);
	}

	/**
	 * Mark retry item status.
	 *
	 * @since    1.0.0
	 * @param    int       $retry_id    Retry queue ID.
	 * @param    string    $status      Status.
	 * @return   bool
	 */
	private function mark_status( $retry_id, $status ) {
		global $wpdb;

		$updated = $wpdb->update(
			self::get_table_name(),
			array(
				'status'     => sanitize_key( $status ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => absint( $retry_id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Get table version option key.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private static function get_table_version_option() {
		return defined( 'WP_DIALYRA_OPTION_RETRY_QUEUE_TABLE_VERSION' ) ? WP_DIALYRA_OPTION_RETRY_QUEUE_TABLE_VERSION : 'dialyra_retry_queue_table_version';
	}

	/**
	 * Sanitize nullable short text.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
	 * @return   string|null
	 */
	private function nullable_text( $value ) {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize nullable textarea text.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
	 * @return   string|null
	 */
	private function nullable_textarea( $value ) {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return sanitize_textarea_field( $value );
	}
}
