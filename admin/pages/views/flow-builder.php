<?php

/**
 * Flow builder page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<section class="wp-dialyra-flow-builder">
	<div class="wp-dialyra-flow-builder__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Flow Builder', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Build a customer-friendly IVR menu with guided choices.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Create menus, collect keypad choices, define order actions, and preview the full call path before publishing.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-flow-builder__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flows' ) ); ?>"><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-preview' ) ); ?>"><?php esc_html_e( 'Preview', 'wp-dialyra' ); ?></a>
			<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button"><?php esc_html_e( 'Save Draft', 'wp-dialyra' ); ?></button>
			<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Publish', 'wp-dialyra' ); ?></button>
		</div>
	</div>

	<div class="wp-dialyra-flow-builder__workspace">
		<aside class="wp-dialyra-flow-sidebar" aria-label="<?php esc_attr_e( 'Flow menus', 'wp-dialyra' ); ?>">
			<section class="wp-dialyra-flow-sidebar__card">
				<div class="wp-dialyra-flow-sidebar__head">
					<span aria-hidden="true">01</span>
					<div>
						<h3><?php esc_html_e( 'Flow info', 'wp-dialyra' ); ?></h3>
						<p><?php esc_html_e( 'Name and describe this call flow.', 'wp-dialyra' ); ?></p>
					</div>
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-flow-name"><?php esc_html_e( 'Flow name', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-flow-name" name="flow_name" type="text" value="Order Confirmation Flow">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-flow-description"><?php esc_html_e( 'Description', 'wp-dialyra' ); ?></label>
					<textarea id="wp-dialyra-flow-description" name="flow_description" rows="4"><?php esc_html_e( 'Confirm orders, answer common questions, and transfer customers when needed.', 'wp-dialyra' ); ?></textarea>
				</div>
			</section>

			<section class="wp-dialyra-flow-sidebar__card">
				<div class="wp-dialyra-flow-sidebar__head wp-dialyra-flow-sidebar__head--split">
					<div class="wp-dialyra-flow-sidebar__title">
						<span aria-hidden="true">02</span>
						<div>
							<h3><?php esc_html_e( 'Menus', 'wp-dialyra' ); ?></h3>
							<p><?php esc_html_e( 'Select, add, rename, delete, or mark a start menu.', 'wp-dialyra' ); ?></p>
						</div>
					</div>
					<button class="wp-dialyra-flow-icon-button wp-dialyra-flow-icon-button--add" type="button" aria-label="<?php esc_attr_e( 'Add menu', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Add menu', 'wp-dialyra' ); ?>">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
					</button>
				</div>

				<div class="wp-dialyra-menu-list">
					<article class="wp-dialyra-menu-list__item wp-dialyra-menu-list__item--selected">
						<button type="button">
							<strong><?php esc_html_e( 'Main Menu', 'wp-dialyra' ); ?></strong>
							<small><?php esc_html_e( 'Start menu', 'wp-dialyra' ); ?></small>
						</button>
						<em class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'Valid', 'wp-dialyra' ); ?></em>
						<div class="wp-dialyra-menu-list__actions">
							<button class="wp-dialyra-flow-icon-button" type="button" aria-label="<?php esc_attr_e( 'Rename Main Menu', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Rename menu', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>
							<button class="wp-dialyra-flow-icon-button wp-dialyra-flow-icon-button--start" type="button" aria-label="<?php esc_attr_e( 'Main Menu is the start menu', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Start menu', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-star-filled" aria-hidden="true"></span></button>
						</div>
					</article>

					<article class="wp-dialyra-menu-list__item">
						<button type="button">
							<strong><?php esc_html_e( 'Order Info', 'wp-dialyra' ); ?></strong>
							<small><?php esc_html_e( '2 choices', 'wp-dialyra' ); ?></small>
						</button>
						<em class="wp-dialyra-result wp-dialyra-result--warning"><?php esc_html_e( 'Warning', 'wp-dialyra' ); ?></em>
						<div class="wp-dialyra-menu-list__actions">
							<button class="wp-dialyra-flow-icon-button" type="button" aria-label="<?php esc_attr_e( 'Rename Order Info', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Rename menu', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>
							<button class="wp-dialyra-flow-icon-button wp-dialyra-flow-icon-button--delete" type="button" aria-label="<?php esc_attr_e( 'Delete Order Info', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Delete menu', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
						</div>
					</article>

					<article class="wp-dialyra-menu-list__item">
						<button type="button">
							<strong><?php esc_html_e( 'Offers', 'wp-dialyra' ); ?></strong>
							<small><?php esc_html_e( '1 choice', 'wp-dialyra' ); ?></small>
						</button>
						<em class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'Valid', 'wp-dialyra' ); ?></em>
						<div class="wp-dialyra-menu-list__actions">
							<button class="wp-dialyra-flow-icon-button" type="button" aria-label="<?php esc_attr_e( 'Rename Offers', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Rename menu', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>
							<button class="wp-dialyra-flow-icon-button wp-dialyra-flow-icon-button--delete" type="button" aria-label="<?php esc_attr_e( 'Delete Offers', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Delete menu', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
						</div>
					</article>
				</div>
			</section>
		</aside>

		<main class="wp-dialyra-menu-editor" aria-label="<?php esc_attr_e( 'Selected menu editor', 'wp-dialyra' ); ?>">
			<section class="wp-dialyra-menu-editor__card">
				<div class="wp-dialyra-menu-editor__head wp-dialyra-menu-editor__head--split">
					<div class="wp-dialyra-menu-editor__title">
						<span aria-hidden="true">01</span>
						<div>
							<h3><?php esc_html_e( 'Menu basics', 'wp-dialyra' ); ?></h3>
							<p><?php esc_html_e( 'Edit the selected menu. Menu names must be unique.', 'wp-dialyra' ); ?></p>
						</div>
					</div>
				</div>

				<div class="wp-dialyra-flow-fields wp-dialyra-flow-fields--two">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-menu-name"><?php esc_html_e( 'Menu name', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-menu-name" name="menu_name" type="text" value="Main Menu">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-menu-description"><?php esc_html_e( 'Description optional', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-menu-description" name="menu_description" type="text" value="First message customers hear.">
					</div>
				</div>
			</section>

			<section class="wp-dialyra-menu-editor__card">
				<div class="wp-dialyra-menu-editor__head">
					<span aria-hidden="true">02</span>
					<div>
						<h3><?php esc_html_e( 'Customer Instruction Message', 'wp-dialyra' ); ?></h3>
						<p><?php esc_html_e( 'Fields change based on the selected message type.', 'wp-dialyra' ); ?></p>
					</div>
				</div>

				<div class="wp-dialyra-message-composer" data-dialyra-dynamic-group>
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-menu-message-type"><?php esc_html_e( 'Message type', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-menu-message-type" name="menu_message_type" data-dialyra-dynamic-select>
							<option value="tts"><?php esc_html_e( 'Text to Speech', 'wp-dialyra' ); ?></option>
							<option value="audio"><?php esc_html_e( 'Audio', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row wp-dialyra-message-composer__wide" data-dialyra-show-for="tts">
						<label for="wp-dialyra-menu-message-text"><?php esc_html_e( 'Message', 'wp-dialyra' ); ?></label>
						<textarea id="wp-dialyra-menu-message-text" name="menu_message_text" rows="4"><?php esc_html_e( 'Press 1 to confirm your order, press 2 to cancel, press 3 to talk to support.', 'wp-dialyra' ); ?></textarea>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="audio">
						<label for="wp-dialyra-menu-audio"><?php esc_html_e( 'Audio asset', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-menu-audio" name="menu_audio_asset">
							<option><?php esc_html_e( 'main-menu-instruction.wav', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'order-info.wav', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-menu-language"><?php esc_html_e( 'Language', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-menu-language" name="menu_tts_language">
							<option><?php esc_html_e( 'Bangla', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'English', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-menu-voice"><?php esc_html_e( 'Voice', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-menu-voice" name="menu_tts_voice">
							<option><?php esc_html_e( 'Female warm', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'Male clear', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-menu-provider"><?php esc_html_e( 'Provider', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-menu-provider" name="menu_tts_provider">
							<option><?php esc_html_e( 'Dialyra Voice', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'Google TTS', 'wp-dialyra' ); ?></option>
						</select>
					</div>
				</div>
			</section>

			<section class="wp-dialyra-menu-editor__card">
				<div class="wp-dialyra-menu-editor__head">
					<span aria-hidden="true">03</span>
					<div>
						<h3><?php esc_html_e( 'Menu input settings', 'wp-dialyra' ); ?></h3>
						<p><?php esc_html_e( 'Control how long the menu waits and how many retries are allowed.', 'wp-dialyra' ); ?></p>
					</div>
				</div>

				<div class="wp-dialyra-flow-fields wp-dialyra-flow-fields--four">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-max-digits"><?php esc_html_e( 'Max digits', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-max-digits" name="max_digits" type="number" min="1" max="9" value="1">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-timeout-seconds"><?php esc_html_e( 'Timeout seconds', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-timeout-seconds" name="timeout_seconds" type="number" min="1" value="5">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-invalid-retries"><?php esc_html_e( 'Max invalid retries', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-invalid-retries" name="max_invalid_retries" type="number" min="0" value="2">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-timeout-retries"><?php esc_html_e( 'Max timeout retries', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-timeout-retries" name="max_timeout_retries" type="number" min="0" value="1">
					</div>
				</div>
			</section>

			<section class="wp-dialyra-menu-editor__card">
				<div class="wp-dialyra-menu-editor__head wp-dialyra-menu-editor__head--split">
					<div class="wp-dialyra-menu-editor__title">
						<span aria-hidden="true">04</span>
						<div>
							<h3><?php esc_html_e( 'DTMF actions', 'wp-dialyra' ); ?></h3>
							<p><?php esc_html_e( 'Map keypad choices to business actions and the next step.', 'wp-dialyra' ); ?></p>
						</div>
					</div>
					<button id="wp-dialyra-add-dtmf-action" class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( '+ Add DTMF Action', 'wp-dialyra' ); ?></button>
				</div>

				<div id="wp-dialyra-dtmf-actions" class="wp-dialyra-dtmf-actions">
					<?php
					$wp_dialyra_dtmf_actions = array(
						array(
							'key'             => '1',
							'response_text'   => 'Your order is confirmed.',
							'business_action' => 'confirm_order',
							'next_step'       => 'end_flow',
						),
						array(
							'key'             => '2',
							'response_text'   => 'Please wait while we connect you.',
							'business_action' => 'transfer_department',
							'next_step'       => 'hangup',
						),
					);
					foreach ( $wp_dialyra_dtmf_actions as $wp_dialyra_index => $wp_dialyra_action ) :
						$wp_dialyra_action_number = $wp_dialyra_index + 1;
						?>
						<article class="wp-dialyra-dtmf-action" data-dtmf-action>
							<div class="wp-dialyra-dtmf-actions__key">
								<label for="wp-dialyra-dtmf-key-<?php echo esc_attr( $wp_dialyra_action_number ); ?>"><?php esc_html_e( 'Input key', 'wp-dialyra' ); ?></label>
								<select id="wp-dialyra-dtmf-key-<?php echo esc_attr( $wp_dialyra_action_number ); ?>" name="dtmf_key_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
									<?php for ( $wp_dialyra_key = 1; $wp_dialyra_key <= 9; $wp_dialyra_key++ ) : ?>
										<option <?php selected( (string) $wp_dialyra_key, $wp_dialyra_action['key'] ); ?>><?php echo esc_html( $wp_dialyra_key ); ?></option>
									<?php endfor; ?>
								</select>
							</div>

							<div class="wp-dialyra-dtmf-actions__body">
								<div class="wp-dialyra-flow-fields wp-dialyra-flow-fields--three">
									<div class="wp-dialyra-settings-row">
										<label><?php esc_html_e( 'Response message', 'wp-dialyra' ); ?></label>
										<select name="response_type_<?php echo esc_attr( $wp_dialyra_action_number ); ?>" data-dialyra-dynamic-select>
											<option value="none"><?php esc_html_e( 'None', 'wp-dialyra' ); ?></option>
											<option value="tts" selected><?php esc_html_e( 'Text to Speech', 'wp-dialyra' ); ?></option>
											<option value="audio"><?php esc_html_e( 'Audio', 'wp-dialyra' ); ?></option>
										</select>
									</div>

									<div class="wp-dialyra-settings-row">
										<label><?php esc_html_e( 'Business action', 'wp-dialyra' ); ?></label>
										<select name="business_action_<?php echo esc_attr( $wp_dialyra_action_number ); ?>" data-dialyra-dynamic-select>
											<option value="no_action"><?php esc_html_e( 'No action', 'wp-dialyra' ); ?></option>
											<option value="confirm_order" <?php selected( 'confirm_order', $wp_dialyra_action['business_action'] ); ?>><?php esc_html_e( 'Confirm order', 'wp-dialyra' ); ?></option>
											<option value="cancel_order"><?php esc_html_e( 'Cancel order', 'wp-dialyra' ); ?></option>
											<option value="transfer_department" <?php selected( 'transfer_department', $wp_dialyra_action['business_action'] ); ?>><?php esc_html_e( 'Transfer department', 'wp-dialyra' ); ?></option>
										</select>
									</div>

									<div class="wp-dialyra-settings-row">
										<label><?php esc_html_e( 'Next step', 'wp-dialyra' ); ?></label>
										<select name="next_step_<?php echo esc_attr( $wp_dialyra_action_number ); ?>" data-dialyra-dynamic-select>
											<option value="hangup" <?php selected( 'hangup', $wp_dialyra_action['next_step'] ); ?>><?php esc_html_e( 'Hangup', 'wp-dialyra' ); ?></option>
											<option value="go_to_menu"><?php esc_html_e( 'Go To Menu', 'wp-dialyra' ); ?></option>
											<option value="repeat_current_menu"><?php esc_html_e( 'Repeat Current Menu', 'wp-dialyra' ); ?></option>
											<option value="end_flow" <?php selected( 'end_flow', $wp_dialyra_action['next_step'] ); ?>><?php esc_html_e( 'End Flow', 'wp-dialyra' ); ?></option>
										</select>
									</div>
								</div>

								<div class="wp-dialyra-flow-fields wp-dialyra-flow-fields--three">
									<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
										<label><?php esc_html_e( 'Message', 'wp-dialyra' ); ?></label>
										<input name="response_text_<?php echo esc_attr( $wp_dialyra_action_number ); ?>" type="text" value="<?php echo esc_attr( $wp_dialyra_action['response_text'] ); ?>">
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
										<label><?php esc_html_e( 'Language', 'wp-dialyra' ); ?></label>
										<select name="response_tts_language_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
											<option><?php esc_html_e( 'Bangla', 'wp-dialyra' ); ?></option>
											<option><?php esc_html_e( 'English', 'wp-dialyra' ); ?></option>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
										<label><?php esc_html_e( 'Voice', 'wp-dialyra' ); ?></label>
										<select name="response_tts_voice_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
											<option><?php esc_html_e( 'Female warm', 'wp-dialyra' ); ?></option>
											<option><?php esc_html_e( 'Male clear', 'wp-dialyra' ); ?></option>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
										<label><?php esc_html_e( 'Provider', 'wp-dialyra' ); ?></label>
										<select name="response_tts_provider_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
											<option><?php esc_html_e( 'Dialyra Voice', 'wp-dialyra' ); ?></option>
											<option><?php esc_html_e( 'Google TTS', 'wp-dialyra' ); ?></option>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-show-for="audio">
										<label><?php esc_html_e( 'Audio asset', 'wp-dialyra' ); ?></label>
										<select name="response_audio_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
											<option><?php esc_html_e( 'confirmation.wav', 'wp-dialyra' ); ?></option>
											<option><?php esc_html_e( 'transfer.wav', 'wp-dialyra' ); ?></option>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-show-for="transfer_department">
										<label><?php esc_html_e( 'Department', 'wp-dialyra' ); ?></label>
										<select name="department_target_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
											<option><?php esc_html_e( 'Billing Department', 'wp-dialyra' ); ?></option>
											<option><?php esc_html_e( 'Support Department', 'wp-dialyra' ); ?></option>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-show-for="go_to_menu">
										<label><?php esc_html_e( 'Target menu', 'wp-dialyra' ); ?></label>
										<select name="target_menu_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
											<option><?php esc_html_e( 'Order Info', 'wp-dialyra' ); ?></option>
											<option><?php esc_html_e( 'Offers', 'wp-dialyra' ); ?></option>
											<option><?php esc_html_e( 'Main Menu', 'wp-dialyra' ); ?></option>
										</select>
									</div>
								</div>

								<div class="wp-dialyra-dtmf-actions__footer">
									<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-remove-dtmf-action><?php esc_html_e( 'Remove DTMF Action', 'wp-dialyra' ); ?></button>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="wp-dialyra-menu-editor__card">
				<div class="wp-dialyra-menu-editor__head">
					<span aria-hidden="true">05</span>
					<div>
						<h3><?php esc_html_e( 'Invalid input handling', 'wp-dialyra' ); ?></h3>
						<p><?php esc_html_e( 'Configure what customers hear after an unsupported key.', 'wp-dialyra' ); ?></p>
					</div>
				</div>

				<div class="wp-dialyra-message-composer" data-dialyra-dynamic-group>
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-invalid-message-type"><?php esc_html_e( 'Message type', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-invalid-message-type" name="invalid_message_type" data-dialyra-dynamic-select>
							<option value="tts"><?php esc_html_e( 'Text to Speech', 'wp-dialyra' ); ?></option>
							<option value="audio"><?php esc_html_e( 'Audio', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-invalid-message"><?php esc_html_e( 'Message', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-invalid-message" name="invalid_message" type="text" value="Sorry, that option is not available.">
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-invalid-language"><?php esc_html_e( 'Language', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-invalid-language" name="invalid_tts_language">
							<option><?php esc_html_e( 'Bangla', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'English', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-invalid-voice"><?php esc_html_e( 'Voice', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-invalid-voice" name="invalid_tts_voice">
							<option><?php esc_html_e( 'Female warm', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'Male clear', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-invalid-provider"><?php esc_html_e( 'Provider', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-invalid-provider" name="invalid_tts_provider">
							<option><?php esc_html_e( 'Dialyra Voice', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'Google TTS', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="audio">
						<label for="wp-dialyra-invalid-audio"><?php esc_html_e( 'Invalid response audio', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-invalid-audio" name="invalid_audio">
							<option><?php esc_html_e( 'invalid-option.wav', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'try-again.wav', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-invalid-action"><?php esc_html_e( 'After max invalid retry action', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-invalid-action" name="invalid_action" data-dialyra-dynamic-select>
							<option value="repeat_current_menu"><?php esc_html_e( 'Repeat Current Menu', 'wp-dialyra' ); ?></option>
							<option value="go_to_menu"><?php esc_html_e( 'Go To Menu', 'wp-dialyra' ); ?></option>
							<option value="hangup"><?php esc_html_e( 'Hangup', 'wp-dialyra' ); ?></option>
							<option value="end_flow"><?php esc_html_e( 'End Flow', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="go_to_menu">
						<label for="wp-dialyra-invalid-target-menu"><?php esc_html_e( 'Target menu', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-invalid-target-menu" name="invalid_target_menu">
							<option><?php esc_html_e( 'Main Menu', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'Order Info', 'wp-dialyra' ); ?></option>
							<option><?php esc_html_e( 'Offers', 'wp-dialyra' ); ?></option>
						</select>
					</div>
				</div>
			</section>

			<section class="wp-dialyra-menu-editor__card">
				<div class="wp-dialyra-menu-editor__head">
					<span aria-hidden="true">06</span>
					<div>
						<h3><?php esc_html_e( 'Timeout handling', 'wp-dialyra' ); ?></h3>
						<p><?php esc_html_e( 'Configure no-input, transfer timeout, and transfer failed outcomes.', 'wp-dialyra' ); ?></p>
					</div>
				</div>

				<div class="wp-dialyra-timeout-configs">
					<?php
					$wp_dialyra_timeout_configs = array(
						'timeout'          => __( 'Timeout response', 'wp-dialyra' ),
						'transfer_timeout' => __( 'Transfer Timeout', 'wp-dialyra' ),
						'transfer_failed'  => __( 'Transfer Failed', 'wp-dialyra' ),
					);
					foreach ( $wp_dialyra_timeout_configs as $wp_dialyra_key => $wp_dialyra_title ) :
						?>
						<article data-dialyra-dynamic-group>
							<h4><?php echo esc_html( $wp_dialyra_title ); ?></h4>
							<div class="wp-dialyra-flow-fields wp-dialyra-flow-fields--three">
								<div class="wp-dialyra-settings-row">
									<label for="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-message-type"><?php esc_html_e( 'Message type', 'wp-dialyra' ); ?></label>
									<select id="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-message-type" name="<?php echo esc_attr( $wp_dialyra_key ); ?>_message_type" data-dialyra-dynamic-select>
										<option value="tts"><?php esc_html_e( 'Text to Speech', 'wp-dialyra' ); ?></option>
										<option value="audio"><?php esc_html_e( 'Audio', 'wp-dialyra' ); ?></option>
									</select>
								</div>

								<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
									<label for="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-text"><?php esc_html_e( 'Message', 'wp-dialyra' ); ?></label>
									<input id="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-text" name="<?php echo esc_attr( $wp_dialyra_key ); ?>_text" type="text" value="<?php esc_attr_e( 'We could not complete this step. Please try again.', 'wp-dialyra' ); ?>">
								</div>

								<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
									<label for="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-language"><?php esc_html_e( 'Language', 'wp-dialyra' ); ?></label>
									<select id="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-language" name="<?php echo esc_attr( $wp_dialyra_key ); ?>_tts_language">
										<option><?php esc_html_e( 'Bangla', 'wp-dialyra' ); ?></option>
										<option><?php esc_html_e( 'English', 'wp-dialyra' ); ?></option>
									</select>
								</div>

								<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
									<label for="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-voice"><?php esc_html_e( 'Voice', 'wp-dialyra' ); ?></label>
									<select id="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-voice" name="<?php echo esc_attr( $wp_dialyra_key ); ?>_tts_voice">
										<option><?php esc_html_e( 'Female warm', 'wp-dialyra' ); ?></option>
										<option><?php esc_html_e( 'Male clear', 'wp-dialyra' ); ?></option>
									</select>
								</div>

								<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
									<label for="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-provider"><?php esc_html_e( 'Provider', 'wp-dialyra' ); ?></label>
									<select id="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-provider" name="<?php echo esc_attr( $wp_dialyra_key ); ?>_tts_provider">
										<option><?php esc_html_e( 'Dialyra Voice', 'wp-dialyra' ); ?></option>
										<option><?php esc_html_e( 'Google TTS', 'wp-dialyra' ); ?></option>
									</select>
								</div>

								<div class="wp-dialyra-settings-row" data-dialyra-show-for="audio">
									<label for="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-audio"><?php esc_html_e( 'Audio asset', 'wp-dialyra' ); ?></label>
									<select id="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-audio" name="<?php echo esc_attr( $wp_dialyra_key ); ?>_audio">
										<option><?php esc_html_e( 'timeout.wav', 'wp-dialyra' ); ?></option>
										<option><?php esc_html_e( 'transfer-failed.wav', 'wp-dialyra' ); ?></option>
									</select>
								</div>

								<div class="wp-dialyra-settings-row">
									<label for="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-next-step"><?php esc_html_e( 'Next step', 'wp-dialyra' ); ?></label>
									<select id="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-next-step" name="<?php echo esc_attr( $wp_dialyra_key ); ?>_next_step" data-dialyra-dynamic-select>
										<option value="repeat_current_menu"><?php esc_html_e( 'Repeat Current Menu', 'wp-dialyra' ); ?></option>
										<option value="go_to_menu"><?php esc_html_e( 'Go To Menu', 'wp-dialyra' ); ?></option>
										<option value="hangup"><?php esc_html_e( 'Hangup', 'wp-dialyra' ); ?></option>
										<option value="end_flow"><?php esc_html_e( 'End Flow', 'wp-dialyra' ); ?></option>
									</select>
								</div>

								<div class="wp-dialyra-settings-row" data-dialyra-show-for="go_to_menu">
									<label for="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-target-menu"><?php esc_html_e( 'Target menu', 'wp-dialyra' ); ?></label>
									<select id="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-target-menu" name="<?php echo esc_attr( $wp_dialyra_key ); ?>_target_menu">
										<option><?php esc_html_e( 'Main Menu', 'wp-dialyra' ); ?></option>
										<option><?php esc_html_e( 'Order Info', 'wp-dialyra' ); ?></option>
										<option><?php esc_html_e( 'Offers', 'wp-dialyra' ); ?></option>
									</select>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="wp-dialyra-flow-bottom-grid">
				<div class="wp-dialyra-menu-editor__card">
					<div class="wp-dialyra-menu-editor__head">
						<span aria-hidden="true">07</span>
						<div>
						<h3><?php esc_html_e( 'Preview panel', 'wp-dialyra' ); ?></h3>
						<p><?php esc_html_e( 'Readable menu tree for store owners.', 'wp-dialyra' ); ?></p>
					</div>
					<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-preview' ) ); ?>"><?php esc_html_e( 'Open Preview', 'wp-dialyra' ); ?></a>
				</div>

					<pre class="wp-dialyra-flow-preview">Main Menu
├── 1 → Confirm order
├── 2 → Transfer department
└── Timeout → Repeat Current Menu</pre>
				</div>

				<div class="wp-dialyra-menu-editor__card">
					<div class="wp-dialyra-menu-editor__head">
						<span aria-hidden="true">08</span>
						<div>
							<h3><?php esc_html_e( 'Validation', 'wp-dialyra' ); ?></h3>
							<p><?php esc_html_e( 'Fix critical issues before publishing.', 'wp-dialyra' ); ?></p>
						</div>
					</div>

					<ul class="wp-dialyra-validation-list">
						<li class="wp-dialyra-validation-list__success"><?php esc_html_e( 'Flow name is set.', 'wp-dialyra' ); ?></li>
						<li class="wp-dialyra-validation-list__success"><?php esc_html_e( 'Start menu selected.', 'wp-dialyra' ); ?></li>
						<li class="wp-dialyra-validation-list__warning"><?php esc_html_e( 'Order Info may have no exit path.', 'wp-dialyra' ); ?></li>
						<li class="wp-dialyra-validation-list__danger"><?php esc_html_e( 'Go To Menu target missing in one action.', 'wp-dialyra' ); ?></li>
					</ul>
				</div>
			</section>
		</main>
	</div>
</section>
