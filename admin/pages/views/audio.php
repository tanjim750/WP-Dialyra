<?php

/**
 * Audio library page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<section class="wp-dialyra-audio">
	<div class="wp-dialyra-audio__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Audio Library', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Upload prompts, hold music, and reusable call audio.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Manage business audio assets for IVR prompts, queue experiences, and call flows from one clean library.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-audio__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="#wp-dialyra-upload-audio"><?php esc_html_e( 'Upload Audio', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<div class="wp-dialyra-audio__grid">
		<section id="wp-dialyra-upload-audio" class="wp-dialyra-audio-panel">
			<div class="wp-dialyra-audio-panel__head">
				<span aria-hidden="true">01</span>
				<div>
					<h3><?php esc_html_e( 'Upload audio', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Add a WAV asset and classify it for call flow usage.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<form class="wp-dialyra-audio-form" method="post" action="#" enctype="multipart/form-data">
				<input type="hidden" name="business_id" value="1">

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-audio-name"><?php esc_html_e( 'Audio name', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-audio-name" name="name" type="text" value="final voice">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-audio-category"><?php esc_html_e( 'Category', 'wp-dialyra' ); ?></label>
					<select id="wp-dialyra-audio-category" name="category">
						<option><?php esc_html_e( 'hold_music', 'wp-dialyra' ); ?></option>
						<option><?php esc_html_e( 'ivr_prompt', 'wp-dialyra' ); ?></option>
						<option><?php esc_html_e( 'greeting', 'wp-dialyra' ); ?></option>
						<option><?php esc_html_e( 'fallback', 'wp-dialyra' ); ?></option>
					</select>
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-audio-file"><?php esc_html_e( 'Audio file', 'wp-dialyra' ); ?></label>
					<div class="wp-dialyra-audio-dropzone">
						<input id="wp-dialyra-audio-file" name="file" type="file" accept=".wav,audio/wav,audio/x-wav">
						<span><?php esc_html_e( 'WAV recommended for telephony playback.', 'wp-dialyra' ); ?></span>
					</div>
				</div>

				<div class="wp-dialyra-audio-hint">
					<span><?php esc_html_e( 'Upload endpoint', 'wp-dialyra' ); ?></span>
					<code>POST api/v2/audio-assets/upload</code>
				</div>

				<div class="wp-dialyra-audio-panel__footer">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Upload audio', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</section>

		<section class="wp-dialyra-audio-panel wp-dialyra-audio-panel--wide">
			<div class="wp-dialyra-audio-panel__head">
				<span aria-hidden="true">02</span>
				<div>
					<h3><?php esc_html_e( 'Uploaded audio', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'View uploaded assets and use quick actions to stream, update, or delete audio.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-audio-list">
				<article>
					<input type="hidden" name="audio_asset_id" value="12">
					<div class="wp-dialyra-audio-wave" aria-hidden="true">
						<span></span><span></span><span></span><span></span><span></span><span></span>
					</div>
					<div>
						<h4><?php esc_html_e( 'final voice', 'wp-dialyra' ); ?></h4>
						<p><?php esc_html_e( 'filename.wav · WAV prompt asset', 'wp-dialyra' ); ?></p>
					</div>
					<em class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'hold_music', 'wp-dialyra' ); ?></em>
					<div class="wp-dialyra-audio-list__actions">
						<a class="wp-dialyra-audio-action wp-dialyra-audio-action--stream" href="#" aria-label="<?php esc_attr_e( 'Stream final voice', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Stream', 'wp-dialyra' ); ?>">
							<span class="dashicons dashicons-controls-play" aria-hidden="true"></span>
						</a>
						<button class="wp-dialyra-audio-action wp-dialyra-audio-action--edit" type="button" aria-label="<?php esc_attr_e( 'Update final voice', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Update', 'wp-dialyra' ); ?>">
							<span class="dashicons dashicons-edit" aria-hidden="true"></span>
						</button>
						<button class="wp-dialyra-audio-action wp-dialyra-audio-action--delete" type="button" aria-label="<?php esc_attr_e( 'Delete final voice', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Delete', 'wp-dialyra' ); ?>">
							<span class="dashicons dashicons-trash" aria-hidden="true"></span>
						</button>
					</div>
				</article>

				<article>
					<input type="hidden" name="audio_asset_id" value="13">
					<div class="wp-dialyra-audio-wave" aria-hidden="true">
						<span></span><span></span><span></span><span></span><span></span><span></span>
					</div>
					<div>
						<h4><?php esc_html_e( 'call hold', 'wp-dialyra' ); ?></h4>
						<p><?php esc_html_e( 'queue-hold.wav · IVR playback asset', 'wp-dialyra' ); ?></p>
					</div>
					<em class="wp-dialyra-result wp-dialyra-result--warning"><?php esc_html_e( 'ivr_prompt', 'wp-dialyra' ); ?></em>
					<div class="wp-dialyra-audio-list__actions">
						<a class="wp-dialyra-audio-action wp-dialyra-audio-action--stream" href="#" aria-label="<?php esc_attr_e( 'Stream call hold', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Stream', 'wp-dialyra' ); ?>">
							<span class="dashicons dashicons-controls-play" aria-hidden="true"></span>
						</a>
						<button class="wp-dialyra-audio-action wp-dialyra-audio-action--edit" type="button" aria-label="<?php esc_attr_e( 'Update call hold', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Update', 'wp-dialyra' ); ?>">
							<span class="dashicons dashicons-edit" aria-hidden="true"></span>
						</button>
						<button class="wp-dialyra-audio-action wp-dialyra-audio-action--delete" type="button" aria-label="<?php esc_attr_e( 'Delete call hold', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Delete', 'wp-dialyra' ); ?>">
							<span class="dashicons dashicons-trash" aria-hidden="true"></span>
						</button>
					</div>
				</article>
			</div>
		</section>
	</div>
</section>
