<?php

/**
 * Dialyra webhook health check service.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/webhooks
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Webhook_Health_Check {

	/**
	 * Dialyra API endpoints.
	 *
	 * @var Dialyra_API_Endpoints
	 */
	private $api_endpoints;

	/**
	 * Constructor.
	 *
	 * @param Dialyra_API_Endpoints $api_endpoints API endpoints service.
	 */
	public function __construct( Dialyra_API_Endpoints $api_endpoints ) {
		$this->api_endpoints = $api_endpoints;
	}

	/**
	 * Run the external Dialyra webhook delivery test.
	 *
	 * @param array|null $webhook_data Optional webhook data.
	 * @return array
	 */
	public function check( $webhook_data = null ) {
		$webhook_data = is_array( $webhook_data ) ? $webhook_data : $this->get_stored_webhook_data();
		$webhook_id   = absint( $webhook_data['webhook_id'] ?? ( $webhook_data['id'] ?? 0 ) );

		if ( ! $webhook_id ) {
			return $this->persist(
				array(
					'status'             => 'unknown',
					'healthy'            => false,
					'last_error_code'    => 'missing_webhook_id',
					'last_error_message' => __( 'No Dialyra webhook subscription is stored for this business yet.', 'wp-dialyra' ),
					'webhook_url'        => $this->get_webhook_url( $webhook_data ),
					'webhook_id'         => 0,
					'business_id'        => absint( $webhook_data['business_id'] ?? 0 ),
				)
			);
		}

		$response = $this->api_endpoints->test_business_webhook( $webhook_id );
		$state    = $this->map_response( $response, $webhook_data, $webhook_id );

		return $this->persist( $state );
	}

	/**
	 * Get stored health state.
	 *
	 * @return array
	 */
	public static function get_stored_status() {
		$status = defined( 'WP_DIALYRA_OPTION_WEBHOOK_HEALTH' ) ? get_option( WP_DIALYRA_OPTION_WEBHOOK_HEALTH, array() ) : array();

		return is_array( $status ) ? array_merge( self::get_default_status(), $status ) : self::get_default_status();
	}

	/**
	 * Get a human label for a health status.
	 *
	 * @param string $status Health status.
	 * @return string
	 */
	public static function get_status_label( $status ) {
		$labels = array(
			'healthy'                => __( 'Healthy', 'wp-dialyra' ),
			'blocked'                => __( 'Blocked', 'wp-dialyra' ),
			'route_missing'          => __( 'Route missing', 'wp-dialyra' ),
			'authentication_blocked' => __( 'REST API blocked', 'wp-dialyra' ),
			'network_error'          => __( 'Network error', 'wp-dialyra' ),
			'delivery_failed'        => __( 'Delivery failed', 'wp-dialyra' ),
			'signature_error'        => __( 'Signature error', 'wp-dialyra' ),
			'unknown'                => __( 'Not checked', 'wp-dialyra' ),
		);

		return $labels[ $status ] ?? $labels['unknown'];
	}

	/**
	 * Map API response to a normalized health state.
	 *
	 * @param Dialyra_API_Response|false $response     API response.
	 * @param array                      $webhook_data Webhook data.
	 * @param int                        $webhook_id   Webhook ID.
	 * @return array
	 */
	private function map_response( $response, $webhook_data, $webhook_id ) {
		$base = array(
			'webhook_url' => $this->get_webhook_url( $webhook_data ),
			'webhook_id'  => $webhook_id,
			'business_id' => absint( $webhook_data['business_id'] ?? 0 ),
		);

		if ( ! $response || ! is_object( $response ) || ! method_exists( $response, 'is_successful' ) ) {
			return array_merge(
				$base,
				array(
					'status'             => 'unknown',
					'healthy'            => false,
					'last_error_code'    => 'health_check_unavailable',
					'last_error_message' => __( 'Webhook health check service is not available.', 'wp-dialyra' ),
				)
			);
		}

		$data        = $response->get_data();
		$status_code = absint( $response->get_status_code() );
		$message     = $response->get_message();
		$error_code  = $this->extract_error_code( $data, $response->get_error_type() );
		$text        = strtolower( wp_json_encode( $data ) . ' ' . $message . ' ' . $error_code );

		if ( $response->is_successful() ) {
			if ( $this->response_reports_failed_delivery( $data, $text ) ) {
				if ( false !== strpos( $text, 'rest_no_route' ) ) {
					return $this->failure_state( $base, 'route_missing', 'rest_no_route', __( 'The Dialyra webhook REST route is not registered or the request method does not match.', 'wp-dialyra' ), $status_code );
				}

				if ( false !== strpos( $text, 'rest_disabled' ) ) {
					return $this->failure_state( $base, 'authentication_blocked', 'rest_disabled', __( 'The WordPress REST API is being blocked before the Dialyra webhook route executes. Check security plugins, theme code, MU plugins, or hosting security settings.', 'wp-dialyra' ), $status_code );
				}

				return $this->failure_state( $base, 'delivery_failed', $error_code, $this->extract_delivery_message( $data, $message ), $status_code );
			}

			if ( false !== strpos( $text, 'signature' ) || false !== strpos( $text, 'hmac' ) ) {
				return array_merge(
					$base,
					array(
						'status'             => 'signature_error',
						'healthy'            => false,
						'last_error_code'    => 'signature_error',
						'last_error_message' => __( 'Dialyra reached WordPress, but the webhook signature was rejected. Reconcile the webhook subscription.', 'wp-dialyra' ),
						'response_status_code' => $status_code,
					)
				);
			}

			return array_merge(
				$base,
				array(
					'status'             => 'healthy',
					'healthy'            => true,
					'last_error_code'    => '',
					'last_error_message' => __( 'Dialyra successfully reached the WordPress webhook endpoint.', 'wp-dialyra' ),
					'response_status_code' => $status_code,
				)
			);
		}

		if ( 0 === $status_code || 'network_error' === $response->get_error_type() ) {
			return $this->failure_state( $base, 'network_error', $error_code, __( 'Dialyra could not reach this WordPress site. Check DNS, public URL, local development URL, firewall, or hosting network rules.', 'wp-dialyra' ), $status_code );
		}

		if ( 404 === $status_code || false !== strpos( $text, 'rest_no_route' ) ) {
			return $this->failure_state( $base, 'route_missing', $error_code, __( 'The Dialyra webhook REST route is not registered or the request method does not match.', 'wp-dialyra' ), $status_code );
		}

		if ( false !== strpos( $text, 'rest_disabled' ) ) {
			return $this->failure_state( $base, 'authentication_blocked', 'rest_disabled', __( 'The WordPress REST API is being blocked before the Dialyra webhook route executes. Check security plugins, theme code, MU plugins, or hosting security settings.', 'wp-dialyra' ), $status_code );
		}

		if ( in_array( $status_code, array( 401, 403, 406 ), true ) ) {
			return $this->failure_state( $base, 'blocked', $error_code, __( 'The webhook request is blocked before Dialyra can process it. Check security plugins, WAF, CDN, or hosting firewall settings.', 'wp-dialyra' ), $status_code );
		}

		if ( false !== strpos( $text, 'signature' ) || false !== strpos( $text, 'hmac' ) ) {
			return $this->failure_state( $base, 'signature_error', $error_code, __( 'Dialyra reached WordPress, but the webhook signature was rejected. Reconcile the webhook subscription.', 'wp-dialyra' ), $status_code );
		}

		return $this->failure_state( $base, 'delivery_failed', $error_code, $message, $status_code );
	}

	/**
	 * Build a normalized failure state.
	 *
	 * @param array  $base        Base state.
	 * @param string $status      Health status.
	 * @param string $error_code  Error code.
	 * @param string $message     Error message.
	 * @param int    $status_code HTTP status code.
	 * @return array
	 */
	private function failure_state( $base, $status, $error_code, $message, $status_code ) {
		return array_merge(
			$base,
			array(
				'status'               => $status,
				'healthy'              => false,
				'last_error_code'      => sanitize_key( $error_code ? $error_code : $status ),
				'last_error_message'   => sanitize_text_field( $message ),
				'response_status_code' => absint( $status_code ),
			)
		);
	}

	/**
	 * Persist health state.
	 *
	 * @param array $state Health state.
	 * @return array
	 */
	private function persist( $state ) {
		$state = array_merge(
			self::get_default_status(),
			$state,
			array(
				'last_checked_at' => current_time( 'mysql' ),
			)
		);

		if ( defined( 'WP_DIALYRA_OPTION_WEBHOOK_HEALTH' ) ) {
			update_option( WP_DIALYRA_OPTION_WEBHOOK_HEALTH, $state, false );
		}

		return $state;
	}

	/**
	 * Get locally stored webhook data.
	 *
	 * @return array
	 */
	private function get_stored_webhook_data() {
		$data = defined( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA' ) ? get_option( WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA, array() ) : array();

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Resolve webhook URL.
	 *
	 * @param array $webhook_data Webhook data.
	 * @return string
	 */
	private function get_webhook_url( $webhook_data ) {
		if ( ! empty( $webhook_data['webhook_url'] ) ) {
			return esc_url_raw( $webhook_data['webhook_url'] );
		}

		if ( ! empty( $webhook_data['url'] ) ) {
			return esc_url_raw( $webhook_data['url'] );
		}

		return class_exists( 'Dialyra_Webhook_Controller' ) ? Dialyra_Webhook_Controller::get_endpoint_url() : rest_url( 'dialyra/v1/webhooks/call-events' );
	}

	/**
	 * Extract error code from response data.
	 *
	 * @param array|null  $data       Response data.
	 * @param string|null $error_type Response error type.
	 * @return string
	 */
	private function extract_error_code( $data, $error_type ) {
		if ( is_array( $data ) ) {
			if ( ! empty( $data['code'] ) ) {
				return sanitize_key( $data['code'] );
			}

			if ( ! empty( $data['error'] ) && is_string( $data['error'] ) ) {
				return sanitize_key( $data['error'] );
			}
		}

		return sanitize_key( (string) $error_type );
	}

	/**
	 * Detect a failed delivery reported inside a successful API envelope.
	 *
	 * @param array|null $data Response data.
	 * @param string     $text Searchable response text.
	 * @return bool
	 */
	private function response_reports_failed_delivery( $data, $text ) {
		if ( is_array( $data ) ) {
			foreach ( array( 'delivered', 'success', 'ok' ) as $key ) {
				if ( array_key_exists( $key, $data ) && false === (bool) $data[ $key ] ) {
					return true;
				}
			}

			foreach ( array( 'delivery', 'result', 'test', 'response' ) as $key ) {
				if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) && $this->response_reports_failed_delivery( $data[ $key ], $text ) ) {
					return true;
				}
			}
		}

		return false !== strpos( $text, 'delivery_failed' )
			|| false !== strpos( $text, 'failed delivery' )
			|| false !== strpos( $text, 'request failed' );
	}

	/**
	 * Extract a useful delivery message from nested API data.
	 *
	 * @param array|null $data     Response data.
	 * @param string     $fallback Fallback message.
	 * @return string
	 */
	private function extract_delivery_message( $data, $fallback ) {
		if ( is_array( $data ) ) {
			foreach ( array( 'message', 'error', 'body' ) as $key ) {
				if ( ! empty( $data[ $key ] ) && is_string( $data[ $key ] ) ) {
					return $data[ $key ];
				}
			}

			foreach ( array( 'delivery', 'result', 'test', 'response' ) as $key ) {
				if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
					$message = $this->extract_delivery_message( $data[ $key ], '' );

					if ( '' !== $message ) {
						return $message;
					}
				}
			}
		}

		return $fallback ? $fallback : __( 'Dialyra could not deliver a webhook test to this site.', 'wp-dialyra' );
	}

	/**
	 * Default health state.
	 *
	 * @return array
	 */
	private static function get_default_status() {
		return array(
			'status'               => 'unknown',
			'healthy'              => false,
			'last_checked_at'      => '',
			'last_error_code'      => '',
			'last_error_message'   => '',
			'webhook_url'          => class_exists( 'Dialyra_Webhook_Controller' ) ? Dialyra_Webhook_Controller::get_endpoint_url() : '',
			'webhook_id'           => 0,
			'business_id'          => 0,
			'response_status_code' => 0,
		);
	}
}
