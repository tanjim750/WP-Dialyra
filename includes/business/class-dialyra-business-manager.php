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
	const SITE_TOKEN_OPTION     = 'dialyra_site_access_token';

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
		$previous_business_id = $this->get_connected_business_id();
		$new_business_id = ! empty( $business_data['id'] ) ? absint( $business_data['id'] ) : 0;

		if ( $new_business_id && class_exists( 'Dialyra_Auth_Manager' ) ) {
			Dialyra_Auth_Manager::save_business_id( $new_business_id );
		}

		if ( $previous_business_id && $new_business_id && $previous_business_id !== $new_business_id ) {
			$this->clear_site_access_token();
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
		$this->clear_site_access_token();
	}

	/**
	 * Get the locally stored site runtime access token data.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function get_site_access_token_data() {
		$token_data = get_option( self::SITE_TOKEN_OPTION, array() );

		return is_array( $token_data ) ? $token_data : array();
	}

	/**
	 * Check whether the local site token belongs to a business.
	 *
	 * @since    1.0.0
	 * @param    int|null    $business_id    Optional business ID.
	 * @return   bool
	 */
	public function has_site_access_token_for_business( $business_id = null ) {
		$business_id = $business_id ? absint( $business_id ) : $this->get_connected_business_id();
		$token_data = $this->get_site_access_token_data();

		return ! empty( $token_data['token'] ) && ! empty( $token_data['business_id'] ) && absint( $token_data['business_id'] ) === $business_id;
	}

	/**
	 * Ensure this WordPress site has a runtime access token for the connected business.
	 *
	 * @since    1.0.0
	 * @param    int|null    $business_id    Optional business ID.
	 * @return   Dialyra_API_Response|true|false
	 */
	public function ensure_site_access_token( $business_id = null ) {
		$business_id = $business_id ? absint( $business_id ) : $this->get_connected_business_id();

		if ( ! $business_id ) {
			return false;
		}

		if ( $this->has_site_access_token_for_business( $business_id ) ) {
			return true;
		}

		return $this->create_site_access_token( $business_id );
	}

	/**
	 * Create and store a runtime access token for this WordPress site.
	 *
	 * @since    1.0.0
	 * @param    int       $business_id    Business ID.
	 * @return   Dialyra_API_Response
	 */
	public function create_site_access_token( $business_id ) {
		$business_id = absint( $business_id );
		$response = $this->api_endpoints->create_access_token( $this->prepare_site_access_token_payload( $business_id ) );

		if ( $response->is_successful() ) {
			$this->save_site_access_token_from_response( $response, $business_id );
		}

		return $response;
	}

	/**
	 * Clear the locally stored site runtime access token.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function clear_site_access_token() {
		return delete_option( self::SITE_TOKEN_OPTION );
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
	 * Save site token data from a create token response.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response       API response.
	 * @param    int                     $business_id    Business ID.
	 * @return   bool
	 */
	private function save_site_access_token_from_response( Dialyra_API_Response $response, $business_id ) {
		$data = $response->get_data();
		$data = is_array( $data ) ? $data : array();

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		$access_token = isset( $data['access_token'] ) && is_array( $data['access_token'] ) ? $data['access_token'] : array();
		$token = ! empty( $data['token'] ) ? sanitize_text_field( $data['token'] ) : '';

		if ( empty( $token ) ) {
			return false;
		}

		$token_data = array(
			'business_id'  => absint( $business_id ),
			'token'        => $token,
			'token_id'     => ! empty( $access_token['id'] ) ? absint( $access_token['id'] ) : 0,
			'token_prefix' => ! empty( $access_token['token_prefix'] ) ? sanitize_text_field( $access_token['token_prefix'] ) : '',
			'name'         => ! empty( $access_token['name'] ) ? sanitize_text_field( $access_token['name'] ) : '',
			'scopes'       => ! empty( $access_token['scopes'] ) && is_array( $access_token['scopes'] ) ? array_values( array_map( 'sanitize_text_field', $access_token['scopes'] ) ) : array(),
			'expires_at'   => ! empty( $access_token['expires_at'] ) ? sanitize_text_field( $access_token['expires_at'] ) : '',
			'created_at'   => ! empty( $access_token['created_at'] ) ? sanitize_text_field( $access_token['created_at'] ) : current_time( 'mysql' ),
		);

		return update_option( self::SITE_TOKEN_OPTION, $token_data, false );
	}

	/**
	 * Prepare the default runtime access token payload for this site.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Business ID.
	 * @return   array
	 */
	private function prepare_site_access_token_payload( $business_id ) {
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$site_name = get_bloginfo( 'name' );
		$defaults = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_site_access_token_defaults() : array();
		$token_name = $site_name ? sprintf( '%s WordPress Store', $site_name ) : __( 'WordPress Store', 'wp-dialyra' );

		if ( $site_host ) {
			$token_name .= ' - ' . $site_host;
		}

		return array(
			'name'         => $token_name,
			'business_id'  => absint( $business_id ),
			'expires_days' => ! empty( $defaults['expires_days'] ) ? absint( $defaults['expires_days'] ) : 365,
			'scopes'       => ! empty( $defaults['scopes'] ) && is_array( $defaults['scopes'] ) ? $defaults['scopes'] : array( 'calls:originate', 'calls:read' ),
		);
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

		if ( empty( $payload['slug'] ) && ! empty( $payload['name'] ) ) {
			$payload['slug'] = sanitize_title( $payload['name'] );
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
