<?php

/**
 * Settings page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$wp_dialyra_plugin           = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
$wp_dialyra_business_manager = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_business_manager' ) ? $wp_dialyra_plugin->get_business_manager() : null;
$wp_dialyra_flow_manager     = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_flow_manager' ) ? $wp_dialyra_plugin->get_flow_manager() : null;
$wp_dialyra_business_id      = class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
$wp_dialyra_error            = '';
$wp_dialyra_success          = '';
$wp_dialyra_businesses       = array();
$wp_dialyra_flows            = array();
$wp_dialyra_flow_fetch_error = '';
$wp_dialyra_default_country  = '';
$wp_dialyra_wc               = function_exists( 'WC' ) ? WC() : null;
$wp_dialyra_default_timezone = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_default_timezone() : 'UTC';
$wp_dialyra_setup_settings   = $wp_dialyra_business_manager && method_exists( $wp_dialyra_business_manager, 'get_setup_settings' ) ? $wp_dialyra_business_manager->get_setup_settings() : ( class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_setup_defaults() : array() );
$wp_dialyra_order_statuses   = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_default_order_statuses() : array(
	'processing' => __( 'Processing', 'wp-dialyra' ),
	'pending'    => __( 'Pending payment', 'wp-dialyra' ),
	'on-hold'    => __( 'On hold', 'wp-dialyra' ),
	'completed'  => __( 'Completed', 'wp-dialyra' ),
	'cancelled'  => __( 'Cancelled', 'wp-dialyra' ),
);

if ( function_exists( 'wc_get_order_statuses' ) ) {
	$wp_dialyra_order_statuses = array();

	foreach ( wc_get_order_statuses() as $wp_dialyra_wc_status_key => $wp_dialyra_wc_status_label ) {
		$wp_dialyra_order_statuses[ preg_replace( '/^wc-/', '', $wp_dialyra_wc_status_key ) ] = $wp_dialyra_wc_status_label;
	}
}

$wp_dialyra_order_statuses['draft']     = isset( $wp_dialyra_order_statuses['draft'] ) ? $wp_dialyra_order_statuses['draft'] : __( 'Draft', 'wp-dialyra' );
$wp_dialyra_order_statuses['refunded']  = isset( $wp_dialyra_order_statuses['refunded'] ) ? $wp_dialyra_order_statuses['refunded'] : __( 'Refunded', 'wp-dialyra' );
$wp_dialyra_order_statuses['no_change'] = __( 'Keep current status', 'wp-dialyra' );

$wp_dialyra_sanitize_order_status = static function ( $status, $fallback = 'no_change' ) use ( $wp_dialyra_order_statuses ) {
	$status = sanitize_key( $status );

	return array_key_exists( $status, $wp_dialyra_order_statuses ) ? $status : $fallback;
};

$wp_dialyra_business_days    = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_business_hour_days() : array(
	'all' => __( 'All', 'wp-dialyra' ),
	'mon' => __( 'Mon', 'wp-dialyra' ),
	'tue' => __( 'Tue', 'wp-dialyra' ),
	'wed' => __( 'Wed', 'wp-dialyra' ),
	'thu' => __( 'Thu', 'wp-dialyra' ),
	'fri' => __( 'Fri', 'wp-dialyra' ),
	'sat' => __( 'Sat', 'wp-dialyra' ),
	'sun' => __( 'Sun', 'wp-dialyra' ),
);

if ( $wp_dialyra_wc && isset( $wp_dialyra_wc->countries ) ) {
	$wp_dialyra_base_country = $wp_dialyra_wc->countries->get_base_country();
	$wp_dialyra_countries    = $wp_dialyra_wc->countries->get_countries();

	if ( $wp_dialyra_base_country ) {
		$wp_dialyra_default_country = isset( $wp_dialyra_countries[ $wp_dialyra_base_country ] ) ? $wp_dialyra_countries[ $wp_dialyra_base_country ] : $wp_dialyra_base_country;
	}
}

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

$wp_dialyra_extract_response_data = static function ( $response ) {
	if ( ! $response || ! is_object( $response ) || ! method_exists( $response, 'is_successful' ) || ! $response->is_successful() || ! method_exists( $response, 'get_data' ) ) {
		return array();
	}

	$data = $response->get_data();
	$data = is_array( $data ) ? $data : array();

	if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
		$data = $data['data'];
	}

	if ( isset( $data['business'] ) && is_array( $data['business'] ) ) {
		$data = $data['business'];
	}

	return $data;
};

$wp_dialyra_extract_response_items = static function ( $response ) use ( $wp_dialyra_extract_response_data ) {
	$data = $wp_dialyra_extract_response_data( $response );

	foreach ( array( 'items', 'businesses', 'flows', 'data' ) as $container_key ) {
		if ( isset( $data[ $container_key ] ) && is_array( $data[ $container_key ] ) ) {
			return $data[ $container_key ];
		}
	}

	return isset( $data[0] ) && is_array( $data[0] ) ? $data : array();
};

$wp_dialyra_normalize_flow = static function ( $flow ) {
	$flow = is_array( $flow ) ? $flow : array();
	$summary = '';

	if ( ! empty( $flow['description'] ) ) {
		$summary = sanitize_text_field( $flow['description'] );
	} elseif ( ! empty( $flow['summary'] ) ) {
		$summary = sanitize_text_field( $flow['summary'] );
	} else {
		$summary = __( 'Ready to use as a default order call flow.', 'wp-dialyra' );
	}

	return array(
		'id'         => isset( $flow['id'] ) ? absint( $flow['id'] ) : 0,
		'name'       => ! empty( $flow['name'] ) ? sanitize_text_field( $flow['name'] ) : __( 'Untitled flow', 'wp-dialyra' ),
		'summary'    => $summary,
		'status'     => ! empty( $flow['status'] ) ? sanitize_key( $flow['status'] ) : 'draft',
		'created_at' => ! empty( $flow['created_at'] ) ? sanitize_text_field( $flow['created_at'] ) : '',
	);
};

$wp_dialyra_normalize_business = static function ( $business ) use ( $wp_dialyra_normalize_timezone ) {
	$business = is_array( $business ) ? $business : array();

	return array(
		'id'        => isset( $business['id'] ) ? absint( $business['id'] ) : 0,
		'name'      => ! empty( $business['name'] ) ? sanitize_text_field( $business['name'] ) : __( 'Untitled business', 'wp-dialyra' ),
		'email'     => ! empty( $business['email'] ) ? sanitize_email( $business['email'] ) : '',
		'phone'     => ! empty( $business['phone'] ) ? sanitize_text_field( $business['phone'] ) : '',
		'timezone'  => $wp_dialyra_normalize_timezone( ! empty( $business['timezone'] ) ? $business['timezone'] : '' ),
		'country'   => ! empty( $business['country'] ) ? sanitize_text_field( $business['country'] ) : '',
		'status'    => ! empty( $business['status'] ) ? sanitize_key( $business['status'] ) : 'active',
	);
};

if ( 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) && isset( $_POST['wp_dialyra_settings_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_dialyra_settings_nonce'] ), 'wp-dialyra-settings' ) ) {
	$settings_action = isset( $_POST['wp_dialyra_settings_action'] ) ? sanitize_key( wp_unslash( $_POST['wp_dialyra_settings_action'] ) ) : '';

	if ( 'regenerate_access_token' === $settings_action ) {
		if ( ! $wp_dialyra_business_manager || ! $wp_dialyra_business_id ) {
			$wp_dialyra_error = esc_html__( 'Connect a Dialyra business before regenerating the access token.', 'wp-dialyra' );
		} else {
			$response = $wp_dialyra_business_manager->create_site_access_token( $wp_dialyra_business_id );

			if ( $response && method_exists( $response, 'is_successful' ) && $response->is_successful() ) {
				$wp_dialyra_success = esc_html__( 'Access token regenerated and saved successfully.', 'wp-dialyra' );
			} else {
				$wp_dialyra_error = $response && method_exists( $response, 'get_message' ) ? $response->get_message() : esc_html__( 'Access token could not be regenerated.', 'wp-dialyra' );
			}
		}
	} elseif ( 'save_business' === $settings_action ) {
		$selected_business_id = isset( $_POST['dialyra_business_id'] ) ? absint( wp_unslash( $_POST['dialyra_business_id'] ) ) : $wp_dialyra_business_id;

		if ( ! $wp_dialyra_business_manager || ! $selected_business_id ) {
			$wp_dialyra_error = esc_html__( 'Connect a Dialyra business before saving details.', 'wp-dialyra' );
		} else {
			if ( $selected_business_id !== $wp_dialyra_business_id ) {
				$connect_response = $wp_dialyra_business_manager->connect_business( $selected_business_id );

				if ( $connect_response && method_exists( $connect_response, 'is_successful' ) && $connect_response->is_successful() ) {
					$wp_dialyra_business_id = $selected_business_id;
				} else {
					$wp_dialyra_error = $connect_response && method_exists( $connect_response, 'get_message' ) ? $connect_response->get_message() : esc_html__( 'Selected business could not be connected.', 'wp-dialyra' );
				}
			}

			$response = empty( $wp_dialyra_error ) ? $wp_dialyra_business_manager->update_connected_business(
				array(
					'name'     => isset( $_POST['dialyra_business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_business_name'] ) ) : '',
					'email'    => isset( $_POST['dialyra_business_email'] ) ? sanitize_email( wp_unslash( $_POST['dialyra_business_email'] ) ) : '',
					'phone'    => isset( $_POST['dialyra_business_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_business_phone'] ) ) : '',
					'timezone' => isset( $_POST['dialyra_business_timezone'] ) ? $wp_dialyra_normalize_timezone( wp_unslash( $_POST['dialyra_business_timezone'] ) ) : $wp_dialyra_default_timezone,
					'country'  => isset( $_POST['dialyra_business_country'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_business_country'] ) ) : '',
				)
			) : false;

			if ( empty( $wp_dialyra_error ) && $response && method_exists( $response, 'is_successful' ) && $response->is_successful() ) {
				$wp_dialyra_success = esc_html__( 'Business details saved successfully.', 'wp-dialyra' );
			} elseif ( empty( $wp_dialyra_error ) ) {
				$wp_dialyra_error = $response && method_exists( $response, 'get_message' ) ? $response->get_message() : esc_html__( 'Business details could not be saved.', 'wp-dialyra' );
			}
		}
	} elseif ( 'create_business' === $settings_action ) {
		$business_name = isset( $_POST['dialyra_new_business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_new_business_name'] ) ) : '';

		if ( ! $wp_dialyra_business_manager ) {
			$wp_dialyra_error = esc_html__( 'Dialyra business tools are not available right now.', 'wp-dialyra' );
		} elseif ( empty( $business_name ) ) {
			$wp_dialyra_error = esc_html__( 'Business name is required.', 'wp-dialyra' );
		} else {
			$response = $wp_dialyra_business_manager->create_and_connect_business(
				array(
					'name'     => $business_name,
					'email'    => isset( $_POST['dialyra_new_business_email'] ) ? sanitize_email( wp_unslash( $_POST['dialyra_new_business_email'] ) ) : '',
					'phone'    => isset( $_POST['dialyra_new_business_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_new_business_phone'] ) ) : '',
					'timezone' => isset( $_POST['dialyra_new_business_timezone'] ) ? $wp_dialyra_normalize_timezone( wp_unslash( $_POST['dialyra_new_business_timezone'] ) ) : $wp_dialyra_default_timezone,
					'country'  => isset( $_POST['dialyra_new_business_country'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_new_business_country'] ) ) : '',
				)
			);

			if ( $response && method_exists( $response, 'is_successful' ) && $response->is_successful() ) {
				$wp_dialyra_business_id = $wp_dialyra_business_manager->get_connected_business_id();
				$wp_dialyra_success     = esc_html__( 'Business created and connected successfully.', 'wp-dialyra' );
			} else {
				$wp_dialyra_error = $response && method_exists( $response, 'get_message' ) ? $response->get_message() : esc_html__( 'Business could not be created.', 'wp-dialyra' );
			}
		}
	} elseif ( 'save_call_trigger' === $settings_action ) {
		$trigger_mode = isset( $_POST['dialyra_trigger_mode'] ) ? sanitize_key( wp_unslash( $_POST['dialyra_trigger_mode'] ) ) : 'instant';
		$trigger_mode = in_array( $trigger_mode, array( 'instant', 'delay', 'status' ), true ) ? $trigger_mode : 'instant';

		if ( ! $wp_dialyra_business_manager || ! method_exists( $wp_dialyra_business_manager, 'save_setup_settings' ) ) {
			$wp_dialyra_error = esc_html__( 'Dialyra setup settings are not available right now.', 'wp-dialyra' );
		} else {
			$wp_dialyra_business_manager->save_setup_settings(
				array(
					'call_trigger' => array(
						'mode'          => $trigger_mode,
						'order_status'  => isset( $_POST['dialyra_trigger_status'] ) ? sanitize_key( wp_unslash( $_POST['dialyra_trigger_status'] ) ) : 'processing',
						'delay_minutes' => isset( $_POST['dialyra_trigger_delay'] ) ? absint( wp_unslash( $_POST['dialyra_trigger_delay'] ) ) : 0,
					),
				)
			);

			$wp_dialyra_setup_settings = $wp_dialyra_business_manager->get_setup_settings();
			$wp_dialyra_success        = esc_html__( 'Call trigger mode saved successfully.', 'wp-dialyra' );
		}
	} elseif ( 'save_flow_capacity' === $settings_action ) {
		$selected_flow_id = isset( $_POST['dialyra_default_flow'] ) ? absint( wp_unslash( $_POST['dialyra_default_flow'] ) ) : 0;
		$max_concurrent_calls = isset( $_POST['dialyra_max_concurrent_calls'] ) ? max( 1, absint( wp_unslash( $_POST['dialyra_max_concurrent_calls'] ) ) ) : 1;

		if ( ! $wp_dialyra_business_manager || ! method_exists( $wp_dialyra_business_manager, 'save_setup_settings' ) ) {
			$wp_dialyra_error = esc_html__( 'Dialyra setup settings are not available right now.', 'wp-dialyra' );
		} else {
			$wp_dialyra_business_manager->save_setup_settings(
				array(
					'default_flow_id' => $selected_flow_id,
					'call_capacity'   => array(
						'max_concurrent_calls' => $max_concurrent_calls,
					),
				)
			);

			if ( $selected_flow_id && $wp_dialyra_flow_manager && method_exists( $wp_dialyra_flow_manager, 'set_default_flow' ) ) {
				$flow_response = $wp_dialyra_flow_manager->set_default_flow( $selected_flow_id );

				if ( $flow_response && method_exists( $flow_response, 'is_successful' ) && ! $flow_response->is_successful() ) {
					$wp_dialyra_error = $flow_response->get_message();
				}
			} elseif ( ! $selected_flow_id && $wp_dialyra_flow_manager && method_exists( $wp_dialyra_flow_manager, 'clear_default_flow' ) ) {
				$wp_dialyra_flow_manager->clear_default_flow();
			}

			if ( empty( $wp_dialyra_error ) ) {
				$wp_dialyra_setup_settings = $wp_dialyra_business_manager->get_setup_settings();
				$wp_dialyra_success        = esc_html__( 'Flow and capacity settings saved successfully.', 'wp-dialyra' );
			}
		}
	} elseif ( 'save_retry_policy' === $settings_action ) {
		$retry_enabled = ! empty( $_POST['dialyra_retry_enabled'] );
		$max_attempts = $retry_enabled && isset( $_POST['dialyra_retry_attempts'] ) ? max( 1, absint( wp_unslash( $_POST['dialyra_retry_attempts'] ) ) ) : 0;
		$delay_minutes = $retry_enabled && isset( $_POST['dialyra_retry_delay'] ) ? max( 1, absint( wp_unslash( $_POST['dialyra_retry_delay'] ) ) ) : 15;

		if ( ! $wp_dialyra_business_manager || ! method_exists( $wp_dialyra_business_manager, 'save_setup_settings' ) ) {
			$wp_dialyra_error = esc_html__( 'Dialyra setup settings are not available right now.', 'wp-dialyra' );
		} else {
			$wp_dialyra_business_manager->save_setup_settings(
				array(
					'retry_policy' => array(
						'max_attempts'               => $max_attempts,
						'delay_minutes'              => $delay_minutes,
						'only_during_business_hours' => ! empty( $_POST['dialyra_retry_business_hours'] ),
						'stop_on_confirmed'          => true,
						'stop_on_cancelled'          => true,
					),
				)
			);

			$wp_dialyra_setup_settings = $wp_dialyra_business_manager->get_setup_settings();
			$wp_dialyra_success        = esc_html__( 'Retry policy saved successfully.', 'wp-dialyra' );
		}
	} elseif ( 'save_business_hours' === $settings_action ) {
		$business_hours_mode = ! empty( $_POST['dialyra_business_hours_always_active'] ) ? 'always_active' : 'scheduled';
		$business_hours_days = isset( $_POST['dialyra_business_hours_days'] ) && is_array( $_POST['dialyra_business_hours_days'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['dialyra_business_hours_days'] ) ) : array();
		$business_hours_days = array_values( array_intersect( $business_hours_days, array_keys( $wp_dialyra_business_days ) ) );
		$business_hours_days = ! empty( $business_hours_days ) ? $business_hours_days : array( 'all' );

		if ( 'always_active' === $business_hours_mode ) {
			$business_hours_days = array( 'all' );
		}

		if ( ! $wp_dialyra_business_manager || ! method_exists( $wp_dialyra_business_manager, 'save_setup_settings' ) ) {
			$wp_dialyra_error = esc_html__( 'Dialyra setup settings are not available right now.', 'wp-dialyra' );
		} else {
			$wp_dialyra_business_manager->save_setup_settings(
				array(
					'business_hours' => array(
						'availability_mode' => $business_hours_mode,
						'timezone'          => isset( $_POST['dialyra_business_hours_timezone'] ) ? $wp_dialyra_normalize_timezone( wp_unslash( $_POST['dialyra_business_hours_timezone'] ) ) : $wp_dialyra_default_timezone,
						'days'              => $business_hours_days,
						'open_time'         => isset( $_POST['dialyra_business_hours_open'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_business_hours_open'] ) ) : '09:00',
						'close_time'        => isset( $_POST['dialyra_business_hours_close'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_business_hours_close'] ) ) : '18:00',
					),
				)
			);

			$wp_dialyra_setup_settings = $wp_dialyra_business_manager->get_setup_settings();
			$wp_dialyra_success        = esc_html__( 'Business hours saved successfully.', 'wp-dialyra' );
		}
	} elseif ( 'save_order_status_mapping' === $settings_action ) {
		$skip_call_statuses = isset( $_POST['dialyra_skip_call_statuses'] ) && is_array( $_POST['dialyra_skip_call_statuses'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['dialyra_skip_call_statuses'] ) ) : array();
		$skip_call_statuses = array_values( array_intersect( $skip_call_statuses, array_keys( $wp_dialyra_order_statuses ) ) );

		if ( ! $wp_dialyra_business_manager || ! method_exists( $wp_dialyra_business_manager, 'save_setup_settings' ) ) {
			$wp_dialyra_error = esc_html__( 'Dialyra setup settings are not available right now.', 'wp-dialyra' );
		} else {
			$wp_dialyra_business_manager->save_setup_settings(
				array(
					'order_status_map' => array(
						'confirmed_status'  => isset( $_POST['dialyra_confirmed_status'] ) ? $wp_dialyra_sanitize_order_status( wp_unslash( $_POST['dialyra_confirmed_status'] ), 'processing' ) : 'processing',
						'cancelled_status'  => isset( $_POST['dialyra_cancelled_status'] ) ? $wp_dialyra_sanitize_order_status( wp_unslash( $_POST['dialyra_cancelled_status'] ), 'cancelled' ) : 'cancelled',
						'no_answer_status'  => isset( $_POST['dialyra_no_answer_status'] ) ? $wp_dialyra_sanitize_order_status( wp_unslash( $_POST['dialyra_no_answer_status'] ) ) : 'no_change',
						'busy_status'       => isset( $_POST['dialyra_busy_status'] ) ? $wp_dialyra_sanitize_order_status( wp_unslash( $_POST['dialyra_busy_status'] ) ) : 'no_change',
						'failed_status'      => isset( $_POST['dialyra_failed_status'] ) ? $wp_dialyra_sanitize_order_status( wp_unslash( $_POST['dialyra_failed_status'] ) ) : 'no_change',
						'skip_call_statuses' => $skip_call_statuses,
					),
				)
			);

			$wp_dialyra_setup_settings = $wp_dialyra_business_manager->get_setup_settings();
			$wp_dialyra_success        = esc_html__( 'Order status mapping saved successfully.', 'wp-dialyra' );
		}
	}
}

if ( $wp_dialyra_business_manager ) {
	$businesses_response = $wp_dialyra_business_manager->get_businesses();
	if ( $businesses_response && method_exists( $businesses_response, 'is_successful' ) && $businesses_response->is_successful() ) {
		$wp_dialyra_businesses = array_values( array_filter( array_map( $wp_dialyra_normalize_business, $wp_dialyra_extract_response_items( $businesses_response ) ) ) );
	}
}

if ( $wp_dialyra_flow_manager && $wp_dialyra_business_id ) {
	$flows_response = $wp_dialyra_flow_manager->get_flows( array( 'business_id' => $wp_dialyra_business_id ) );

	if ( $flows_response && method_exists( $flows_response, 'is_successful' ) && $flows_response->is_successful() ) {
		$wp_dialyra_flows = array_values( array_filter( array_map( $wp_dialyra_normalize_flow, $wp_dialyra_extract_response_items( $flows_response ) ), static function ( $flow ) {
			return ! empty( $flow['id'] );
		} ) );
	} elseif ( $flows_response && method_exists( $flows_response, 'get_message' ) ) {
		$wp_dialyra_flow_fetch_error = $flows_response->get_message();
	}
}

$wp_dialyra_connected_business = $wp_dialyra_business_manager && method_exists( $wp_dialyra_business_manager, 'get_connected_business_data' ) ? $wp_dialyra_normalize_business( $wp_dialyra_business_manager->get_connected_business_data() ) : $wp_dialyra_normalize_business( array() );

if ( $wp_dialyra_business_id && empty( $wp_dialyra_connected_business['id'] ) ) {
	$wp_dialyra_connected_business['id'] = $wp_dialyra_business_id;
}

if ( $wp_dialyra_business_id && ! empty( $wp_dialyra_businesses ) ) {
	foreach ( $wp_dialyra_businesses as $available_business ) {
		if ( absint( $available_business['id'] ) === $wp_dialyra_business_id ) {
			$wp_dialyra_connected_business = array_merge( $available_business, array_filter( $wp_dialyra_connected_business ) );
			break;
		}
	}
}

$wp_dialyra_business_status = ! empty( $wp_dialyra_connected_business['status'] ) ? sanitize_key( $wp_dialyra_connected_business['status'] ) : 'inactive';
$wp_dialyra_business_status_class = in_array( $wp_dialyra_business_status, array( 'active', 'inactive', 'suspended', 'deleted' ), true ) ? $wp_dialyra_business_status : 'inactive';

$wp_dialyra_site_token = $wp_dialyra_business_manager && method_exists( $wp_dialyra_business_manager, 'get_site_access_token_data' ) ? $wp_dialyra_business_manager->get_site_access_token_data() : array();
$wp_dialyra_site_token = is_array( $wp_dialyra_site_token ) ? $wp_dialyra_site_token : array();
$wp_dialyra_token_for_current_business = ! empty( $wp_dialyra_site_token['token'] ) && ! empty( $wp_dialyra_site_token['business_id'] ) && absint( $wp_dialyra_site_token['business_id'] ) === $wp_dialyra_business_id;
$wp_dialyra_token_prefix = ! empty( $wp_dialyra_site_token['token_prefix'] ) ? sanitize_text_field( $wp_dialyra_site_token['token_prefix'] ) : '';
$wp_dialyra_token_created_at = ! empty( $wp_dialyra_site_token['created_at'] ) ? sanitize_text_field( $wp_dialyra_site_token['created_at'] ) : '';
$wp_dialyra_token_display = $wp_dialyra_token_for_current_business ? ( $wp_dialyra_token_prefix ? $wp_dialyra_token_prefix . '******' : '******' ) : __( 'No token created', 'wp-dialyra' );
$wp_dialyra_token_created_label = $wp_dialyra_token_created_at ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $wp_dialyra_token_created_at ) ) : '';
$wp_dialyra_call_trigger_settings = isset( $wp_dialyra_setup_settings['call_trigger'] ) && is_array( $wp_dialyra_setup_settings['call_trigger'] ) ? $wp_dialyra_setup_settings['call_trigger'] : array();
$wp_dialyra_trigger_mode = ! empty( $wp_dialyra_call_trigger_settings['mode'] ) ? sanitize_key( $wp_dialyra_call_trigger_settings['mode'] ) : 'instant';
$wp_dialyra_trigger_mode = in_array( $wp_dialyra_trigger_mode, array( 'instant', 'delay', 'status' ), true ) ? $wp_dialyra_trigger_mode : 'instant';
$wp_dialyra_trigger_delay = isset( $wp_dialyra_call_trigger_settings['delay_minutes'] ) ? absint( $wp_dialyra_call_trigger_settings['delay_minutes'] ) : 0;
$wp_dialyra_trigger_status = ! empty( $wp_dialyra_call_trigger_settings['order_status'] ) ? sanitize_key( $wp_dialyra_call_trigger_settings['order_status'] ) : 'processing';
$wp_dialyra_capacity_settings = isset( $wp_dialyra_setup_settings['call_capacity'] ) && is_array( $wp_dialyra_setup_settings['call_capacity'] ) ? $wp_dialyra_setup_settings['call_capacity'] : array();
$wp_dialyra_default_flow_id = $wp_dialyra_flow_manager && method_exists( $wp_dialyra_flow_manager, 'get_default_flow_id' ) ? $wp_dialyra_flow_manager->get_default_flow_id() : absint( isset( $wp_dialyra_setup_settings['default_flow_id'] ) ? $wp_dialyra_setup_settings['default_flow_id'] : 0 );
$wp_dialyra_default_flow_id = $wp_dialyra_default_flow_id ? $wp_dialyra_default_flow_id : absint( isset( $wp_dialyra_setup_settings['default_flow_id'] ) ? $wp_dialyra_setup_settings['default_flow_id'] : 0 );
$wp_dialyra_max_concurrent_calls = isset( $wp_dialyra_capacity_settings['max_concurrent_calls'] ) ? max( 1, absint( $wp_dialyra_capacity_settings['max_concurrent_calls'] ) ) : 1;
$wp_dialyra_retry_settings = isset( $wp_dialyra_setup_settings['retry_policy'] ) && is_array( $wp_dialyra_setup_settings['retry_policy'] ) ? $wp_dialyra_setup_settings['retry_policy'] : array();
$wp_dialyra_retry_attempts = isset( $wp_dialyra_retry_settings['max_attempts'] ) ? absint( $wp_dialyra_retry_settings['max_attempts'] ) : 2;
$wp_dialyra_retry_enabled = $wp_dialyra_retry_attempts > 0;
$wp_dialyra_retry_delay = isset( $wp_dialyra_retry_settings['delay_minutes'] ) ? max( 1, absint( $wp_dialyra_retry_settings['delay_minutes'] ) ) : 15;
$wp_dialyra_retry_business_hours = ! empty( $wp_dialyra_retry_settings['only_during_business_hours'] );
$wp_dialyra_business_hours_settings = isset( $wp_dialyra_setup_settings['business_hours'] ) && is_array( $wp_dialyra_setup_settings['business_hours'] ) ? $wp_dialyra_setup_settings['business_hours'] : array();
$wp_dialyra_business_hours_mode = ! empty( $wp_dialyra_business_hours_settings['availability_mode'] ) ? sanitize_key( $wp_dialyra_business_hours_settings['availability_mode'] ) : 'always_active';
$wp_dialyra_business_hours_mode = in_array( $wp_dialyra_business_hours_mode, array( 'always_active', 'scheduled' ), true ) ? $wp_dialyra_business_hours_mode : 'always_active';
$wp_dialyra_business_hours_timezone = ! empty( $wp_dialyra_business_hours_settings['timezone'] ) ? $wp_dialyra_normalize_timezone( $wp_dialyra_business_hours_settings['timezone'] ) : $wp_dialyra_default_timezone;
$wp_dialyra_selected_days = ! empty( $wp_dialyra_business_hours_settings['days'] ) && is_array( $wp_dialyra_business_hours_settings['days'] ) ? array_map( 'sanitize_key', $wp_dialyra_business_hours_settings['days'] ) : array( 'all' );
$wp_dialyra_selected_days = array_values( array_intersect( $wp_dialyra_selected_days, array_keys( $wp_dialyra_business_days ) ) );
$wp_dialyra_selected_days = ! empty( $wp_dialyra_selected_days ) ? $wp_dialyra_selected_days : array( 'all' );
$wp_dialyra_business_hours_open = ! empty( $wp_dialyra_business_hours_settings['open_time'] ) ? sanitize_text_field( $wp_dialyra_business_hours_settings['open_time'] ) : '09:00';
$wp_dialyra_business_hours_close = ! empty( $wp_dialyra_business_hours_settings['close_time'] ) ? sanitize_text_field( $wp_dialyra_business_hours_settings['close_time'] ) : '18:00';
$wp_dialyra_status_mapping_settings = isset( $wp_dialyra_setup_settings['order_status_map'] ) && is_array( $wp_dialyra_setup_settings['order_status_map'] ) ? $wp_dialyra_setup_settings['order_status_map'] : array();
$wp_dialyra_map_confirmed = ! empty( $wp_dialyra_status_mapping_settings['confirmed_status'] ) ? $wp_dialyra_sanitize_order_status( $wp_dialyra_status_mapping_settings['confirmed_status'], 'processing' ) : 'processing';
$wp_dialyra_map_cancelled = ! empty( $wp_dialyra_status_mapping_settings['cancelled_status'] ) ? $wp_dialyra_sanitize_order_status( $wp_dialyra_status_mapping_settings['cancelled_status'], 'cancelled' ) : 'cancelled';
$wp_dialyra_map_no_answer = ! empty( $wp_dialyra_status_mapping_settings['no_answer_status'] ) ? $wp_dialyra_sanitize_order_status( $wp_dialyra_status_mapping_settings['no_answer_status'] ) : 'no_change';
$wp_dialyra_map_busy = ! empty( $wp_dialyra_status_mapping_settings['busy_status'] ) ? $wp_dialyra_sanitize_order_status( $wp_dialyra_status_mapping_settings['busy_status'] ) : 'no_change';
$wp_dialyra_map_failed = ! empty( $wp_dialyra_status_mapping_settings['failed_status'] ) ? $wp_dialyra_sanitize_order_status( $wp_dialyra_status_mapping_settings['failed_status'] ) : 'no_change';
$wp_dialyra_skip_call_statuses = isset( $wp_dialyra_status_mapping_settings['skip_call_statuses'] ) && is_array( $wp_dialyra_status_mapping_settings['skip_call_statuses'] ) ? array_map( 'sanitize_key', $wp_dialyra_status_mapping_settings['skip_call_statuses'] ) : array( 'completed', 'cancelled', 'draft', 'refunded' );
$wp_dialyra_skip_call_statuses = array_values( array_intersect( $wp_dialyra_skip_call_statuses, array_keys( $wp_dialyra_order_statuses ) ) );
?>

<section class="wp-dialyra-settings">
	<div class="wp-dialyra-settings__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Settings', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Configure how Dialyra calls, retries, and updates WooCommerce orders.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Manage access, automation triggers, call capacity, business hours, webhook security, and order status mapping from one place.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-settings__actions">
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

	<div class="wp-dialyra-settings__grid">
		<section class="wp-dialyra-settings-card wp-dialyra-settings-card--wide">
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">01</span>
				<div>
					<h3><?php esc_html_e( 'Access token', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Manage and regenerate the token used for Dialyra API requests.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-settings-row">
				<div class="wp-dialyra-token-field">
					<input id="wp-dialyra-access-token" type="text" value="<?php echo esc_attr( $wp_dialyra_token_display ); ?>" readonly aria-label="<?php esc_attr_e( 'Current access token', 'wp-dialyra' ); ?>">
					<button type="button" data-dialyra-dialog-open="wp-dialyra-regenerate-token-dialog"><?php esc_html_e( 'Regenerate', 'wp-dialyra' ); ?></button>
				</div>
				<p class="wp-dialyra-settings-help">
					<?php
					if ( $wp_dialyra_token_for_current_business ) {
						echo esc_html( $wp_dialyra_token_created_label ? sprintf( /* translators: %s: token creation datetime. */ __( 'Created: %s', 'wp-dialyra' ), $wp_dialyra_token_created_label ) : __( 'Access token is active for this business.', 'wp-dialyra' ) );
					} else {
						esc_html_e( 'No access token is saved for the connected business yet.', 'wp-dialyra' );
					}
					?>
				</p>
			</div>
		</section>

		<form class="wp-dialyra-settings-card wp-dialyra-settings-card--wide" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>">
			<?php wp_nonce_field( 'wp-dialyra-settings', 'wp_dialyra_settings_nonce' ); ?>
			<div class="wp-dialyra-settings-card__head wp-dialyra-settings-card__head--split">
				<div class="wp-dialyra-settings-card__title">
					<span aria-hidden="true">02</span>
					<div>
						<h3>
								<span class="wp-dialyra-status-dot wp-dialyra-status-dot--<?php echo esc_attr( $wp_dialyra_business_status_class ); ?>" title="<?php echo esc_attr( sprintf( /* translators: %s: business status. */ __( 'Business status: %s', 'wp-dialyra' ), $wp_dialyra_business_status ) ); ?>" data-dialyra-business-status-dot></span>
							<?php esc_html_e( 'Business details', 'wp-dialyra' ); ?>
						</h3>
						<p><?php esc_html_e( 'Edit the connected Dialyra business profile used for WooCommerce call automation.', 'wp-dialyra' ); ?></p>
					</div>
				</div>

				<div class="wp-dialyra-business-toolbar">
					<div class="wp-dialyra-settings-row wp-dialyra-business-select">
						<label for="wp-dialyra-business-select"><?php esc_html_e( 'Select business', 'wp-dialyra' ); ?></label>
						<select id="wp-dialyra-business-select" name="dialyra_business_id">
							<?php if ( empty( $wp_dialyra_businesses ) ) : ?>
								<option
									value="<?php echo esc_attr( $wp_dialyra_business_id ); ?>"
									data-name="<?php echo esc_attr( $wp_dialyra_connected_business['name'] ); ?>"
									data-email="<?php echo esc_attr( $wp_dialyra_connected_business['email'] ); ?>"
									data-phone="<?php echo esc_attr( $wp_dialyra_connected_business['phone'] ); ?>"
									data-timezone="<?php echo esc_attr( $wp_dialyra_connected_business['timezone'] ); ?>"
									data-country="<?php echo esc_attr( $wp_dialyra_connected_business['country'] ); ?>"
									data-status="<?php echo esc_attr( $wp_dialyra_connected_business['status'] ); ?>"
								><?php echo esc_html( $wp_dialyra_connected_business['name'] ? $wp_dialyra_connected_business['name'] : __( 'Connected business', 'wp-dialyra' ) ); ?></option>
							<?php else : ?>
								<?php foreach ( $wp_dialyra_businesses as $business ) : ?>
									<option
										value="<?php echo esc_attr( $business['id'] ); ?>"
										data-name="<?php echo esc_attr( $business['name'] ); ?>"
										data-email="<?php echo esc_attr( $business['email'] ); ?>"
										data-phone="<?php echo esc_attr( $business['phone'] ); ?>"
										data-timezone="<?php echo esc_attr( $business['timezone'] ); ?>"
										data-country="<?php echo esc_attr( $business['country'] ); ?>"
										data-status="<?php echo esc_attr( $business['status'] ); ?>"
										<?php selected( $wp_dialyra_business_id, $business['id'] ); ?>
									><?php echo esc_html( $business['name'] ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</div>

					<button class="wp-dialyra-button wp-dialyra-button--ghost wp-dialyra-business-create-button" type="button" data-dialyra-dialog-open="wp-dialyra-create-business-dialog">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'Create business', 'wp-dialyra' ); ?>
					</button>
				</div>
			</div>

			<div class="wp-dialyra-business-grid">
				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-name"><?php esc_html_e( 'Business name', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-name" name="dialyra_business_name" type="text" value="<?php echo esc_attr( $wp_dialyra_connected_business['name'] ); ?>" required>
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-email"><?php esc_html_e( 'Business email', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-email" name="dialyra_business_email" type="email" value="<?php echo esc_attr( $wp_dialyra_connected_business['email'] ); ?>">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-phone"><?php esc_html_e( 'Business phone', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-phone" name="dialyra_business_phone" type="tel" value="<?php echo esc_attr( $wp_dialyra_connected_business['phone'] ); ?>">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-timezone"><?php esc_html_e( 'Timezone', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-timezone" name="dialyra_business_timezone" type="text" value="<?php echo esc_attr( $wp_dialyra_connected_business['timezone'] ); ?>">
				</div>

				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-country"><?php esc_html_e( 'Country', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-country" name="dialyra_business_country" type="text" value="<?php echo esc_attr( $wp_dialyra_connected_business['country'] ); ?>">
				</div>

			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" name="wp_dialyra_settings_action" value="save_business"><?php esc_html_e( 'Save business', 'wp-dialyra' ); ?></button>
			</div>
		</form>

		<form class="wp-dialyra-settings-card" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>" data-dialyra-dynamic-group>
			<?php wp_nonce_field( 'wp-dialyra-settings', 'wp_dialyra_settings_nonce' ); ?>
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">03</span>
				<div>
					<h3><?php esc_html_e( 'Call trigger mode', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Choose when WooCommerce orders should trigger calls.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-trigger-mode"><?php esc_html_e( 'Trigger mode', 'wp-dialyra' ); ?></label>
				<select id="wp-dialyra-trigger-mode" name="dialyra_trigger_mode" data-dialyra-dynamic-select>
					<option value="instant" <?php selected( $wp_dialyra_trigger_mode, 'instant' ); ?>><?php esc_html_e( 'Instant on new order', 'wp-dialyra' ); ?></option>
					<option value="delay" <?php selected( $wp_dialyra_trigger_mode, 'delay' ); ?>><?php esc_html_e( 'Delay after order', 'wp-dialyra' ); ?></option>
					<option value="status" <?php selected( $wp_dialyra_trigger_mode, 'status' ); ?>><?php esc_html_e( 'Specific WooCommerce status', 'wp-dialyra' ); ?></option>
				</select>
			</div>

			<div class="wp-dialyra-settings-row" data-dialyra-show-for="delay">
				<label for="wp-dialyra-trigger-delay"><?php esc_html_e( 'Delay minutes', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-trigger-delay" name="dialyra_trigger_delay" type="number" min="0" value="<?php echo esc_attr( $wp_dialyra_trigger_delay ); ?>">
			</div>

			<div class="wp-dialyra-settings-row" data-dialyra-show-for="status">
				<label for="wp-dialyra-trigger-status"><?php esc_html_e( 'Trigger status', 'wp-dialyra' ); ?></label>
				<select id="wp-dialyra-trigger-status" name="dialyra_trigger_status">
					<?php foreach ( $wp_dialyra_order_statuses as $wp_dialyra_status_key => $wp_dialyra_status_label ) : ?>
						<?php if ( 'no_change' === $wp_dialyra_status_key ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<option value="<?php echo esc_attr( $wp_dialyra_status_key ); ?>" <?php selected( $wp_dialyra_trigger_status, $wp_dialyra_status_key ); ?>><?php echo esc_html( $wp_dialyra_status_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" name="wp_dialyra_settings_action" value="save_call_trigger"><?php esc_html_e( 'Save trigger', 'wp-dialyra' ); ?></button>
			</div>
		</form>

		<form class="wp-dialyra-settings-card" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>" data-dialyra-dynamic-group>
			<?php wp_nonce_field( 'wp-dialyra-settings', 'wp_dialyra_settings_nonce' ); ?>
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">04</span>
				<div>
					<h3><?php esc_html_e( 'Flow and capacity', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Select the default flow and concurrent call limit.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<?php if ( ! empty( $wp_dialyra_flows ) ) : ?>
				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-default-flow"><?php esc_html_e( 'Default flow', 'wp-dialyra' ); ?></label>
					<select id="wp-dialyra-default-flow" name="dialyra_default_flow">
						<option value="0"><?php esc_html_e( 'No default flow selected', 'wp-dialyra' ); ?></option>
						<?php foreach ( $wp_dialyra_flows as $wp_dialyra_flow ) : ?>
							<option value="<?php echo esc_attr( $wp_dialyra_flow['id'] ); ?>" <?php selected( $wp_dialyra_default_flow_id, $wp_dialyra_flow['id'] ); ?>>
								<?php echo esc_html( $wp_dialyra_flow['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<small><?php esc_html_e( 'Flows are fetched from the connected Dialyra business.', 'wp-dialyra' ); ?></small>
				</div>
			<?php else : ?>
				<div class="wp-dialyra-empty-card wp-dialyra-empty-card--flow">
					<span class="dashicons dashicons-warning" aria-hidden="true"></span>
					<div>
						<strong><?php esc_html_e( 'No flow found', 'wp-dialyra' ); ?></strong>
						<p>
							<?php
							echo esc_html(
								$wp_dialyra_flow_fetch_error
									? $wp_dialyra_flow_fetch_error
									: __( 'Create a flow first, then return here to choose it as the default flow.', 'wp-dialyra' )
							);
							?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-builder' ) ); ?>"><?php esc_html_e( 'Create Flow', 'wp-dialyra' ); ?></a>
					</div>
				</div>
			<?php endif; ?>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-max-concurrent"><?php esc_html_e( 'Max concurrent calls', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-max-concurrent" name="dialyra_max_concurrent_calls" type="number" min="1" value="<?php echo esc_attr( $wp_dialyra_max_concurrent_calls ); ?>">
			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" name="wp_dialyra_settings_action" value="save_flow_capacity"><?php esc_html_e( 'Save flow settings', 'wp-dialyra' ); ?></button>
			</div>
		</form>

		<form class="wp-dialyra-settings-card" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>" data-dialyra-dynamic-group>
			<?php wp_nonce_field( 'wp-dialyra-settings', 'wp_dialyra_settings_nonce' ); ?>
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">05</span>
				<div>
					<h3><?php esc_html_e( 'Retry policy', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Control failed, busy, no-answer, and timeout retries.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-toggle-row">
				<span><?php esc_html_e( 'Enable retry', 'wp-dialyra' ); ?></span>
				<label>
					<input type="checkbox" name="dialyra_retry_enabled" value="enabled" <?php checked( $wp_dialyra_retry_enabled ); ?> data-dialyra-dynamic-select>
					<i></i>
				</label>
			</div>

			<div class="wp-dialyra-settings-row" data-dialyra-show-for="enabled">
				<label for="wp-dialyra-retry-delay"><?php esc_html_e( 'Retry delay minutes', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-retry-delay" name="dialyra_retry_delay" type="number" min="1" value="<?php echo esc_attr( $wp_dialyra_retry_delay ); ?>">
			</div>

			<div class="wp-dialyra-settings-row" data-dialyra-show-for="enabled">
				<label for="wp-dialyra-retry-limit"><?php esc_html_e( 'Max retry attempts', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-retry-limit" name="dialyra_retry_attempts" type="number" min="1" value="<?php echo esc_attr( max( 1, $wp_dialyra_retry_attempts ) ); ?>">
			</div>

			<label class="wp-dialyra-setup-check" data-dialyra-show-for="enabled">
				<input type="checkbox" name="dialyra_retry_business_hours" <?php checked( $wp_dialyra_retry_business_hours ); ?>>
				<span><?php esc_html_e( 'Retry only during business hours', 'wp-dialyra' ); ?></span>
			</label>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" name="wp_dialyra_settings_action" value="save_retry_policy"><?php esc_html_e( 'Save retry policy', 'wp-dialyra' ); ?></button>
			</div>
		</form>

		<form class="wp-dialyra-settings-card" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>" data-dialyra-business-hours-group>
			<?php wp_nonce_field( 'wp-dialyra-settings', 'wp_dialyra_settings_nonce' ); ?>
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">06</span>
				<div>
					<h3><?php esc_html_e( 'Business hours', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Queue calls outside your operating schedule.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-toggle-row">
				<span><?php esc_html_e( 'Always active', 'wp-dialyra' ); ?></span>
				<label>
					<input type="checkbox" name="dialyra_business_hours_always_active" value="always_active" <?php checked( $wp_dialyra_business_hours_mode, 'always_active' ); ?> data-dialyra-business-hours-toggle>
					<i></i>
				</label>
			</div>

			<div class="wp-dialyra-settings-row" data-dialyra-business-hours-always>
				<span class="wp-dialyra-setup-label"><?php esc_html_e( 'Calling days', 'wp-dialyra' ); ?></span>
				<div class="wp-dialyra-setup-muted-card">
					<strong><?php esc_html_e( 'All days selected', 'wp-dialyra' ); ?></strong>
					<small><?php esc_html_e( 'Dialyra can place calls any day, all day. No schedule fields are needed.', 'wp-dialyra' ); ?></small>
				</div>
			</div>

			<div class="wp-dialyra-settings-row" data-dialyra-business-hours-scheduled>
				<label for="wp-dialyra-business-hours-timezone"><?php esc_html_e( 'Timezone', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-business-hours-timezone" name="dialyra_business_hours_timezone" type="text" value="<?php echo esc_attr( $wp_dialyra_business_hours_timezone ); ?>">
			</div>

			<div class="wp-dialyra-settings-row wp-dialyra-day-picker-row" data-dialyra-business-hours-scheduled>
				<span class="wp-dialyra-setup-label"><?php esc_html_e( 'Calling days', 'wp-dialyra' ); ?></span>
				<small><?php esc_html_e( 'Choose which days Dialyra can place calls.', 'wp-dialyra' ); ?></small>
				<div class="wp-dialyra-day-picker" aria-label="<?php esc_attr_e( 'Select calling days', 'wp-dialyra' ); ?>">
					<?php foreach ( $wp_dialyra_business_days as $wp_dialyra_day_key => $wp_dialyra_day_label ) : ?>
						<label>
							<input type="checkbox" name="dialyra_business_hours_days[]" value="<?php echo esc_attr( $wp_dialyra_day_key ); ?>" <?php checked( in_array( $wp_dialyra_day_key, $wp_dialyra_selected_days, true ) ); ?>>
							<span><?php echo esc_html( $wp_dialyra_day_label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="wp-dialyra-settings-inline" data-dialyra-business-hours-scheduled>
				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-hours-open"><?php esc_html_e( 'Open time', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-hours-open" name="dialyra_business_hours_open" type="time" value="<?php echo esc_attr( $wp_dialyra_business_hours_open ); ?>">
				</div>
				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-business-hours-close"><?php esc_html_e( 'Close time', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-business-hours-close" name="dialyra_business_hours_close" type="time" value="<?php echo esc_attr( $wp_dialyra_business_hours_close ); ?>">
				</div>
			</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" name="wp_dialyra_settings_action" value="save_business_hours"><?php esc_html_e( 'Save business hours', 'wp-dialyra' ); ?></button>
			</div>
		</form>

		<form class="wp-dialyra-settings-card wp-dialyra-settings-card--wide" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>">
			<?php wp_nonce_field( 'wp-dialyra-settings', 'wp_dialyra_settings_nonce' ); ?>
			<div class="wp-dialyra-settings-card__head">
				<span aria-hidden="true">07</span>
				<div>
					<h3><?php esc_html_e( 'Order status mapping', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Map Dialyra call outcomes to WooCommerce order statuses and skip calls for final statuses.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-status-map">
				<div>
					<span><i class="dashicons dashicons-yes-alt" aria-hidden="true"></i><?php esc_html_e( 'Confirmed', 'wp-dialyra' ); ?></span>
					<strong>→</strong>
					<select name="dialyra_confirmed_status">
						<?php foreach ( $wp_dialyra_order_statuses as $wp_dialyra_status_key => $wp_dialyra_status_label ) : ?>
							<option value="<?php echo esc_attr( $wp_dialyra_status_key ); ?>" <?php selected( $wp_dialyra_map_confirmed, $wp_dialyra_status_key ); ?>><?php echo esc_html( $wp_dialyra_status_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<span><i class="dashicons dashicons-dismiss" aria-hidden="true"></i><?php esc_html_e( 'Cancelled', 'wp-dialyra' ); ?></span>
					<strong>→</strong>
					<select name="dialyra_cancelled_status">
						<?php foreach ( $wp_dialyra_order_statuses as $wp_dialyra_status_key => $wp_dialyra_status_label ) : ?>
							<option value="<?php echo esc_attr( $wp_dialyra_status_key ); ?>" <?php selected( $wp_dialyra_map_cancelled, $wp_dialyra_status_key ); ?>><?php echo esc_html( $wp_dialyra_status_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<span><i class="dashicons dashicons-phone" aria-hidden="true"></i><?php esc_html_e( 'No Answer', 'wp-dialyra' ); ?></span>
					<strong>→</strong>
					<select name="dialyra_no_answer_status">
						<?php foreach ( $wp_dialyra_order_statuses as $wp_dialyra_status_key => $wp_dialyra_status_label ) : ?>
							<option value="<?php echo esc_attr( $wp_dialyra_status_key ); ?>" <?php selected( $wp_dialyra_map_no_answer, $wp_dialyra_status_key ); ?>><?php echo esc_html( $wp_dialyra_status_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<span><i class="dashicons dashicons-clock" aria-hidden="true"></i><?php esc_html_e( 'Busy', 'wp-dialyra' ); ?></span>
					<strong>→</strong>
					<select name="dialyra_busy_status">
						<?php foreach ( $wp_dialyra_order_statuses as $wp_dialyra_status_key => $wp_dialyra_status_label ) : ?>
							<option value="<?php echo esc_attr( $wp_dialyra_status_key ); ?>" <?php selected( $wp_dialyra_map_busy, $wp_dialyra_status_key ); ?>><?php echo esc_html( $wp_dialyra_status_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<span><i class="dashicons dashicons-warning" aria-hidden="true"></i><?php esc_html_e( 'Failed', 'wp-dialyra' ); ?></span>
					<strong>→</strong>
					<select name="dialyra_failed_status">
						<?php foreach ( $wp_dialyra_order_statuses as $wp_dialyra_status_key => $wp_dialyra_status_label ) : ?>
							<option value="<?php echo esc_attr( $wp_dialyra_status_key ); ?>" <?php selected( $wp_dialyra_map_failed, $wp_dialyra_status_key ); ?>><?php echo esc_html( $wp_dialyra_status_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

				<div class="wp-dialyra-skip-status-card">
					<div class="wp-dialyra-skip-status-card__head">
						<span class="dashicons dashicons-controls-forward" aria-hidden="true"></span>
						<div>
							<label><?php esc_html_e( 'Skip calls for order statuses', 'wp-dialyra' ); ?></label>
							<small><?php esc_html_e( 'Choose final order statuses where Dialyra should not place a call.', 'wp-dialyra' ); ?></small>
						</div>
					</div>

					<div class="wp-dialyra-skip-status-options">
						<?php foreach ( $wp_dialyra_order_statuses as $wp_dialyra_status_key => $wp_dialyra_status_label ) : ?>
							<?php if ( 'no_change' === $wp_dialyra_status_key ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<label class="wp-dialyra-skip-status-option">
								<input type="checkbox" name="dialyra_skip_call_statuses[]" value="<?php echo esc_attr( $wp_dialyra_status_key ); ?>" <?php checked( in_array( $wp_dialyra_status_key, $wp_dialyra_skip_call_statuses, true ) ); ?>>
								<span class="wp-dialyra-skip-status-option__check" aria-hidden="true"><i class="dashicons dashicons-yes"></i></span>
								<span class="wp-dialyra-skip-status-option__text"><?php echo esc_html( $wp_dialyra_status_label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>

					<p class="wp-dialyra-settings-help"><?php esc_html_e( 'Default skipped statuses are Completed, Cancelled, Draft, and Refunded. Dialyra will call anyway for unchecked statuses.', 'wp-dialyra' ); ?></p>
				</div>

			<div class="wp-dialyra-settings-card__footer">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" name="wp_dialyra_settings_action" value="save_order_status_mapping"><?php esc_html_e( 'Save status mapping', 'wp-dialyra' ); ?></button>
			</div>
		</form>
	</div>

	<div id="wp-dialyra-regenerate-token-dialog" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="wp-dialyra-regenerate-token-title" hidden data-dialyra-dialog>
		<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
		<div class="wp-dialyra-dialog__panel">
			<div class="wp-dialyra-dialog__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Confirm action', 'wp-dialyra' ); ?></p>
					<h3 id="wp-dialyra-regenerate-token-title"><?php esc_html_e( 'Regenerate access token?', 'wp-dialyra' ); ?></h3>
				</div>
				<button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>
			<p class="wp-dialyra-dialog__warning"><?php esc_html_e( 'This creates a new Dialyra site access token and saves it for this WordPress store. Use this when the current token is missing, expired, or compromised.', 'wp-dialyra' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>">
				<?php wp_nonce_field( 'wp-dialyra-settings', 'wp_dialyra_settings_nonce' ); ?>
				<input type="hidden" name="wp_dialyra_settings_action" value="regenerate_access_token">
				<div class="wp-dialyra-agent-panel__footer">
					<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-dialog-close><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></button>
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Regenerate token', 'wp-dialyra' ); ?></button>
				</div>
			</form>
		</div>
	</div>

	<div id="wp-dialyra-create-business-dialog" class="wp-dialyra-dialog wp-dialyra-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="wp-dialyra-create-business-title" hidden data-dialyra-dialog>
		<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
		<div class="wp-dialyra-dialog__panel">
			<div class="wp-dialyra-dialog__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'New workspace', 'wp-dialyra' ); ?></p>
					<h3 id="wp-dialyra-create-business-title"><?php esc_html_e( 'Create Dialyra business', 'wp-dialyra' ); ?></h3>
				</div>
				<button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>">
				<?php wp_nonce_field( 'wp-dialyra-settings', 'wp_dialyra_settings_nonce' ); ?>
				<input type="hidden" name="wp_dialyra_settings_action" value="create_business">

				<div class="wp-dialyra-business-grid wp-dialyra-business-grid--dialog">
					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-new-business-name"><?php esc_html_e( 'Business name', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-new-business-name" name="dialyra_new_business_name" type="text" value="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" required>
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-new-business-email"><?php esc_html_e( 'Business email', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-new-business-email" name="dialyra_new_business_email" type="email" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-new-business-phone"><?php esc_html_e( 'Phone', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-new-business-phone" name="dialyra_new_business_phone" type="tel" value="<?php echo esc_attr( $wp_dialyra_connected_business['phone'] ); ?>">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-new-business-timezone"><?php esc_html_e( 'Timezone', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-new-business-timezone" name="dialyra_new_business_timezone" type="text" value="<?php echo esc_attr( $wp_dialyra_default_timezone ); ?>">
					</div>

					<div class="wp-dialyra-settings-row">
						<label for="wp-dialyra-new-business-country"><?php esc_html_e( 'Country', 'wp-dialyra' ); ?></label>
						<input id="wp-dialyra-new-business-country" name="dialyra_new_business_country" type="text" value="<?php echo esc_attr( $wp_dialyra_default_country ); ?>">
					</div>
				</div>

				<p class="wp-dialyra-settings-help"><?php esc_html_e( 'Slug is generated automatically from the business name. The new business will be selected after creation.', 'wp-dialyra' ); ?></p>

				<div class="wp-dialyra-agent-panel__footer">
					<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-dialog-close><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></button>
					<button class="wp-dialyra-button wp-dialyra-button--primary wp-dialyra-business-submit-button" type="submit">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'Create business', 'wp-dialyra' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
</section>
