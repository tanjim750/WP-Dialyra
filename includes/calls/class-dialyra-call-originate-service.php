<?php

/**
 * Dialyra call originate service.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/calls
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Call_Originate_Service {

	/**
	 * API endpoint service.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_API_Endpoints
	 */
	private $api_endpoints;

	/**
	 * Business manager.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Business_Manager
	 */
	private $business_manager;

	/**
	 * Flow resolver.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Flow_Resolver
	 */
	private $flow_resolver;

	/**
	 * Request builder.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Call_Request_Builder
	 */
	private $request_builder;

	/**
	 * Local call log repository.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Call_Log_Repository|null
	 */
	private $call_log_repository;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Endpoints          $api_endpoints      API endpoint service.
	 * @param    Dialyra_Business_Manager       $business_manager   Business manager.
	 * @param    Dialyra_Flow_Resolver          $flow_resolver      Flow resolver.
	 * @param    Dialyra_Call_Request_Builder   $request_builder    Request builder.
	 * @param    Dialyra_Call_Log_Repository|null $call_log_repository Local call log repository.
	 */
	public function __construct( Dialyra_API_Endpoints $api_endpoints, Dialyra_Business_Manager $business_manager, Dialyra_Flow_Resolver $flow_resolver, Dialyra_Call_Request_Builder $request_builder, $call_log_repository = null ) {
		$this->api_endpoints    = $api_endpoints;
		$this->business_manager = $business_manager;
		$this->flow_resolver    = $flow_resolver;
		$this->request_builder  = $request_builder;
		$this->call_log_repository = $call_log_repository;
	}

	/**
	 * Originate a Dialyra call for a WooCommerce order.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id          WooCommerce order ID.
	 * @param    array    $origin_context    Optional originate context.
	 * @return   Dialyra_API_Response
	 */
	public function originate_for_order( $order_id, $origin_context = array() ) {
		$order_id = absint( $order_id );
		$origin_context = is_array( $origin_context ) ? $origin_context : array();

		if ( ! function_exists( 'wc_get_order' ) ) {
			return $this->local_error( 'woocommerce_unavailable', __( 'WooCommerce is not available.', 'wp-dialyra' ) );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return $this->local_error( 'order_not_found', __( 'WooCommerce order was not found.', 'wp-dialyra' ) );
		}

		$business_id = $this->business_manager->get_connected_business_id();

		if ( ! $business_id ) {
			return $this->local_error( 'business_not_connected', __( 'No Dialyra business is connected.', 'wp-dialyra' ), $order_id );
		}

		$this->business_manager->ensure_site_access_token( $business_id );

		$token_data = $this->business_manager->get_site_access_token_data();
		$token      = ! empty( $token_data['token'] ) && absint( $token_data['business_id'] ?? 0 ) === $business_id ? sanitize_text_field( $token_data['token'] ) : '';

		if ( '' === $token ) {
			return $this->local_error( 'unauthenticated', __( 'Business access token missing.', 'wp-dialyra' ), $order_id );
		}

		if ( ! $this->has_originate_scope( $token_data ) ) {
			return $this->local_error( 'missing_scope', __( 'Business access token does not include the calls:originate scope.', 'wp-dialyra' ), $order_id );
		}

		$flow_result = $this->flow_resolver->resolve_for_order( $order_id, $business_id );

		if ( empty( $flow_result['success'] ) ) {
			return $this->local_error( $flow_result['error_type'] ?? 'flow_not_configured', $flow_result['message'] ?? __( 'No product-specific or default Dialyra flow is configured.', 'wp-dialyra' ), $order_id );
		}

		$request_result = $this->request_builder->build_order_call_request( $order, absint( $flow_result['flow_id'] ) );

		if ( empty( $request_result['success'] ) ) {
			return $this->local_error( $request_result['error_type'] ?? 'bad_request', $request_result['message'] ?? __( 'Call request could not be prepared.', 'wp-dialyra' ), $order_id );
		}

		$response = $this->api_endpoints->originate_call( $request_result['payload'], $token );
		$log_context = array(
			'business_id' => $business_id,
			'flow_id'     => absint( $flow_result['flow_id'] ?? 0 ),
			'phone'       => sanitize_text_field( $request_result['payload']['phone'] ?? '' ),
			'source'      => sanitize_key( $origin_context['source'] ?? 'originate' ),
		);

		if ( $response && $response->is_successful() ) {
			$this->save_success_meta( $order, $response );
			$this->log_originate_result( $order_id, $response, $log_context );
			do_action( Dialyra_Hook_Names::get_or_default( 'call', 'call_originated', 'dialyra_call_originated' ), $order_id, $response );

			return $response;
		}

		$this->save_failure_meta( $order, $response );
		$this->log_originate_result( $order_id, $response, $log_context );

		$status_code = $response ? absint( $response->get_status_code() ) : 0;

		if ( 401 === $status_code ) {
			do_action( Dialyra_Hook_Names::get_or_default( 'call', 'call_unauthorized', 'dialyra_call_unauthorized' ), $order_id, $response );

			return $response;
		}

		if ( 402 === $status_code ) {
			do_action( Dialyra_Hook_Names::get_or_default( 'call', 'call_billing_blocked', 'dialyra_call_billing_blocked' ), $order_id, $response );

			return $response;
		}

		if ( 400 === $status_code && $this->is_invalid_flow_response( $response ) ) {
			do_action( Dialyra_Hook_Names::get_or_default( 'call', 'call_invalid_flow', 'dialyra_call_invalid_flow' ), $order_id, $response, $this->build_flow_error_context( $business_id, $flow_result, $response ) );

			return $response;
		}

		if ( $response instanceof Dialyra_API_Response && empty( $origin_context['suppress_originate_error_hook'] ) ) {
			do_action( Dialyra_Hook_Names::get_or_default( 'call', 'call_originate_error', 'dialyra_call_originate_error' ), $order_id, $response, $this->build_flow_error_context( $business_id, $flow_result, $response ) );

			return $response;
		}

		do_action( Dialyra_Hook_Names::get_or_default( 'call', 'call_originate_failed', 'dialyra_call_originate_failed' ), $order_id, $response );

		return $response ? $response : $this->local_error( 'network_error', __( 'Call originate request failed.', 'wp-dialyra' ), $order_id );
	}

	/**
	 * Save minimal success call references to order meta.
	 *
	 * @since    1.0.0
	 * @param    WC_Order                $order       WooCommerce order.
	 * @param    Dialyra_API_Response    $response    API response.
	 */
	private function save_success_meta( $order, Dialyra_API_Response $response ) {
		$data = $this->extract_response_data( $response );

		if ( ! empty( $data['call_session_id'] ) ) {
			$order->update_meta_data( '_dialyra_last_call_session_id', absint( $data['call_session_id'] ) );
		}

		$order->update_meta_data( '_dialyra_last_call_status', sanitize_text_field( $data['status'] ?? 'initiated' ) );
		$order->update_meta_data( '_dialyra_last_call_at', current_time( 'mysql' ) );

		if ( method_exists( $order, 'save' ) ) {
			$order->save();
		}
	}

	/**
	 * Save minimal failure call state to order meta.
	 *
	 * @since    1.0.0
	 * @param    WC_Order                     $order       WooCommerce order.
	 * @param    Dialyra_API_Response|null    $response    API response.
	 */
	private function save_failure_meta( $order, $response ) {
		$order->update_meta_data( '_dialyra_last_call_status', 'originate_failed' );
		$order->update_meta_data( '_dialyra_last_call_at', current_time( 'mysql' ) );

		if ( $response instanceof Dialyra_API_Response ) {
			$order->update_meta_data( '_dialyra_last_call_error_type', sanitize_key( $response->get_error_type() ) );
		}

		if ( method_exists( $order, 'save' ) ) {
			$order->save();
		}
	}

	/**
	 * Extract normalized response data.
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

		return $data;
	}

	/**
	 * Check whether stored business token has originate scope.
	 *
	 * @since    1.0.0
	 * @param    array    $token_data    Stored token data.
	 * @return   bool
	 */
	private function has_originate_scope( $token_data ) {
		if ( empty( $token_data['scopes'] ) || ! is_array( $token_data['scopes'] ) ) {
			return true;
		}

		return in_array( 'calls:originate', array_map( 'sanitize_text_field', $token_data['scopes'] ), true );
	}

	/**
	 * Check whether a 400 response is the Dialyra invalid-flow failure.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   bool
	 */
	private function is_invalid_flow_response( Dialyra_API_Response $response ) {
		$message = strtoupper( $response->get_message() );
		$data    = $response->get_data();
		$data    = is_array( $data ) ? $data : array();

		if ( ! empty( $data['error'] ) ) {
			$message .= ' ' . strtoupper( sanitize_text_field( $data['error'] ) );
		}

		return false !== strpos( $message, 'INVALID_FLOW_ID' );
	}

	/**
	 * Build flow context for originate error hooks.
	 *
	 * @since    1.0.0
	 * @param    int      $business_id     Business ID.
	 * @param    array    $flow_result     Flow resolver result.
	 * @return   array
	 */
	private function build_flow_error_context( $business_id, $flow_result, $response = null ) {
		$flow_result = is_array( $flow_result ) ? $flow_result : array();
		$status_code = $response instanceof Dialyra_API_Response ? absint( $response->get_status_code() ) : 0;

		return array(
			'business_id' => absint( $business_id ),
			'flow_id'     => absint( $flow_result['flow_id'] ?? 0 ),
			'flow_source' => sanitize_key( $flow_result['source'] ?? '' ),
			'product_ids' => ! empty( $flow_result['product_ids'] ) && is_array( $flow_result['product_ids'] ) ? array_values( array_filter( array_unique( array_map( 'absint', $flow_result['product_ids'] ) ) ) ) : array(),
			'status_code' => $status_code,
			'error_type'  => $response instanceof Dialyra_API_Response ? sanitize_key( $response->get_error_type() ) : '',
			'message'     => $response instanceof Dialyra_API_Response ? sanitize_text_field( $response->get_message() ) : '',
		);
	}

	/**
	 * Build a local normalized API response and trigger failure hook when order is known.
	 *
	 * @since    1.0.0
	 * @param    string    $error_type    Error type.
	 * @param    string    $message       Message.
	 * @param    int       $order_id      Optional order ID.
	 * @return   Dialyra_API_Response
	 */
	private function local_error( $error_type, $message, $order_id = 0 ) {
		$response = new Dialyra_API_Response(
			array(
				'error'      => sanitize_text_field( $message ),
				'error_type' => sanitize_key( $error_type ),
			),
			400,
			sanitize_text_field( $message ),
			sanitize_key( $error_type )
		);

		if ( $order_id ) {
			$this->log_originate_result(
				absint( $order_id ),
				$response,
				array(
					'business_id' => class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0,
					'source'      => 'local_error',
				)
			);
			do_action( Dialyra_Hook_Names::get_or_default( 'call', 'call_originate_failed', 'dialyra_call_originate_failed' ), absint( $order_id ), $response );
		}

		return $response;
	}

	/**
	 * Persist an originate result to the local call log table.
	 *
	 * @since    1.0.0
	 * @param    int                       $order_id    WooCommerce order ID.
	 * @param    Dialyra_API_Response|null $response    API response.
	 * @param    array                     $context     Log context.
	 */
	private function log_originate_result( $order_id, $response, $context = array() ) {
		if ( ! $this->call_log_repository || ! method_exists( $this->call_log_repository, 'log_originate_result' ) ) {
			return;
		}

		$this->call_log_repository->log_originate_result( $order_id, $response, $context );
	}
}
