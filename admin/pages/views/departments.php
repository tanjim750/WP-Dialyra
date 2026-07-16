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

$wp_dialyra_plugin        = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
$wp_dialyra_api_endpoints = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_api_endpoints() : null;
$wp_dialyra_business_id   = class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
$wp_dialyra_error         = '';
$wp_dialyra_success       = '';
$wp_dialyra_departments   = array();
$wp_dialyra_agents        = array();
$wp_dialyra_department_schedules = array();
$wp_dialyra_department_mappings  = array();
$wp_dialyra_department_live      = array();
$wp_dialyra_default_timezone     = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_default_timezone() : 'UTC';

$wp_dialyra_statuses = array(
	'active'   => __( 'active', 'wp-dialyra' ),
	'inactive' => __( 'inactive', 'wp-dialyra' ),
	'archived' => __( 'archived', 'wp-dialyra' ),
);

$wp_dialyra_strategies = array(
	'least_busy'  => __( 'least_busy', 'wp-dialyra' ),
	'round_robin' => __( 'round_robin', 'wp-dialyra' ),
	'priority'    => __( 'priority', 'wp-dialyra' ),
	'random'      => __( 'random', 'wp-dialyra' ),
	'skill_based' => __( 'skill_based', 'wp-dialyra' ),
);

$wp_dialyra_availability_modes = array(
	'always_open' => __( 'always_open', 'wp-dialyra' ),
	'scheduled'   => __( 'scheduled', 'wp-dialyra' ),
	'closed'      => __( 'closed', 'wp-dialyra' ),
);

$wp_dialyra_days = array(
	'mon' => __( 'Mon', 'wp-dialyra' ),
	'tue' => __( 'Tue', 'wp-dialyra' ),
	'wed' => __( 'Wed', 'wp-dialyra' ),
	'thu' => __( 'Thu', 'wp-dialyra' ),
	'fri' => __( 'Fri', 'wp-dialyra' ),
	'sat' => __( 'Sat', 'wp-dialyra' ),
	'sun' => __( 'Sun', 'wp-dialyra' ),
);

$wp_dialyra_normalize_timezone = static function ( $timezone ) use ( $wp_dialyra_default_timezone ) {
	$timezone = sanitize_text_field( $timezone );

	if ( $timezone && in_array( $timezone, timezone_identifiers_list(), true ) ) {
		return $timezone;
	}

	if ( '+06:00' === $timezone ) {
		return 'Asia/Dhaka';
	}

	return $wp_dialyra_default_timezone;
};

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

	foreach ( array( 'items', 'departments', 'agents', 'mappings', 'data' ) as $container_key ) {
		if ( isset( $data[ $container_key ] ) && is_array( $data[ $container_key ] ) ) {
			return $data[ $container_key ];
		}
	}

	if ( isset( $data[0] ) && is_array( $data[0] ) ) {
		return $data;
	}

	return array();
};

$wp_dialyra_normalize_department = static function ( $department ) {
	$department = is_array( $department ) ? $department : array();
	$metadata   = isset( $department['metadata'] ) && is_array( $department['metadata'] ) ? $department['metadata'] : array();
	$availability = isset( $department['availability'] ) && is_array( $department['availability'] ) ? $department['availability'] : array();

	return array(
		'id'                  => isset( $department['id'] ) ? absint( $department['id'] ) : 0,
		'name'                => ! empty( $department['name'] ) ? sanitize_text_field( $department['name'] ) : __( 'Untitled department', 'wp-dialyra' ),
		'description'         => ! empty( $department['description'] ) ? sanitize_textarea_field( $department['description'] ) : '',
		'status'              => ! empty( $department['status'] ) ? sanitize_key( $department['status'] ) : 'active',
		'strategy'            => ! empty( $department['strategy'] ) ? sanitize_key( $department['strategy'] ) : 'least_busy',
		'default_language'    => ! empty( $metadata['default_language'] ) ? sanitize_text_field( $metadata['default_language'] ) : '',
		'availability_status' => ! empty( $department['availability_status'] ) ? sanitize_key( $department['availability_status'] ) : ( ! empty( $availability['availability_status'] ) ? sanitize_key( $availability['availability_status'] ) : 'open' ),
		'updated_at'          => ! empty( $department['updated_at'] ) ? sanitize_text_field( $department['updated_at'] ) : '',
	);
};

$wp_dialyra_normalize_agent = static function ( $agent ) {
	$agent = is_array( $agent ) ? $agent : array();

	return array(
		'id'    => isset( $agent['id'] ) ? absint( $agent['id'] ) : ( isset( $agent['agent_id'] ) ? absint( $agent['agent_id'] ) : 0 ),
		'name'  => ! empty( $agent['name'] ) ? sanitize_text_field( $agent['name'] ) : ( ! empty( $agent['full_name'] ) ? sanitize_text_field( $agent['full_name'] ) : __( 'Unnamed agent', 'wp-dialyra' ) ),
		'email' => ! empty( $agent['email'] ) ? sanitize_email( $agent['email'] ) : '',
	);
};

$wp_dialyra_normalize_mapping = static function ( $mapping ) {
	$mapping = is_array( $mapping ) ? $mapping : array();

	return array(
		'id'         => isset( $mapping['id'] ) ? absint( $mapping['id'] ) : 0,
		'agent_id'   => isset( $mapping['agent_id'] ) ? absint( $mapping['agent_id'] ) : 0,
		'priority'   => isset( $mapping['priority'] ) ? max( 1, absint( $mapping['priority'] ) ) : 1,
		'is_active'  => isset( $mapping['is_active'] ) ? (bool) $mapping['is_active'] : true,
		'updated_at' => ! empty( $mapping['updated_at'] ) ? sanitize_text_field( $mapping['updated_at'] ) : '',
	);
};

$wp_dialyra_normalize_schedule = static function ( $schedule ) use ( $wp_dialyra_normalize_timezone ) {
	$schedule = is_array( $schedule ) ? $schedule : array();

	return array(
		'id'                => isset( $schedule['id'] ) ? absint( $schedule['id'] ) : 0,
		'availability_mode' => ! empty( $schedule['availability_mode'] ) ? sanitize_key( $schedule['availability_mode'] ) : 'always_open',
		'availability_status' => ! empty( $schedule['availability_status'] ) ? sanitize_key( $schedule['availability_status'] ) : 'open',
		'timezone'          => ! empty( $schedule['timezone'] ) ? $wp_dialyra_normalize_timezone( $schedule['timezone'] ) : $wp_dialyra_normalize_timezone( '' ),
		'weekly_hours'      => isset( $schedule['weekly_hours'] ) && is_array( $schedule['weekly_hours'] ) ? $schedule['weekly_hours'] : array(),
		'holiday_overrides' => isset( $schedule['holiday_overrides'] ) && is_array( $schedule['holiday_overrides'] ) ? $schedule['holiday_overrides'] : array(),
		'is_active'         => isset( $schedule['is_active'] ) ? (bool) $schedule['is_active'] : true,
		'is_configured'     => ! empty( $schedule['is_configured'] ) || ! empty( $schedule['id'] ),
	);
};

$wp_dialyra_normalize_live = static function ( $live ) {
	$live = is_array( $live ) ? $live : array();

	return array(
		'routing_readiness'  => ! empty( $live['routing_readiness'] ) ? sanitize_key( $live['routing_readiness'] ) : '',
		'ready_for_transfer' => isset( $live['ready_for_transfer'] ) ? (bool) $live['ready_for_transfer'] : false,
		'total_agents'       => isset( $live['total_agents'] ) ? absint( $live['total_agents'] ) : 0,
		'available_agents'   => isset( $live['available_agents'] ) ? absint( $live['available_agents'] ) : 0,
		'busy_agents'        => isset( $live['busy_agents'] ) ? absint( $live['busy_agents'] ) : 0,
		'offline_agents'     => isset( $live['offline_agents'] ) ? absint( $live['offline_agents'] ) : 0,
		'reason_code'        => ! empty( $live['reason_code'] ) ? sanitize_key( $live['reason_code'] ) : '',
	);
};

$wp_dialyra_get_agent_name = static function ( $agent_id, $agents ) {
	foreach ( $agents as $agent ) {
		if ( absint( $agent['id'] ) === absint( $agent_id ) ) {
			return $agent['email'] ? sprintf( '%1$s (%2$s)', $agent['name'], $agent['email'] ) : $agent['name'];
		}
	}

	return sprintf( /* translators: %d: agent ID. */ __( 'Agent #%d', 'wp-dialyra' ), absint( $agent_id ) );
};

$wp_dialyra_build_department_payload = static function ( $business_id ) {
	$default_language = isset( $_POST['default_language'] ) ? sanitize_text_field( wp_unslash( $_POST['default_language'] ) ) : '';

	return array(
		'business_id'  => absint( $business_id ),
		'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
		'description'  => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
		'status'       => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
		'strategy'     => isset( $_POST['strategy'] ) ? sanitize_key( wp_unslash( $_POST['strategy'] ) ) : 'least_busy',
		'metadata'     => array(
			'default_language' => $default_language,
		),
	);
};

$wp_dialyra_build_schedule_payload = static function () use ( $wp_dialyra_days, $wp_dialyra_normalize_timezone ) {
	$availability_mode  = isset( $_POST['availability_mode'] ) ? sanitize_key( wp_unslash( $_POST['availability_mode'] ) ) : 'always_open';
	$weekly_hours_post = isset( $_POST['weekly_hours'] ) && is_array( $_POST['weekly_hours'] ) ? wp_unslash( $_POST['weekly_hours'] ) : array();
	$weekly_days_post  = isset( $_POST['weekly_days'] ) && is_array( $_POST['weekly_days'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['weekly_days'] ) ) : array();
	$holiday_post      = isset( $_POST['holiday_override'] ) && is_array( $_POST['holiday_override'] ) ? wp_unslash( $_POST['holiday_override'] ) : array();
	$weekly_hours      = array();
	$holiday_overrides = array();

	foreach ( $wp_dialyra_days as $day_key => $day_label ) {
		$day_window = isset( $weekly_hours_post[ $day_key ] ) && is_array( $weekly_hours_post[ $day_key ] ) ? $weekly_hours_post[ $day_key ] : array();
		$open_time  = ! empty( $day_window['open'] ) ? sanitize_text_field( $day_window['open'] ) : '';
		$close_time = ! empty( $day_window['close'] ) ? sanitize_text_field( $day_window['close'] ) : '';

		$weekly_hours[ $day_key ] = array();

		if ( in_array( $day_key, $weekly_days_post, true ) && $open_time && $close_time ) {
			$weekly_hours[ $day_key ][] = array(
				'open'  => $open_time,
				'close' => $close_time,
			);
		}
	}

	$holiday_date  = ! empty( $holiday_post['date'] ) ? sanitize_text_field( $holiday_post['date'] ) : '';
	$holiday_mode  = ! empty( $holiday_post['mode'] ) ? sanitize_key( $holiday_post['mode'] ) : 'closed';
	$holiday_open  = ! empty( $holiday_post['open'] ) ? sanitize_text_field( $holiday_post['open'] ) : '';
	$holiday_close = ! empty( $holiday_post['close'] ) ? sanitize_text_field( $holiday_post['close'] ) : '';

	if ( 'scheduled' === $availability_mode && $holiday_date && in_array( $holiday_mode, array( 'closed', 'custom' ), true ) ) {
		$holiday_override = array(
			'date' => $holiday_date,
			'mode' => $holiday_mode,
		);

		if ( 'custom' === $holiday_mode && $holiday_open && $holiday_close ) {
			$holiday_override['windows'] = array(
				array(
					'open'  => $holiday_open,
					'close' => $holiday_close,
				),
			);
		}

		$holiday_overrides[] = $holiday_override;
	}

	if ( 'scheduled' !== $availability_mode ) {
		$weekly_hours = array_fill_keys( array_keys( $wp_dialyra_days ), array() );
		$holiday_overrides = array();
	}

	return array(
		'availability_mode' => $availability_mode,
		'timezone'          => isset( $_POST['timezone'] ) ? $wp_dialyra_normalize_timezone( wp_unslash( $_POST['timezone'] ) ) : $wp_dialyra_normalize_timezone( '' ),
		'weekly_hours'      => $weekly_hours,
		'holiday_overrides' => $holiday_overrides,
		'is_active'         => ! empty( $_POST['is_active'] ),
		'metadata'          => array(
			'note' => __( 'Configured from WP Dialyra.', 'wp-dialyra' ),
		),
	);
};

if ( 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) && isset( $_POST['wp_dialyra_department_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_dialyra_department_nonce'] ) ), 'wp-dialyra-departments' ) ) {
	$wp_dialyra_action = isset( $_POST['wp_dialyra_department_action'] ) ? sanitize_key( wp_unslash( $_POST['wp_dialyra_department_action'] ) ) : '';

	if ( ! $wp_dialyra_business_id ) {
		$wp_dialyra_error = esc_html__( 'Connect a business before managing departments.', 'wp-dialyra' );
	} elseif ( ! $wp_dialyra_api_endpoints ) {
		$wp_dialyra_error = esc_html__( 'Department service is not available.', 'wp-dialyra' );
	} elseif ( 'create_department' === $wp_dialyra_action ) {
		$payload = $wp_dialyra_build_department_payload( $wp_dialyra_business_id );

		if ( empty( $payload['name'] ) ) {
			$wp_dialyra_error = esc_html__( 'Department name is required.', 'wp-dialyra' );
		} else {
			$response = $wp_dialyra_api_endpoints->create_department( $payload );
			$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Department created successfully.', 'wp-dialyra' ) : '';
			$wp_dialyra_error   = $response && ! $response->is_successful() ? $response->get_message() : $wp_dialyra_error;
		}
	} elseif ( 'update_department' === $wp_dialyra_action ) {
		$department_id = isset( $_POST['department_id'] ) ? absint( wp_unslash( $_POST['department_id'] ) ) : 0;
		$payload       = $wp_dialyra_build_department_payload( $wp_dialyra_business_id );
		unset( $payload['business_id'] );

		if ( ! $department_id || empty( $payload['name'] ) ) {
			$wp_dialyra_error = esc_html__( 'Department and name are required for update.', 'wp-dialyra' );
		} else {
			$response = $wp_dialyra_api_endpoints->update_department( $department_id, $payload );
			$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Department updated successfully.', 'wp-dialyra' ) : '';
			$wp_dialyra_error   = $response && ! $response->is_successful() ? $response->get_message() : $wp_dialyra_error;
		}
	} elseif ( 'delete_department' === $wp_dialyra_action ) {
		$department_id = isset( $_POST['department_id'] ) ? absint( wp_unslash( $_POST['department_id'] ) ) : 0;

		if ( ! $department_id ) {
			$wp_dialyra_error = esc_html__( 'Choose a department to delete.', 'wp-dialyra' );
		} else {
			$response = $wp_dialyra_api_endpoints->delete_department( $department_id );
			$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Department deleted successfully.', 'wp-dialyra' ) : '';
			$wp_dialyra_error   = $response && ! $response->is_successful() ? $response->get_message() : $wp_dialyra_error;
		}
	} elseif ( 'bind_agent' === $wp_dialyra_action ) {
		$department_id = isset( $_POST['department_id'] ) ? absint( wp_unslash( $_POST['department_id'] ) ) : 0;
		$agent_id      = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;

		if ( ! $department_id || ! $agent_id ) {
			$wp_dialyra_error = esc_html__( 'Choose a department and agent to bind.', 'wp-dialyra' );
		} else {
			$response = $wp_dialyra_api_endpoints->add_department_agent(
				$department_id,
				array(
					'agent_id'   => $agent_id,
					'priority'   => isset( $_POST['priority'] ) ? absint( wp_unslash( $_POST['priority'] ) ) : 1,
					'is_active'  => ! empty( $_POST['is_active'] ),
				)
			);
			$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Agent mapping saved successfully.', 'wp-dialyra' ) : '';
			$wp_dialyra_error   = $response && ! $response->is_successful() ? $response->get_message() : $wp_dialyra_error;
		}
	} elseif ( 'remove_agent' === $wp_dialyra_action ) {
		$department_id = isset( $_POST['department_id'] ) ? absint( wp_unslash( $_POST['department_id'] ) ) : 0;
		$agent_id      = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;

		if ( ! $department_id || ! $agent_id ) {
			$wp_dialyra_error = esc_html__( 'Choose an agent mapping to remove.', 'wp-dialyra' );
		} else {
			$response = $wp_dialyra_api_endpoints->delete_department_agent( $department_id, $agent_id );
			$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Agent removed from department.', 'wp-dialyra' ) : '';
			$wp_dialyra_error   = $response && ! $response->is_successful() ? $response->get_message() : $wp_dialyra_error;
		}
	} elseif ( 'save_schedule' === $wp_dialyra_action ) {
		$department_id = isset( $_POST['department_id'] ) ? absint( wp_unslash( $_POST['department_id'] ) ) : 0;

		if ( ! $department_id ) {
			$wp_dialyra_error = esc_html__( 'Choose a department before saving schedule.', 'wp-dialyra' );
		} else {
			$payload           = $wp_dialyra_build_schedule_payload();
			$schedule_response = $wp_dialyra_api_endpoints->get_department_schedule( $department_id );
			$schedule_data     = $wp_dialyra_normalize_schedule( $wp_dialyra_extract_data( $schedule_response ) );
			$response          = ! empty( $schedule_data['is_configured'] ) ? $wp_dialyra_api_endpoints->update_department_schedule( $department_id, $payload ) : $wp_dialyra_api_endpoints->create_department_schedule( $department_id, $payload );

			if ( $response && ! $response->is_successful() && 409 === $response->get_status_code() ) {
				$response = $wp_dialyra_api_endpoints->update_department_schedule( $department_id, $payload );
			}

			$wp_dialyra_success = $response && $response->is_successful() ? esc_html__( 'Department schedule saved successfully.', 'wp-dialyra' ) : '';
			$wp_dialyra_error   = $response && ! $response->is_successful() ? $response->get_message() : $wp_dialyra_error;
		}
	}
}

if ( $wp_dialyra_api_endpoints && $wp_dialyra_business_id ) {
	$wp_dialyra_department_response = $wp_dialyra_api_endpoints->get_departments( array( 'business_id' => $wp_dialyra_business_id ) );

	if ( $wp_dialyra_department_response && $wp_dialyra_department_response->is_successful() ) {
		$wp_dialyra_departments = array_filter(
			array_map( $wp_dialyra_normalize_department, $wp_dialyra_extract_items( $wp_dialyra_department_response ) ),
			static function ( $department ) {
				return ! empty( $department['id'] );
			}
		);
	} elseif ( empty( $wp_dialyra_error ) && $wp_dialyra_department_response ) {
		$wp_dialyra_error = $wp_dialyra_department_response->get_message();
	}

	$wp_dialyra_agents_response = $wp_dialyra_api_endpoints->get_agents( array( 'business_id' => $wp_dialyra_business_id ) );

	if ( $wp_dialyra_agents_response && $wp_dialyra_agents_response->is_successful() ) {
		$wp_dialyra_agents = array_filter(
			array_map( $wp_dialyra_normalize_agent, $wp_dialyra_extract_items( $wp_dialyra_agents_response ) ),
			static function ( $agent ) {
				return ! empty( $agent['id'] );
			}
		);
	}

	foreach ( $wp_dialyra_departments as $department ) {
		$department_id = absint( $department['id'] );

		$mappings_response = $wp_dialyra_api_endpoints->get_department_agents( $department_id );
		if ( $mappings_response && $mappings_response->is_successful() ) {
			$wp_dialyra_department_mappings[ $department_id ] = array_filter(
				array_map( $wp_dialyra_normalize_mapping, $wp_dialyra_extract_items( $mappings_response ) ),
				static function ( $mapping ) {
					return ! empty( $mapping['agent_id'] );
				}
			);
		} else {
			$wp_dialyra_department_mappings[ $department_id ] = array();
		}

		$schedule_response = $wp_dialyra_api_endpoints->get_department_schedule( $department_id );
		$wp_dialyra_department_schedules[ $department_id ] = $wp_dialyra_normalize_schedule( $wp_dialyra_extract_data( $schedule_response ) );

		$live_response = $wp_dialyra_api_endpoints->get_department_live( $department_id );
		$wp_dialyra_department_live[ $department_id ] = $wp_dialyra_normalize_live( $wp_dialyra_extract_data( $live_response ) );
	}
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
			<button class="wp-dialyra-button wp-dialyra-button--primary" type="button" data-dialyra-dialog-open="wp-dialyra-create-department"><?php esc_html_e( 'Create Department', 'wp-dialyra' ); ?></button>
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

	<div class="wp-dialyra-departments__grid">
		<section class="wp-dialyra-department-panel wp-dialyra-department-panel--wide">
			<div class="wp-dialyra-department-panel__head">
				<span aria-hidden="true">01</span>
				<div>
					<h3><?php esc_html_e( 'Department directory', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'View routing groups row-by-row. Create, edit, delete, assign agents, and schedule departments from centered dialogs.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<?php if ( empty( $wp_dialyra_departments ) ) : ?>
				<div class="wp-dialyra-empty-card">
					<span class="dashicons dashicons-groups" aria-hidden="true"></span>
					<strong><?php esc_html_e( 'No departments yet', 'wp-dialyra' ); ?></strong>
					<p><?php esc_html_e( 'Create your first routing group, then bind agents and configure schedule windows.', 'wp-dialyra' ); ?></p>
				</div>
			<?php else : ?>
				<div class="wp-dialyra-department-table-wrap">
					<table class="wp-dialyra-department-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Department', 'wp-dialyra' ); ?></th>
								<th><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></th>
								<th><?php esc_html_e( 'Strategy', 'wp-dialyra' ); ?></th>
								<th><?php esc_html_e( 'Availability', 'wp-dialyra' ); ?></th>
								<th><?php esc_html_e( 'Readiness', 'wp-dialyra' ); ?></th>
								<th><?php esc_html_e( 'Agents', 'wp-dialyra' ); ?></th>
								<th><?php esc_html_e( 'Updated', 'wp-dialyra' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'wp-dialyra' ); ?></th>
							</tr>
						</thead>
						<tbody>
					<?php foreach ( $wp_dialyra_departments as $department ) : ?>
						<?php
						$department_id = absint( $department['id'] );
						$schedule      = isset( $wp_dialyra_department_schedules[ $department_id ] ) ? $wp_dialyra_department_schedules[ $department_id ] : $wp_dialyra_normalize_schedule( array() );
						$mappings      = isset( $wp_dialyra_department_mappings[ $department_id ] ) ? $wp_dialyra_department_mappings[ $department_id ] : array();
						$live          = isset( $wp_dialyra_department_live[ $department_id ] ) ? $wp_dialyra_department_live[ $department_id ] : $wp_dialyra_normalize_live( array() );
						$status_class  = 'active' === $department['status'] ? 'wp-dialyra-result--success' : ( 'inactive' === $department['status'] ? 'wp-dialyra-result--warning' : 'wp-dialyra-result--muted' );
						$readiness     = $live['routing_readiness'] ? $live['routing_readiness'] : 'not_checked';
						$dialog_prefix = 'wp-dialyra-department-' . $department_id;
						?>
						<tr>
							<td data-label="<?php esc_attr_e( 'Department', 'wp-dialyra' ); ?>">
								<strong><?php echo esc_html( $department['name'] ); ?></strong>
								<small><?php echo esc_html( $department['description'] ? $department['description'] : __( 'No description added yet.', 'wp-dialyra' ) ); ?></small>
							</td>
							<td data-label="<?php esc_attr_e( 'Status', 'wp-dialyra' ); ?>"><span class="wp-dialyra-result <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $department['status'] ); ?></span></td>
							<td data-label="<?php esc_attr_e( 'Strategy', 'wp-dialyra' ); ?>"><code><?php echo esc_html( $department['strategy'] ); ?></code></td>
							<td data-label="<?php esc_attr_e( 'Availability', 'wp-dialyra' ); ?>"><code><?php echo esc_html( $schedule['availability_status'] ? $schedule['availability_status'] : $department['availability_status'] ); ?></code></td>
							<td data-label="<?php esc_attr_e( 'Readiness', 'wp-dialyra' ); ?>"><code><?php echo esc_html( $readiness ); ?></code></td>
							<td data-label="<?php esc_attr_e( 'Agents', 'wp-dialyra' ); ?>"><?php echo esc_html( sprintf( '%1$d / %2$d', $live['available_agents'], $live['total_agents'] ) ); ?></td>
							<td data-label="<?php esc_attr_e( 'Updated', 'wp-dialyra' ); ?>"><?php echo esc_html( $department['updated_at'] ? $department['updated_at'] : '—' ); ?></td>
							<td data-label="<?php esc_attr_e( 'Actions', 'wp-dialyra' ); ?>">
								<div class="wp-dialyra-department-actions">
									<button class="wp-dialyra-icon-button" type="button" data-dialyra-dialog-open="<?php echo esc_attr( $dialog_prefix . '-edit' ); ?>" title="<?php esc_attr_e( 'Edit department', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Edit department', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>
									<button class="wp-dialyra-icon-button" type="button" data-dialyra-dialog-open="<?php echo esc_attr( $dialog_prefix . '-agents' ); ?>" title="<?php esc_attr_e( 'Bind agents', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Bind agents', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-groups" aria-hidden="true"></span></button>
									<button class="wp-dialyra-icon-button" type="button" data-dialyra-dialog-open="<?php echo esc_attr( $dialog_prefix . '-schedule' ); ?>" title="<?php esc_attr_e( 'Schedule', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Schedule', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span></button>
									<button class="wp-dialyra-icon-button wp-dialyra-icon-button--danger" type="button" data-dialyra-dialog-open="<?php echo esc_attr( $dialog_prefix . '-delete' ); ?>" title="<?php esc_attr_e( 'Delete department', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Delete department', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
	</div>

	<div id="wp-dialyra-create-department" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="wp-dialyra-create-department-title" hidden data-dialyra-dialog>
		<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
		<div class="wp-dialyra-dialog__panel">
			<div class="wp-dialyra-dialog__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Create', 'wp-dialyra' ); ?></p>
					<h3 id="wp-dialyra-create-department-title"><?php esc_html_e( 'Create department', 'wp-dialyra' ); ?></h3>
				</div>
				<button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>

			<form class="wp-dialyra-department-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=departments' ) ); ?>">
				<?php wp_nonce_field( 'wp-dialyra-departments', 'wp_dialyra_department_nonce' ); ?>
				<input type="hidden" name="wp_dialyra_department_action" value="create_department">

				<div class="wp-dialyra-department-form__grid">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-department-name"><?php esc_html_e( 'Department name', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-department-name" name="name" type="text" maxlength="128" placeholder="<?php esc_attr_e( 'Billing Department', 'wp-dialyra' ); ?>">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-department-language"><?php esc_html_e( 'Default language', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-department-language" name="default_language" type="text" value="bn">
					</div>
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-department-description"><?php esc_html_e( 'Description', 'wp-dialyra' ); ?></label>
					<textarea id="wp-dialyra-department-description" name="description" rows="4" placeholder="<?php esc_attr_e( 'Billing and payment support', 'wp-dialyra' ); ?>"></textarea>
				</div>

				<div class="wp-dialyra-department-form__grid">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-department-status"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-department-status" name="status">
							<?php foreach ( $wp_dialyra_statuses as $status_key => $status_label ) : ?>
								<option value="<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $status_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-department-strategy"><?php esc_html_e( 'Strategy', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-department-strategy" name="strategy">
							<?php foreach ( $wp_dialyra_strategies as $strategy_key => $strategy_label ) : ?>
								<option value="<?php echo esc_attr( $strategy_key ); ?>"><?php echo esc_html( $strategy_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="wp-dialyra-department-panel__footer">
					<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-dialog-close><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></button>
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Create department', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</div>
	</div>

	<?php foreach ( $wp_dialyra_departments as $department ) : ?>
		<?php
		$department_id = absint( $department['id'] );
		$schedule      = isset( $wp_dialyra_department_schedules[ $department_id ] ) ? $wp_dialyra_department_schedules[ $department_id ] : $wp_dialyra_normalize_schedule( array() );
		$mappings      = isset( $wp_dialyra_department_mappings[ $department_id ] ) ? $wp_dialyra_department_mappings[ $department_id ] : array();
		$dialog_prefix = 'wp-dialyra-department-' . $department_id;
		?>

		<div id="<?php echo esc_attr( $dialog_prefix . '-edit' ); ?>" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $dialog_prefix . '-edit-title' ); ?>" hidden data-dialyra-dialog>
			<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
			<div class="wp-dialyra-dialog__panel">
				<div class="wp-dialyra-dialog__head">
					<div>
						<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Edit', 'wp-dialyra' ); ?></p>
						<h3 id="<?php echo esc_attr( $dialog_prefix . '-edit-title' ); ?>"><?php echo esc_html( $department['name'] ); ?></h3>
					</div>
					<button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
				</div>

							<form class="wp-dialyra-department-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=departments' ) ); ?>">
								<?php wp_nonce_field( 'wp-dialyra-departments', 'wp_dialyra_department_nonce' ); ?>
								<input type="hidden" name="wp_dialyra_department_action" value="update_department">
								<input type="hidden" name="department_id" value="<?php echo esc_attr( $department_id ); ?>">

								<div class="wp-dialyra-department-form__grid">
									<div class="wp-dialyra-settings-row">
										<label for="wp-dialyra-edit-name-<?php echo esc_attr( $department_id ); ?>"><?php esc_html_e( 'Department name', 'wp-dialyra' ); ?></label>
										<input id="wp-dialyra-edit-name-<?php echo esc_attr( $department_id ); ?>" name="name" type="text" maxlength="128" value="<?php echo esc_attr( $department['name'] ); ?>">
									</div>

									<div class="wp-dialyra-settings-row">
										<label for="wp-dialyra-edit-language-<?php echo esc_attr( $department_id ); ?>"><?php esc_html_e( 'Default language', 'wp-dialyra' ); ?></label>
										<input id="wp-dialyra-edit-language-<?php echo esc_attr( $department_id ); ?>" name="default_language" type="text" value="<?php echo esc_attr( $department['default_language'] ); ?>">
									</div>
								</div>

								<div class="wp-dialyra-settings-row">
									<label for="wp-dialyra-edit-description-<?php echo esc_attr( $department_id ); ?>"><?php esc_html_e( 'Description', 'wp-dialyra' ); ?></label>
									<textarea id="wp-dialyra-edit-description-<?php echo esc_attr( $department_id ); ?>" name="description" rows="3"><?php echo esc_textarea( $department['description'] ); ?></textarea>
								</div>

								<div class="wp-dialyra-department-form__grid">
									<div class="wp-dialyra-settings-row">
										<label for="wp-dialyra-edit-status-<?php echo esc_attr( $department_id ); ?>"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></label>
										<select id="wp-dialyra-edit-status-<?php echo esc_attr( $department_id ); ?>" name="status">
											<?php foreach ( $wp_dialyra_statuses as $status_key => $status_label ) : ?>
												<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status_key, $department['status'] ); ?>><?php echo esc_html( $status_label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>

									<div class="wp-dialyra-settings-row">
										<label for="wp-dialyra-edit-strategy-<?php echo esc_attr( $department_id ); ?>"><?php esc_html_e( 'Strategy', 'wp-dialyra' ); ?></label>
										<select id="wp-dialyra-edit-strategy-<?php echo esc_attr( $department_id ); ?>" name="strategy">
											<?php foreach ( $wp_dialyra_strategies as $strategy_key => $strategy_label ) : ?>
												<option value="<?php echo esc_attr( $strategy_key ); ?>" <?php selected( $strategy_key, $department['strategy'] ); ?>><?php echo esc_html( $strategy_label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>

								<div class="wp-dialyra-department-panel__footer wp-dialyra-department-panel__footer--split">
									<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-dialog-close><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></button>
									<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Update department', 'wp-dialyra' ); ?></button>
								</div>
							</form>
			</div>
		</div>

		<div id="<?php echo esc_attr( $dialog_prefix . '-agents' ); ?>" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $dialog_prefix . '-agents-title' ); ?>" hidden data-dialyra-dialog>
			<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
			<div class="wp-dialyra-dialog__panel">
				<div class="wp-dialyra-dialog__head">
					<div>
						<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Agents', 'wp-dialyra' ); ?></p>
						<h3 id="<?php echo esc_attr( $dialog_prefix . '-agents-title' ); ?>"><?php esc_html_e( 'Bind agents', 'wp-dialyra' ); ?></h3>
					</div>
					<button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
				</div>

							<form class="wp-dialyra-department-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=departments' ) ); ?>">
								<?php wp_nonce_field( 'wp-dialyra-departments', 'wp_dialyra_department_nonce' ); ?>
								<input type="hidden" name="wp_dialyra_department_action" value="bind_agent">
								<input type="hidden" name="department_id" value="<?php echo esc_attr( $department_id ); ?>">

								<div class="wp-dialyra-department-form__grid">
									<div class="wp-dialyra-settings-row">
										<label for="wp-dialyra-agent-select-<?php echo esc_attr( $department_id ); ?>"><?php esc_html_e( 'Bind agent', 'wp-dialyra' ); ?></label>
										<select id="wp-dialyra-agent-select-<?php echo esc_attr( $department_id ); ?>" name="agent_id">
											<?php if ( empty( $wp_dialyra_agents ) ) : ?>
												<option value=""><?php esc_html_e( 'No agents available', 'wp-dialyra' ); ?></option>
											<?php else : ?>
												<?php foreach ( $wp_dialyra_agents as $agent ) : ?>
													<option value="<?php echo esc_attr( $agent['id'] ); ?>"><?php echo esc_html( $agent['email'] ? sprintf( '%1$s — %2$s', $agent['name'], $agent['email'] ) : $agent['name'] ); ?></option>
												<?php endforeach; ?>
											<?php endif; ?>
										</select>
									</div>

									<div class="wp-dialyra-settings-row">
										<label for="wp-dialyra-agent-priority-<?php echo esc_attr( $department_id ); ?>"><?php esc_html_e( 'Priority', 'wp-dialyra' ); ?></label>
										<input id="wp-dialyra-agent-priority-<?php echo esc_attr( $department_id ); ?>" name="priority" type="number" min="1" value="1">
									</div>
								</div>

								<div class="wp-dialyra-toggle-row">
									<span><?php esc_html_e( 'Mapping active', 'wp-dialyra' ); ?></span>
									<label>
										<input type="checkbox" name="is_active" checked>
										<i></i>
									</label>
								</div>

								<div class="wp-dialyra-department-panel__footer">
									<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" <?php disabled( empty( $wp_dialyra_agents ) ); ?>><?php esc_html_e( 'Bind agent', 'wp-dialyra' ); ?></button>
								</div>
							</form>

							<div class="wp-dialyra-agent-mapping">
								<span><?php esc_html_e( 'Current mappings', 'wp-dialyra' ); ?></span>
								<?php if ( empty( $mappings ) ) : ?>
									<em><?php esc_html_e( 'No agents are mapped to this department yet.', 'wp-dialyra' ); ?></em>
								<?php else : ?>
									<?php foreach ( $mappings as $mapping ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=departments' ) ); ?>" class="wp-dialyra-department-inline-form">
											<?php wp_nonce_field( 'wp-dialyra-departments', 'wp_dialyra_department_nonce' ); ?>
											<input type="hidden" name="wp_dialyra_department_action" value="remove_agent">
											<input type="hidden" name="department_id" value="<?php echo esc_attr( $department_id ); ?>">
											<input type="hidden" name="agent_id" value="<?php echo esc_attr( $mapping['agent_id'] ); ?>">
											<strong><?php echo esc_html( $wp_dialyra_get_agent_name( $mapping['agent_id'], $wp_dialyra_agents ) ); ?></strong>
											<em><?php echo esc_html( sprintf( /* translators: 1: priority number, 2: mapping status. */ __( 'Priority %1$d · %2$s', 'wp-dialyra' ), $mapping['priority'], $mapping['is_active'] ? __( 'Active', 'wp-dialyra' ) : __( 'Inactive', 'wp-dialyra' ) ) ); ?></em>
											<button class="wp-dialyra-icon-button" type="submit" title="<?php esc_attr_e( 'Remove agent', 'wp-dialyra' ); ?>" aria-label="<?php esc_attr_e( 'Remove agent', 'wp-dialyra' ); ?>">
												<span class="dashicons dashicons-trash" aria-hidden="true"></span>
											</button>
										</form>
									<?php endforeach; ?>
									<?php endif; ?>
								</div>
			</div>
		</div>

		<div id="<?php echo esc_attr( $dialog_prefix . '-schedule' ); ?>" class="wp-dialyra-dialog wp-dialyra-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $dialog_prefix . '-schedule-title' ); ?>" hidden data-dialyra-dialog>
			<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
			<div class="wp-dialyra-dialog__panel">
				<div class="wp-dialyra-dialog__head">
					<div>
						<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Schedule', 'wp-dialyra' ); ?></p>
						<h3 id="<?php echo esc_attr( $dialog_prefix . '-schedule-title' ); ?>"><?php echo esc_html( $department['name'] ); ?></h3>
					</div>
					<button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
				</div>

							<form class="wp-dialyra-department-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=departments' ) ); ?>" data-dialyra-schedule-group>
								<?php wp_nonce_field( 'wp-dialyra-departments', 'wp_dialyra_department_nonce' ); ?>
								<input type="hidden" name="wp_dialyra_department_action" value="save_schedule">
								<input type="hidden" name="department_id" value="<?php echo esc_attr( $department_id ); ?>">

								<div class="wp-dialyra-department-form__grid wp-dialyra-department-form__grid--three">
									<div class="wp-dialyra-settings-row">
										<label for="wp-dialyra-availability-mode-<?php echo esc_attr( $department_id ); ?>"><?php esc_html_e( 'Availability mode', 'wp-dialyra' ); ?></label>
										<select id="wp-dialyra-availability-mode-<?php echo esc_attr( $department_id ); ?>" name="availability_mode" data-dialyra-schedule-mode>
											<?php foreach ( $wp_dialyra_availability_modes as $mode_key => $mode_label ) : ?>
												<option value="<?php echo esc_attr( $mode_key ); ?>" <?php selected( $mode_key, $schedule['availability_mode'] ); ?>><?php echo esc_html( $mode_label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-scheduled-fields>
										<label for="wp-dialyra-schedule-timezone-<?php echo esc_attr( $department_id ); ?>"><?php esc_html_e( 'Timezone', 'wp-dialyra' ); ?></label>
										<input id="wp-dialyra-schedule-timezone-<?php echo esc_attr( $department_id ); ?>" name="timezone" type="text" value="<?php echo esc_attr( $schedule['timezone'] ); ?>">
									</div>

									<div class="wp-dialyra-toggle-row">
										<span><?php esc_html_e( 'Schedule active', 'wp-dialyra' ); ?></span>
										<label>
											<input type="checkbox" name="is_active" <?php checked( $schedule['is_active'] ); ?>>
											<i></i>
										</label>
									</div>
								</div>

								<div class="wp-dialyra-weekly-hours" data-dialyra-scheduled-fields>
									<?php foreach ( $wp_dialyra_days as $day_key => $day_label ) : ?>
										<?php
										$day_windows = isset( $schedule['weekly_hours'][ $day_key ] ) && is_array( $schedule['weekly_hours'][ $day_key ] ) ? $schedule['weekly_hours'][ $day_key ] : array();
										$day_window  = ! empty( $day_windows[0] ) && is_array( $day_windows[0] ) ? $day_windows[0] : array();
										$open_time   = ! empty( $day_window['open'] ) ? sanitize_text_field( $day_window['open'] ) : '09:00';
										$close_time  = ! empty( $day_window['close'] ) ? sanitize_text_field( $day_window['close'] ) : ( 'fri' === $day_key ? '17:00' : '18:00' );
										$is_enabled  = ! empty( $day_windows );
										?>
										<div>
											<label>
												<input type="checkbox" name="weekly_days[]" value="<?php echo esc_attr( $day_key ); ?>" <?php checked( $is_enabled ); ?>>
												<strong><?php echo esc_html( $day_label ); ?></strong>
											</label>
											<input type="time" name="weekly_hours[<?php echo esc_attr( $day_key ); ?>][open]" value="<?php echo esc_attr( $open_time ); ?>">
											<input type="time" name="weekly_hours[<?php echo esc_attr( $day_key ); ?>][close]" value="<?php echo esc_attr( $close_time ); ?>">
										</div>
									<?php endforeach; ?>
								</div>

								<?php
								$holiday_override = ! empty( $schedule['holiday_overrides'][0] ) && is_array( $schedule['holiday_overrides'][0] ) ? $schedule['holiday_overrides'][0] : array();
								$holiday_windows  = ! empty( $holiday_override['windows'] ) && is_array( $holiday_override['windows'] ) ? $holiday_override['windows'] : array();
								$holiday_window   = ! empty( $holiday_windows[0] ) && is_array( $holiday_windows[0] ) ? $holiday_windows[0] : array();
								?>
								<div class="wp-dialyra-department-form__grid wp-dialyra-department-form__grid--three" data-dialyra-scheduled-fields>
									<div class="wp-dialyra-settings-row">
										<label for="wp-dialyra-holiday-date-<?php echo esc_attr( $department_id ); ?>"><?php esc_html_e( 'Holiday date', 'wp-dialyra' ); ?></label>
										<input id="wp-dialyra-holiday-date-<?php echo esc_attr( $department_id ); ?>" name="holiday_override[date]" type="date" value="<?php echo esc_attr( ! empty( $holiday_override['date'] ) ? sanitize_text_field( $holiday_override['date'] ) : '' ); ?>">
									</div>

									<div class="wp-dialyra-settings-row">
										<label for="wp-dialyra-holiday-mode-<?php echo esc_attr( $department_id ); ?>"><?php esc_html_e( 'Holiday mode', 'wp-dialyra' ); ?></label>
										<select id="wp-dialyra-holiday-mode-<?php echo esc_attr( $department_id ); ?>" name="holiday_override[mode]" data-dialyra-holiday-mode>
											<option value="closed" <?php selected( ! empty( $holiday_override['mode'] ) ? sanitize_key( $holiday_override['mode'] ) : 'closed', 'closed' ); ?>><?php esc_html_e( 'closed', 'wp-dialyra' ); ?></option>
											<option value="custom" <?php selected( ! empty( $holiday_override['mode'] ) ? sanitize_key( $holiday_override['mode'] ) : 'closed', 'custom' ); ?>><?php esc_html_e( 'custom', 'wp-dialyra' ); ?></option>
										</select>
									</div>

									<div class="wp-dialyra-settings-row" data-dialyra-holiday-custom-fields>
										<label><?php esc_html_e( 'Custom window', 'wp-dialyra' ); ?></label>
										<div class="wp-dialyra-department-time-pair">
											<input name="holiday_override[open]" type="time" value="<?php echo esc_attr( ! empty( $holiday_window['open'] ) ? sanitize_text_field( $holiday_window['open'] ) : '10:00' ); ?>">
											<input name="holiday_override[close]" type="time" value="<?php echo esc_attr( ! empty( $holiday_window['close'] ) ? sanitize_text_field( $holiday_window['close'] ) : '14:00' ); ?>">
										</div>
									</div>
								</div>

								<div class="wp-dialyra-schedule-note" data-dialyra-scheduled-fields>
									<span><?php esc_html_e( 'Schedule policy', 'wp-dialyra' ); ?></span>
									<code><?php esc_html_e( 'Always open ignores weekly windows. Scheduled uses selected days, times, and optional holiday override. Closed keeps the department unavailable.', 'wp-dialyra' ); ?></code>
								</div>

								<div class="wp-dialyra-department-panel__footer">
									<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-dialog-close><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></button>
									<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Save schedule', 'wp-dialyra' ); ?></button>
								</div>
							</form>
			</div>
		</div>

		<div id="<?php echo esc_attr( $dialog_prefix . '-delete' ); ?>" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $dialog_prefix . '-delete-title' ); ?>" hidden data-dialyra-dialog>
			<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
			<div class="wp-dialyra-dialog__panel wp-dialyra-dialog__panel--danger">
				<div class="wp-dialyra-dialog__head">
					<div>
						<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Delete', 'wp-dialyra' ); ?></p>
						<h3 id="<?php echo esc_attr( $dialog_prefix . '-delete-title' ); ?>"><?php echo esc_html( $department['name'] ); ?></h3>
					</div>
					<button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
				</div>

				<p class="wp-dialyra-dialog__warning"><?php esc_html_e( 'This removes the department and its schedule. Assigned agents must be removed before the API allows deletion.', 'wp-dialyra' ); ?></p>
				<form class="wp-dialyra-department-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=departments' ) ); ?>">
					<?php wp_nonce_field( 'wp-dialyra-departments', 'wp_dialyra_department_nonce' ); ?>
					<input type="hidden" name="wp_dialyra_department_action" value="delete_department">
					<input type="hidden" name="department_id" value="<?php echo esc_attr( $department_id ); ?>">
					<div class="wp-dialyra-department-panel__footer wp-dialyra-department-panel__footer--split">
						<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-dialog-close><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></button>
						<button class="wp-dialyra-button wp-dialyra-button--danger" type="submit"><?php esc_html_e( 'Delete department', 'wp-dialyra' ); ?></button>
					</div>
				</form>
			</div>
		</div>
	<?php endforeach; ?>
</section>
