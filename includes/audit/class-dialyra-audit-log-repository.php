<?php

/**
 * Dialyra local audit log repository.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/audit
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Audit_Log_Repository {

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
	 * Install the audit log table.
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
			level varchar(20) NOT NULL DEFAULT 'info',
			source varchar(80) NOT NULL DEFAULT 'system',
			event varchar(120) NOT NULL,
			message text NULL,
			business_id bigint(20) unsigned NULL,
			order_id bigint(20) unsigned NULL,
			user_id bigint(20) unsigned NULL,
			request_id varchar(120) NULL,
			context longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY level (level),
			KEY source (source),
			KEY event (event),
			KEY business_id (business_id),
			KEY order_id (order_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( self::get_table_version_option(), self::TABLE_VERSION, false );
	}

	/**
	 * Write an audit row.
	 *
	 * @since    1.0.0
	 * @param    string    $event      Event key.
	 * @param    string    $message    Human readable message.
	 * @param    array     $context    Structured context.
	 * @param    string    $level      Log level.
	 * @param    string    $source     Source area.
	 * @return   int
	 */
	public function log( $event, $message = '', $context = array(), $level = 'info', $source = 'system' ) {
		global $wpdb;

		if ( ! self::is_enabled() ) {
			return 0;
		}

		$context = is_array( $context ) ? $context : array();
		$row     = array(
			'level'       => $this->sanitize_level( $level ),
			'source'      => sanitize_key( $source ),
			'event'       => sanitize_key( $event ),
			'message'     => sanitize_text_field( $message ),
			'business_id' => $this->nullable_absint( $context['business_id'] ?? ( class_exists( 'Dialyra_Auth_Manager' ) ? Dialyra_Auth_Manager::get_business_id() : null ) ),
			'order_id'    => $this->nullable_absint( $context['order_id'] ?? null ),
			'user_id'     => get_current_user_id() ? absint( get_current_user_id() ) : null,
			'request_id'  => $this->nullable_text( $context['request_id'] ?? null ),
			'context'     => wp_json_encode( $this->sanitize_context( $context ) ),
			'created_at'  => current_time( 'mysql' ),
		);

		$row      = $this->strip_nulls( $row );
		$inserted = $wpdb->insert( self::get_table_name(), $row, $this->formats_for_row( $row ) );

		return false === $inserted ? 0 : absint( $wpdb->insert_id );
	}

	/**
	 * Query audit rows.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query args.
	 * @return   array
	 */
	public function query( $args = array() ) {
		global $wpdb;

		if ( ! self::is_enabled() ) {
			return array();
		}

		$args       = is_array( $args ) ? $args : array();
		$table_name = self::get_table_name();
		$where      = array( '1=1' );
		$params     = array();

		if ( ! empty( $args['level'] ) ) {
			$where[]  = 'level = %s';
			$params[] = $this->sanitize_level( $args['level'] );
		}

		if ( ! empty( $args['source'] ) ) {
			$where[]  = 'source = %s';
			$params[] = sanitize_key( $args['source'] );
		}

		if ( ! empty( $args['event'] ) ) {
			$where[]  = 'event LIKE %s';
			$params[] = '%' . $wpdb->esc_like( sanitize_key( $args['event'] ) ) . '%';
		}

		if ( ! empty( $args['order_id'] ) ) {
			$where[]  = 'order_id = %d';
			$params[] = absint( $args['order_id'] );
		}

		$limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 100;
		$limit = min( 500, max( 1, $limit ) );

		$sql = "SELECT * FROM {$table_name} WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d';
		$params[] = $limit;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
	}

	/**
	 * Clear all audit rows.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function clear() {
		global $wpdb;

		if ( ! self::is_enabled() ) {
			return false;
		}

		if ( ! self::table_exists() ) {
			return false;
		}

		return false !== $wpdb->query( 'TRUNCATE TABLE ' . self::get_table_name() );
	}

	/**
	 * Check if the table exists.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public static function table_exists() {
		global $wpdb;

		$table_name = self::get_table_name();

		return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
	}

	/**
	 * Get table name.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'dialyra_audit_logs';
	}

	/**
	 * Check if Dialyra debug audit logging is enabled.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public static function is_enabled() {
		return defined( 'WP_DIALYRA_DEBUG_MODE' ) && WP_DIALYRA_DEBUG_MODE;
	}

	/**
	 * Install table if missing or outdated.
	 *
	 * @since    1.0.0
	 */
	private function maybe_install_table() {
		if ( ! self::is_enabled() ) {
			return;
		}

		$installed_version = get_option( self::get_table_version_option(), '' );

		if ( self::TABLE_VERSION !== $installed_version || ! self::table_exists() ) {
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
		return defined( 'WP_DIALYRA_OPTION_AUDIT_LOGS_TABLE_VERSION' ) ? WP_DIALYRA_OPTION_AUDIT_LOGS_TABLE_VERSION : 'dialyra_audit_logs_table_version';
	}

	/**
	 * Sanitize level.
	 *
	 * @since    1.0.0
	 * @param    string    $level    Raw level.
	 * @return   string
	 */
	private function sanitize_level( $level ) {
		$level = sanitize_key( $level );

		return in_array( $level, array( 'debug', 'info', 'warning', 'error', 'success' ), true ) ? $level : 'info';
	}

	/**
	 * Sanitize nested context for storage.
	 *
	 * @since    1.0.0
	 * @param    array    $context    Raw context.
	 * @return   array
	 */
	private function sanitize_context( $context ) {
		$clean = array();

		foreach ( $context as $key => $value ) {
			$key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				$clean[ $key ] = $this->sanitize_context( $value );
			} elseif ( is_bool( $value ) ) {
				$clean[ $key ] = $value;
			} elseif ( is_numeric( $value ) ) {
				$clean[ $key ] = $value;
			} else {
				$clean[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $clean;
	}

	/**
	 * Remove null values before insert.
	 *
	 * @since    1.0.0
	 * @param    array    $row    Row data.
	 * @return   array
	 */
	private function strip_nulls( $row ) {
		return array_filter(
			$row,
			static function ( $value ) {
				return null !== $value;
			}
		);
	}

	/**
	 * Build wpdb formats for a row.
	 *
	 * @since    1.0.0
	 * @param    array    $row    Row data.
	 * @return   array
	 */
	private function formats_for_row( $row ) {
		$formats = array();

		foreach ( array_keys( $row ) as $key ) {
			$formats[] = in_array( $key, array( 'id', 'business_id', 'order_id', 'user_id' ), true ) ? '%d' : '%s';
		}

		return $formats;
	}

	/**
	 * Sanitize nullable integer.
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
	 * Sanitize nullable text.
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
}
