<?php

/**
 * Dialyra menu flow compiler.
 *
 * Converts the menu-oriented Flow Builder JSON into the Dialyra graph payload
 * expected by /api/v2/flows/create-and-publish.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/flow
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Flow_Compiler {

	const END_NODE_KEY = 'flow_end';

	/**
	 * Validation errors.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $errors = array();

	/**
	 * Validation warnings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $warnings = array();

	/**
	 * Generated nodes keyed by node_key.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $nodes = array();

	/**
	 * Generated edges.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $edges = array();

	/**
	 * Menus keyed by menu ID.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $menus = array();

	/**
	 * Compile frontend menu JSON into Dialyra graph payload.
	 *
	 * @since    1.0.0
	 * @param    array    $frontend_flow    Frontend menu-oriented flow JSON.
	 * @param    int|null $business_id      Optional business ID override.
	 * @return   array {
	 *     @type bool       $valid     Whether the compiled payload is valid.
	 *     @type array      $errors    Validation errors.
	 *     @type array      $warnings  Validation warnings.
	 *     @type array|null $payload   Dialyra graph payload when valid.
	 * }
	 */
	public function compile( $frontend_flow, $business_id = null ) {
		$this->reset();

		$frontend_flow = $this->unwrap_frontend_flow( $frontend_flow );
		$frontend_flow = is_array( $frontend_flow ) ? $frontend_flow : array();
		$business_id   = $business_id ? absint( $business_id ) : $this->get_connected_business_id();

		if ( ! $business_id ) {
			$this->add_error( 'flow.business_id', 'BUSINESS_ID_REQUIRED', __( 'A connected business is required before compiling a flow.', 'wp-dialyra' ) );
		}

		$this->index_menus( $frontend_flow );
		$this->validate_frontend_flow( $frontend_flow );

		if ( empty( $this->errors ) ) {
			$this->compile_menus( $frontend_flow );
			$this->add_end_node();
			$this->validate_generated_graph( $frontend_flow );
		}

		$payload = null;

		if ( empty( $this->errors ) ) {
			$payload = array(
				'flow'           => array(
					'business_id' => $business_id,
					'name'        => $this->string_value( $frontend_flow['name'] ?? '' ),
					'description' => $this->string_value( $frontend_flow['description'] ?? '' ),
				),
				'start_node_key' => $this->menu_message_key( $frontend_flow['startMenuId'] ?? '' ),
				'nodes'          => array_values( $this->nodes ),
				'edges'          => $this->edges,
			);
		}

		return array(
			'valid'    => empty( $this->errors ),
			'errors'   => $this->errors,
			'warnings' => $this->warnings,
			'payload'  => $payload,
		);
	}

	/**
	 * Reset compiler state.
	 *
	 * @since    1.0.0
	 */
	private function reset() {
		$this->errors   = array();
		$this->warnings = array();
		$this->nodes    = array();
		$this->edges    = array();
		$this->menus    = array();
	}

	/**
	 * Accept either raw frontend flow JSON or builder result wrapper.
	 *
	 * @since    1.0.0
	 * @param    mixed    $frontend_flow    Raw flow value.
	 * @return   mixed
	 */
	private function unwrap_frontend_flow( $frontend_flow ) {
		if ( is_array( $frontend_flow ) && isset( $frontend_flow['flow'] ) && is_array( $frontend_flow['flow'] ) && array_key_exists( 'valid', $frontend_flow ) ) {
			return $frontend_flow['flow'];
		}

		return $frontend_flow;
	}

	/**
	 * Index menus by ID.
	 *
	 * @since    1.0.0
	 * @param    array    $frontend_flow    Frontend flow.
	 */
	private function index_menus( $frontend_flow ) {
		$menus = isset( $frontend_flow['menus'] ) && is_array( $frontend_flow['menus'] ) ? $frontend_flow['menus'] : array();

		foreach ( $menus as $menu ) {
			if ( ! is_array( $menu ) || empty( $menu['id'] ) ) {
				continue;
			}

			$menu_id = $this->menu_id_value( $menu['id'] );

			if ( '' !== $menu_id && ! isset( $this->menus[ $menu_id ] ) ) {
				$menu['id'] = $menu_id;
				$this->menus[ $menu_id ] = $menu;
			}
		}
	}

	/**
	 * Validate frontend flow before graph compilation.
	 *
	 * @since    1.0.0
	 * @param    array    $frontend_flow    Frontend flow.
	 */
	private function validate_frontend_flow( $frontend_flow ) {
		$name          = $this->string_value( $frontend_flow['name'] ?? '' );
		$start_menu_id = $this->menu_id_value( $frontend_flow['startMenuId'] ?? '' );
		$menus         = isset( $frontend_flow['menus'] ) && is_array( $frontend_flow['menus'] ) ? array_values( $frontend_flow['menus'] ) : array();

		if ( '' === $name ) {
			$this->add_error( 'name', 'FLOW_NAME_REQUIRED', __( 'Flow name is required.', 'wp-dialyra' ) );
		}

		if ( empty( $menus ) ) {
			$this->add_error( 'menus', 'MENUS_REQUIRED', __( 'At least one menu is required.', 'wp-dialyra' ) );
		}

		if ( '' === $start_menu_id ) {
			$this->add_error( 'startMenuId', 'START_MENU_REQUIRED', __( 'Start menu is required.', 'wp-dialyra' ) );
		} elseif ( ! isset( $this->menus[ $start_menu_id ] ) ) {
			$this->add_error( 'startMenuId', 'INVALID_START_MENU', __( 'The selected start menu does not exist.', 'wp-dialyra' ) );
		}

		$seen_menu_ids = array();

		foreach ( $menus as $menu_index => $menu ) {
			$this->validate_menu( is_array( $menu ) ? $menu : array(), $menu_index, $seen_menu_ids );
		}

		$this->validate_transfer_fallback( $frontend_flow['transferFailed'] ?? array(), 'transferFailed' );
		$this->validate_transfer_fallback( $frontend_flow['transferTimeout'] ?? array(), 'transferTimeout' );
	}

	/**
	 * Validate one menu.
	 *
	 * @since    1.0.0
	 * @param    array    $menu            Menu state.
	 * @param    int      $menu_index      Menu index.
	 * @param    array    $seen_menu_ids   Previously seen menu IDs.
	 */
	private function validate_menu( $menu, $menu_index, &$seen_menu_ids ) {
		$field_prefix = 'menus.' . $menu_index;
		$menu_id      = $this->menu_id_value( $menu['id'] ?? '' );
		$menu_name    = $this->string_value( $menu['name'] ?? '' );

		if ( '' === $menu_id ) {
			$this->add_error( $field_prefix . '.id', 'MENU_ID_REQUIRED', __( 'Menu ID is required.', 'wp-dialyra' ) );
		} elseif ( in_array( $menu_id, $seen_menu_ids, true ) ) {
			$this->add_error( $field_prefix . '.id', 'DUPLICATE_MENU_ID', __( 'Menu ID must be unique.', 'wp-dialyra' ) );
		} else {
			$seen_menu_ids[] = $menu_id;
		}

		if ( '' === $menu_name ) {
			$this->add_error( $field_prefix . '.name', 'MENU_NAME_REQUIRED', __( 'Menu name is required.', 'wp-dialyra' ) );
		}

		$this->validate_message( $menu['customerInstructionMessage'] ?? array(), $field_prefix . '.customerInstructionMessage', false );
		$this->validate_menu_input_settings( $menu['menuInputSettings'] ?? array(), $field_prefix . '.menuInputSettings' );

		$actions   = isset( $menu['dtmfActions'] ) && is_array( $menu['dtmfActions'] ) ? array_values( $menu['dtmfActions'] ) : array();
		$seen_keys = array();

		foreach ( $actions as $action_index => $action ) {
			$action       = is_array( $action ) ? $action : array();
			$action_field = $field_prefix . '.dtmfActions.' . $action_index;
			$input_key    = $this->dtmf_key_value( $action['inputKey'] ?? '' );

			if ( '' === $input_key ) {
				$this->add_error( $action_field . '.inputKey', 'DTMF_KEY_REQUIRED', __( 'DTMF input key is required.', 'wp-dialyra' ) );
			} elseif ( in_array( $input_key, $seen_keys, true ) ) {
				$this->add_error( $action_field . '.inputKey', 'DUPLICATE_DTMF_KEY', __( 'DTMF input key must be unique inside the menu.', 'wp-dialyra' ) );
			} else {
				$seen_keys[] = $input_key;
			}

			$this->validate_message( $action['responseMessage'] ?? array( 'type' => 'none' ), $action_field . '.responseMessage', true );
			$this->validate_business_action( $action['businessAction'] ?? array( 'type' => 'no_action' ), $action_field . '.businessAction' );
			$this->validate_next_step( $action['nextStep'] ?? array(), $action_field . '.nextStep' );
		}

		$this->validate_message( $menu['invalidInputHandling']['responseMessage'] ?? array( 'type' => 'none' ), $field_prefix . '.invalidInputHandling.responseMessage', true );
		$this->validate_next_step( $menu['invalidInputHandling']['afterMaxInvalidRetryAction'] ?? array( 'type' => 'repeat_current_menu' ), $field_prefix . '.invalidInputHandling.afterMaxInvalidRetryAction' );
		$this->validate_message( $menu['timeoutHandling']['responseMessage'] ?? array( 'type' => 'none' ), $field_prefix . '.timeoutHandling.responseMessage', true );
		$this->validate_next_step( $menu['timeoutHandling']['nextStep'] ?? array( 'type' => 'repeat_current_menu' ), $field_prefix . '.timeoutHandling.nextStep' );
	}

	/**
	 * Validate menu input settings.
	 *
	 * @since    1.0.0
	 * @param    array     $settings       Input settings.
	 * @param    string    $field_prefix   Field prefix.
	 */
	private function validate_menu_input_settings( $settings, $field_prefix ) {
		$settings = is_array( $settings ) ? $settings : array();
		$values   = array(
			'maxDigits'         => $settings['maxDigits'] ?? 1,
			'timeoutSeconds'    => $settings['timeoutSeconds'] ?? 5,
			'maxInvalidRetries' => $settings['maxInvalidRetries'] ?? 2,
			'maxTimeoutRetries' => $settings['maxTimeoutRetries'] ?? 1,
		);

		foreach ( $values as $field => $value ) {
			if ( ! $this->is_integer_like( $value ) ) {
				$this->add_error( $field_prefix . '.' . $field, 'INTEGER_REQUIRED', __( 'This field must be an integer.', 'wp-dialyra' ) );
				continue;
			}

			$value = intval( $value );

			if ( in_array( $field, array( 'maxDigits', 'timeoutSeconds' ), true ) && $value < 1 ) {
				$this->add_error( $field_prefix . '.' . $field, 'POSITIVE_INTEGER_REQUIRED', __( 'This field must be a positive integer.', 'wp-dialyra' ) );
			}

			if ( in_array( $field, array( 'maxInvalidRetries', 'maxTimeoutRetries' ), true ) && $value < 0 ) {
				$this->add_error( $field_prefix . '.' . $field, 'ZERO_OR_POSITIVE_INTEGER_REQUIRED', __( 'This field must be zero or a positive integer.', 'wp-dialyra' ) );
			}
		}
	}

	/**
	 * Validate a message.
	 *
	 * @since    1.0.0
	 * @param    array     $message        Message state.
	 * @param    string    $field_prefix   Field prefix.
	 * @param    bool      $allow_none     Whether none is allowed.
	 */
	private function validate_message( $message, $field_prefix, $allow_none ) {
		$message = is_array( $message ) ? $message : array();
		$type    = $this->message_type_value( $message['type'] ?? '' );

		if ( 'none' === $type && $allow_none ) {
			return;
		}

		if ( ! in_array( $type, array( 'tts', 'audio' ), true ) ) {
			$this->add_error( $field_prefix . '.type', 'INVALID_MESSAGE_TYPE', __( 'Message type must be TTS or audio.', 'wp-dialyra' ) );
			return;
		}

		if ( 'audio' === $type ) {
			if ( absint( $message['audioAssetId'] ?? 0 ) < 1 ) {
				$this->add_error( $field_prefix . '.audioAssetId', 'VALID_AUDIO_ASSET_REQUIRED', __( 'Audio asset must be a positive integer.', 'wp-dialyra' ) );
			}

			return;
		}

		foreach ( array( 'message', 'language', 'provider', 'voice' ) as $required_field ) {
			if ( '' === $this->string_value( $message[ $required_field ] ?? '' ) ) {
				$this->add_error( $field_prefix . '.' . $required_field, 'TTS_FIELD_REQUIRED', __( 'TTS message, language, provider, and voice are required.', 'wp-dialyra' ) );
			}
		}
	}

	/**
	 * Validate a business action.
	 *
	 * @since    1.0.0
	 * @param    array     $business_action   Business action.
	 * @param    string    $field_prefix      Field prefix.
	 */
	private function validate_business_action( $business_action, $field_prefix ) {
		$business_action = is_array( $business_action ) ? $business_action : array();
		$type            = sanitize_key( $business_action['type'] ?? 'no_action' );

		if ( ! in_array( $type, array( 'no_action', 'confirm_order', 'cancel_order', 'transfer_department' ), true ) ) {
			$this->add_error( $field_prefix . '.type', 'INVALID_BUSINESS_ACTION', __( 'Business action type is invalid.', 'wp-dialyra' ) );
			return;
		}

		if ( 'transfer_department' === $type && absint( $business_action['departmentId'] ?? 0 ) < 1 ) {
			$this->add_error( $field_prefix . '.departmentId', 'VALID_DEPARTMENT_REQUIRED', __( 'Transfer department must be a positive integer.', 'wp-dialyra' ) );
		}
	}

	/**
	 * Validate a next step.
	 *
	 * @since    1.0.0
	 * @param    array     $next_step      Next step.
	 * @param    string    $field_prefix   Field prefix.
	 */
	private function validate_next_step( $next_step, $field_prefix ) {
		$next_step = is_array( $next_step ) ? $next_step : array();
		$type      = sanitize_key( $next_step['type'] ?? '' );

		if ( ! in_array( $type, array( 'repeat_current_menu', 'go_to_menu', 'hangup', 'end_flow' ), true ) ) {
			$this->add_error( $field_prefix . '.type', 'INVALID_NEXT_STEP', __( 'Next step type is invalid.', 'wp-dialyra' ) );
			return;
		}

		if ( 'go_to_menu' === $type ) {
			$target_menu_id = $this->menu_id_value( $next_step['targetMenuId'] ?? '' );

			if ( '' === $target_menu_id ) {
				$this->add_error( $field_prefix . '.targetMenuId', 'TARGET_MENU_REQUIRED', __( 'Target menu is required.', 'wp-dialyra' ) );
			} elseif ( ! isset( $this->menus[ $target_menu_id ] ) ) {
				$this->add_error( $field_prefix . '.targetMenuId', 'INVALID_TARGET_MENU', __( 'The selected target menu does not exist.', 'wp-dialyra' ) );
			}
		}
	}

	/**
	 * Validate transfer fallback.
	 *
	 * @since    1.0.0
	 * @param    array     $fallback       Transfer fallback.
	 * @param    string    $field_prefix   Field prefix.
	 */
	private function validate_transfer_fallback( $fallback, $field_prefix ) {
		$fallback = is_array( $fallback ) ? $fallback : array();

		$this->validate_message( $fallback['responseMessage'] ?? array( 'type' => 'none' ), $field_prefix . '.responseMessage', true );
		$this->validate_next_step( $fallback['nextStep'] ?? array( 'type' => 'hangup' ), $field_prefix . '.nextStep' );
	}

	/**
	 * Compile all menus.
	 *
	 * @since    1.0.0
	 * @param    array    $frontend_flow    Frontend flow.
	 */
	private function compile_menus( $frontend_flow ) {
		$menus = isset( $frontend_flow['menus'] ) && is_array( $frontend_flow['menus'] ) ? array_values( $frontend_flow['menus'] ) : array();

		foreach ( $menus as $menu ) {
			$this->compile_menu( $menu, $frontend_flow );
		}
	}

	/**
	 * Compile one menu.
	 *
	 * @since    1.0.0
	 * @param    array    $menu            Menu state.
	 * @param    array    $frontend_flow   Full frontend flow.
	 */
	private function compile_menu( $menu, $frontend_flow ) {
		$menu_id     = $this->menu_id_value( $menu['id'] ?? '' );
		$menu_name   = $this->string_value( $menu['name'] ?? $menu_id );
		$message_key = $this->menu_message_key( $menu_id );
		$gather_key  = $this->menu_gather_key( $menu_id );
		$actions     = isset( $menu['dtmfActions'] ) && is_array( $menu['dtmfActions'] ) ? array_values( $menu['dtmfActions'] ) : array();

		$this->add_message_node( $message_key, $menu_name . ' Message', $menu['customerInstructionMessage'] ?? array() );

		if ( empty( $actions ) ) {
			$this->add_edge( $message_key, self::END_NODE_KEY, 'always', '', 1 );
			return;
		}

		$this->add_node(
			array(
				'node_key'  => $gather_key,
				'node_type' => 'gather_input',
				'name'      => $menu_name . ' Input',
				'config'    => array(
					'max_digits'          => max( 1, intval( $menu['menuInputSettings']['maxDigits'] ?? 1 ) ),
					'timeout_seconds'     => max( 1, intval( $menu['menuInputSettings']['timeoutSeconds'] ?? 5 ) ),
					'max_invalid_retries' => max( 0, intval( $menu['menuInputSettings']['maxInvalidRetries'] ?? 2 ) ),
					'max_timeout_retries' => max( 0, intval( $menu['menuInputSettings']['maxTimeoutRetries'] ?? 1 ) ),
					'allowed_inputs'      => $this->collect_allowed_inputs( $actions ),
				),
			)
		);

		$this->add_edge( $message_key, $gather_key, 'always', '', 1 );

		foreach ( $actions as $action_index => $action ) {
			$this->compile_dtmf_action( $menu, $action, $action_index, $frontend_flow );
		}

		$this->compile_invalid_input_handler( $menu );
		$this->compile_timeout_handler( $menu );
	}

	/**
	 * Compile a DTMF action chain.
	 *
	 * @since    1.0.0
	 * @param    array    $menu            Menu state.
	 * @param    array    $action          DTMF action.
	 * @param    int      $action_index    Action index.
	 * @param    array    $frontend_flow   Full frontend flow.
	 */
	private function compile_dtmf_action( $menu, $action, $action_index, $frontend_flow ) {
		$menu_id         = $this->menu_id_value( $menu['id'] ?? '' );
		$input_key       = $this->dtmf_key_value( $action['inputKey'] ?? '' );
		$source_key      = $this->menu_gather_key( $menu_id );
		$business_action = is_array( $action['businessAction'] ?? null ) ? $action['businessAction'] : array( 'type' => 'no_action' );
		$action_type     = sanitize_key( $business_action['type'] ?? 'no_action' );
		$response        = is_array( $action['responseMessage'] ?? null ) ? $action['responseMessage'] : array( 'type' => 'none' );
		$next_step       = is_array( $action['nextStep'] ?? null ) ? $action['nextStep'] : array( 'type' => 'end_flow' );
		$chain_nodes     = array();

		if ( in_array( $action_type, array( 'confirm_order', 'cancel_order' ), true ) ) {
			$set_key = $this->dtmf_node_key( $menu_id, $input_key, 'set_order_action' );
			$value   = 'confirm_order' === $action_type ? 'confirmed' : 'cancelled';
			$name    = 'confirm_order' === $action_type ? __( 'Set Order Action Confirmed', 'wp-dialyra' ) : __( 'Set Order Action Cancelled', 'wp-dialyra' );

			$this->add_node(
				array(
					'node_key'  => $set_key,
						'node_type' => 'set_variable',
						'name'      => $name,
						'config'    => array(
							'key'       => 'order_action',
							'value'     => $value,
							'variables' => array(
								'order_action' => $value,
							),
						),
				)
			);

			$chain_nodes[] = $set_key;
		}

		if ( 'transfer_department' !== $action_type && $this->has_response_message( $response ) ) {
			$response_key = $this->dtmf_node_key( $menu_id, $input_key, 'response' );
			$this->add_message_node( $response_key, $this->humanize_menu_id( $menu_id ) . ' DTMF ' . $input_key . ' Response', $response );
			$chain_nodes[] = $response_key;
		}

		if ( 'transfer_department' === $action_type ) {
			if ( $this->has_response_message( $response ) ) {
				$response_key = $this->dtmf_node_key( $menu_id, $input_key, 'response' );
				$this->add_message_node( $response_key, $this->humanize_menu_id( $menu_id ) . ' DTMF ' . $input_key . ' Response', $response );
				$chain_nodes[] = $response_key;
			}

			$transfer_key = $this->dtmf_node_key( $menu_id, $input_key, 'transfer_department' );
			$department_id = absint( $business_action['departmentId'] ?? 0 );
			$this->add_node(
				array(
					'node_key'  => $transfer_key,
					'node_type' => 'transfer_call',
					'name'      => sprintf( __( 'Transfer to Department %d', 'wp-dialyra' ), $department_id ),
					'config'    => array(
						'target_type'   => 'department',
						'department_id' => $department_id,
					),
				)
			);
			$chain_nodes[] = $transfer_key;
		}

		$entry_key = ! empty( $chain_nodes ) ? $chain_nodes[0] : $this->resolve_next_step_target( $next_step, $menu_id );
		$this->add_edge( $source_key, $entry_key, 'dtmf', $input_key, $action_index + 1 );

		for ( $index = 0; $index < count( $chain_nodes ) - 1; $index++ ) {
			$this->add_edge( $chain_nodes[ $index ], $chain_nodes[ $index + 1 ], 'always', '', 1 );
		}

		if ( 'transfer_department' === $action_type ) {
			$transfer_key = end( $chain_nodes );
			$this->add_edge( $transfer_key, $this->resolve_next_step_target( $next_step, $menu_id ), 'transfer_connected', '', 1 );
			$this->add_edge( $transfer_key, $this->compile_transfer_handler( $frontend_flow, 'failed' ), 'transfer_failed', '', 2 );
			$this->add_edge( $transfer_key, $this->compile_transfer_handler( $frontend_flow, 'timeout' ), 'transfer_timeout', '', 3 );
			return;
		}

		if ( ! empty( $chain_nodes ) ) {
			$this->add_edge( end( $chain_nodes ), $this->resolve_next_step_target( $next_step, $menu_id ), 'always', '', 1 );
		}
	}

	/**
	 * Compile invalid input handling for a menu.
	 *
	 * @since    1.0.0
	 * @param    array    $menu    Menu state.
	 */
	private function compile_invalid_input_handler( $menu ) {
		$menu_id   = $this->menu_id_value( $menu['id'] ?? '' );
		$handling  = is_array( $menu['invalidInputHandling'] ?? null ) ? $menu['invalidInputHandling'] : array();
		$response  = is_array( $handling['responseMessage'] ?? null ) ? $handling['responseMessage'] : array( 'type' => 'none' );
		$next_step = is_array( $handling['afterMaxInvalidRetryAction'] ?? null ) ? $handling['afterMaxInvalidRetryAction'] : array( 'type' => 'repeat_current_menu' );
		$target    = $this->resolve_next_step_target( $next_step, $menu_id );

		if ( $this->has_response_message( $response ) ) {
			$response_key = 'menu_' . $menu_id . '_invalid_response';
			$this->add_message_node( $response_key, $this->humanize_menu_id( $menu_id ) . ' Invalid Response', $response );
			$this->add_edge( $this->menu_gather_key( $menu_id ), $response_key, 'invalid_input', '', 90 );
			$this->add_edge( $response_key, $target, 'always', '', 1 );
			return;
		}

		$this->add_edge( $this->menu_gather_key( $menu_id ), $target, 'invalid_input', '', 90 );
	}

	/**
	 * Compile timeout handling for a menu.
	 *
	 * @since    1.0.0
	 * @param    array    $menu    Menu state.
	 */
	private function compile_timeout_handler( $menu ) {
		$menu_id   = $this->menu_id_value( $menu['id'] ?? '' );
		$handling  = is_array( $menu['timeoutHandling'] ?? null ) ? $menu['timeoutHandling'] : array();
		$response  = is_array( $handling['responseMessage'] ?? null ) ? $handling['responseMessage'] : array( 'type' => 'none' );
		$next_step = is_array( $handling['nextStep'] ?? null ) ? $handling['nextStep'] : array( 'type' => 'repeat_current_menu' );
		$target    = $this->resolve_next_step_target( $next_step, $menu_id );

		if ( $this->has_response_message( $response ) ) {
			$response_key = 'menu_' . $menu_id . '_timeout_response';
			$this->add_message_node( $response_key, $this->humanize_menu_id( $menu_id ) . ' Timeout Response', $response );
			$this->add_edge( $this->menu_gather_key( $menu_id ), $response_key, 'timeout', '', 91 );
			$this->add_edge( $response_key, $target, 'always', '', 1 );
			return;
		}

		$this->add_edge( $this->menu_gather_key( $menu_id ), $target, 'timeout', '', 91 );
	}

	/**
	 * Compile and return shared transfer handler entry node.
	 *
	 * @since    1.0.0
	 * @param    array     $frontend_flow   Full frontend flow.
	 * @param    string    $mode            failed|timeout.
	 * @return   string
	 */
	private function compile_transfer_handler( $frontend_flow, $mode ) {
		$is_timeout = 'timeout' === $mode;
		$key_prefix = $is_timeout ? 'global_transfer_timeout' : 'global_transfer_failed';
		$fallback   = $is_timeout ? ( $frontend_flow['transferTimeout'] ?? array() ) : ( $frontend_flow['transferFailed'] ?? array() );
		$fallback   = is_array( $fallback ) ? $fallback : array();
		$response   = is_array( $fallback['responseMessage'] ?? null ) ? $fallback['responseMessage'] : array( 'type' => 'none' );
		$next_step  = is_array( $fallback['nextStep'] ?? null ) ? $fallback['nextStep'] : array( 'type' => 'hangup' );
		$target     = $this->resolve_next_step_target( $next_step, $frontend_flow['startMenuId'] ?? '' );

		if ( $this->has_response_message( $response ) ) {
			$response_key = $key_prefix . '_response';

			if ( ! isset( $this->nodes[ $response_key ] ) ) {
				$this->add_message_node( $response_key, $is_timeout ? __( 'Global Transfer Timeout Response', 'wp-dialyra' ) : __( 'Global Transfer Failed Response', 'wp-dialyra' ), $response );
				$this->add_edge( $response_key, $target, 'always', '', 1 );
			}

			return $response_key;
		}

		return $target;
	}

	/**
	 * Add the shared end node.
	 *
	 * @since    1.0.0
	 */
	private function add_end_node() {
		$this->add_node(
			array(
				'node_key'  => self::END_NODE_KEY,
				'node_type' => 'hangup',
				'name'      => __( 'End Flow', 'wp-dialyra' ),
				'config'    => array(
					'reason' => 'flow_completed',
				),
			)
		);
	}

	/**
	 * Add a message node from frontend message config.
	 *
	 * @since    1.0.0
	 * @param    string    $node_key   Node key.
	 * @param    string    $name       Node name.
	 * @param    array     $message    Message config.
	 */
	private function add_message_node( $node_key, $name, $message ) {
		$message = is_array( $message ) ? $message : array();
		$type    = $this->message_type_value( $message['type'] ?? '' );

		if ( 'audio' === $type ) {
			$this->add_node(
				array(
					'node_key'  => $node_key,
					'node_type' => 'play_audio',
					'name'      => $name,
					'config'    => array(
						'audio_asset_id' => absint( $message['audioAssetId'] ?? 0 ),
					),
				)
			);
			return;
		}

		$config = array(
			'text'     => $this->string_value( $message['message'] ?? '' ),
			'provider' => $this->string_value( $message['provider'] ?? '' ),
			'language' => $this->string_value( $message['language'] ?? '' ),
			'voice'    => $this->string_value( $message['voice'] ?? '' ),
		);

		if ( ! empty( $message['provider_variant'] ) ) {
			$config['provider_variant'] = $this->string_value( $message['provider_variant'] );
		} elseif ( ! empty( $message['providerVariant'] ) ) {
			$config['provider_variant'] = $this->string_value( $message['providerVariant'] );
		}

		$this->add_node(
			array(
				'node_key'  => $node_key,
				'node_type' => 'say_text',
				'name'      => $name,
				'config'    => $config,
			)
		);
	}

	/**
	 * Add a node to the graph.
	 *
	 * @since    1.0.0
	 * @param    array    $node    Node payload.
	 */
	private function add_node( $node ) {
		$node_key = $this->node_key_value( $node['node_key'] ?? '' );

		if ( '' === $node_key ) {
			$this->add_error( 'nodes', 'NODE_KEY_REQUIRED', __( 'Generated node key is missing.', 'wp-dialyra' ) );
			return;
		}

		if ( isset( $this->nodes[ $node_key ] ) ) {
			$this->add_error( 'nodes.' . $node_key, 'DUPLICATE_NODE_KEY', __( 'Generated node key is duplicated.', 'wp-dialyra' ) );
			return;
		}

		$node['node_key'] = $node_key;
		$this->nodes[ $node_key ] = $node;
	}

	/**
	 * Add an edge to the graph.
	 *
	 * @since    1.0.0
	 * @param    string    $source_node_key   Source node key.
	 * @param    string    $target_node_key   Target node key.
	 * @param    string    $condition_type    Condition type.
	 * @param    string    $condition_value   Condition value.
	 * @param    int       $priority          Priority.
	 */
	private function add_edge( $source_node_key, $target_node_key, $condition_type, $condition_value = '', $priority = 1 ) {
		$edge = array(
			'source_node_key' => $this->node_key_value( $source_node_key ),
			'target_node_key' => $this->node_key_value( $target_node_key ),
			'condition_type'  => sanitize_key( $condition_type ),
			'priority'        => absint( $priority ),
		);

		if ( '' !== $condition_value ) {
			$edge['condition_value'] = $this->string_value( $condition_value );
		}

		foreach ( $this->edges as $existing_edge ) {
			if (
				$existing_edge['source_node_key'] === $edge['source_node_key']
				&& $existing_edge['condition_type'] === $edge['condition_type']
				&& ( $existing_edge['condition_value'] ?? '' ) === ( $edge['condition_value'] ?? '' )
			) {
				$this->add_error( 'edges.' . $edge['source_node_key'], 'DUPLICATE_EDGE_CONDITION', __( 'Generated duplicate edge condition from the same source node.', 'wp-dialyra' ) );
				return;
			}
		}

		$this->edges[] = $edge;
	}

	/**
	 * Resolve frontend next step to backend target node key.
	 *
	 * @since    1.0.0
	 * @param    array     $next_step          Next step.
	 * @param    string    $current_menu_id    Current menu ID.
	 * @return   string
	 */
	private function resolve_next_step_target( $next_step, $current_menu_id ) {
		$next_step = is_array( $next_step ) ? $next_step : array();
		$type      = sanitize_key( $next_step['type'] ?? 'end_flow' );

		if ( 'repeat_current_menu' === $type ) {
			return $this->menu_message_key( $current_menu_id );
		}

		if ( 'go_to_menu' === $type ) {
			return $this->menu_message_key( $next_step['targetMenuId'] ?? '' );
		}

		return self::END_NODE_KEY;
	}

	/**
	 * Validate generated graph safety.
	 *
	 * @since    1.0.0
	 * @param    array    $frontend_flow    Frontend flow.
	 */
	private function validate_generated_graph( $frontend_flow ) {
		$this->validate_edge_targets();
		$this->validate_reachability( $frontend_flow );
		$this->validate_no_automatic_loop();
		$this->validate_terminal_path( $frontend_flow );
	}

	/**
	 * Ensure edge source and target nodes exist.
	 *
	 * @since    1.0.0
	 */
	private function validate_edge_targets() {
		foreach ( $this->edges as $edge_index => $edge ) {
			if ( ! isset( $this->nodes[ $edge['source_node_key'] ] ) ) {
				$this->add_error( 'edges.' . $edge_index . '.source_node_key', 'EDGE_SOURCE_MISSING', __( 'Generated edge source node does not exist.', 'wp-dialyra' ) );
			}

			if ( ! isset( $this->nodes[ $edge['target_node_key'] ] ) ) {
				$this->add_error( 'edges.' . $edge_index . '.target_node_key', 'EDGE_TARGET_MISSING', __( 'Generated edge target node does not exist.', 'wp-dialyra' ) );
			}
		}
	}

	/**
	 * Validate node reachability and menu reachability.
	 *
	 * @since    1.0.0
	 * @param    array    $frontend_flow    Frontend flow.
	 */
	private function validate_reachability( $frontend_flow ) {
		$start_key = $this->menu_message_key( $frontend_flow['startMenuId'] ?? '' );
		$reachable = $this->reachable_nodes_from( $start_key );

		foreach ( $this->menus as $menu_id => $menu ) {
			if ( ! isset( $reachable[ $this->menu_message_key( $menu_id ) ] ) ) {
				$this->warnings[] = array(
					'code'    => 'UNREACHABLE_MENU',
					'menuId'  => $menu_id,
					'message' => __( 'This menu cannot be reached from the start menu.', 'wp-dialyra' ),
				);
			}
		}

		foreach ( $this->nodes as $node_key => $node ) {
			if ( ! isset( $reachable[ $node_key ] ) ) {
				$this->warnings[] = array(
					'code'     => 'ORPHAN_NODE',
					'node_key' => $node_key,
					'message'  => __( 'Generated node is not reachable from the start menu.', 'wp-dialyra' ),
				);
			}
		}
	}

	/**
	 * Validate that automatic transitions do not form infinite loops.
	 *
	 * @since    1.0.0
	 */
	private function validate_no_automatic_loop() {
		$automatic_graph = array();

		foreach ( $this->edges as $edge ) {
			if ( $this->is_automatic_condition( $edge['condition_type'] ) ) {
				$automatic_graph[ $edge['source_node_key'] ][] = $edge['target_node_key'];
			}
		}

		$visited = array();
		$stack   = array();
		$path    = array();

		foreach ( array_keys( $this->nodes ) as $node_key ) {
			if ( $this->detect_automatic_cycle( $node_key, $automatic_graph, $visited, $stack, $path ) ) {
				return;
			}
		}
	}

	/**
	 * Depth-first detection for automatic-only cycles.
	 *
	 * @since    1.0.0
	 * @param    string    $node_key          Node key.
	 * @param    array     $automatic_graph   Automatic graph.
	 * @param    array     $visited           Visited map.
	 * @param    array     $stack             Recursion stack.
	 * @param    array     $path              Current path.
	 * @return   bool
	 */
	private function detect_automatic_cycle( $node_key, $automatic_graph, &$visited, &$stack, &$path ) {
		if ( ! empty( $stack[ $node_key ] ) ) {
			$cycle_nodes = array_slice( $path, array_search( $node_key, $path, true ) );
			$this->errors[] = array(
				'code'    => 'INFINITE_AUTOMATIC_LOOP',
				'message' => __( 'The generated flow contains an automatic loop that can execute indefinitely.', 'wp-dialyra' ),
				'nodes'   => array_values( $cycle_nodes ),
			);

			return true;
		}

		if ( ! empty( $visited[ $node_key ] ) ) {
			return false;
		}

		$visited[ $node_key ] = true;
		$stack[ $node_key ]   = true;
		$path[]               = $node_key;

		foreach ( $automatic_graph[ $node_key ] ?? array() as $target_key ) {
			if ( $this->detect_automatic_cycle( $target_key, $automatic_graph, $visited, $stack, $path ) ) {
				return true;
			}
		}

		array_pop( $path );
		unset( $stack[ $node_key ] );

		return false;
	}

	/**
	 * Validate that reachable graph has a possible path to flow_end.
	 *
	 * @since    1.0.0
	 * @param    array    $frontend_flow    Frontend flow.
	 */
	private function validate_terminal_path( $frontend_flow ) {
		$start_key      = $this->menu_message_key( $frontend_flow['startMenuId'] ?? '' );
		$reachable      = $this->reachable_nodes_from( $start_key );
		$can_reach_end  = $this->nodes_that_can_reach( self::END_NODE_KEY );
		$end_is_reached = isset( $reachable[ self::END_NODE_KEY ] );

		if ( ! $end_is_reached ) {
			$this->errors[] = array(
				'code'    => 'NO_TERMINAL_PATH',
				'message' => __( 'The flow does not contain a reachable end path.', 'wp-dialyra' ),
			);
			return;
		}

		foreach ( array_keys( $reachable ) as $node_key ) {
			if ( ! isset( $can_reach_end[ $node_key ] ) ) {
				$this->errors[] = array(
					'code'     => 'NO_TERMINAL_PATH',
					'node_key' => $node_key,
					'message'  => __( 'A reachable branch has no possible path to the end node.', 'wp-dialyra' ),
				);
				return;
			}
		}
	}

	/**
	 * Get nodes reachable from a start node.
	 *
	 * @since    1.0.0
	 * @param    string    $start_key    Start node key.
	 * @return   array
	 */
	private function reachable_nodes_from( $start_key ) {
		$adjacency = $this->build_adjacency();
		$visited   = array();
		$queue     = array( $start_key );

		while ( ! empty( $queue ) ) {
			$node_key = array_shift( $queue );

			if ( isset( $visited[ $node_key ] ) ) {
				continue;
			}

			$visited[ $node_key ] = true;

			foreach ( $adjacency[ $node_key ] ?? array() as $target_key ) {
				if ( ! isset( $visited[ $target_key ] ) ) {
					$queue[] = $target_key;
				}
			}
		}

		return $visited;
	}

	/**
	 * Get nodes that can reach a target.
	 *
	 * @since    1.0.0
	 * @param    string    $target_key    Target node key.
	 * @return   array
	 */
	private function nodes_that_can_reach( $target_key ) {
		$reverse = array();

		foreach ( $this->edges as $edge ) {
			$reverse[ $edge['target_node_key'] ][] = $edge['source_node_key'];
		}

		$visited = array();
		$queue   = array( $target_key );

		while ( ! empty( $queue ) ) {
			$node_key = array_shift( $queue );

			if ( isset( $visited[ $node_key ] ) ) {
				continue;
			}

			$visited[ $node_key ] = true;

			foreach ( $reverse[ $node_key ] ?? array() as $source_key ) {
				if ( ! isset( $visited[ $source_key ] ) ) {
					$queue[] = $source_key;
				}
			}
		}

		return $visited;
	}

	/**
	 * Build full adjacency map.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function build_adjacency() {
		$adjacency = array();

		foreach ( $this->edges as $edge ) {
			$adjacency[ $edge['source_node_key'] ][] = $edge['target_node_key'];
		}

		return $adjacency;
	}

	/**
	 * Check whether condition is automatic.
	 *
	 * @since    1.0.0
	 * @param    string    $condition_type    Condition type.
	 * @return   bool
	 */
	private function is_automatic_condition( $condition_type ) {
		return in_array( $condition_type, array( 'always', 'condition_matched', 'condition_not_matched', 'webhook_success', 'webhook_failed' ), true );
	}

	/**
	 * Collect allowed DTMF inputs from actions.
	 *
	 * @since    1.0.0
	 * @param    array    $actions    DTMF actions.
	 * @return   array
	 */
	private function collect_allowed_inputs( $actions ) {
		$inputs = array();

		foreach ( $actions as $action ) {
			$input_key = $this->dtmf_key_value( is_array( $action ) ? ( $action['inputKey'] ?? '' ) : '' );

			if ( '' !== $input_key ) {
				$inputs[] = $input_key;
			}
		}

		return array_values( array_unique( $inputs ) );
	}

	/**
	 * Check response message presence.
	 *
	 * @since    1.0.0
	 * @param    array    $response    Response message.
	 * @return   bool
	 */
	private function has_response_message( $response ) {
		return is_array( $response ) && 'none' !== $this->message_type_value( $response['type'] ?? 'none' );
	}

	/**
	 * Add validation error.
	 *
	 * @since    1.0.0
	 * @param    string    $field      Field path.
	 * @param    string    $code       Error code.
	 * @param    string    $message    Error message.
	 */
	private function add_error( $field, $code, $message ) {
		$this->errors[] = array(
			'field'   => $field,
			'code'    => $code,
			'message' => $message,
		);
	}

	/**
	 * Get connected business ID.
	 *
	 * @since    1.0.0
	 * @return   int
	 */
	private function get_connected_business_id() {
		return class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
	}

	/**
	 * Build menu message key.
	 *
	 * @since    1.0.0
	 * @param    string    $menu_id    Menu ID.
	 * @return   string
	 */
	private function menu_message_key( $menu_id ) {
		return 'menu_' . $this->menu_id_value( $menu_id ) . '_message';
	}

	/**
	 * Build menu gather key.
	 *
	 * @since    1.0.0
	 * @param    string    $menu_id    Menu ID.
	 * @return   string
	 */
	private function menu_gather_key( $menu_id ) {
		return 'menu_' . $this->menu_id_value( $menu_id ) . '_gather';
	}

	/**
	 * Build DTMF node key.
	 *
	 * @since    1.0.0
	 * @param    string    $menu_id      Menu ID.
	 * @param    string    $input_key    DTMF key.
	 * @param    string    $suffix       Node suffix.
	 * @return   string
	 */
	private function dtmf_node_key( $menu_id, $input_key, $suffix ) {
		return 'menu_' . $this->menu_id_value( $menu_id ) . '_dtmf_' . $this->dtmf_key_value( $input_key ) . '_' . $this->node_key_value( $suffix );
	}

	/**
	 * Sanitize node key.
	 *
	 * @since    1.0.0
	 * @param    string    $value    Raw value.
	 * @return   string
	 */
	private function node_key_value( $value ) {
		return sanitize_key( str_replace( '-', '_', $this->string_value( $value ) ) );
	}

	/**
	 * Sanitize menu ID.
	 *
	 * @since    1.0.0
	 * @param    string    $value    Raw value.
	 * @return   string
	 */
	private function menu_id_value( $value ) {
		return sanitize_key( str_replace( '-', '_', $this->string_value( $value ) ) );
	}

	/**
	 * Sanitize DTMF input key.
	 *
	 * @since    1.0.0
	 * @param    string    $value    Raw value.
	 * @return   string
	 */
	private function dtmf_key_value( $value ) {
		$value = $this->string_value( $value );

		return in_array( $value, array( '1', '2', '3', '4', '5', '6', '7', '8', '9' ), true ) ? $value : '';
	}

	/**
	 * Sanitize message type.
	 *
	 * @since    1.0.0
	 * @param    string    $value    Raw value.
	 * @return   string
	 */
	private function message_type_value( $value ) {
		$type = sanitize_key( $this->string_value( $value ) );

		if ( in_array( $type, array( 'text_to_speech', 'text-to-speech', 'say_text' ), true ) ) {
			return 'tts';
		}

		return $type;
	}

	/**
	 * Humanize a menu ID.
	 *
	 * @since    1.0.0
	 * @param    string    $menu_id    Menu ID.
	 * @return   string
	 */
	private function humanize_menu_id( $menu_id ) {
		return ucwords( str_replace( '_', ' ', $this->menu_id_value( $menu_id ) ) );
	}

	/**
	 * Sanitize string value.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
	 * @return   string
	 */
	private function string_value( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	/**
	 * Check if value is integer-like.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
	 * @return   bool
	 */
	private function is_integer_like( $value ) {
		if ( is_int( $value ) ) {
			return true;
		}

		if ( is_string( $value ) ) {
			return (bool) preg_match( '/^-?\d+$/', trim( $value ) );
		}

		return false;
	}
}
