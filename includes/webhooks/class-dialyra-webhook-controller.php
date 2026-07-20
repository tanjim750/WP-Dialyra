<?php

/**
 * Dialyra webhook REST controller.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/webhooks
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Webhook_Controller {

	const REST_NAMESPACE = 'dialyra/v1';
	const REST_ROUTE     = '/webhooks/call-events';

	/**
	 * Signature verifier.
	 *
	 * @since    1.0.0
	 * @var      Dialyra_Webhook_Signature
	 */
	private $signature;

	/**
	 * Idempotency repository.
	 *
	 * @since    1.0.0
	 * @var      Dialyra_Webhook_Idempotency
	 */
	private $idempotency;

	/**
	 * Event normalizer.
	 *
	 * @since    1.0.0
	 * @var      Dialyra_Webhook_Event_Normalizer
	 */
	private $normalizer;

	/**
	 * Audit log repository.
	 *
	 * @since    1.0.0
	 * @var      Dialyra_Audit_Log_Repository|null
	 */
	private $audit_log_repository;

	/**
	 * Construct controller.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_Audit_Log_Repository|null    $audit_log_repository    Optional audit logger.
	 */
	public function __construct( $audit_log_repository = null ) {
		$this->signature   = new Dialyra_Webhook_Signature();
		$this->idempotency = new Dialyra_Webhook_Idempotency();
		$this->normalizer  = new Dialyra_Webhook_Event_Normalizer();
		$this->audit_log_repository = $audit_log_repository;
	}

	/**
	 * Register REST route.
	 *
	 * @since    1.0.0
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_call_event' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle Dialyra call event webhooks.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    REST request.
	 * @return   WP_REST_Response|WP_Error
	 */
	public function handle_call_event( WP_REST_Request $request ) {
		$raw_body  = $request->get_body();
		$timestamp = $request->get_header( 'x-dialyra-timestamp' );
		$signature = $request->get_header( 'x-dialyra-signature' );
		$this->audit_webhook(
			'webhook_received',
			'Dialyra webhook request received.',
			array(
				'route'          => self::REST_NAMESPACE . self::REST_ROUTE,
				'content_length' => strlen( (string) $raw_body ),
				'has_timestamp'  => '' !== (string) $timestamp,
				'has_signature'  => '' !== (string) $signature,
			)
		);

		$payload = json_decode( $raw_body, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
			$this->audit_webhook(
				'webhook_invalid_json',
				'Dialyra webhook JSON body is invalid.',
				array(
					'json_error' => json_last_error_msg(),
				),
				'error'
			);
			return new WP_Error( 'dialyra_webhook_json_invalid', __( 'Dialyra webhook JSON body is invalid.', 'wp-dialyra' ), array( 'status' => 400 ) );
		}

		$secret = $this->get_webhook_secret( $payload );
		$business_id = $this->extract_business_id( $payload );

		$verified = $this->signature->verify( $raw_body, $timestamp, $signature, $secret );

		if ( is_wp_error( $verified ) ) {
			$this->audit_webhook(
				'webhook_signature_failed',
				$verified->get_error_message(),
				array(
					'business_id' => $business_id,
					'error_code'  => $verified->get_error_code(),
					'has_secret'  => '' !== (string) $secret,
				),
				'error'
			);
			return $verified;
		}

		$event      = is_array( $payload['event'] ?? null ) ? $payload['event'] : array();
		$header_id  = sanitize_text_field( $request->get_header( 'x-dialyra-event-id' ) );
		$payload_id = sanitize_text_field( $event['id'] ?? ( $payload['event_id'] ?? '' ) );
		$event_id   = $header_id ? $header_id : $payload_id;
		$event_type = sanitize_text_field( $request->get_header( 'x-dialyra-event-type' ) );
		$event_type = $event_type ? $event_type : sanitize_text_field( $event['type'] ?? ( $payload['event_type'] ?? '' ) );

		if ( '' === $event_id ) {
			$this->audit_webhook(
				'webhook_event_id_missing',
				'Dialyra webhook event ID is required.',
				array(
					'business_id' => $business_id,
					'event_type'  => $event_type,
				),
				'error'
			);
			return new WP_Error( 'dialyra_webhook_event_id_missing', __( 'Dialyra webhook event ID is required.', 'wp-dialyra' ), array( 'status' => 422 ) );
		}

		if ( $header_id && $payload_id && $header_id !== $payload_id ) {
			$this->audit_webhook(
				'webhook_event_id_mismatch',
				'Dialyra webhook event ID header and payload do not match.',
				array(
					'business_id' => $business_id,
					'header_id'   => $header_id,
					'payload_id'  => $payload_id,
					'event_type'  => $event_type,
				),
				'error'
			);
			return new WP_Error( 'dialyra_webhook_event_id_mismatch', __( 'Dialyra webhook event ID header and payload do not match.', 'wp-dialyra' ), array( 'status' => 422 ) );
		}

		if ( ! $this->idempotency->reserve_event( $event_id, $event_type ) ) {
			$this->audit_webhook(
				'webhook_duplicate_skipped',
				'Duplicate Dialyra webhook skipped.',
				array(
					'business_id' => $business_id,
					'event_id'    => $event_id,
					'event_type'  => $event_type,
				),
				'warning'
			);
			return rest_ensure_response(
				array(
					'received'  => true,
					'duplicate' => true,
				)
			);
		}

		$normalized_event = $this->normalizer->normalize( $payload, $event_id, $event_type );
		$this->audit_webhook(
			'webhook_normalized',
			'Dialyra webhook normalized.',
			array(
				'business_id' => $business_id,
				'order_id'    => absint( $normalized_event['order_id'] ?? 0 ),
				'event_id'    => $event_id,
				'event_type'  => $event_type,
				'call_status' => sanitize_key( $normalized_event['call_status'] ?? '' ),
				'order_action' => sanitize_key( $normalized_event['order_action'] ?? '' ),
			)
		);

		do_action( Dialyra_Hook_Names::get_or_default( 'webhook', 'call_event_received', 'dialyra_call_event_received' ), $normalized_event );
		$this->audit_webhook(
			'webhook_dispatched',
			'Dialyra webhook event dispatched to plugin listeners.',
			array(
				'business_id' => $business_id,
				'order_id'    => absint( $normalized_event['order_id'] ?? 0 ),
				'event_id'    => $event_id,
				'event_type'  => $event_type,
			),
			'success'
		);

		$this->idempotency->mark_processed( $event_id );
		$this->audit_webhook(
			'webhook_processed',
			'Dialyra webhook marked processed.',
			array(
				'business_id' => $business_id,
				'order_id'    => absint( $normalized_event['order_id'] ?? 0 ),
				'event_id'    => $event_id,
				'event_type'  => $event_type,
			),
			'success'
		);

		return rest_ensure_response(
			array(
				'received'   => true,
				'duplicate'  => false,
				'event_id'   => $event_id,
				'event_type' => $event_type,
			)
		);
	}

	/**
	 * Get endpoint URL for subscription setup.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public static function get_endpoint_url() {
		return rest_url( self::REST_NAMESPACE . self::REST_ROUTE );
	}

	/**
	 * Get the business-scoped webhook secret for an incoming payload.
	 *
	 * @since    1.0.0
	 * @param    array    $payload    Incoming webhook payload.
	 * @return   string
	 */
	private function get_webhook_secret( $payload ) {
		$business_id = $this->extract_business_id( $payload );

		if ( ! $business_id && class_exists( 'Dialyra_Auth_Manager' ) ) {
			$business_id = absint( Dialyra_Auth_Manager::get_business_id() );
		}

		if ( ! $business_id || ! defined( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_CREDENTIALS' ) ) {
			return '';
		}

		$credentials = get_option( WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_CREDENTIALS, array() );
		$key         = (string) absint( $business_id );

		if ( is_array( $credentials ) && ! empty( $credentials[ $key ]['webhook_secret'] ) ) {
			return (string) $credentials[ $key ]['webhook_secret'];
		}

		$current = defined( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA' ) ? get_option( WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA, array() ) : array();

		if ( is_array( $current ) && absint( $current['business_id'] ?? 0 ) === absint( $business_id ) && ! empty( $current['webhook_secret'] ) ) {
			return (string) $current['webhook_secret'];
		}

		return '';
	}

	/**
	 * Extract business ID from common webhook payload locations.
	 *
	 * @since    1.0.0
	 * @param    array    $payload    Incoming webhook payload.
	 * @return   int
	 */
	private function extract_business_id( $payload ) {
		$paths = array(
			array( 'business_id' ),
			array( 'business', 'id' ),
			array( 'data', 'business_id' ),
			array( 'data', 'business', 'id' ),
			array( 'event', 'business_id' ),
			array( 'event', 'business', 'id' ),
			array( 'event', 'data', 'business_id' ),
			array( 'event', 'data', 'business', 'id' ),
			array( 'call', 'business_id' ),
			array( 'event', 'call', 'business_id' ),
		);

		foreach ( $paths as $path ) {
			$value = $payload;

			foreach ( $path as $key ) {
				if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
					$value = null;
					break;
				}

				$value = $value[ $key ];
			}

			if ( $value ) {
				return absint( $value );
			}
		}

		return 0;
	}

	/**
	 * Persist a webhook audit event.
	 *
	 * @since    1.0.0
	 * @param    string    $event      Event key.
	 * @param    string    $message    Human-readable message.
	 * @param    array     $context    Structured context.
	 * @param    string    $level      Log level.
	 * @return   void
	 */
	private function audit_webhook( $event, $message, $context = array(), $level = 'info' ) {
		if ( ! $this->audit_log_repository || ! method_exists( $this->audit_log_repository, 'log' ) ) {
			return;
		}

		$this->audit_log_repository->log( $event, $message, is_array( $context ) ? $context : array(), $level, 'webhook' );
	}
}
