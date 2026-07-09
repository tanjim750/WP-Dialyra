<?php

/**
 * Agents page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<section class="wp-dialyra-agents">
	<div class="wp-dialyra-agents__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Agents', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Provision phone identities, manage profiles, and watch live availability.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Keep agent profiles, SIP extensions, department assignments, and realtime readiness in one focused workspace.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-agents__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="#wp-dialyra-create-extension"><?php esc_html_e( 'Create Extension', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<div class="wp-dialyra-agent-strip">
		<div>
			<span><?php esc_html_e( 'Available agents', 'wp-dialyra' ); ?></span>
			<strong>01</strong>
		</div>
		<div>
			<span><?php esc_html_e( 'Bound extensions', 'wp-dialyra' ); ?></span>
			<strong>01</strong>
		</div>
		<div>
			<span><?php esc_html_e( 'At capacity', 'wp-dialyra' ); ?></span>
			<strong>00</strong>
		</div>
		<div>
			<span><?php esc_html_e( 'Offline', 'wp-dialyra' ); ?></span>
			<strong>01</strong>
		</div>
	</div>

	<div class="wp-dialyra-agents__grid">
		<section class="wp-dialyra-agent-panel wp-dialyra-agent-panel--wide">
			<div class="wp-dialyra-agent-panel__head">
				<span aria-hidden="true">01</span>
				<div>
					<h3><?php esc_html_e( 'View agents', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Business-facing directory with contact, extension, status, capacity, and skills.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-agent-directory" role="table" aria-label="<?php esc_attr_e( 'Agent directory', 'wp-dialyra' ); ?>">
				<div role="row">
					<span><?php esc_html_e( 'Agent', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Contact', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Extension', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Capacity', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Skills', 'wp-dialyra' ); ?></span>
				</div>
				<div role="row">
					<span><strong><?php esc_html_e( 'Support Agent 1', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'team: support', 'wp-dialyra' ); ?></small></span>
					<span><strong>agent1@example.com</strong><small>+8801XXXXXXXXX</small></span>
					<span><code>1001</code><small><?php esc_html_e( 'endpoint 00011001', 'wp-dialyra' ); ?></small></span>
					<span><em class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'active', 'wp-dialyra' ); ?></em><em class="wp-dialyra-result wp-dialyra-result--muted"><?php esc_html_e( 'offline', 'wp-dialyra' ); ?></em></span>
					<span><strong>0 / 1</strong><small><?php esc_html_e( 'current / max calls', 'wp-dialyra' ); ?></small></span>
					<span><span class="wp-dialyra-tag">bn</span><span class="wp-dialyra-tag">en</span></span>
				</div>
			</div>
		</section>

		<section id="wp-dialyra-create-extension" class="wp-dialyra-agent-panel">
			<div class="wp-dialyra-agent-panel__head">
				<span aria-hidden="true">02</span>
				<div>
					<h3><?php esc_html_e( 'Create extension', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Provision or update the local realtime SIP endpoint. Username is derived by the system.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<form class="wp-dialyra-agent-form" method="post" action="#">
				<input type="hidden" name="business_id" value="2">
				<input type="hidden" name="user_id" value="21">

				<div class="wp-dialyra-agent-form__grid">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-extension"><?php esc_html_e( 'Extension', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-extension" name="extension" type="text" inputmode="numeric" pattern="[0-9]{2,16}" value="1001">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-extension-password"><?php esc_html_e( 'Password', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-extension-password" name="password" type="password" value="1001pass">
					</div>
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-extension-display"><?php esc_html_e( 'Display name', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-extension-display" name="display_name" type="text" value="Agent 1001">
				</div>

				<div class="wp-dialyra-agent-form__grid">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-extension-transport"><?php esc_html_e( 'Transport', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-extension-transport" name="transport" type="text" value="transport-udp">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-extension-context"><?php esc_html_e( 'Context', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-extension-context" name="context" type="text" value="dialyra-ivr">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-extension-allow"><?php esc_html_e( 'Allowed codecs', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-extension-allow" name="allow" type="text" value="ulaw,alaw">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-extension-dtmf"><?php esc_html_e( 'DTMF mode', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-extension-dtmf" name="dtmf_mode" type="text" value="rfc4733">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-extension-contacts"><?php esc_html_e( 'Max contacts', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-extension-contacts" name="max_contacts" type="number" min="1" value="1">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-extension-qualify"><?php esc_html_e( 'Qualify frequency', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-extension-qualify" name="qualify_frequency" type="number" min="0" value="30">
					</div>
				</div>

				<div class="wp-dialyra-toggle-row">
					<span><?php esc_html_e( 'Remove existing realtime rows first', 'wp-dialyra' ); ?></span>
					<label>
						<input type="checkbox" name="remove_existing" checked>
						<i></i>
					</label>
				</div>

				<div class="wp-dialyra-agent-identity">
					<span><?php esc_html_e( 'Derived SIP identity', 'wp-dialyra' ); ?></span>
					<strong>00011001</strong>
					<em><?php esc_html_e( 'Generated from business prefix + extension; not sent manually.', 'wp-dialyra' ); ?></em>
				</div>

				<div class="wp-dialyra-agent-panel__footer">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Create extension', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</section>

		<section class="wp-dialyra-agent-panel">
			<div class="wp-dialyra-agent-panel__head">
				<span aria-hidden="true">03</span>
				<div>
					<h3><?php esc_html_e( 'Create or update agent', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Edit agent profile fields when the connected account allows profile management.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<form class="wp-dialyra-agent-form" method="post" action="#">
				<input type="hidden" name="business_id" value="2">
				<input type="hidden" name="agent_id" value="10">

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-agent-name"><?php esc_html_e( 'Agent name', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-agent-name" name="name" type="text" value="Support Agent 1">
				</div>

				<div class="wp-dialyra-agent-form__grid">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-agent-email"><?php esc_html_e( 'Email', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-agent-email" name="email" type="email" value="agent1@example.com">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-agent-phone"><?php esc_html_e( 'Phone', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-agent-phone" name="phone" type="tel" value="+8801XXXXXXXXX">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-agent-status"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-agent-status" name="status">
							<option><?php esc_html_e( 'active', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'inactive', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'suspended', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-agent-max-calls"><?php esc_html_e( 'Max concurrent calls', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-agent-max-calls" name="max_concurrent_calls" type="number" min="1" value="1">
					</div>
				</div>

				<div class="wp-dialyra-agent-panel__footer">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Save agent', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</section>

		<section class="wp-dialyra-agent-panel">
			<div class="wp-dialyra-agent-panel__head">
				<span aria-hidden="true">04</span>
				<div>
					<h3><?php esc_html_e( 'Assign to department', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Bind agents to routing groups with priority and active mapping state.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<form class="wp-dialyra-agent-form" method="post" action="#">
				<input type="hidden" name="department_id" value="3">
				<input type="hidden" name="agent_id" value="10">

				<div class="wp-dialyra-agent-form__grid">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-assignment-agent"><?php esc_html_e( 'Agent', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-assignment-agent" name="selected_agent">
							<option><?php esc_html_e( 'Support Agent 1', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-assignment-department"><?php esc_html_e( 'Department', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-assignment-department" name="selected_department">
							<option><?php esc_html_e( 'Billing Department', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-assignment-priority"><?php esc_html_e( 'Priority', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-assignment-priority" name="priority" type="number" min="1" value="1">
					</div>
				</div>

				<div class="wp-dialyra-toggle-row">
					<span><?php esc_html_e( 'Mapping active', 'wp-dialyra' ); ?></span>
					<label>
						<input type="checkbox" name="is_active" checked>
						<i></i>
					</label>
				</div>

				<div class="wp-dialyra-agent-panel__footer wp-dialyra-agent-panel__footer--split">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Assign agent', 'wp-dialyra' ); ?></button>
					<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=departments' ) ); ?>"><?php esc_html_e( 'Open Departments', 'wp-dialyra' ); ?></a>
				</div>
			</form>
		</section>

		<section class="wp-dialyra-agent-panel">
			<div class="wp-dialyra-agent-panel__head">
				<span aria-hidden="true">05</span>
				<div>
					<h3><?php esc_html_e( 'Extension binding', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Bind an existing extension row to a different active agent user or toggle availability.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<form class="wp-dialyra-agent-form" method="post" action="#">
				<input type="hidden" name="extension_assignment_id" value="42">
				<input type="hidden" name="user_id" value="21">

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-extension-row"><?php esc_html_e( 'Extension row', 'wp-dialyra' ); ?></label>
					<select id="wp-dialyra-extension-row" name="selected_extension">
						<option><?php esc_html_e( '1001 · Support Agent 1', 'wp-dialyra' ); ?></option>
					</select>
				</div>

				<div class="wp-dialyra-toggle-row">
					<span><?php esc_html_e( 'Primary extension', 'wp-dialyra' ); ?></span>
					<label>
						<input type="checkbox" name="is_primary" checked>
						<i></i>
					</label>
				</div>

				<div class="wp-dialyra-toggle-row">
					<span><?php esc_html_e( 'Extension active', 'wp-dialyra' ); ?></span>
					<label>
						<input type="checkbox" name="is_active" checked>
						<i></i>
					</label>
				</div>

				<div class="wp-dialyra-agent-panel__footer wp-dialyra-agent-panel__footer--split">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Bind extension', 'wp-dialyra' ); ?></button>
					<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button"><?php esc_html_e( 'Update active state', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</section>

		<section class="wp-dialyra-agent-panel wp-dialyra-agent-panel--wide">
			<div class="wp-dialyra-agent-panel__head">
				<span aria-hidden="true">06</span>
				<div>
					<h3><?php esc_html_e( 'Availability and status', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'View profile state, current load, and readiness signals before routing calls.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-agent-status-board">
				<article>
					<span class="wp-dialyra-status-dot wp-dialyra-status-dot--active"></span>
					<div>
						<h4><?php esc_html_e( 'Support Agent 1', 'wp-dialyra' ); ?></h4>
						<p><?php esc_html_e( 'Active profile · Offline softphone · 0 active calls', 'wp-dialyra' ); ?></p>
					</div>
					<dl>
						<div><dt><?php esc_html_e( 'Availability', 'wp-dialyra' ); ?></dt><dd><code>offline</code></dd></div>
						<div><dt><?php esc_html_e( 'Capacity', 'wp-dialyra' ); ?></dt><dd><?php esc_html_e( 'Ready when registered', 'wp-dialyra' ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Department', 'wp-dialyra' ); ?></dt><dd><?php esc_html_e( 'Billing Department', 'wp-dialyra' ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Primary extension', 'wp-dialyra' ); ?></dt><dd><code>1001</code></dd></div>
					</dl>
				</article>
			</div>
		</section>
	</div>
</section>
