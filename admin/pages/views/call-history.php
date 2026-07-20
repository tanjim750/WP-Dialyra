<?php

/**
 * Call history page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;

$wp_dialyra_business_id  = class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
$wp_dialyra_plugin       = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
$wp_dialyra_notice_error = '';
$wp_dialyra_notice_success = '';
$wp_dialyra_calls        = array();
$wp_dialyra_call_groups  = array();
$wp_dialyra_log_table    = $wpdb->prefix . 'dialyra_call_logs';

$wp_dialyra_filters = array(
	'order_id'     => isset( $_GET['order_id'] ) ? sanitize_text_field( wp_unslash( $_GET['order_id'] ) ) : '',
	'status'       => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
	'started_date' => isset( $_GET['started_date'] ) ? sanitize_text_field( wp_unslash( $_GET['started_date'] ) ) : '',
	'phone'        => isset( $_GET['phone'] ) ? sanitize_text_field( wp_unslash( $_GET['phone'] ) ) : '',
);

$wp_dialyra_table_exists = static function ( $table_name ) use ( $wpdb ) {
	return $table_name && $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
};

$wp_dialyra_get_table_columns = static function ( $table_name ) use ( $wpdb ) {
	$columns = $wpdb->get_col( "DESC {$table_name}", 0 );

	return is_array( $columns ) ? array_map( 'sanitize_key', $columns ) : array();
};

$wp_dialyra_has_column = static function ( $columns, $column ) {
	return in_array( sanitize_key( $column ), $columns, true );
};

$wp_dialyra_first_column = static function ( $columns, $candidates ) use ( $wp_dialyra_has_column ) {
	foreach ( $candidates as $candidate ) {
		if ( $wp_dialyra_has_column( $columns, $candidate ) ) {
			return sanitize_key( $candidate );
		}
	}

	return '';
};

$wp_dialyra_format_datetime = static function ( $datetime ) {
	if ( empty( $datetime ) ) {
		return '—';
	}

	$timestamp = strtotime( $datetime );

	if ( ! $timestamp ) {
		return sanitize_text_field( $datetime );
	}

	return wp_date( 'Y-m-d H:i', $timestamp );
};

$wp_dialyra_format_duration = static function ( $seconds ) {
	$seconds = absint( $seconds );

	if ( ! $seconds ) {
		return '00:00';
	}

	return sprintf( '%02d:%02d', floor( $seconds / 60 ), $seconds % 60 );
};

$wp_dialyra_format_money = static function ( $amount ) {
	if ( null === $amount || '' === $amount ) {
		return '—';
	}

	return '৳ ' . number_format_i18n( (float) $amount, 2 );
};

$wp_dialyra_row_value = static function ( $row, $keys, $default = '' ) {
	foreach ( $keys as $key ) {
		if ( is_array( $row ) && array_key_exists( $key, $row ) && null !== $row[ $key ] && '' !== $row[ $key ] ) {
			return $row[ $key ];
		}
	}

	return $default;
};

$wp_dialyra_parse_json = static function ( $value ) {
	if ( is_array( $value ) ) {
		return $value;
	}

	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return array();
	}

	$decoded = json_decode( $value, true );

	return is_array( $decoded ) ? $decoded : array();
};

$wp_dialyra_get_nested_value = static function ( $data, $keys, $default = '' ) {
	foreach ( $keys as $key_path ) {
		$value = $data;

		foreach ( explode( '.', $key_path ) as $key ) {
			if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
				$value = null;
				break;
			}

			$value = $value[ $key ];
		}

		if ( null !== $value && '' !== $value ) {
			return $value;
		}
	}

	return $default;
};

$wp_dialyra_extract_response_data = static function ( $response ) {
	if ( ! $response || ! is_object( $response ) || ! method_exists( $response, 'get_data' ) ) {
		return array();
	}

	$data = $response->get_data();
	$data = is_array( $data ) ? $data : array();

	if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
		$data = $data['data'];
	}

	return $data;
};

$wp_dialyra_get_local_log = static function ( $log_id ) use ( $wpdb, $wp_dialyra_log_table ) {
	$log_id = absint( $log_id );

	if ( ! $log_id ) {
		return array();
	}

	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wp_dialyra_log_table} WHERE id = %d LIMIT 1", $log_id ), ARRAY_A );

	return is_array( $row ) ? $row : array();
};

$wp_dialyra_find_order_for_log = static function ( $log ) {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return null;
	}

	$order_id = absint( $log['order_id'] ?? 0 );

	if ( $order_id ) {
		return wc_get_order( $order_id );
	}

	$meta_queries = array();

	if ( ! empty( $log['id'] ) ) {
		$meta_queries[] = array(
			'key'   => '_dialyra_last_call_log_id',
			'value' => absint( $log['id'] ),
		);
	}

	if ( ! empty( $log['call_session_id'] ) ) {
		$meta_queries[] = array(
			'key'   => '_dialyra_last_call_session_id',
			'value' => absint( $log['call_session_id'] ),
		);
	}

	if ( empty( $meta_queries ) ) {
		return null;
	}

	$args = array(
		'limit'      => 1,
		'return'     => 'objects',
		'meta_query' => count( $meta_queries ) > 1 ? array_merge( array( 'relation' => 'OR' ), $meta_queries ) : $meta_queries,
	);
	$orders = wc_get_orders( $args );

	return ! empty( $orders[0] ) ? $orders[0] : null;
};

$wp_dialyra_get_retry_counts = static function ( $business_id, $order_ids ) use ( $wpdb, $wp_dialyra_table_exists ) {
	$order_ids = array_values( array_filter( array_map( 'absint', $order_ids ) ) );

	if ( empty( $order_ids ) || ! class_exists( 'Dialyra_Retry_Repository' ) ) {
		return array(
			'by_order'   => array(),
			'by_session' => array(),
		);
	}

	$table_name = Dialyra_Retry_Repository::get_table_name();

	if ( ! $wp_dialyra_table_exists( $table_name ) ) {
		return array(
			'by_order'   => array(),
			'by_session' => array(),
		);
	}

	$placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
	$params       = array_merge( array( absint( $business_id ) ), $order_ids );
	$rows         = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT order_id, call_session_id, source_call_session_id, attempt_count FROM {$table_name} WHERE business_id = %d AND order_id IN ({$placeholders})",
			$params
		),
		ARRAY_A
	);

	$counts = array(
		'by_order'   => array(),
		'by_session' => array(),
	);

	foreach ( is_array( $rows ) ? $rows : array() as $row ) {
		$order_id      = absint( $row['order_id'] ?? 0 );
		$attempt_count = absint( $row['attempt_count'] ?? 0 );

		if ( ! $attempt_count ) {
			continue;
		}

		if ( $order_id ) {
			$counts['by_order'][ $order_id ] = ( $counts['by_order'][ $order_id ] ?? 0 ) + $attempt_count;
		}

		$session_ids = array_values(
			array_unique(
				array_filter(
					array(
						absint( $row['source_call_session_id'] ?? 0 ),
						absint( $row['call_session_id'] ?? 0 ),
					)
				)
			)
		);

		foreach ( $session_ids as $call_session_id ) {
			$counts['by_session'][ $call_session_id ] = ( $counts['by_session'][ $call_session_id ] ?? 0 ) + $attempt_count;
		}
	}

	return $counts;
};

$wp_dialyra_get_log_rows = static function ( $table_name, $columns, $filters, $business_id ) use ( $wpdb, $wp_dialyra_has_column, $wp_dialyra_first_column ) {
	$where  = array( '1=1' );
	$params = array();

	if ( $business_id && $wp_dialyra_has_column( $columns, 'business_id' ) ) {
		$where[]  = 'business_id = %d';
		$params[] = absint( $business_id );
	}

	if ( '' !== $filters['order_id'] ) {
		$order_column = $wp_dialyra_first_column( $columns, array( 'order_id', 'woocommerce_order_id', 'wc_order_id' ) );

		if ( $order_column ) {
			$where[]  = "{$order_column} = %d";
			$params[] = absint( $filters['order_id'] );
		}
	}

	if ( '' !== $filters['status'] && $wp_dialyra_has_column( $columns, 'status' ) ) {
		$where[]  = 'status = %s';
		$params[] = sanitize_key( $filters['status'] );
	}

	if ( '' !== $filters['started_date'] ) {
		$date_column = $wp_dialyra_first_column( $columns, array( 'started_at', 'created_at' ) );

		if ( $date_column ) {
			$where[]  = "DATE({$date_column}) = %s";
			$params[] = sanitize_text_field( $filters['started_date'] );
		}
	}

	if ( '' !== $filters['phone'] ) {
		$phone_columns = array_filter(
			array( 'to_number', 'dialed_number', 'phone', 'from_number' ),
			static function ( $column ) use ( $columns, $wp_dialyra_has_column ) {
				return $wp_dialyra_has_column( $columns, $column );
			}
		);

		if ( ! empty( $phone_columns ) ) {
			$where[] = '(' . implode( ' OR ', array_map( static function ( $column ) { return "{$column} LIKE %s"; }, $phone_columns ) ) . ')';

			foreach ( $phone_columns as $phone_column ) {
				$params[] = '%' . $wpdb->esc_like( $filters['phone'] ) . '%';
			}
		}
	}

	$order_by = $wp_dialyra_has_column( $columns, 'updated_at' ) ? 'updated_at DESC' : 'id DESC';

	if ( $wp_dialyra_has_column( $columns, 'id' ) ) {
		$order_by .= ', id DESC';
	}

	$sql          = "SELECT * FROM {$table_name} WHERE " . implode( ' AND ', $where ) . " ORDER BY {$order_by} LIMIT 100";

	if ( ! empty( $params ) ) {
		$sql = $wpdb->prepare( $sql, $params );
	}

	return $wpdb->get_results( $sql, ARRAY_A );
};

$wp_dialyra_normalize_log = static function ( $row, $retry_counts ) use ( $wp_dialyra_row_value, $wp_dialyra_parse_json, $wp_dialyra_get_nested_value, $wp_dialyra_find_order_for_log, $wp_dialyra_format_datetime, $wp_dialyra_format_duration, $wp_dialyra_format_money ) {
	$row      = is_array( $row ) ? $row : array();
	$timeline = $wp_dialyra_parse_json( $wp_dialyra_row_value( $row, array( 'timeline' ), array() ) );
	$metadata = $wp_dialyra_parse_json( $wp_dialyra_row_value( $row, array( 'metadata', 'webhook_variables', 'template_values' ), array() ) );

	$order_id = absint(
		$wp_dialyra_row_value(
			$row,
			array( 'order_id', 'woocommerce_order_id', 'wc_order_id' ),
			$wp_dialyra_get_nested_value( $metadata, array( 'order_id', 'woocommerce_order_id', 'wc_order_id' ), 0 )
		)
	);
	$order    = $wp_dialyra_find_order_for_log(
		array(
			'id'              => absint( $wp_dialyra_row_value( $row, array( 'remote_call_log_id', 'call_log_id' ), 0 ) ),
			'order_id'        => $order_id,
			'call_session_id' => absint( $wp_dialyra_row_value( $row, array( 'call_session_id' ), 0 ) ),
		)
	);

	if ( ! $order_id && $order && is_object( $order ) && method_exists( $order, 'get_id' ) ) {
		$order_id = absint( $order->get_id() );
	}

	$customer_name = $wp_dialyra_get_nested_value( $metadata, array( 'customer_name', 'customer.name', 'order.customer_name' ), '' );
	$billing_phone = '';
	$order_status  = '';

	if ( $order && is_object( $order ) ) {
		$customer_name = $customer_name ? $customer_name : ( method_exists( $order, 'get_formatted_billing_full_name' ) ? trim( $order->get_formatted_billing_full_name() ) : '' );
		$billing_phone = method_exists( $order, 'get_billing_phone' ) ? sanitize_text_field( $order->get_billing_phone() ) : '';
		$order_status  = method_exists( $order, 'get_status' ) ? sanitize_key( $order->get_status() ) : '';
	}

	$dtmf_events = $wp_dialyra_get_nested_value( $timeline, array( 'dtmf_events' ), array() );
	$digits      = array();

	if ( is_array( $dtmf_events ) ) {
		foreach ( $dtmf_events as $event ) {
			if ( is_array( $event ) && isset( $event['digits'] ) && '' !== $event['digits'] ) {
				$digits[] = sanitize_text_field( $event['digits'] );
			} elseif ( is_array( $event ) && isset( $event['value'] ) && '' !== $event['value'] ) {
				$digits[] = sanitize_text_field( $event['value'] );
			}
		}
	}

	if ( empty( $digits ) ) {
		$template_dtmf_sequence = $wp_dialyra_get_nested_value( $metadata, array( 'template_values.dtmf_sequence' ), array() );

		if ( is_array( $template_dtmf_sequence ) ) {
			$digits = array_values( array_filter( array_map( 'sanitize_text_field', $template_dtmf_sequence ) ) );
		}
	}

	$status     = sanitize_key( $wp_dialyra_row_value( $row, array( 'status' ), 'pending' ) );
	$started_at = sanitize_text_field( $wp_dialyra_row_value( $row, array( 'started_at', 'created_at' ), '' ) );
	$updated_at = sanitize_text_field( $wp_dialyra_row_value( $row, array( 'updated_at', 'started_at', 'created_at' ), '' ) );
	$flow_id    = absint( $wp_dialyra_row_value( $row, array( 'flow_id' ), $wp_dialyra_get_nested_value( $metadata, array( 'flow_id' ), 0 ) ) );
	$number     = sanitize_text_field( $wp_dialyra_row_value( $row, array( 'to_number', 'dialed_number', 'phone' ), $billing_phone ) );
	$session_id = absint( $wp_dialyra_row_value( $row, array( 'call_session_id' ), 0 ) );
	$manual_retries = absint( $wp_dialyra_row_value( $row, array( 'retry_attempts' ), 0 ) );
	$auto_retries   = 0;

	if ( $session_id && isset( $retry_counts['by_session'][ $session_id ] ) ) {
		$auto_retries = absint( $retry_counts['by_session'][ $session_id ] );
	} elseif ( $order_id && isset( $retry_counts['by_order'][ $order_id ] ) ) {
		$auto_retries = absint( $retry_counts['by_order'][ $order_id ] );
	}

	return array(
		'local_log_id'    => absint( $wp_dialyra_row_value( $row, array( 'id' ), 0 ) ),
		'order_id'       => $order_id,
		'order_status'   => $order_status,
		'order_status_label' => $order_status ? ( function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( 'wc-' . $order_status ) : ucwords( str_replace( array( '-', '_' ), ' ', $order_status ) ) ) : __( 'Unknown', 'wp-dialyra' ),
		'customer_name'  => $customer_name ? sanitize_text_field( $customer_name ) : __( 'Unknown customer', 'wp-dialyra' ),
		'flow_name'      => $flow_id ? sprintf( __( 'Flow #%d', 'wp-dialyra' ), $flow_id ) : __( 'Dialyra flow', 'wp-dialyra' ),
		'number'         => $number ? $number : '—',
		'status'         => $status,
		'call_status'    => sanitize_text_field( $wp_dialyra_row_value( $row, array( 'call_status', 'hangup_cause_text', 'hangup_cause' ), '—' ) ),
		'duration'       => $wp_dialyra_format_duration( $wp_dialyra_row_value( $row, array( 'duration_sec', 'duration_seconds' ), 0 ) ),
		'billsec'        => $wp_dialyra_format_duration( $wp_dialyra_row_value( $row, array( 'billsec', 'bill_seconds' ), 0 ) ),
		'cost'           => $wp_dialyra_format_money( $wp_dialyra_row_value( $row, array( 'billing_charged_amount', 'cost' ), null ) ),
		'billing_status' => sanitize_text_field( $wp_dialyra_row_value( $row, array( 'billing_status', 'billing_clear_reason' ), '—' ) ),
		'dtmf'           => $digits ? implode( ', ', array_unique( $digits ) ) : sanitize_text_field( $wp_dialyra_row_value( $row, array( 'dtmf' ), $wp_dialyra_get_nested_value( $metadata, array( 'dtmf', 'dtmf_value', 'template_values.dtmf_value' ), '—' ) ) ),
		'dtmf_meta'      => $digits ? sprintf( _n( '%d event', '%d events', count( $digits ), 'wp-dialyra' ), count( $digits ) ) : __( 'none', 'wp-dialyra' ),
		'from_number'    => sanitize_text_field( $wp_dialyra_row_value( $row, array( 'from_number' ), '—' ) ),
		'sip_trunk_id'   => absint( $wp_dialyra_row_value( $row, array( 'sip_trunk_id' ), 0 ) ),
		'retries'        => $manual_retries + $auto_retries,
		'started_at_raw' => $started_at,
		'updated_at_raw' => $updated_at,
		'started_at'     => $wp_dialyra_format_datetime( $started_at ),
		'hangup_cause'   => sanitize_text_field( $wp_dialyra_row_value( $row, array( 'hangup_cause_text', 'hangup_cause' ), '—' ) ),
	);
};

$wp_dialyra_group_calls_by_order = static function ( $calls ) {
	$groups = array();

	foreach ( is_array( $calls ) ? $calls : array() as $call ) {
		$order_id  = absint( $call['order_id'] ?? 0 );
		$group_key = $order_id ? 'order_' . $order_id : 'log_' . absint( $call['local_log_id'] ?? 0 );

		if ( ! isset( $groups[ $group_key ] ) ) {
			$groups[ $group_key ] = array(
				'order_id' => $order_id,
				'latest'   => $call,
				'history'  => array(),
			);
		}

		$groups[ $group_key ]['history'][] = $call;

		$current_latest_time = ! empty( $groups[ $group_key ]['latest']['updated_at_raw'] ) ? strtotime( $groups[ $group_key ]['latest']['updated_at_raw'] ) : 0;
		$candidate_time      = ! empty( $call['updated_at_raw'] ) ? strtotime( $call['updated_at_raw'] ) : 0;

		if ( $candidate_time >= $current_latest_time ) {
			$groups[ $group_key ]['latest'] = $call;
		}
	}

	foreach ( $groups as $group_key => $group ) {
		usort(
			$group['history'],
			static function ( $left, $right ) {
				$left_time  = ! empty( $left['updated_at_raw'] ) ? strtotime( $left['updated_at_raw'] ) : 0;
				$right_time = ! empty( $right['updated_at_raw'] ) ? strtotime( $right['updated_at_raw'] ) : 0;

				if ( $left_time === $right_time ) {
					return absint( $right['local_log_id'] ?? 0 ) <=> absint( $left['local_log_id'] ?? 0 );
				}

				return $right_time <=> $left_time;
			}
		);

		$groups[ $group_key ]['history'] = $group['history'];
	}

	uasort(
		$groups,
		static function ( $left, $right ) {
			$left_latest  = is_array( $left['latest'] ?? null ) ? $left['latest'] : array();
			$right_latest = is_array( $right['latest'] ?? null ) ? $right['latest'] : array();
			$left_time    = ! empty( $left_latest['updated_at_raw'] ) ? strtotime( $left_latest['updated_at_raw'] ) : 0;
			$right_time   = ! empty( $right_latest['updated_at_raw'] ) ? strtotime( $right_latest['updated_at_raw'] ) : 0;

			if ( $left_time === $right_time ) {
				return absint( $right_latest['local_log_id'] ?? 0 ) <=> absint( $left_latest['local_log_id'] ?? 0 );
			}

			return $right_time <=> $left_time;
		}
	);

	return array_values( $groups );
};

$wp_dialyra_status_class = static function ( $status ) {
	if ( in_array( $status, array( 'completed', 'answered', 'answer', 'confirmed' ), true ) ) {
		return 'wp-dialyra-result--success';
	}

	if ( in_array( $status, array( 'busy', 'pending', 'initiated', 'ringing' ), true ) ) {
		return 'wp-dialyra-result--warning';
	}

	if ( in_array( $status, array( 'failed', 'no_answer', 'canceled', 'cancelled', 'originate_failed' ), true ) ) {
		return 'wp-dialyra-result--danger';
	}

	return 'wp-dialyra-result--muted';
};

$wp_dialyra_filter_calls = static function ( $calls, $filters ) {
	return array_values(
		array_filter(
			$calls,
			static function ( $call ) use ( $filters ) {
				if ( '' !== $filters['order_id'] && false === stripos( (string) $call['order_id'], $filters['order_id'] ) ) {
					return false;
				}

				if ( '' !== $filters['status'] && $filters['status'] !== $call['status'] ) {
					return false;
				}

				if ( '' !== $filters['started_date'] ) {
					$started_date = ! empty( $call['started_at_raw'] ) ? wp_date( 'Y-m-d', strtotime( $call['started_at_raw'] ) ) : '';

					if ( $filters['started_date'] !== $started_date ) {
						return false;
					}
				}

				if ( '' !== $filters['phone'] ) {
					$haystack = strtolower( $call['number'] . ' ' . $call['from_number'] );
					$needle   = strtolower( $filters['phone'] );

					if ( false === strpos( $haystack, $needle ) ) {
						return false;
					}
				}

				return true;
			}
		)
	);
};

if ( ! $wp_dialyra_table_exists( $wp_dialyra_log_table ) ) {
	$wp_dialyra_notice_error = sprintf(
		/* translators: %s: table name. */
		__( 'Call log table %s was not found.', 'wp-dialyra' ),
		$wp_dialyra_log_table
	);
} else {
	$wp_dialyra_log_columns = $wp_dialyra_get_table_columns( $wp_dialyra_log_table );

	if ( isset( $_POST['wp_dialyra_call_history_action'] ) && in_array( sanitize_key( wp_unslash( $_POST['wp_dialyra_call_history_action'] ) ), array( 'sync_call', 'retry_call' ), true ) ) {
		if ( ! isset( $_POST['wp_dialyra_call_history_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_dialyra_call_history_nonce'] ) ), 'wp-dialyra-call-history-sync' ) ) {
			$wp_dialyra_notice_error = __( 'Call action could not run because the security check failed.', 'wp-dialyra' );
		} else {
			$wp_dialyra_call_action = sanitize_key( wp_unslash( $_POST['wp_dialyra_call_history_action'] ) );
			$wp_dialyra_sync_log_id = isset( $_POST['dialyra_local_log_id'] ) ? absint( wp_unslash( $_POST['dialyra_local_log_id'] ) ) : 0;
			$wp_dialyra_sync_log    = $wp_dialyra_get_local_log( $wp_dialyra_sync_log_id );

			if ( empty( $wp_dialyra_sync_log ) ) {
				$wp_dialyra_notice_error = __( 'Local call log row was not found.', 'wp-dialyra' );
			} elseif ( 'retry_call' === $wp_dialyra_call_action ) {
				$wp_dialyra_retry_order_id = absint( $wp_dialyra_row_value( $wp_dialyra_sync_log, array( 'order_id', 'woocommerce_order_id', 'wc_order_id' ), 0 ) );
				$wp_dialyra_retry_status   = sanitize_key( $wp_dialyra_row_value( $wp_dialyra_sync_log, array( 'status' ), '' ) );

				if ( in_array( $wp_dialyra_retry_status, array( 'completed', 'answered', 'answer', 'confirmed' ), true ) ) {
					$wp_dialyra_notice_error = __( 'Completed calls cannot be retried.', 'wp-dialyra' );
				} elseif ( ! $wp_dialyra_retry_order_id ) {
					$wp_dialyra_notice_error = __( 'This call log is not linked to a WooCommerce order, so it cannot be retried.', 'wp-dialyra' );
				} elseif ( ! $wp_dialyra_plugin || ! method_exists( $wp_dialyra_plugin, 'get_call_originate_service' ) || ! class_exists( 'Dialyra_Call_Eligibility' ) || ! class_exists( 'Dialyra_Call_Log_Repository' ) ) {
					$wp_dialyra_notice_error = __( 'Instant retry service is not available.', 'wp-dialyra' );
				} else {
					$wp_dialyra_retry_eligibility = new Dialyra_Call_Eligibility();
					$wp_dialyra_retry_order        = function_exists( 'wc_get_order' ) ? wc_get_order( $wp_dialyra_retry_order_id ) : null;
					$wp_dialyra_order_action       = $wp_dialyra_retry_order && method_exists( $wp_dialyra_retry_order, 'get_meta' ) ? sanitize_key( $wp_dialyra_retry_order->get_meta( '_dialyra_last_order_action', true ) ) : '';
					$wp_dialyra_order_status       = $wp_dialyra_retry_order && method_exists( $wp_dialyra_retry_order, 'get_status' ) ? sanitize_key( $wp_dialyra_retry_order->get_status() ) : '';

					if ( ! $wp_dialyra_retry_order ) {
						$wp_dialyra_notice_error = __( 'This call cannot be retried because the WooCommerce order was not found.', 'wp-dialyra' );
					} elseif ( 'completed' === $wp_dialyra_order_status || 'confirmed' === $wp_dialyra_order_action ) {
						$wp_dialyra_notice_error = __( 'This call cannot be retried because the order is already completed.', 'wp-dialyra' );
					} elseif ( ! $wp_dialyra_retry_eligibility->has_concurrency_capacity() ) {
						$wp_dialyra_notice_error = __( 'No call slot is available right now. Please try again after the active call finishes.', 'wp-dialyra' );
					} else {
						$wp_dialyra_retry_event = array(
							'event_type'      => 'manual.call_retry_requested',
							'order_id'        => $wp_dialyra_retry_order_id,
							'call_session_id' => absint( $wp_dialyra_row_value( $wp_dialyra_sync_log, array( 'call_session_id' ), 0 ) ),
							'call_log_id'     => absint( $wp_dialyra_row_value( $wp_dialyra_sync_log, array( 'remote_call_log_id' ), 0 ) ),
							'local_log_id'    => $wp_dialyra_sync_log_id,
							'source'          => 'call_history',
						);

						do_action(
							class_exists( 'Dialyra_Hook_Names' ) ? Dialyra_Hook_Names::get_or_default( 'call', 'call_retry_requested', 'dialyra_call_retry_requested' ) : 'dialyra_call_retry_requested',
							$wp_dialyra_retry_order_id,
							absint( $wp_dialyra_retry_event['call_session_id'] ),
							$wp_dialyra_retry_event
						);

						$wp_dialyra_call_log_repository = new Dialyra_Call_Log_Repository();
						$wp_dialyra_call_log_repository->increment_retry_attempts( $wp_dialyra_sync_log_id );

						$wp_dialyra_retry_response = $wp_dialyra_plugin->get_call_originate_service()->originate_for_order(
							$wp_dialyra_retry_order_id,
							array(
								'source'               => 'manual_history_retry',
								'suppress_error_hooks' => true,
							)
						);

						if ( $wp_dialyra_retry_response instanceof Dialyra_API_Response && $wp_dialyra_retry_response->is_successful() ) {
							$wp_dialyra_notice_success = __( 'Retry call started immediately.', 'wp-dialyra' );
						} else {
							$wp_dialyra_notice_error = $wp_dialyra_retry_response instanceof Dialyra_API_Response ? $wp_dialyra_retry_response->get_message() : __( 'Retry call could not be started.', 'wp-dialyra' );
						}
					}
				}
			} else {
				$wp_dialyra_api_endpoints = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_api_endpoints() : null;
				$wp_dialyra_query         = array();
				$wp_dialyra_path_id       = 0;

				if ( ! empty( $wp_dialyra_sync_log['action_id'] ) ) {
					$wp_dialyra_query['action_id'] = sanitize_text_field( $wp_dialyra_sync_log['action_id'] );
				} elseif ( ! empty( $wp_dialyra_sync_log['call_session_id'] ) ) {
					$wp_dialyra_query['call_session_id'] = absint( $wp_dialyra_sync_log['call_session_id'] );
				} elseif ( ! empty( $wp_dialyra_sync_log['remote_call_log_id'] ) ) {
					$wp_dialyra_path_id = absint( $wp_dialyra_sync_log['remote_call_log_id'] );
				}

				if ( ! $wp_dialyra_api_endpoints || ! method_exists( $wp_dialyra_api_endpoints, 'get_call_history' ) ) {
					$wp_dialyra_notice_error = __( 'Dialyra API service is not available.', 'wp-dialyra' );
				} elseif ( empty( $wp_dialyra_query ) && ! $wp_dialyra_path_id ) {
					$wp_dialyra_notice_error = __( 'This local call log has no action ID, call session ID, or remote call log ID to sync from Dialyra.', 'wp-dialyra' );
				} else {
					$wp_dialyra_history_response = $wp_dialyra_api_endpoints->get_call_history( $wp_dialyra_path_id, $wp_dialyra_query );

					if ( ! $wp_dialyra_history_response || ! $wp_dialyra_history_response->is_successful() ) {
						$wp_dialyra_notice_error = $wp_dialyra_history_response ? $wp_dialyra_history_response->get_message() : __( 'Dialyra call history could not be fetched.', 'wp-dialyra' );
					} else {
						$wp_dialyra_history_data = $wp_dialyra_extract_response_data( $wp_dialyra_history_response );
						$wp_dialyra_repository   = class_exists( 'Dialyra_Call_Log_Repository' ) ? new Dialyra_Call_Log_Repository() : null;
						$wp_dialyra_synced       = $wp_dialyra_repository ? $wp_dialyra_repository->sync_from_history_response( $wp_dialyra_sync_log_id, $wp_dialyra_history_data ) : false;

						if ( $wp_dialyra_synced ) {
							$wp_dialyra_notice_success = __( 'Call log synced from Dialyra history.', 'wp-dialyra' );
						} else {
							$wp_dialyra_notice_error = __( 'Call history was fetched, but the local row could not be updated.', 'wp-dialyra' );
						}
					}
				}
			}
		}
	}

	$wp_dialyra_log_rows    = $wp_dialyra_get_log_rows( $wp_dialyra_log_table, $wp_dialyra_log_columns, $wp_dialyra_filters, $wp_dialyra_business_id );
	$wp_dialyra_order_ids   = array();

	foreach ( is_array( $wp_dialyra_log_rows ) ? $wp_dialyra_log_rows : array() as $wp_dialyra_log_row ) {
		$wp_dialyra_order_ids[] = absint( $wp_dialyra_row_value( $wp_dialyra_log_row, array( 'order_id', 'woocommerce_order_id', 'wc_order_id' ), 0 ) );
	}

	$wp_dialyra_retry_counts = $wp_dialyra_get_retry_counts( $wp_dialyra_business_id, $wp_dialyra_order_ids );

	foreach ( is_array( $wp_dialyra_log_rows ) ? $wp_dialyra_log_rows : array() as $wp_dialyra_log_row ) {
		$wp_dialyra_calls[] = $wp_dialyra_normalize_log( $wp_dialyra_log_row, $wp_dialyra_retry_counts );
	}

	$wp_dialyra_calls = $wp_dialyra_filter_calls( $wp_dialyra_calls, $wp_dialyra_filters );
	$wp_dialyra_call_groups = $wp_dialyra_group_calls_by_order( $wp_dialyra_calls );
}

$wp_dialyra_statuses = array( 'completed', 'failed', 'busy', 'no_answer', 'canceled', 'pending', 'initiated', 'ringing', 'answered', 'originate_failed' );
?>

<section class="wp-dialyra-call-history">
	<div class="wp-dialyra-call-history__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Call History', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Review locally saved call logs with WooCommerce context.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'This page reads from the WordPress Dialyra call log table and enriches rows with WooCommerce order meta.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-call-history__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="#wp-dialyra-call-log"><?php esc_html_e( 'View Calls', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<?php if ( $wp_dialyra_notice_error ) : ?>
		<div class="wp-dialyra-notice wp-dialyra-notice--error">
			<strong><?php echo esc_html( $wp_dialyra_notice_error ); ?></strong>
		</div>
	<?php endif; ?>

	<?php if ( $wp_dialyra_notice_success ) : ?>
		<div class="wp-dialyra-notice wp-dialyra-notice--success">
			<strong><?php echo esc_html( $wp_dialyra_notice_success ); ?></strong>
		</div>
	<?php endif; ?>

	<section class="wp-dialyra-call-history-panel">
		<div class="wp-dialyra-call-history-panel__head">
			<span aria-hidden="true">01</span>
			<div>
				<h3><?php esc_html_e( 'Filters', 'wp-dialyra' ); ?></h3>
				<p><?php esc_html_e( 'Narrow local call logs by WooCommerce order, call state, date, or phone.', 'wp-dialyra' ); ?></p>
			</div>
		</div>

		<form class="wp-dialyra-call-filters" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="wp-dialyra">
			<input type="hidden" name="p" value="call-history">

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-filter-order"><?php esc_html_e( 'Order ID', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-filter-order" name="order_id" type="search" value="<?php echo esc_attr( $wp_dialyra_filters['order_id'] ); ?>" placeholder="<?php esc_attr_e( '1048', 'wp-dialyra' ); ?>">
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-filter-status"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></label>
				<select id="wp-dialyra-filter-status" name="status">
					<option value=""><?php esc_html_e( 'All statuses', 'wp-dialyra' ); ?></option>
					<?php foreach ( $wp_dialyra_statuses as $status ) : ?>
						<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $wp_dialyra_filters['status'], $status ); ?>><?php echo esc_html( $status ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-filter-date"><?php esc_html_e( 'Started date', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-filter-date" name="started_date" type="date" value="<?php echo esc_attr( $wp_dialyra_filters['started_date'] ); ?>">
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-filter-phone"><?php esc_html_e( 'Phone number', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-filter-phone" name="phone" type="search" value="<?php echo esc_attr( $wp_dialyra_filters['phone'] ); ?>" placeholder="<?php esc_attr_e( '01631596697', 'wp-dialyra' ); ?>">
			</div>

			<div class="wp-dialyra-call-filters__actions">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Apply filters', 'wp-dialyra' ); ?></button>
				<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>"><?php esc_html_e( 'Reset', 'wp-dialyra' ); ?></a>
			</div>
		</form>
	</section>

	<section id="wp-dialyra-call-log" class="wp-dialyra-call-history-panel wp-dialyra-call-history-panel--table">
		<div class="wp-dialyra-call-history-panel__head wp-dialyra-call-history-panel__head--split">
			<div class="wp-dialyra-call-history-panel__title">
				<span aria-hidden="true">02</span>
				<div>
					<h3><?php esc_html_e( 'Call log', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Completed, failed, and no-answer sessions with order links and billing result.', 'wp-dialyra' ); ?></p>
				</div>
			</div>
				<em class="wp-dialyra-result wp-dialyra-result--muted"><?php esc_html_e( 'View only', 'wp-dialyra' ); ?></em>
		</div>

		<?php if ( empty( $wp_dialyra_call_groups ) ) : ?>
			<div class="wp-dialyra-empty-card">
				<span class="dashicons dashicons-phone" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'No local call logs found', 'wp-dialyra' ); ?></h3>
				<p><?php esc_html_e( 'Call rows appear here after records are available in the local Dialyra call logs table.', 'wp-dialyra' ); ?></p>
			</div>
		<?php else : ?>
			<div class="wp-dialyra-call-table" role="table" aria-label="<?php esc_attr_e( 'Call history table', 'wp-dialyra' ); ?>">
				<div role="row">
					<span><?php esc_html_e( 'Order ID', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Customer name', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Number', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Duration', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Cost', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'DTMF', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'From number', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Retries', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Started time', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Actions', 'wp-dialyra' ); ?></span>
				</div>

				<?php foreach ( $wp_dialyra_call_groups as $call_group ) : ?>
					<?php
					$call          = is_array( $call_group['latest'] ?? null ) ? $call_group['latest'] : array();
					$call_history  = is_array( $call_group['history'] ?? null ) ? $call_group['history'] : array();
					$history_count = count( $call_history );
					?>
					<div class="wp-dialyra-call-order-group">
					<div role="row" class="wp-dialyra-call-order-row">
							<span>
								<?php if ( $call['order_id'] ) : ?>
									<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $call['order_id'] ) . '&action=edit' ) ); ?>"><?php echo esc_html( $call['order_id'] ); ?></a>
									<small><?php echo esc_html( $call['order_status_label'] ); ?></small>
							<?php else : ?>
								<strong>—</strong>
								<small><?php esc_html_e( 'No order link', 'wp-dialyra' ); ?></small>
							<?php endif; ?>
						</span>
							<span><strong><?php echo esc_html( $call['customer_name'] ); ?></strong><small><?php echo esc_html( $call['flow_name'] ); ?></small></span>
							<span><strong><?php echo esc_html( $call['number'] ); ?></strong><small><?php esc_html_e( 'dialed outbound', 'wp-dialyra' ); ?></small></span>
						<span><em class="wp-dialyra-result <?php echo esc_attr( $wp_dialyra_status_class( $call['status'] ) ); ?>"><?php echo esc_html( $call['status'] ); ?></em><small><?php echo esc_html( $call['call_status'] ); ?></small></span>
						<span><strong><?php echo esc_html( $call['duration'] ); ?></strong><small><?php echo esc_html( sprintf( __( 'billsec %s', 'wp-dialyra' ), $call['billsec'] ) ); ?></small></span>
						<span><strong><?php echo esc_html( $call['cost'] ); ?></strong><small><?php echo esc_html( $call['billing_status'] ); ?></small></span>
						<span><code><?php echo esc_html( $call['dtmf'] ); ?></code><small><?php echo esc_html( $call['dtmf_meta'] ); ?></small></span>
						<span><code><?php echo esc_html( $call['from_number'] ); ?></code><small><?php echo $call['sip_trunk_id'] ? esc_html( sprintf( __( 'trunk %d', 'wp-dialyra' ), $call['sip_trunk_id'] ) ) : esc_html__( 'no trunk', 'wp-dialyra' ); ?></small></span>
							<span><strong><?php echo esc_html( $call['retries'] ); ?></strong><small><?php echo esc_html( 1 === absint( $call['retries'] ) ? __( 'attempt', 'wp-dialyra' ) : __( 'attempts', 'wp-dialyra' ) ); ?></small></span>
						<span><strong><?php echo esc_html( $call['started_at'] ); ?></strong><small><?php echo esc_html( $call['hangup_cause'] ); ?></small></span>
						<span>
							<span class="wp-dialyra-call-history-row-actions">
							<form class="wp-dialyra-call-sync-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>">
								<?php wp_nonce_field( 'wp-dialyra-call-history-sync', 'wp_dialyra_call_history_nonce' ); ?>
								<input type="hidden" name="wp_dialyra_call_history_action" value="sync_call">
								<input type="hidden" name="dialyra_local_log_id" value="<?php echo esc_attr( $call['local_log_id'] ); ?>">
								<button class="wp-dialyra-icon-button wp-dialyra-call-sync-button" type="submit" title="<?php esc_attr_e( 'Sync from Dialyra', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Sync call from Dialyra', 'wp-dialyra' ); ?>">
									<span class="dashicons dashicons-update" aria-hidden="true"></span>
								</button>
							</form>
							<?php if ( ! in_array( $call['status'], array( 'completed', 'answered', 'answer', 'confirmed' ), true ) ) : ?>
								<form class="wp-dialyra-call-sync-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>">
									<?php wp_nonce_field( 'wp-dialyra-call-history-sync', 'wp_dialyra_call_history_nonce' ); ?>
									<input type="hidden" name="wp_dialyra_call_history_action" value="retry_call">
									<input type="hidden" name="dialyra_local_log_id" value="<?php echo esc_attr( $call['local_log_id'] ); ?>">
									<button class="wp-dialyra-icon-button wp-dialyra-call-sync-button wp-dialyra-call-retry-button" type="submit" title="<?php esc_attr_e( 'Retry call', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Retry call', 'wp-dialyra' ); ?>">
										<span class="dashicons dashicons-controls-repeat" aria-hidden="true"></span>
									</button>
								</form>
							<?php endif; ?>
							</span>
						</span>
					</div>
					<?php if ( $history_count > 1 ) : ?>
						<details class="wp-dialyra-call-history-attempts">
							<summary>
								<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
								<?php
								printf(
									/* translators: %d: call attempt count. */
									esc_html__( 'Show %d call attempts', 'wp-dialyra' ),
									absint( $history_count )
								);
								?>
							</summary>
							<div class="wp-dialyra-call-history-attempt-list">
								<?php foreach ( $call_history as $history_call ) : ?>
									<div role="row" class="wp-dialyra-call-attempt-row">
										<span>
											<strong><?php echo esc_html( sprintf( __( 'Attempt #%d', 'wp-dialyra' ), absint( $history_call['local_log_id'] ) ) ); ?></strong>
											<small><?php echo esc_html( $history_call['order_id'] ? $history_call['order_status_label'] : __( 'No order link', 'wp-dialyra' ) ); ?></small>
										</span>
										<span><strong><?php echo esc_html( $history_call['customer_name'] ); ?></strong><small><?php echo esc_html( $history_call['flow_name'] ); ?></small></span>
										<span><strong><?php echo esc_html( $history_call['number'] ); ?></strong><small><?php esc_html_e( 'dialed outbound', 'wp-dialyra' ); ?></small></span>
										<span><em class="wp-dialyra-result <?php echo esc_attr( $wp_dialyra_status_class( $history_call['status'] ) ); ?>"><?php echo esc_html( $history_call['status'] ); ?></em><small><?php echo esc_html( $history_call['call_status'] ); ?></small></span>
										<span><strong><?php echo esc_html( $history_call['duration'] ); ?></strong><small><?php echo esc_html( sprintf( __( 'billsec %s', 'wp-dialyra' ), $history_call['billsec'] ) ); ?></small></span>
										<span><strong><?php echo esc_html( $history_call['cost'] ); ?></strong><small><?php echo esc_html( $history_call['billing_status'] ); ?></small></span>
										<span><code><?php echo esc_html( $history_call['dtmf'] ); ?></code><small><?php echo esc_html( $history_call['dtmf_meta'] ); ?></small></span>
										<span><code><?php echo esc_html( $history_call['from_number'] ); ?></code><small><?php echo $history_call['sip_trunk_id'] ? esc_html( sprintf( __( 'trunk %d', 'wp-dialyra' ), $history_call['sip_trunk_id'] ) ) : esc_html__( 'no trunk', 'wp-dialyra' ); ?></small></span>
										<span><strong><?php echo esc_html( $history_call['retries'] ); ?></strong><small><?php echo esc_html( 1 === absint( $history_call['retries'] ) ? __( 'attempt', 'wp-dialyra' ) : __( 'attempts', 'wp-dialyra' ) ); ?></small></span>
										<span><strong><?php echo esc_html( $history_call['started_at'] ); ?></strong><small><?php echo esc_html( $history_call['hangup_cause'] ); ?></small></span>
										<span>
											<span class="wp-dialyra-call-history-row-actions">
												<form class="wp-dialyra-call-sync-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>">
													<?php wp_nonce_field( 'wp-dialyra-call-history-sync', 'wp_dialyra_call_history_nonce' ); ?>
													<input type="hidden" name="wp_dialyra_call_history_action" value="sync_call">
													<input type="hidden" name="dialyra_local_log_id" value="<?php echo esc_attr( $history_call['local_log_id'] ); ?>">
													<button class="wp-dialyra-icon-button wp-dialyra-call-sync-button" type="submit" title="<?php esc_attr_e( 'Sync this call', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Sync this call from Dialyra', 'wp-dialyra' ); ?>">
														<span class="dashicons dashicons-update" aria-hidden="true"></span>
													</button>
												</form>
											</span>
										</span>
									</div>
								<?php endforeach; ?>
							</div>
						</details>
					<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
</section>
