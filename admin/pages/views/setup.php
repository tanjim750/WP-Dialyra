<?php

/**
 * Setup page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$wp_dialyra_user_info = class_exists( 'Dialyra_Auth_Manager' ) ? Dialyra_Auth_Manager::get_user_info() : array();
$wp_dialyra_user_info = is_array( $wp_dialyra_user_info ) ? $wp_dialyra_user_info : array();
$wp_dialyra_user_name = ! empty( $wp_dialyra_user_info['full_name'] ) ? $wp_dialyra_user_info['full_name'] : __( 'Dialyra user', 'wp-dialyra' );
$wp_dialyra_user_email = ! empty( $wp_dialyra_user_info['email'] ) ? $wp_dialyra_user_info['email'] : __( 'Connected account', 'wp-dialyra' );
$wp_dialyra_store_name = get_bloginfo( 'name' );
$wp_dialyra_store_email = get_option( 'admin_email' );
$wp_dialyra_store_country = '';
$wp_dialyra_setup_defaults = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_setup_defaults() : array();
$wp_dialyra_order_statuses = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_default_order_statuses() : array();

if ( function_exists( 'WC' ) && WC() && isset( WC()->countries ) ) {
	$wp_dialyra_country_code = WC()->countries->get_base_country();
	$wp_dialyra_countries = WC()->countries->get_countries();
	$wp_dialyra_store_country = isset( $wp_dialyra_countries[ $wp_dialyra_country_code ] ) ? $wp_dialyra_countries[ $wp_dialyra_country_code ] : '';
} elseif ( get_option( 'woocommerce_default_country' ) ) {
	$wp_dialyra_country_parts = explode( ':', get_option( 'woocommerce_default_country' ) );
	$wp_dialyra_store_country = ! empty( $wp_dialyra_country_parts[0] ) ? sanitize_text_field( $wp_dialyra_country_parts[0] ) : '';
}

if ( function_exists( 'wc_get_order_statuses' ) ) {
	$wp_dialyra_order_statuses = array();

	foreach ( wc_get_order_statuses() as $wp_dialyra_wc_status_key => $wp_dialyra_wc_status_label ) {
		$wp_dialyra_order_statuses[ preg_replace( '/^wc-/', '', $wp_dialyra_wc_status_key ) ] = $wp_dialyra_wc_status_label;
	}

	$wp_dialyra_order_statuses['no_change'] = __( 'Keep current status', 'wp-dialyra' );
}

$wp_dialyra_retry_defaults = isset( $wp_dialyra_setup_defaults['retry_policy'] ) ? $wp_dialyra_setup_defaults['retry_policy'] : array();
$wp_dialyra_business_hours_defaults = isset( $wp_dialyra_setup_defaults['business_hours'] ) ? $wp_dialyra_setup_defaults['business_hours'] : array();
$wp_dialyra_call_trigger_defaults = isset( $wp_dialyra_setup_defaults['call_trigger'] ) ? $wp_dialyra_setup_defaults['call_trigger'] : array();
$wp_dialyra_status_mapping_defaults = isset( $wp_dialyra_setup_defaults['order_status_map'] ) ? $wp_dialyra_setup_defaults['order_status_map'] : array();
$wp_dialyra_capacity_defaults = isset( $wp_dialyra_setup_defaults['call_capacity'] ) ? $wp_dialyra_setup_defaults['call_capacity'] : array();
$wp_dialyra_timezone = ! empty( $wp_dialyra_business_hours_defaults['timezone'] ) ? $wp_dialyra_business_hours_defaults['timezone'] : wp_timezone_string();
$wp_dialyra_business_days = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_business_hour_days() : array();
$wp_dialyra_selected_days = ! empty( $wp_dialyra_business_hours_defaults['days'] ) ? $wp_dialyra_business_hours_defaults['days'] : array( 'all' );
$wp_dialyra_plugin = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
$wp_dialyra_api_endpoints = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_api_endpoints() : null;
$wp_dialyra_business_manager = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_business_manager() : null;
$wp_dialyra_flow_manager = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_flow_manager() : null;

if ( $wp_dialyra_business_manager ) {
	$wp_dialyra_setup_defaults = $wp_dialyra_business_manager->get_setup_settings();
	$wp_dialyra_retry_defaults = isset( $wp_dialyra_setup_defaults['retry_policy'] ) ? $wp_dialyra_setup_defaults['retry_policy'] : array();
	$wp_dialyra_business_hours_defaults = isset( $wp_dialyra_setup_defaults['business_hours'] ) ? $wp_dialyra_setup_defaults['business_hours'] : array();
	$wp_dialyra_call_trigger_defaults = isset( $wp_dialyra_setup_defaults['call_trigger'] ) ? $wp_dialyra_setup_defaults['call_trigger'] : array();
	$wp_dialyra_status_mapping_defaults = isset( $wp_dialyra_setup_defaults['order_status_map'] ) ? $wp_dialyra_setup_defaults['order_status_map'] : array();
	$wp_dialyra_capacity_defaults = isset( $wp_dialyra_setup_defaults['call_capacity'] ) ? $wp_dialyra_setup_defaults['call_capacity'] : array();
	$wp_dialyra_timezone = ! empty( $wp_dialyra_business_hours_defaults['timezone'] ) ? $wp_dialyra_business_hours_defaults['timezone'] : wp_timezone_string();
	$wp_dialyra_selected_days = ! empty( $wp_dialyra_business_hours_defaults['days'] ) ? $wp_dialyra_business_hours_defaults['days'] : array( 'all' );
}

$wp_dialyra_trigger_mode = ! empty( $wp_dialyra_call_trigger_defaults['mode'] ) ? sanitize_key( $wp_dialyra_call_trigger_defaults['mode'] ) : 'instant';
$wp_dialyra_trigger_status = ! empty( $wp_dialyra_call_trigger_defaults['order_status'] ) ? sanitize_key( $wp_dialyra_call_trigger_defaults['order_status'] ) : 'processing';
$wp_dialyra_default_flow_id = $wp_dialyra_flow_manager ? $wp_dialyra_flow_manager->get_default_flow_id() : absint( isset( $wp_dialyra_setup_defaults['default_flow_id'] ) ? $wp_dialyra_setup_defaults['default_flow_id'] : 0 );

$error_message = null;
$success_message = null;
$wp_dialyra_setup_businesses = array();
$wp_dialyra_setup_flows = array();
$wp_dialyra_account_fetch_error = null;
$wp_dialyra_business_fetch_error = null;
$wp_dialyra_flow_fetch_error = null;
$wp_dialyra_post_connected_business_id = 0;
$wp_dialyra_site_token_data = array();

$wp_dialyra_extract_items = static function ( $response, $container_keys = array() ) {
	if ( ! $response || ! is_object( $response ) || ! method_exists( $response, 'is_successful' ) || ! $response->is_successful() || ! method_exists( $response, 'get_data' ) ) {
		return array();
	}

	$data = $response->get_data();
	$data = is_array( $data ) ? $data : array();

	if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
		$data = $data['data'];
	}

	if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
		return $data['items'];
	}

	foreach ( $container_keys as $container_key ) {
		if ( isset( $data[ $container_key ] ) && is_array( $data[ $container_key ] ) ) {
			return $data[ $container_key ];
		}
	}

	if ( isset( $data[0] ) && is_array( $data[0] ) ) {
		return $data;
	}

	return array();
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

$wp_dialyra_normalize_business = static function ( $business ) {
	$business = is_array( $business ) ? $business : array();

	return array(
		'id'      => isset( $business['id'] ) ? absint( $business['id'] ) : 0,
		'name'    => ! empty( $business['name'] ) ? sanitize_text_field( $business['name'] ) : __( 'Untitled business', 'wp-dialyra' ),
		'email'   => ! empty( $business['email'] ) ? sanitize_email( $business['email'] ) : '',
		'phone'   => ! empty( $business['phone'] ) ? sanitize_text_field( $business['phone'] ) : '',
		'status'  => ! empty( $business['status'] ) ? sanitize_key( $business['status'] ) : 'active',
	);
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

if ( $wp_dialyra_api_endpoints && class_exists( 'Dialyra_Auth_Manager' ) && Dialyra_Auth_Manager::is_logged_in() ) {
	$wp_dialyra_account_response = $wp_dialyra_api_endpoints->get_me();

	if ( $wp_dialyra_account_response && $wp_dialyra_account_response->is_successful() ) {
		$wp_dialyra_account_data = $wp_dialyra_account_response->get_data();
		$wp_dialyra_account_data = is_array( $wp_dialyra_account_data ) ? $wp_dialyra_account_data : array();

		if ( isset( $wp_dialyra_account_data['data'] ) && is_array( $wp_dialyra_account_data['data'] ) ) {
			$wp_dialyra_account_data = $wp_dialyra_account_data['data'];
		}

		if ( ! empty( $wp_dialyra_account_data['user'] ) && is_array( $wp_dialyra_account_data['user'] ) ) {
			Dialyra_Auth_Manager::save_user_info( $wp_dialyra_account_data['user'] );
			$wp_dialyra_user_info = $wp_dialyra_account_data['user'];
			$wp_dialyra_user_name = ! empty( $wp_dialyra_user_info['full_name'] ) ? $wp_dialyra_user_info['full_name'] : __( 'Dialyra user', 'wp-dialyra' );
			$wp_dialyra_user_email = ! empty( $wp_dialyra_user_info['email'] ) ? $wp_dialyra_user_info['email'] : __( 'Connected account', 'wp-dialyra' );
		}

		if ( ! empty( $wp_dialyra_account_data['business'] ) && is_array( $wp_dialyra_account_data['business'] ) && $wp_dialyra_business_manager ) {
			$wp_dialyra_current_business_id = absint( Dialyra_Auth_Manager::get_business_id() );
			$wp_dialyra_account_business_id = isset( $wp_dialyra_account_data['business']['id'] ) ? absint( $wp_dialyra_account_data['business']['id'] ) : 0;

			if ( ! $wp_dialyra_current_business_id || $wp_dialyra_current_business_id === $wp_dialyra_account_business_id ) {
				$wp_dialyra_business_manager->save_connected_business_data( $wp_dialyra_account_data['business'] );
			}
		}
	} elseif ( $wp_dialyra_account_response && ( 401 === $wp_dialyra_account_response->get_status_code() || 'unauthenticated' === $wp_dialyra_account_response->get_error_type() ) ) {
		Dialyra_Auth_Manager::clear_authentication();
		wp_safe_redirect( Dialyra_Auth_Manager::get_login_url() );
		exit;
	} elseif ( $wp_dialyra_account_response ) {
		$wp_dialyra_account_fetch_error = $wp_dialyra_account_response->get_message();
	}
} elseif ( class_exists( 'Dialyra_Auth_Manager' ) && ! Dialyra_Auth_Manager::is_logged_in() ) {
	wp_safe_redirect( Dialyra_Auth_Manager::get_login_url() );
	exit;
}

if ( 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) && isset( $_POST['wp_dialyra_setup_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_dialyra_setup_nonce'] ), 'wp-dialyra-setup' ) ) {
	$setup_action = isset( $_POST['dialyra_setup_action'] ) ? sanitize_key( wp_unslash( $_POST['dialyra_setup_action'] ) ) : 'finish_setup';
	$business_choice = isset( $_POST['dialyra_business_choice'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_business_choice'] ) ) : '';
	$create_new_business = 'create_business' === $setup_action || 'new' === $business_choice;
	$selected_business_id = isset( $_POST['dialyra_setup_business_id'] ) ? absint( wp_unslash( $_POST['dialyra_setup_business_id'] ) ) : 0;
	$selected_flow_id = isset( $_POST['dialyra_setup_default_flow'] ) ? absint( wp_unslash( $_POST['dialyra_setup_default_flow'] ) ) : 0;
	$connected_business_response = null;

	if ( ! $create_new_business && $business_choice && is_numeric( $business_choice ) ) {
		$selected_business_id = absint( $business_choice );
	}

	if ( $create_new_business ) {
		$new_business_name    = isset( $_POST['dialyra_new_business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_new_business_name'] ) ) : '';
		$new_business_email   = isset( $_POST['dialyra_new_business_email'] ) ? sanitize_email( wp_unslash( $_POST['dialyra_new_business_email'] ) ) : '';
		$new_business_phone   = isset( $_POST['dialyra_new_business_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_new_business_phone'] ) ) : '';
		$new_business_timezone = isset( $_POST['dialyra_new_business_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_new_business_timezone'] ) ) : '';

		if ( empty( $new_business_name ) || empty( $new_business_email ) ) {
			$error_message = esc_html__( 'Business name and email are required.', 'wp-dialyra' );
		} else {
			$business_data = array(
				'name'     => $new_business_name,
				'slug'     => sanitize_title( $new_business_name ),
				'email'    => $new_business_email,
				'phone'    => $new_business_phone,
				'timezone' => $new_business_timezone,
			);

			if ( ! empty( $wp_dialyra_store_country ) ) {
				$business_data['country'] = $wp_dialyra_store_country;
			}
			$connected_business_response = $wp_dialyra_business_manager ? $wp_dialyra_business_manager->create_and_connect_business( $business_data ) : false;

			if ( ! $connected_business_response || ! $connected_business_response->is_successful() ) {
				$error_message = $connected_business_response ? $connected_business_response->get_message() : esc_html__( 'Business service is not available.', 'wp-dialyra' );
			} else {
				$connected_business_data = $wp_dialyra_extract_response_data( $connected_business_response );
				$wp_dialyra_post_connected_business_id = ! empty( $connected_business_data['id'] ) ? absint( $connected_business_data['id'] ) : 0;

				if ( 'create_business' === $setup_action ) {
					$success_message = esc_html__( 'Business created and selected. Continue setup by choosing a default flow.', 'wp-dialyra' );
				}
			}
		}
	} elseif ( ! empty( $selected_business_id ) ) {
		$connected_business_response = $wp_dialyra_business_manager ? $wp_dialyra_business_manager->connect_business( $selected_business_id ) : false;

		if ( ! $connected_business_response || ! $connected_business_response->is_successful() ) {
			$error_message = $connected_business_response ? $connected_business_response->get_message() : esc_html__( 'Business service is not available.', 'wp-dialyra' );
		} else {
			$wp_dialyra_post_connected_business_id = $selected_business_id;
		}
	} else {
		$error_message = esc_html__( 'Please select or create a business to continue.', 'wp-dialyra' );
	}

	if ( empty( $error_message ) ) {
		$wp_dialyra_connected_business_id = class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
		$wp_dialyra_token_response = $wp_dialyra_business_manager ? $wp_dialyra_business_manager->ensure_site_access_token( $wp_dialyra_connected_business_id ) : false;

		if ( false === $wp_dialyra_token_response ) {
			$error_message = esc_html__( 'Access token could not be created because no business is connected.', 'wp-dialyra' );
		} elseif ( is_object( $wp_dialyra_token_response ) && method_exists( $wp_dialyra_token_response, 'is_successful' ) && ! $wp_dialyra_token_response->is_successful() ) {
			$error_message = $wp_dialyra_token_response->get_message();
		}
	}

	if ( empty( $error_message ) ) {
		$setup_settings = array(
			'business_id'      => class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0,
			'default_flow_id'  => $selected_flow_id,
			'call_trigger'     => array(
				'mode'           => isset( $_POST['dialyra_setup_trigger'] ) ? sanitize_key( wp_unslash( $_POST['dialyra_setup_trigger'] ) ) : 'instant',
				'order_status'   => isset( $_POST['dialyra_setup_order_status'] ) ? sanitize_key( wp_unslash( $_POST['dialyra_setup_order_status'] ) ) : 'processing',
				'delay_minutes'  => isset( $_POST['dialyra_trigger_delay'] ) ? absint( wp_unslash( $_POST['dialyra_trigger_delay'] ) ) : 0,
			),
			'retry_policy'     => array(
				'max_attempts'                 => isset( $_POST['dialyra_retry_attempts'] ) ? absint( wp_unslash( $_POST['dialyra_retry_attempts'] ) ) : 2,
				'delay_minutes'                => isset( $_POST['dialyra_retry_delay'] ) ? absint( wp_unslash( $_POST['dialyra_retry_delay'] ) ) : 15,
				'only_during_business_hours'   => ! empty( $_POST['dialyra_retry_business_hours'] ),
			),
			'business_hours'   => array(
				'availability_mode' => isset( $_POST['dialyra_business_hours_mode'] ) ? sanitize_key( wp_unslash( $_POST['dialyra_business_hours_mode'] ) ) : 'always_active',
				'timezone'          => isset( $_POST['dialyra_business_hours_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_business_hours_timezone'] ) ) : $wp_dialyra_timezone,
				'days'              => isset( $_POST['dialyra_business_hours_days'] ) && is_array( $_POST['dialyra_business_hours_days'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['dialyra_business_hours_days'] ) ) : array( 'all' ),
				'open_time'         => isset( $_POST['dialyra_business_hours_open'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_business_hours_open'] ) ) : '09:00',
				'close_time'        => isset( $_POST['dialyra_business_hours_close'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_business_hours_close'] ) ) : '18:00',
			),
			'order_status_map' => array(
				'confirmed_status' => isset( $_POST['dialyra_confirmed_status'] ) ? sanitize_key( wp_unslash( $_POST['dialyra_confirmed_status'] ) ) : 'processing',
				'cancelled_status' => isset( $_POST['dialyra_cancelled_status'] ) ? sanitize_key( wp_unslash( $_POST['dialyra_cancelled_status'] ) ) : 'cancelled',
				'order_note'       => isset( $_POST['dialyra_status_mapping_note'] ) ? sanitize_text_field( wp_unslash( $_POST['dialyra_status_mapping_note'] ) ) : '',
			),
			'call_capacity'    => array(
				'max_concurrent_calls' => isset( $_POST['dialyra_max_concurrent_calls'] ) ? absint( wp_unslash( $_POST['dialyra_max_concurrent_calls'] ) ) : 1,
			),
		);

		if ( $wp_dialyra_business_manager ) {
			$wp_dialyra_business_manager->save_setup_settings( $setup_settings );
			$wp_dialyra_setup_defaults = $wp_dialyra_business_manager->get_setup_settings();
			$wp_dialyra_retry_defaults = isset( $wp_dialyra_setup_defaults['retry_policy'] ) ? $wp_dialyra_setup_defaults['retry_policy'] : array();
			$wp_dialyra_business_hours_defaults = isset( $wp_dialyra_setup_defaults['business_hours'] ) ? $wp_dialyra_setup_defaults['business_hours'] : array();
			$wp_dialyra_call_trigger_defaults = isset( $wp_dialyra_setup_defaults['call_trigger'] ) ? $wp_dialyra_setup_defaults['call_trigger'] : array();
			$wp_dialyra_status_mapping_defaults = isset( $wp_dialyra_setup_defaults['order_status_map'] ) ? $wp_dialyra_setup_defaults['order_status_map'] : array();
			$wp_dialyra_capacity_defaults = isset( $wp_dialyra_setup_defaults['call_capacity'] ) ? $wp_dialyra_setup_defaults['call_capacity'] : array();
			$wp_dialyra_timezone = ! empty( $wp_dialyra_business_hours_defaults['timezone'] ) ? $wp_dialyra_business_hours_defaults['timezone'] : wp_timezone_string();
			$wp_dialyra_selected_days = ! empty( $wp_dialyra_business_hours_defaults['days'] ) ? $wp_dialyra_business_hours_defaults['days'] : array( 'all' );
			$wp_dialyra_trigger_mode = ! empty( $wp_dialyra_call_trigger_defaults['mode'] ) ? sanitize_key( $wp_dialyra_call_trigger_defaults['mode'] ) : 'instant';
			$wp_dialyra_trigger_status = ! empty( $wp_dialyra_call_trigger_defaults['order_status'] ) ? sanitize_key( $wp_dialyra_call_trigger_defaults['order_status'] ) : 'processing';
			$wp_dialyra_default_flow_id = absint( isset( $wp_dialyra_setup_defaults['default_flow_id'] ) ? $wp_dialyra_setup_defaults['default_flow_id'] : 0 );
		}

		if ( 'create_business' === $setup_action ) {
			$success_message = $success_message ? $success_message : esc_html__( 'Business created and selected. Continue setup by choosing a default flow.', 'wp-dialyra' );
		} elseif ( $selected_flow_id && $wp_dialyra_flow_manager ) {
			$flow_response = $wp_dialyra_flow_manager->set_default_flow( $selected_flow_id );

			if ( $flow_response && $flow_response->is_successful() ) {
				wp_safe_redirect( admin_url( 'admin.php?page=wp-dialyra&p=dashboard' ) );
				exit;
			}

			$error_message = $flow_response ? $flow_response->get_message() : esc_html__( 'Flow service is not available.', 'wp-dialyra' );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=wp-dialyra&p=dashboard' ) );
			exit;
		}
	}
}

if ( $wp_dialyra_business_manager ) {
	$business_response = $wp_dialyra_business_manager->get_businesses();

	if ( $business_response && $business_response->is_successful() ) {
		$wp_dialyra_setup_businesses = array_filter( array_map( $wp_dialyra_normalize_business, $wp_dialyra_extract_items( $business_response, array( 'businesses' ) ) ), static function ( $business ) {
			return ! empty( $business['id'] );
		} );
	} elseif ( $business_response ) {
		$wp_dialyra_business_error_message = $business_response->get_message();

		if ( false === stripos( $wp_dialyra_business_error_message, 'membership' ) ) {
			$wp_dialyra_business_fetch_error = $wp_dialyra_business_error_message;
		}
	}
}

$wp_dialyra_selected_business_id = $wp_dialyra_post_connected_business_id ? $wp_dialyra_post_connected_business_id : ( class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0 );

if ( ! $wp_dialyra_selected_business_id && ! empty( $wp_dialyra_setup_businesses ) ) {
	$first_business = reset( $wp_dialyra_setup_businesses );
	$wp_dialyra_selected_business_id = ! empty( $first_business['id'] ) ? absint( $first_business['id'] ) : 0;
}

if ( $wp_dialyra_selected_business_id && $wp_dialyra_business_manager ) {
	$wp_dialyra_connected_business_data = $wp_dialyra_business_manager->get_connected_business_data();
	$wp_dialyra_site_token_data = $wp_dialyra_business_manager->get_site_access_token_data();
	$wp_dialyra_has_connected_business = false;

	foreach ( $wp_dialyra_setup_businesses as $wp_dialyra_setup_business ) {
		if ( ! empty( $wp_dialyra_setup_business['id'] ) && absint( $wp_dialyra_setup_business['id'] ) === $wp_dialyra_selected_business_id ) {
			$wp_dialyra_has_connected_business = true;
			break;
		}
	}

	if ( ! $wp_dialyra_has_connected_business && ! empty( $wp_dialyra_connected_business_data['id'] ) ) {
		array_unshift( $wp_dialyra_setup_businesses, $wp_dialyra_normalize_business( $wp_dialyra_connected_business_data ) );
	}
}

if ( $wp_dialyra_flow_manager && $wp_dialyra_selected_business_id ) {
	$flow_response = $wp_dialyra_flow_manager->get_flows( array( 'business_id' => $wp_dialyra_selected_business_id ) );

	if ( $flow_response && $flow_response->is_successful() ) {
		$wp_dialyra_setup_flows = array_filter( array_map( $wp_dialyra_normalize_flow, $wp_dialyra_extract_items( $flow_response, array( 'flows' ) ) ), static function ( $flow ) {
			return ! empty( $flow['id'] );
		} );
	} elseif ( $flow_response ) {
		$wp_dialyra_flow_fetch_error = $flow_response->get_message();
	}
}

?>

<section class="wp-dialyra-setup">
	<div class="wp-dialyra-setup__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Quick Setup', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Connect Dialyra to this WooCommerce store.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Choose a business and default call flow. Dialyra will use recommended defaults for retry, webhook, and business hour settings.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-setup-account">
			<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
			<div>
				<strong><?php echo esc_html( $wp_dialyra_user_name ); ?></strong>
				<small><?php echo esc_html( $wp_dialyra_user_email ); ?></small>
			</div>
		</div>
	</div>

	<div class="wp-dialyra-setup-steps" aria-label="<?php esc_attr_e( 'Setup progress', 'wp-dialyra' ); ?>">
		<a class="wp-dialyra-setup-back" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=dashboard' ) ); ?>"><?php esc_html_e( '<< Dashboard', 'wp-dialyra' ); ?></a>
		<div class="wp-dialyra-setup-steps__links">
			<a class="wp-dialyra-setup-step wp-dialyra-setup-step--active" href="#wp-dialyra-setup-business-section"><?php esc_html_e( 'Business', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-setup-step" href="#wp-dialyra-setup-token-section"><?php esc_html_e( 'Access Token', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-setup-step" href="#wp-dialyra-setup-flow-section"><?php esc_html_e( 'Default Flow', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-setup-step" href="#wp-dialyra-setup-trigger-section"><?php esc_html_e( 'Call Trigger', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<form class="wp-dialyra-setup__grid" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=setup' ) ); ?>">
		<?php wp_nonce_field( 'wp-dialyra-setup', 'wp_dialyra_setup_nonce' ); ?>

		<?php if ( ! empty( $error_message ) ) : ?>
			<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--error">
				<span class="dashicons dashicons-warning" aria-hidden="true"></span>
				<p><?php echo esc_html( $error_message ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $success_message ) ) : ?>
			<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--success">
				<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
				<p><?php echo esc_html( $success_message ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $wp_dialyra_business_fetch_error ) ) : ?>
			<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--warning">
				<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
				<p><?php echo esc_html( $wp_dialyra_business_fetch_error ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $wp_dialyra_flow_fetch_error ) ) : ?>
			<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--warning">
				<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
				<p><?php echo esc_html( $wp_dialyra_flow_fetch_error ); ?></p>
			</div>
		<?php endif; ?>

		<section id="wp-dialyra-setup-business-section" class="wp-dialyra-setup-card wp-dialyra-setup-card--wide">
			<div class="wp-dialyra-setup-card__head">
				<div class="wp-dialyra-setup-card__title">
					<span aria-hidden="true">01</span>
					<div>
						<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Business', 'wp-dialyra' ); ?></p>
						<h3><?php esc_html_e( 'Select or create your Dialyra business', 'wp-dialyra' ); ?></h3>
						<p><?php esc_html_e( 'This tells Dialyra which business account should own calls, flows, agents, and audio for this store.', 'wp-dialyra' ); ?></p>
					</div>
				</div>
			</div>

			<div class="wp-dialyra-business-choice">
				<div class="wp-dialyra-business-choice__list">
					<div class="wp-dialyra-setup-subsection">
						<span class="dashicons dashicons-building" aria-hidden="true"></span>
						<div>
							<h4><?php esc_html_e( 'Use an existing business', 'wp-dialyra' ); ?></h4>
							<p><?php esc_html_e( 'Select a business already available in your Dialyra account. The business ID stays hidden and is stored automatically.', 'wp-dialyra' ); ?></p>
						</div>
					</div>

					<?php if ( ! empty( $wp_dialyra_setup_businesses ) && is_array( $wp_dialyra_setup_businesses ) ) : ?>
						<div class="wp-dialyra-settings-row">
							<label for="wp-dialyra-setup-business"><?php esc_html_e( 'Existing business', 'wp-dialyra' ); ?></label>
							<select id="wp-dialyra-setup-business" name="dialyra_setup_business_id">
								<?php foreach ( $wp_dialyra_setup_businesses as $wp_dialyra_business ) : ?>
									<option value="<?php echo esc_attr( $wp_dialyra_business['id'] ); ?>" <?php selected( $wp_dialyra_selected_business_id, $wp_dialyra_business['id'] ); ?>>
										<?php echo esc_html( $wp_dialyra_business['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<small><?php esc_html_e( 'Fetched from your Dialyra account.', 'wp-dialyra' ); ?></small>
						</div>

						<div class="wp-dialyra-business-cards">
							<?php foreach ( $wp_dialyra_setup_businesses as $wp_dialyra_business ) : ?>
								<label class="wp-dialyra-business-card">
									<input type="radio" name="dialyra_business_choice" value="<?php echo esc_attr( $wp_dialyra_business['id'] ); ?>" <?php checked( $wp_dialyra_selected_business_id, $wp_dialyra_business['id'] ); ?>>
									<span>
										<strong><?php echo esc_html( $wp_dialyra_business['name'] ); ?></strong>
										<small>
											<?php
											echo esc_html(
												trim(
													implode(
														' · ',
														array_filter(
															array(
																$wp_dialyra_business['email'],
																$wp_dialyra_business['phone'],
															)
														)
													)
												)
											);
											?>
										</small>
									</span>
									<em class="wp-dialyra-result wp-dialyra-result--success"><?php echo esc_html( ucfirst( $wp_dialyra_business['status'] ) ); ?></em>
								</label>
							<?php endforeach; ?>
						</div>

						<label class="wp-dialyra-business-card wp-dialyra-business-card--new">
							<input type="radio" name="dialyra_business_choice" value="new">
							<span>
								<strong><?php esc_html_e( 'Create new business', 'wp-dialyra' ); ?></strong>
								<small><?php esc_html_e( 'Use this store’s details. You can edit the profile later from Settings.', 'wp-dialyra' ); ?></small>
							</span>
						</label>
					<?php else : ?>
						<input type="hidden" name="dialyra_business_choice" value="new">
						<div class="wp-dialyra-empty-card wp-dialyra-empty-card--business">
							<span class="dashicons dashicons-store" aria-hidden="true"></span>
							<div>
								<strong><?php esc_html_e( 'No business found', 'wp-dialyra' ); ?></strong>
								<p><?php esc_html_e( 'This Dialyra account is valid, but no business is available yet. Use the Create business section to create one from this store.', 'wp-dialyra' ); ?></p>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<div class="wp-dialyra-create-business">
					<div class="wp-dialyra-create-business__details">
						<div class="wp-dialyra-create-business__head">
							<span class="dashicons dashicons-store" aria-hidden="true"></span>
							<div>
								<h4><?php esc_html_e( 'Business profile details', 'wp-dialyra' ); ?></h4>
								<p><?php esc_html_e( 'We prefill this from WordPress. You can update the business profile later from Settings.', 'wp-dialyra' ); ?></p>
							</div>
						</div>

						<div class="wp-dialyra-setup-fields">
							<div class="wp-dialyra-settings-row">
								<label for="wp-dialyra-new-business-name"><?php esc_html_e( 'Business name', 'wp-dialyra' ); ?></label>
								<input id="wp-dialyra-new-business-name" name="dialyra_new_business_name" type="text" value="<?php echo esc_attr( $wp_dialyra_store_name ); ?>">
							</div>
							<div class="wp-dialyra-settings-row">
								<label for="wp-dialyra-new-business-email"><?php esc_html_e( 'Business email', 'wp-dialyra' ); ?></label>
								<input id="wp-dialyra-new-business-email" name="dialyra_new_business_email" type="email" value="<?php echo esc_attr( $wp_dialyra_store_email ); ?>">
							</div>
							<div class="wp-dialyra-settings-row">
								<label for="wp-dialyra-new-business-phone"><?php esc_html_e( 'Phone', 'wp-dialyra' ); ?></label>
								<input id="wp-dialyra-new-business-phone" name="dialyra_new_business_phone" type="tel" placeholder="+8801XXXXXXXXX">
							</div>
							<div class="wp-dialyra-settings-row">
								<label for="wp-dialyra-new-business-timezone"><?php esc_html_e( 'Timezone', 'wp-dialyra' ); ?></label>
								<input id="wp-dialyra-new-business-timezone" name="dialyra_new_business_timezone" type="text" value="<?php echo esc_attr( $wp_dialyra_timezone ); ?>">
							</div>
						</div>

						<div class="wp-dialyra-create-business__actions">
							<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit" name="dialyra_setup_action" value="create_business"><?php esc_html_e( 'Create business', 'wp-dialyra' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section id="wp-dialyra-setup-token-section" class="wp-dialyra-setup-card">
			<div class="wp-dialyra-setup-card__head">
				<span aria-hidden="true">02</span>
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Access Token', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Site token will be created automatically', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'After the business is selected, Dialyra creates the access token used by this WordPress store.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-token-preview">
				<span class="dashicons dashicons-lock" aria-hidden="true"></span>
				<div>
					<?php if ( ! empty( $wp_dialyra_site_token_data['token'] ) && ! empty( $wp_dialyra_site_token_data['business_id'] ) && absint( $wp_dialyra_site_token_data['business_id'] ) === $wp_dialyra_selected_business_id ) : ?>
						<strong><?php esc_html_e( 'Access token is ready', 'wp-dialyra' ); ?></strong>
						<small>
							<?php
							echo esc_html(
								! empty( $wp_dialyra_site_token_data['token_prefix'] )
									? sprintf( __( 'Connected to this business with token prefix %s.', 'wp-dialyra' ), $wp_dialyra_site_token_data['token_prefix'] )
									: __( 'Connected to this business and stored securely.', 'wp-dialyra' )
							);
							?>
						</small>
					<?php else : ?>
						<strong><?php esc_html_e( 'No manual token entry needed', 'wp-dialyra' ); ?></strong>
						<small><?php esc_html_e( 'We will generate and save it in the background for the selected business.', 'wp-dialyra' ); ?></small>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<section id="wp-dialyra-setup-flow-section" class="wp-dialyra-setup-card">
			<div class="wp-dialyra-setup-card__head">
				<span aria-hidden="true">03</span>
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Default Flow', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Choose the flow every order can use', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'A default flow is required so calls know what menu to play.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<?php if ( ! empty( $wp_dialyra_setup_flows ) && is_array( $wp_dialyra_setup_flows ) ) : ?>
				<div class="wp-dialyra-settings-row">
					<label for="wp-dialyra-setup-flow"><?php esc_html_e( 'Default flow', 'wp-dialyra' ); ?></label>
					<select id="wp-dialyra-setup-flow" name="dialyra_setup_default_flow">
						<?php foreach ( $wp_dialyra_setup_flows as $wp_dialyra_flow ) : ?>
							<option value="<?php echo esc_attr( $wp_dialyra_flow['id'] ); ?>" <?php selected( $wp_dialyra_default_flow_id, $wp_dialyra_flow['id'] ); ?>>
								<?php echo esc_html( $wp_dialyra_flow['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<small><?php esc_html_e( 'Flows are fetched from the selected Dialyra business.', 'wp-dialyra' ); ?></small>
				</div>

				<div class="wp-dialyra-flow-mini-list">
					<?php foreach ( $wp_dialyra_setup_flows as $wp_dialyra_flow ) : ?>
						<article>
							<div>
								<strong><?php echo esc_html( $wp_dialyra_flow['name'] ); ?></strong>
								<small><?php echo esc_html( $wp_dialyra_flow['summary'] ); ?></small>
							</div>
							<em class="wp-dialyra-result wp-dialyra-result--muted"><?php echo esc_html( $wp_dialyra_flow['status'] ); ?></em>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="wp-dialyra-empty-card wp-dialyra-empty-card--flow">
					<span class="dashicons dashicons-warning" aria-hidden="true"></span>
					<div>
						<strong><?php esc_html_e( 'No flow found', 'wp-dialyra' ); ?></strong>
						<p><?php esc_html_e( 'Create a flow first, then return here to set it as default.', 'wp-dialyra' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-builder' ) ); ?>"><?php esc_html_e( 'Create Flow', 'wp-dialyra' ); ?></a>
					</div>
				</div>
			<?php endif; ?>
		</section>

		<section id="wp-dialyra-setup-trigger-section" class="wp-dialyra-setup-card wp-dialyra-setup-card--wide" data-dialyra-dynamic-group>
			<div class="wp-dialyra-setup-card__head">
				<span aria-hidden="true">04</span>
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Call Trigger', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Decide when Dialyra starts calling customers', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Keep it simple now. Delay, retry, webhook, and status mapping can stay on recommended defaults.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-trigger-options">
				<label>
					<input type="radio" name="dialyra_setup_trigger" value="instant" <?php checked( $wp_dialyra_trigger_mode, 'instant' ); ?> data-dialyra-dynamic-select>
					<span>
						<strong><?php esc_html_e( 'Call instantly after order', 'wp-dialyra' ); ?></strong>
						<small><?php esc_html_e( 'Best for COD confirmation and fast fulfillment workflows.', 'wp-dialyra' ); ?></small>
					</span>
				</label>
				<label>
					<input type="radio" name="dialyra_setup_trigger" value="status" <?php checked( $wp_dialyra_trigger_mode, 'status' ); ?> data-dialyra-dynamic-select>
					<span>
						<strong><?php esc_html_e( 'Call on selected order status', 'wp-dialyra' ); ?></strong>
						<small><?php esc_html_e( 'Use this when calls should wait until WooCommerce reaches a specific status.', 'wp-dialyra' ); ?></small>
					</span>
				</label>
			</div>

			<div class="wp-dialyra-settings-row wp-dialyra-setup-status-select" data-dialyra-trigger-status-field data-dialyra-show-for="status" hidden>
				<label for="wp-dialyra-setup-order-status"><?php esc_html_e( 'Order status', 'wp-dialyra' ); ?></label>
				<select id="wp-dialyra-setup-order-status" name="dialyra_setup_order_status">
					<?php foreach ( $wp_dialyra_order_statuses as $wp_dialyra_status_key => $wp_dialyra_status_label ) : ?>
						<?php if ( 'no_change' === $wp_dialyra_status_key ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<option value="<?php echo esc_attr( $wp_dialyra_status_key ); ?>" <?php selected( $wp_dialyra_trigger_status, $wp_dialyra_status_key ); ?>><?php echo esc_html( $wp_dialyra_status_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</section>

		<details class="wp-dialyra-setup-advanced">
			<summary>
				<span><?php esc_html_e( 'Advanced options', 'wp-dialyra' ); ?></span>
				<small><?php esc_html_e( 'Recommended defaults are already prepared.', 'wp-dialyra' ); ?></small>
			</summary>

			<div class="wp-dialyra-advanced-grid">
				<section class="wp-dialyra-advanced-card">
					<div class="wp-dialyra-advanced-card__head">
						<span class="dashicons dashicons-backup" aria-hidden="true"></span>
						<div>
							<strong><?php esc_html_e( 'Retry policy', 'wp-dialyra' ); ?></strong>
							<small><?php esc_html_e( 'Control how Dialyra retries calls that do not connect.', 'wp-dialyra' ); ?></small>
						</div>
					</div>
					<div class="wp-dialyra-advanced-fields">
						<div class="wp-dialyra-settings-row">
							<label for="wp-dialyra-setup-retry-attempts"><?php esc_html_e( 'Max retry attempts', 'wp-dialyra' ); ?></label>
							<input id="wp-dialyra-setup-retry-attempts" name="dialyra_retry_attempts" type="number" min="0" value="<?php echo esc_attr( isset( $wp_dialyra_retry_defaults['max_attempts'] ) ? $wp_dialyra_retry_defaults['max_attempts'] : 2 ); ?>">
						</div>
						<div class="wp-dialyra-settings-row">
							<label for="wp-dialyra-setup-retry-delay"><?php esc_html_e( 'Retry delay minutes', 'wp-dialyra' ); ?></label>
							<input id="wp-dialyra-setup-retry-delay" name="dialyra_retry_delay" type="number" min="1" value="<?php echo esc_attr( isset( $wp_dialyra_retry_defaults['delay_minutes'] ) ? $wp_dialyra_retry_defaults['delay_minutes'] : 15 ); ?>">
						</div>
						<label class="wp-dialyra-setup-check">
							<input type="checkbox" name="dialyra_retry_business_hours" <?php checked( ! empty( $wp_dialyra_retry_defaults['only_during_business_hours'] ) ); ?>>
							<span><?php esc_html_e( 'Retry only during business hours', 'wp-dialyra' ); ?></span>
						</label>
					</div>
				</section>

				<section class="wp-dialyra-advanced-card" data-dialyra-dynamic-group>
						<div class="wp-dialyra-advanced-card__head">
							<span class="dashicons dashicons-clock" aria-hidden="true"></span>
							<div>
								<strong><?php esc_html_e( 'Business hours', 'wp-dialyra' ); ?></strong>
								<small><?php esc_html_e( 'Use always-on calling or keep calls inside working hours.', 'wp-dialyra' ); ?></small>
							</div>
						</div>
						<div class="wp-dialyra-advanced-fields">
							<div class="wp-dialyra-settings-row">
								<label for="wp-dialyra-setup-hours-mode"><?php esc_html_e( 'Availability mode', 'wp-dialyra' ); ?></label>
								<select id="wp-dialyra-setup-hours-mode" name="dialyra_business_hours_mode" data-dialyra-dynamic-select>
									<option value="always_active" <?php selected( isset( $wp_dialyra_business_hours_defaults['availability_mode'] ) ? $wp_dialyra_business_hours_defaults['availability_mode'] : '', 'always_active' ); ?>><?php esc_html_e( 'Always active', 'wp-dialyra' ); ?></option>
									<option value="scheduled" <?php selected( isset( $wp_dialyra_business_hours_defaults['availability_mode'] ) ? $wp_dialyra_business_hours_defaults['availability_mode'] : '', 'scheduled' ); ?>><?php esc_html_e( 'Scheduled hours', 'wp-dialyra' ); ?></option>
									<option value="paused" <?php selected( isset( $wp_dialyra_business_hours_defaults['availability_mode'] ) ? $wp_dialyra_business_hours_defaults['availability_mode'] : '', 'paused' ); ?>><?php esc_html_e( 'Paused until enabled', 'wp-dialyra' ); ?></option>
								</select>
							</div>
							<div class="wp-dialyra-settings-row" data-dialyra-show-for="scheduled" hidden>
								<label for="wp-dialyra-setup-hours-timezone"><?php esc_html_e( 'Timezone', 'wp-dialyra' ); ?></label>
								<input id="wp-dialyra-setup-hours-timezone" name="dialyra_business_hours_timezone" type="text" value="<?php echo esc_attr( $wp_dialyra_timezone ); ?>">
							</div>
							<div class="wp-dialyra-settings-row wp-dialyra-day-picker-row" data-dialyra-show-for="always_active scheduled">
								<span class="wp-dialyra-setup-label"><?php esc_html_e( 'Selected days', 'wp-dialyra' ); ?></span>
								<div class="wp-dialyra-day-picker" aria-label="<?php esc_attr_e( 'Select calling days', 'wp-dialyra' ); ?>">
									<?php foreach ( $wp_dialyra_business_days as $wp_dialyra_day_key => $wp_dialyra_day_label ) : ?>
										<label>
											<input type="checkbox" name="dialyra_business_hours_days[]" value="<?php echo esc_attr( $wp_dialyra_day_key ); ?>" <?php checked( in_array( $wp_dialyra_day_key, $wp_dialyra_selected_days, true ) ); ?>>
											<span><?php echo esc_html( $wp_dialyra_day_label ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
								<small><?php esc_html_e( 'Always active uses All by default. Scheduled hours can be limited to selected days.', 'wp-dialyra' ); ?></small>
							</div>
							<div class="wp-dialyra-settings-row" data-dialyra-show-for="scheduled" hidden>
								<label for="wp-dialyra-setup-hours-open"><?php esc_html_e( 'Open time', 'wp-dialyra' ); ?></label>
								<input id="wp-dialyra-setup-hours-open" name="dialyra_business_hours_open" type="time" value="<?php echo esc_attr( isset( $wp_dialyra_business_hours_defaults['open_time'] ) ? $wp_dialyra_business_hours_defaults['open_time'] : '09:00' ); ?>">
							</div>
							<div class="wp-dialyra-settings-row" data-dialyra-show-for="scheduled" hidden>
								<label for="wp-dialyra-setup-hours-close"><?php esc_html_e( 'Close time', 'wp-dialyra' ); ?></label>
								<input id="wp-dialyra-setup-hours-close" name="dialyra_business_hours_close" type="time" value="<?php echo esc_attr( isset( $wp_dialyra_business_hours_defaults['close_time'] ) ? $wp_dialyra_business_hours_defaults['close_time'] : '18:00' ); ?>">
							</div>
						</div>
					</section>

					<section class="wp-dialyra-advanced-card" data-dialyra-dynamic-group>
						<div class="wp-dialyra-advanced-card__head">
							<span class="dashicons dashicons-update" aria-hidden="true"></span>
							<div>
								<strong><?php esc_html_e( 'Order status mapping', 'wp-dialyra' ); ?></strong>
								<small><?php esc_html_e( 'Choose fallback WooCommerce status updates for common call results.', 'wp-dialyra' ); ?></small>
							</div>
						</div>
						<div class="wp-dialyra-advanced-fields">
							<div class="wp-dialyra-settings-row">
								<label for="wp-dialyra-setup-confirmed-status"><?php esc_html_e( 'Confirmed orders', 'wp-dialyra' ); ?></label>
								<select id="wp-dialyra-setup-confirmed-status" name="dialyra_confirmed_status" data-dialyra-dynamic-select>
									<option value="processing" <?php selected( isset( $wp_dialyra_status_mapping_defaults['confirmed_status'] ) ? $wp_dialyra_status_mapping_defaults['confirmed_status'] : '', 'processing' ); ?>><?php esc_html_e( 'Processing', 'wp-dialyra' ); ?></option>
									<option value="completed" <?php selected( isset( $wp_dialyra_status_mapping_defaults['confirmed_status'] ) ? $wp_dialyra_status_mapping_defaults['confirmed_status'] : '', 'completed' ); ?>><?php esc_html_e( 'Completed', 'wp-dialyra' ); ?></option>
									<option value="no_change" <?php selected( isset( $wp_dialyra_status_mapping_defaults['confirmed_status'] ) ? $wp_dialyra_status_mapping_defaults['confirmed_status'] : '', 'no_change' ); ?>><?php esc_html_e( 'Keep current status', 'wp-dialyra' ); ?></option>
								</select>
							</div>
							<div class="wp-dialyra-settings-row">
								<label for="wp-dialyra-setup-cancelled-status"><?php esc_html_e( 'Cancelled orders', 'wp-dialyra' ); ?></label>
								<select id="wp-dialyra-setup-cancelled-status" name="dialyra_cancelled_status" data-dialyra-dynamic-select>
									<option value="cancelled" <?php selected( isset( $wp_dialyra_status_mapping_defaults['cancelled_status'] ) ? $wp_dialyra_status_mapping_defaults['cancelled_status'] : '', 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'wp-dialyra' ); ?></option>
									<option value="on-hold" <?php selected( isset( $wp_dialyra_status_mapping_defaults['cancelled_status'] ) ? $wp_dialyra_status_mapping_defaults['cancelled_status'] : '', 'on-hold' ); ?>><?php esc_html_e( 'On hold', 'wp-dialyra' ); ?></option>
									<option value="no_change" <?php selected( isset( $wp_dialyra_status_mapping_defaults['cancelled_status'] ) ? $wp_dialyra_status_mapping_defaults['cancelled_status'] : '', 'no_change' ); ?>><?php esc_html_e( 'Keep current status', 'wp-dialyra' ); ?></option>
								</select>
							</div>
							<div class="wp-dialyra-settings-row" data-dialyra-show-for="processing completed cancelled on-hold">
								<label for="wp-dialyra-setup-status-note"><?php esc_html_e( 'Order note', 'wp-dialyra' ); ?></label>
								<input id="wp-dialyra-setup-status-note" name="dialyra_status_mapping_note" type="text" value="<?php echo esc_attr( isset( $wp_dialyra_status_mapping_defaults['order_note'] ) ? $wp_dialyra_status_mapping_defaults['order_note'] : __( 'Updated by Dialyra call result.', 'wp-dialyra' ) ); ?>">
							</div>
						</div>
					</section>

					<section class="wp-dialyra-advanced-card">
						<div class="wp-dialyra-advanced-card__head">
							<span class="dashicons dashicons-performance" aria-hidden="true"></span>
							<div>
								<strong><?php esc_html_e( 'Call capacity', 'wp-dialyra' ); ?></strong>
								<small><?php esc_html_e( 'Prevent too many calls from starting at the same time.', 'wp-dialyra' ); ?></small>
							</div>
						</div>
						<div class="wp-dialyra-advanced-fields">
							<div class="wp-dialyra-settings-row">
								<label for="wp-dialyra-setup-max-concurrent"><?php esc_html_e( 'Max concurrent calls', 'wp-dialyra' ); ?></label>
								<input id="wp-dialyra-setup-max-concurrent" name="dialyra_max_concurrent_calls" type="number" min="1" value="<?php echo esc_attr( isset( $wp_dialyra_capacity_defaults['max_concurrent_calls'] ) ? $wp_dialyra_capacity_defaults['max_concurrent_calls'] : 1 ); ?>">
							</div>
						</div>
					</section>

					<section class="wp-dialyra-advanced-card">
						<div class="wp-dialyra-advanced-card__head">
							<span class="dashicons dashicons-controls-pause" aria-hidden="true"></span>
							<div>
								<strong><?php esc_html_e( 'Delay trigger', 'wp-dialyra' ); ?></strong>
								<small><?php esc_html_e( 'Optional wait time before instant order calls begin.', 'wp-dialyra' ); ?></small>
							</div>
						</div>
						<div class="wp-dialyra-advanced-fields">
							<div class="wp-dialyra-settings-row">
								<label for="wp-dialyra-setup-trigger-delay"><?php esc_html_e( 'Delay minutes', 'wp-dialyra' ); ?></label>
								<input id="wp-dialyra-setup-trigger-delay" name="dialyra_trigger_delay" type="number" min="0" value="<?php echo esc_attr( isset( $wp_dialyra_call_trigger_defaults['delay_minutes'] ) ? $wp_dialyra_call_trigger_defaults['delay_minutes'] : 0 ); ?>">
							</div>
						</div>
					</section>
				</div>
			</details>

			<div class="wp-dialyra-setup-footer">
				<button class="wp-dialyra-button wp-dialyra-button--ghost" type="reset"><?php esc_html_e( 'Reset choices', 'wp-dialyra' ); ?></button>
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Finish Setup', 'wp-dialyra' ); ?></button>
			</div>
		</form>
	</section>
