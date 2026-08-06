<?php

/**
 * Dialyra frontend flow JSON builder.
 *
 * Builds the menu-oriented Flow Builder state used by the admin UI. This class
 * intentionally does not generate backend nodes, edges, node keys, or publish
 * payloads. Graph compilation belongs to a separate compiler layer.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/flow
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Frontend_Flow_Json_Builder {

	const MESSAGE_CONTEXT_INSTRUCTION = 'instruction';
	const MESSAGE_CONTEXT_RESPONSE    = 'response';

	/**
	 * Validation errors collected during build.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $errors = array();

	/**
	 * Menu IDs available in the current flow.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $menu_ids = array();

	/**
	 * Build normalized frontend flow JSON from raw UI state.
	 *
	 * @since    1.0.0
	 * @param    array    $ui_state    Raw menu-oriented Flow Builder state.
	 * @return   array {
	 *     @type bool       $valid   Whether the generated flow is valid.
	 *     @type array      $errors  Validation errors.
	 *     @type array|null $flow    Normalized frontend flow JSON, or null when invalid.
	 * }
	 */
	public function build( $ui_state ) {
		$this->errors   = array();
		$this->menu_ids = array();
		$ui_state       = is_array( $ui_state ) ? $ui_state : array();

		$flow = array(
			'name'            => $this->string_value( $this->get_value( $ui_state, array( 'name', 'flowName', 'flow_name' ) ) ),
			'description'     => $this->string_value( $this->get_value( $ui_state, array( 'description', 'flowDescription', 'flow_description' ) ) ),
			'startMenuId'     => $this->menu_reference_value( $this->get_value( $ui_state, array( 'startMenuId', 'start_menu_id', 'startMenu', 'start_menu' ) ) ),
			'transferFailed'  => array(),
			'transferTimeout' => array(),
			'menus'           => array(),
		);

		$menus = $this->get_value( $ui_state, array( 'menus' ), array() );
		$menus = is_array( $menus ) ? array_values( $menus ) : array();

		foreach ( $menus as $menu_index => $menu ) {
			$menu_id = $this->menu_reference_value( $this->get_value( is_array( $menu ) ? $menu : array(), array( 'id', 'menuId', 'menu_id' ) ) );

			if ( '' !== $menu_id ) {
				$this->menu_ids[] = $menu_id;
			}
		}

		$this->menu_ids = array_values( array_unique( $this->menu_ids ) );

		if ( '' === $flow['name'] ) {
			$this->add_error( 'name', 'FLOW_NAME_REQUIRED', __( 'Flow name is required.', 'wp-dialyra' ) );
		}

		if ( empty( $menus ) ) {
			$this->add_error( 'menus', 'MENUS_REQUIRED', __( 'At least one menu is required.', 'wp-dialyra' ) );
		}

		$seen_menu_ids = array();

		foreach ( $menus as $menu_index => $menu ) {
			$normalized_menu = $this->normalize_menu( is_array( $menu ) ? $menu : array(), $menu_index, $seen_menu_ids );

			if ( '' !== $normalized_menu['id'] ) {
				$seen_menu_ids[] = $normalized_menu['id'];
			}

			$flow['menus'][] = $normalized_menu;
		}

		if ( '' === $flow['startMenuId'] ) {
			$this->add_error( 'startMenuId', 'START_MENU_REQUIRED', __( 'Start menu is required.', 'wp-dialyra' ) );
		} elseif ( ! in_array( $flow['startMenuId'], $this->menu_ids, true ) ) {
			$this->add_error( 'startMenuId', 'INVALID_START_MENU', __( 'The selected start menu does not exist.', 'wp-dialyra' ) );
		}

		$flow['transferFailed']  = $this->normalize_transfer_fallback( $this->get_value( $ui_state, array( 'transferFailed', 'transfer_failed' ), array() ), 'transferFailed' );
		$flow['transferTimeout'] = $this->normalize_transfer_fallback( $this->get_value( $ui_state, array( 'transferTimeout', 'transfer_timeout' ), array() ), 'transferTimeout' );

		return array(
			'valid'  => empty( $this->errors ),
			'errors' => $this->errors,
			'flow'   => empty( $this->errors ) ? $flow : null,
		);
	}

	/**
	 * Create a stable-looking menu ID for the UI when a new menu is created.
	 *
	 * @since    1.0.0
	 * @param    string    $menu_name       Menu display name.
	 * @param    array     $existing_ids    Existing menu IDs.
	 * @return   string
	 */
	public function create_menu_id( $menu_name, $existing_ids = array() ) {
		$base_id = sanitize_key( str_replace( '-', '_', sanitize_title( $menu_name ) ) );
		$base_id = $base_id ? $base_id : 'menu';
		$menu_id = $base_id;
		$suffix  = 2;

		while ( in_array( $menu_id, $existing_ids, true ) ) {
			$menu_id = $base_id . '_' . $suffix;
			$suffix++;
		}

		return $menu_id;
	}

	/**
	 * Normalize a single menu.
	 *
	 * @since    1.0.0
	 * @param    array    $menu            Raw menu state.
	 * @param    int      $menu_index      Menu index.
	 * @param    array    $seen_menu_ids   IDs seen before this menu.
	 * @return   array
	 */
	private function normalize_menu( $menu, $menu_index, $seen_menu_ids ) {
		$field_prefix = 'menus.' . $menu_index;
		$menu_id      = $this->menu_reference_value( $this->get_value( $menu, array( 'id', 'menuId', 'menu_id' ) ) );

		if ( '' === $menu_id ) {
			$this->add_error( $field_prefix . '.id', 'MENU_ID_REQUIRED', __( 'Menu ID is required.', 'wp-dialyra' ) );
		} elseif ( in_array( $menu_id, $seen_menu_ids, true ) ) {
			$this->add_error( $field_prefix . '.id', 'DUPLICATE_MENU_ID', __( 'Menu ID must be unique.', 'wp-dialyra' ) );
		}

		$normalized_menu = array(
			'id'                         => $menu_id,
			'name'                       => $this->string_value( $this->get_value( $menu, array( 'name', 'menuName', 'menu_name' ) ) ),
			'description'                => $this->string_value( $this->get_value( $menu, array( 'description', 'menuDescription', 'menu_description' ) ) ),
			'customerInstructionMessage' => $this->normalize_message( $this->get_value( $menu, array( 'customerInstructionMessage', 'customer_instruction_message', 'instructionMessage' ), array() ), $field_prefix . '.customerInstructionMessage', self::MESSAGE_CONTEXT_INSTRUCTION ),
			'menuInputSettings'          => $this->normalize_menu_input_settings( $this->get_value( $menu, array( 'menuInputSettings', 'menu_input_settings', 'inputSettings' ), array() ), $field_prefix . '.menuInputSettings' ),
			'dtmfActions'                => array(),
			'invalidInputHandling'       => $this->normalize_invalid_input_handling( $this->get_value( $menu, array( 'invalidInputHandling', 'invalid_input_handling' ), array() ), $field_prefix . '.invalidInputHandling' ),
			'timeoutHandling'            => $this->normalize_timeout_handling( $this->get_value( $menu, array( 'timeoutHandling', 'timeout_handling' ), array() ), $field_prefix . '.timeoutHandling' ),
		);

		if ( '' === $normalized_menu['name'] ) {
			$this->add_error( $field_prefix . '.name', 'MENU_NAME_REQUIRED', __( 'Menu name is required.', 'wp-dialyra' ) );
		}

		$dtmf_actions = $this->get_value( $menu, array( 'dtmfActions', 'dtmf_actions', 'actions' ), array() );
		$dtmf_actions = is_array( $dtmf_actions ) ? array_values( $dtmf_actions ) : array();
		$seen_keys    = array();

		foreach ( $dtmf_actions as $action_index => $action ) {
			$normalized_action = $this->normalize_dtmf_action( is_array( $action ) ? $action : array(), $field_prefix . '.dtmfActions.' . $action_index );

			if ( '' === $normalized_action['inputKey'] ) {
				$this->add_error( $field_prefix . '.dtmfActions.' . $action_index . '.inputKey', 'DTMF_KEY_REQUIRED', __( 'DTMF input key is required.', 'wp-dialyra' ) );
			} elseif ( in_array( $normalized_action['inputKey'], $seen_keys, true ) ) {
				$this->add_error( $field_prefix . '.dtmfActions.' . $action_index . '.inputKey', 'DUPLICATE_DTMF_KEY', __( 'DTMF input key must be unique inside the menu.', 'wp-dialyra' ) );
			}

			if ( '' !== $normalized_action['inputKey'] ) {
				$seen_keys[] = $normalized_action['inputKey'];
			}

			$normalized_menu['dtmfActions'][] = $normalized_action;
		}

		return $normalized_menu;
	}

	/**
	 * Normalize menu input settings.
	 *
	 * @since    1.0.0
	 * @param    array     $settings       Raw input settings.
	 * @param    string    $field_prefix   Validation field prefix.
	 * @return   array
	 */
	private function normalize_menu_input_settings( $settings, $field_prefix ) {
		$settings = is_array( $settings ) ? $settings : array();
		$raw_settings = array(
			'maxDigits'         => $this->get_value( $settings, array( 'maxDigits', 'max_digits' ), 1 ),
			'timeoutSeconds'    => $this->get_value( $settings, array( 'timeoutSeconds', 'timeout_seconds' ), 5 ),
			'maxInvalidRetries' => $this->get_value( $settings, array( 'maxInvalidRetries', 'max_invalid_retries' ), 2 ),
			'maxTimeoutRetries' => $this->get_value( $settings, array( 'maxTimeoutRetries', 'max_timeout_retries' ), 1 ),
		);

		$normalized_settings = array(
			'maxDigits'         => $this->integer_value( $raw_settings['maxDigits'] ),
			'timeoutSeconds'    => $this->integer_value( $raw_settings['timeoutSeconds'] ),
			'maxInvalidRetries' => $this->integer_value( $raw_settings['maxInvalidRetries'] ),
			'maxTimeoutRetries' => $this->integer_value( $raw_settings['maxTimeoutRetries'] ),
		);

		foreach ( $raw_settings as $setting_key => $raw_value ) {
			if ( ! $this->is_integer_like( $raw_value ) ) {
				$this->add_error( $field_prefix . '.' . $setting_key, 'INTEGER_REQUIRED', __( 'This field must be an integer.', 'wp-dialyra' ) );
			}
		}

		foreach ( array( 'maxDigits', 'timeoutSeconds' ) as $positive_field ) {
			if ( $normalized_settings[ $positive_field ] < 1 ) {
				$this->add_error( $field_prefix . '.' . $positive_field, 'POSITIVE_INTEGER_REQUIRED', __( 'This field must be a positive integer.', 'wp-dialyra' ) );
			}
		}

		foreach ( array( 'maxInvalidRetries', 'maxTimeoutRetries' ) as $zero_or_positive_field ) {
			if ( $normalized_settings[ $zero_or_positive_field ] < 0 ) {
				$this->add_error( $field_prefix . '.' . $zero_or_positive_field, 'ZERO_OR_POSITIVE_INTEGER_REQUIRED', __( 'This field must be zero or a positive integer.', 'wp-dialyra' ) );
			}
		}

		return $normalized_settings;
	}

	/**
	 * Normalize a DTMF action.
	 *
	 * @since    1.0.0
	 * @param    array     $action         Raw DTMF action.
	 * @param    string    $field_prefix   Validation field prefix.
	 * @return   array
	 */
	private function normalize_dtmf_action( $action, $field_prefix ) {
		return array(
			'inputKey'        => $this->dtmf_key_value( $this->get_value( $action, array( 'inputKey', 'input_key' ) ) ),
			'responseMessage' => $this->normalize_message( $this->get_value( $action, array( 'responseMessage', 'response_message' ), array( 'type' => 'none' ) ), $field_prefix . '.responseMessage', self::MESSAGE_CONTEXT_RESPONSE ),
			'businessAction'  => $this->normalize_business_action( $this->get_value( $action, array( 'businessAction', 'business_action' ), array( 'type' => 'no_action' ) ), $field_prefix . '.businessAction' ),
			'nextStep'        => $this->normalize_next_step( $this->get_value( $action, array( 'nextStep', 'next_step' ), array() ), $field_prefix . '.nextStep' ),
		);
	}

	/**
	 * Normalize invalid-input behavior.
	 *
	 * @since    1.0.0
	 * @param    array     $handling       Raw handling state.
	 * @param    string    $field_prefix   Validation field prefix.
	 * @return   array
	 */
	private function normalize_invalid_input_handling( $handling, $field_prefix ) {
		$handling = is_array( $handling ) ? $handling : array();

		return array(
			'responseMessage'            => $this->normalize_message( $this->get_value( $handling, array( 'responseMessage', 'response_message' ), array( 'type' => 'none' ) ), $field_prefix . '.responseMessage', self::MESSAGE_CONTEXT_RESPONSE ),
			'afterMaxInvalidRetryAction' => $this->normalize_next_step( $this->get_value( $handling, array( 'afterMaxInvalidRetryAction', 'after_max_invalid_retry_action', 'nextStep', 'next_step' ), array( 'type' => 'repeat_current_menu' ) ), $field_prefix . '.afterMaxInvalidRetryAction' ),
		);
	}

	/**
	 * Normalize timeout behavior.
	 *
	 * @since    1.0.0
	 * @param    array     $handling       Raw handling state.
	 * @param    string    $field_prefix   Validation field prefix.
	 * @return   array
	 */
	private function normalize_timeout_handling( $handling, $field_prefix ) {
		$handling = is_array( $handling ) ? $handling : array();

		return array(
			'responseMessage' => $this->normalize_message( $this->get_value( $handling, array( 'responseMessage', 'response_message' ), array( 'type' => 'none' ) ), $field_prefix . '.responseMessage', self::MESSAGE_CONTEXT_RESPONSE ),
			'nextStep'        => $this->normalize_next_step( $this->get_value( $handling, array( 'nextStep', 'next_step' ), array( 'type' => 'repeat_current_menu' ) ), $field_prefix . '.nextStep' ),
		);
	}

	/**
	 * Normalize transfer fallback behavior.
	 *
	 * @since    1.0.0
	 * @param    array|string    $fallback       Raw fallback state.
	 * @param    string          $field_prefix   Validation field prefix.
	 * @return   array
	 */
	private function normalize_transfer_fallback( $fallback, $field_prefix ) {
		$fallback = is_array( $fallback ) ? $fallback : array();

		return array(
			'responseMessage' => $this->normalize_message( $this->get_value( $fallback, array( 'responseMessage', 'response_message' ), array( 'type' => 'none' ) ), $field_prefix . '.responseMessage', self::MESSAGE_CONTEXT_RESPONSE ),
			'nextStep'        => $this->normalize_next_step( $this->get_value( $fallback, array( 'nextStep', 'next_step' ), array( 'type' => 'hangup' ) ), $field_prefix . '.nextStep' ),
		);
	}

	/**
	 * Normalize a message object.
	 *
	 * @since    1.0.0
	 * @param    array     $message       Raw message state.
	 * @param    string    $field_prefix  Validation field prefix.
	 * @param    string    $context       Message context.
	 * @return   array
	 */
	private function normalize_message( $message, $field_prefix, $context ) {
		$message = is_array( $message ) ? $message : array();
		$type    = $this->message_type_value( $this->get_value( $message, array( 'type', 'messageType', 'message_type' ), self::MESSAGE_CONTEXT_RESPONSE === $context ? 'none' : '' ) );

		if ( self::MESSAGE_CONTEXT_INSTRUCTION === $context && 'none' === $type ) {
			$type = '';
		}

		if ( ! in_array( $type, $this->allowed_message_types( $context ), true ) ) {
			$this->add_error( $field_prefix . '.type', 'INVALID_MESSAGE_TYPE', __( 'Message type is invalid.', 'wp-dialyra' ) );

			return array( 'type' => $type );
		}

		if ( 'none' === $type ) {
			return array( 'type' => 'none' );
		}

		if ( 'audio' === $type ) {
			$audio_asset_id = absint( $this->get_value( $message, array( 'audioAssetId', 'audio_asset_id', 'audio' ) ) );

			if ( $audio_asset_id < 1 ) {
				$this->add_error( $field_prefix . '.audioAssetId', 'VALID_AUDIO_ASSET_REQUIRED', __( 'Audio asset must be a positive integer.', 'wp-dialyra' ) );
			}

			return array(
				'type'         => 'audio',
				'audioAssetId' => $audio_asset_id,
			);
		}

		$normalized_message = array(
			'type'     => 'tts',
			'message'  => $this->string_value( $this->get_value( $message, array( 'message', 'text' ) ) ),
			'language' => $this->string_value( $this->get_value( $message, array( 'language' ) ) ),
			'provider' => $this->string_value( $this->get_value( $message, array( 'provider' ) ) ),
			'voice'    => $this->string_value( $this->get_value( $message, array( 'voice' ) ) ),
		);

		foreach ( array( 'message', 'language', 'provider', 'voice' ) as $required_field ) {
			if ( '' === $normalized_message[ $required_field ] ) {
				$this->add_error( $field_prefix . '.' . $required_field, 'TTS_FIELD_REQUIRED', __( 'TTS message, language, provider, and voice are required.', 'wp-dialyra' ) );
			}
		}

		return $normalized_message;
	}

	/**
	 * Normalize a business action.
	 *
	 * @since    1.0.0
	 * @param    array     $business_action   Raw business action.
	 * @param    string    $field_prefix      Validation field prefix.
	 * @return   array
	 */
	private function normalize_business_action( $business_action, $field_prefix ) {
		$business_action = is_array( $business_action ) ? $business_action : array();
		$type            = sanitize_key( $this->get_value( $business_action, array( 'type' ), 'no_action' ) );

		if ( ! in_array( $type, array( 'no_action', 'confirm_order', 'cancel_order', 'transfer_department' ), true ) ) {
			$this->add_error( $field_prefix . '.type', 'INVALID_BUSINESS_ACTION', __( 'Business action type is invalid.', 'wp-dialyra' ) );
			$type = 'no_action';
		}

		if ( 'transfer_department' !== $type ) {
			return array( 'type' => $type );
		}

		$department_id = $this->extract_department_id( $business_action );

		if ( $department_id < 1 ) {
			$this->add_error( $field_prefix . '.departmentId', 'VALID_DEPARTMENT_REQUIRED', __( 'Transfer department must be a positive integer.', 'wp-dialyra' ) );
		}

		return array(
			'type'         => 'transfer_department',
			'departmentId' => $department_id,
		);
	}

	/**
	 * Normalize a next-step object.
	 *
	 * @since    1.0.0
	 * @param    array     $next_step      Raw next-step state.
	 * @param    string    $field_prefix   Validation field prefix.
	 * @return   array
	 */
	private function normalize_next_step( $next_step, $field_prefix ) {
		$next_step = is_array( $next_step ) ? $next_step : array();
		$type      = sanitize_key( $this->get_value( $next_step, array( 'type' ), '' ) );

		if ( ! in_array( $type, array( 'repeat_current_menu', 'go_to_menu', 'hangup', 'end_flow' ), true ) ) {
			$this->add_error( $field_prefix . '.type', 'INVALID_NEXT_STEP', __( 'Next step type is invalid.', 'wp-dialyra' ) );
			$type = '';
		}

		if ( 'go_to_menu' !== $type ) {
			return array( 'type' => $type );
		}

		$target_menu_id = $this->menu_reference_value(
			$this->get_value(
				$next_step,
				array(
					'targetMenuId',
					'targetMenu',
					'target_menu_id',
					'target_menu',
					'transfer_failed_target_menu',
					'transfer_timeout_target_menu',
				)
			)
		);

		if ( '' === $target_menu_id ) {
			$this->add_error( $field_prefix . '.targetMenuId', 'TARGET_MENU_REQUIRED', __( 'Target menu is required.', 'wp-dialyra' ) );
		} elseif ( ! in_array( $target_menu_id, $this->menu_ids, true ) ) {
			$this->add_error( $field_prefix . '.targetMenuId', 'INVALID_TARGET_MENU', __( 'The selected target menu does not exist.', 'wp-dialyra' ) );
		}

		return array(
			'type'         => 'go_to_menu',
			'targetMenuId' => $target_menu_id,
		);
	}

	/**
	 * Extract department ID from normalized or dynamic UI keys.
	 *
	 * @since    1.0.0
	 * @param    array    $business_action    Raw business action.
	 * @return   int
	 */
	private function extract_department_id( $business_action ) {
		$department_id = absint( $this->get_value( $business_action, array( 'departmentId', 'department_id', 'department' ) ) );

		if ( $department_id ) {
			return $department_id;
		}

		foreach ( $business_action as $key => $value ) {
			if ( is_string( $key ) && 0 === strpos( $key, 'department_target_' ) ) {
				return absint( $value );
			}
		}

		return 0;
	}

	/**
	 * Return allowed message types for a context.
	 *
	 * @since    1.0.0
	 * @param    string    $context    Message context.
	 * @return   array
	 */
	private function allowed_message_types( $context ) {
		if ( self::MESSAGE_CONTEXT_RESPONSE === $context ) {
			return array( 'none', 'tts', 'audio' );
		}

		return array( 'tts', 'audio' );
	}

	/**
	 * Add a validation error.
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
	 * Get the first existing value from possible keys.
	 *
	 * @since    1.0.0
	 * @param    array    $source     Source array.
	 * @param    array    $keys       Possible keys.
	 * @param    mixed    $default    Default value.
	 * @return   mixed
	 */
	private function get_value( $source, $keys, $default = '' ) {
		if ( ! is_array( $source ) ) {
			return $default;
		}

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $source ) ) {
				return $source[ $key ];
			}
		}

		return $default;
	}

	/**
	 * Sanitize a normal string value.
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
	 * Sanitize a menu reference value.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
	 * @return   string
	 */
	private function menu_reference_value( $value ) {
		return sanitize_key( str_replace( '-', '_', $this->string_value( $value ) ) );
	}

	/**
	 * Sanitize a DTMF key.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
	 * @return   string
	 */
	private function dtmf_key_value( $value ) {
		$value = $this->string_value( $value );

		return in_array( $value, array( '1', '2', '3', '4', '5', '6', '7', '8', '9' ), true ) ? $value : '';
	}

	/**
	 * Sanitize a message type.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
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
	 * Convert value to integer without mutating UI state.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw value.
	 * @return   int
	 */
	private function integer_value( $value ) {
		return is_numeric( $value ) ? intval( $value ) : 0;
	}

	/**
	 * Check whether a value represents an integer.
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
