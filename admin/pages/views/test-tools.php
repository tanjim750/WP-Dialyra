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
					<p><?php esc_html_e( 'Simulate a Dialyra callback to validate signature handling, event parsing, and order notes.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-webhook-event"><?php esc_html_e( 'Webhook event', 'wp-dialyra' ); ?></label>
				<select id="wp-dialyra-webhook-event" name="dialyra_webhook_event">
					<option><?php esc_html_e( 'Call confirmed', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'Call failed', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'No answer', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'DTMF received', 'wp-dialyra' ); ?></option>
				</select>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-webhook-order"><?php esc_html_e( 'WooCommerce order ID', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-webhook-order" name="dialyra_webhook_order" type="text" value="#1048">
			</div>

			<div class="wp-dialyra-test-payload">
				<span><?php esc_html_e( 'Preview payload', 'wp-dialyra' ); ?></span>
				<code>{"event":"call.confirmed","order_id":"1048","result":"confirmed"}</code>
			</div>

			<div class="wp-dialyra-test-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Send Test Webhook', 'wp-dialyra' ); ?></button>
			</div>
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
					<p><?php esc_html_e( 'The agent must be logged in to a SIP calling app with the extension username and password provided by Dialyra. Apps like Linphone can be used. If the agent is not logged in, their phone cannot ring.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-test-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Originate Agent Call', 'wp-dialyra' ); ?></button>
			</div>
		</section>
	</div>
</section>
