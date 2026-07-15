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

$wp_dialyra_plugin        = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
$wp_dialyra_api_endpoints = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_api_endpoints() : null;
$wp_dialyra_business_id   = class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
$wp_dialyra_error         = '';
$wp_dialyra_success       = '';
$wp_dialyra_agents        = array();
$wp_dialyra_extensions    = array();
$wp_dialyra_departments   = array();
$wp_dialyra_users         = array();
$wp_dialyra_sip_domain    = defined( 'WP_DIALYRA_SIP_DOMAIN' ) ? sanitize_text_field( WP_DIALYRA_SIP_DOMAIN ) : 'dialyra.com';

$wp_dialyra_agent_statuses = array(
	'active'    => __( 'active', 'wp-dialyra' ),
	'inactive'  => __( 'inactive', 'wp-dialyra' ),
	'suspended' => __( 'suspended', 'wp-dialyra' ),
	'deleted'   => __( 'deleted', 'wp-dialyra' ),
);

$wp_dialyra_availability_statuses = array(
	'available'       => __( 'available', 'wp-dialyra' ),
	'busy'            => __( 'busy', 'wp-dialyra' ),
	'offline'         => __( 'offline', 'wp-dialyra' ),
	'paused'          => __( 'paused', 'wp-dialyra' ),
	'after_call_work' => __( 'after_call_work', 'wp-dialyra' ),
);

$wp_dialyra_extract_data = static function ( $response ) {
	if ( ! $response || ! is_object( $response ) || ! method_exists( $response, 'is_successful' ) || ! $response->is_successful() || ! method_exists( $response, 'get_data' ) ) {
		return array();
	}

	$data = $response->get_data();
	$data = is_array( $data ) ? $data : array();

	if ( isset( $data['data'] ) && is_array( $data['data'] ) && ! isset( $data['id'] ) && ! isset( $data['items'] ) ) {
		$data = $data['data'];
	}

	return $data;
};

$wp_dialyra_extract_items = static function ( $response ) use ( $wp_dialyra_extract_data ) {
	$data = $wp_dialyra_extract_data( $response );

	foreach ( array( 'items', 'agents', 'extensions', 'departments', 'users', 'data' ) as $container_key ) {
		if ( isset( $data[ $container_key ] ) && is_array( $data[ $container_key ] ) ) {
			return $data[ $container_key ];
		}
	}

	return isset( $data[0] ) && is_array( $data[0] ) ? $data : array();
};

$wp_dialyra_normalize_skills = static function ( $skills ) {
	if ( is_string( $skills ) ) {
		$decoded = json_decode( $skills, true );
		$skills  = is_array( $decoded ) ? $decoded : array();
	}

	$skills = is_array( $skills ) ? $skills : array();

	if ( isset( $skills['language'] ) && is_array( $skills['language'] ) ) {
		$skills['language'] = array_values( array_filter( array_map( 'sanitize_text_field', $skills['language'] ) ) );
	}

	return $skills;
};

$wp_dialyra_skills_to_label = static function ( $skills ) use ( $wp_dialyra_normalize_skills ) {
	$skills    = $wp_dialyra_normalize_skills( $skills );
	$languages = ! empty( $skills['language'] ) && is_array( $skills['language'] ) ? $skills['language'] : array();

	return ! empty( $languages ) ? implode( ', ', array_map( 'sanitize_text_field', $languages ) ) : __( 'None', 'wp-dialyra' );
};

$wp_dialyra_parse_language_skills = static function ( $raw_value ) {
	$languages = array_map( 'trim', explode( ',', sanitize_text_field( $raw_value ) ) );
	$languages = array_values( array_filter( $languages ) );

	return ! empty( $languages ) ? array( 'language' => $languages ) : array();
};

$wp_dialyra_normalize_agent = static function ( $agent ) use ( $wp_dialyra_normalize_skills ) {
	$agent    = is_array( $agent ) ? $agent : array();
	$metadata = isset( $agent['metadata'] ) && is_array( $agent['metadata'] ) ? $agent['metadata'] : array();

	return array(
		'id'                   => isset( $agent['id'] ) ? absint( $agent['id'] ) : 0,
		'user_id'              => isset( $agent['user_id'] ) ? absint( $agent['user_id'] ) : 0,
		'name'                 => ! empty( $agent['name'] ) ? sanitize_text_field( $agent['name'] ) : __( 'Unnamed agent', 'wp-dialyra' ),
		'email'                => ! empty( $agent['email'] ) ? sanitize_email( $agent['email'] ) : '',
		'phone'                => ! empty( $agent['phone'] ) ? sanitize_text_field( $agent['phone'] ) : '',
		'sip_extension'        => ! empty( $agent['sip_extension'] ) ? sanitize_text_field( $agent['sip_extension'] ) : '',
		'sip_username'         => ! empty( $agent['sip_username'] ) ? sanitize_text_field( $agent['sip_username'] ) : '',
		'sip_endpoint'         => ! empty( $agent['sip_endpoint'] ) ? sanitize_text_field( $agent['sip_endpoint'] ) : '',
		'status'               => ! empty( $agent['status'] ) ? sanitize_key( $agent['status'] ) : 'active',
		'availability_status'  => ! empty( $agent['availability_status'] ) ? sanitize_key( $agent['availability_status'] ) : 'offline',
		'max_concurrent_calls' => isset( $agent['max_concurrent_calls'] ) ? max( 1, absint( $agent['max_concurrent_calls'] ) ) : 1,
		'current_active_calls' => isset( $agent['current_active_calls'] ) ? absint( $agent['current_active_calls'] ) : 0,
		'skills'               => $wp_dialyra_normalize_skills( isset( $agent['skills'] ) ? $agent['skills'] : array() ),
		'team'                 => ! empty( $metadata['team'] ) ? sanitize_text_field( $metadata['team'] ) : '',
	);
};

$wp_dialyra_normalize_extension = static function ( $extension ) {
	$extension = is_array( $extension ) ? $extension : array();

	return array(
		'id'           => isset( $extension['id'] ) ? absint( $extension['id'] ) : 0,
		'agent_id'     => isset( $extension['agent_id'] ) ? absint( $extension['agent_id'] ) : 0,
		'user_id'      => isset( $extension['user_id'] ) ? absint( $extension['user_id'] ) : 0,
		'agent_name'   => ! empty( $extension['agent_name'] ) ? sanitize_text_field( $extension['agent_name'] ) : '',
		'display_name' => ! empty( $extension['display_name'] ) ? sanitize_text_field( $extension['display_name'] ) : '',
		'extension'    => ! empty( $extension['extension'] ) ? sanitize_text_field( $extension['extension'] ) : '',
		'sip_username' => ! empty( $extension['sip_username'] ) ? sanitize_text_field( $extension['sip_username'] ) : '',
		'sip_endpoint' => ! empty( $extension['sip_endpoint'] ) ? sanitize_text_field( $extension['sip_endpoint'] ) : '',
		'is_primary'   => ! empty( $extension['is_primary'] ),
		'is_active'    => ! empty( $extension['is_active'] ),
	);
};

$wp_dialyra_normalize_department = static function ( $department ) {
	$department = is_array( $department ) ? $department : array();

	return array(
		'id'     => isset( $department['id'] ) ? absint( $department['id'] ) : 0,
		'name'   => ! empty( $department['name'] ) ? sanitize_text_field( $department['name'] ) : __( 'Untitled department', 'wp-dialyra' ),
		'status' => ! empty( $department['status'] ) ? sanitize_key( $department['status'] ) : 'active',
	);
};

$wp_dialyra_normalize_user = static function ( $user ) {
	$user        = is_array( $user ) ? $user : array();
	$memberships = isset( $user['memberships'] ) && is_array( $user['memberships'] ) ? $user['memberships'] : array();

	if ( isset( $user['user'] ) && is_array( $user['user'] ) ) {
		$user = $user['user'];
	}

	return array(
		'id'          => isset( $user['id'] ) ? absint( $user['id'] ) : ( isset( $user['user_id'] ) ? absint( $user['user_id'] ) : 0 ),
		'full_name'   => ! empty( $user['full_name'] ) ? sanitize_text_field( $user['full_name'] ) : ( ! empty( $user['name'] ) ? sanitize_text_field( $user['name'] ) : '' ),
		'email'       => ! empty( $user['email'] ) ? sanitize_email( $user['email'] ) : '',
		'status'      => ! empty( $user['status'] ) ? sanitize_key( $user['status'] ) : '',
		'memberships' => $memberships,
	);
};

$wp_dialyra_is_agent_user = static function ( $user ) use ( $wp_dialyra_business_id ) {
	if ( empty( $user['id'] ) || ( ! empty( $user['status'] ) && 'active' !== $user['status'] ) ) {
		return false;
	}

	if ( empty( $user['memberships'] ) ) {
		return true;
	}

	foreach ( $user['memberships'] as $membership ) {
		$membership = is_array( $membership ) ? $membership : array();

		if (
			absint( $membership['business_id'] ?? 0 ) === $wp_dialyra_business_id
			&& 'agent' === sanitize_key( $membership['membership_role'] ?? '' )
			&& 'active' === sanitize_key( $membership['membership_status'] ?? '' )
		) {
			return true;
		}
	}

	return false;
};

$wp_dialyra_user_label = static function ( $user ) {
	$name  = ! empty( $user['full_name'] ) ? sanitize_text_field( $user['full_name'] ) : sprintf( /* translators: %d: user ID. */ __( 'User #%d', 'wp-dialyra' ), absint( $user['id'] ) );
	$email = ! empty( $user['email'] ) ? sanitize_email( $user['email'] ) : '';

	return $email ? $name . ' · ' . $email : $name;
};

$wp_dialyra_extension_label = static function ( $extension ) {
	$name = ! empty( $extension['display_name'] ) ? $extension['display_name'] : ( ! empty( $extension['agent_name'] ) ? $extension['agent_name'] : __( 'Available SIP', 'wp-dialyra' ) );

	return sprintf( '%1$s · %2$s', $name, $extension['extension'] );
};

$wp_dialyra_extension_value = static function ( $extension ) {
	return ! empty( $extension['extension'] ) ? $extension['extension'] : '';
};

if ( $wp_dialyra_api_endpoints && $wp_dialyra_business_id ) {
	$users_response = $wp_dialyra_api_endpoints->get_users();
	if ( $users_response && $users_response->is_successful() ) {
		$wp_dialyra_users = array_values( array_filter( array_map( $wp_dialyra_normalize_user, $wp_dialyra_extract_items( $users_response ) ), $wp_dialyra_is_agent_user ) );
	}
}

$wp_dialyra_user_by_id = array();
foreach ( $wp_dialyra_users as $user ) {
	$wp_dialyra_user_by_id[ absint( $user['id'] ) ] = $user;
}

if ( 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) && isset( $_POST['wp_dialyra_agents_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_dialyra_agents_nonce'] ), 'wp-dialyra-agents' ) ) {
	$action = isset( $_POST['wp_dialyra_agent_action'] ) ? sanitize_key( wp_unslash( $_POST['wp_dialyra_agent_action'] ) ) : '';

	if ( ! $wp_dialyra_api_endpoints || ! $wp_dialyra_business_id ) {
		$wp_dialyra_error = esc_html__( 'Connect a Dialyra business before managing agents.', 'wp-dialyra' );
	} elseif ( 'create_agent' === $action ) {
		$agent_user_mode = isset( $_POST['agent_user_mode'] ) ? sanitize_key( wp_unslash( $_POST['agent_user_mode'] ) ) : 'new';
		$agent_name      = '';
		$agent_email     = '';
		$user_id         = 0;

		if ( 'existing' === $agent_user_mode ) {
			$user_id       = isset( $_POST['existing_user_id'] ) ? absint( wp_unslash( $_POST['existing_user_id'] ) ) : 0;
			$existing_user = $user_id && isset( $wp_dialyra_user_by_id[ $user_id ] ) ? $wp_dialyra_user_by_id[ $user_id ] : array();

			if ( empty( $existing_user ) ) {
				$wp_dialyra_error = esc_html__( 'Select an active agent user before creating the agent profile.', 'wp-dialyra' );
			} else {
				$agent_name  = ! empty( $existing_user['full_name'] ) ? $existing_user['full_name'] : sprintf( /* translators: %d: user ID. */ __( 'User #%d', 'wp-dialyra' ), $user_id );
				$agent_email = ! empty( $existing_user['email'] ) ? $existing_user['email'] : '';
			}
		} else {
			$agent_name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$agent_email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			$agent_password   = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';
			$confirm_password = isset( $_POST['confirm_password'] ) ? sanitize_text_field( wp_unslash( $_POST['confirm_password'] ) ) : '';

			if ( empty( $agent_name ) || empty( $agent_email ) || empty( $agent_password ) ) {
				$wp_dialyra_error = esc_html__( 'Agent name, email, and password are required.', 'wp-dialyra' );
			} elseif ( $agent_password !== $confirm_password ) {
				$wp_dialyra_error = esc_html__( 'Password confirmation does not match.', 'wp-dialyra' );
			} else {
				$user_response = $wp_dialyra_api_endpoints->create_user(
					array(
						'full_name'       => $agent_name,
						'email'           => $agent_email,
						'password'        => $agent_password,
						'role'            => 'general',
						'business_id'     => $wp_dialyra_business_id,
						'membership_role' => 'agent',
					)
				);
				$user_data     = $wp_dialyra_extract_data( $user_response );

				if ( $user_response && $user_response->is_successful() ) {
					if ( isset( $user_data['user'] ) && is_array( $user_data['user'] ) ) {
						$user_data = $user_data['user'];
					}

					$user_id = isset( $user_data['id'] ) ? absint( $user_data['id'] ) : ( isset( $user_data['user_id'] ) ? absint( $user_data['user_id'] ) : 0 );
				}

				if ( ! $user_id ) {
					$wp_dialyra_error = $user_response ? $user_response->get_message() : esc_html__( 'User could not be created.', 'wp-dialyra' );
				}
			}
		}

		$payload = array(
			'business_id'             => $wp_dialyra_business_id,
			'user_id'                 => $user_id,
			'name'                    => $agent_name,
			'email'                   => $agent_email,
			'phone'                   => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'sip_extension'           => isset( $_POST['sip_extension'] ) ? sanitize_text_field( wp_unslash( $_POST['sip_extension'] ) ) : '',
			'status'                  => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
			'availability_status'     => isset( $_POST['availability_status'] ) ? sanitize_key( wp_unslash( $_POST['availability_status'] ) ) : 'offline',
			'max_concurrent_calls'    => isset( $_POST['max_concurrent_calls'] ) ? max( 1, absint( wp_unslash( $_POST['max_concurrent_calls'] ) ) ) : 1,
			'current_active_calls'    => 0,
			'skills'                  => isset( $_POST['skills_language'] ) ? $wp_dialyra_parse_language_skills( wp_unslash( $_POST['skills_language'] ) ) : array(),
			'metadata'                => array(
				'team' => isset( $_POST['team'] ) ? sanitize_text_field( wp_unslash( $_POST['team'] ) ) : '',
			),
		);

		if ( empty( $wp_dialyra_error ) ) {
			$response = $wp_dialyra_api_endpoints->create_agent( $payload );
			$wp_dialyra_success = $response && $response->is_successful() ? ( 'existing' === $agent_user_mode ? esc_html__( 'Agent created for existing user.', 'wp-dialyra' ) : esc_html__( 'User and agent created successfully.', 'wp-dialyra' ) ) : '';
			$wp_dialyra_error   = $wp_dialyra_success ? '' : ( $response ? $response->get_message() : esc_html__( 'Agent service is not available.', 'wp-dialyra' ) );
		}
	} elseif ( 'update_agent' === $action ) {
		$agent_id = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;
		$payload  = array(
			'name'                 => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'phone'                => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'status'               => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
			'availability_status'  => isset( $_POST['availability_status'] ) ? sanitize_key( wp_unslash( $_POST['availability_status'] ) ) : 'offline',
			'max_concurrent_calls' => isset( $_POST['max_concurrent_calls'] ) ? max( 1, absint( wp_unslash( $_POST['max_concurrent_calls'] ) ) ) : 1,
			'skills'               => isset( $_POST['skills_language'] ) ? $wp_dialyra_parse_language_skills( wp_unslash( $_POST['skills_language'] ) ) : array(),
		);
		$response = $agent_id ? $wp_dialyra_api_endpoints->update_agent( $agent_id, $payload ) : false;
		$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Agent updated successfully.', 'wp-dialyra' ) : '';
		$wp_dialyra_error   = $wp_dialyra_success ? '' : ( $response ? $response->get_message() : esc_html__( 'Choose an agent before saving.', 'wp-dialyra' ) );
	} elseif ( 'delete_agent' === $action ) {
		$agent_id = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;
		$response = $agent_id ? $wp_dialyra_api_endpoints->delete_agent( $agent_id ) : false;
		$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Agent deleted successfully.', 'wp-dialyra' ) : '';
		$wp_dialyra_error   = $wp_dialyra_success ? '' : ( $response ? $response->get_message() : esc_html__( 'Choose an agent before deleting.', 'wp-dialyra' ) );
	} elseif ( 'update_agent_status' === $action ) {
		$agent_id = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;
		$payload  = array(
			'status'              => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
			'availability_status' => isset( $_POST['availability_status'] ) ? sanitize_key( wp_unslash( $_POST['availability_status'] ) ) : 'offline',
		);
		$response = $agent_id ? $wp_dialyra_api_endpoints->update_agent( $agent_id, $payload ) : false;
		$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Agent status updated.', 'wp-dialyra' ) : '';
		$wp_dialyra_error   = $wp_dialyra_success ? '' : ( $response ? $response->get_message() : esc_html__( 'Choose an agent before updating status.', 'wp-dialyra' ) );
	} elseif ( 'create_extension' === $action ) {
		$payload = array(
			'business_id'       => $wp_dialyra_business_id,
			'extension'         => isset( $_POST['extension'] ) ? sanitize_text_field( wp_unslash( $_POST['extension'] ) ) : '',
			'password'          => isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '',
			'display_name'      => isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '',
			'transport'         => isset( $_POST['transport'] ) ? sanitize_text_field( wp_unslash( $_POST['transport'] ) ) : 'transport-udp',
			'context'           => isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : 'dialyra-ivr',
			'allow'             => isset( $_POST['allow'] ) ? sanitize_text_field( wp_unslash( $_POST['allow'] ) ) : 'ulaw,alaw',
			'dtmf_mode'         => isset( $_POST['dtmf_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['dtmf_mode'] ) ) : 'rfc4733',
			'max_contacts'      => isset( $_POST['max_contacts'] ) ? max( 1, absint( wp_unslash( $_POST['max_contacts'] ) ) ) : 1,
			'qualify_frequency' => isset( $_POST['qualify_frequency'] ) ? absint( wp_unslash( $_POST['qualify_frequency'] ) ) : 30,
			'remove_existing'   => ! empty( $_POST['remove_existing'] ),
		);
		$response = $wp_dialyra_api_endpoints->create_agent_extension( $payload );
		$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Extension provisioned successfully.', 'wp-dialyra' ) : '';
		$wp_dialyra_error   = $wp_dialyra_success ? '' : ( $response ? $response->get_message() : esc_html__( 'Extension service is not available.', 'wp-dialyra' ) );
	} elseif ( 'assign_extension' === $action ) {
		$agent_id = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;
		$response = $agent_id ? $wp_dialyra_api_endpoints->assign_agent_extension(
			$agent_id,
			array(
				'extension'  => isset( $_POST['extension'] ) ? sanitize_text_field( wp_unslash( $_POST['extension'] ) ) : '',
				'is_primary' => ! empty( $_POST['is_primary'] ),
				'transfer'   => ! empty( $_POST['transfer'] ),
			)
		) : false;
		$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Extension bound to agent.', 'wp-dialyra' ) : '';
		$wp_dialyra_error   = $wp_dialyra_success ? '' : ( $response ? $response->get_message() : esc_html__( 'Choose an agent before assigning an extension.', 'wp-dialyra' ) );
	} elseif ( 'assign_department' === $action ) {
		$department_id = isset( $_POST['department_id'] ) ? absint( wp_unslash( $_POST['department_id'] ) ) : 0;
		$response      = $department_id ? $wp_dialyra_api_endpoints->add_department_agent(
			$department_id,
			array(
				'agent_id'  => isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0,
				'priority'  => isset( $_POST['priority'] ) ? max( 1, absint( wp_unslash( $_POST['priority'] ) ) ) : 1,
				'is_active' => ! empty( $_POST['is_active'] ),
			)
		) : false;
		$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Agent assigned to department.', 'wp-dialyra' ) : '';
		$wp_dialyra_error   = $wp_dialyra_success ? '' : ( $response ? $response->get_message() : esc_html__( 'Choose a department before assigning.', 'wp-dialyra' ) );
	}
}

if ( $wp_dialyra_api_endpoints && $wp_dialyra_business_id ) {
	$agents_response = $wp_dialyra_api_endpoints->get_agents( array( 'business_id' => $wp_dialyra_business_id ) );
	if ( $agents_response && $agents_response->is_successful() ) {
		$wp_dialyra_agents = array_values( array_filter( array_map( $wp_dialyra_normalize_agent, $wp_dialyra_extract_items( $agents_response ) ) ) );
	} elseif ( ! $wp_dialyra_error && $agents_response ) {
		$wp_dialyra_error = $agents_response->get_message();
	}

	$extensions_response = $wp_dialyra_api_endpoints->get_agent_extensions( array( 'business_id' => $wp_dialyra_business_id ) );
	if ( $extensions_response && $extensions_response->is_successful() ) {
		$wp_dialyra_extensions = array_values( array_filter( array_map( $wp_dialyra_normalize_extension, $wp_dialyra_extract_items( $extensions_response ) ) ) );
	}

	$departments_response = $wp_dialyra_api_endpoints->get_departments( array( 'business_id' => $wp_dialyra_business_id ) );
	if ( $departments_response && $departments_response->is_successful() ) {
		$wp_dialyra_departments = array_values( array_filter( array_map( $wp_dialyra_normalize_department, $wp_dialyra_extract_items( $departments_response ) ) ) );
	}
}

$wp_dialyra_has_agent_users = ! empty( $wp_dialyra_users );
$wp_dialyra_active_extensions = array_values(
	array_filter(
		$wp_dialyra_extensions,
		static function ( $extension ) {
			return ! empty( $extension['is_active'] ) && ! empty( $extension['extension'] );
		}
	)
);
$wp_dialyra_available_extensions = array_values(
	array_filter(
		$wp_dialyra_active_extensions,
		static function ( $extension ) {
			return empty( $extension['agent_id'] ) && empty( $extension['user_id'] ) && ! empty( $extension['extension'] );
		}
	)
);
$wp_dialyra_extensions_by_agent = array();
foreach ( $wp_dialyra_active_extensions as $extension ) {
	$agent_id = absint( $extension['agent_id'] );

	if ( $agent_id ) {
		if ( ! isset( $wp_dialyra_extensions_by_agent[ $agent_id ] ) ) {
			$wp_dialyra_extensions_by_agent[ $agent_id ] = array();
		}

		$wp_dialyra_extensions_by_agent[ $agent_id ][] = $extension;
	}
}
$wp_dialyra_get_agent_extensions = static function ( $agent ) use ( $wp_dialyra_extensions_by_agent ) {
	$agent_id    = absint( $agent['id'] );
	$extensions  = isset( $wp_dialyra_extensions_by_agent[ $agent_id ] ) ? $wp_dialyra_extensions_by_agent[ $agent_id ] : array();
	$known_values = array();

	foreach ( $extensions as $extension ) {
		if ( ! empty( $extension['extension'] ) ) {
			$known_values[] = $extension['extension'];
		}
	}

	if ( ! empty( $agent['sip_extension'] ) && ! in_array( $agent['sip_extension'], $known_values, true ) ) {
		array_unshift(
			$extensions,
			array(
				'extension'    => $agent['sip_extension'],
				'sip_username' => $agent['sip_username'],
				'is_primary'   => true,
				'is_active'    => true,
			)
		);
	}

	return $extensions;
};
$wp_dialyra_available_count = count(
	array_filter(
		$wp_dialyra_agents,
		static function ( $agent ) {
			return 'available' === $agent['availability_status'];
		}
	)
);
$wp_dialyra_at_capacity_count = count(
	array_filter(
		$wp_dialyra_agents,
		static function ( $agent ) {
			return $agent['current_active_calls'] >= $agent['max_concurrent_calls'];
		}
	)
);
$wp_dialyra_offline_count = count(
	array_filter(
		$wp_dialyra_agents,
		static function ( $agent ) {
			return 'offline' === $agent['availability_status'];
		}
	)
);
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
		</div>
	</div>

	<?php if ( $wp_dialyra_error ) : ?>
		<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--error">
			<span class="dashicons dashicons-warning" aria-hidden="true"></span>
			<p><?php echo esc_html( $wp_dialyra_error ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $wp_dialyra_success ) : ?>
		<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--success">
			<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
			<p><?php echo esc_html( $wp_dialyra_success ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! $wp_dialyra_business_id ) : ?>
		<div class="wp-dialyra-empty-state">
			<span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span>
			<h3><?php esc_html_e( 'Connect a business first', 'wp-dialyra' ); ?></h3>
			<p><?php esc_html_e( 'Agents belong to a Dialyra business. Finish setup before provisioning extensions.', 'wp-dialyra' ); ?></p>
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=setup' ) ); ?>"><?php esc_html_e( 'Open setup', 'wp-dialyra' ); ?></a>
		</div>
	<?php else : ?>
		<div class="wp-dialyra-agent-strip">
			<div><span><?php esc_html_e( 'Available agents', 'wp-dialyra' ); ?></span><strong><?php echo esc_html( number_format_i18n( $wp_dialyra_available_count ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'Bound extensions', 'wp-dialyra' ); ?></span><strong><?php echo esc_html( number_format_i18n( count( $wp_dialyra_extensions ) ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'At capacity', 'wp-dialyra' ); ?></span><strong><?php echo esc_html( number_format_i18n( $wp_dialyra_at_capacity_count ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'Offline', 'wp-dialyra' ); ?></span><strong><?php echo esc_html( number_format_i18n( $wp_dialyra_offline_count ) ); ?></strong></div>
		</div>

		<section class="wp-dialyra-agent-panel wp-dialyra-agent-panel--wide">
			<div class="wp-dialyra-agent-panel__head wp-dialyra-agent-panel__head--actions">
				<span aria-hidden="true">01</span>
				<div>
					<h3><?php esc_html_e( 'View agents', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Live directory with contact, SIP extension, capacity, and action dialogs.', 'wp-dialyra' ); ?></p>
				</div>
				<div class="wp-dialyra-agent-create-actions">
					<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-dialog-open="wp-dialyra-create-extension-dialog"><span class="dashicons dashicons-phone" aria-hidden="true"></span><?php esc_html_e( 'Create Extension', 'wp-dialyra' ); ?></button>
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button" data-dialyra-dialog-open="wp-dialyra-create-agent-dialog"><span class="dashicons dashicons-businessperson" aria-hidden="true"></span><?php esc_html_e( 'Create Agent', 'wp-dialyra' ); ?></button>
				</div>
			</div>

			<?php if ( empty( $wp_dialyra_agents ) ) : ?>
				<div class="wp-dialyra-empty-state wp-dialyra-empty-state--compact">
					<span class="dashicons dashicons-businessperson" aria-hidden="true"></span>
					<h3><?php esc_html_e( 'No agents found', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Create an agent profile from the button above.', 'wp-dialyra' ); ?></p>
				</div>
			<?php else : ?>
				<div class="wp-dialyra-agent-directory" role="table" aria-label="<?php esc_attr_e( 'Agent directory', 'wp-dialyra' ); ?>">
					<div role="row">
						<span><?php esc_html_e( 'Agent', 'wp-dialyra' ); ?></span>
						<span><?php esc_html_e( 'Contact', 'wp-dialyra' ); ?></span>
						<span><?php esc_html_e( 'Extension', 'wp-dialyra' ); ?></span>
						<span><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></span>
						<span><?php esc_html_e( 'Capacity', 'wp-dialyra' ); ?></span>
						<span><?php esc_html_e( 'Skills', 'wp-dialyra' ); ?></span>
						<span><?php esc_html_e( 'Actions', 'wp-dialyra' ); ?></span>
					</div>
					<?php foreach ( $wp_dialyra_agents as $agent ) : ?>
						<?php
						$dialog_prefix    = 'wp-dialyra-agent-' . absint( $agent['id'] );
						$agent_extensions = $wp_dialyra_get_agent_extensions( $agent );
						?>
						<div role="row">
							<span><strong><?php echo esc_html( $agent['name'] ); ?></strong><small><?php echo esc_html( $agent['team'] ? 'team: ' . $agent['team'] : __( 'No team assigned', 'wp-dialyra' ) ); ?></small></span>
							<span><strong><?php echo esc_html( $agent['email'] ? $agent['email'] : __( 'No email', 'wp-dialyra' ) ); ?></strong><small><?php echo esc_html( $agent['phone'] ? $agent['phone'] : __( 'No phone', 'wp-dialyra' ) ); ?></small></span>
							<span class="wp-dialyra-agent-extension-stack">
								<?php if ( empty( $agent_extensions ) ) : ?>
									<code>—</code><small><?php esc_html_e( 'No extension', 'wp-dialyra' ); ?></small>
								<?php else : ?>
									<span class="wp-dialyra-agent-extension-chips">
										<?php foreach ( array_slice( $agent_extensions, 0, 3 ) as $extension ) : ?>
											<code><?php echo esc_html( $extension['extension'] ); ?><?php echo ! empty( $extension['is_primary'] ) ? esc_html__( ' · primary', 'wp-dialyra' ) : ''; ?></code>
										<?php endforeach; ?>
									</span>
									<small><?php echo esc_html( count( $agent_extensions ) > 3 ? sprintf( /* translators: %d: hidden extension count. */ __( '+%d more extensions', 'wp-dialyra' ), count( $agent_extensions ) - 3 ) : sprintf( /* translators: %d: extension count. */ _n( '%d extension', '%d extensions', count( $agent_extensions ), 'wp-dialyra' ), count( $agent_extensions ) ) ); ?></small>
								<?php endif; ?>
							</span>
							<span><em class="wp-dialyra-result wp-dialyra-result--success"><?php echo esc_html( $agent['status'] ); ?></em><em class="wp-dialyra-result wp-dialyra-result--muted"><?php echo esc_html( $agent['availability_status'] ); ?></em></span>
							<span><strong><?php echo esc_html( $agent['current_active_calls'] . ' / ' . $agent['max_concurrent_calls'] ); ?></strong><small><?php esc_html_e( 'current / max calls', 'wp-dialyra' ); ?></small></span>
							<span><?php echo esc_html( $wp_dialyra_skills_to_label( $agent['skills'] ) ); ?></span>
							<span class="wp-dialyra-department-actions wp-dialyra-agent-actions">
								<button class="wp-dialyra-icon-button" type="button" data-dialyra-dialog-open="<?php echo esc_attr( $dialog_prefix . '-read' ); ?>" title="<?php esc_attr_e( 'View agent', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'View agent', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></button>
								<button class="wp-dialyra-icon-button" type="button" data-dialyra-dialog-open="<?php echo esc_attr( $dialog_prefix . '-edit' ); ?>" title="<?php esc_attr_e( 'Edit agent', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Edit agent', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>
								<button class="wp-dialyra-icon-button wp-dialyra-icon-button--danger" type="button" data-dialyra-dialog-open="<?php echo esc_attr( $dialog_prefix . '-delete' ); ?>" title="<?php esc_attr_e( 'Delete agent', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Delete agent', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
								<button class="wp-dialyra-icon-button" type="button" data-dialyra-dialog-open="<?php echo esc_attr( $dialog_prefix . '-department' ); ?>" title="<?php esc_attr_e( 'Assign to department', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Assign to department', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-groups" aria-hidden="true"></span></button>
								<button class="wp-dialyra-icon-button" type="button" data-dialyra-dialog-open="<?php echo esc_attr( $dialog_prefix . '-extension' ); ?>" title="<?php esc_attr_e( 'Bind extension', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Bind extension', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-phone" aria-hidden="true"></span></button>
								<button class="wp-dialyra-icon-button" type="button" data-dialyra-dialog-open="<?php echo esc_attr( $dialog_prefix . '-status' ); ?>" title="<?php esc_attr_e( 'Update status', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Update status', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-update" aria-hidden="true"></span></button>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</section>

<?php if ( $wp_dialyra_business_id ) : ?>
	<div id="wp-dialyra-create-agent-dialog" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="wp-dialyra-create-agent-title" hidden data-dialyra-dialog>
		<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
		<div class="wp-dialyra-dialog__panel">
			<div class="wp-dialyra-dialog__head">
				<div><p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Create', 'wp-dialyra' ); ?></p><h3 id="wp-dialyra-create-agent-title"><?php esc_html_e( 'Create agent', 'wp-dialyra' ); ?></h3></div>
				<button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>
			<form class="wp-dialyra-agent-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=agents' ) ); ?>" data-dialyra-dynamic-group>
				<?php wp_nonce_field( 'wp-dialyra-agents', 'wp_dialyra_agents_nonce' ); ?>
				<input type="hidden" name="wp_dialyra_agent_action" value="create_agent">
				<div class="wp-dialyra-agent-mode">
					<label class="wp-dialyra-agent-mode__option">
						<input type="radio" name="agent_user_mode" value="new" checked data-dialyra-dynamic-select>
						<span><strong><?php esc_html_e( 'Create new user', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'Create login credentials and the agent profile together.', 'wp-dialyra' ); ?></small></span>
					</label>
					<label class="wp-dialyra-agent-mode__option">
						<input type="radio" name="agent_user_mode" value="existing" data-dialyra-dynamic-select>
						<span><strong><?php esc_html_e( 'Select existing user', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'Use an active agent user already available in this business.', 'wp-dialyra' ); ?></small></span>
					</label>
				</div>
				<div class="wp-dialyra-agent-form__grid">
					<div class="wp-dialyra-settings-row wp-dialyra-agent-form__full" data-dialyra-show-for="existing" hidden>
						<label for="wp-dialyra-agent-existing-user"><?php esc_html_e( 'Agent user', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-agent-existing-user" name="existing_user_id" required disabled>
							<option value=""><?php echo esc_html( empty( $wp_dialyra_users ) ? __( 'No active agent users found', 'wp-dialyra' ) : __( 'Select active agent user', 'wp-dialyra' ) ); ?></option>
							<?php foreach ( $wp_dialyra_users as $user ) : ?>
								<option value="<?php echo esc_attr( $user['id'] ); ?>"><?php echo esc_html( $wp_dialyra_user_label( $user ) ); ?></option>
							<?php endforeach; ?>
						</select>
						<small><?php esc_html_e( 'Dialyra will use this user’s name and email for the new agent profile.', 'wp-dialyra' ); ?></small>
					</div>
					<div class="wp-dialyra-settings-row" data-dialyra-show-for="new"><label for="wp-dialyra-agent-name"><?php esc_html_e( 'Agent name', 'wp-dialyra' ); ?></label><input id="wp-dialyra-agent-name" name="name" type="text" required></div>
					<div class="wp-dialyra-settings-row" data-dialyra-show-for="new"><label for="wp-dialyra-agent-email"><?php esc_html_e( 'Login email', 'wp-dialyra' ); ?></label><input id="wp-dialyra-agent-email" name="email" type="email" required></div>
					<div class="wp-dialyra-settings-row" data-dialyra-show-for="new"><label for="wp-dialyra-agent-password"><?php esc_html_e( 'Password', 'wp-dialyra' ); ?></label><input id="wp-dialyra-agent-password" name="password" type="password" required></div>
					<div class="wp-dialyra-settings-row" data-dialyra-show-for="new"><label for="wp-dialyra-agent-confirm-password"><?php esc_html_e( 'Confirm password', 'wp-dialyra' ); ?></label><input id="wp-dialyra-agent-confirm-password" name="confirm_password" type="password" required></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-agent-phone"><?php esc_html_e( 'Phone', 'wp-dialyra' ); ?></label><input id="wp-dialyra-agent-phone" name="phone" type="tel"></div>
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-agent-sip-extension"><?php esc_html_e( 'SIP extension', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-agent-sip-extension" name="sip_extension">
							<option value=""><?php echo esc_html( empty( $wp_dialyra_available_extensions ) ? __( 'No available SIP extensions', 'wp-dialyra' ) : __( 'Select SIP extension', 'wp-dialyra' ) ); ?></option>
							<?php foreach ( $wp_dialyra_available_extensions as $extension ) : ?>
								<option value="<?php echo esc_attr( $wp_dialyra_extension_value( $extension ) ); ?>"><?php echo esc_html( $wp_dialyra_extension_label( $extension ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-agent-max-calls"><?php esc_html_e( 'Max concurrent calls', 'wp-dialyra' ); ?></label><input id="wp-dialyra-agent-max-calls" name="max_concurrent_calls" type="number" min="1" value="1"></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-agent-team"><?php esc_html_e( 'Team metadata', 'wp-dialyra' ); ?></label><input id="wp-dialyra-agent-team" name="team" type="text" placeholder="support"></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-agent-skills"><?php esc_html_e( 'Language skills', 'wp-dialyra' ); ?></label><input id="wp-dialyra-agent-skills" name="skills_language" type="text" placeholder="bn, en"></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-agent-status"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></label><select id="wp-dialyra-agent-status" name="status"><?php foreach ( $wp_dialyra_agent_statuses as $status_key => $status_label ) : ?><option value="<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $status_label ); ?></option><?php endforeach; ?></select></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-agent-availability"><?php esc_html_e( 'Availability', 'wp-dialyra' ); ?></label><select id="wp-dialyra-agent-availability" name="availability_status"><?php foreach ( $wp_dialyra_availability_statuses as $status_key => $status_label ) : ?><option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status_key, 'offline' ); ?>><?php echo esc_html( $status_label ); ?></option><?php endforeach; ?></select></div>
				</div>
				<div class="wp-dialyra-agent-panel__footer"><button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Create agent', 'wp-dialyra' ); ?></button></div>
			</form>
		</div>
	</div>

	<div id="wp-dialyra-create-extension-dialog" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="wp-dialyra-create-extension-title" hidden data-dialyra-dialog>
		<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
		<div class="wp-dialyra-dialog__panel">
			<div class="wp-dialyra-dialog__head">
				<div><p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Create', 'wp-dialyra' ); ?></p><h3 id="wp-dialyra-create-extension-title"><?php esc_html_e( 'Create extension', 'wp-dialyra' ); ?></h3></div>
				<button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>
			<form class="wp-dialyra-agent-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=agents' ) ); ?>">
				<?php wp_nonce_field( 'wp-dialyra-agents', 'wp_dialyra_agents_nonce' ); ?>
				<input type="hidden" name="wp_dialyra_agent_action" value="create_extension">
				<div class="wp-dialyra-agent-form__grid">
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-extension"><?php esc_html_e( 'Extension', 'wp-dialyra' ); ?></label><input id="wp-dialyra-extension" name="extension" type="text" inputmode="numeric" pattern="[0-9]{2,16}" placeholder="1001" required></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-extension-password"><?php esc_html_e( 'Password', 'wp-dialyra' ); ?></label><input id="wp-dialyra-extension-password" name="password" type="password" required></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-extension-display"><?php esc_html_e( 'Display name', 'wp-dialyra' ); ?></label><input id="wp-dialyra-extension-display" name="display_name" type="text" placeholder="<?php esc_attr_e( 'Agent 1001', 'wp-dialyra' ); ?>"></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-extension-transport"><?php esc_html_e( 'Transport', 'wp-dialyra' ); ?></label><input id="wp-dialyra-extension-transport" name="transport" type="text" value="transport-udp"></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-extension-context"><?php esc_html_e( 'Context', 'wp-dialyra' ); ?></label><input id="wp-dialyra-extension-context" name="context" type="text" value="dialyra-ivr"></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-extension-allow"><?php esc_html_e( 'Allowed codecs', 'wp-dialyra' ); ?></label><input id="wp-dialyra-extension-allow" name="allow" type="text" value="ulaw,alaw"></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-extension-dtmf"><?php esc_html_e( 'DTMF mode', 'wp-dialyra' ); ?></label><input id="wp-dialyra-extension-dtmf" name="dtmf_mode" type="text" value="rfc4733"></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-extension-contacts"><?php esc_html_e( 'Max contacts', 'wp-dialyra' ); ?></label><input id="wp-dialyra-extension-contacts" name="max_contacts" type="number" min="1" value="1"></div>
					<div class="wp-dialyra-settings-row"><label for="wp-dialyra-extension-qualify"><?php esc_html_e( 'Qualify frequency', 'wp-dialyra' ); ?></label><input id="wp-dialyra-extension-qualify" name="qualify_frequency" type="number" min="0" value="30"></div>
				</div>
				<div class="wp-dialyra-toggle-row"><span><?php esc_html_e( 'Remove existing realtime rows first', 'wp-dialyra' ); ?></span><label><input type="checkbox" name="remove_existing" checked><i></i></label></div>
				<div class="wp-dialyra-agent-panel__footer"><button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Create extension', 'wp-dialyra' ); ?></button></div>
			</form>
		</div>
	</div>

	<?php foreach ( $wp_dialyra_agents as $agent ) : ?>
		<?php
		$dialog_prefix    = 'wp-dialyra-agent-' . absint( $agent['id'] );
		$agent_extensions = $wp_dialyra_get_agent_extensions( $agent );
		?>
		<div id="<?php echo esc_attr( $dialog_prefix . '-read' ); ?>" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $dialog_prefix . '-read-title' ); ?>" hidden data-dialyra-dialog>
			<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
			<div class="wp-dialyra-dialog__panel">
				<div class="wp-dialyra-dialog__head"><div><p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Agent profile', 'wp-dialyra' ); ?></p><h3 id="<?php echo esc_attr( $dialog_prefix . '-read-title' ); ?>"><?php echo esc_html( $agent['name'] ); ?></h3></div><button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></div>
				<div class="wp-dialyra-agent-profile-card">
					<div class="wp-dialyra-agent-profile-card__hero">
						<span class="wp-dialyra-agent-profile-card__avatar" aria-hidden="true"><?php echo esc_html( strtoupper( substr( $agent['name'], 0, 1 ) ) ); ?></span>
						<div>
							<h4><?php echo esc_html( $agent['name'] ); ?></h4>
							<p><?php echo esc_html( $agent['team'] ? sprintf( /* translators: %s: team name. */ __( '%s team', 'wp-dialyra' ), $agent['team'] ) : __( 'Dialyra support agent', 'wp-dialyra' ) ); ?></p>
							<div class="wp-dialyra-agent-profile-card__badges">
								<em class="wp-dialyra-result wp-dialyra-result--success"><?php echo esc_html( $agent['status'] ); ?></em>
								<em class="wp-dialyra-result wp-dialyra-result--muted"><?php echo esc_html( $agent['availability_status'] ); ?></em>
							</div>
						</div>
						</div>
						<dl class="wp-dialyra-detail-list wp-dialyra-agent-profile-card__details">
							<div><dt><?php esc_html_e( 'Email', 'wp-dialyra' ); ?></dt><dd><?php echo esc_html( $agent['email'] ? $agent['email'] : '—' ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Phone', 'wp-dialyra' ); ?></dt><dd><?php echo esc_html( $agent['phone'] ? $agent['phone'] : '—' ); ?></dd></div>
						</dl>
						<section class="wp-dialyra-agent-extension-section" aria-label="<?php esc_attr_e( 'SIP extensions', 'wp-dialyra' ); ?>">
							<div class="wp-dialyra-agent-extension-section__head">
								<div>
									<span class="dashicons dashicons-phone" aria-hidden="true"></span>
									<div>
										<h5><?php esc_html_e( 'SIP extensions', 'wp-dialyra' ); ?></h5>
										<p><?php esc_html_e( 'Phone identities this agent can use for SIP calling.', 'wp-dialyra' ); ?></p>
									</div>
								</div>
								<em><?php echo esc_html( sprintf( /* translators: %d: extension count. */ _n( '%d extension', '%d extensions', count( $agent_extensions ), 'wp-dialyra' ), count( $agent_extensions ) ) ); ?></em>
							</div>
							<p class="wp-dialyra-agent-extension-section__hint">
								<?php
								echo wp_kses(
									sprintf(
										/* translators: %s: SIP domain. */
										esc_html__( 'To connect in Linphone, open the app, add a SIP account, then use the SIP username shown below, the extension password created in Dialyra, and domain %s.', 'wp-dialyra' ),
										'<code>' . esc_html( $wp_dialyra_sip_domain ) . '</code>'
									),
									array(
										'code' => array(),
									)
								);
								?>
							</p>
							<?php if ( empty( $agent_extensions ) ) : ?>
								<div class="wp-dialyra-agent-extension-empty">
									<span class="dashicons dashicons-phone" aria-hidden="true"></span>
									<strong><?php esc_html_e( 'No extension assigned yet', 'wp-dialyra' ); ?></strong>
									<small><?php esc_html_e( 'Use Bind extension from the agent row actions to connect one.', 'wp-dialyra' ); ?></small>
								</div>
							<?php else : ?>
								<div class="wp-dialyra-agent-extension-list">
									<?php foreach ( $agent_extensions as $extension ) : ?>
										<article class="wp-dialyra-agent-extension-card">
											<span class="dashicons dashicons-phone" aria-hidden="true"></span>
											<div>
												<strong><?php echo esc_html( $extension['extension'] ); ?></strong>
												<small><span><?php esc_html_e( 'Username:', 'wp-dialyra' ); ?></span> <?php echo esc_html( ! empty( $extension['sip_username'] ) ? $extension['sip_username'] : __( 'SIP user pending', 'wp-dialyra' ) ); ?></small>
											</div>
											<?php if ( ! empty( $extension['is_primary'] ) ) : ?>
												<em><?php esc_html_e( 'Primary', 'wp-dialyra' ); ?></em>
											<?php endif; ?>
										</article>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</section>
						<dl class="wp-dialyra-detail-list wp-dialyra-agent-profile-card__details">
							<div><dt><?php esc_html_e( 'Call capacity', 'wp-dialyra' ); ?></dt><dd><?php echo esc_html( $agent['current_active_calls'] . ' / ' . $agent['max_concurrent_calls'] ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Skills', 'wp-dialyra' ); ?></dt><dd><?php echo esc_html( $wp_dialyra_skills_to_label( $agent['skills'] ) ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Team', 'wp-dialyra' ); ?></dt><dd><?php echo esc_html( $agent['team'] ? $agent['team'] : '—' ); ?></dd></div>
						</dl>
				</div>
				<div class="wp-dialyra-agent-panel__footer"><button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-dialog-close><?php esc_html_e( 'Close', 'wp-dialyra' ); ?></button></div>
			</div>
		</div>

		<div id="<?php echo esc_attr( $dialog_prefix . '-edit' ); ?>" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $dialog_prefix . '-edit-title' ); ?>" hidden data-dialyra-dialog>
			<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
			<div class="wp-dialyra-dialog__panel">
				<div class="wp-dialyra-dialog__head"><div><p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Edit', 'wp-dialyra' ); ?></p><h3 id="<?php echo esc_attr( $dialog_prefix . '-edit-title' ); ?>"><?php echo esc_html( $agent['name'] ); ?></h3></div><button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></div>
				<form class="wp-dialyra-agent-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=agents' ) ); ?>">
					<?php wp_nonce_field( 'wp-dialyra-agents', 'wp_dialyra_agents_nonce' ); ?>
					<input type="hidden" name="wp_dialyra_agent_action" value="update_agent"><input type="hidden" name="agent_id" value="<?php echo esc_attr( $agent['id'] ); ?>">
					<div class="wp-dialyra-agent-form__grid">
						<div class="wp-dialyra-settings-row"><label><?php esc_html_e( 'Name', 'wp-dialyra' ); ?></label><input name="name" type="text" value="<?php echo esc_attr( $agent['name'] ); ?>" required></div>
						<div class="wp-dialyra-settings-row"><label><?php esc_html_e( 'Phone', 'wp-dialyra' ); ?></label><input name="phone" type="tel" value="<?php echo esc_attr( $agent['phone'] ); ?>"></div>
						<div class="wp-dialyra-settings-row"><label><?php esc_html_e( 'Max calls', 'wp-dialyra' ); ?></label><input name="max_concurrent_calls" type="number" min="1" value="<?php echo esc_attr( $agent['max_concurrent_calls'] ); ?>"></div>
						<div class="wp-dialyra-settings-row"><label><?php esc_html_e( 'Language skills', 'wp-dialyra' ); ?></label><input name="skills_language" type="text" value="<?php echo esc_attr( $wp_dialyra_skills_to_label( $agent['skills'] ) ); ?>"></div>
						<div class="wp-dialyra-settings-row"><label><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></label><select name="status"><?php foreach ( $wp_dialyra_agent_statuses as $status_key => $status_label ) : ?><option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $agent['status'], $status_key ); ?>><?php echo esc_html( $status_label ); ?></option><?php endforeach; ?></select></div>
						<div class="wp-dialyra-settings-row"><label><?php esc_html_e( 'Availability', 'wp-dialyra' ); ?></label><select name="availability_status"><?php foreach ( $wp_dialyra_availability_statuses as $status_key => $status_label ) : ?><option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $agent['availability_status'], $status_key ); ?>><?php echo esc_html( $status_label ); ?></option><?php endforeach; ?></select></div>
					</div>
					<div class="wp-dialyra-agent-panel__footer"><button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Save agent', 'wp-dialyra' ); ?></button></div>
				</form>
			</div>
		</div>

		<div id="<?php echo esc_attr( $dialog_prefix . '-delete' ); ?>" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $dialog_prefix . '-delete-title' ); ?>" hidden data-dialyra-dialog>
			<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
			<div class="wp-dialyra-dialog__panel wp-dialyra-dialog__panel--danger">
				<div class="wp-dialyra-dialog__head"><div><p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Delete', 'wp-dialyra' ); ?></p><h3 id="<?php echo esc_attr( $dialog_prefix . '-delete-title' ); ?>"><?php echo esc_html( $agent['name'] ); ?></h3></div><button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></div>
				<p class="wp-dialyra-dialog__warning"><?php esc_html_e( 'This deletes the agent profile. Confirm only when the agent should no longer be managed by Dialyra.', 'wp-dialyra' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=agents' ) ); ?>">
					<?php wp_nonce_field( 'wp-dialyra-agents', 'wp_dialyra_agents_nonce' ); ?>
					<input type="hidden" name="wp_dialyra_agent_action" value="delete_agent"><input type="hidden" name="agent_id" value="<?php echo esc_attr( $agent['id'] ); ?>">
					<div class="wp-dialyra-agent-panel__footer"><button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-dialog-close><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></button><button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Delete agent', 'wp-dialyra' ); ?></button></div>
				</form>
			</div>
		</div>

		<div id="<?php echo esc_attr( $dialog_prefix . '-department' ); ?>" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $dialog_prefix . '-department-title' ); ?>" hidden data-dialyra-dialog>
			<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
			<div class="wp-dialyra-dialog__panel">
				<div class="wp-dialyra-dialog__head"><div><p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Department', 'wp-dialyra' ); ?></p><h3 id="<?php echo esc_attr( $dialog_prefix . '-department-title' ); ?>"><?php echo esc_html( $agent['name'] ); ?></h3></div><button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></div>
				<form class="wp-dialyra-agent-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=agents' ) ); ?>">
					<?php wp_nonce_field( 'wp-dialyra-agents', 'wp_dialyra_agents_nonce' ); ?>
					<input type="hidden" name="wp_dialyra_agent_action" value="assign_department"><input type="hidden" name="agent_id" value="<?php echo esc_attr( $agent['id'] ); ?>">
					<div class="wp-dialyra-agent-form__grid">
						<div class="wp-dialyra-settings-row"><label><?php esc_html_e( 'Department', 'wp-dialyra' ); ?></label><select name="department_id"><?php foreach ( $wp_dialyra_departments as $department ) : ?><option value="<?php echo esc_attr( $department['id'] ); ?>"><?php echo esc_html( $department['name'] ); ?></option><?php endforeach; ?></select></div>
						<div class="wp-dialyra-settings-row"><label><?php esc_html_e( 'Priority', 'wp-dialyra' ); ?></label><input name="priority" type="number" min="1" value="1"></div>
					</div>
					<div class="wp-dialyra-toggle-row"><span><?php esc_html_e( 'Mapping active', 'wp-dialyra' ); ?></span><label><input type="checkbox" name="is_active" checked><i></i></label></div>
					<div class="wp-dialyra-agent-panel__footer"><button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" <?php disabled( empty( $wp_dialyra_departments ) ); ?>><?php esc_html_e( 'Assign to department', 'wp-dialyra' ); ?></button></div>
				</form>
			</div>
		</div>

		<div id="<?php echo esc_attr( $dialog_prefix . '-extension' ); ?>" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $dialog_prefix . '-extension-title' ); ?>" hidden data-dialyra-dialog>
			<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
			<div class="wp-dialyra-dialog__panel">
				<div class="wp-dialyra-dialog__head"><div><p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Extension', 'wp-dialyra' ); ?></p><h3 id="<?php echo esc_attr( $dialog_prefix . '-extension-title' ); ?>"><?php echo esc_html( $agent['name'] ); ?></h3></div><button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></div>
				<form class="wp-dialyra-agent-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=agents' ) ); ?>">
					<?php wp_nonce_field( 'wp-dialyra-agents', 'wp_dialyra_agents_nonce' ); ?>
					<input type="hidden" name="wp_dialyra_agent_action" value="assign_extension"><input type="hidden" name="agent_id" value="<?php echo esc_attr( $agent['id'] ); ?>">
					<div class="wp-dialyra-settings-row"><label><?php esc_html_e( 'Available SIP extension', 'wp-dialyra' ); ?></label><select name="extension"><option value=""><?php echo esc_html( empty( $wp_dialyra_active_extensions ) ? __( 'No available SIP extensions', 'wp-dialyra' ) : __( 'Select SIP extension', 'wp-dialyra' ) ); ?></option><?php foreach ( $wp_dialyra_active_extensions as $extension ) : ?><option value="<?php echo esc_attr( $wp_dialyra_extension_value( $extension ) ); ?>"><?php echo esc_html( $wp_dialyra_extension_label( $extension ) ); ?></option><?php endforeach; ?></select></div>
					<div class="wp-dialyra-agent-toggle-grid">
						<div class="wp-dialyra-toggle-row"><span><?php esc_html_e( 'Set as primary extension', 'wp-dialyra' ); ?></span><label><input type="checkbox" name="is_primary"><i></i></label></div>
						<div class="wp-dialyra-toggle-row"><span><?php esc_html_e( 'Transfer if owned by another agent', 'wp-dialyra' ); ?></span><label><input type="checkbox" name="transfer"><i></i></label></div>
					</div>
					<div class="wp-dialyra-agent-panel__footer"><button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" <?php disabled( empty( $wp_dialyra_active_extensions ) ); ?>><?php esc_html_e( 'Bind extension', 'wp-dialyra' ); ?></button></div>
				</form>
			</div>
		</div>

		<div id="<?php echo esc_attr( $dialog_prefix . '-status' ); ?>" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $dialog_prefix . '-status-title' ); ?>" hidden data-dialyra-dialog>
			<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
			<div class="wp-dialyra-dialog__panel">
				<div class="wp-dialyra-dialog__head"><div><p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></p><h3 id="<?php echo esc_attr( $dialog_prefix . '-status-title' ); ?>"><?php echo esc_html( $agent['name'] ); ?></h3></div><button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></div>
				<form class="wp-dialyra-agent-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=agents' ) ); ?>">
					<?php wp_nonce_field( 'wp-dialyra-agents', 'wp_dialyra_agents_nonce' ); ?>
					<input type="hidden" name="wp_dialyra_agent_action" value="update_agent_status"><input type="hidden" name="agent_id" value="<?php echo esc_attr( $agent['id'] ); ?>">
					<div class="wp-dialyra-agent-form__grid">
						<div class="wp-dialyra-settings-row"><label><?php esc_html_e( 'Profile status', 'wp-dialyra' ); ?></label><select name="status"><?php foreach ( $wp_dialyra_agent_statuses as $status_key => $status_label ) : ?><option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $agent['status'], $status_key ); ?>><?php echo esc_html( $status_label ); ?></option><?php endforeach; ?></select></div>
						<div class="wp-dialyra-settings-row"><label><?php esc_html_e( 'Availability', 'wp-dialyra' ); ?></label><select name="availability_status"><?php foreach ( $wp_dialyra_availability_statuses as $status_key => $status_label ) : ?><option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $agent['availability_status'], $status_key ); ?>><?php echo esc_html( $status_label ); ?></option><?php endforeach; ?></select></div>
					</div>
					<div class="wp-dialyra-agent-panel__footer"><button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Update status', 'wp-dialyra' ); ?></button></div>
				</form>
			</div>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
