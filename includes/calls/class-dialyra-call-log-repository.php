<?php

/**
 * Dialyra local call log repository.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/calls
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Call_Log_Repository {

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
	 * Install the local call logs table.
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
			business_id bigint(20) unsigned NULL,
			order_id bigint(20) unsigned NULL,
			remote_call_log_id bigint(20) unsigned NULL,
			call_session_id bigint(20) unsigned NULL,
			uuid varchar(80) NULL,
			action_id varchar(80) NULL,
			asterisk_uniqueid varchar(80) NULL,
			linkedid varchar(80) NULL,
			sip_trunk_id bigint(20) unsigned NULL,
			flow_id bigint(20) unsigned NULL,
			actor_user_id bigint(20) unsigned NULL,
			direction varchar(24) NULL,
			from_number varchar(64) NULL,
			to_number varchar(64) NULL,
			dialed_number varchar(64) NULL,
			status varchar(40) NOT NULL DEFAULT 'pending',
			call_status varchar(40) NULL,
			started_at datetime NULL,
			answered_at datetime NULL,
			ended_at datetime NULL,
			duration_sec int unsigned NOT NULL DEFAULT 0,
			billsec int unsigned NOT NULL DEFAULT 0,
			hangup_cause varchar(80) NULL,
			hangup_cause_text varchar(160) NULL,
			billing_status varchar(60) NULL,
			billing_charged_amount decimal(18,6) NULL,
			retry_attempts int unsigned NOT NULL DEFAULT 0,
			dtmf text NULL,
			timeline longtext NULL,
			metadata longtext NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY business_id (business_id),
			KEY order_id (order_id),
			KEY remote_call_log_id (remote_call_log_id),
			KEY call_session_id (call_session_id),
			KEY status (status),
			KEY started_at (started_at),
			KEY uuid (uuid),
			KEY action_id (action_id)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( self::get_table_version_option(), self::TABLE_VERSION, false );
	}

	/**
	 * Log a call originate result.
	 *
	 * @since    1.0.0
	 * @param    int                       $order_id    WooCommerce order ID.
	 * @param    Dialyra_API_Response|null $response    Originate response.
	 * @param    array                     $context     Optional context.
	 * @return   int
	 */
	public function log_originate_result( $order_id, $response = null, $context = array() ) {
		$context = is_array( $context ) ? $context : array();
		$data    = $response instanceof Dialyra_API_Response ? $this->extract_response_data( $response ) : array();
		$success = $response instanceof Dialyra_API_Response && $response->is_successful();

		$row = array(
			'business_id'     => $this->nullable_absint( $data['business_id'] ?? ( $context['business_id'] ?? ( class_exists( 'Dialyra_Auth_Manager' ) ? Dialyra_Auth_Manager::get_business_id() : null ) ) ),
			'order_id'        => $this->nullable_absint( $order_id ),
			'remote_call_log_id' => $this->nullable_absint( $data['call_log_id'] ?? null ),
			'call_session_id' => $this->nullable_absint( $data['call_session_id'] ?? ( $data['id'] ?? null ) ),
			'uuid'            => $this->nullable_text( $data['call_log_uuid'] ?? ( $data['uuid'] ?? null ) ),
			'action_id'       => $this->nullable_text( $data['action_id'] ?? null ),
			'sip_trunk_id'    => $this->nullable_absint( $data['sip_trunk_id'] ?? null ),
			'flow_id'         => $this->nullable_absint( $context['flow_id'] ?? null ),
			'direction'       => $this->nullable_text( $data['direction'] ?? 'outbound' ),
			'to_number'       => $this->nullable_text( $data['to_number'] ?? ( $data['dialed_number'] ?? ( $context['phone'] ?? null ) ) ),
			'dialed_number'   => $this->nullable_text( $data['dialed_number'] ?? ( $data['to'] ?? ( $context['phone'] ?? null ) ) ),
			'status'          => $success ? sanitize_key( $data['status'] ?? 'initiated' ) : 'originate_failed',
			'call_status'     => $this->nullable_text( $data['call_status'] ?? null ),
			'started_at'      => current_time( 'mysql' ),
			'metadata'        => $this->json_encode(
				array(
					'origin'                 => sanitize_key( $context['source'] ?? 'originate' ),
					'status_code'            => $response instanceof Dialyra_API_Response ? absint( $response->get_status_code() ) : 0,
					'error_type'             => $response instanceof Dialyra_API_Response ? sanitize_key( $response->get_error_type() ) : '',
					'message'                => $response instanceof Dialyra_API_Response ? sanitize_text_field( $response->get_message() ) : '',
					'retry_id'               => ! empty( $context['retry_id'] ) ? absint( $context['retry_id'] ) : null,
					'retry_attempt'          => ! empty( $context['retry_attempt'] ) ? absint( $context['retry_attempt'] ) : null,
					'source_call_session_id' => ! empty( $context['source_call_session_id'] ) ? absint( $context['source_call_session_id'] ) : null,
					'queue_call_session_id'  => ! empty( $context['queue_call_session_id'] ) ? absint( $context['queue_call_session_id'] ) : null,
					'response'               => $data,
				)
			),
		);

		return $this->upsert( $row );
	}

	/**
	 * Log why an automatic call candidate did not reach the originate API.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id       WooCommerce order ID.
	 * @param    string    $reason         Block reason.
	 * @param    array     $context        Optional context.
	 * @return   int
	 */
	public function log_trigger_blocked( $order_id, $reason, $context = array() ) {
		$context     = is_array( $context ) ? $context : array();
		$business_id = $this->nullable_absint( $context['business_id'] ?? ( class_exists( 'Dialyra_Auth_Manager' ) ? Dialyra_Auth_Manager::get_business_id() : null ) );

		$row = array(
			'business_id' => $business_id,
			'order_id'    => $this->nullable_absint( $order_id ),
			'status'      => 'trigger_blocked',
			'started_at'  => current_time( 'mysql' ),
			'metadata'    => $this->json_encode(
				array(
					'origin' => sanitize_key( $context['source'] ?? 'automatic_trigger' ),
					'reason' => sanitize_key( $reason ),
					'gate'   => sanitize_key( $context['gate'] ?? 'pre_originate' ),
					'data'   => is_array( $context['data'] ?? null ) ? $context['data'] : array(),
				)
			),
		);

		return $this->upsert( $row );
	}

	/**
	 * Handle normalized webhook event.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Normalized event.
	 * @return   int
	 */
	public function handle_call_event( $event ) {
		return $this->log_webhook_event( $event );
	}

	/**
	 * Log a normalized webhook event.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Normalized event.
	 * @return   int
	 */
	public function log_webhook_event( $event ) {
		$event = is_array( $event ) ? $event : array();

		$row = array(
			'business_id'            => $this->nullable_absint( $event['business_id'] ?? null ),
			'order_id'               => $this->nullable_absint( $event['order_id'] ?? null ),
			'remote_call_log_id'     => $this->nullable_absint( $event['call_log_id'] ?? null ),
			'call_session_id'        => $this->nullable_absint( $event['call_session_id'] ?? null ),
			'action_id'              => $this->nullable_text( $event['action_id'] ?? null ),
			'sip_trunk_id'           => $this->nullable_absint( $event['sip_trunk_id'] ?? null ),
			'flow_id'                => $this->nullable_absint( $event['flow_id'] ?? null ),
			'from_number'            => $this->nullable_text( $event['from_number'] ?? null ),
			'to_number'              => $this->nullable_text( $event['dialed_number'] ?? null ),
			'dialed_number'          => $this->nullable_text( $event['dialed_number'] ?? null ),
			'status'                 => $this->status_from_event( $event ),
			'call_status'            => $this->nullable_text( $event['call_status'] ?? null ),
			'started_at'             => $this->nullable_datetime( $event['started_at'] ?? null ),
			'answered_at'            => $this->nullable_datetime( $event['answered_at'] ?? null ),
			'ended_at'               => $this->nullable_datetime( $event['ended_at'] ?? ( $event['occurred_at'] ?? null ) ),
			'duration_sec'           => absint( $event['duration_seconds'] ?? 0 ),
			'billsec'                => absint( $event['bill_seconds'] ?? 0 ),
			'hangup_cause'           => $this->nullable_text( $event['hangup_cause'] ?? null ),
			'hangup_cause_text'      => $this->nullable_text( $event['hangup_cause'] ?? null ),
			'billing_status'         => $this->nullable_text( $event['billing_status'] ?? null ),
			'billing_charged_amount' => isset( $event['billing_amount'] ) && '' !== $event['billing_amount'] ? (float) $event['billing_amount'] : null,
			'dtmf'                   => ! empty( $event['dtmf_sequence'] ) && is_array( $event['dtmf_sequence'] ) ? implode( ', ', array_map( 'sanitize_text_field', $event['dtmf_sequence'] ) ) : $this->nullable_text( $event['dtmf_value'] ?? null ),
			'timeline'               => $this->json_encode(
				array(
					'dtmf_events' => is_array( $event['dtmf_history'] ?? null ) ? $event['dtmf_history'] : array(),
				)
			),
			'metadata'               => $this->json_encode(
				array(
					'event_id'     => sanitize_text_field( $event['event_id'] ?? '' ),
					'event_type'   => sanitize_text_field( $event['event_type'] ?? '' ),
					'order_action' => sanitize_key( $event['order_action'] ?? 'none' ),
					'raw_payload'  => is_array( $event['raw_payload'] ?? null ) ? $event['raw_payload'] : array(),
				)
			),
		);

		return $this->upsert( $row );
	}

	/**
	 * Find a local call log row for a normalized webhook event.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Normalized event.
	 * @return   int
	 */
	public function find_log_id_for_event( $event ) {
		global $wpdb;

		$event      = is_array( $event ) ? $event : array();
		$table_name = self::get_table_name();

		$selectors = array(
			'action_id'          => $this->nullable_text( $event['action_id'] ?? null ),
			'call_session_id'    => $this->nullable_absint( $event['call_session_id'] ?? null ),
			'remote_call_log_id' => $this->nullable_absint( $event['call_log_id'] ?? null ),
		);

		foreach ( $selectors as $column => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			$format = in_array( $column, array( 'call_session_id', 'remote_call_log_id' ), true ) ? '%d' : '%s';
			$id     = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE {$column} = {$format} ORDER BY id ASC LIMIT 1", $value ) ) );

			if ( $id ) {
				return $id;
			}
		}

		return 0;
	}

	/**
	 * Increment local retry attempts for a call history row.
	 *
	 * @since    1.0.0
	 * @param    int    $local_log_id    Local call log row ID.
	 * @return   int
	 */
	public function increment_retry_attempts( $local_log_id ) {
		global $wpdb;

		$local_log_id = absint( $local_log_id );

		if ( ! $local_log_id ) {
			return 0;
		}

		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::get_table_name() . ' SET retry_attempts = retry_attempts + 1, updated_at = %s WHERE id = %d',
				current_time( 'mysql' ),
				$local_log_id
			)
		);

		return absint(
			$wpdb->get_var(
				$wpdb->prepare(
					'SELECT retry_attempts FROM ' . self::get_table_name() . ' WHERE id = %d',
					$local_log_id
				)
			)
		);
	}

	/**
	 * Sync a local call log row from a Dialyra call-history API response.
	 *
	 * @since    1.0.0
	 * @param    int      $local_log_id    Local call log row ID.
	 * @param    array    $history         Dialyra call history response.
	 * @return   bool
	 */
	public function sync_from_history_response( $local_log_id, $history ) {
		global $wpdb;

		$local_log_id = absint( $local_log_id );
		$history      = is_array( $history ) ? $history : array();

		if ( ! $local_log_id || empty( $history ) ) {
			return false;
		}

		$template_values = is_array( $history['template_values'] ?? null ) ? $history['template_values'] : array();
		$dtmf_sequence   = is_array( $template_values['dtmf_sequence'] ?? null ) ? $template_values['dtmf_sequence'] : array();
		$dtmf_history    = is_array( $template_values['dtmf_history'] ?? null ) ? $template_values['dtmf_history'] : array();
		$order_action    = sanitize_key( $template_values['order_action'] ?? 'none' );
		$order_id        = $this->nullable_absint( $template_values['order_id'] ?? ( $history['order_id'] ?? null ) );

		$row = array(
			'id'                      => $local_log_id,
			'business_id'             => $this->nullable_absint( $history['business_id'] ?? null ),
			'order_id'                => $order_id,
			'remote_call_log_id'      => $this->nullable_absint( $history['id'] ?? null ),
			'call_session_id'         => $this->nullable_absint( $history['id'] ?? ( $template_values['call_session_id'] ?? null ) ),
			'uuid'                    => $this->nullable_text( $history['uuid'] ?? ( $template_values['call_log_uuid'] ?? null ) ),
			'action_id'               => $this->nullable_text( $history['action_id'] ?? ( $template_values['call_action_id'] ?? null ) ),
			'asterisk_uniqueid'       => $this->nullable_text( $history['asterisk_uniqueid'] ?? null ),
			'linkedid'                => $this->nullable_text( $history['linkedid'] ?? null ),
			'sip_trunk_id'            => $this->nullable_absint( $history['sip_trunk_id'] ?? null ),
			'flow_id'                 => $this->nullable_absint( $template_values['flow_id'] ?? null ),
			'actor_user_id'           => $this->nullable_absint( $history['actor_user_id'] ?? null ),
			'direction'               => $this->nullable_text( $history['direction'] ?? null ),
			'from_number'             => $this->nullable_text( $history['from_number'] ?? null ),
			'to_number'               => $this->nullable_text( $history['to_number'] ?? ( $history['dialed_number'] ?? null ) ),
			'dialed_number'           => $this->nullable_text( $history['dialed_number'] ?? null ),
			'status'                  => sanitize_key( $history['status'] ?? 'completed' ),
			'call_status'             => $this->nullable_text( $history['call_status'] ?? null ),
			'started_at'              => $this->nullable_datetime( $history['started_at'] ?? null ),
			'answered_at'             => $this->nullable_datetime( $history['answered_at'] ?? null ),
			'ended_at'                => $this->nullable_datetime( $history['ended_at'] ?? null ),
			'duration_sec'            => absint( $history['duration_sec'] ?? 0 ),
			'billsec'                 => absint( $history['billsec'] ?? 0 ),
			'hangup_cause'            => $this->nullable_text( $history['hangup_cause'] ?? null ),
			'hangup_cause_text'       => $this->nullable_text( $history['hangup_cause_text'] ?? null ),
			'billing_status'          => $this->nullable_text( $history['billing_status'] ?? null ),
			'billing_charged_amount'  => isset( $history['billing_charged_amount'] ) && '' !== $history['billing_charged_amount'] ? (float) $history['billing_charged_amount'] : null,
			'dtmf'                    => ! empty( $dtmf_sequence ) ? implode( ', ', array_map( 'sanitize_text_field', $dtmf_sequence ) ) : null,
			'timeline'                => $this->json_encode(
				array(
					'dtmf_events' => $dtmf_history,
					'actions'     => is_array( $history['timeline']['actions'] ?? null ) ? $history['timeline']['actions'] : array(),
				)
			),
			'metadata'                => $this->json_encode(
				array(
					'origin'          => 'history_sync',
					'order_action'    => $order_action,
					'resolved_by'     => sanitize_key( $history['resolved_by'] ?? '' ),
					'resolved_input'  => sanitize_text_field( $history['resolved_input'] ?? '' ),
					'template_values' => $template_values,
					'synced_at'       => current_time( 'mysql' ),
				)
			),
			'updated_at'              => current_time( 'mysql' ),
		);

		$row = $this->strip_nulls( $row );
		unset( $row['id'] );

		$updated = $wpdb->update(
			self::get_table_name(),
			$row,
			array( 'id' => $local_log_id ),
			$this->formats_for_row( $row ),
			array( '%d' )
		);

		if ( false !== $updated && $order_id && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );

			if ( $order && is_object( $order ) && method_exists( $order, 'update_meta_data' ) ) {
				$order->update_meta_data( '_dialyra_last_call_log_id', absint( $history['id'] ?? $local_log_id ) );
				$order->update_meta_data( '_dialyra_last_call_session_id', absint( $history['id'] ?? 0 ) );
				$order->update_meta_data( '_dialyra_last_call_status', sanitize_key( $history['status'] ?? '' ) );
				$order->update_meta_data( '_dialyra_last_order_action', $order_action );
				$order->save();
			}
		}

		if ( false !== $updated && $order_id && $order_action && 'none' !== $order_action ) {
			$this->dispatch_synced_order_action( $order_id, $order_action, $history, $template_values );
		}

		return false !== $updated;
	}

	/**
	 * Dispatch the standard order-action hook after manual history sync.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id          WooCommerce order ID.
	 * @param    string    $order_action      Dialyra order action.
	 * @param    array     $history           Dialyra call history response.
	 * @param    array     $template_values   Template values from history.
	 */
	private function dispatch_synced_order_action( $order_id, $order_action, $history, $template_values ) {
		$dtmf_sequence = is_array( $template_values['dtmf_sequence'] ?? null ) ? $template_values['dtmf_sequence'] : array();

		$event = array(
			'event_type'             => 'call.completed',
			'event_source'           => 'history_sync',
			'order_id'               => absint( $order_id ),
			'order_action'           => sanitize_key( $order_action ),
			'business_id'            => $this->nullable_absint( $history['business_id'] ?? null ),
			'call_log_id'            => $this->nullable_absint( $history['id'] ?? null ),
			'call_session_id'        => $this->nullable_absint( $history['id'] ?? ( $template_values['call_session_id'] ?? null ) ),
			'action_id'              => $this->nullable_text( $history['action_id'] ?? ( $template_values['call_action_id'] ?? null ) ),
			'call_status'            => sanitize_key( $history['call_status'] ?? '' ),
			'status'                 => sanitize_key( $history['status'] ?? '' ),
			'billing_status'         => sanitize_key( $history['billing_status'] ?? '' ),
			'billing_amount'         => isset( $history['billing_charged_amount'] ) ? (float) $history['billing_charged_amount'] : null,
			'duration_seconds'       => absint( $history['duration_sec'] ?? 0 ),
			'bill_seconds'           => absint( $history['billsec'] ?? 0 ),
			'dtmf_sequence'          => array_map( 'sanitize_text_field', $dtmf_sequence ),
			'dtmf_history'           => is_array( $template_values['dtmf_history'] ?? null ) ? $template_values['dtmf_history'] : array(),
			'started_at'             => $this->nullable_datetime( $history['started_at'] ?? null ),
			'answered_at'            => $this->nullable_datetime( $history['answered_at'] ?? null ),
			'ended_at'               => $this->nullable_datetime( $history['ended_at'] ?? null ),
			'raw_payload'            => $history,
		);

		do_action(
			class_exists( 'Dialyra_Hook_Names' ) ? Dialyra_Hook_Names::get_or_default( 'order', 'order_action_received', 'dialyra_order_action_received' ) : 'dialyra_order_action_received',
			absint( $order_id ),
			sanitize_key( $order_action ),
			$event
		);
	}

	/**
	 * Get table name.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'dialyra_call_logs';
	}

	/**
	 * Insert or update a call log row.
	 *
	 * @since    1.0.0
	 * @param    array    $row    Row data.
	 * @return   int
	 */
	private function upsert( $row ) {
		global $wpdb;

		$row = is_array( $row ) ? $row : array();
		$now = current_time( 'mysql' );
		$id  = $this->find_existing_id( $row );

		$row['updated_at'] = $now;

		if ( $id ) {
			$data = $this->strip_nulls( $row );

			unset( $data['id'] );

			$wpdb->update(
				self::get_table_name(),
				$data,
				array( 'id' => $id ),
				$this->formats_for_row( $data ),
				array( '%d' )
			);

			return $id;
		}

		$row['created_at'] = $now;
		$row               = $this->strip_nulls( $row );

		$inserted = $wpdb->insert( self::get_table_name(), $row, $this->formats_for_row( $row ) );

		return false === $inserted ? 0 : absint( $wpdb->insert_id );
	}

	/**
	 * Find existing log ID by remote identifiers.
	 *
	 * @since    1.0.0
	 * @param    array    $row    Row data.
	 * @return   int
	 */
	private function find_existing_id( $row ) {
		global $wpdb;

		$table_name = self::get_table_name();

		foreach ( array( 'remote_call_log_id', 'call_session_id', 'uuid', 'action_id' ) as $key ) {
			if ( empty( $row[ $key ] ) ) {
				continue;
			}

			$format = in_array( $key, array( 'remote_call_log_id', 'call_session_id' ), true ) ? '%d' : '%s';
			$id     = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE {$key} = {$format} ORDER BY id ASC LIMIT 1", $row[ $key ] ) ) );

			if ( $id ) {
				return $id;
			}
		}

		return 0;
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
		return defined( 'WP_DIALYRA_OPTION_CALL_LOGS_TABLE_VERSION' ) ? WP_DIALYRA_OPTION_CALL_LOGS_TABLE_VERSION : 'dialyra_call_logs_table_version';
	}

	/**
	 * Extract normalized response data.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   array
	 */
	private function extract_response_data( Dialyra_API_Response $response ) {
		$data = $response->get_data();
		$data = is_array( $data ) ? $data : array();

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		return $data;
	}

	/**
	 * Determine call status from event fields.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Event.
	 * @return   string
	 */
	private function status_from_event( $event ) {
		$event_type  = sanitize_text_field( $event['event_type'] ?? '' );
		$call_status = sanitize_key( $event['call_status'] ?? '' );

		if ( 'call.completed' === $event_type ) {
			return 'completed';
		}

		if ( in_array( $call_status, array( 'busy', 'no_answer', 'failed', 'answered', 'completed' ), true ) ) {
			return $call_status;
		}

		if ( false !== strpos( $event_type, 'busy' ) ) {
			return 'busy';
		}

		if ( false !== strpos( $event_type, 'no_answer' ) || false !== strpos( $event_type, 'no-answer' ) ) {
			return 'no_answer';
		}

		if ( false !== strpos( $event_type, 'failed' ) ) {
			return 'failed';
		}

		return $call_status ? $call_status : 'completed';
	}

	/**
	 * Remove null values before insert/update.
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
			if ( in_array( $key, array( 'id', 'business_id', 'order_id', 'remote_call_log_id', 'call_session_id', 'sip_trunk_id', 'flow_id', 'actor_user_id', 'duration_sec', 'billsec', 'retry_attempts' ), true ) ) {
				$formats[] = '%d';
			} elseif ( 'billing_charged_amount' === $key ) {
				$formats[] = '%f';
			} else {
				$formats[] = '%s';
			}
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

	/**
	 * Sanitize nullable datetime.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
	 * @return   string|null
	 */
	private function nullable_datetime( $value ) {
		if ( null === $value || '' === $value ) {
			return null;
		}

		$timestamp = strtotime( $value );

		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : sanitize_text_field( $value );
	}

	/**
	 * JSON encode array safely for storage.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Data.
	 * @return   string
	 */
	private function json_encode( $data ) {
		return wp_json_encode( is_array( $data ) ? $data : array() );
	}
}
