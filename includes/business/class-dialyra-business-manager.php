<?php

/**
 * Dialyra Business Manager.
 *
 * Coordinates business selection, creation, local business state, and setup defaults.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/business
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Business_Manager {

	const BUSINESS_DATA_OPTION  = 'dialyra_business_data';
	const SETUP_SETTINGS_OPTION = 'dialyra_setup_settings';

	/**
	 * The API client object.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_API_Client
	 */
	private $api_client;

	/**
	 * The API endpoints object.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_API_Endpoints
	 */
	private $api_endpoints;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Client     $api_client     The API client object.
	 * @param    Dialyra_API_Endpoints    $api_endpoints    The API endpoints object.
	 */
	public function __construct( Dialyra_API_Client $api_client, Dialyra_API_Endpoints $api_endpoints ) {
		$this->api_client    = $api_client;
		$this->api_endpoints = $api_endpoints;
	}

	/**
	 * Get businesses available to the current Dialyra user.
	 *
	 * @since    1.0.0
	 * @param    array    $query_params    Optional query parameters.
	 * @return   Dialyra_API_Response
	 */
	public function get_businesses( $query_params = array() ) {
		return $this->api_endpoints->get_businesses( $query_params );
	}

	/**
	 * Create a business in Dialyra.
	 *
	 * @since    1.0.0
	 * @param    array    $business_data    Business data.
	 * @return   Dialyra_API_Response
	 */
	public function create_business( $business_data ) {
		return $this->api_endpoints->create_business( $this->prepare_business_payload( $business_data ) );
	}

	/**
	 * Create a business and connect it locally when successful.
	 *
	 * @since    1.0.0
	 * @param    array    $business_data    Business data.
	 * @return   Dialyra_API_Response
	 */
	public function create_and_connect_business( $business_data ) {
		$response = $this->create_business( $business_data );

		if ( $response->is_successful() ) {
			$this->save_business_from_response( $response );
		}

		return $response;
	}

	/**
	 * Connect an existing business locally.
	 *
	 * @since    1.0.0
	 * @param    int      $business_id    Business ID.
	 * @return   Dialyra_API_Response
	 */
	public function connect_business( $business_id ) {
		$response = $this->api_endpoints->get_business( $business_id );

		if ( $response->is_successful() ) {
			$this->save_business_from_response( $response );
		}

		return $response;
	}

	/**
	 * Update the currently connected business.
	 *
	 * @since    1.0.0
	 * @param    array    $business_data    Business fields to update.
	 * @return   Dialyra_API_Response|false
	 */
	public function update_connected_business( $business_data ) {
		$business_id = $this->get_connected_business_id();

		if ( ! $business_id ) {
			return false;
		}

		$response = $this->api_endpoints->update_business( $business_id, $this->prepare_business_payload( $business_data ) );

		if ( $response->is_successful() ) {
			$this->save_business_from_response( $response );
		}

		return $response;
	}

	/**
	 * Get the connected business ID.
	 *
	 * @since    1.0.0
	 * @return   int
	 */
	public function get_connected_business_id() {
		return class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
	}

	/**
	 * Check whether a business is connected locally.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function has_connected_business() {
		return (bool) $this->get_connected_business_id();
	}

	/**
	 * Get connected business data stored locally.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function get_connected_business_data() {
		$business_data = get_option( self::BUSINESS_DATA_OPTION, array() );

		return is_array( $business_data ) ? $business_data : array();
	}

	/**
	 * Save connected business data locally.
	 *
	 * @since    1.0.0
	 * @param    array    $business_data    Business data.
	 * @return   bool
	 */
	public function save_connected_business_data( $business_data ) {
		$business_data = $this->sanitize_payload( $business_data );

		if ( ! empty( $business_data['id'] ) && class_exists( 'Dialyra_Auth_Manager' ) ) {
			Dialyra_Auth_Manager::save_business_id( $business_data['id'] );
		}

		return update_option( self::BUSINESS_DATA_OPTION, $business_data, false );
	}

	/**
	 * Clear local business connection data.
	 *
	 * @since    1.0.0
	 */
	public function clear_connected_business() {
		if ( class_exists( 'Dialyra_Auth_Manager' ) ) {
			Dialyra_Auth_Manager::remove_business_id();
		}

		delete_option( self::BUSINESS_DATA_OPTION );
	}

	/**
	 * Get stored setup settings merged with defaults.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function get_setup_settings() {
		$defaults = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_setup_defaults() : array();
		$settings = get_option( self::SETUP_SETTINGS_OPTION, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return array_replace_recursive( $defaults, $settings );
	}

	/**
	 * Save setup settings locally.
	 *
	 * @since    1.0.0
	 * @param    array    $settings    Setup settings.
	 * @return   bool
	 */
	public function save_setup_settings( $settings ) {
		$settings = array_replace_recursive( $this->get_setup_settings(), $this->sanitize_payload( $settings ) );

		return update_option( self::SETUP_SETTINGS_OPTION, $settings, false );
	}

	/**
	 * Get business settings from Dialyra.
	 *
	 * @since    1.0.0
	 * @param    int|null    $business_id    Optional business ID.
	 * @return   Dialyra_API_Response|false
	 */
	public function get_business_settings( $business_id = null ) {
		$business_id = $business_id ? absint( $business_id ) : $this->get_connected_business_id();

		if ( ! $business_id ) {
			return false;
		}

		return $this->api_endpoints->get_business_settings( $business_id );
	}

	/**
	 * Update business settings in Dialyra.
	 *
	 * @since    1.0.0
	 * @param    array       $settings       Business settings.
	 * @param    int|null    $business_id    Optional business ID.
	 * @return   Dialyra_API_Response|false
	 */
	public function update_business_settings( $settings, $business_id = null ) {
		$business_id = $business_id ? absint( $business_id ) : $this->get_connected_business_id();

		if ( ! $business_id ) {
			return false;
		}

		return $this->api_endpoints->update_business_settings( $business_id, $this->sanitize_payload( $settings ) );
	}

	/**
	 * Save business data from an API response.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   bool
	 */
	private function save_business_from_response( Dialyra_API_Response $response ) {
		$business_data = $this->extract_response_data( $response );

		if ( empty( $business_data['id'] ) ) {
			return false;
		}

		return $this->save_connected_business_data( $business_data );
	}

	/**
	 * Extract response data and unwrap common API response containers.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   array
	 */
	private function extract_response_data( Dialyra_API_Response $response ) {
		$data = $response->get_data();
		$data = is_array( $data ) ? $data : array();

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		if ( isset( $data['business'] ) && is_array( $data['business'] ) ) {
			$data = $data['business'];
		}

		return $data;
	}

	/**
	 * Prepare business payload from setup/profile fields.
	 *
	 * @since    1.0.0
	 * @param    array    $business_data    Raw business data.
	 * @return   array
	 */
	private function prepare_business_payload( $business_data ) {
		$payload = $this->sanitize_allowed_payload( $business_data, array(
			'name',
			'slug',
			'email',
			'phone',
			'timezone',
			'country',
			'logo_path',
			'status',
		) );

		if ( ! empty( $payload['email'] ) ) {
			$payload['email'] = sanitize_email( $payload['email'] );
		}

		if ( empty( $payload['timezone'] ) && class_exists( 'Wp_Dialyra_Utils' ) ) {
			$payload['timezone'] = Wp_Dialyra_Utils::get_default_timezone();
		}

		return $payload;
	}

	/**
	 * Sanitize payload fields and keep only allowed keys.
	 *
	 * @since    1.0.0
	 * @param    array    $data             Raw payload data.
	 * @param    array    $allowed_fields   Allowed field names.
	 * @return   array
	 */
	private function sanitize_allowed_payload( $data, $allowed_fields ) {
		$payload = array();

		if ( ! is_array( $data ) ) {
			return $payload;
		}

		foreach ( $allowed_fields as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$payload[ $field ] = $this->sanitize_payload_value( $data[ $field ] );
			}
		}

		return $payload;
	}

	/**
	 * Sanitize payload recursively.
	 *
	 * @since    1.0.0
	 * @param    mixed    $data    Raw payload data.
	 * @return   mixed
	 */
	private function sanitize_payload( $data ) {
		if ( is_array( $data ) ) {
			return array_map( array( $this, 'sanitize_payload' ), $data );
		}

		return $this->sanitize_payload_value( $data );
	}

	/**
	 * Sanitize a single payload value.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Raw payload value.
	 * @return   mixed
	 */
	private function sanitize_payload_value( $value ) {
		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || is_null( $value ) ) {
			return $value;
		}

		if ( is_array( $value ) ) {
			return $this->sanitize_payload( $value );
		}

		return sanitize_text_field( $value );
	}
}
