<?php

/**
 * Call queue page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;

$wp_dialyra_plugin                = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
$wp_dialyra_business_id           = class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
$wp_dialyra_queue_repository      = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_call_queue_repository' ) ? $wp_dialyra_plugin->get_call_queue_repository() : ( class_exists( 'Dialyra_Call_Queue_Repository' ) ? new Dialyra_Call_Queue_Repository() : null );
$wp_dialyra_queue_processor       = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_call_queue_processor' ) ? $wp_dialyra_plugin->get_call_queue_processor() : null;
$wp_dialyra_retry_repository      = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_retry_repository' ) ? $wp_dialyra_plugin->get_retry_repository() : ( class_exists( 'Dialyra_Retry_Repository' ) ? new Dialyra_Retry_Repository() : null );
$wp_dialyra_retry_processor       = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_retry_queue_processor' ) ? $wp_dialyra_plugin->get_retry_queue_processor() : null;
$wp_dialyra_call_log_table        = class_exists( 'Dialyra_Call_Log_Repository' ) ? Dialyra_Call_Log_Repository::get_table_name() : $wpdb->prefix . 'dialyra_call_logs';
$wp_dialyra_queue_table           = class_exists( 'Dialyra_Call_Queue_Repository' ) ? Dialyra_Call_Queue_Repository::get_table_name() : $wpdb->prefix . 'dialyra_call_queue';
$wp_dialyra_retry_table           = class_exists( 'Dialyra_Retry_Repository' ) ? Dialyra_Retry_Repository::get_table_name() : $wpdb->prefix . 'dialyra_retry_queue';
$wp_dialyra_notice_success        = '';
$wp_dialyra_notice_error          = '';

$wp_dialyra_table_exists = static function ( $table_name ) use ( $wpdb ) {
	return $table_name && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
};

$wp_dialyra_human_time = static function ( $datetime, $fallback = '' ) {
	$timestamp = $datetime ? strtotime( $datetime ) : 0;

	if ( ! $timestamp ) {
		return $fallback ? $fallback : __( '—', 'wp-dialyra' );
	}

	return human_time_diff( $timestamp, current_time( 'timestamp' ) );
};

$wp_dialyra_status_label = static function ( $status ) {
	$status = sanitize_key( $status );

	return $status ? ucwords( str_replace( '_', ' ', $status ) ) : __( 'Unknown', 'wp-dialyra' );
};

$wp_dialyra_result_class = static function ( $status ) {
	$status = sanitize_key( $status );

	if ( in_array( $status, array( 'completed', 'ready', 'active' ), true ) ) {
		return 'wp-dialyra-result--success';
	}

	if ( in_array( $status, array( 'pending', 'scheduled', 'processing', 'concurrency', 'business_hours', 'payment_required', 'unauthorized', 'invalid_flow', 'originate_error' ), true ) ) {
		return 'wp-dialyra-result--warning';
	}

	if ( in_array( $status, array( 'cancelled', 'failed', 'exhausted' ), true ) ) {
		return 'wp-dialyra-result--danger';
	}

	return 'wp-dialyra-result--muted';
};

$wp_dialyra_get_order_details = static function ( $order_id ) {
	$order_id = absint( $order_id );

	if ( ! $order_id || ! function_exists( 'wc_get_order' ) ) {
		return array(
			'name'   => __( 'Unknown customer', 'wp-dialyra' ),
			'phone'  => '',
			'status' => __( 'No order link', 'wp-dialyra' ),
			'url'    => '',
		);
	}

	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return array(
			'name'   => __( 'Unknown customer', 'wp-dialyra' ),
			'phone'  => '',
			'status' => __( 'Order not found', 'wp-dialyra' ),
			'url'    => '',
		);
	}

	$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

	return array(
		'name'   => $name ? $name : __( 'Guest customer', 'wp-dialyra' ),
		'phone'  => $order->get_billing_phone(),
		'status' => function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $order->get_status() ) : $order->get_status(),
		'url'    => admin_url( 'post.php?post=' . $order_id . '&action=edit' ),
	);
};

$wp_dialyra_outcome_message = static function ( $outcome, $type ) {
	$outcome = sanitize_key( $outcome );

	$messages = array(
		'queue' => array(
			'completed'   => __( 'Queued call started successfully.', 'wp-dialyra' ),
			'deferred'    => __( 'Queued call was deferred because it is not ready yet.', 'wp-dialyra' ),
			'cancelled'   => __( 'Queued call was cancelled by eligibility rules.', 'wp-dialyra' ),
			'failed'      => __( 'Queued call could not be processed.', 'wp-dialyra' ),
			'not_found'   => __( 'Queued call was not found or is no longer pending.', 'wp-dialyra' ),
			'not_claimed' => __( 'Queued call is already being processed.', 'wp-dialyra' ),
		),
		'retry' => array(
			'completed'   => __( 'Retry call started successfully.', 'wp-dialyra' ),
			'scheduled'   => __( 'Retry was rescheduled because it is not ready yet.', 'wp-dialyra' ),
			'cancelled'   => __( 'Retry was cancelled by eligibility rules.', 'wp-dialyra' ),
			'exhausted'   => __( 'Retry limit has been reached.', 'wp-dialyra' ),
			'failed'      => __( 'Retry attempt failed and was scheduled again.', 'wp-dialyra' ),
			'not_found'   => __( 'Retry item was not found or is no longer pending.', 'wp-dialyra' ),
			'not_claimed' => __( 'Retry item is already being processed.', 'wp-dialyra' ),
		),
	);

	return $messages[ $type ][ $outcome ] ?? __( 'Queue action completed.', 'wp-dialyra' );
};

if ( isset( $_POST['wp_dialyra_queue_action'] ) ) {
	if ( ! isset( $_POST['wp_dialyra_queue_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_dialyra_queue_nonce'] ) ), 'wp-dialyra-queue-actions' ) ) {
		$wp_dialyra_notice_error = __( 'Queue action could not run because the security check failed.', 'wp-dialyra' );
	} else {
		$wp_dialyra_action = sanitize_key( wp_unslash( $_POST['wp_dialyra_queue_action'] ) );
		$wp_dialyra_item_id = isset( $_POST['dialyra_item_id'] ) ? absint( wp_unslash( $_POST['dialyra_item_id'] ) ) : 0;

		if ( 'process_next_queue' === $wp_dialyra_action && $wp_dialyra_queue_processor && method_exists( $wp_dialyra_queue_processor, 'process_due_queue' ) ) {
			$result = $wp_dialyra_queue_processor->process_due_queue( 1 );
			$wp_dialyra_notice_success = sprintf(
				/* translators: 1: completed count, 2: deferred count. */
				__( 'Queue processor ran. Completed: %1$d · Deferred: %2$d.', 'wp-dialyra' ),
				absint( $result['completed'] ?? 0 ),
				absint( $result['deferred'] ?? 0 )
			);
		} elseif ( 'process_queue_item' === $wp_dialyra_action && $wp_dialyra_queue_processor && method_exists( $wp_dialyra_queue_processor, 'process_queue_item' ) ) {
			$outcome = $wp_dialyra_queue_processor->process_queue_item( $wp_dialyra_item_id );
			$wp_dialyra_notice_success = $wp_dialyra_outcome_message( $outcome, 'queue' );
		} elseif ( 'cancel_queue_item' === $wp_dialyra_action && $wp_dialyra_queue_repository && method_exists( $wp_dialyra_queue_repository, 'mark_cancelled' ) ) {
			$wp_dialyra_notice_success = $wp_dialyra_queue_repository->mark_cancelled( $wp_dialyra_item_id ) ? __( 'Queued call cancelled.', 'wp-dialyra' ) : __( 'Queued call could not be cancelled.', 'wp-dialyra' );
		} elseif ( 'process_next_retry' === $wp_dialyra_action && $wp_dialyra_retry_processor && method_exists( $wp_dialyra_retry_processor, 'process_due_queue' ) ) {
			$result = $wp_dialyra_retry_processor->process_due_queue( 1 );
			$wp_dialyra_notice_success = sprintf(
				/* translators: 1: completed count, 2: scheduled count. */
				__( 'Retry processor ran. Completed: %1$d · Scheduled: %2$d.', 'wp-dialyra' ),
				absint( $result['completed'] ?? 0 ),
				absint( $result['scheduled'] ?? 0 )
			);
		} elseif ( 'retry_item' === $wp_dialyra_action && $wp_dialyra_retry_processor && method_exists( $wp_dialyra_retry_processor, 'process_retry_item' ) ) {
			$outcome = $wp_dialyra_retry_processor->process_retry_item( $wp_dialyra_item_id );
			$wp_dialyra_notice_success = $wp_dialyra_outcome_message( $outcome, 'retry' );
		} elseif ( 'cancel_retry_item' === $wp_dialyra_action && $wp_dialyra_retry_repository && method_exists( $wp_dialyra_retry_repository, 'mark_cancelled' ) ) {
			$wp_dialyra_notice_success = $wp_dialyra_retry_repository->mark_cancelled( $wp_dialyra_item_id ) ? __( 'Retry item cancelled.', 'wp-dialyra' ) : __( 'Retry item could not be cancelled.', 'wp-dialyra' );
		} else {
			$wp_dialyra_notice_error = __( 'Queue service is not available for this action.', 'wp-dialyra' );
		}
	}
}

$wp_dialyra_has_queue_table = $wp_dialyra_table_exists( $wp_dialyra_queue_table );
$wp_dialyra_has_retry_table = $wp_dialyra_table_exists( $wp_dialyra_retry_table );
$wp_dialyra_has_call_table  = $wp_dialyra_table_exists( $wp_dialyra_call_log_table );
$wp_dialyra_business_where  = $wp_dialyra_business_id ? $wpdb->prepare( ' AND business_id = %d', $wp_dialyra_business_id ) : '';
$wp_dialyra_now             = current_time( 'mysql' );

$wp_dialyra_waiting_count = $wp_dialyra_has_queue_table ? absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wp_dialyra_queue_table} WHERE status IN ('pending', 'processing'){$wp_dialyra_business_where}" ) ) : 0;
$wp_dialyra_retry_ready_count = $wp_dialyra_has_retry_table ? absint(
	$wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wp_dialyra_retry_table} WHERE status IN ('pending', 'scheduled') AND (scheduled_at IS NULL OR scheduled_at <= %s){$wp_dialyra_business_where}",
			$wp_dialyra_now
		)
	)
) : 0;
$wp_dialyra_scheduled_count = $wp_dialyra_has_retry_table ? absint(
	$wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wp_dialyra_retry_table} WHERE status = 'scheduled' AND scheduled_at > %s{$wp_dialyra_business_where}",
			$wp_dialyra_now
		)
	)
) : 0;

$wp_dialyra_active_calls = $wp_dialyra_has_call_table ? absint(
	$wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wp_dialyra_call_log_table} WHERE status IN ('pending', 'initiated', 'ringing', 'answered') AND updated_at >= %s{$wp_dialyra_business_where}",
			date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( 120 * MINUTE_IN_SECONDS ) )
		)
	)
) : 0;
$wp_dialyra_max_calls = defined( 'WP_DIALYRA_OPTION_MAX_CONCURRENT_CALLS' ) ? max( 1, absint( get_option( WP_DIALYRA_OPTION_MAX_CONCURRENT_CALLS, 1 ) ) ) : 1;
$wp_dialyra_idle_slots = max( 0, $wp_dialyra_max_calls - $wp_dialyra_active_calls );

$wp_dialyra_queue_rows = $wp_dialyra_has_queue_table ? $wpdb->get_results(
	"SELECT * FROM {$wp_dialyra_queue_table} WHERE status IN ('pending', 'processing'){$wp_dialyra_business_where} ORDER BY scheduled_at ASC, id ASC LIMIT 50",
	ARRAY_A
) : array();

$wp_dialyra_retry_rows = $wp_dialyra_has_retry_table ? $wpdb->get_results(
	"SELECT * FROM {$wp_dialyra_retry_table} WHERE status IN ('pending', 'scheduled', 'processing'){$wp_dialyra_business_where} ORDER BY COALESCE(scheduled_at, registered_at, created_at) ASC, id ASC LIMIT 25",
	ARRAY_A
) : array();

$wp_dialyra_next_queue_row = ! empty( $wp_dialyra_queue_rows ) ? $wp_dialyra_queue_rows[0] : array();
$wp_dialyra_next_order_id  = absint( $wp_dialyra_next_queue_row['order_id'] ?? 0 );
?>

<section class="wp-dialyra-call-queue">
	<div class="wp-dialyra-call-queue__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Call Queue', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Control waiting calls, retries, and idle-slot processing.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'See what is waiting, cancel queued calls safely, retry failed attempts manually, and process the next call when capacity opens.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-call-queue__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=queue-calls' ) ); ?>"><?php esc_html_e( 'Refresh queue', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<?php if ( $wp_dialyra_notice_success ) : ?>
		<div class="wp-dialyra-notice wp-dialyra-notice--success"><?php echo esc_html( $wp_dialyra_notice_success ); ?></div>
	<?php endif; ?>

	<?php if ( $wp_dialyra_notice_error ) : ?>
		<div class="wp-dialyra-notice wp-dialyra-notice--error"><?php echo esc_html( $wp_dialyra_notice_error ); ?></div>
	<?php endif; ?>

	<div class="wp-dialyra-queue-strip">
		<div>
			<span><?php esc_html_e( 'Waiting', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $wp_dialyra_waiting_count ) ); ?></strong>
		</div>
		<div>
			<span><?php esc_html_e( 'Retry ready', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $wp_dialyra_retry_ready_count ) ); ?></strong>
		</div>
		<div>
			<span><?php esc_html_e( 'Idle slots', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $wp_dialyra_idle_slots ) ); ?></strong>
		</div>
		<div>
			<span><?php esc_html_e( 'Scheduled', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $wp_dialyra_scheduled_count ) ); ?></strong>
		</div>
	</div>

	<div class="wp-dialyra-call-queue__grid">
		<section id="wp-dialyra-waiting-calls" class="wp-dialyra-queue-panel wp-dialyra-queue-panel--wide">
			<div class="wp-dialyra-queue-panel__head wp-dialyra-queue-panel__head--split">
				<div class="wp-dialyra-queue-panel__title">
					<span aria-hidden="true">01</span>
					<div>
						<h3><?php esc_html_e( 'Waiting calls', 'wp-dialyra' ); ?></h3>
						<p><?php esc_html_e( 'Queued calls ready for the next available automation slot.', 'wp-dialyra' ); ?></p>
					</div>
				</div>
				<form method="post">
					<?php wp_nonce_field( 'wp-dialyra-queue-actions', 'wp_dialyra_queue_nonce' ); ?>
					<input type="hidden" name="wp_dialyra_queue_action" value="process_next_queue">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Process idle slot', 'wp-dialyra' ); ?></button>
				</form>
			</div>

			<div class="wp-dialyra-queue-table" role="table" aria-label="<?php esc_attr_e( 'Waiting calls table', 'wp-dialyra' ); ?>">
				<div role="row">
					<span><?php esc_html_e( 'Order', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Customer', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Phone', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Source', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Queued age', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Schedule', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Actions', 'wp-dialyra' ); ?></span>
				</div>

				<?php if ( empty( $wp_dialyra_queue_rows ) ) : ?>
					<div role="row">
						<span><strong><?php esc_html_e( 'No queued calls', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'Queue is clear', 'wp-dialyra' ); ?></small></span>
						<span>—</span><span>—</span><span>—</span><span>—</span><span>—</span><span>—</span><span>—</span>
					</div>
				<?php else : ?>
					<?php foreach ( $wp_dialyra_queue_rows as $queue_row ) : ?>
						<?php
						$order_id      = absint( $queue_row['order_id'] ?? 0 );
						$order_details = $wp_dialyra_get_order_details( $order_id );
						$source        = sanitize_key( $queue_row['source'] ?? '' );
						$status        = sanitize_key( $queue_row['status'] ?? '' );
						$is_due        = ! empty( $queue_row['scheduled_at'] ) && strtotime( $queue_row['scheduled_at'] ) <= current_time( 'timestamp' );
						?>
						<div role="row">
							<span>
								<?php if ( ! empty( $order_details['url'] ) ) : ?>
									<a href="<?php echo esc_url( $order_details['url'] ); ?>">#<?php echo esc_html( $order_id ); ?></a>
								<?php else : ?>
									<strong>#<?php echo esc_html( $order_id ); ?></strong>
								<?php endif; ?>
								<small><?php echo esc_html( $order_details['status'] ); ?></small>
							</span>
							<span><strong><?php echo esc_html( $order_details['name'] ); ?></strong><small><?php esc_html_e( 'WooCommerce order', 'wp-dialyra' ); ?></small></span>
							<span><strong><?php echo esc_html( $order_details['phone'] ? $order_details['phone'] : __( 'No phone', 'wp-dialyra' ) ); ?></strong><small><?php esc_html_e( 'outbound', 'wp-dialyra' ); ?></small></span>
							<span><code><?php echo esc_html( $wp_dialyra_status_label( $source ) ); ?></code><small><?php esc_html_e( 'queue reason', 'wp-dialyra' ); ?></small></span>
							<span><strong><?php echo esc_html( $wp_dialyra_human_time( $queue_row['created_at'] ?? '' ) ); ?></strong><small><?php esc_html_e( 'in queue', 'wp-dialyra' ); ?></small></span>
							<span><em class="wp-dialyra-result <?php echo esc_attr( $is_due ? 'wp-dialyra-result--success' : 'wp-dialyra-result--warning' ); ?>"><?php echo esc_html( $is_due ? __( 'ready', 'wp-dialyra' ) : __( 'scheduled', 'wp-dialyra' ) ); ?></em></span>
							<span><em class="wp-dialyra-result <?php echo esc_attr( $wp_dialyra_result_class( $status ) ); ?>"><?php echo esc_html( $wp_dialyra_status_label( $status ) ); ?></em></span>
							<span class="wp-dialyra-queue-table__actions">
								<?php if ( 'pending' === $status ) : ?>
									<form method="post">
										<?php wp_nonce_field( 'wp-dialyra-queue-actions', 'wp_dialyra_queue_nonce' ); ?>
										<input type="hidden" name="wp_dialyra_queue_action" value="process_queue_item">
										<input type="hidden" name="dialyra_item_id" value="<?php echo esc_attr( absint( $queue_row['id'] ?? 0 ) ); ?>">
										<button class="wp-dialyra-queue-action wp-dialyra-queue-action--process" type="submit" aria-label="<?php esc_attr_e( 'Process queued call', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Process now', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-controls-play" aria-hidden="true"></span></button>
									</form>
								<?php endif; ?>
								<form method="post">
									<?php wp_nonce_field( 'wp-dialyra-queue-actions', 'wp_dialyra_queue_nonce' ); ?>
									<input type="hidden" name="wp_dialyra_queue_action" value="cancel_queue_item">
									<input type="hidden" name="dialyra_item_id" value="<?php echo esc_attr( absint( $queue_row['id'] ?? 0 ) ); ?>">
									<button class="wp-dialyra-queue-action wp-dialyra-queue-action--cancel" type="submit" aria-label="<?php esc_attr_e( 'Cancel queued call', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Cancel queued call', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span></button>
								</form>
							</span>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

		<section class="wp-dialyra-queue-panel">
			<div class="wp-dialyra-queue-panel__head">
				<span aria-hidden="true">02</span>
				<div>
					<h3><?php esc_html_e( 'Retry queue', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Failed or busy calls waiting for another attempt.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-retry-list">
				<?php if ( empty( $wp_dialyra_retry_rows ) ) : ?>
					<article>
						<div>
							<h4><?php esc_html_e( 'No retry calls', 'wp-dialyra' ); ?></h4>
							<p><?php esc_html_e( 'Retry queue is clear.', 'wp-dialyra' ); ?></p>
						</div>
						<em class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'clear', 'wp-dialyra' ); ?></em>
					</article>
				<?php else : ?>
					<?php foreach ( $wp_dialyra_retry_rows as $retry_row ) : ?>
						<?php
						$order_id      = absint( $retry_row['order_id'] ?? 0 );
						$order_details = $wp_dialyra_get_order_details( $order_id );
						$status        = sanitize_key( $retry_row['status'] ?? '' );
						?>
						<article>
							<div>
								<h4><?php echo esc_html( sprintf( /* translators: %d: order id. */ __( 'Order #%d', 'wp-dialyra' ), $order_id ) ); ?></h4>
								<p><?php echo esc_html( sprintf( /* translators: 1: failure source, 2: scheduled time. */ __( '%1$s · next %2$s', 'wp-dialyra' ), $wp_dialyra_status_label( $retry_row['failure_source'] ?? '' ), ! empty( $retry_row['scheduled_at'] ) ? $wp_dialyra_human_time( $retry_row['scheduled_at'] ) : __( 'ready', 'wp-dialyra' ) ) ); ?></p>
								<p><small><?php echo esc_html( $order_details['name'] ); ?></small></p>
							</div>
							<em class="wp-dialyra-result <?php echo esc_attr( $wp_dialyra_result_class( $status ) ); ?>"><?php echo esc_html( sprintf( /* translators: %d: retry attempt count. */ __( 'attempt %d', 'wp-dialyra' ), absint( $retry_row['attempt_count'] ?? 0 ) + 1 ) ); ?></em>
							<?php if ( in_array( $status, array( 'pending', 'scheduled' ), true ) ) : ?>
								<form method="post">
									<?php wp_nonce_field( 'wp-dialyra-queue-actions', 'wp_dialyra_queue_nonce' ); ?>
									<input type="hidden" name="wp_dialyra_queue_action" value="retry_item">
									<input type="hidden" name="dialyra_item_id" value="<?php echo esc_attr( absint( $retry_row['id'] ?? 0 ) ); ?>">
									<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Retry now', 'wp-dialyra' ); ?></button>
								</form>
							<?php else : ?>
								<em class="wp-dialyra-result wp-dialyra-result--muted"><?php esc_html_e( 'Locked', 'wp-dialyra' ); ?></em>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

		<section class="wp-dialyra-queue-panel">
			<div class="wp-dialyra-queue-panel__head">
				<span aria-hidden="true">03</span>
				<div>
					<h3><?php esc_html_e( 'Slot processor', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Run the next eligible queued call only when concurrency capacity is idle.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-slot-meter">
				<div>
					<span><?php esc_html_e( 'Active calls', 'wp-dialyra' ); ?></span>
					<strong><?php echo esc_html( absint( $wp_dialyra_active_calls ) . ' / ' . absint( $wp_dialyra_max_calls ) ); ?></strong>
				</div>
				<div>
					<span><?php esc_html_e( 'Next call', 'wp-dialyra' ); ?></span>
					<strong><?php echo $wp_dialyra_next_order_id ? esc_html( '#' . $wp_dialyra_next_order_id ) : esc_html__( '—', 'wp-dialyra' ); ?></strong>
				</div>
				<div>
					<span><?php esc_html_e( 'Readiness', 'wp-dialyra' ); ?></span>
					<strong><?php echo esc_html( $wp_dialyra_idle_slots > 0 ? __( 'Idle', 'wp-dialyra' ) : __( 'Busy', 'wp-dialyra' ) ); ?></strong>
				</div>
			</div>

			<div class="wp-dialyra-queue-panel__footer wp-dialyra-queue-panel__footer--split">
				<form method="post">
					<?php wp_nonce_field( 'wp-dialyra-queue-actions', 'wp_dialyra_queue_nonce' ); ?>
					<input type="hidden" name="wp_dialyra_queue_action" value="process_next_queue">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Process next call', 'wp-dialyra' ); ?></button>
				</form>
				<form method="post">
					<?php wp_nonce_field( 'wp-dialyra-queue-actions', 'wp_dialyra_queue_nonce' ); ?>
					<input type="hidden" name="wp_dialyra_queue_action" value="process_next_retry">
					<button class="wp-dialyra-button wp-dialyra-button--ghost" type="submit"><?php esc_html_e( 'Process retry', 'wp-dialyra' ); ?></button>
				</form>
			</div>
		</section>
	</div>
</section>
