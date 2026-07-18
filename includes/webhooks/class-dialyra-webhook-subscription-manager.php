<?php

/**
 * Dialyra webhook subscription reconciliation service.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/webhooks
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Webhook_Subscription_Manager {

	const TIMEOUT_SECONDS = 5;

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
	 * @param    Dialyra_API_Endpoints    $api_endpoints    The API endpoints object.
	 */
	public function __construct( Dialyra_API_Endpoints $api_endpoints ) {
		$this->api_endpoints = $api_endpoints;
	}

	/**
	 * Reconcile plugin-owned webhook subscriptions for a business.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Dialyra business ID.
	 * @return   array
	 */
	public function reconcile_for_business( $business_id ) {
		$business_id = absint( $business_id );

		if ( ! $business_id || ! class_exists( 'Dialyra_Webhook_Controller' ) ) {
			return $this->result( false, __( 'A valid business and webhook endpoint are required.', 'wp-dialyra' ) );
		}

		$expected_url = $this->get_expected_webhook_url();
		$fetch        = $this->fetch_business_webhooks( $business_id );

		if ( empty( $fetch['success'] ) ) {
			return $fetch;
		}

		$webhooks       = $fetch['items'];
		$matches        = $this->find_plugin_webhooks( $webhooks, $business_id, $expected_url );
		$stored         = $this->get_business_webhook_data( $business_id );
		$canonical      = $this->find_locally_known_match( $matches, $stored );
		$legacy_matches = $this->find_plugin_webhooks_for_stored_url( $webhooks, $business_id, $stored, $expected_url );

		if ( ! $canonical ) {
			$created = $this->create_webhook( $business_id );

			if ( empty( $created['success'] ) ) {
				return $created;
			}

			$canonical = $created['webhook'];
			$save      = $this->save_webhook_credentials( $business_id, $canonical, $expected_url );

			if ( ! $save ) {
				$this->disable_webhook( absint( $canonical['id'] ?? 0 ) );
				return $this->result( false, __( 'Webhook was created but its secret could not be stored locally.', 'wp-dialyra' ) );
			}

			$this->disable_duplicate_webhooks( absint( $canonical['id'] ), array_merge( $matches, $legacy_matches ) );

			return $this->result( true, __( 'Webhook subscription created and stored.', 'wp-dialyra' ), $canonical );
		}

		$active = $this->ensure_webhook_active( $canonical );

		if ( empty( $active['success'] ) ) {
			return $active;
		}

		$canonical = ! empty( $active['webhook'] ) ? $active['webhook'] : $canonical;
		$events    = $this->ensure_required_events( $canonical, $business_id, $expected_url );

		if ( empty( $events['success'] ) ) {
			return $events;
		}

		$canonical = ! empty( $events['webhook'] ) ? $events['webhook'] : $canonical;
		$this->save_current_webhook_state( $business_id, $canonical, $expected_url, $stored['webhook_secret'] ?? '' );
		$this->disable_duplicate_webhooks( absint( $canonical['id'] ), array_merge( $matches, $legacy_matches ) );

		return $this->result( true, __( 'Webhook subscription is ready.', 'wp-dialyra' ), $canonical );
	}

	/**
	 * Create a plugin-owned business webhook.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Dialyra business ID.
	 * @return   array
	 */
	public function create_webhook( $business_id ) {
		$response = $this->api_endpoints->create_business_webhook( $this->prepare_create_payload( $business_id ) );

		if ( ! $response || ! $response->is_successful() ) {
			return $this->result( false, $response ? $response->get_message() : __( 'Webhook creation failed.', 'wp-dialyra' ) );
		}

		$webhook = $this->extract_webhook_data( $response );

		if ( empty( $webhook['id'] ) || empty( $webhook['secret'] ) ) {
			return $this->result( false, __( 'Webhook was created but Dialyra did not return the required ID and secret.', 'wp-dialyra' ) );
		}

		return $this->result( true, __( 'Webhook created.', 'wp-dialyra' ), $webhook );
	}

	/**
	 * Fetch business webhooks.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Dialyra business ID.
	 * @return   array
	 */
	public function fetch_business_webhooks( $business_id ) {
		$response = $this->api_endpoints->get_business_webhooks( array( 'business_id' => absint( $business_id ) ) );

		if ( ! $response || ! $response->is_successful() ) {
			return $this->result( false, $response ? $response->get_message() : __( 'Unable to fetch business webhooks.', 'wp-dialyra' ) );
		}

		return array(
			'success' => true,
			'message' => __( 'Business webhooks fetched.', 'wp-dialyra' ),
			'items'   => $this->extract_items( $response ),
		);
	}

	/**
	 * Find plugin-owned webhooks for the expected endpoint URL.
	 *
	 * @since    1.0.0
	 * @param    array     $webhooks       Webhook rows.
	 * @param    int       $business_id    Dialyra business ID.
	 * @param    string    $expected_url   Expected endpoint URL.
	 * @return   array
	 */
	public function find_plugin_webhooks( $webhooks, $business_id, $expected_url ) {
		return array_values(
			array_filter(
				is_array( $webhooks ) ? $webhooks : array(),
				function ( $webhook ) use ( $business_id, $expected_url ) {
					return $this->is_plugin_owned_webhook( $webhook, $business_id, $expected_url );
				}
			)
		);
	}

	/**
	 * Ensure webhook is active/resumed.
	 *
	 * @since    1.0.0
	 * @param    array    $webhook    Webhook row.
	 * @return   array
	 */
	public function ensure_webhook_active( $webhook ) {
		$status     = sanitize_key( $webhook['status'] ?? 'active' );
		$webhook_id = absint( $webhook['id'] ?? 0 );

		if ( ! $webhook_id || 'active' === $status ) {
			return $this->result( true, __( 'Webhook is active.', 'wp-dialyra' ), $webhook );
		}

		if ( 'paused' === $status ) {
			$response = $this->api_endpoints->resume_business_webhook( $webhook_id );
		} else {
			$response = $this->api_endpoints->update_business_webhook( $webhook_id, array( 'status' => 'active' ) );
		}

		if ( ! $response || ! $response->is_successful() ) {
			return $this->result( false, $response ? $response->get_message() : __( 'Unable to activate webhook.', 'wp-dialyra' ) );
		}

		return $this->result( true, __( 'Webhook activated.', 'wp-dialyra' ), $this->extract_webhook_data( $response ) );
	}

	/**
	 * Ensure all required webhook events are subscribed.
	 *
	 * @since    1.0.0
	 * @param    array     $webhook        Webhook row.
	 * @param    int       $business_id    Dialyra business ID.
	 * @param    string    $expected_url   Expected endpoint URL.
	 * @return   array
	 */
	public function ensure_required_events( $webhook, $business_id, $expected_url ) {
		$existing_events = $this->normalize_event_types( $webhook['event_types'] ?? array() );
		$required_events = $this->get_required_events();
		$missing_events  = array_values( array_diff( $required_events, $existing_events ) );

		if ( empty( $missing_events ) ) {
			return $this->result( true, __( 'Webhook events are ready.', 'wp-dialyra' ), $webhook );
		}

		$response = $this->api_endpoints->update_business_webhook(
			absint( $webhook['id'] ?? 0 ),
			array(
				'business_id'     => absint( $business_id ),
				'name'            => $this->get_webhook_name(),
				'url'             => esc_url_raw( $expected_url ),
				'event_types'     => array_values( array_unique( array_merge( $existing_events, $required_events ) ) ),
				'timeout_seconds' => self::TIMEOUT_SECONDS,
				'status'          => 'active',
			)
		);

		if ( ! $response || ! $response->is_successful() ) {
			return $this->result( false, $response ? $response->get_message() : __( 'Unable to update webhook events.', 'wp-dialyra' ) );
		}

		return $this->result( true, __( 'Webhook events updated.', 'wp-dialyra' ), $this->extract_webhook_data( $response ) );
	}

	/**
	 * Disable duplicate plugin-owned webhooks.
	 *
	 * @since    1.0.0
	 * @param    int      $canonical_id    Canonical webhook ID.
	 * @param    array    $matches         Matching plugin-owned webhooks.
	 * @return   array
	 */
	public function disable_duplicate_webhooks( $canonical_id, $matches ) {
		$results = array();

		foreach ( is_array( $matches ) ? $matches : array() as $webhook ) {
			$webhook_id = absint( $webhook['id'] ?? 0 );

			if ( ! $webhook_id || $webhook_id === absint( $canonical_id ) ) {
				continue;
			}

			$results[ $webhook_id ] = $this->disable_webhook( $webhook_id );
		}

		return $results;
	}

	/**
	 * Pause the locally known webhook for a business.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Dialyra business ID.
	 * @return   array
	 */
	public function pause_business_webhook( $business_id ) {
		$stored     = $this->get_business_webhook_data( $business_id );
		$webhook_id = absint( $stored['webhook_id'] ?? ( $stored['id'] ?? 0 ) );

		if ( ! $webhook_id ) {
			return $this->result( true, __( 'No local webhook needs pausing.', 'wp-dialyra' ) );
		}

		$response = $this->api_endpoints->pause_business_webhook( $webhook_id );

		if ( ! $response || ! $response->is_successful() ) {
			return $this->result( false, $response ? $response->get_message() : __( 'Unable to pause webhook.', 'wp-dialyra' ) );
		}

		return $this->result( true, __( 'Webhook paused.', 'wp-dialyra' ), $this->extract_webhook_data( $response ) );
	}

	/**
	 * Resume/reconcile a business webhook.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Dialyra business ID.
	 * @return   array
	 */
	public function resume_business_webhook( $business_id ) {
		return $this->reconcile_for_business( $business_id );
	}

	/**
	 * Get expected webhook URL.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function get_expected_webhook_url() {
		return Dialyra_Webhook_Controller::get_endpoint_url();
	}

	/**
	 * Check whether local secret exists for a business webhook ID.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Dialyra business ID.
	 * @param    int    $webhook_id     Webhook ID.
	 * @return   bool
	 */
	public function has_valid_local_secret( $business_id, $webhook_id ) {
		$stored = $this->get_business_webhook_data( $business_id );

		return absint( $stored['webhook_id'] ?? ( $stored['id'] ?? 0 ) ) === absint( $webhook_id ) && ! empty( $stored['webhook_secret'] );
	}

	/**
	 * Get locally stored webhook data.
	 *
	 * @since    1.0.0
	 * @param    int|null    $business_id    Optional business ID.
	 * @return   array
	 */
	public function get_business_webhook_data( $business_id = null ) {
		if ( $business_id ) {
			$credentials = $this->get_business_webhook_credentials();
			$key         = (string) absint( $business_id );

			return isset( $credentials[ $key ] ) && is_array( $credentials[ $key ] ) ? $credentials[ $key ] : array();
		}

		$data = defined( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA' ) ? get_option( WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA, array() ) : array();

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Get required event types.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function get_required_events() {
		return array( 'call.completed', 'call.failed' );
	}

	/**
	 * Get plugin-owned webhook name prefix.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function get_webhook_name_prefix() {
		return defined( 'WP_DIALYRA_WEBHOOK_NAME_PREFIX' ) ? WP_DIALYRA_WEBHOOK_NAME_PREFIX : 'Dialyra WooCommerce Plugin';
	}

	/**
	 * Disable a webhook subscription.
	 *
	 * @since    1.0.0
	 * @param    int    $webhook_id    Webhook ID.
	 * @return   bool
	 */
	private function disable_webhook( $webhook_id ) {
		if ( ! $webhook_id ) {
			return false;
		}

		$response = $this->api_endpoints->delete_business_webhook( $webhook_id );

		return $response && $response->is_successful();
	}

	/**
	 * Find the matching webhook with a locally stored secret.
	 *
	 * @since    1.0.0
	 * @param    array    $matches    Plugin-owned matches.
	 * @param    array    $stored     Stored webhook data.
	 * @return   array|null
	 */
	private function find_locally_known_match( $matches, $stored ) {
		$stored_webhook_id = absint( $stored['webhook_id'] ?? ( $stored['id'] ?? 0 ) );

		if ( ! $stored_webhook_id || empty( $stored['webhook_secret'] ) ) {
			return null;
		}

		foreach ( $matches as $webhook ) {
			if ( absint( $webhook['id'] ?? 0 ) === $stored_webhook_id ) {
				return $webhook;
			}
		}

		return null;
	}

	/**
	 * Find plugin-owned webhooks for a previously stored URL.
	 *
	 * @since    1.0.0
	 * @param    array     $webhooks       Webhook rows.
	 * @param    int       $business_id    Dialyra business ID.
	 * @param    array     $stored         Stored webhook data.
	 * @param    string    $expected_url   Expected endpoint URL.
	 * @return   array
	 */
	private function find_plugin_webhooks_for_stored_url( $webhooks, $business_id, $stored, $expected_url ) {
		$stored_url = ! empty( $stored['webhook_url'] ) ? esc_url_raw( $stored['webhook_url'] ) : ( ! empty( $stored['url'] ) ? esc_url_raw( $stored['url'] ) : '' );

		if ( ! $stored_url || $stored_url === $expected_url ) {
			return array();
		}

		return $this->find_plugin_webhooks( $webhooks, $business_id, $stored_url );
	}

	/**
	 * Check plugin ownership.
	 *
	 * @since    1.0.0
	 * @param    array     $webhook        Webhook row.
	 * @param    int       $business_id    Dialyra business ID.
	 * @param    string    $expected_url   Expected endpoint URL.
	 * @return   bool
	 */
	private function is_plugin_owned_webhook( $webhook, $business_id, $expected_url ) {
		$name = sanitize_text_field( $webhook['name'] ?? '' );
		$url  = ! empty( $webhook['url'] ) ? esc_url_raw( $webhook['url'] ) : '';

		return absint( $webhook['business_id'] ?? 0 ) === absint( $business_id )
			&& $url === esc_url_raw( $expected_url )
			&& 0 === strpos( $name, $this->get_webhook_name_prefix() );
	}

	/**
	 * Prepare webhook create payload.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Dialyra business ID.
	 * @return   array
	 */
	private function prepare_create_payload( $business_id ) {
		return array(
			'business_id'     => absint( $business_id ),
			'name'            => $this->get_webhook_name(),
			'url'             => $this->get_expected_webhook_url(),
			'event_types'     => $this->get_required_events(),
			'timeout_seconds' => self::TIMEOUT_SECONDS,
		);
	}

	/**
	 * Get plugin-owned webhook name.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_webhook_name() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return $host ? $this->get_webhook_name_prefix() . ' - ' . $host : $this->get_webhook_name_prefix();
	}

	/**
	 * Save webhook credentials locally.
	 *
	 * @since    1.0.0
	 * @param    int       $business_id     Dialyra business ID.
	 * @param    array     $webhook         Webhook row.
	 * @param    string    $webhook_url     Webhook URL.
	 * @return   bool
	 */
	private function save_webhook_credentials( $business_id, $webhook, $webhook_url ) {
		$webhook_id = absint( $webhook['id'] ?? ( $webhook['webhook_id'] ?? 0 ) );
		$secret     = sanitize_text_field( $webhook['secret'] ?? ( $webhook['webhook_secret'] ?? '' ) );

		if ( ! $webhook_id || '' === $secret ) {
			return false;
		}

		return $this->save_current_webhook_state( $business_id, $webhook, $webhook_url, $secret );
	}

	/**
	 * Save current and per-business webhook state.
	 *
	 * @since    1.0.0
	 * @param    int       $business_id       Dialyra business ID.
	 * @param    array     $webhook           Webhook row.
	 * @param    string    $webhook_url       Webhook URL.
	 * @param    string    $webhook_secret    Webhook secret.
	 * @return   bool
	 */
	private function save_current_webhook_state( $business_id, $webhook, $webhook_url, $webhook_secret ) {
		$webhook_id = absint( $webhook['id'] ?? ( $webhook['webhook_id'] ?? 0 ) );

		if ( ! $webhook_id || '' === $webhook_secret ) {
			return false;
		}

		$data = array(
			'id'              => $webhook_id,
			'webhook_id'      => $webhook_id,
			'business_id'     => absint( $business_id ),
			'webhook_secret'  => sanitize_text_field( $webhook_secret ),
			'webhook_url'     => esc_url_raw( $webhook_url ),
			'url'             => esc_url_raw( $webhook_url ),
			'event_types'     => $this->normalize_event_types( $webhook['event_types'] ?? $this->get_required_events() ),
			'status'          => sanitize_key( $webhook['status'] ?? 'active' ),
			'timeout_seconds' => absint( $webhook['timeout_seconds'] ?? self::TIMEOUT_SECONDS ),
			'updated_at'      => current_time( 'mysql' ),
		);

		$credentials = $this->get_business_webhook_credentials();
		$credentials[ (string) absint( $business_id ) ] = $data;

		$credentials_saved = defined( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_CREDENTIALS' ) ? $this->update_option_value( WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_CREDENTIALS, $credentials ) : false;
		$current_saved     = defined( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA' ) ? $this->update_option_value( WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA, $data ) : false;

		return $credentials_saved && $current_saved;
	}

	/**
	 * Update an option and treat unchanged values as a successful persisted state.
	 *
	 * @since    1.0.0
	 * @param    string    $option_name    Option name.
	 * @param    mixed     $value          Option value.
	 * @return   bool
	 */
	private function update_option_value( $option_name, $value ) {
		$updated = update_option( $option_name, $value, false );

		return $updated || get_option( $option_name ) === $value;
	}

	/**
	 * Get all stored per-business webhook credentials.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function get_business_webhook_credentials() {
		$credentials = defined( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_CREDENTIALS' ) ? get_option( WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_CREDENTIALS, array() ) : array();

		return is_array( $credentials ) ? $credentials : array();
	}

	/**
	 * Extract webhook rows from a list response.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   array
	 */
	private function extract_items( Dialyra_API_Response $response ) {
		$data = $response->get_data();
		$data = is_array( $data ) ? $data : array();

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		foreach ( array( 'items', 'webhooks', 'business_webhooks' ) as $key ) {
			if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
				return array_values( array_filter( $data[ $key ], 'is_array' ) );
			}
		}

		return array_values( array_filter( $data, 'is_array' ) );
	}

	/**
	 * Extract webhook data from a single response.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   array
	 */
	private function extract_webhook_data( Dialyra_API_Response $response ) {
		$root = $response->get_data();
		$root = is_array( $root ) ? $root : array();
		$data = $root;

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		if ( isset( $data['webhook'] ) && is_array( $data['webhook'] ) ) {
			$data = $data['webhook'];
		}

		if ( isset( $data['business_webhook'] ) && is_array( $data['business_webhook'] ) ) {
			$data = $data['business_webhook'];
		}

		if ( empty( $data['secret'] ) && ! empty( $root['secret'] ) ) {
			$data['secret'] = $root['secret'];
		}

		if ( empty( $data['webhook_secret'] ) && ! empty( $root['webhook_secret'] ) ) {
			$data['webhook_secret'] = $root['webhook_secret'];
		}

		if ( empty( $data['event_types'] ) && ! empty( $data['events'] ) ) {
			$data['event_types'] = $data['events'];
		}

		return $data;
	}

	/**
	 * Normalize event types.
	 *
	 * @since    1.0.0
	 * @param    mixed    $event_types    Event types.
	 * @return   array
	 */
	private function normalize_event_types( $event_types ) {
		if ( is_string( $event_types ) ) {
			$event_types = explode( ',', $event_types );
		}

		return array_values( array_unique( array_filter( array_map( 'sanitize_text_field', is_array( $event_types ) ? $event_types : array() ) ) ) );
	}

	/**
	 * Build a service result.
	 *
	 * @since    1.0.0
	 * @param    bool      $success    Success flag.
	 * @param    string    $message    Message.
	 * @param    array     $webhook    Optional webhook row.
	 * @return   array
	 */
	private function result( $success, $message, $webhook = array() ) {
		return array(
			'success' => (bool) $success,
			'message' => sanitize_text_field( $message ),
			'webhook' => is_array( $webhook ) ? $webhook : array(),
		);
	}
}
