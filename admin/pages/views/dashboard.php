<?php

/**
 * Dashboard page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;

$wp_dialyra_plugin        = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
$wp_dialyra_api_endpoints = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_api_endpoints() : null;
$wp_dialyra_business_manager = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_business_manager() : null;
$wp_dialyra_flow_manager  = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_flow_manager() : null;
$wp_dialyra_business_id   = class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
$wp_dialyra_today_start   = date( 'Y-m-d 00:00:00', current_time( 'timestamp' ) );
$wp_dialyra_dashboard_notice = '';

if ( isset( $_POST['wp_dialyra_dashboard_action'] ) && 'reload_balance' === sanitize_key( wp_unslash( $_POST['wp_dialyra_dashboard_action'] ) ) ) {
	if ( ! isset( $_POST['wp_dialyra_dashboard_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_dialyra_dashboard_nonce'] ) ), 'wp-dialyra-dashboard' ) ) {
		$wp_dialyra_dashboard_notice = __( 'Balance could not be refreshed because the security check failed.', 'wp-dialyra' );
	} else {
		$wp_dialyra_load_balance_hook = class_exists( 'Dialyra_Hook_Names' ) ? Dialyra_Hook_Names::get_or_default( 'business', 'balance_load_requested', 'wp_dialyra_load_balance' ) : 'wp_dialyra_load_balance';

		do_action( $wp_dialyra_load_balance_hook );

		$wp_dialyra_dashboard_notice = __( 'Balance refreshed from Dialyra.', 'wp-dialyra' );
	}
}

$wp_dialyra_extract_data = static function ( $response ) {
	if ( ! $response || ! is_object( $response ) || ! method_exists( $response, 'is_successful' ) || ! $response->is_successful() || ! method_exists( $response, 'get_data' ) ) {
		return array();
	}

	$data = $response->get_data();
	$data = is_array( $data ) ? $data : array();

	if ( isset( $data['data'] ) && is_array( $data['data'] ) && ! isset( $data['id'] ) && ! isset( $data['items'] ) ) {
		$data = $data['data'];
	}

	return $data;
};

$wp_dialyra_extract_items = static function ( $response ) use ( $wp_dialyra_extract_data ) {
	$data = $wp_dialyra_extract_data( $response );

	foreach ( array( 'items', 'agents', 'departments', 'flows', 'data' ) as $container_key ) {
		if ( isset( $data[ $container_key ] ) && is_array( $data[ $container_key ] ) ) {
			return $data[ $container_key ];
		}
	}

	return isset( $data[0] ) && is_array( $data[0] ) ? $data : array();
};

$wp_dialyra_table_exists = static function ( $table_name ) use ( $wpdb ) {
	return $table_name && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
};

$wp_dialyra_money = static function ( $amount ) {
	return '৳ ' . number_format_i18n( (float) $amount, 2 );
};

$wp_dialyra_format_balance = static function ( $amount, $currency = 'BDT' ) {
	$currency = strtoupper( sanitize_text_field( $currency ) );

	if ( in_array( $currency, array( 'BDT', '৳', 'TK' ), true ) ) {
		return '৳ ' . number_format_i18n( (float) $amount, 2 );
	}

	return trim( $currency . ' ' . number_format_i18n( (float) $amount, 2 ) );
};

$wp_dialyra_percent = static function ( $part, $total ) {
	$total = absint( $total );

	return $total ? round( ( absint( $part ) / $total ) * 100 ) : 0;
};

$wp_dialyra_format_datetime = static function ( $datetime ) {
	if ( empty( $datetime ) ) {
		return __( '—', 'wp-dialyra' );
	}

	$timestamp = strtotime( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $datetime ) ) ) );

	if ( ! $timestamp ) {
		$timestamp = strtotime( $datetime );
	}

	return $timestamp ? date_i18n( 'Y-m-d H:i', $timestamp ) : sanitize_text_field( $datetime );
};

$wp_dialyra_human_interval = static function ( $datetime ) {
	$timestamp = $datetime ? strtotime( $datetime ) : 0;

	if ( ! $timestamp ) {
		return __( 'Queued', 'wp-dialyra' );
	}

	return human_time_diff( $timestamp, current_time( 'timestamp' ) );
};

$wp_dialyra_result_class = static function ( $status ) {
	$status = sanitize_key( $status );

	if ( in_array( $status, array( 'completed', 'confirmed', 'answered', 'active', 'open', 'ready' ), true ) ) {
		return 'wp-dialyra-result--success';
	}

	if ( in_array( $status, array( 'busy', 'pending', 'initiated', 'ringing', 'scheduled', 'queued', 'warning' ), true ) ) {
		return 'wp-dialyra-result--warning';
	}

	if ( in_array( $status, array( 'failed', 'no_answer', 'cancelled', 'canceled', 'originate_failed', 'closed', 'inactive', 'suspended' ), true ) ) {
		return 'wp-dialyra-result--danger';
	}

	return 'wp-dialyra-result--muted';
};

$wp_dialyra_status_label = static function ( $status ) {
	$status = sanitize_key( $status );

	return $status ? ucwords( str_replace( '_', ' ', $status ) ) : __( 'Unknown', 'wp-dialyra' );
};

$wp_dialyra_get_order_details = static function ( $order_id ) {
	$order_id = absint( $order_id );

	if ( ! $order_id || ! function_exists( 'wc_get_order' ) ) {
		return array(
			'name'   => __( 'Unknown customer', 'wp-dialyra' ),
			'phone'  => '',
			'status' => __( 'No order link', 'wp-dialyra' ),
		);
	}

	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return array(
			'name'   => __( 'Unknown customer', 'wp-dialyra' ),
			'phone'  => '',
			'status' => __( 'Order not found', 'wp-dialyra' ),
		);
	}

	$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

	return array(
		'name'   => $name ? $name : __( 'Guest customer', 'wp-dialyra' ),
		'phone'  => $order->get_billing_phone(),
		'status' => wc_get_order_status_name( $order->get_status() ),
	);
};

$wp_dialyra_normalize_agent = static function ( $agent ) {
	$agent    = is_array( $agent ) ? $agent : array();
	$metadata = isset( $agent['metadata'] ) && is_array( $agent['metadata'] ) ? $agent['metadata'] : array();
	$skills   = isset( $agent['skills'] ) && is_array( $agent['skills'] ) ? $agent['skills'] : array();

	return array(
		'name'                 => ! empty( $agent['name'] ) ? sanitize_text_field( $agent['name'] ) : __( 'Unnamed agent', 'wp-dialyra' ),
		'email'                => ! empty( $agent['email'] ) ? sanitize_email( $agent['email'] ) : '',
		'phone'                => ! empty( $agent['phone'] ) ? sanitize_text_field( $agent['phone'] ) : '',
		'sip_extension'        => ! empty( $agent['sip_extension'] ) ? sanitize_text_field( $agent['sip_extension'] ) : '',
		'status'               => ! empty( $agent['status'] ) ? sanitize_key( $agent['status'] ) : 'active',
		'availability_status'  => ! empty( $agent['availability_status'] ) ? sanitize_key( $agent['availability_status'] ) : 'offline',
		'max_concurrent_calls' => isset( $agent['max_concurrent_calls'] ) ? max( 1, absint( $agent['max_concurrent_calls'] ) ) : 1,
		'current_active_calls' => isset( $agent['current_active_calls'] ) ? absint( $agent['current_active_calls'] ) : 0,
		'languages'            => isset( $skills['language'] ) && is_array( $skills['language'] ) ? array_slice( array_values( array_filter( array_map( 'sanitize_text_field', $skills['language'] ) ) ), 0, 3 ) : array(),
		'team'                 => ! empty( $metadata['team'] ) ? sanitize_text_field( $metadata['team'] ) : '',
	);
};

$wp_dialyra_normalize_department = static function ( $department ) {
	$department   = is_array( $department ) ? $department : array();
	$metadata     = isset( $department['metadata'] ) && is_array( $department['metadata'] ) ? $department['metadata'] : array();
	$availability = isset( $department['availability'] ) && is_array( $department['availability'] ) ? $department['availability'] : array();

	return array(
		'name'                => ! empty( $department['name'] ) ? sanitize_text_field( $department['name'] ) : __( 'Untitled department', 'wp-dialyra' ),
		'description'         => ! empty( $department['description'] ) ? sanitize_textarea_field( $department['description'] ) : '',
		'status'              => ! empty( $department['status'] ) ? sanitize_key( $department['status'] ) : 'active',
		'strategy'            => ! empty( $department['strategy'] ) ? sanitize_key( $department['strategy'] ) : 'least_busy',
		'default_language'    => ! empty( $metadata['default_language'] ) ? sanitize_text_field( $metadata['default_language'] ) : '',
		'availability_status' => ! empty( $department['availability_status'] ) ? sanitize_key( $department['availability_status'] ) : ( ! empty( $availability['availability_status'] ) ? sanitize_key( $availability['availability_status'] ) : '' ),
		'created_at'          => ! empty( $department['created_at'] ) ? sanitize_text_field( $department['created_at'] ) : '',
		'updated_at'          => ! empty( $department['updated_at'] ) ? sanitize_text_field( $department['updated_at'] ) : '',
	);
};

$wp_dialyra_call_table  = class_exists( 'Dialyra_Call_Log_Repository' ) ? Dialyra_Call_Log_Repository::get_table_name() : $wpdb->prefix . 'dialyra_call_logs';
$wp_dialyra_queue_table = class_exists( 'Dialyra_Call_Queue_Repository' ) ? Dialyra_Call_Queue_Repository::get_table_name() : $wpdb->prefix . 'dialyra_call_queue';
$wp_dialyra_retry_table = class_exists( 'Dialyra_Retry_Repository' ) ? Dialyra_Retry_Repository::get_table_name() : $wpdb->prefix . 'dialyra_retry_queue';
$wp_dialyra_has_calls   = $wp_dialyra_table_exists( $wp_dialyra_call_table );
$wp_dialyra_has_queue   = $wp_dialyra_table_exists( $wp_dialyra_queue_table );
$wp_dialyra_has_retries = $wp_dialyra_table_exists( $wp_dialyra_retry_table );

$wp_dialyra_business_where = $wp_dialyra_business_id ? $wpdb->prepare( ' AND business_id = %d', $wp_dialyra_business_id ) : '';

$wp_dialyra_today_calls = $wp_dialyra_has_calls ? absint(
	$wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wp_dialyra_call_table} WHERE created_at >= %s{$wp_dialyra_business_where}",
			$wp_dialyra_today_start
		)
	)
) : 0;

$wp_dialyra_yesterday_calls = $wp_dialyra_has_calls ? absint(
	$wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wp_dialyra_call_table} WHERE created_at >= %s AND created_at < %s{$wp_dialyra_business_where}",
			date( 'Y-m-d 00:00:00', current_time( 'timestamp' ) - DAY_IN_SECONDS ),
			$wp_dialyra_today_start
		)
	)
) : 0;

$wp_dialyra_today_answered = $wp_dialyra_has_calls ? absint(
	$wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wp_dialyra_call_table} WHERE created_at >= %s AND (status = 'completed' OR call_status = 'answer'){$wp_dialyra_business_where}",
			$wp_dialyra_today_start
		)
	)
) : 0;

$wp_dialyra_today_confirmed = $wp_dialyra_has_calls ? absint(
	$wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wp_dialyra_call_table} WHERE created_at >= %s AND metadata LIKE %s{$wp_dialyra_business_where}",
			$wp_dialyra_today_start,
			'%"order_action":"confirmed"%'
		)
	)
) : 0;

$wp_dialyra_failed_retry_calls = $wp_dialyra_has_calls ? absint(
	$wpdb->get_var(
		"SELECT COUNT(*) FROM {$wp_dialyra_call_table} WHERE status IN ('failed', 'busy', 'no_answer', 'originate_failed'){$wp_dialyra_business_where}"
	)
) : 0;

$wp_dialyra_waiting_queue = $wp_dialyra_has_queue ? absint(
	$wpdb->get_var(
		"SELECT COUNT(*) FROM {$wp_dialyra_queue_table} WHERE status IN ('pending', 'processing'){$wp_dialyra_business_where}"
	)
) : 0;

$wp_dialyra_retry_queue = $wp_dialyra_has_retries ? absint(
	$wpdb->get_var(
		"SELECT COUNT(*) FROM {$wp_dialyra_retry_table} WHERE status IN ('pending', 'scheduled', 'processing'){$wp_dialyra_business_where}"
	)
) : 0;

$wp_dialyra_next_queue_time = $wp_dialyra_has_queue ? $wpdb->get_var(
	"SELECT scheduled_at FROM {$wp_dialyra_queue_table} WHERE status IN ('pending', 'processing'){$wp_dialyra_business_where} ORDER BY scheduled_at ASC, id ASC LIMIT 1"
) : '';

$wp_dialyra_answer_rate  = $wp_dialyra_percent( $wp_dialyra_today_answered, $wp_dialyra_today_calls );
$wp_dialyra_confirm_rate = $wp_dialyra_percent( $wp_dialyra_today_confirmed, $wp_dialyra_today_calls );
$wp_dialyra_call_delta   = $wp_dialyra_today_calls - $wp_dialyra_yesterday_calls;

$wp_dialyra_recent_queue_rows = array();
if ( $wp_dialyra_has_queue ) {
	$wp_dialyra_recent_queue_rows = $wpdb->get_results(
		"SELECT * FROM {$wp_dialyra_queue_table} WHERE status IN ('pending', 'processing'){$wp_dialyra_business_where} ORDER BY scheduled_at ASC, id ASC LIMIT 5",
		ARRAY_A
	);
}

$wp_dialyra_recent_call_rows = array();
if ( $wp_dialyra_has_calls ) {
	$wp_dialyra_recent_call_rows = $wpdb->get_results(
		"SELECT * FROM {$wp_dialyra_call_table} WHERE 1=1{$wp_dialyra_business_where} ORDER BY updated_at DESC, id DESC LIMIT 3",
		ARRAY_A
	);
}

$wp_dialyra_bar_counts = array();
if ( $wp_dialyra_has_calls ) {
	for ( $day_offset = 6; $day_offset >= 0; $day_offset-- ) {
		$day_start = date( 'Y-m-d 00:00:00', current_time( 'timestamp' ) - ( $day_offset * DAY_IN_SECONDS ) );
		$day_end   = date( 'Y-m-d 00:00:00', strtotime( $day_start ) + DAY_IN_SECONDS );
		$count     = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wp_dialyra_call_table} WHERE created_at >= %s AND created_at < %s{$wp_dialyra_business_where}",
					$day_start,
					$day_end
				)
			)
		);

		$wp_dialyra_bar_counts[] = $count;
	}
}
$wp_dialyra_max_bar_count = max( 1, ! empty( $wp_dialyra_bar_counts ) ? max( $wp_dialyra_bar_counts ) : 1 );

$wp_dialyra_agents      = array();
$wp_dialyra_departments = array();

if ( $wp_dialyra_api_endpoints && $wp_dialyra_business_id ) {
	$agents_response = $wp_dialyra_api_endpoints->get_agents( array( 'business_id' => $wp_dialyra_business_id ) );
	if ( $agents_response && $agents_response->is_successful() ) {
		$wp_dialyra_agents = array_slice( array_map( $wp_dialyra_normalize_agent, $wp_dialyra_extract_items( $agents_response ) ), 0, 2 );
	}

	$departments_response = $wp_dialyra_api_endpoints->get_departments( array( 'business_id' => $wp_dialyra_business_id ) );
	if ( $departments_response && $departments_response->is_successful() ) {
		$wp_dialyra_departments = array_slice( array_map( $wp_dialyra_normalize_department, $wp_dialyra_extract_items( $departments_response ) ), 0, 2 );
	}
}

$wp_dialyra_default_flow_id   = $wp_dialyra_flow_manager ? $wp_dialyra_flow_manager->get_default_flow_id() : 0;
$wp_dialyra_default_flow_data = $wp_dialyra_flow_manager ? $wp_dialyra_flow_manager->get_default_flow_data() : array();
$wp_dialyra_default_flow_name = ! empty( $wp_dialyra_default_flow_data['name'] ) ? sanitize_text_field( $wp_dialyra_default_flow_data['name'] ) : '';

$wp_dialyra_balance_data = $wp_dialyra_business_manager && method_exists( $wp_dialyra_business_manager, 'get_balance_data' ) ? $wp_dialyra_business_manager->get_balance_data() : array();

if ( empty( $wp_dialyra_balance_data ) ) {
	$wp_dialyra_load_balance_hook = class_exists( 'Dialyra_Hook_Names' ) ? Dialyra_Hook_Names::get_or_default( 'business', 'balance_load_requested', 'wp_dialyra_load_balance' ) : 'wp_dialyra_load_balance';

	do_action( $wp_dialyra_load_balance_hook );

	$wp_dialyra_balance_data = $wp_dialyra_business_manager && method_exists( $wp_dialyra_business_manager, 'get_balance_data' ) ? $wp_dialyra_business_manager->get_balance_data() : array();
}

if ( ! $wp_dialyra_default_flow_name && $wp_dialyra_default_flow_id ) {
	$wp_dialyra_default_flow_name = sprintf(
		/* translators: %d: flow id. */
		__( 'Flow #%d', 'wp-dialyra' ),
		$wp_dialyra_default_flow_id
	);
}

$wp_dialyra_trigger_mode = defined( 'WP_DIALYRA_OPTION_CALL_TRIGGER_MODE' ) ? sanitize_key( get_option( WP_DIALYRA_OPTION_CALL_TRIGGER_MODE, '' ) ) : '';

if ( ! $wp_dialyra_trigger_mode ) {
	$wp_dialyra_setup_settings   = defined( 'WP_DIALYRA_OPTION_SETUP_SETTINGS' ) ? get_option( WP_DIALYRA_OPTION_SETUP_SETTINGS, array() ) : array();
	$wp_dialyra_trigger_settings = is_array( $wp_dialyra_setup_settings ) && isset( $wp_dialyra_setup_settings['call_trigger'] ) && is_array( $wp_dialyra_setup_settings['call_trigger'] ) ? $wp_dialyra_setup_settings['call_trigger'] : array();
	$wp_dialyra_trigger_mode     = sanitize_key( $wp_dialyra_trigger_settings['mode'] ?? '' );
}

if ( ! $wp_dialyra_trigger_mode ) {
	$wp_dialyra_trigger_mode = defined( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_MODE' ) ? WP_DIALYRA_DEFAULT_CALL_TRIGGER_MODE : 'instant';
}
?>

<section class="wp-dialyra-dashboard">
	<div class="wp-dialyra-dashboard__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Control Room', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Monitor every WooCommerce call from one calm command center.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Track active calls, queued orders, retries, balance, and customer confirmations as Dialyra automates your order follow-up workflow.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-dashboard__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>"><?php esc_html_e( 'Settings', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=test-tools' ) ); ?>"><?php esc_html_e( 'Test Call', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<?php if ( $wp_dialyra_dashboard_notice ) : ?>
		<div class="wp-dialyra-notice wp-dialyra-notice--success">
			<?php echo esc_html( $wp_dialyra_dashboard_notice ); ?>
		</div>
	<?php endif; ?>

	<div class="wp-dialyra-dashboard__stats" aria-label="<?php esc_attr_e( 'Dialyra dashboard summary', 'wp-dialyra' ); ?>">
		<a class="wp-dialyra-stat wp-dialyra-stat--blue" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>">
			<span><?php esc_html_e( 'Today’s Calls', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $wp_dialyra_today_calls ) ); ?></strong>
			<small>
				<?php
				if ( $wp_dialyra_call_delta > 0 ) {
					echo esc_html( sprintf( /* translators: %d: call delta. */ __( '+%d from yesterday', 'wp-dialyra' ), $wp_dialyra_call_delta ) );
				} elseif ( $wp_dialyra_call_delta < 0 ) {
					echo esc_html( sprintf( /* translators: %d: call delta. */ __( '%d from yesterday', 'wp-dialyra' ), $wp_dialyra_call_delta ) );
				} else {
					esc_html_e( 'Same as yesterday', 'wp-dialyra' );
				}
				?>
			</small>
		</a>

		<a class="wp-dialyra-stat wp-dialyra-stat--mint" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>">
			<span><?php esc_html_e( 'Confirmed Orders', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $wp_dialyra_today_confirmed ) ); ?></strong>
			<small><?php echo esc_html( sprintf( /* translators: %d: percent. */ __( '%d%% confirmation rate today', 'wp-dialyra' ), $wp_dialyra_confirm_rate ) ); ?></small>
		</a>

		<a class="wp-dialyra-stat wp-dialyra-stat--gold" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=queue-calls' ) ); ?>">
			<span><?php esc_html_e( 'Waiting Queue', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $wp_dialyra_waiting_queue ) ); ?></strong>
			<small>
				<?php
				echo $wp_dialyra_next_queue_time
					? esc_html( sprintf( /* translators: %s: human time. */ __( 'Next slot %s', 'wp-dialyra' ), $wp_dialyra_human_interval( $wp_dialyra_next_queue_time ) ) )
					: esc_html__( 'No waiting calls', 'wp-dialyra' );
				?>
			</small>
		</a>

		<a class="wp-dialyra-stat wp-dialyra-stat--rose" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=queue-calls' ) ); ?>">
			<span><?php esc_html_e( 'Failed / Retry', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $wp_dialyra_failed_retry_calls ) ); ?></strong>
			<small><?php echo esc_html( sprintf( /* translators: %d: retry queue count. */ _n( '%d retry scheduled', '%d retries scheduled', $wp_dialyra_retry_queue, 'wp-dialyra' ), $wp_dialyra_retry_queue ) ); ?></small>
		</a>
	</div>

	<div class="wp-dialyra-dashboard__grid">
		<section class="wp-dialyra-panel wp-dialyra-panel--wide">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Automation Flow', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Order calling pipeline', 'wp-dialyra' ); ?></h3>
				</div>
				<a class="wp-dialyra-chip <?php echo $wp_dialyra_business_id ? 'wp-dialyra-chip--active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flows' ) ); ?>"><?php echo esc_html( $wp_dialyra_business_id ? __( 'Running', 'wp-dialyra' ) : __( 'Setup needed', 'wp-dialyra' ) ); ?></a>
			</div>

			<div class="wp-dialyra-flow">
				<div class="wp-dialyra-flow__step">
					<span>01</span>
					<strong><?php esc_html_e( 'New Order', 'wp-dialyra' ); ?></strong>
					<small><?php echo esc_html( sprintf( /* translators: %s: mode. */ __( '%s trigger', 'wp-dialyra' ), ucwords( str_replace( '_', ' ', $wp_dialyra_trigger_mode ) ) ) ); ?></small>
				</div>
				<div class="wp-dialyra-flow__step">
					<span>02</span>
					<strong><?php esc_html_e( 'Rule Check', 'wp-dialyra' ); ?></strong>
					<small><?php esc_html_e( 'Status + hours', 'wp-dialyra' ); ?></small>
				</div>
				<div class="wp-dialyra-flow__step">
					<span>03</span>
					<strong><?php esc_html_e( 'Dialyra Call', 'wp-dialyra' ); ?></strong>
					<small><?php echo esc_html( $wp_dialyra_default_flow_name ? $wp_dialyra_default_flow_name : __( 'No default flow selected', 'wp-dialyra' ) ); ?></small>
				</div>
				<div class="wp-dialyra-flow__step">
					<span>04</span>
					<strong><?php esc_html_e( 'Webhook Result', 'wp-dialyra' ); ?></strong>
					<small><?php esc_html_e( 'Update order', 'wp-dialyra' ); ?></small>
				</div>
			</div>
		</section>

		<section class="wp-dialyra-panel wp-dialyra-balance">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Balance', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Call credit', 'wp-dialyra' ); ?></h3>
				</div>
				<form method="post" class="wp-dialyra-balance-reload-form">
					<?php wp_nonce_field( 'wp-dialyra-dashboard', 'wp_dialyra_dashboard_nonce' ); ?>
					<input type="hidden" name="wp_dialyra_dashboard_action" value="reload_balance">
					<button class="wp-dialyra-icon-button wp-dialyra-balance-reload-button" type="submit" title="<?php esc_attr_e( 'Reload balance', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Reload balance from Dialyra', 'wp-dialyra' ); ?>">
						<span class="dashicons dashicons-update" aria-hidden="true"></span>
					</button>
				</form>
			</div>
			<?php if ( ! empty( $wp_dialyra_balance_data ) ) : ?>
				<?php
				$wp_dialyra_balance_amount = (float) ( $wp_dialyra_balance_data['available_credit'] ?? ( $wp_dialyra_balance_data['amount'] ?? 0 ) );
				$wp_dialyra_balance_width  = min( 100, max( 4, round( ( $wp_dialyra_balance_amount / 1000 ) * 100 ) ) );
				?>
				<strong><?php echo esc_html( $wp_dialyra_format_balance( $wp_dialyra_balance_amount, $wp_dialyra_balance_data['currency'] ?? 'BDT' ) ); ?></strong>
				<div class="wp-dialyra-meter" aria-hidden="true"><span style="width: <?php echo esc_attr( $wp_dialyra_balance_width ); ?>%;"></span></div>
				<p>
					<?php
					$wp_dialyra_wallet_balance = (float) ( $wp_dialyra_balance_data['balance'] ?? ( $wp_dialyra_balance_data['amount'] ?? $wp_dialyra_balance_amount ) );
					$wp_dialyra_loaded_at      = ! empty( $wp_dialyra_balance_data['loaded_at'] ) ? strtotime( $wp_dialyra_balance_data['loaded_at'] ) : 0;

					echo esc_html(
						sprintf(
							/* translators: 1: wallet balance, 2: balance load time. */
							__( 'Wallet balance %1$s · loaded %2$s.', 'wp-dialyra' ),
							$wp_dialyra_format_balance( $wp_dialyra_wallet_balance, $wp_dialyra_balance_data['currency'] ?? 'BDT' ),
							$wp_dialyra_loaded_at ? human_time_diff( $wp_dialyra_loaded_at, current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'wp-dialyra' ) : __( 'from Dialyra', 'wp-dialyra' )
						)
					);
					?>
				</p>
			<?php else : ?>
				<strong><?php esc_html_e( 'Not loaded', 'wp-dialyra' ); ?></strong>
				<div class="wp-dialyra-meter" aria-hidden="true"><span style="width: 4%;"></span></div>
				<p><?php esc_html_e( 'Balance will appear after Dialyra returns wallet data.', 'wp-dialyra' ); ?></p>
			<?php endif; ?>
		</section>

		<section class="wp-dialyra-panel">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Live Queue', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Calls waiting', 'wp-dialyra' ); ?></h3>
				</div>
				<a class="wp-dialyra-chip" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=queue-calls' ) ); ?>"><?php esc_html_e( 'FIFO', 'wp-dialyra' ); ?></a>
			</div>

			<?php if ( ! empty( $wp_dialyra_recent_queue_rows ) ) : ?>
				<ul class="wp-dialyra-queue">
					<?php foreach ( $wp_dialyra_recent_queue_rows as $queue_row ) : ?>
						<?php $order_details = $wp_dialyra_get_order_details( $queue_row['order_id'] ?? 0 ); ?>
						<li>
							<strong>#<?php echo esc_html( absint( $queue_row['order_id'] ?? 0 ) ); ?></strong>
							<span><?php echo esc_html( $order_details['status'] ); ?></span>
							<em><?php echo esc_html( $wp_dialyra_human_interval( $queue_row['scheduled_at'] ?? '' ) ); ?></em>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div class="wp-dialyra-empty-card">
					<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
					<strong><?php esc_html_e( 'Queue is clear', 'wp-dialyra' ); ?></strong>
					<p><?php esc_html_e( 'No waiting calls are currently pending.', 'wp-dialyra' ); ?></p>
				</div>
			<?php endif; ?>
		</section>

		<section class="wp-dialyra-panel">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Performance', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Call outcomes', 'wp-dialyra' ); ?></h3>
				</div>
				<a class="wp-dialyra-chip" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>"><?php esc_html_e( 'View all', 'wp-dialyra' ); ?></a>
			</div>

			<div class="wp-dialyra-rings">
				<div>
					<strong><?php echo esc_html( $wp_dialyra_answer_rate ); ?>%</strong>
					<span><?php esc_html_e( 'Answer rate', 'wp-dialyra' ); ?></span>
				</div>
				<div>
					<strong><?php echo esc_html( $wp_dialyra_confirm_rate ); ?>%</strong>
					<span><?php esc_html_e( 'Confirmed', 'wp-dialyra' ); ?></span>
				</div>
			</div>

			<div class="wp-dialyra-bars">
				<?php if ( ! empty( $wp_dialyra_bar_counts ) ) : ?>
					<?php foreach ( $wp_dialyra_bar_counts as $bar_count ) : ?>
						<span style="height: <?php echo esc_attr( max( 14, round( ( $bar_count / $wp_dialyra_max_bar_count ) * 100 ) ) ); ?>%;"></span>
					<?php endforeach; ?>
				<?php else : ?>
					<?php foreach ( array( 14, 14, 14, 14, 14, 14, 14 ) as $bar_height ) : ?>
						<span style="height: <?php echo esc_attr( $bar_height ); ?>%;"></span>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

		<section class="wp-dialyra-panel wp-dialyra-panel--tall">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Departments', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Routing groups', 'wp-dialyra' ); ?></h3>
				</div>
				<a class="wp-dialyra-chip" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=departments' ) ); ?>"><?php esc_html_e( 'View all', 'wp-dialyra' ); ?></a>
			</div>

			<?php if ( ! empty( $wp_dialyra_departments ) ) : ?>
				<?php foreach ( $wp_dialyra_departments as $department ) : ?>
					<div class="wp-dialyra-department-card">
						<div class="wp-dialyra-department-card__top">
							<div>
								<h4><?php echo esc_html( $department['name'] ); ?></h4>
								<p><?php echo esc_html( $department['description'] ? $department['description'] : __( 'No description added yet.', 'wp-dialyra' ) ); ?></p>
							</div>
							<em class="wp-dialyra-result <?php echo esc_attr( $wp_dialyra_result_class( $department['status'] ) ); ?>"><?php echo esc_html( $wp_dialyra_status_label( $department['status'] ) ); ?></em>
						</div>

						<dl class="wp-dialyra-detail-list">
							<div>
								<dt><?php esc_html_e( 'Strategy', 'wp-dialyra' ); ?></dt>
								<dd><code><?php echo esc_html( $department['strategy'] ); ?></code></dd>
							</div>
							<div>
								<dt><?php esc_html_e( 'Availability', 'wp-dialyra' ); ?></dt>
								<dd><span class="wp-dialyra-tag"><?php echo esc_html( $department['availability_status'] ? $department['availability_status'] : __( 'unknown', 'wp-dialyra' ) ); ?></span></dd>
							</div>
							<div>
								<dt><?php esc_html_e( 'Created', 'wp-dialyra' ); ?></dt>
								<dd><?php echo esc_html( $wp_dialyra_format_datetime( $department['created_at'] ) ); ?></dd>
							</div>
							<div>
								<dt><?php esc_html_e( 'Updated', 'wp-dialyra' ); ?></dt>
								<dd><?php echo esc_html( $wp_dialyra_format_datetime( $department['updated_at'] ) ); ?></dd>
							</div>
						</dl>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="wp-dialyra-empty-card">
					<span class="dashicons dashicons-groups" aria-hidden="true"></span>
					<strong><?php esc_html_e( 'No departments yet', 'wp-dialyra' ); ?></strong>
					<p><?php esc_html_e( 'Create routing groups to transfer calls to teams.', 'wp-dialyra' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=departments' ) ); ?>"><?php esc_html_e( 'Open departments', 'wp-dialyra' ); ?></a>
				</div>
			<?php endif; ?>
		</section>

		<section class="wp-dialyra-panel wp-dialyra-panel--wide">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Recent Activity', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Latest call history', 'wp-dialyra' ); ?></h3>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>"><?php esc_html_e( 'View all', 'wp-dialyra' ); ?></a>
			</div>

			<div class="wp-dialyra-table" role="table" aria-label="<?php esc_attr_e( 'Latest Dialyra calls', 'wp-dialyra' ); ?>">
				<div role="row">
					<span role="columnheader"><?php esc_html_e( 'Order', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Customer', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Flow', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Result', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Cost', 'wp-dialyra' ); ?></span>
				</div>
				<?php if ( ! empty( $wp_dialyra_recent_call_rows ) ) : ?>
					<?php foreach ( $wp_dialyra_recent_call_rows as $call_row ) : ?>
						<?php
						$order_details = $wp_dialyra_get_order_details( $call_row['order_id'] ?? 0 );
						$flow_label     = ! empty( $call_row['flow_id'] ) ? sprintf( /* translators: %d: flow ID. */ __( 'Flow #%d', 'wp-dialyra' ), absint( $call_row['flow_id'] ) ) : __( 'Default flow', 'wp-dialyra' );
						$status         = sanitize_key( $call_row['status'] ?? '' );
						?>
						<div role="row">
							<span role="cell">
								<?php if ( ! empty( $call_row['order_id'] ) ) : ?>
									<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $call_row['order_id'] ) . '&action=edit' ) ); ?>">#<?php echo esc_html( absint( $call_row['order_id'] ) ); ?></a>
								<?php else : ?>
									<?php esc_html_e( '—', 'wp-dialyra' ); ?>
								<?php endif; ?>
							</span>
							<span role="cell"><?php echo esc_html( $order_details['name'] ); ?></span>
							<span role="cell"><?php echo esc_html( $flow_label ); ?></span>
							<span role="cell"><em class="wp-dialyra-result <?php echo esc_attr( $wp_dialyra_result_class( $status ) ); ?>"><?php echo esc_html( $wp_dialyra_status_label( $status ) ); ?></em></span>
							<span role="cell"><?php echo esc_html( $wp_dialyra_money( $call_row['billing_charged_amount'] ?? 0 ) ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div role="row">
						<span role="cell">—</span>
						<span role="cell"><?php esc_html_e( 'No local call history yet.', 'wp-dialyra' ); ?></span>
						<span role="cell">—</span>
						<span role="cell"><em class="wp-dialyra-result wp-dialyra-result--muted"><?php esc_html_e( 'Empty', 'wp-dialyra' ); ?></em></span>
						<span role="cell">—</span>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<section class="wp-dialyra-panel wp-dialyra-panel--wide">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Agents', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Support agent directory', 'wp-dialyra' ); ?></h3>
				</div>
				<a class="wp-dialyra-chip" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=agents' ) ); ?>"><?php esc_html_e( 'View all', 'wp-dialyra' ); ?></a>
			</div>

			<div class="wp-dialyra-data-table wp-dialyra-data-table--agents" role="table" aria-label="<?php esc_attr_e( 'Dialyra agents', 'wp-dialyra' ); ?>">
				<div role="row">
					<span role="columnheader"><?php esc_html_e( 'Agent', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Contact', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'SIP', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Calls', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Skills', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Metadata', 'wp-dialyra' ); ?></span>
				</div>
				<?php if ( ! empty( $wp_dialyra_agents ) ) : ?>
					<?php foreach ( $wp_dialyra_agents as $agent ) : ?>
						<div role="row">
							<span role="cell">
								<strong><?php echo esc_html( $agent['name'] ); ?></strong>
								<small><?php echo esc_html( $agent['email'] ? $agent['email'] : __( 'No email', 'wp-dialyra' ) ); ?></small>
							</span>
							<span role="cell"><?php echo esc_html( $agent['phone'] ? $agent['phone'] : __( '—', 'wp-dialyra' ) ); ?></span>
							<span role="cell"><?php echo $agent['sip_extension'] ? '<code>' . esc_html( $agent['sip_extension'] ) . '</code>' : esc_html__( '—', 'wp-dialyra' ); ?></span>
							<span role="cell">
								<em class="wp-dialyra-result <?php echo esc_attr( $wp_dialyra_result_class( $agent['status'] ) ); ?>"><?php echo esc_html( $wp_dialyra_status_label( $agent['status'] ) ); ?></em>
								<em class="wp-dialyra-result <?php echo esc_attr( $wp_dialyra_result_class( $agent['availability_status'] ) ); ?>"><?php echo esc_html( $wp_dialyra_status_label( $agent['availability_status'] ) ); ?></em>
							</span>
							<span role="cell"><?php echo esc_html( absint( $agent['current_active_calls'] ) . ' / ' . absint( $agent['max_concurrent_calls'] ) ); ?></span>
							<span role="cell">
								<?php if ( ! empty( $agent['languages'] ) ) : ?>
									<?php foreach ( $agent['languages'] as $language ) : ?>
										<span class="wp-dialyra-tag"><?php echo esc_html( $language ); ?></span>
									<?php endforeach; ?>
								<?php else : ?>
									<?php esc_html_e( '—', 'wp-dialyra' ); ?>
								<?php endif; ?>
							</span>
							<span role="cell"><?php echo $agent['team'] ? '<code>' . esc_html( 'team: ' . $agent['team'] ) . '</code>' : esc_html__( '—', 'wp-dialyra' ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div role="row">
						<span role="cell">
							<strong><?php esc_html_e( 'No agents found', 'wp-dialyra' ); ?></strong>
							<small><?php esc_html_e( 'Create agents to receive transferred calls.', 'wp-dialyra' ); ?></small>
						</span>
						<span role="cell">—</span>
						<span role="cell">—</span>
						<span role="cell"><em class="wp-dialyra-result wp-dialyra-result--muted"><?php esc_html_e( 'Empty', 'wp-dialyra' ); ?></em></span>
						<span role="cell">—</span>
						<span role="cell">—</span>
						<span role="cell">—</span>
					</div>
				<?php endif; ?>
			</div>
		</section>
	</div>
</section>
