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

if ( ! defined( 'WP_DIALYRA_OPTION_BUSINESS_DATA' ) ) {
	require_once dirname( __DIR__ ) . '/constant.php';
}

class Dialyra_Business_Manager {

	const BUSINESS_DATA_OPTION  = WP_DIALYRA_OPTION_BUSINESS_DATA;
	const SETUP_SETTINGS_OPTION = WP_DIALYRA_OPTION_SETUP_SETTINGS;
	const SITE_TOKEN_OPTION     = WP_DIALYRA_OPTION_SITE_ACCESS_TOKEN;
	const SITE_TOKEN_CACHE_OPTION = WP_DIALYRA_OPTION_SITE_ACCESS_TOKEN_CACHE;

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
	 * The webhook subscription manager.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Webhook_Subscription_Manager|null
	 */
	private $webhook_subscription_manager;

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
		$this->webhook_subscription_manager = class_exists( 'Dialyra_Webhook_Subscription_Manager' ) ? new Dialyra_Webhook_Subscription_Manager( $api_endpoints ) : null;
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
			$this->save_business_from_response( $response, 'created' );
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
		$business_id = absint( $business_id );

		$response = $this->api_endpoints->get_business( $business_id );

		if ( $response->is_successful() ) {
			$this->save_business_from_response( $response, 'selected' );
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
			$this->save_business_from_response( $response, 'updated' );
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
	public function save_connected_business_data( $business_data, $source = 'saved' ) {
		$business_data = $this->sanitize_payload( $business_data );
		$previous_business_id = $this->get_connected_business_id();
		$new_business_id = ! empty( $business_data['id'] ) ? absint( $business_data['id'] ) : 0;

		if ( $new_business_id && class_exists( 'Dialyra_Auth_Manager' ) ) {
			Dialyra_Auth_Manager::save_business_id( $new_business_id );
		}

		$saved = update_option( self::BUSINESS_DATA_OPTION, $business_data, false );

		if ( $new_business_id && $previous_business_id !== $new_business_id ) {
			$business_changed_hook = class_exists( 'Dialyra_Hook_Names' ) ? Dialyra_Hook_Names::get_or_default( 'business', 'business_changed', 'dialyra_business_changed' ) : 'dialyra_business_changed';

			do_action(
				$business_changed_hook,
				$new_business_id,
				$previous_business_id,
				$business_data,
				sanitize_key( $source )
			);
		}

		return $saved || get_option( self::BUSINESS_DATA_OPTION ) === $business_data;
	}

	/**
	 * Handle connected business changes.
	 *
	 * Keeps the business-scoped webhook and runtime access token in sync whenever
	 * the connected business changes from any admin page or setup flow.
	 *
	 * @since    1.0.0
	 * @param    int       $new_business_id         Newly connected business ID.
	 * @param    int       $previous_business_id    Previously connected business ID.
	 * @param    array     $business_data           Saved business data.
	 * @param    string    $source                  Change source.
	 */
	public function handle_connected_business_changed( $new_business_id, $previous_business_id = 0, $business_data = array(), $source = 'saved' ) {
		$new_business_id      = absint( $new_business_id );
		$previous_business_id = absint( $previous_business_id );

		if ( ! $new_business_id ) {
			return;
		}

		if ( $previous_business_id && $previous_business_id !== $new_business_id ) {
			$this->pause_business_webhook( $previous_business_id );
			$this->clear_site_access_token();
		}

		$this->ensure_site_access_token( $new_business_id );

		$this->ensure_business_webhook( $new_business_id );
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
		$this->clear_business_webhook();
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

		if ( $this->restore_cached_site_access_token( $business_id ) ) {
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
	 * Restore the latest cached token for a business when Dialyra still lists it.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Business ID.
	 * @return   bool
	 */
	public function restore_cached_site_access_token( $business_id ) {
		$business_id = absint( $business_id );
		$cached      = $this->get_cached_site_access_token_data( $business_id );

		if ( ! $business_id || empty( $cached['encrypted_token'] ) ) {
			return false;
		}

		$token = $this->decrypt_site_access_token( $cached['encrypted_token'] );

		if ( '' === $token || ! $this->remote_access_token_exists( $business_id, $cached ) ) {
			return false;
		}

		$token_data          = $cached;
		$token_data['token'] = $token;
		unset( $token_data['encrypted_token'] );

		return update_option( self::SITE_TOKEN_OPTION, $this->sanitize_site_token_data( $token_data ), false ) || get_option( self::SITE_TOKEN_OPTION ) === $this->sanitize_site_token_data( $token_data );
	}

	/**
	 * Clear the locally stored site runtime access token.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function clear_site_access_token() {
		$token_data = $this->get_site_access_token_data();

		if ( ! empty( $token_data['token'] ) && ! empty( $token_data['business_id'] ) ) {
			$this->cache_site_access_token_data( $token_data );
		}

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

		$this->sync_setup_setting_options( $settings );

		return update_option( self::SETUP_SETTINGS_OPTION, $settings, false );
	}

	/**
	 * Mirror setup sections into dedicated option keys used by runtime modules.
	 *
	 * @since    1.0.0
	 * @param    array    $settings    Setup settings.
	 */
	private function sync_setup_setting_options( $settings ) {
		if ( defined( 'WP_DIALYRA_OPTION_CALL_TRIGGER_MODE' ) && ! empty( $settings['call_trigger']['mode'] ) ) {
			update_option( WP_DIALYRA_OPTION_CALL_TRIGGER_MODE, sanitize_key( $settings['call_trigger']['mode'] ), false );
		}

		if ( defined( 'WP_DIALYRA_OPTION_BUSINESS_HOURS' ) && isset( $settings['business_hours'] ) && is_array( $settings['business_hours'] ) ) {
			update_option( WP_DIALYRA_OPTION_BUSINESS_HOURS, $this->sanitize_payload( $settings['business_hours'] ), false );
		}

		if ( defined( 'WP_DIALYRA_OPTION_MAX_CONCURRENT_CALLS' ) && isset( $settings['call_capacity']['max_concurrent_calls'] ) ) {
			update_option( WP_DIALYRA_OPTION_MAX_CONCURRENT_CALLS, max( 1, absint( $settings['call_capacity']['max_concurrent_calls'] ) ), false );
		}

		if ( defined( 'WP_DIALYRA_OPTION_RETRY_POLICY' ) && isset( $settings['retry_policy'] ) && is_array( $settings['retry_policy'] ) ) {
			update_option( WP_DIALYRA_OPTION_RETRY_POLICY, $this->sanitize_payload( $settings['retry_policy'] ), false );
		}

		if ( defined( 'WP_DIALYRA_OPTION_SKIP_CALL_STATUSES' ) && isset( $settings['order_status_map']['skip_call_statuses'] ) && is_array( $settings['order_status_map']['skip_call_statuses'] ) ) {
			update_option( WP_DIALYRA_OPTION_SKIP_CALL_STATUSES, array_values( array_filter( array_map( 'sanitize_key', $settings['order_status_map']['skip_call_statuses'] ) ) ), false );
		}
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
	 * Ensure the connected business has a webhook subscription for this site.
	 *
	 * @since    1.0.0
	 * @param    int|null    $business_id    Optional business ID.
	 * @return   array|false
	 */
	public function ensure_business_webhook( $business_id = null ) {
		$business_id = $business_id ? absint( $business_id ) : $this->get_connected_business_id();

		if ( ! $business_id || ! $this->webhook_subscription_manager ) {
			return false;
		}

		$result = $this->webhook_subscription_manager->reconcile_for_business( $business_id );

		if ( is_array( $result ) && ! empty( $result['success'] ) && class_exists( 'Dialyra_Webhook_Health_Check' ) ) {
			$health_check      = new Dialyra_Webhook_Health_Check( $this->api_endpoints );
			$result['health'] = $health_check->check( $result['webhook'] ?? null );
		}

		return $result;
	}

	/**
	 * Get locally stored business webhook data.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function get_business_webhook_data() {
		return $this->webhook_subscription_manager ? $this->webhook_subscription_manager->get_business_webhook_data() : array();
	}

	/**
	 * Clear locally stored business webhook metadata.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function clear_business_webhook() {
		$cleared = defined( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA' ) ? delete_option( WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA ) : false;

		return $cleared;
	}

	/**
	 * Get the locally stored webhook secret for the connected business.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function get_or_create_webhook_secret() {
		$business_id = $this->get_connected_business_id();

		if ( ! $business_id || ! $this->webhook_subscription_manager ) {
			return '';
		}

		$data = $this->webhook_subscription_manager->get_business_webhook_data( $business_id );

		return ! empty( $data['webhook_secret'] ) ? (string) $data['webhook_secret'] : '';
	}

	/**
	 * Pause the plugin-owned webhook for a business when possible.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Business ID.
	 * @return   array|false
	 */
	public function pause_business_webhook( $business_id ) {
		if ( ! $this->webhook_subscription_manager ) {
			return false;
		}

		return $this->webhook_subscription_manager->pause_business_webhook( $business_id );
	}

	/**
	 * Resume/reconcile the plugin-owned webhook for a business when possible.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Business ID.
	 * @return   array|false
	 */
	public function resume_business_webhook( $business_id ) {
		if ( ! $this->webhook_subscription_manager ) {
			return false;
		}

		return $this->webhook_subscription_manager->resume_business_webhook( $business_id );
	}

	/**
	 * Save business data from an API response.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @param    string                  $source      Business change source.
	 * @return   bool
	 */
	private function save_business_from_response( Dialyra_API_Response $response, $source = 'saved' ) {
		$business_data = $this->extract_response_data( $response );

		if ( empty( $business_data['id'] ) ) {
			return false;
		}

		return $this->save_connected_business_data( $business_data, $source );
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
			'token_id'     => ! empty( $access_token['id'] ) ? absint( $access_token['id'] ) : ( ! empty( $data['id'] ) ? absint( $data['id'] ) : 0 ),
			'token_prefix' => ! empty( $access_token['token_prefix'] ) ? sanitize_text_field( $access_token['token_prefix'] ) : ( ! empty( $data['token_prefix'] ) ? sanitize_text_field( $data['token_prefix'] ) : '' ),
			'name'         => ! empty( $access_token['name'] ) ? sanitize_text_field( $access_token['name'] ) : ( ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '' ),
			'scopes'       => ! empty( $access_token['scopes'] ) && is_array( $access_token['scopes'] ) ? array_values( array_map( 'sanitize_text_field', $access_token['scopes'] ) ) : ( ! empty( $data['scopes'] ) && is_array( $data['scopes'] ) ? array_values( array_map( 'sanitize_text_field', $data['scopes'] ) ) : array() ),
			'expires_at'   => ! empty( $access_token['expires_at'] ) ? sanitize_text_field( $access_token['expires_at'] ) : ( ! empty( $data['expires_at'] ) ? sanitize_text_field( $data['expires_at'] ) : '' ),
			'created_at'   => ! empty( $access_token['created_at'] ) ? sanitize_text_field( $access_token['created_at'] ) : ( ! empty( $data['created_at'] ) ? sanitize_text_field( $data['created_at'] ) : current_time( 'mysql' ) ),
		);

		$this->cache_site_access_token_data( $token_data );

		return update_option( self::SITE_TOKEN_OPTION, $this->sanitize_site_token_data( $token_data ), false );
	}

	/**
	 * Get cached encrypted access token data for a business.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Business ID.
	 * @return   array
	 */
	private function get_cached_site_access_token_data( $business_id ) {
		$cache = get_option( self::SITE_TOKEN_CACHE_OPTION, array() );
		$cache = is_array( $cache ) ? $cache : array();
		$key   = (string) absint( $business_id );

		return isset( $cache[ $key ] ) && is_array( $cache[ $key ] ) ? $cache[ $key ] : array();
	}

	/**
	 * Cache one latest encrypted access token per business.
	 *
	 * @since    1.0.0
	 * @param    array    $token_data    Token data including raw token.
	 * @return   bool
	 */
	private function cache_site_access_token_data( $token_data ) {
		$token_data  = $this->sanitize_site_token_data( $token_data );
		$business_id = absint( $token_data['business_id'] ?? 0 );
		$token       = ! empty( $token_data['token'] ) ? (string) $token_data['token'] : '';

		if ( ! $business_id || '' === $token ) {
			return false;
		}

		$encrypted_token = $this->encrypt_site_access_token( $token );

		if ( '' === $encrypted_token ) {
			return false;
		}

		$cache = get_option( self::SITE_TOKEN_CACHE_OPTION, array() );
		$cache = is_array( $cache ) ? $cache : array();

		unset( $token_data['token'] );
		$token_data['encrypted_token'] = $encrypted_token;
		$token_data['cached_at']       = current_time( 'mysql' );
		$cache[ (string) $business_id ] = $token_data;

		return update_option( self::SITE_TOKEN_CACHE_OPTION, $cache, false ) || get_option( self::SITE_TOKEN_CACHE_OPTION ) === $cache;
	}

	/**
	 * Check whether cached token metadata still exists remotely for the business.
	 *
	 * @since    1.0.0
	 * @param    int      $business_id    Business ID.
	 * @param    array    $cached         Cached token metadata.
	 * @return   bool
	 */
	private function remote_access_token_exists( $business_id, $cached ) {
		if ( ! $this->is_cached_token_current( $cached ) ) {
			return false;
		}

		$response = $this->api_endpoints->get_access_tokens( array( 'business_id' => absint( $business_id ) ) );

		if ( ! $response || ! $response->is_successful() ) {
			return false;
		}

		foreach ( $this->extract_access_token_items( $response ) as $remote_token ) {
			if ( $this->access_token_metadata_matches( $remote_token, $cached, $business_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract access token list items from common API response containers.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   array
	 */
	private function extract_access_token_items( Dialyra_API_Response $response ) {
		$data = $response->get_data();
		$data = is_array( $data ) ? $data : array();

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		foreach ( array( 'items', 'access_tokens', 'tokens' ) as $key ) {
			if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
				return array_values( array_filter( $data[ $key ], 'is_array' ) );
			}
		}

		return array_values( array_filter( $data, 'is_array' ) );
	}

	/**
	 * Compare cached token metadata with an API token list row.
	 *
	 * @since    1.0.0
	 * @param    array    $remote_token    Remote token list row.
	 * @param    array    $cached          Cached token metadata.
	 * @param    int      $business_id     Business ID.
	 * @return   bool
	 */
	private function access_token_metadata_matches( $remote_token, $cached, $business_id ) {
		if ( empty( $remote_token['is_active'] ) || ! empty( $remote_token['revoked_at'] ) ) {
			return false;
		}

		if ( absint( $remote_token['business_id'] ?? 0 ) !== absint( $business_id ) ) {
			return false;
		}

		if ( ! empty( $cached['token_id'] ) && absint( $remote_token['id'] ?? 0 ) !== absint( $cached['token_id'] ) ) {
			return false;
		}

		return sanitize_text_field( $remote_token['token_prefix'] ?? '' ) === sanitize_text_field( $cached['token_prefix'] ?? '' )
			&& sanitize_text_field( $remote_token['created_at'] ?? '' ) === sanitize_text_field( $cached['created_at'] ?? '' )
			&& sanitize_text_field( $remote_token['expires_at'] ?? '' ) === sanitize_text_field( $cached['expires_at'] ?? '' );
	}

	/**
	 * Check whether cached token is locally unexpired.
	 *
	 * @since    1.0.0
	 * @param    array    $token_data    Token metadata.
	 * @return   bool
	 */
	private function is_cached_token_current( $token_data ) {
		$expires_at = ! empty( $token_data['expires_at'] ) ? strtotime( $token_data['expires_at'] ) : 0;

		return ! $expires_at || $expires_at > current_time( 'timestamp' );
	}

	/**
	 * Sanitize site access token data before storing it.
	 *
	 * @since    1.0.0
	 * @param    array    $token_data    Token data.
	 * @return   array
	 */
	private function sanitize_site_token_data( $token_data ) {
		return array(
			'business_id'     => absint( $token_data['business_id'] ?? 0 ),
			'token'           => ! empty( $token_data['token'] ) ? sanitize_text_field( $token_data['token'] ) : '',
			'token_id'        => ! empty( $token_data['token_id'] ) ? absint( $token_data['token_id'] ) : 0,
			'token_prefix'    => ! empty( $token_data['token_prefix'] ) ? sanitize_text_field( $token_data['token_prefix'] ) : '',
			'name'            => ! empty( $token_data['name'] ) ? sanitize_text_field( $token_data['name'] ) : '',
			'scopes'          => ! empty( $token_data['scopes'] ) && is_array( $token_data['scopes'] ) ? array_values( array_map( 'sanitize_text_field', $token_data['scopes'] ) ) : array(),
			'expires_at'      => ! empty( $token_data['expires_at'] ) ? sanitize_text_field( $token_data['expires_at'] ) : '',
			'created_at'      => ! empty( $token_data['created_at'] ) ? sanitize_text_field( $token_data['created_at'] ) : '',
			'encrypted_token' => ! empty( $token_data['encrypted_token'] ) ? sanitize_text_field( $token_data['encrypted_token'] ) : '',
			'cached_at'       => ! empty( $token_data['cached_at'] ) ? sanitize_text_field( $token_data['cached_at'] ) : '',
		);
	}

	/**
	 * Encrypt a raw site access token for per-business local cache storage.
	 *
	 * @since    1.0.0
	 * @param    string    $token    Raw token.
	 * @return   string
	 */
	private function encrypt_site_access_token( $token ) {
		if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return '';
		}

		$iv = openssl_random_pseudo_bytes( 16 );

		if ( false === $iv ) {
			return '';
		}

		$ciphertext = openssl_encrypt( (string) $token, 'AES-256-CBC', $this->get_token_encryption_key(), OPENSSL_RAW_DATA, $iv );

		if ( false === $ciphertext ) {
			return '';
		}

		return base64_encode( wp_json_encode( array(
			'cipher' => 'AES-256-CBC',
			'iv'     => base64_encode( $iv ),
			'value'  => base64_encode( $ciphertext ),
		) ) );
	}

	/**
	 * Decrypt a cached site access token.
	 *
	 * @since    1.0.0
	 * @param    string    $encrypted_token    Encrypted token payload.
	 * @return   string
	 */
	private function decrypt_site_access_token( $encrypted_token ) {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$payload = json_decode( base64_decode( (string) $encrypted_token ), true );

		if ( ! is_array( $payload ) || empty( $payload['iv'] ) || empty( $payload['value'] ) ) {
			return '';
		}

		$iv         = base64_decode( $payload['iv'] );
		$ciphertext = base64_decode( $payload['value'] );

		if ( false === $iv || false === $ciphertext ) {
			return '';
		}

		$token = openssl_decrypt( $ciphertext, 'AES-256-CBC', $this->get_token_encryption_key(), OPENSSL_RAW_DATA, $iv );

		return is_string( $token ) ? sanitize_text_field( $token ) : '';
	}

	/**
	 * Get the local encryption key for cached access tokens.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_token_encryption_key() {
		$salt = function_exists( 'wp_salt' ) ? wp_salt( 'auth' ) : ( defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_parse_url( home_url(), PHP_URL_HOST ) );

		return hash( 'sha256', 'wp-dialyra-site-token-cache|' . $salt, true );
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
