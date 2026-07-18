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

$wp_dialyra_plugin        = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
$wp_dialyra_api_endpoints = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_api_endpoints() : null;
$wp_dialyra_flow_manager  = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_flow_manager() : null;
$wp_dialyra_business_manager = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_business_manager' ) ? $wp_dialyra_plugin->get_business_manager() : null;
$wp_dialyra_flow_product_manager = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_flow_product_assignment_manager' ) ? $wp_dialyra_plugin->get_flow_product_assignment_manager() : null;
$wp_dialyra_business_id   = class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
$wp_dialyra_error         = '';
$wp_dialyra_success       = '';
$wp_dialyra_edit_flow_id  = isset( $_GET['flow_id'] ) ? absint( wp_unslash( $_GET['flow_id'] ) ) : 0;
$wp_dialyra_source_option = defined( 'WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX' ) && $wp_dialyra_business_id && $wp_dialyra_edit_flow_id ? WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX . $wp_dialyra_business_id . '_' . $wp_dialyra_edit_flow_id : '';
$wp_dialyra_draft_json    = defined( 'WP_DIALYRA_OPTION_FLOW_DRAFT_JSON' ) ? get_option( WP_DIALYRA_OPTION_FLOW_DRAFT_JSON, '' ) : '';
$wp_dialyra_draft_flow    = array();
$wp_dialyra_validation    = array(
	array( 'type' => 'success', 'message' => __( 'Flow name is set.', 'wp-dialyra' ) ),
	array( 'type' => 'success', 'message' => __( 'Start menu selected.', 'wp-dialyra' ) ),
	array( 'type' => 'warning', 'message' => __( 'Preview warnings appear here before publish.', 'wp-dialyra' ) ),
);
$wp_dialyra_audio_assets  = array();
$wp_dialyra_departments   = array();

$wp_dialyra_extract_response_items = static function ( $response, $keys ) {
	if ( ! $response || ! is_object( $response ) || ! method_exists( $response, 'is_successful' ) || ! $response->is_successful() || ! method_exists( $response, 'get_data' ) ) {
		return array();
	}

	$data = $response->get_data();
	$data = is_array( $data ) ? $data : array();

	if ( isset( $data['data'] ) && is_array( $data['data'] ) && ! isset( $data['items'] ) ) {
		$data = $data['data'];
	}

	foreach ( $keys as $key ) {
		if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
			return $data[ $key ];
		}
	}

	return isset( $data[0] ) && is_array( $data[0] ) ? $data : array();
};

$wp_dialyra_create_fallback_flow = static function ( $flow ) {
	$flow = is_array( $flow ) ? $flow : array();

	return array(
		'name'            => ! empty( $flow['name'] ) ? sanitize_text_field( $flow['name'] ) : __( 'Untitled flow', 'wp-dialyra' ),
		'description'     => ! empty( $flow['description'] ) ? sanitize_text_field( $flow['description'] ) : '',
		'startMenuId'     => 'main_menu',
		'menus'           => array(
			array(
				'id'                         => 'main_menu',
				'name'                       => __( 'Main Menu', 'wp-dialyra' ),
				'isStart'                    => true,
				'description'                => '',
				'customerInstructionMessage' => array(
					'type'     => 'tts',
					'message'  => __( 'Review and update this customer instruction message.', 'wp-dialyra' ),
					'language' => 'en',
					'provider' => 'google',
					'voice'    => 'gtts:free',
				),
				'inputSettings'              => array(
					'maxDigits'         => 1,
					'timeoutSeconds'    => 5,
					'maxInvalidRetries' => 2,
					'maxTimeoutRetries' => 1,
				),
				'dtmfActions'                => array(
					array(
						'inputKey'        => '1',
						'responseMessage' => array( 'type' => 'none' ),
						'businessAction'  => array( 'type' => 'no_action' ),
						'nextStep'        => array( 'type' => 'hangup' ),
					),
				),
				'invalidInputHandling'       => array(
					'responseMessage'             => array( 'type' => 'none' ),
					'afterMaxInvalidRetryAction'  => array( 'type' => 'repeat_current_menu' ),
				),
				'timeoutHandling'            => array(
					'responseMessage' => array( 'type' => 'none' ),
					'nextStep'        => array( 'type' => 'repeat_current_menu' ),
				),
			),
		),
		'transferFailed'  => array(
			'responseMessage' => array( 'type' => 'none' ),
			'nextStep'        => array( 'type' => 'hangup' ),
		),
		'transferTimeout' => array(
			'responseMessage' => array( 'type' => 'none' ),
			'nextStep'        => array( 'type' => 'hangup' ),
		),
	);
};

if ( $wp_dialyra_source_option ) {
	$wp_dialyra_edit_source_json = get_option( $wp_dialyra_source_option, '' );

	if ( is_string( $wp_dialyra_edit_source_json ) && '' !== $wp_dialyra_edit_source_json ) {
		$wp_dialyra_draft_json = $wp_dialyra_edit_source_json;
	} else {
		$wp_dialyra_existing_flow_response = $wp_dialyra_flow_manager ? $wp_dialyra_flow_manager->get_flow( $wp_dialyra_edit_flow_id ) : false;

		if ( $wp_dialyra_existing_flow_response && $wp_dialyra_existing_flow_response->is_successful() ) {
			$wp_dialyra_existing_flow_data = $wp_dialyra_existing_flow_response->get_data();

			if ( isset( $wp_dialyra_existing_flow_data['data'] ) && is_array( $wp_dialyra_existing_flow_data['data'] ) ) {
				$wp_dialyra_existing_flow_data = $wp_dialyra_existing_flow_data['data'];
			}

			if ( isset( $wp_dialyra_existing_flow_data['flow'] ) && is_array( $wp_dialyra_existing_flow_data['flow'] ) ) {
				$wp_dialyra_existing_flow_data = $wp_dialyra_existing_flow_data['flow'];
			}

			$wp_dialyra_nodes_response = $wp_dialyra_flow_manager->get_flow_nodes( $wp_dialyra_edit_flow_id );
			$wp_dialyra_edges_response = $wp_dialyra_flow_manager->get_flow_edges( $wp_dialyra_edit_flow_id );
			$wp_dialyra_graph_loaded   = $wp_dialyra_nodes_response && $wp_dialyra_nodes_response->is_successful() && $wp_dialyra_edges_response && $wp_dialyra_edges_response->is_successful();

			if ( $wp_dialyra_graph_loaded && class_exists( 'Dialyra_Flow_Graph_Decompiler' ) ) {
				$wp_dialyra_node_items = $wp_dialyra_extract_response_items( $wp_dialyra_nodes_response, array( 'items', 'nodes', 'data' ) );
				$wp_dialyra_edge_items = $wp_dialyra_extract_response_items( $wp_dialyra_edges_response, array( 'items', 'edges', 'data' ) );
				$wp_dialyra_decompiler = new Dialyra_Flow_Graph_Decompiler();
				$wp_dialyra_draft_flow = $wp_dialyra_decompiler->decompile( $wp_dialyra_existing_flow_data, $wp_dialyra_node_items, $wp_dialyra_edge_items );
				$wp_dialyra_draft_json = wp_json_encode( $wp_dialyra_draft_flow );
				$wp_dialyra_validation[] = array(
					'type'    => 'success',
					'message' => __( 'Flow graph reconstructed into editable menus from Dialyra nodes and edges.', 'wp-dialyra' ),
				);
			} else {
				$wp_dialyra_draft_flow = $wp_dialyra_create_fallback_flow( $wp_dialyra_existing_flow_data );
				$wp_dialyra_draft_json = wp_json_encode( $wp_dialyra_draft_flow );
				$wp_dialyra_validation[] = array(
					'type'    => 'warning',
					'message' => __( 'Dialyra graph data could not be loaded, so the builder started from a clean editable Main Menu using the existing flow name and description.', 'wp-dialyra' ),
				);
			}
		} else {
			$wp_dialyra_error = $wp_dialyra_existing_flow_response && method_exists( $wp_dialyra_existing_flow_response, 'get_message' ) ? $wp_dialyra_existing_flow_response->get_message() : __( 'Unable to load this flow for editing.', 'wp-dialyra' );
			$wp_dialyra_edit_flow_id = 0;
			$wp_dialyra_source_option = '';
		}
	}
}

if ( is_string( $wp_dialyra_draft_json ) && '' !== $wp_dialyra_draft_json ) {
	$wp_dialyra_decoded_draft = json_decode( $wp_dialyra_draft_json, true );
	$wp_dialyra_draft_flow    = JSON_ERROR_NONE === json_last_error() && is_array( $wp_dialyra_decoded_draft ) ? $wp_dialyra_decoded_draft : array();
}

$wp_dialyra_add_validation_messages = static function ( $messages, $type ) use ( &$wp_dialyra_validation ) {
	foreach ( $messages as $message ) {
		if ( is_array( $message ) && ! empty( $message['code'] ) && 'ORPHAN_NODE' === $message['code'] ) {
			continue;
		}

		if ( is_array( $message ) ) {
			if ( ! empty( $message['code'] ) && 'UNREACHABLE_MENU' === $message['code'] && ! empty( $message['menuId'] ) ) {
				$message = sprintf(
					/* translators: %s: menu ID. */
					__( 'Menu "%s" is not connected yet. Add a DTMF action with Next step set to Go To Menu if customers should reach it.', 'wp-dialyra' ),
					$message['menuId']
				);
			} else {
				$message = ! empty( $message['message'] ) ? $message['message'] : wp_json_encode( $message );
			}
		}

		if ( $message ) {
			$wp_dialyra_validation[] = array(
				'type'    => $type,
				'message' => sanitize_text_field( $message ),
			);
		}
	}
};

if ( $wp_dialyra_api_endpoints && $wp_dialyra_business_id ) {
	$wp_dialyra_audio_response = $wp_dialyra_api_endpoints->get_audio_assets( array( 'business_id' => $wp_dialyra_business_id ) );
	$wp_dialyra_audio_assets   = array_filter(
		array_map(
			static function ( $asset ) {
				$asset = is_array( $asset ) ? $asset : array();

				return array(
					'id'   => isset( $asset['id'] ) ? absint( $asset['id'] ) : 0,
					'name' => ! empty( $asset['name'] ) ? sanitize_text_field( $asset['name'] ) : __( 'Untitled audio', 'wp-dialyra' ),
					'type' => ! empty( $asset['type'] ) ? sanitize_key( $asset['type'] ) : '',
				);
			},
			$wp_dialyra_extract_response_items( $wp_dialyra_audio_response, array( 'items', 'audio_assets', 'assets', 'data' ) )
		),
		static function ( $asset ) {
			return ! empty( $asset['id'] ) && 'upload' === $asset['type'];
		}
	);

	$wp_dialyra_department_response = $wp_dialyra_api_endpoints->get_departments( array( 'business_id' => $wp_dialyra_business_id ) );
	$wp_dialyra_departments         = array_filter(
		array_map(
			static function ( $department ) {
				$department = is_array( $department ) ? $department : array();

				return array(
					'id'   => isset( $department['id'] ) ? absint( $department['id'] ) : 0,
					'name' => ! empty( $department['name'] ) ? sanitize_text_field( $department['name'] ) : __( 'Untitled department', 'wp-dialyra' ),
				);
			},
			$wp_dialyra_extract_response_items( $wp_dialyra_department_response, array( 'items', 'departments', 'data' ) )
		),
		static function ( $department ) {
			return ! empty( $department['id'] );
		}
	);
}

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['wp_dialyra_flow_builder_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_dialyra_flow_builder_nonce'] ) ), 'wp_dialyra_flow_builder' ) ) {
	$wp_dialyra_action = isset( $_POST['wp_dialyra_flow_builder_action'] ) ? sanitize_key( wp_unslash( $_POST['wp_dialyra_flow_builder_action'] ) ) : '';
	$wp_dialyra_post_edit_flow_id = isset( $_POST['edit_flow_id'] ) ? absint( wp_unslash( $_POST['edit_flow_id'] ) ) : 0;

	if ( in_array( $wp_dialyra_action, array( 'save_draft', 'publish_flow' ), true ) ) {
		$wp_dialyra_validation = array();
		$wp_dialyra_raw_flow   = isset( $_POST['frontend_flow_json'] ) ? wp_unslash( $_POST['frontend_flow_json'] ) : '';
		$wp_dialyra_ui_flow    = json_decode( $wp_dialyra_raw_flow, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $wp_dialyra_ui_flow ) ) {
			$wp_dialyra_error = __( 'Flow builder data is invalid. Please refresh the page and try again.', 'wp-dialyra' );
		} else {
			$wp_dialyra_draft_json = wp_json_encode( $wp_dialyra_ui_flow );
			$wp_dialyra_draft_flow = $wp_dialyra_ui_flow;

			if ( $wp_dialyra_post_edit_flow_id && defined( 'WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX' ) && $wp_dialyra_business_id ) {
				update_option( WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX . $wp_dialyra_business_id . '_' . $wp_dialyra_post_edit_flow_id, $wp_dialyra_draft_json, false );
			} elseif ( defined( 'WP_DIALYRA_OPTION_FLOW_DRAFT_JSON' ) ) {
				update_option( WP_DIALYRA_OPTION_FLOW_DRAFT_JSON, $wp_dialyra_draft_json, false );
			}

			if ( 'save_draft' === $wp_dialyra_action ) {
				$wp_dialyra_success      = __( 'Flow draft saved successfully.', 'wp-dialyra' );
				$wp_dialyra_validation[] = array( 'type' => 'success', 'message' => $wp_dialyra_post_edit_flow_id ? __( 'Edit draft is saved for this flow.', 'wp-dialyra' ) : __( 'Draft is saved locally in WordPress.', 'wp-dialyra' ) );
			}
		}
	}

	if ( 'publish_flow' === $wp_dialyra_action && empty( $wp_dialyra_error ) ) {

		if ( ! $wp_dialyra_business_id ) {
			$wp_dialyra_error = __( 'Please complete business setup before publishing a flow.', 'wp-dialyra' );
		} elseif ( ! $wp_dialyra_flow_manager || ! class_exists( 'Dialyra_Frontend_Flow_Json_Builder' ) || ! class_exists( 'Dialyra_Flow_Compiler' ) ) {
			$wp_dialyra_error = __( 'Flow services are not available. Please reload the plugin and try again.', 'wp-dialyra' );
		} else {
			$wp_dialyra_frontend_builder = new Dialyra_Frontend_Flow_Json_Builder();
			$wp_dialyra_built_flow       = $wp_dialyra_frontend_builder->build( $wp_dialyra_ui_flow );

			if ( empty( $wp_dialyra_built_flow['valid'] ) ) {
				$wp_dialyra_error = __( 'Please fix the highlighted flow issues before publishing.', 'wp-dialyra' );
				$wp_dialyra_add_validation_messages( $wp_dialyra_built_flow['errors'] ?? array(), 'danger' );
			} else {
				$wp_dialyra_compiler      = new Dialyra_Flow_Compiler();
				$wp_dialyra_compiled_flow = $wp_dialyra_compiler->compile( $wp_dialyra_built_flow['flow'], $wp_dialyra_business_id );

				$wp_dialyra_add_validation_messages( $wp_dialyra_compiled_flow['warnings'] ?? array(), 'warning' );

				if ( empty( $wp_dialyra_compiled_flow['valid'] ) ) {
					$wp_dialyra_error = __( 'Compiled flow failed validation. Please review the flow choices.', 'wp-dialyra' );
					$wp_dialyra_add_validation_messages( $wp_dialyra_compiled_flow['errors'] ?? array(), 'danger' );
				} else {
					$wp_dialyra_publish_response = $wp_dialyra_flow_manager->create_and_publish_flow( $wp_dialyra_compiled_flow['payload'] );

					if ( $wp_dialyra_publish_response && $wp_dialyra_publish_response->is_successful() ) {
						$wp_dialyra_response_data = $wp_dialyra_publish_response->get_data();
						$wp_dialyra_flow_data     = isset( $wp_dialyra_response_data['flow'] ) && is_array( $wp_dialyra_response_data['flow'] ) ? $wp_dialyra_response_data['flow'] : array();
						$wp_dialyra_flow_id       = ! empty( $wp_dialyra_flow_data['id'] ) ? absint( $wp_dialyra_flow_data['id'] ) : 0;

						if ( $wp_dialyra_flow_id && defined( 'WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX' ) ) {
							update_option( WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX . $wp_dialyra_business_id . '_' . $wp_dialyra_flow_id, $wp_dialyra_draft_json, false );
						}

						if ( $wp_dialyra_post_edit_flow_id && $wp_dialyra_flow_id && $wp_dialyra_post_edit_flow_id !== $wp_dialyra_flow_id ) {
							$wp_dialyra_was_default = method_exists( $wp_dialyra_flow_manager, 'get_default_flow_id' ) && $wp_dialyra_post_edit_flow_id === $wp_dialyra_flow_manager->get_default_flow_id();

							if ( $wp_dialyra_was_default ) {
								$wp_dialyra_flow_manager->set_default_flow( $wp_dialyra_flow_id );

								if ( $wp_dialyra_business_manager && method_exists( $wp_dialyra_business_manager, 'save_setup_settings' ) ) {
									$wp_dialyra_business_manager->save_setup_settings(
										array(
											'business_id'     => $wp_dialyra_business_id,
											'default_flow_id' => $wp_dialyra_flow_id,
										)
									);
								}
							}

							if ( $wp_dialyra_flow_product_manager ) {
								$wp_dialyra_existing_assignments = $wp_dialyra_flow_product_manager->get_assignments_by_flow( $wp_dialyra_business_id );
								$wp_dialyra_existing_products    = ! empty( $wp_dialyra_existing_assignments[ $wp_dialyra_post_edit_flow_id ] ) && is_array( $wp_dialyra_existing_assignments[ $wp_dialyra_post_edit_flow_id ] ) ? $wp_dialyra_existing_assignments[ $wp_dialyra_post_edit_flow_id ] : array();

								if ( ! empty( $wp_dialyra_existing_products ) ) {
									$wp_dialyra_flow_product_manager->set_flow_products( $wp_dialyra_business_id, $wp_dialyra_flow_id, $wp_dialyra_existing_products );
								}

								$wp_dialyra_flow_product_manager->delete_flow_assignments( $wp_dialyra_business_id, $wp_dialyra_post_edit_flow_id );
							}

							$wp_dialyra_flow_manager->delete_flow( $wp_dialyra_post_edit_flow_id );

							if ( defined( 'WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX' ) ) {
								delete_option( WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX . $wp_dialyra_business_id . '_' . $wp_dialyra_post_edit_flow_id );
							}
						}

						if ( defined( 'WP_DIALYRA_OPTION_FLOW_DRAFT_JSON' ) ) {
							delete_option( WP_DIALYRA_OPTION_FLOW_DRAFT_JSON );
						}
						$wp_dialyra_draft_json    = '';
						$wp_dialyra_draft_flow    = array();
						$wp_dialyra_edit_flow_id  = 0;
						$wp_dialyra_success       = $wp_dialyra_post_edit_flow_id ? sprintf( __( 'Flow updated and published successfully. New Flow ID: %d.', 'wp-dialyra' ), $wp_dialyra_flow_id ) : ( $wp_dialyra_flow_id ? sprintf( __( 'Flow created and published successfully. Flow ID: %d.', 'wp-dialyra' ), $wp_dialyra_flow_id ) : __( 'Flow created and published successfully.', 'wp-dialyra' ) );
						$wp_dialyra_validation    = array(
							array( 'type' => 'success', 'message' => $wp_dialyra_post_edit_flow_id ? __( 'Dialyra published the updated flow and archived the previous version.', 'wp-dialyra' ) : __( 'Dialyra accepted and published the compiled flow. Local draft was removed.', 'wp-dialyra' ) ),
						);
					} else {
						$wp_dialyra_error = $wp_dialyra_publish_response ? $wp_dialyra_publish_response->get_message() : __( 'Flow publish failed.', 'wp-dialyra' );
						if ( $wp_dialyra_publish_response && method_exists( $wp_dialyra_publish_response, 'get_errors' ) ) {
							$wp_dialyra_add_validation_messages( $wp_dialyra_publish_response->get_errors(), 'danger' );
						}
					}
				}
			}
		}
	}
}
?>

<form class="wp-dialyra-flow-builder" method="post" data-dialyra-flow-builder-form>
	<?php wp_nonce_field( 'wp_dialyra_flow_builder', 'wp_dialyra_flow_builder_nonce' ); ?>
	<input id="wp-dialyra-flow-builder-action" type="hidden" name="wp_dialyra_flow_builder_action" value="publish_flow">
	<input type="hidden" name="edit_flow_id" value="<?php echo esc_attr( $wp_dialyra_edit_flow_id ); ?>">
	<textarea id="wp-dialyra-frontend-flow-json" name="frontend_flow_json" hidden></textarea>
	<script id="wp-dialyra-flow-draft-json" type="application/json"><?php echo wp_json_encode( $wp_dialyra_draft_flow, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>

	<div class="wp-dialyra-flow-builder__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Flow Builder', 'wp-dialyra' ); ?></p>
			<h2><?php echo esc_html( $wp_dialyra_edit_flow_id ? __( 'Edit this customer call flow safely.', 'wp-dialyra' ) : __( 'Build a customer-friendly IVR menu with guided choices.', 'wp-dialyra' ) ); ?></h2>
			<p><?php echo esc_html( $wp_dialyra_edit_flow_id ? __( 'Publishing an edit creates the updated Dialyra flow, carries over default/product targeting, and archives the old one to avoid duplicates.', 'wp-dialyra' ) : __( 'Create menus, collect keypad choices, define order actions, and preview the full call path before publishing.', 'wp-dialyra' ) ); ?></p>
		</div>

		<div class="wp-dialyra-flow-builder__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flows' ) ); ?>"><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></a>
			<button class="wp-dialyra-button wp-dialyra-button--ghost" type="submit" data-dialyra-flow-action="save_draft"><?php esc_html_e( 'Save Draft', 'wp-dialyra' ); ?></button>
			<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" data-dialyra-flow-action="publish_flow"><?php echo esc_html( $wp_dialyra_edit_flow_id ? __( 'Publish Update', 'wp-dialyra' ) : __( 'Publish', 'wp-dialyra' ) ); ?></button>
		</div>
	</div>

	<?php if ( ! empty( $wp_dialyra_error ) ) : ?>
		<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--error">
			<span class="dashicons dashicons-warning" aria-hidden="true"></span>
			<p><?php echo esc_html( $wp_dialyra_error ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $wp_dialyra_success ) ) : ?>
		<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--success">
			<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
			<p><?php echo esc_html( $wp_dialyra_success ); ?></p>
		</div>
	<?php endif; ?>

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
							<p><?php esc_html_e( 'Main Menu is the active start menu for this flow.', 'wp-dialyra' ); ?></p>
						</div>
					</div>
					<button id="wp-dialyra-add-menu" class="wp-dialyra-flow-icon-button wp-dialyra-flow-icon-button--add" type="button" aria-label="<?php esc_attr_e( 'Add menu', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Add menu', 'wp-dialyra' ); ?>">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
					</button>
				</div>

				<div class="wp-dialyra-menu-list" data-dialyra-menu-list>
					<article class="wp-dialyra-menu-list__item wp-dialyra-menu-list__item--selected" data-dialyra-menu-id="main_menu">
						<button type="button">
							<strong><?php esc_html_e( 'Main Menu', 'wp-dialyra' ); ?></strong>
							<small><?php esc_html_e( 'Start menu', 'wp-dialyra' ); ?></small>
						</button>
						<em class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'Valid', 'wp-dialyra' ); ?></em>
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
							<?php if ( ! empty( $wp_dialyra_audio_assets ) ) : ?>
								<?php foreach ( $wp_dialyra_audio_assets as $wp_dialyra_audio_asset ) : ?>
									<option value="<?php echo esc_attr( $wp_dialyra_audio_asset['id'] ); ?>"><?php echo esc_html( $wp_dialyra_audio_asset['name'] ); ?></option>
								<?php endforeach; ?>
							<?php else : ?>
								<option value=""><?php esc_html_e( 'No audio assets available', 'wp-dialyra' ); ?></option>
							<?php endif; ?>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-menu-language"><?php esc_html_e( 'Language', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-menu-language" name="menu_tts_language">
							<option value="bn"><?php esc_html_e( 'Bangla', 'wp-dialyra' ); ?></option>
							<option value="en" selected><?php esc_html_e( 'English', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-menu-voice"><?php esc_html_e( 'Voice', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-menu-voice" name="menu_tts_voice">
							<option value="gtts:free"><?php esc_html_e( 'Standard voice', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-menu-provider"><?php esc_html_e( 'Provider', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-menu-provider" name="menu_tts_provider">
							<option value="google"><?php esc_html_e( 'Google TTS', 'wp-dialyra' ); ?></option>
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
											<option value="bn"><?php esc_html_e( 'Bangla', 'wp-dialyra' ); ?></option>
											<option value="en" selected><?php esc_html_e( 'English', 'wp-dialyra' ); ?></option>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
										<label><?php esc_html_e( 'Voice', 'wp-dialyra' ); ?></label>
										<select name="response_tts_voice_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
											<option value="gtts:free"><?php esc_html_e( 'Standard voice', 'wp-dialyra' ); ?></option>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
										<label><?php esc_html_e( 'Provider', 'wp-dialyra' ); ?></label>
										<select name="response_tts_provider_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
											<option value="google"><?php esc_html_e( 'Google TTS', 'wp-dialyra' ); ?></option>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-show-for="audio">
										<label><?php esc_html_e( 'Audio asset', 'wp-dialyra' ); ?></label>
										<select name="response_audio_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
											<?php if ( ! empty( $wp_dialyra_audio_assets ) ) : ?>
												<?php foreach ( $wp_dialyra_audio_assets as $wp_dialyra_audio_asset ) : ?>
													<option value="<?php echo esc_attr( $wp_dialyra_audio_asset['id'] ); ?>"><?php echo esc_html( $wp_dialyra_audio_asset['name'] ); ?></option>
												<?php endforeach; ?>
											<?php else : ?>
												<option value=""><?php esc_html_e( 'No audio assets available', 'wp-dialyra' ); ?></option>
											<?php endif; ?>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-show-for="transfer_department">
										<label><?php esc_html_e( 'Department', 'wp-dialyra' ); ?></label>
										<select name="department_target_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
											<?php if ( ! empty( $wp_dialyra_departments ) ) : ?>
												<?php foreach ( $wp_dialyra_departments as $wp_dialyra_department ) : ?>
													<option value="<?php echo esc_attr( $wp_dialyra_department['id'] ); ?>"><?php echo esc_html( $wp_dialyra_department['name'] ); ?></option>
												<?php endforeach; ?>
											<?php else : ?>
												<option value=""><?php esc_html_e( 'No departments available', 'wp-dialyra' ); ?></option>
											<?php endif; ?>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-show-for="go_to_menu">
										<label><?php esc_html_e( 'Target menu', 'wp-dialyra' ); ?></label>
										<select name="target_menu_<?php echo esc_attr( $wp_dialyra_action_number ); ?>">
											<option value="main_menu"><?php esc_html_e( 'Main Menu', 'wp-dialyra' ); ?></option>
										</select>
									</div>
								</div>

								<div class="wp-dialyra-dtmf-actions__footer">
									<button class="wp-dialyra-flow-icon-button wp-dialyra-flow-icon-button--delete" type="button" data-remove-dtmf-action aria-label="<?php esc_attr_e( 'Remove DTMF action', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Remove DTMF action', 'wp-dialyra' ); ?>">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</button>
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
							<option value="bn"><?php esc_html_e( 'Bangla', 'wp-dialyra' ); ?></option>
							<option value="en" selected><?php esc_html_e( 'English', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-invalid-voice"><?php esc_html_e( 'Voice', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-invalid-voice" name="invalid_tts_voice">
							<option value="gtts:free"><?php esc_html_e( 'Standard voice', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
						<label for="wp-dialyra-invalid-provider"><?php esc_html_e( 'Provider', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-invalid-provider" name="invalid_tts_provider">
							<option value="google"><?php esc_html_e( 'Google TTS', 'wp-dialyra' ); ?></option>
						</select>
					</div>

					<div class="wp-dialyra-settings-row" data-dialyra-show-for="audio">
						<label for="wp-dialyra-invalid-audio"><?php esc_html_e( 'Invalid response audio', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-invalid-audio" name="invalid_audio">
							<?php if ( ! empty( $wp_dialyra_audio_assets ) ) : ?>
								<?php foreach ( $wp_dialyra_audio_assets as $wp_dialyra_audio_asset ) : ?>
									<option value="<?php echo esc_attr( $wp_dialyra_audio_asset['id'] ); ?>"><?php echo esc_html( $wp_dialyra_audio_asset['name'] ); ?></option>
								<?php endforeach; ?>
							<?php else : ?>
								<option value=""><?php esc_html_e( 'No audio assets available', 'wp-dialyra' ); ?></option>
							<?php endif; ?>
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
							<option value="main_menu"><?php esc_html_e( 'Main Menu', 'wp-dialyra' ); ?></option>
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
										<option value="bn"><?php esc_html_e( 'Bangla', 'wp-dialyra' ); ?></option>
										<option value="en" selected><?php esc_html_e( 'English', 'wp-dialyra' ); ?></option>
									</select>
								</div>

								<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
									<label for="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-voice"><?php esc_html_e( 'Voice', 'wp-dialyra' ); ?></label>
									<select id="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-voice" name="<?php echo esc_attr( $wp_dialyra_key ); ?>_tts_voice">
										<option value="gtts:free"><?php esc_html_e( 'Standard voice', 'wp-dialyra' ); ?></option>
									</select>
								</div>

								<div class="wp-dialyra-settings-row" data-dialyra-show-for="tts">
									<label for="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-provider"><?php esc_html_e( 'Provider', 'wp-dialyra' ); ?></label>
									<select id="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-provider" name="<?php echo esc_attr( $wp_dialyra_key ); ?>_tts_provider">
										<option value="google"><?php esc_html_e( 'Google TTS', 'wp-dialyra' ); ?></option>
									</select>
								</div>

								<div class="wp-dialyra-settings-row" data-dialyra-show-for="audio">
									<label for="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-audio"><?php esc_html_e( 'Audio asset', 'wp-dialyra' ); ?></label>
									<select id="wp-dialyra-<?php echo esc_attr( $wp_dialyra_key ); ?>-audio" name="<?php echo esc_attr( $wp_dialyra_key ); ?>_audio">
										<?php if ( ! empty( $wp_dialyra_audio_assets ) ) : ?>
											<?php foreach ( $wp_dialyra_audio_assets as $wp_dialyra_audio_asset ) : ?>
												<option value="<?php echo esc_attr( $wp_dialyra_audio_asset['id'] ); ?>"><?php echo esc_html( $wp_dialyra_audio_asset['name'] ); ?></option>
											<?php endforeach; ?>
										<?php else : ?>
											<option value=""><?php esc_html_e( 'No audio assets available', 'wp-dialyra' ); ?></option>
										<?php endif; ?>
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
										<option value="main_menu"><?php esc_html_e( 'Main Menu', 'wp-dialyra' ); ?></option>
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
							<p><?php echo empty( $wp_dialyra_error ) ? esc_html__( 'Review warnings and publish status.', 'wp-dialyra' ) : esc_html__( 'Fix critical issues before publishing.', 'wp-dialyra' ); ?></p>
						</div>
					</div>

					<ul class="wp-dialyra-validation-list">
						<?php foreach ( $wp_dialyra_validation as $wp_dialyra_validation_item ) : ?>
							<li class="wp-dialyra-validation-list__<?php echo esc_attr( $wp_dialyra_validation_item['type'] ); ?>"><?php echo esc_html( $wp_dialyra_validation_item['message'] ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		</main>
	</div>
</form>

<div id="wp-dialyra-flow-menu-dialog" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="wp-dialyra-flow-menu-dialog-title" hidden data-dialyra-dialog>
	<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
	<div class="wp-dialyra-dialog__panel wp-dialyra-dialog__panel--danger">
		<div class="wp-dialyra-dialog__head">
			<div>
				<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Flow Builder', 'wp-dialyra' ); ?></p>
				<h3 id="wp-dialyra-flow-menu-dialog-title" data-dialyra-flow-menu-title><?php esc_html_e( 'Menu action', 'wp-dialyra' ); ?></h3>
			</div>
			<button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>">
				<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
			</button>
		</div>

		<p class="wp-dialyra-dialog__warning" data-dialyra-flow-menu-message></p>
		<ul class="wp-dialyra-dialog__reference-list" data-dialyra-flow-menu-references hidden></ul>

		<div class="wp-dialyra-agent-panel__footer">
			<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-dialog-close><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></button>
			<button class="wp-dialyra-button wp-dialyra-button--primary" type="button" data-dialyra-flow-menu-confirm hidden><?php esc_html_e( 'Confirm', 'wp-dialyra' ); ?></button>
		</div>
	</div>
</div>
