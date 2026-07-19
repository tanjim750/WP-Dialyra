<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://triizync.com
 * @since      1.0.0
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages
 */

$wp_dialyra_pages = array(
	'agents'      => 'agents.php',
	'audio'      => 'audio.php',
	'call-history' => 'call-history.php',
	'dashboard' => 'dashboard.php',
	'departments' => 'departments.php',
	'flow-builder' => 'flow-builder.php',
	'flow-preview' => 'flow-preview.php',
	'flows'      => 'flows.php',
	'login'     => 'login.php',
	'queue-calls' => 'queue-calls.php',
	'settings'  => 'settings.php',
	'setup'     => 'setup.php',
	'test-tools' => 'test-tools.php',
);

if ( defined( 'WP_DIALYRA_DEBUG_MODE' ) && WP_DIALYRA_DEBUG_MODE ) {
	$wp_dialyra_pages['logs'] = 'logs.php';
}

$wp_dialyra_current_page = isset( $_GET['p'] ) ? sanitize_key( wp_unslash( $_GET['p'] ) ) : 'dashboard';

if ( ! isset( $wp_dialyra_pages[ $wp_dialyra_current_page ] ) ) {
	$wp_dialyra_current_page = 'dashboard';
}

$wp_dialyra_page_path = plugin_dir_path( __FILE__ ) . 'views/' . $wp_dialyra_pages[ $wp_dialyra_current_page ];
$wp_dialyra_is_setup_complete = class_exists( 'Dialyra_Auth_Manager' ) ? Dialyra_Auth_Manager::is_setup_complete() : false;
$wp_dialyra_is_logged_in = class_exists( 'Dialyra_Auth_Manager' ) ? Dialyra_Auth_Manager::is_logged_in() : false;
$wp_dialyra_webhook_health = class_exists( 'Dialyra_Webhook_Health_Check' ) ? Dialyra_Webhook_Health_Check::get_stored_status() : array();
$wp_dialyra_webhook_health_status = ! empty( $wp_dialyra_webhook_health['status'] ) ? sanitize_key( $wp_dialyra_webhook_health['status'] ) : 'unknown';
$wp_dialyra_webhook_health_label = class_exists( 'Dialyra_Webhook_Health_Check' ) ? Dialyra_Webhook_Health_Check::get_status_label( $wp_dialyra_webhook_health_status ) : __( 'Not checked', 'wp-dialyra' );
$wp_dialyra_webhook_health_checked = ! empty( $wp_dialyra_webhook_health['last_checked_at'] ) ? date_i18n( 'M j, Y g:i A', strtotime( $wp_dialyra_webhook_health['last_checked_at'] ) ) : __( 'Never', 'wp-dialyra' );
$wp_dialyra_is_webhook_healthy = ! empty( $wp_dialyra_webhook_health['healthy'] );
$wp_dialyra_footer_api_status = $wp_dialyra_is_logged_in ? __( 'API health: Connected', 'wp-dialyra' ) : __( 'API health: Not connected', 'wp-dialyra' );
$wp_dialyra_footer_webhook_status = $wp_dialyra_is_setup_complete ? sprintf( /* translators: %s: webhook health status. */ __( 'Webhook: %s', 'wp-dialyra' ), $wp_dialyra_webhook_health_label ) : __( 'Webhook: Waiting setup', 'wp-dialyra' );
$wp_dialyra_footer_api_class = $wp_dialyra_is_logged_in ? 'wp-dialyra-footer-status--ready' : 'wp-dialyra-footer-status--warning';
$wp_dialyra_footer_webhook_class = $wp_dialyra_is_setup_complete && $wp_dialyra_is_webhook_healthy ? 'wp-dialyra-footer-status--ready' : 'wp-dialyra-footer-status--warning';
?>

<div class="wrap wp-dialyra-admin">
	<header class="wp-dialyra-header">
		<div class="wp-dialyra-header__brand">
			<div class="wp-dialyra-header__mark" aria-hidden="true">
				<span></span>
			</div>
			<div>
				<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'WooCommerce Call Automation', 'wp-dialyra' ); ?></p>
				<h1><?php esc_html_e( 'WP Dialyra', 'wp-dialyra' ); ?></h1>
			</div>
		</div>

		<div class="wp-dialyra-header__actions" aria-label="<?php esc_attr_e( 'Dialyra quick status', 'wp-dialyra' ); ?>">
			<?php if ( 'login' !== $wp_dialyra_current_page ) : ?>
				<nav class="wp-dialyra-header__nav" aria-label="<?php esc_attr_e( 'Dialyra primary navigation', 'wp-dialyra' ); ?>">
					<a class="wp-dialyra-nav-link <?php echo 'dashboard' === $wp_dialyra_current_page ? 'wp-dialyra-nav-link--active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=dashboard' ) ); ?>"><?php esc_html_e( 'Dashboard', 'wp-dialyra' ); ?></a>
					<a class="wp-dialyra-nav-link <?php echo in_array( $wp_dialyra_current_page, array( 'flows', 'flow-builder', 'flow-preview' ), true ) ? 'wp-dialyra-nav-link--active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flows' ) ); ?>"><?php esc_html_e( 'Flows', 'wp-dialyra' ); ?></a>
					<a class="wp-dialyra-nav-link <?php echo 'audio' === $wp_dialyra_current_page ? 'wp-dialyra-nav-link--active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=audio' ) ); ?>"><?php esc_html_e( 'Audio', 'wp-dialyra' ); ?></a>
					<a class="wp-dialyra-nav-link <?php echo 'call-history' === $wp_dialyra_current_page ? 'wp-dialyra-nav-link--active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>"><?php esc_html_e( 'Call History', 'wp-dialyra' ); ?></a>
					<a class="wp-dialyra-nav-link <?php echo 'test-tools' === $wp_dialyra_current_page ? 'wp-dialyra-nav-link--active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=test-tools' ) ); ?>"><?php esc_html_e( 'Test Tools', 'wp-dialyra' ); ?></a>
					<a class="wp-dialyra-nav-link <?php echo 'settings' === $wp_dialyra_current_page ? 'wp-dialyra-nav-link--active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>"><?php esc_html_e( 'Settings', 'wp-dialyra' ); ?></a>
				</nav>
			<?php endif; ?>
			<span class="wp-dialyra-status <?php echo $wp_dialyra_is_setup_complete ? 'wp-dialyra-status--ready' : 'wp-dialyra-status--pending'; ?>"><?php echo esc_html( $wp_dialyra_is_setup_complete ? __( 'Setup ready', 'wp-dialyra' ) : __( 'Setup required', 'wp-dialyra' ) ); ?></span>
		</div>
	</header>

	<main class="wp-dialyra-main">
		<?php if ( $wp_dialyra_is_setup_complete && ! $wp_dialyra_is_webhook_healthy && 'login' !== $wp_dialyra_current_page ) : ?>
			<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--warning wp-dialyra-webhook-health-warning">
				<span class="dashicons dashicons-warning" aria-hidden="true"></span>
				<p>
					<strong><?php esc_html_e( 'Dialyra webhook is not reachable.', 'wp-dialyra' ); ?></strong>
					<?php esc_html_e( 'Order confirmation and call-result processing may not work correctly.', 'wp-dialyra' ); ?>
					<?php echo esc_html( sprintf( /* translators: 1: status label, 2: checked date. */ __( 'Status: %1$s. Last checked: %2$s.', 'wp-dialyra' ), $wp_dialyra_webhook_health_label, $wp_dialyra_webhook_health_checked ) ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=test-tools' ) ); ?>"><?php esc_html_e( 'Run Webhook Test', 'wp-dialyra' ); ?></a>
				</p>
			</div>
		<?php endif; ?>
		<?php require $wp_dialyra_page_path; ?>
	</main>

	<footer class="wp-dialyra-footer">
		<div>
			<strong><?php esc_html_e( 'WP Dialyra', 'wp-dialyra' ); ?></strong>
			<span><?php esc_html_e( 'Version 1.0.0', 'wp-dialyra' ); ?></span>
		</div>
		<div class="wp-dialyra-footer__meta">
			<span class="wp-dialyra-footer-status <?php echo esc_attr( $wp_dialyra_footer_api_class ); ?>"><?php echo esc_html( $wp_dialyra_footer_api_status ); ?></span>
			<span class="wp-dialyra-footer-status <?php echo esc_attr( $wp_dialyra_footer_webhook_class ); ?>"><?php echo esc_html( $wp_dialyra_footer_webhook_status ); ?></span>
		</div>
	</footer>
</div>
