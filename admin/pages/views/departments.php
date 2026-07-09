<?php

/**
 * Departments page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<section class="wp-dialyra-departments">
	<div class="wp-dialyra-departments__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Departments', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Manage routing groups, agent bindings, and schedules.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Create business-scoped departments, keep routing strategy visible, bind agents by priority, and configure weekly availability windows.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-departments__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="#wp-dialyra-create-department"><?php esc_html_e( 'Create Department', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<div class="wp-dialyra-departments__grid">
		<section id="wp-dialyra-create-department" class="wp-dialyra-department-panel wp-dialyra-department-panel--wide">
			<div class="wp-dialyra-department-panel__head">
				<span aria-hidden="true">01</span>
				<div>
					<h3><?php esc_html_e( 'Create department', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Scoped to the selected business automatically with only business-facing details shown.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<form class="wp-dialyra-department-form" method="post" action="#">
				<input type="hidden" name="business_id" value="2">

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-department-name"><?php esc_html_e( 'Department name', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-department-name" name="name" type="text" maxlength="128" value="Billing Department">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-department-description"><?php esc_html_e( 'Description', 'wp-dialyra' ); ?></label>
					<textarea id="wp-dialyra-department-description" name="description" rows="4">Billing and payment support</textarea>
				</div>

				<div class="wp-dialyra-department-form__grid">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-department-status"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-department-status" name="status">
							<option><?php esc_html_e( 'active', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'inactive', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'archived', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-department-strategy"><?php esc_html_e( 'Strategy', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-department-strategy" name="strategy">
							<option><?php esc_html_e( 'least_busy', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'round_robin', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'priority', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'random', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'skill_based', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-department-language"><?php esc_html_e( 'Default language', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-department-language" name="metadata[default_language]" type="text" value="bn">
					</div>
				</div>

				<div class="wp-dialyra-department-panel__footer">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Create department', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</section>

		<section class="wp-dialyra-department-panel">
			<div class="wp-dialyra-department-panel__head">
				<span aria-hidden="true">02</span>
				<div>
					<h3><?php esc_html_e( 'Department list', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'View clean business-facing department details and routing health.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-department-list">
				<article>
					<div>
						<h4><?php esc_html_e( 'Billing Department', 'wp-dialyra' ); ?></h4>
						<p><?php esc_html_e( 'Billing and payment support', 'wp-dialyra' ); ?></p>
					</div>
					<em class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'active', 'wp-dialyra' ); ?></em>
					<dl>
						<div><dt><?php esc_html_e( 'Strategy', 'wp-dialyra' ); ?></dt><dd><code>least_busy</code></dd></div>
						<div><dt><?php esc_html_e( 'Availability', 'wp-dialyra' ); ?></dt><dd><code>open</code></dd></div>
						<div><dt><?php esc_html_e( 'Language', 'wp-dialyra' ); ?></dt><dd><span class="wp-dialyra-tag">bn</span></dd></div>
						<div><dt><?php esc_html_e( 'Updated', 'wp-dialyra' ); ?></dt><dd><?php esc_html_e( '2026-06-04 10:00', 'wp-dialyra' ); ?></dd></div>
					</dl>
				</article>
			</div>
		</section>

		<section class="wp-dialyra-department-panel">
			<div class="wp-dialyra-department-panel__head">
				<span aria-hidden="true">03</span>
				<div>
					<h3><?php esc_html_e( 'Update or delete', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Update allowed department fields or delete when no agents are assigned.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<form class="wp-dialyra-department-form" method="post" action="#">
				<input type="hidden" name="department_id" value="3">

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-edit-department"><?php esc_html_e( 'Selected department', 'wp-dialyra' ); ?></label>
					<select id="wp-dialyra-edit-department" name="selected_department">
						<option><?php esc_html_e( 'Billing Department', 'wp-dialyra' ); ?></option>
					</select>
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-edit-name"><?php esc_html_e( 'Department name', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-edit-name" name="name" type="text" maxlength="128" value="Billing Department">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-edit-description"><?php esc_html_e( 'Description', 'wp-dialyra' ); ?></label>
					<textarea id="wp-dialyra-edit-description" name="description" rows="4">Billing and payment support</textarea>
				</div>

				<div class="wp-dialyra-department-form__grid">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-edit-status"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-edit-status" name="status">
							<option><?php esc_html_e( 'active', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'inactive', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'archived', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-edit-strategy"><?php esc_html_e( 'Strategy', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-edit-strategy" name="strategy">
							<option><?php esc_html_e( 'least_busy', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'round_robin', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'priority', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'random', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'skill_based', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-edit-language"><?php esc_html_e( 'Default language', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-edit-language" name="metadata[default_language]" type="text" value="bn">
					</div>
				</div>

				<div class="wp-dialyra-department-panel__footer wp-dialyra-department-panel__footer--split">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Update department', 'wp-dialyra' ); ?></button>
					<button class="wp-dialyra-button wp-dialyra-button--danger" type="button"><?php esc_html_e( 'Delete department', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</section>

		<section class="wp-dialyra-department-panel">
			<div class="wp-dialyra-department-panel__head">
				<span aria-hidden="true">04</span>
				<div>
					<h3><?php esc_html_e( 'Bind agents', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Add or update department-agent mappings by priority.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<form class="wp-dialyra-department-form" method="post" action="#">
				<input type="hidden" name="department_id" value="3">

				<div class="wp-dialyra-department-form__grid">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-agent-select"><?php esc_html_e( 'Agent', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-agent-select" name="agent_id">
							<option><?php esc_html_e( 'Support Agent 1', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-agent-priority"><?php esc_html_e( 'Priority', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-agent-priority" name="priority" type="number" min="1" value="1">
					</div>
				</div>

				<div class="wp-dialyra-toggle-row">
					<span><?php esc_html_e( 'Mapping active', 'wp-dialyra' ); ?></span>
					<label>
						<input type="checkbox" name="is_active" checked>
						<i></i>
					</label>
				</div>

				<div class="wp-dialyra-agent-mapping">
					<span><?php esc_html_e( 'Current mapping', 'wp-dialyra' ); ?></span>
					<strong><?php esc_html_e( 'Support Agent 1', 'wp-dialyra' ); ?></strong>
					<em><?php esc_html_e( 'Priority 1 · Active', 'wp-dialyra' ); ?></em>
				</div>

				<div class="wp-dialyra-department-panel__footer wp-dialyra-department-panel__footer--split">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Bind agent', 'wp-dialyra' ); ?></button>
					<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button"><?php esc_html_e( 'Remove agent', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</section>

		<section class="wp-dialyra-department-panel wp-dialyra-department-panel--wide">
			<div class="wp-dialyra-department-panel__head">
				<span aria-hidden="true">05</span>
				<div>
					<h3><?php esc_html_e( 'Department schedule', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Set availability mode, timezone, weekly windows, and holiday overrides.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<form class="wp-dialyra-department-form" method="post" action="#">
				<input type="hidden" name="department_id" value="3">

				<div class="wp-dialyra-department-form__grid wp-dialyra-department-form__grid--three">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-availability-mode"><?php esc_html_e( 'Availability mode', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-availability-mode" name="availability_mode">
							<option><?php esc_html_e( 'scheduled', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'always_open', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'closed', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-schedule-timezone"><?php esc_html_e( 'Timezone', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-schedule-timezone" name="timezone" type="text" value="Asia/Dhaka">
					</div>

					<div class="wp-dialyra-toggle-row">
						<span><?php esc_html_e( 'Schedule active', 'wp-dialyra' ); ?></span>
						<label>
							<input type="checkbox" name="is_active" checked>
							<i></i>
						</label>
					</div>
				</div>

				<div class="wp-dialyra-weekly-hours">
					<div><strong><?php esc_html_e( 'Mon', 'wp-dialyra' ); ?></strong><input type="time" value="09:00"><input type="time" value="18:00"></div>
					<div><strong><?php esc_html_e( 'Tue', 'wp-dialyra' ); ?></strong><input type="time" value="09:00"><input type="time" value="18:00"></div>
					<div><strong><?php esc_html_e( 'Wed', 'wp-dialyra' ); ?></strong><input type="time" value="09:00"><input type="time" value="18:00"></div>
					<div><strong><?php esc_html_e( 'Thu', 'wp-dialyra' ); ?></strong><input type="time" value="09:00"><input type="time" value="18:00"></div>
					<div><strong><?php esc_html_e( 'Fri', 'wp-dialyra' ); ?></strong><input type="time" value="09:00"><input type="time" value="17:00"></div>
					<div><strong><?php esc_html_e( 'Sat', 'wp-dialyra' ); ?></strong><span><?php esc_html_e( 'Closed', 'wp-dialyra' ); ?></span></div>
					<div><strong><?php esc_html_e( 'Sun', 'wp-dialyra' ); ?></strong><span><?php esc_html_e( 'Closed', 'wp-dialyra' ); ?></span></div>
				</div>

				<div class="wp-dialyra-schedule-note">
					<span><?php esc_html_e( 'Holiday override sample', 'wp-dialyra' ); ?></span>
					<code>2026-06-16 closed · 2026-06-17 custom 10:00–14:00</code>
				</div>

				<div class="wp-dialyra-department-panel__footer">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Save schedule', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</section>
	</div>
</section>
