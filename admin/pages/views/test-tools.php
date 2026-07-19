<?php

/**
 * Test tools page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$wp_dialyra_sip_domain = defined( 'WP_DIALYRA_SIP_DOMAIN' ) ? sanitize_text_field( WP_DIALYRA_SIP_DOMAIN ) : 'dialyra.com';
$wp_dialyra_plugin = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
$wp_dialyra_api_endpoints = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_api_endpoints() : null;
$wp_dialyra_webhook_health_state = class_exists( 'Dialyra_Webhook_Health_Check' ) ? Dialyra_Webhook_Health_Check::get_stored_status() : array();
$wp_dialyra_webhook_test_notice = null;
$wp_dialyra_webhook_test_notice_type = 'warning';

if ( isset( $_POST['dialyra_test_action'] ) && 'webhook_health_check' === sanitize_key( wp_unslash( $_POST['dialyra_test_action'] ) ) ) {
	if ( ! isset( $_POST['wp_dialyra_test_tools_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_dialyra_test_tools_nonce'] ) ), 'wp-dialyra-test-tools' ) ) {
		$wp_dialyra_webhook_test_notice = __( 'Webhook test could not run because the security check failed.', 'wp-dialyra' );
		$wp_dialyra_webhook_test_notice_type = 'error';
	} elseif ( $wp_dialyra_api_endpoints && class_exists( 'Dialyra_Webhook_Health_Check' ) ) {
		$wp_dialyra_webhook_health_check = new Dialyra_Webhook_Health_Check( $wp_dialyra_api_endpoints );
		$wp_dialyra_webhook_health_state = $wp_dialyra_webhook_health_check->check();
		$wp_dialyra_webhook_test_notice = ! empty( $wp_dialyra_webhook_health_state['last_error_message'] ) ? $wp_dialyra_webhook_health_state['last_error_message'] : __( 'Webhook health check completed.', 'wp-dialyra' );
		$wp_dialyra_webhook_test_notice_type = ! empty( $wp_dialyra_webhook_health_state['healthy'] ) ? 'success' : 'warning';
	} else {
		$wp_dialyra_webhook_test_notice = __( 'Webhook test could not run because Dialyra API services are not available.', 'wp-dialyra' );
		$wp_dialyra_webhook_test_notice_type = 'error';
	}
}

$wp_dialyra_webhook_status = ! empty( $wp_dialyra_webhook_health_state['status'] ) ? sanitize_key( $wp_dialyra_webhook_health_state['status'] ) : 'unknown';
$wp_dialyra_webhook_status_label = class_exists( 'Dialyra_Webhook_Health_Check' ) ? Dialyra_Webhook_Health_Check::get_status_label( $wp_dialyra_webhook_status ) : __( 'Not checked', 'wp-dialyra' );
$wp_dialyra_webhook_checked_at = ! empty( $wp_dialyra_webhook_health_state['last_checked_at'] ) ? date_i18n( 'M j, Y g:i A', strtotime( $wp_dialyra_webhook_health_state['last_checked_at'] ) ) : __( 'Never', 'wp-dialyra' );
?>

<section class="wp-dialyra-test-tools">
	<div class="wp-dialyra-test-tools__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Test Tools', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Run safe checks before live order automation starts.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Use test calls and webhook simulations to confirm Dialyra connectivity, call flow behavior, and WooCommerce order update handling.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-test-tools__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<div class="wp-dialyra-test-tools__grid">
		<section class="wp-dialyra-test-card">
			<div class="wp-dialyra-test-card__head">
				<span aria-hidden="true">01</span>
				<div>
					<h3><?php esc_html_e( 'Test call', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Place a controlled Dialyra call to verify phone formatting, selected flow, and call initiation.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-test-phone"><?php esc_html_e( 'Customer phone', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-test-phone" name="dialyra_test_phone" type="tel" value="+8801XXXXXXXXX">
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-test-flow"><?php esc_html_e( 'Calling flow', 'wp-dialyra' ); ?></label>
				<select id="wp-dialyra-test-flow" name="dialyra_test_flow">
					<option><?php esc_html_e( 'Order Confirmation Flow', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'COD Verification Flow', 'wp-dialyra' ); ?></option>
				</select>
			</div>

			<div class="wp-dialyra-test-summary">
				<div>
					<span><?php esc_html_e( 'Mode', 'wp-dialyra' ); ?></span>
					<strong><?php esc_html_e( 'Manual test', 'wp-dialyra' ); ?></strong>
				</div>
				<div>
					<span><?php esc_html_e( 'Expected result', 'wp-dialyra' ); ?></span>
					<strong><?php esc_html_e( 'Call queued or started', 'wp-dialyra' ); ?></strong>
				</div>
			</div>

			<div class="wp-dialyra-test-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Run Test Call', 'wp-dialyra' ); ?></button>
			</div>
		</section>

		<section class="wp-dialyra-test-card">
			<div class="wp-dialyra-test-card__head">
				<span aria-hidden="true">02</span>
				<div>
					<h3><?php esc_html_e( 'Test webhook', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Ask Dialyra to deliver a real test webhook to this WordPress site and report what blocked or accepted it.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<?php if ( ! empty( $wp_dialyra_webhook_test_notice ) ) : ?>
				<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--<?php echo esc_attr( $wp_dialyra_webhook_test_notice_type ); ?>">
					<span class="dashicons <?php echo 'success' === $wp_dialyra_webhook_test_notice_type ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
					<p><?php echo esc_html( $wp_dialyra_webhook_test_notice ); ?></p>
				</div>
			<?php endif; ?>

			<div class="wp-dialyra-test-summary wp-dialyra-test-summary--webhook">
				<div>
					<span><?php esc_html_e( 'Webhook URL', 'wp-dialyra' ); ?></span>
					<strong><?php echo esc_html( $wp_dialyra_webhook_health_state['webhook_url'] ?? ( class_exists( 'Dialyra_Webhook_Controller' ) ? Dialyra_Webhook_Controller::get_endpoint_url() : '' ) ); ?></strong>
				</div>
				<div>
					<span><?php esc_html_e( 'Subscription ID', 'wp-dialyra' ); ?></span>
					<strong><?php echo ! empty( $wp_dialyra_webhook_health_state['webhook_id'] ) ? esc_html( '#' . absint( $wp_dialyra_webhook_health_state['webhook_id'] ) ) : esc_html__( 'Not available', 'wp-dialyra' ); ?></strong>
				</div>
				<div>
					<span><?php esc_html_e( 'Health status', 'wp-dialyra' ); ?></span>
					<strong><?php echo esc_html( $wp_dialyra_webhook_status_label ); ?></strong>
				</div>
				<div>
					<span><?php esc_html_e( 'Last checked', 'wp-dialyra' ); ?></span>
					<strong><?php echo esc_html( $wp_dialyra_webhook_checked_at ); ?></strong>
				</div>
			</div>

			<div class="wp-dialyra-test-payload">
				<span><?php esc_html_e( 'Last delivery result', 'wp-dialyra' ); ?></span>
				<code><?php echo esc_html( ! empty( $wp_dialyra_webhook_health_state['last_error_message'] ) ? $wp_dialyra_webhook_health_state['last_error_message'] : __( 'Webhook has not been tested yet.', 'wp-dialyra' ) ); ?></code>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=test-tools' ) ); ?>">
				<?php wp_nonce_field( 'wp-dialyra-test-tools', 'wp_dialyra_test_tools_nonce' ); ?>
				<input type="hidden" name="dialyra_test_action" value="webhook_health_check">
				<div class="wp-dialyra-test-card__footer">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Run Webhook Test', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</section>

		<section class="wp-dialyra-test-card wp-dialyra-test-card--wide">
			<div class="wp-dialyra-test-card__head">
				<span aria-hidden="true">03</span>
				<div>
					<h3><?php esc_html_e( 'Agent call', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Originate a local SIP agent call to another extension or an external number.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-agent-call-grid">
				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-agent-from-extension"><?php esc_html_e( 'From extension', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-agent-from-extension" name="dialyra_agent_from_extension" type="text" value="1003">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-agent-to-type"><?php esc_html_e( 'Target type', 'wp-dialyra' ); ?></label>
					<select id="wp-dialyra-agent-to-type" name="dialyra_agent_to_type">
						<option value="extension"><?php esc_html_e( 'Extension', 'wp-dialyra' ); ?></option>
						<option value="external_number"><?php esc_html_e( 'External number', 'wp-dialyra' ); ?></option>
					</select>
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-agent-to"><?php esc_html_e( 'To', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-agent-to" name="dialyra_agent_to" type="text" value="1004">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-agent-timeout"><?php esc_html_e( 'Timeout seconds', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-agent-timeout" name="dialyra_agent_timeout_seconds" type="number" min="5" value="30">
				</div>
			</div>

			<div class="wp-dialyra-agent-call-manual">
				<div>
					<span><?php esc_html_e( 'How agent calling works', 'wp-dialyra' ); ?></span>
					<p><?php esc_html_e( 'Use this when you want one team member phone to ring first, then connect that agent to another agent or a customer number.', 'wp-dialyra' ); ?></p>
				</div>

				<ol>
					<li><?php esc_html_e( 'Choose the agent phone that should start the call in From extension.', 'wp-dialyra' ); ?></li>
					<li><?php esc_html_e( 'Choose Extension when calling another team member, or External number when calling a customer or outside number.', 'wp-dialyra' ); ?></li>
					<li><?php esc_html_e( 'Enter the destination in To. For another agent use their extension, for a customer use their phone number.', 'wp-dialyra' ); ?></li>
					<li><?php esc_html_e( 'Click Originate Agent Call. Dialyra rings the first agent, then connects the second side after pickup.', 'wp-dialyra' ); ?></li>
				</ol>

				<div class="wp-dialyra-agent-call-examples">
					<strong><?php esc_html_e( 'Examples', 'wp-dialyra' ); ?></strong>
					<span><?php esc_html_e( 'Agent to agent: From 1003 → To 1004', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Agent to customer: From 1004 → To 09617179124', 'wp-dialyra' ); ?></span>
				</div>

				<div class="wp-dialyra-agent-call-requirement">
					<strong><?php esc_html_e( 'Before testing', 'wp-dialyra' ); ?></strong>
					<p>
						<?php
						echo wp_kses(
							sprintf(
								/* translators: %s: SIP domain. */
								esc_html__( 'The agent must be logged in to a SIP calling app such as Linphone with the extension username, password, and domain %s. If the agent is not logged in, their phone cannot ring.', 'wp-dialyra' ),
								'<code>' . esc_html( $wp_dialyra_sip_domain ) . '</code>'
							),
							array(
								'code' => array(),
							)
						);
						?>
					</p>
				</div>
			</div>

			<div class="wp-dialyra-test-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Originate Agent Call', 'wp-dialyra' ); ?></button>
			</div>
		</section>
	</div>
</section>
