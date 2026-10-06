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
$wp_dialyra_business_manager = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_business_manager' ) ? $wp_dialyra_plugin->get_business_manager() : null;
$wp_dialyra_flow_manager = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_flow_manager' ) ? $wp_dialyra_plugin->get_flow_manager() : null;
$wp_dialyra_call_log_repository = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_call_log_repository' ) ? $wp_dialyra_plugin->get_call_log_repository() : null;
$wp_dialyra_audit_repository = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_audit_log_repository' ) ? $wp_dialyra_plugin->get_audit_log_repository() : null;
$wp_dialyra_business_id = class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
$wp_dialyra_webhook_health_state = class_exists( 'Dialyra_Webhook_Health_Check' ) ? Dialyra_Webhook_Health_Check::get_stored_status() : array();
$wp_dialyra_test_call_notice = null;
$wp_dialyra_test_call_notice_type = 'warning';
$wp_dialyra_test_phone = '';
$wp_dialyra_test_flow_id = $wp_dialyra_flow_manager && method_exists( $wp_dialyra_flow_manager, 'get_default_flow_id' ) ? $wp_dialyra_flow_manager->get_default_flow_id() : 0;
$wp_dialyra_agent_call_notice = null;
$wp_dialyra_agent_call_notice_type = 'warning';
$wp_dialyra_agent_from_extension = '1003';
$wp_dialyra_agent_to_type = 'extension';
$wp_dialyra_agent_to = '1004';
$wp_dialyra_agent_timeout_seconds = 30;
$wp_dialyra_webhook_test_notice = null;
$wp_dialyra_webhook_test_notice_type = 'warning';

$wp_dialyra_extract_items = static function ( $response ) {
	if ( ! $response || ! is_object( $response ) || ! method_exists( $response, 'is_successful' ) || ! $response->is_successful() || ! method_exists( $response, 'get_data' ) ) {
		return array();
	}

	$data = $response->get_data();
	$data = is_array( $data ) ? $data : array();

	if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
		$data = $data['data'];
	}

	foreach ( array( 'items', 'flows', 'results', 'data' ) as $container_key ) {
		if ( isset( $data[ $container_key ] ) && is_array( $data[ $container_key ] ) ) {
			return $data[ $container_key ];
		}
	}

	return isset( $data[0] ) && is_array( $data[0] ) ? $data : array();
};

$wp_dialyra_normalize_test_phone = static function ( $phone ) {
	$phone = trim( sanitize_text_field( $phone ) );

	if ( '' === $phone ) {
		return '';
	}

	$phone  = preg_replace( '/(?!^\+)[^\d]/', '', $phone );
	$digits = preg_replace( '/\D/', '', $phone );

	if ( strlen( $digits ) < 7 || strlen( $digits ) > 15 ) {
		return '';
	}

	return $phone;
};

$wp_dialyra_normalize_extension = static function ( $extension ) {
	$extension = preg_replace( '/\D/', '', sanitize_text_field( $extension ) );

	return preg_match( '/^\d{2,16}$/', $extension ) ? $extension : '';
};

if ( isset( $_POST['dialyra_test_action'] ) && 'run_test_call' === sanitize_key( wp_unslash( $_POST['dialyra_test_action'] ) ) ) {
	$wp_dialyra_test_phone = isset( $_POST['dialyra_test_phone'] ) ? $wp_dialyra_normalize_test_phone( wp_unslash( $_POST['dialyra_test_phone'] ) ) : '';
	$wp_dialyra_test_flow_id = isset( $_POST['dialyra_test_flow'] ) ? absint( wp_unslash( $_POST['dialyra_test_flow'] ) ) : 0;

	if ( ! isset( $_POST['wp_dialyra_test_tools_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_dialyra_test_tools_nonce'] ) ), 'wp-dialyra-test-tools' ) ) {
		$wp_dialyra_test_call_notice = __( 'Test call could not run because the security check failed.', 'wp-dialyra' );
		$wp_dialyra_test_call_notice_type = 'error';
	} elseif ( ! $wp_dialyra_api_endpoints || ! $wp_dialyra_business_manager ) {
		$wp_dialyra_test_call_notice = __( 'Test call service is not available.', 'wp-dialyra' );
		$wp_dialyra_test_call_notice_type = 'error';
	} elseif ( ! $wp_dialyra_business_id ) {
		$wp_dialyra_test_call_notice = __( 'Connect a Dialyra business before running a test call.', 'wp-dialyra' );
		$wp_dialyra_test_call_notice_type = 'error';
	} elseif ( '' === $wp_dialyra_test_phone ) {
		$wp_dialyra_test_call_notice = __( 'Enter a valid customer phone number before running a test call.', 'wp-dialyra' );
		$wp_dialyra_test_call_notice_type = 'error';
	} elseif ( ! $wp_dialyra_test_flow_id ) {
		$wp_dialyra_test_call_notice = __( 'Select a published Dialyra flow before running a test call.', 'wp-dialyra' );
		$wp_dialyra_test_call_notice_type = 'error';
	} else {
		$wp_dialyra_business_manager->ensure_site_access_token( $wp_dialyra_business_id );
		$wp_dialyra_token_data = $wp_dialyra_business_manager->get_site_access_token_data();
		$wp_dialyra_site_token = ! empty( $wp_dialyra_token_data['token'] ) && absint( $wp_dialyra_token_data['business_id'] ?? 0 ) === $wp_dialyra_business_id ? sanitize_text_field( $wp_dialyra_token_data['token'] ) : '';

		if ( '' === $wp_dialyra_site_token ) {
			$wp_dialyra_test_call_notice = __( 'Business access token is missing. Regenerate the access token from Settings, then try again.', 'wp-dialyra' );
			$wp_dialyra_test_call_notice_type = 'error';
		} else {
			$wp_dialyra_test_call_response = $wp_dialyra_api_endpoints->originate_call(
				array(
					'phone'             => $wp_dialyra_test_phone,
					'flow_id'           => $wp_dialyra_test_flow_id,
					'webhook_variables' => array(
						'order_id'     => 'test-call',
						'order_action' => 'none',
						'source'       => 'wp_dialyra_test_tools',
					),
				),
				$wp_dialyra_site_token
			);

			if ( $wp_dialyra_test_call_response && $wp_dialyra_test_call_response->is_successful() ) {
				$wp_dialyra_test_call_data = $wp_dialyra_test_call_response->get_data();
				$wp_dialyra_test_call_data = is_array( $wp_dialyra_test_call_data ) ? $wp_dialyra_test_call_data : array();
				$wp_dialyra_test_call_data = isset( $wp_dialyra_test_call_data['data'] ) && is_array( $wp_dialyra_test_call_data['data'] ) ? $wp_dialyra_test_call_data['data'] : $wp_dialyra_test_call_data;
				$wp_dialyra_test_call_id = ! empty( $wp_dialyra_test_call_data['call_session_id'] ) ? absint( $wp_dialyra_test_call_data['call_session_id'] ) : 0;

				if ( $wp_dialyra_call_log_repository && method_exists( $wp_dialyra_call_log_repository, 'log_originate_result' ) ) {
					$wp_dialyra_call_log_repository->log_originate_result(
						0,
						$wp_dialyra_test_call_response,
						array(
							'business_id' => $wp_dialyra_business_id,
							'flow_id'     => $wp_dialyra_test_flow_id,
							'phone'       => $wp_dialyra_test_phone,
							'source'      => 'test_call',
						)
					);
				}

				if ( $wp_dialyra_audit_repository && method_exists( $wp_dialyra_audit_repository, 'log' ) ) {
					$wp_dialyra_audit_repository->log(
						'test_call_originated',
						__( 'Dialyra test call originated successfully.', 'wp-dialyra' ),
						array(
							'business_id'     => $wp_dialyra_business_id,
							'flow_id'         => $wp_dialyra_test_flow_id,
							'phone'           => $wp_dialyra_test_phone,
							'call_session_id' => $wp_dialyra_test_call_id,
						),
						'success',
						'test_tools'
					);
				}

				$wp_dialyra_test_call_notice = $wp_dialyra_test_call_id ? sprintf( /* translators: %d: call session ID. */ __( 'Test call started successfully. Call session #%d.', 'wp-dialyra' ), $wp_dialyra_test_call_id ) : __( 'Test call started successfully.', 'wp-dialyra' );
				$wp_dialyra_test_call_notice_type = 'success';
			} else {
				if ( $wp_dialyra_call_log_repository && method_exists( $wp_dialyra_call_log_repository, 'log_originate_result' ) ) {
					$wp_dialyra_call_log_repository->log_originate_result(
						0,
						$wp_dialyra_test_call_response,
						array(
							'business_id' => $wp_dialyra_business_id,
							'flow_id'     => $wp_dialyra_test_flow_id,
							'phone'       => $wp_dialyra_test_phone,
							'source'      => 'test_call',
						)
					);
				}

				$wp_dialyra_test_call_notice = $wp_dialyra_test_call_response ? $wp_dialyra_test_call_response->get_message() : __( 'Test call could not be started.', 'wp-dialyra' );
				$wp_dialyra_test_call_notice_type = 'error';
			}
		}
	}
} elseif ( isset( $_POST['dialyra_test_action'] ) && 'run_agent_call' === sanitize_key( wp_unslash( $_POST['dialyra_test_action'] ) ) ) {
	$wp_dialyra_agent_from_extension = isset( $_POST['dialyra_agent_from_extension'] ) ? $wp_dialyra_normalize_extension( wp_unslash( $_POST['dialyra_agent_from_extension'] ) ) : '';
	$wp_dialyra_agent_to_type = isset( $_POST['dialyra_agent_to_type'] ) && 'external_number' === sanitize_key( wp_unslash( $_POST['dialyra_agent_to_type'] ) ) ? 'external_number' : 'extension';
	$wp_dialyra_agent_to = isset( $_POST['dialyra_agent_to'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_agent_to'] ) ) : '';
	$wp_dialyra_agent_timeout_seconds = isset( $_POST['dialyra_agent_timeout_seconds'] ) ? max( 5, absint( wp_unslash( $_POST['dialyra_agent_timeout_seconds'] ) ) ) : 30;
	$wp_dialyra_agent_target = 'extension' === $wp_dialyra_agent_to_type ? $wp_dialyra_normalize_extension( $wp_dialyra_agent_to ) : $wp_dialyra_normalize_test_phone( $wp_dialyra_agent_to );

	if ( ! isset( $_POST['wp_dialyra_test_tools_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_dialyra_test_tools_nonce'] ) ), 'wp-dialyra-test-tools' ) ) {
		$wp_dialyra_agent_call_notice = __( 'Agent call could not run because the security check failed.', 'wp-dialyra' );
		$wp_dialyra_agent_call_notice_type = 'error';
	} elseif ( ! $wp_dialyra_api_endpoints || ! method_exists( $wp_dialyra_api_endpoints, 'originate_agent_call' ) ) {
		$wp_dialyra_agent_call_notice = __( 'Agent call service is not available.', 'wp-dialyra' );
		$wp_dialyra_agent_call_notice_type = 'error';
	} elseif ( ! $wp_dialyra_business_id ) {
		$wp_dialyra_agent_call_notice = __( 'Connect a Dialyra business before running an agent call.', 'wp-dialyra' );
		$wp_dialyra_agent_call_notice_type = 'error';
	} elseif ( '' === $wp_dialyra_agent_from_extension ) {
		$wp_dialyra_agent_call_notice = __( 'Enter a valid source extension.', 'wp-dialyra' );
		$wp_dialyra_agent_call_notice_type = 'error';
	} elseif ( '' === $wp_dialyra_agent_target ) {
		$wp_dialyra_agent_call_notice = 'extension' === $wp_dialyra_agent_to_type ? __( 'Enter a valid destination extension.', 'wp-dialyra' ) : __( 'Enter a valid destination phone number.', 'wp-dialyra' );
		$wp_dialyra_agent_call_notice_type = 'error';
	} elseif ( 'extension' === $wp_dialyra_agent_to_type && $wp_dialyra_agent_from_extension === $wp_dialyra_agent_target ) {
		$wp_dialyra_agent_call_notice = __( 'From extension and destination extension must be different.', 'wp-dialyra' );
		$wp_dialyra_agent_call_notice_type = 'error';
	} else {
		$wp_dialyra_agent_to = $wp_dialyra_agent_target;
		$wp_dialyra_agent_call_response = $wp_dialyra_api_endpoints->originate_agent_call(
			array(
				'business_id'      => $wp_dialyra_business_id,
				'from_extension'   => $wp_dialyra_agent_from_extension,
				'to'               => $wp_dialyra_agent_target,
				'to_type'          => $wp_dialyra_agent_to_type,
				'timeout_seconds'  => $wp_dialyra_agent_timeout_seconds,
			)
		);

		if ( $wp_dialyra_audit_repository && method_exists( $wp_dialyra_audit_repository, 'log' ) ) {
			$wp_dialyra_agent_call_data = $wp_dialyra_agent_call_response instanceof Dialyra_API_Response ? $wp_dialyra_agent_call_response->get_data() : array();
			$wp_dialyra_audit_repository->log(
				$wp_dialyra_agent_call_response && $wp_dialyra_agent_call_response->is_successful() ? 'agent_call_originated' : 'agent_call_failed',
				$wp_dialyra_agent_call_response && $wp_dialyra_agent_call_response->is_successful() ? __( 'Dialyra agent call originated successfully.', 'wp-dialyra' ) : __( 'Dialyra agent call could not be originated.', 'wp-dialyra' ),
				array(
					'business_id'    => $wp_dialyra_business_id,
					'from_extension' => $wp_dialyra_agent_from_extension,
					'to'             => $wp_dialyra_agent_target,
					'to_type'        => $wp_dialyra_agent_to_type,
					'status_code'    => $wp_dialyra_agent_call_response instanceof Dialyra_API_Response ? absint( $wp_dialyra_agent_call_response->get_status_code() ) : 0,
					'response'       => is_array( $wp_dialyra_agent_call_data ) ? $wp_dialyra_agent_call_data : array(),
				),
				$wp_dialyra_agent_call_response && $wp_dialyra_agent_call_response->is_successful() ? 'success' : 'error',
				'test_tools'
			);
		}

		if ( $wp_dialyra_agent_call_response && $wp_dialyra_agent_call_response->is_successful() ) {
			$wp_dialyra_agent_call_notice = __( 'Agent call started successfully. The source extension should ring first.', 'wp-dialyra' );
			$wp_dialyra_agent_call_notice_type = 'success';
		} else {
			$wp_dialyra_agent_call_notice = $wp_dialyra_agent_call_response ? $wp_dialyra_agent_call_response->get_message() : __( 'Agent call could not be started.', 'wp-dialyra' );
			$wp_dialyra_agent_call_notice_type = 'error';
		}
	}
} elseif ( isset( $_POST['dialyra_test_action'] ) && 'webhook_health_check' === sanitize_key( wp_unslash( $_POST['dialyra_test_action'] ) ) ) {
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

$wp_dialyra_flow_options = array();

if ( $wp_dialyra_flow_manager && $wp_dialyra_business_id ) {
	$wp_dialyra_flows_response = $wp_dialyra_flow_manager->get_flows( array( 'business_id' => $wp_dialyra_business_id ) );

	foreach ( $wp_dialyra_extract_items( $wp_dialyra_flows_response ) as $wp_dialyra_flow ) {
		$wp_dialyra_flow = is_array( $wp_dialyra_flow ) ? $wp_dialyra_flow : array();
		$wp_dialyra_flow_id = isset( $wp_dialyra_flow['id'] ) ? absint( $wp_dialyra_flow['id'] ) : 0;
		$wp_dialyra_flow_status = ! empty( $wp_dialyra_flow['status'] ) ? sanitize_key( $wp_dialyra_flow['status'] ) : '';

		if ( ! $wp_dialyra_flow_id || 'published' !== $wp_dialyra_flow_status ) {
			continue;
		}

		$wp_dialyra_flow_options[ $wp_dialyra_flow_id ] = ! empty( $wp_dialyra_flow['name'] ) ? sanitize_text_field( $wp_dialyra_flow['name'] ) : sprintf( /* translators: %d: flow ID. */ __( 'Flow #%d', 'wp-dialyra' ), $wp_dialyra_flow_id );
	}
}

if ( empty( $wp_dialyra_flow_options ) && $wp_dialyra_flow_manager && method_exists( $wp_dialyra_flow_manager, 'get_default_flow_id' ) ) {
	$wp_dialyra_default_flow_id = $wp_dialyra_flow_manager->get_default_flow_id();
	$wp_dialyra_default_flow_data = method_exists( $wp_dialyra_flow_manager, 'get_default_flow_data' ) ? $wp_dialyra_flow_manager->get_default_flow_data() : array();

	$wp_dialyra_default_flow_status = ! empty( $wp_dialyra_default_flow_data['status'] ) ? sanitize_key( $wp_dialyra_default_flow_data['status'] ) : 'published';

	if ( $wp_dialyra_default_flow_id && 'published' === $wp_dialyra_default_flow_status ) {
		$wp_dialyra_flow_options[ $wp_dialyra_default_flow_id ] = ! empty( $wp_dialyra_default_flow_data['name'] ) ? sanitize_text_field( $wp_dialyra_default_flow_data['name'] ) : sprintf( /* translators: %d: flow ID. */ __( 'Default flow #%d', 'wp-dialyra' ), $wp_dialyra_default_flow_id );
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

			<?php if ( ! empty( $wp_dialyra_test_call_notice ) ) : ?>
				<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--<?php echo esc_attr( $wp_dialyra_test_call_notice_type ); ?>">
					<span class="dashicons <?php echo 'success' === $wp_dialyra_test_call_notice_type ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
					<p><?php echo esc_html( $wp_dialyra_test_call_notice ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=test-tools' ) ); ?>">
				<?php wp_nonce_field( 'wp-dialyra-test-tools', 'wp_dialyra_test_tools_nonce' ); ?>
				<input type="hidden" name="dialyra_test_action" value="run_test_call">

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-test-phone"><?php esc_html_e( 'Customer phone', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-test-phone" name="dialyra_test_phone" type="tel" value="<?php echo esc_attr( $wp_dialyra_test_phone ); ?>" placeholder="<?php esc_attr_e( '+8801XXXXXXXXX', 'wp-dialyra' ); ?>" required>
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-test-flow"><?php esc_html_e( 'Calling flow', 'wp-dialyra' ); ?></label>
					<select id="wp-dialyra-test-flow" name="dialyra_test_flow" required>
						<?php if ( empty( $wp_dialyra_flow_options ) ) : ?>
							<option value=""><?php esc_html_e( 'No published flow available', 'wp-dialyra' ); ?></option>
						<?php else : ?>
							<?php foreach ( $wp_dialyra_flow_options as $wp_dialyra_flow_id => $wp_dialyra_flow_name ) : ?>
								<option value="<?php echo esc_attr( $wp_dialyra_flow_id ); ?>" <?php selected( $wp_dialyra_test_flow_id, $wp_dialyra_flow_id ); ?>>
									<?php echo esc_html( sprintf( /* translators: 1: flow name, 2: flow ID. */ __( '%1$s · #%2$d', 'wp-dialyra' ), $wp_dialyra_flow_name, $wp_dialyra_flow_id ) ); ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>

				<div class="wp-dialyra-test-summary">
					<div>
						<span><?php esc_html_e( 'Mode', 'wp-dialyra' ); ?></span>
						<strong><?php esc_html_e( 'Manual test', 'wp-dialyra' ); ?></strong>
					</div>
					<div>
						<span><?php esc_html_e( 'Expected result', 'wp-dialyra' ); ?></span>
						<strong><?php esc_html_e( 'Call started by Dialyra', 'wp-dialyra' ); ?></strong>
					</div>
				</div>

				<div class="wp-dialyra-test-card__footer">
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" <?php disabled( empty( $wp_dialyra_flow_options ) ); ?>><?php esc_html_e( 'Run Test Call', 'wp-dialyra' ); ?></button>
				</div>
			</form>
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

			<?php if ( ! empty( $wp_dialyra_agent_call_notice ) ) : ?>
				<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--<?php echo esc_attr( $wp_dialyra_agent_call_notice_type ); ?>">
					<span class="dashicons <?php echo 'success' === $wp_dialyra_agent_call_notice_type ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
					<p><?php echo esc_html( $wp_dialyra_agent_call_notice ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=test-tools' ) ); ?>">
				<?php wp_nonce_field( 'wp-dialyra-test-tools', 'wp_dialyra_test_tools_nonce' ); ?>
				<input type="hidden" name="dialyra_test_action" value="run_agent_call">

				<div class="wp-dialyra-agent-call-grid">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-agent-from-extension"><?php esc_html_e( 'From extension', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-agent-from-extension" name="dialyra_agent_from_extension" type="text" inputmode="numeric" value="<?php echo esc_attr( $wp_dialyra_agent_from_extension ); ?>" required>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-agent-to-type"><?php esc_html_e( 'Target type', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-agent-to-type" name="dialyra_agent_to_type">
							<option value="extension" <?php selected( $wp_dialyra_agent_to_type, 'extension' ); ?>><?php esc_html_e( 'Extension', 'wp-dialyra' ); ?></option>
							<option value="external_number" <?php selected( $wp_dialyra_agent_to_type, 'external_number' ); ?>><?php esc_html_e( 'External number', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-agent-to"><?php esc_html_e( 'To', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-agent-to" name="dialyra_agent_to" type="text" value="<?php echo esc_attr( $wp_dialyra_agent_to ); ?>" required>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-agent-timeout"><?php esc_html_e( 'Timeout seconds', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-agent-timeout" name="dialyra_agent_timeout_seconds" type="number" min="5" value="<?php echo esc_attr( $wp_dialyra_agent_timeout_seconds ); ?>">
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
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Originate Agent Call', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</section>
	</div>
</section>
