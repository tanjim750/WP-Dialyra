<?php

/**
 * Settings page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<section class="wp-dialyra-settings">
	<div class="wp-dialyra-settings__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Settings', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Configure how Dialyra calls, retries, and updates WooCommerce orders.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Manage access, automation triggers, call capacity, business hours, webhook security, and order status mapping from one place.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-settings__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<form class="wp-dialyra-settings__grid" method="post" action="#">
		<section class="wp-dialyra-settings-card wp-dialyra-settings-card--wide">
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">01</span>
				<div>
					<h3><?php esc_html_e( 'Access token', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Manage and regenerate the token used for Dialyra API requests.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-access-token"><?php esc_html_e( 'Current access token', 'wp-dialyra' ); ?></label>
				<div class="wp-dialyra-token-field">
					<input id="wp-dialyra-access-token" type="password" value="••••••••••••••••••••••••••••" readonly>
					<button type="button"><?php esc_html_e( 'Regenerate', 'wp-dialyra' ); ?></button>
				</div>
			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Save access token', 'wp-dialyra' ); ?></button>
			</div>
		</section>

		<section class="wp-dialyra-settings-card wp-dialyra-settings-card--wide">
			<div class="wp-dialyra-settings-card__head wp-dialyra-settings-card__head--split">
				<div class="wp-dialyra-settings-card__title">
					<span aria-hidden="true">02</span>
					<div>
						<h3>
							<span class="wp-dialyra-status-dot wp-dialyra-status-dot--active" title="<?php esc_attr_e( 'Business status: active', 'wp-dialyra' ); ?>"></span>
							<?php esc_html_e( 'Business details', 'wp-dialyra' ); ?>
						</h3>
						<p><?php esc_html_e( 'Edit the connected Dialyra business profile used for WooCommerce call automation.', 'wp-dialyra' ); ?></p>
					</div>
				</div>

				<div class="wp-dialyra-settings-row wp-dialyra-business-select">
					<label for="wp-dialyra-business-select"><?php esc_html_e( 'Select business', 'wp-dialyra' ); ?></label>
					<select id="wp-dialyra-business-select" name="dialyra_business_id">
						<option><?php esc_html_e( 'Dialyra Store', 'wp-dialyra' ); ?></option>
						<option><?php esc_html_e( 'Billing Department Demo', 'wp-dialyra' ); ?></option>
						<option><?php esc_html_e( 'Support Operations', 'wp-dialyra' ); ?></option>
					</select>
				</div>
			</div>

			<div class="wp-dialyra-business-grid">
				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-name"><?php esc_html_e( 'Business name', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-name" name="dialyra_business_name" type="text" value="Dialyra Store">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-email"><?php esc_html_e( 'Business email', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-email" name="dialyra_business_email" type="email" value="business@example.com">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-phone"><?php esc_html_e( 'Business phone', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-phone" name="dialyra_business_phone" type="tel" value="+8801XXXXXXXXX">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-timezone"><?php esc_html_e( 'Timezone', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-timezone" name="dialyra_business_timezone" type="text" value="Asia/Dhaka">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-country"><?php esc_html_e( 'Country', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-country" name="dialyra_business_country" type="text" value="Bangladesh">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-logo"><?php esc_html_e( 'Business logo', 'wp-dialyra' ); ?></label>
					<div class="wp-dialyra-image-field">
						<input id="wp-dialyra-business-logo" name="dialyra_business_logo" type="file" accept="image/*">
						<span><?php esc_html_e( 'PNG, JPG, or SVG', 'wp-dialyra' ); ?></span>
					</div>
				</div>
			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Save business', 'wp-dialyra' ); ?></button>
			</div>
		</section>

		<section class="wp-dialyra-settings-card">
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">03</span>
				<div>
					<h3><?php esc_html_e( 'Call trigger mode', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Choose when WooCommerce orders should trigger calls.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-trigger-mode"><?php esc_html_e( 'Trigger mode', 'wp-dialyra' ); ?></label>
				<select id="wp-dialyra-trigger-mode" name="dialyra_trigger_mode">
					<option><?php esc_html_e( 'Instant on new order', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'Delay after order', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'Specific WooCommerce status', 'wp-dialyra' ); ?></option>
				</select>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-trigger-delay"><?php esc_html_e( 'Delay minutes', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-trigger-delay" name="dialyra_trigger_delay" type="number" min="0" value="5">
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-trigger-status"><?php esc_html_e( 'Trigger status', 'wp-dialyra' ); ?></label>
				<select id="wp-dialyra-trigger-status" name="dialyra_trigger_status">
					<option><?php esc_html_e( 'Processing', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'Pending payment', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'On hold', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'Completed', 'wp-dialyra' ); ?></option>
				</select>
			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Save trigger', 'wp-dialyra' ); ?></button>
			</div>
		</section>

		<section class="wp-dialyra-settings-card">
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">04</span>
				<div>
					<h3><?php esc_html_e( 'Flow and capacity', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Select the default flow and concurrent call limit.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-default-flow"><?php esc_html_e( 'Default flow', 'wp-dialyra' ); ?></label>
				<select id="wp-dialyra-default-flow" name="dialyra_default_flow">
					<option><?php esc_html_e( 'Order Confirmation Flow', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'COD Verification Flow', 'wp-dialyra' ); ?></option>
				</select>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-max-concurrent"><?php esc_html_e( 'Max concurrent calls', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-max-concurrent" name="dialyra_max_concurrent_calls" type="number" min="1" value="1">
			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Save flow settings', 'wp-dialyra' ); ?></button>
			</div>
		</section>

		<section class="wp-dialyra-settings-card">
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">05</span>
				<div>
					<h3><?php esc_html_e( 'Retry policy', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Control failed, busy, no-answer, and timeout retries.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-toggle-row">
				<span><?php esc_html_e( 'Enable retry', 'wp-dialyra' ); ?></span>
				<label>
					<input type="checkbox" name="dialyra_retry_enabled" checked>
					<i></i>
				</label>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-retry-delay"><?php esc_html_e( 'Retry delay minutes', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-retry-delay" name="dialyra_retry_delay" type="number" min="1" value="10">
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-retry-limit"><?php esc_html_e( 'Max retry attempts', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-retry-limit" name="dialyra_retry_limit" type="number" min="1" value="3">
			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Save retry policy', 'wp-dialyra' ); ?></button>
			</div>
		</section>

		<section class="wp-dialyra-settings-card">
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">06</span>
				<div>
					<h3><?php esc_html_e( 'Business hours', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Queue calls outside your operating schedule.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-settings-inline">
				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-start-time"><?php esc_html_e( 'Start time', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-start-time" name="dialyra_start_time" type="time" value="09:00">
				</div>
				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-end-time"><?php esc_html_e( 'End time', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-end-time" name="dialyra_end_time" type="time" value="18:00">
				</div>
			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Save business hours', 'wp-dialyra' ); ?></button>
			</div>
		</section>

		<section class="wp-dialyra-settings-card">
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">07</span>
				<div>
					<h3><?php esc_html_e( 'Webhook security', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Configure the webhook secret used to verify callbacks.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-webhook-secret"><?php esc_html_e( 'Webhook secret', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-webhook-secret" name="dialyra_webhook_secret" type="text" value="whsec_demo_secret">
			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Save webhook secret', 'wp-dialyra' ); ?></button>
			</div>
		</section>

		<section class="wp-dialyra-settings-card wp-dialyra-settings-card--wide">
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">08</span>
				<div>
					<h3><?php esc_html_e( 'Order status mapping', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Map Dialyra call outcomes to WooCommerce order statuses.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-status-map">
				<div>
					<span><?php esc_html_e( 'Confirmed', 'wp-dialyra' ); ?></span>
					<strong>→</strong>
					<select name="dialyra_map_confirmed"><option><?php esc_html_e( 'Processing', 'wp-dialyra' ); ?></option></select>
				</div>
				<div>
					<span><?php esc_html_e( 'Cancelled', 'wp-dialyra' ); ?></span>
					<strong>→</strong>
					<select name="dialyra_map_cancelled"><option><?php esc_html_e( 'Cancelled', 'wp-dialyra' ); ?></option></select>
				</div>
				<div>
					<span><?php esc_html_e( 'No Answer', 'wp-dialyra' ); ?></span>
					<strong>→</strong>
					<select name="dialyra_map_no_answer"><option><?php esc_html_e( 'Retry Queue', 'wp-dialyra' ); ?></option></select>
				</div>
				<div>
					<span><?php esc_html_e( 'Failed / Busy', 'wp-dialyra' ); ?></span>
					<strong>→</strong>
					<select name="dialyra_map_failed"><option><?php esc_html_e( 'Retry Queue', 'wp-dialyra' ); ?></option></select>
				</div>
			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Save status mapping', 'wp-dialyra' ); ?></button>
			</div>
		</section>
	</form>
</section>
