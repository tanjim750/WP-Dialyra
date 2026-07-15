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

$wp_dialyra_plugin = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
$wp_dialyra_api_endpoints = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_api_endpoints() : null;
$wp_dialyra_business_id = class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
$wp_dialyra_audio_assets = array();
$wp_dialyra_audio_error = '';
$wp_dialyra_audio_success = '';
$wp_dialyra_audio_categories = array(
	'hold_music' => __( 'hold_music', 'wp-dialyra' ),
	'ivr_prompt' => __( 'ivr_prompt', 'wp-dialyra' ),
	'greeting' => __( 'greeting', 'wp-dialyra' ),
	'fallback' => __( 'fallback', 'wp-dialyra' ),
);

$wp_dialyra_extract_audio_items = static function ( $response ) {
	if ( ! $response || ! is_object( $response ) || ! method_exists( $response, 'is_successful' ) || ! $response->is_successful() || ! method_exists( $response, 'get_data' ) ) {
		return array();
	}

	$data = $response->get_data();
	$data = is_array( $data ) ? $data : array();

	if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
		$data = $data['data'];
	}

	foreach ( array( 'items', 'audio_assets', 'assets', 'data' ) as $container_key ) {
		if ( isset( $data[ $container_key ] ) && is_array( $data[ $container_key ] ) ) {
			return $data[ $container_key ];
		}
	}

	if ( isset( $data[0] ) && is_array( $data[0] ) ) {
		return $data;
	}

	return array();
};

$wp_dialyra_normalize_audio_asset = static function ( $asset ) {
	$asset = is_array( $asset ) ? $asset : array();
	$file_name = '';

	foreach ( array( 'file_name', 'filename', 'original_filename', 'original_name', 'path' ) as $file_key ) {
		if ( ! empty( $asset[ $file_key ] ) ) {
			$file_name = basename( sanitize_text_field( $asset[ $file_key ] ) );
			break;
		}
	}

	return array(
		'id'         => isset( $asset['id'] ) ? absint( $asset['id'] ) : 0,
		'name'       => ! empty( $asset['name'] ) ? sanitize_text_field( $asset['name'] ) : __( 'Untitled audio', 'wp-dialyra' ),
		'category'   => ! empty( $asset['category'] ) ? sanitize_key( $asset['category'] ) : 'ivr_prompt',
		'file_name'  => $file_name ? $file_name : __( 'Audio asset', 'wp-dialyra' ),
		'mime_type'  => ! empty( $asset['mime_type'] ) ? sanitize_text_field( $asset['mime_type'] ) : '',
		'created_at' => ! empty( $asset['created_at'] ) ? sanitize_text_field( $asset['created_at'] ) : '',
	);
};

if ( 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) && isset( $_POST['wp_dialyra_audio_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_dialyra_audio_nonce'] ), 'wp-dialyra-audio' ) ) {
	$wp_dialyra_audio_action = isset( $_POST['wp_dialyra_audio_action'] ) ? sanitize_key( wp_unslash( $_POST['wp_dialyra_audio_action'] ) ) : '';

	if ( ! $wp_dialyra_business_id ) {
		$wp_dialyra_audio_error = esc_html__( 'Connect a business before managing audio assets.', 'wp-dialyra' );
	} elseif ( ! $wp_dialyra_api_endpoints ) {
		$wp_dialyra_audio_error = esc_html__( 'Audio service is not available.', 'wp-dialyra' );
	} elseif ( 'upload_audio' === $wp_dialyra_audio_action ) {
		$audio_name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$audio_category = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'ivr_prompt';
		$audio_file = isset( $_FILES['file'] ) && is_array( $_FILES['file'] ) ? $_FILES['file'] : array();

		if ( empty( $audio_name ) ) {
			$wp_dialyra_audio_error = esc_html__( 'Audio name is required.', 'wp-dialyra' );
		} elseif ( empty( $audio_file['tmp_name'] ) || ! empty( $audio_file['error'] ) ) {
			$wp_dialyra_audio_error = esc_html__( 'Choose a valid audio file to upload.', 'wp-dialyra' );
		} else {
			$response = $wp_dialyra_api_endpoints->upload_audio_asset(
				array(
					'business_id' => $wp_dialyra_business_id,
					'name'        => $audio_name,
					'category'    => $audio_category,
				),
				$audio_file
			);

			if ( $response && $response->is_successful() ) {
				$wp_dialyra_audio_success = esc_html__( 'Audio uploaded successfully.', 'wp-dialyra' );
			} else {
				$wp_dialyra_audio_error = $response ? $response->get_message() : esc_html__( 'Audio upload failed.', 'wp-dialyra' );
			}
		}
	} elseif ( 'update_audio' === $wp_dialyra_audio_action ) {
		$audio_asset_id = isset( $_POST['audio_asset_id'] ) ? absint( wp_unslash( $_POST['audio_asset_id'] ) ) : 0;
		$audio_name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$audio_category = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'ivr_prompt';

		if ( ! $audio_asset_id || empty( $audio_name ) ) {
			$wp_dialyra_audio_error = esc_html__( 'Audio ID and name are required for update.', 'wp-dialyra' );
		} else {
			$response = $wp_dialyra_api_endpoints->update_audio_asset(
				$audio_asset_id,
				array(
					'name'     => $audio_name,
					'category' => $audio_category,
				)
			);

			if ( $response && $response->is_successful() ) {
				$wp_dialyra_audio_success = esc_html__( 'Audio updated successfully.', 'wp-dialyra' );
			} else {
				$wp_dialyra_audio_error = $response ? $response->get_message() : esc_html__( 'Audio update failed.', 'wp-dialyra' );
			}
		}
	} elseif ( 'delete_audio' === $wp_dialyra_audio_action ) {
		$audio_asset_id = isset( $_POST['audio_asset_id'] ) ? absint( wp_unslash( $_POST['audio_asset_id'] ) ) : 0;

		if ( ! $audio_asset_id ) {
			$wp_dialyra_audio_error = esc_html__( 'Audio ID is required for delete.', 'wp-dialyra' );
		} else {
			$response = $wp_dialyra_api_endpoints->delete_audio_asset( $audio_asset_id );

			if ( $response && $response->is_successful() ) {
				$wp_dialyra_audio_success = esc_html__( 'Audio deleted successfully.', 'wp-dialyra' );
			} else {
				$wp_dialyra_audio_error = $response ? $response->get_message() : esc_html__( 'Audio delete failed.', 'wp-dialyra' );
			}
		}
	}
}

if ( $wp_dialyra_api_endpoints && $wp_dialyra_business_id ) {
	$wp_dialyra_audio_response = $wp_dialyra_api_endpoints->get_audio_assets( array( 'business_id' => $wp_dialyra_business_id ) );

	if ( $wp_dialyra_audio_response && $wp_dialyra_audio_response->is_successful() ) {
		$wp_dialyra_audio_assets = array_filter(
			array_map( $wp_dialyra_normalize_audio_asset, $wp_dialyra_extract_audio_items( $wp_dialyra_audio_response ) ),
			static function ( $asset ) {
				return ! empty( $asset['id'] );
			}
		);
	} elseif ( empty( $wp_dialyra_audio_error ) && $wp_dialyra_audio_response ) {
		$wp_dialyra_audio_error = $wp_dialyra_audio_response->get_message();
	}
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

	<?php if ( ! empty( $wp_dialyra_audio_error ) ) : ?>
		<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--error">
			<span class="dashicons dashicons-warning" aria-hidden="true"></span>
			<p><?php echo esc_html( $wp_dialyra_audio_error ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $wp_dialyra_audio_success ) ) : ?>
		<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--success">
			<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
			<p><?php echo esc_html( $wp_dialyra_audio_success ); ?></p>
		</div>
	<?php endif; ?>

	<div class="wp-dialyra-audio__grid">
		<section id="wp-dialyra-upload-audio" class="wp-dialyra-audio-panel">
			<div class="wp-dialyra-audio-panel__head">
				<span aria-hidden="true">01</span>
				<div>
					<h3><?php esc_html_e( 'Upload audio', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Add a WAV asset and classify it for call flow usage.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<form class="wp-dialyra-audio-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=audio' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'wp-dialyra-audio', 'wp_dialyra_audio_nonce' ); ?>
				<input type="hidden" name="wp_dialyra_audio_action" value="upload_audio">

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-audio-name"><?php esc_html_e( 'Audio name', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-audio-name" name="name" type="text" placeholder="<?php esc_attr_e( 'final voice', 'wp-dialyra' ); ?>">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-audio-category"><?php esc_html_e( 'Category', 'wp-dialyra' ); ?></label>
					<select id="wp-dialyra-audio-category" name="category">
						<?php foreach ( $wp_dialyra_audio_categories as $category_key => $category_label ) : ?>
							<option value="<?php echo esc_attr( $category_key ); ?>"><?php echo esc_html( $category_label ); ?></option>
						<?php endforeach; ?>
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
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Upload audio', 'wp-dialyra' ); ?></button>
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
				<?php if ( ! empty( $wp_dialyra_audio_assets ) ) : ?>
					<?php foreach ( $wp_dialyra_audio_assets as $wp_dialyra_audio_asset ) : ?>
						<?php
						$wp_dialyra_stream_url = wp_nonce_url(
							admin_url( 'admin-post.php?action=wp_dialyra_stream_audio&audio_asset_id=' . absint( $wp_dialyra_audio_asset['id'] ) ),
							'wp-dialyra-stream-audio-' . absint( $wp_dialyra_audio_asset['id'] )
						);
						?>
						<article>
							<div class="wp-dialyra-audio-wave" aria-hidden="true">
								<span></span><span></span><span></span><span></span><span></span><span></span>
							</div>
							<form id="<?php echo esc_attr( 'wp-dialyra-audio-update-' . $wp_dialyra_audio_asset['id'] ); ?>" class="wp-dialyra-audio-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=audio' ) ); ?>">
								<?php wp_nonce_field( 'wp-dialyra-audio', 'wp_dialyra_audio_nonce' ); ?>
								<input type="hidden" name="wp_dialyra_audio_action" value="update_audio">
								<input type="hidden" name="audio_asset_id" value="<?php echo esc_attr( $wp_dialyra_audio_asset['id'] ); ?>">
								<div class="wp-dialyra-audio-inline-fields">
									<div class="wp-dialyra-settings-row">
										<label for="wp-dialyra-audio-name-<?php echo esc_attr( $wp_dialyra_audio_asset['id'] ); ?>"><?php esc_html_e( 'Name', 'wp-dialyra' ); ?></label>
										<input id="wp-dialyra-audio-name-<?php echo esc_attr( $wp_dialyra_audio_asset['id'] ); ?>" name="name" type="text" value="<?php echo esc_attr( $wp_dialyra_audio_asset['name'] ); ?>">
									</div>
									<div class="wp-dialyra-settings-row">
										<label for="wp-dialyra-audio-category-<?php echo esc_attr( $wp_dialyra_audio_asset['id'] ); ?>"><?php esc_html_e( 'Category', 'wp-dialyra' ); ?></label>
										<select id="wp-dialyra-audio-category-<?php echo esc_attr( $wp_dialyra_audio_asset['id'] ); ?>" name="category">
											<?php foreach ( $wp_dialyra_audio_categories as $category_key => $category_label ) : ?>
												<option value="<?php echo esc_attr( $category_key ); ?>" <?php selected( $wp_dialyra_audio_asset['category'], $category_key ); ?>><?php echo esc_html( $category_label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
								<p><?php echo esc_html( $wp_dialyra_audio_asset['file_name'] ); ?></p>
								<audio id="<?php echo esc_attr( 'wp-dialyra-audio-player-' . $wp_dialyra_audio_asset['id'] ); ?>" class="wp-dialyra-audio-player" preload="none" src="<?php echo esc_url( $wp_dialyra_stream_url ); ?>">
									<?php esc_html_e( 'Your browser does not support audio playback.', 'wp-dialyra' ); ?>
								</audio>
							</form>
							<em class="wp-dialyra-result wp-dialyra-result--success"><?php echo esc_html( $wp_dialyra_audio_asset['category'] ); ?></em>
							<div class="wp-dialyra-audio-list__actions">
								<button class="wp-dialyra-audio-action wp-dialyra-audio-action--stream" type="button" data-dialyra-audio-toggle="<?php echo esc_attr( 'wp-dialyra-audio-player-' . $wp_dialyra_audio_asset['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Play %s', 'wp-dialyra' ), $wp_dialyra_audio_asset['name'] ) ); ?>" title="<?php esc_attr_e( 'Play', 'wp-dialyra' ); ?>">
									<span class="dashicons dashicons-controls-play" aria-hidden="true"></span>
								</button>
								<button class="wp-dialyra-audio-action wp-dialyra-audio-action--edit" type="submit" form="<?php echo esc_attr( 'wp-dialyra-audio-update-' . $wp_dialyra_audio_asset['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Update %s', 'wp-dialyra' ), $wp_dialyra_audio_asset['name'] ) ); ?>" title="<?php esc_attr_e( 'Update', 'wp-dialyra' ); ?>">
									<span class="dashicons dashicons-saved" aria-hidden="true"></span>
								</button>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=audio' ) ); ?>">
									<?php wp_nonce_field( 'wp-dialyra-audio', 'wp_dialyra_audio_nonce' ); ?>
									<input type="hidden" name="wp_dialyra_audio_action" value="delete_audio">
									<input type="hidden" name="audio_asset_id" value="<?php echo esc_attr( $wp_dialyra_audio_asset['id'] ); ?>">
									<button class="wp-dialyra-audio-action wp-dialyra-audio-action--delete" type="submit" aria-label="<?php echo esc_attr( sprintf( __( 'Delete %s', 'wp-dialyra' ), $wp_dialyra_audio_asset['name'] ) ); ?>" title="<?php esc_attr_e( 'Delete', 'wp-dialyra' ); ?>">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</button>
								</form>
							</div>
						</article>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="wp-dialyra-empty-card">
						<span class="dashicons dashicons-format-audio" aria-hidden="true"></span>
						<div>
							<strong><?php esc_html_e( 'No audio uploaded yet', 'wp-dialyra' ); ?></strong>
							<p><?php esc_html_e( 'Upload your first prompt or hold music file to start building call flows with audio.', 'wp-dialyra' ); ?></p>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</section>
	</div>
</section>
