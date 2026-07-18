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
$wp_dialyra_notice_error = '';
$wp_dialyra_calls        = array();
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
		return array();
	}

	$table_name = Dialyra_Retry_Repository::get_table_name();

	if ( ! $wp_dialyra_table_exists( $table_name ) ) {
		return array();
	}

	$placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
	$params       = array_merge( array( absint( $business_id ) ), $order_ids );
	$rows         = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT order_id, COUNT(*) AS retry_count, MAX(attempt_count) AS max_attempts FROM {$table_name} WHERE business_id = %d AND order_id IN ({$placeholders}) GROUP BY order_id",
			$params
		),
		ARRAY_A
	);

	$counts = array();

	foreach ( is_array( $rows ) ? $rows : array() as $row ) {
		$order_id = absint( $row['order_id'] ?? 0 );

		if ( $order_id ) {
			$counts[ $order_id ] = max( absint( $row['retry_count'] ?? 0 ), absint( $row['max_attempts'] ?? 0 ) );
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

	$order_column = $wp_dialyra_first_column( $columns, array( 'started_at', 'created_at', 'updated_at', 'id' ) );
	$order_by     = $order_column ? "{$order_column} DESC" : 'id DESC';
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

	if ( $order && is_object( $order ) ) {
		$customer_name = $customer_name ? $customer_name : ( method_exists( $order, 'get_formatted_billing_full_name' ) ? trim( $order->get_formatted_billing_full_name() ) : '' );
		$billing_phone = method_exists( $order, 'get_billing_phone' ) ? sanitize_text_field( $order->get_billing_phone() ) : '';
	}

	$dtmf_events = $wp_dialyra_get_nested_value( $timeline, array( 'dtmf_events' ), array() );
	$digits      = array();

	if ( is_array( $dtmf_events ) ) {
		foreach ( $dtmf_events as $event ) {
			if ( is_array( $event ) && isset( $event['digits'] ) && '' !== $event['digits'] ) {
				$digits[] = sanitize_text_field( $event['digits'] );
			}
		}
	}

	$status     = sanitize_key( $wp_dialyra_row_value( $row, array( 'status' ), 'pending' ) );
	$started_at = sanitize_text_field( $wp_dialyra_row_value( $row, array( 'started_at', 'created_at' ), '' ) );
	$flow_id    = absint( $wp_dialyra_row_value( $row, array( 'flow_id' ), $wp_dialyra_get_nested_value( $metadata, array( 'flow_id' ), 0 ) ) );
	$number     = sanitize_text_field( $wp_dialyra_row_value( $row, array( 'to_number', 'dialed_number', 'phone' ), $billing_phone ) );

	return array(
		'order_id'       => $order_id,
		'customer_name'  => $customer_name ? sanitize_text_field( $customer_name ) : __( 'Unknown customer', 'wp-dialyra' ),
		'flow_name'      => $flow_id ? sprintf( __( 'Flow #%d', 'wp-dialyra' ), $flow_id ) : __( 'Dialyra flow', 'wp-dialyra' ),
		'number'         => $number ? $number : '—',
		'status'         => $status,
		'call_status'    => sanitize_text_field( $wp_dialyra_row_value( $row, array( 'call_status', 'hangup_cause_text', 'hangup_cause' ), '—' ) ),
		'duration'       => $wp_dialyra_format_duration( $wp_dialyra_row_value( $row, array( 'duration_sec', 'duration_seconds' ), 0 ) ),
		'billsec'        => $wp_dialyra_format_duration( $wp_dialyra_row_value( $row, array( 'billsec', 'bill_seconds' ), 0 ) ),
		'cost'           => $wp_dialyra_format_money( $wp_dialyra_row_value( $row, array( 'billing_charged_amount', 'cost' ), null ) ),
		'billing_status' => sanitize_text_field( $wp_dialyra_row_value( $row, array( 'billing_status', 'billing_clear_reason' ), '—' ) ),
		'dtmf'           => $digits ? implode( ', ', array_unique( $digits ) ) : sanitize_text_field( $wp_dialyra_get_nested_value( $metadata, array( 'dtmf', 'dtmf_value' ), '—' ) ),
		'dtmf_meta'      => $digits ? sprintf( _n( '%d event', '%d events', count( $digits ), 'wp-dialyra' ), count( $digits ) ) : __( 'none', 'wp-dialyra' ),
		'from_number'    => sanitize_text_field( $wp_dialyra_row_value( $row, array( 'from_number' ), '—' ) ),
		'sip_trunk_id'   => absint( $wp_dialyra_row_value( $row, array( 'sip_trunk_id' ), 0 ) ),
		'retries'        => $order_id && isset( $retry_counts[ $order_id ] ) ? absint( $retry_counts[ $order_id ] ) : 0,
		'started_at_raw' => $started_at,
		'started_at'     => $wp_dialyra_format_datetime( $started_at ),
		'hangup_cause'   => sanitize_text_field( $wp_dialyra_row_value( $row, array( 'hangup_cause_text', 'hangup_cause' ), '—' ) ),
	);
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
					<p><?php esc_html_e( 'Completed, failed, and no-answer rows from the local Dialyra call logs table.', 'wp-dialyra' ); ?></p>
				</div>
			</div>
			<em class="wp-dialyra-result wp-dialyra-result--muted">
				<?php
				printf(
					/* translators: %d: call count. */
					esc_html( _n( '%d call', '%d calls', count( $wp_dialyra_calls ), 'wp-dialyra' ) ),
					absint( count( $wp_dialyra_calls ) )
				);
				?>
			</em>
		</div>

		<?php if ( empty( $wp_dialyra_calls ) ) : ?>
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
				</div>

				<?php foreach ( $wp_dialyra_calls as $call ) : ?>
					<div role="row">
						<span>
							<?php if ( $call['order_id'] ) : ?>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $call['order_id'] ) . '&action=edit' ) ); ?>">#<?php echo esc_html( $call['order_id'] ); ?></a>
								<small><?php esc_html_e( 'WooCommerce order', 'wp-dialyra' ); ?></small>
							<?php else : ?>
								<strong>—</strong>
								<small><?php esc_html_e( 'No order link', 'wp-dialyra' ); ?></small>
							<?php endif; ?>
						</span>
						<span><strong><?php echo esc_html( $call['customer_name'] ); ?></strong><small><?php echo esc_html( $call['flow_name'] ); ?></small></span>
						<span><strong><?php echo esc_html( $call['number'] ); ?></strong><small><?php esc_html_e( 'local log', 'wp-dialyra' ); ?></small></span>
						<span><em class="wp-dialyra-result <?php echo esc_attr( $wp_dialyra_status_class( $call['status'] ) ); ?>"><?php echo esc_html( $call['status'] ); ?></em><small><?php echo esc_html( $call['call_status'] ); ?></small></span>
						<span><strong><?php echo esc_html( $call['duration'] ); ?></strong><small><?php echo esc_html( sprintf( __( 'billsec %s', 'wp-dialyra' ), $call['billsec'] ) ); ?></small></span>
						<span><strong><?php echo esc_html( $call['cost'] ); ?></strong><small><?php echo esc_html( $call['billing_status'] ); ?></small></span>
						<span><code><?php echo esc_html( $call['dtmf'] ); ?></code><small><?php echo esc_html( $call['dtmf_meta'] ); ?></small></span>
						<span><code><?php echo esc_html( $call['from_number'] ); ?></code><small><?php echo $call['sip_trunk_id'] ? esc_html( sprintf( __( 'trunk %d', 'wp-dialyra' ), $call['sip_trunk_id'] ) ) : esc_html__( 'no trunk', 'wp-dialyra' ); ?></small></span>
						<span><strong><?php echo esc_html( $call['retries'] ); ?></strong><small><?php esc_html_e( 'local retry records', 'wp-dialyra' ); ?></small></span>
						<span><strong><?php echo esc_html( $call['started_at'] ); ?></strong><small><?php echo esc_html( $call['hangup_cause'] ); ?></small></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
</section>
