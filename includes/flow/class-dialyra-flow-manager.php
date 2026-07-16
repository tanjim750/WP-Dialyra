<?php

/**
 * Dialyra Flow Manager.
 *
 * Coordinates flow API operations and local default flow state.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/flow
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'WP_DIALYRA_OPTION_DEFAULT_FLOW_ID' ) ) {
	require_once dirname( __DIR__ ) . '/constant.php';
}

class Dialyra_Flow_Manager {

	const DEFAULT_FLOW_ID_OPTION   = WP_DIALYRA_OPTION_DEFAULT_FLOW_ID;
	const DEFAULT_FLOW_DATA_OPTION = WP_DIALYRA_OPTION_DEFAULT_FLOW_DATA;

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
	 * Create a Dialyra flow.
	 *
	 * @since    1.0.0
	 * @param    array    $flow_data    Flow data.
	 * @return   Dialyra_API_Response
	 */
	public function create_flow( $flow_data ) {
		return $this->api_endpoints->create_flow( $this->prepare_flow_payload( $flow_data ) );
	}

	/**
	 * Create and publish a Dialyra flow.
	 *
	 * @since    1.0.0
	 * @param    array    $flow_data    Flow, nodes, and edges payload.
	 * @return   Dialyra_API_Response
	 */
	public function create_and_publish_flow( $flow_data ) {
		return $this->api_endpoints->create_and_publish_flow( $this->prepare_create_and_publish_payload( $flow_data ) );
	}

	/**
	 * Get flows for the connected business.
	 *
	 * @since    1.0.0
	 * @param    array    $query_params    Optional query parameters.
	 * @return   Dialyra_API_Response
	 */
	public function get_flows( $query_params = array() ) {
		return $this->api_endpoints->get_flows( $this->prepare_flow_query_params( $query_params ) );
	}

	/**
	 * Get a flow by ID.
	 *
	 * @since    1.0.0
	 * @param    int    $flow_id    Flow ID.
	 * @return   Dialyra_API_Response
	 */
	public function get_flow( $flow_id ) {
		return $this->api_endpoints->get_flow( $flow_id );
	}

	/**
	 * Update a flow.
	 *
	 * @since    1.0.0
	 * @param    int      $flow_id      Flow ID.
	 * @param    array    $flow_data    Flow fields to update.
	 * @return   Dialyra_API_Response
	 */
	public function update_flow( $flow_id, $flow_data ) {
		$response = $this->api_endpoints->update_flow( $flow_id, $this->prepare_flow_payload( $flow_data ) );

		if ( $response->is_successful() && absint( $flow_id ) === $this->get_default_flow_id() ) {
			$this->save_default_flow_from_response( $response );
		}

		return $response;
	}

	/**
	 * Delete a flow.
	 *
	 * @since    1.0.0
	 * @param    int    $flow_id    Flow ID.
	 * @return   Dialyra_API_Response
	 */
	public function delete_flow( $flow_id ) {
		$response = $this->api_endpoints->delete_flow( $flow_id );

		if ( $response->is_successful() && absint( $flow_id ) === $this->get_default_flow_id() ) {
			$this->clear_default_flow();
		}

		return $response;
	}

	/**
	 * Validate a flow.
	 *
	 * @since    1.0.0
	 * @param    int    $flow_id    Flow ID.
	 * @return   Dialyra_API_Response
	 */
	public function validate_flow( $flow_id ) {
		return $this->api_endpoints->validate_flow( $flow_id );
	}

	/**
	 * Publish a flow.
	 *
	 * @since    1.0.0
	 * @param    int    $flow_id    Flow ID.
	 * @return   Dialyra_API_Response
	 */
	public function publish_flow( $flow_id ) {
		$response = $this->api_endpoints->publish_flow( $flow_id );

		if ( $response->is_successful() && absint( $flow_id ) === $this->get_default_flow_id() ) {
			$this->save_default_flow_from_response( $response );
		}

		return $response;
	}

	/**
	 * Duplicate a flow.
	 *
	 * @since    1.0.0
	 * @param    int      $flow_id      Flow ID.
	 * @param    array    $flow_data    Optional duplicate overrides.
	 * @return   Dialyra_API_Response
	 */
	public function duplicate_flow( $flow_id, $flow_data = array() ) {
		return $this->api_endpoints->duplicate_flow( $flow_id, $this->prepare_flow_payload( $flow_data ) );
	}

	/**
	 * Create a flow node.
	 *
	 * @since    1.0.0
	 * @param    int      $flow_id      Flow ID.
	 * @param    array    $node_data    Node data.
	 * @return   Dialyra_API_Response
	 */
	public function create_flow_node( $flow_id, $node_data ) {
		return $this->api_endpoints->create_flow_node( $flow_id, $this->sanitize_payload( $node_data ) );
	}

	/**
	 * Get flow nodes.
	 *
	 * @since    1.0.0
	 * @param    int    $flow_id    Flow ID.
	 * @return   Dialyra_API_Response
	 */
	public function get_flow_nodes( $flow_id ) {
		return $this->api_endpoints->get_flow_nodes( $flow_id );
	}

	/**
	 * Get a flow node.
	 *
	 * @since    1.0.0
	 * @param    int    $node_id    Node ID.
	 * @return   Dialyra_API_Response
	 */
	public function get_flow_node( $node_id ) {
		return $this->api_endpoints->get_flow_node( $node_id );
	}

	/**
	 * Update a flow node.
	 *
	 * @since    1.0.0
	 * @param    int      $node_id      Node ID.
	 * @param    array    $node_data    Node fields to update.
	 * @return   Dialyra_API_Response
	 */
	public function update_flow_node( $node_id, $node_data ) {
		return $this->api_endpoints->update_flow_node( $node_id, $this->sanitize_payload( $node_data ) );
	}

	/**
	 * Delete a flow node.
	 *
	 * @since    1.0.0
	 * @param    int    $node_id    Node ID.
	 * @return   Dialyra_API_Response
	 */
	public function delete_flow_node( $node_id ) {
		return $this->api_endpoints->delete_flow_node( $node_id );
	}

	/**
	 * Create a flow edge.
	 *
	 * @since    1.0.0
	 * @param    int      $flow_id      Flow ID.
	 * @param    array    $edge_data    Edge data.
	 * @return   Dialyra_API_Response
	 */
	public function create_flow_edge( $flow_id, $edge_data ) {
		return $this->api_endpoints->create_flow_edge( $flow_id, $this->sanitize_payload( $edge_data ) );
	}

	/**
	 * Get flow edges.
	 *
	 * @since    1.0.0
	 * @param    int    $flow_id    Flow ID.
	 * @return   Dialyra_API_Response
	 */
	public function get_flow_edges( $flow_id ) {
		return $this->api_endpoints->get_flow_edges( $flow_id );
	}

	/**
	 * Update a flow edge.
	 *
	 * @since    1.0.0
	 * @param    int      $edge_id      Edge ID.
	 * @param    array    $edge_data    Edge fields to update.
	 * @return   Dialyra_API_Response
	 */
	public function update_flow_edge( $edge_id, $edge_data ) {
		return $this->api_endpoints->update_flow_edge( $edge_id, $this->sanitize_payload( $edge_data ) );
	}

	/**
	 * Delete a flow edge.
	 *
	 * @since    1.0.0
	 * @param    int    $edge_id    Edge ID.
	 * @return   Dialyra_API_Response
	 */
	public function delete_flow_edge( $edge_id ) {
		return $this->api_endpoints->delete_flow_edge( $edge_id );
	}

	/**
	 * Set the local default flow after fetching it from Dialyra.
	 *
	 * @since    1.0.0
	 * @param    int    $flow_id    Flow ID.
	 * @return   Dialyra_API_Response|false
	 */
	public function set_default_flow( $flow_id ) {
		$flow_id = absint( $flow_id );

		if ( ! $flow_id ) {
			return false;
		}

		$response = $this->get_flow( $flow_id );

		if ( $response->is_successful() ) {
			$this->save_default_flow_from_response( $response );
		}

		return $response;
	}

	/**
	 * Save local default flow data.
	 *
	 * @since    1.0.0
	 * @param    array    $flow_data    Flow data.
	 * @return   bool
	 */
	public function save_default_flow_data( $flow_data ) {
		$flow_data = $this->sanitize_payload( $flow_data );

		if ( empty( $flow_data['id'] ) ) {
			return false;
		}

		update_option( self::DEFAULT_FLOW_ID_OPTION, absint( $flow_data['id'] ), false );

		return update_option( self::DEFAULT_FLOW_DATA_OPTION, $flow_data, false );
	}

	/**
	 * Get local default flow ID.
	 *
	 * @since    1.0.0
	 * @return   int
	 */
	public function get_default_flow_id() {
		return absint( get_option( self::DEFAULT_FLOW_ID_OPTION, 0 ) );
	}

	/**
	 * Get local default flow data.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function get_default_flow_data() {
		$flow_data = get_option( self::DEFAULT_FLOW_DATA_OPTION, array() );

		return is_array( $flow_data ) ? $flow_data : array();
	}

	/**
	 * Check whether a default flow is selected locally.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function has_default_flow() {
		return (bool) $this->get_default_flow_id();
	}

	/**
	 * Clear local default flow state.
	 *
	 * @since    1.0.0
	 */
	public function clear_default_flow() {
		delete_option( self::DEFAULT_FLOW_ID_OPTION );
		delete_option( self::DEFAULT_FLOW_DATA_OPTION );
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
	 * Prepare flow query parameters.
	 *
	 * @since    1.0.0
	 * @param    array    $query_params    Query parameters.
	 * @return   array
	 */
	private function prepare_flow_query_params( $query_params ) {
		$query_params = $this->sanitize_payload( is_array( $query_params ) ? $query_params : array() );

		if ( empty( $query_params['business_id'] ) ) {
			$business_id = $this->get_connected_business_id();

			if ( $business_id ) {
				$query_params['business_id'] = $business_id;
			}
		}

		return $query_params;
	}

	/**
	 * Prepare a single flow payload.
	 *
	 * @since    1.0.0
	 * @param    array    $flow_data    Flow data.
	 * @return   array
	 */
	private function prepare_flow_payload( $flow_data ) {
		$flow_data = $this->sanitize_payload( is_array( $flow_data ) ? $flow_data : array() );

		if ( empty( $flow_data['business_id'] ) ) {
			$business_id = $this->get_connected_business_id();

			if ( $business_id ) {
				$flow_data['business_id'] = $business_id;
			}
		}

		return $flow_data;
	}

	/**
	 * Prepare a create-and-publish flow payload.
	 *
	 * @since    1.0.0
	 * @param    array    $flow_data    Flow, nodes, and edges payload.
	 * @return   array
	 */
	private function prepare_create_and_publish_payload( $flow_data ) {
		$flow_data = $this->sanitize_payload( is_array( $flow_data ) ? $flow_data : array() );

		if ( empty( $flow_data['flow'] ) || ! is_array( $flow_data['flow'] ) ) {
			$flow_data['flow'] = array();
		}

		if ( empty( $flow_data['flow']['business_id'] ) ) {
			$business_id = $this->get_connected_business_id();

			if ( $business_id ) {
				$flow_data['flow']['business_id'] = $business_id;
			}
		}

		return $flow_data;
	}

	/**
	 * Save default flow data from an API response.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   bool
	 */
	private function save_default_flow_from_response( Dialyra_API_Response $response ) {
		$flow_data = $this->extract_response_data( $response );

		if ( empty( $flow_data['id'] ) ) {
			return false;
		}

		return $this->save_default_flow_data( $flow_data );
	}

	/**
	 * Extract useful data from a Dialyra response.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_API_Response    $response    API response.
	 * @return   array
	 */
	private function extract_response_data( Dialyra_API_Response $response ) {
		$data = $response->get_data();

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$data = $data['data'];
		}

		if ( isset( $data['flow'] ) && is_array( $data['flow'] ) ) {
			return $data['flow'];
		}

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Sanitize payload recursively.
	 *
	 * @since    1.0.0
	 * @param    mixed    $payload    Payload value.
	 * @return   mixed
	 */
	private function sanitize_payload( $payload ) {
		if ( is_array( $payload ) ) {
			$sanitized = array();

			foreach ( $payload as $key => $value ) {
				$sanitized_key               = is_string( $key ) ? sanitize_key( $key ) : $key;
				$sanitized[ $sanitized_key ] = $this->sanitize_payload( $value );
			}

			return $sanitized;
		}

		if ( is_bool( $payload ) || is_int( $payload ) || is_float( $payload ) || is_null( $payload ) ) {
			return $payload;
		}

		return sanitize_text_field( wp_unslash( $payload ) );
	}
}
