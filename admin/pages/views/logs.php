<?php

/**
 * Hidden audit log page for WP Dialyra.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'WP_DIALYRA_DEBUG_MODE' ) || ! WP_DIALYRA_DEBUG_MODE ) {
	wp_die( esc_html__( 'Dialyra debug mode is disabled.', 'wp-dialyra' ) );
}

$wp_dialyra_audit_repository = class_exists( 'Dialyra_Audit_Log_Repository' ) ? new Dialyra_Audit_Log_Repository() : null;
$wp_dialyra_notice           = '';

if ( 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) && isset( $_POST['wp_dialyra_audit_action'] ) && 'clear_logs' === sanitize_key( wp_unslash( $_POST['wp_dialyra_audit_action'] ) ) ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		$wp_dialyra_notice = esc_html__( 'You do not have permission to clear logs.', 'wp-dialyra' );
	} elseif ( ! isset( $_POST['wp_dialyra_clear_logs_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wp_dialyra_clear_logs_nonce'] ) ), 'wp-dialyra-clear-audit-logs' ) ) {
		$wp_dialyra_notice = esc_html__( 'Security check failed. Please reload and try again.', 'wp-dialyra' );
	} elseif ( $wp_dialyra_audit_repository && $wp_dialyra_audit_repository->clear() ) {
		$wp_dialyra_notice = esc_html__( 'Audit logs cleared successfully.', 'wp-dialyra' );
	} else {
		$wp_dialyra_notice = esc_html__( 'Audit logs could not be cleared.', 'wp-dialyra' );
	}
}

$wp_dialyra_level            = isset( $_GET['level'] ) ? sanitize_key( wp_unslash( $_GET['level'] ) ) : '';
$wp_dialyra_source           = isset( $_GET['source'] ) ? sanitize_key( wp_unslash( $_GET['source'] ) ) : '';
$wp_dialyra_event            = isset( $_GET['event'] ) ? sanitize_key( wp_unslash( $_GET['event'] ) ) : '';
$wp_dialyra_order_id         = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
$wp_dialyra_limit            = isset( $_GET['limit'] ) ? absint( $_GET['limit'] ) : 100;
$wp_dialyra_limit            = min( 500, max( 25, $wp_dialyra_limit ) );
$wp_dialyra_logs             = $wp_dialyra_audit_repository ? $wp_dialyra_audit_repository->query(
	array(
		'level'    => $wp_dialyra_level,
		'source'   => $wp_dialyra_source,
		'event'    => $wp_dialyra_event,
		'order_id' => $wp_dialyra_order_id,
		'limit'    => $wp_dialyra_limit,
	)
) : array();
$wp_dialyra_levels           = array( '', 'debug', 'info', 'success', 'warning', 'error' );
$wp_dialyra_sources          = array( '', 'entrypoint', 'trigger', 'originate', 'system' );

$wp_dialyra_format_context = static function ( $context ) {
	$decoded = json_decode( (string) $context, true );

	if ( ! is_array( $decoded ) || empty( $decoded ) ) {
		return '';
	}

	return wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
};
?>

<section class="wp-dialyra-audit">
	<div class="wp-dialyra-audit__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Traceability', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Audit Logs', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Plugin-owned logs for WooCommerce hooks, trigger decisions, and call originate attempts. This page is intentionally hidden from navigation.', 'wp-dialyra' ); ?></p>
		</div>
		<div class="wp-dialyra-audit__side">
			<div class="wp-dialyra-audit__meter">
				<strong><?php echo esc_html( number_format_i18n( count( $wp_dialyra_logs ) ) ); ?></strong>
				<span><?php esc_html_e( 'rows loaded', 'wp-dialyra' ); ?></span>
			</div>
			<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Clear all Dialyra audit logs?', 'wp-dialyra' ) ); ?>');">
				<?php wp_nonce_field( 'wp-dialyra-clear-audit-logs', 'wp_dialyra_clear_logs_nonce' ); ?>
				<input type="hidden" name="wp_dialyra_audit_action" value="clear_logs">
				<button class="wp-dialyra-button wp-dialyra-button--danger" type="submit"><?php esc_html_e( 'Clear Logs', 'wp-dialyra' ); ?></button>
			</form>
		</div>
	</div>

	<?php if ( $wp_dialyra_notice ) : ?>
		<div class="wp-dialyra-audit-notice">
			<span class="dashicons dashicons-info" aria-hidden="true"></span>
			<p><?php echo esc_html( $wp_dialyra_notice ); ?></p>
		</div>
	<?php endif; ?>

	<form class="wp-dialyra-audit-filter" method="get">
		<input type="hidden" name="page" value="wp-dialyra">
		<input type="hidden" name="p" value="logs">

		<label>
			<span><?php esc_html_e( 'Level', 'wp-dialyra' ); ?></span>
			<select name="level">
				<?php foreach ( $wp_dialyra_levels as $wp_dialyra_option ) : ?>
					<option value="<?php echo esc_attr( $wp_dialyra_option ); ?>" <?php selected( $wp_dialyra_level, $wp_dialyra_option ); ?>><?php echo esc_html( $wp_dialyra_option ? ucfirst( $wp_dialyra_option ) : __( 'All levels', 'wp-dialyra' ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<label>
			<span><?php esc_html_e( 'Source', 'wp-dialyra' ); ?></span>
			<select name="source">
				<?php foreach ( $wp_dialyra_sources as $wp_dialyra_option ) : ?>
					<option value="<?php echo esc_attr( $wp_dialyra_option ); ?>" <?php selected( $wp_dialyra_source, $wp_dialyra_option ); ?>><?php echo esc_html( $wp_dialyra_option ? ucfirst( $wp_dialyra_option ) : __( 'All sources', 'wp-dialyra' ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<label>
			<span><?php esc_html_e( 'Event', 'wp-dialyra' ); ?></span>
			<input type="text" name="event" value="<?php echo esc_attr( $wp_dialyra_event ); ?>" placeholder="<?php esc_attr_e( 'originate, blocked, hook...', 'wp-dialyra' ); ?>">
		</label>

		<label>
			<span><?php esc_html_e( 'Order ID', 'wp-dialyra' ); ?></span>
			<input type="number" name="order_id" value="<?php echo $wp_dialyra_order_id ? esc_attr( $wp_dialyra_order_id ) : ''; ?>" min="1" placeholder="1048">
		</label>

		<label>
			<span><?php esc_html_e( 'Limit', 'wp-dialyra' ); ?></span>
			<input type="number" name="limit" value="<?php echo esc_attr( $wp_dialyra_limit ); ?>" min="25" max="500">
		</label>

		<div class="wp-dialyra-audit-filter__actions">
			<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Filter Logs', 'wp-dialyra' ); ?></button>
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=logs' ) ); ?>"><?php esc_html_e( 'Reset', 'wp-dialyra' ); ?></a>
		</div>
	</form>

	<div class="wp-dialyra-audit-table-wrap">
		<?php if ( ! $wp_dialyra_audit_repository || ! Dialyra_Audit_Log_Repository::table_exists() ) : ?>
			<div class="wp-dialyra-empty-state">
				<span class="dashicons dashicons-database" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'Audit log table is not ready', 'wp-dialyra' ); ?></h3>
				<p><?php esc_html_e( 'Reload this page or reactivate the plugin to create the audit table.', 'wp-dialyra' ); ?></p>
			</div>
		<?php elseif ( empty( $wp_dialyra_logs ) ) : ?>
			<div class="wp-dialyra-empty-state">
				<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'No audit logs found', 'wp-dialyra' ); ?></h3>
				<p><?php esc_html_e( 'Create an order or run a Dialyra action, then return here to inspect the trace.', 'wp-dialyra' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-dialyra-audit-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'wp-dialyra' ); ?></th>
						<th><?php esc_html_e( 'Level', 'wp-dialyra' ); ?></th>
						<th><?php esc_html_e( 'Source', 'wp-dialyra' ); ?></th>
						<th><?php esc_html_e( 'Event', 'wp-dialyra' ); ?></th>
						<th><?php esc_html_e( 'Message', 'wp-dialyra' ); ?></th>
						<th><?php esc_html_e( 'Order', 'wp-dialyra' ); ?></th>
						<th><?php esc_html_e( 'Context', 'wp-dialyra' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $wp_dialyra_logs as $wp_dialyra_log ) : ?>
						<?php $wp_dialyra_context = $wp_dialyra_format_context( $wp_dialyra_log['context'] ?? '' ); ?>
						<tr>
							<td><span class="wp-dialyra-audit-time"><?php echo esc_html( mysql2date( 'M j, Y g:i A', $wp_dialyra_log['created_at'] ?? current_time( 'mysql' ) ) ); ?></span></td>
							<td><span class="wp-dialyra-audit-level wp-dialyra-audit-level--<?php echo esc_attr( sanitize_key( $wp_dialyra_log['level'] ?? 'info' ) ); ?>"><?php echo esc_html( ucfirst( sanitize_key( $wp_dialyra_log['level'] ?? 'info' ) ) ); ?></span></td>
							<td><?php echo esc_html( sanitize_key( $wp_dialyra_log['source'] ?? '' ) ); ?></td>
							<td><code><?php echo esc_html( sanitize_key( $wp_dialyra_log['event'] ?? '' ) ); ?></code></td>
							<td><?php echo esc_html( $wp_dialyra_log['message'] ?? '' ); ?></td>
							<td><?php echo ! empty( $wp_dialyra_log['order_id'] ) ? '#' . esc_html( absint( $wp_dialyra_log['order_id'] ) ) : '—'; ?></td>
							<td>
								<?php if ( $wp_dialyra_context ) : ?>
									<details class="wp-dialyra-audit-context">
										<summary><?php esc_html_e( 'View', 'wp-dialyra' ); ?></summary>
										<pre><?php echo esc_html( $wp_dialyra_context ); ?></pre>
									</details>
								<?php else : ?>
									<span>—</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</section>
