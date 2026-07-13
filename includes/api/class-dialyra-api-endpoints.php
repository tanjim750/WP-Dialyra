<?php

/**
 * Dialyra API Endpoints.
 *
 * Exposes endpoint-specific methods for the Dialyra API.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/api
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Dialyra_API_Endpoints {

    /**
     * The API client object.
     *
     * @since    1.0.0
     * @access   private
     * @var      Dialyra_API_Client    $client    The API client object.
     */
    private $client;

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    Dialyra_API_Client    $client    The API client object.
     */
    public function __construct( Dialyra_API_Client $client ) {
        $this->client = $client;
    }

    // Authentication

    /**
     * Authenticate a user and get an access token.
     *
     * @since    1.0.0
     * @param    string    $email       User's email address.
     * @param    string    $password    User's password.
     * @return   Dialyra_API_Response  The API response containing token or error.
     */
    public function login( $email, $password ) {
        return $this->client->post( 'auth/login', array(
            'email'    => sanitize_email( $email ),
            'password' => $password,
        ), false ); // No authentication required for login.
    }

    /**
     * Refresh access token using a refresh token.
     *
     * @since    1.0.0
     * @param    string    $refresh_token    The refresh token.
     * @return   Dialyra_API_Response  The API response.
     */
    public function refresh_token( $refresh_token ) {
        return $this->client->post( 'auth/refresh', array(
            'refresh_token' => sanitize_text_field( $refresh_token ),
        ), false );
    }

    /**
     * Log out the current user and revoke the refresh token.
     *
     * @since    1.0.0
     * @param    string    $refresh_token    The refresh token to revoke.
     * @return   Dialyra_API_Response  The API response.
     */
    public function logout( $refresh_token ) {
        return $this->client->post( 'auth/logout', array(
            'refresh_token' => sanitize_text_field( $refresh_token ),
        ) );
    }

    /**
     * Get information about the currently authenticated user and business.
     *
     * @since    1.0.0
     * @return   Dialyra_API_Response  The API response.
     */
    public function get_me() {
        return $this->client->get( 'auth/me' );
    }

    /**
     * Create a new user.
     *
     * @since    1.0.0
     * @param    array     $user_data    User data including full_name, email, password, role, etc.
     * @return   Dialyra_API_Response  The API response.
     */
    public function create_user( $user_data ) {
        return $this->client->post( 'auth/users', $user_data );
    }

    /**
     * Get a list of users.
     *
     * @since    1.0.0
     * @return   Dialyra_API_Response  The API response.
     */
    public function get_users() {
        return $this->client->get( 'auth/users' );
    }

    /**
     * Add a user to a business membership.
     *
     * @since    1.0.0
     * @param    int       $user_id          The ID of the user.
     * @param    int       $business_id      The ID of the business.
     * @param    string    $membership_role  The role in the membership.
     * @param    string    $status           The status of the membership.
     * @return   Dialyra_API_Response  The API response.
     */
    public function add_user_membership( $user_id, $business_id, $membership_role, $status ) {
        return $this->client->post( 'auth/users/' . absint( $user_id ) . '/membership', array(
            'business_id'     => absint( $business_id ),
            'membership_role' => sanitize_text_field( $membership_role ),
            'status'          => sanitize_text_field( $status ),
        ) );
    }

    /**
     * Update a user's business membership.
     *
     * @since    1.0.0
     * @param    int       $user_id          The ID of the user.
     * @param    int       $business_id      The ID of the business.
     * @param    string    $membership_role  Optional. The new role in the membership.
     * @param    string    $status           Optional. The new status of the membership.
     * @return   Dialyra_API_Response  The API response.
     */
    public function update_user_membership( $user_id, $business_id, $membership_role = null, $status = null ) {
        $body = array(
            'business_id' => absint( $business_id ),
        );
        if ( ! is_null( $membership_role ) ) {
            $body['membership_role'] = sanitize_text_field( $membership_role );
        }
        if ( ! is_null( $status ) ) {
            $body['status'] = sanitize_text_field( $status );
        }
        return $this->client->put( 'auth/users/' . absint( $user_id ) . '/membership', $body );
    }

    /**
     * Delete a user's business membership.
     *
     * @since    1.0.0
     * @param    int       $user_id      The ID of the user.
     * @param    int       $business_id  The ID of the business.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_user_membership( $user_id, $business_id ) {
        return $this->client->delete( 'auth/users/' . absint( $user_id ) . '/membership', array(
            'business_id' => absint( $business_id ),
        ) );
    }

    // Dashboard

    /**
     * Get dashboard data.
     *
     * @since    1.0.0
     * @return   Dialyra_API_Response  The API response containing dashboard data or error.
     */
    public function get_dashboard_data() {
        return $this->client->get( 'dashboard' );
    }

    // Business

    /**
     * Create a business.
     *
     * @since    1.0.0
     * @param    array     $business_data    Business fields.
     * @return   Dialyra_API_Response  The API response containing business data or error.
     */
    public function create_business( $business_data ) {
        return $this->client->post( 'businesses', $this->sanitize_business_payload( $business_data ) );
    }

    /**
     * Get businesses available to the authenticated user.
     *
     * @since    1.0.0
     * @param    array     $query_params    Optional query parameters.
     * @return   Dialyra_API_Response  The API response containing businesses data or error.
     */
    public function get_businesses( $query_params = array() ) {
        return $this->client->get( 'businesses', $this->sanitize_payload( $query_params ) );
    }

    /**
     * Get a specific business by ID.
     *
     * @since    1.0.0
     * @param    int       $business_id    The business ID.
     * @return   Dialyra_API_Response  The API response containing business data or error.
     */
    public function get_business( $business_id ) {
        return $this->client->get( 'businesses/' . absint( $business_id ) );
    }

    /**
     * Update a business.
     *
     * @since    1.0.0
     * @param    int       $business_id      The business ID.
     * @param    array     $business_data    Business fields to update.
     * @return   Dialyra_API_Response  The API response containing business data or error.
     */
    public function update_business( $business_id, $business_data ) {
        return $this->client->put( 'businesses/' . absint( $business_id ), $this->sanitize_business_payload( $business_data ) );
    }

    /**
     * Delete a business.
     *
     * @since    1.0.0
     * @param    int       $business_id    The business ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_business( $business_id ) {
        return $this->client->delete( 'businesses/' . absint( $business_id ) );
    }

    /**
     * Get business settings.
     *
     * @since    1.0.0
     * @param    int       $business_id    The business ID.
     * @return   Dialyra_API_Response  The API response containing settings data or error.
     */
    public function get_business_settings( $business_id ) {
        return $this->client->get( 'businesses/' . absint( $business_id ) . '/settings' );
    }

    /**
     * Update business settings.
     *
     * @since    1.0.0
     * @param    int       $business_id    The business ID.
     * @param    array     $settings       Business settings.
     * @return   Dialyra_API_Response  The API response containing settings data or error.
     */
    public function update_business_settings( $business_id, $settings ) {
        return $this->client->put( 'businesses/' . absint( $business_id ) . '/settings', array(
            'settings' => $this->sanitize_payload( $settings ),
        ) );
    }

    /**
     * Get inbound call config for a business.
     *
     * @since    1.0.0
     * @param    int       $business_id    The business ID.
     * @return   Dialyra_API_Response  The API response containing inbound call config data or error.
     */
    public function get_business_inbound_call_config( $business_id ) {
        return $this->client->get( 'businesses/' . absint( $business_id ) . '/inbound-call-config' );
    }

    /**
     * Create inbound call config for a business.
     *
     * @since    1.0.0
     * @param    int       $business_id    The business ID.
     * @param    array     $config_data    Inbound call config fields.
     * @return   Dialyra_API_Response  The API response containing inbound call config data or error.
     */
    public function create_business_inbound_call_config( $business_id, $config_data ) {
        return $this->client->post( 'businesses/' . absint( $business_id ) . '/inbound-call-config', $this->sanitize_inbound_call_config_payload( $config_data ) );
    }

    /**
     * Update inbound call config for a business.
     *
     * @since    1.0.0
     * @param    int       $business_id    The business ID.
     * @param    array     $config_data    Inbound call config fields.
     * @return   Dialyra_API_Response  The API response containing inbound call config data or error.
     */
    public function update_business_inbound_call_config( $business_id, $config_data ) {
        return $this->client->put( 'businesses/' . absint( $business_id ) . '/inbound-call-config', $this->sanitize_inbound_call_config_payload( $config_data ) );
    }

    /**
     * Add a business member.
     *
     * @since    1.0.0
     * @param    int       $business_id    The business ID.
     * @param    int       $user_id        The user ID.
     * @param    string    $role           Member role.
     * @param    string    $status         Member status.
     * @return   Dialyra_API_Response  The API response containing member data or error.
     */
    public function add_business_member( $business_id, $user_id, $role, $status = 'active' ) {
        return $this->client->post( 'businesses/' . absint( $business_id ) . '/members', array(
            'user_id' => absint( $user_id ),
            'role'    => sanitize_text_field( $role ),
            'status'  => sanitize_text_field( $status ),
        ) );
    }

    /**
     * Get business members.
     *
     * @since    1.0.0
     * @param    int       $business_id    The business ID.
     * @return   Dialyra_API_Response  The API response containing members data or error.
     */
    public function get_business_members( $business_id ) {
        return $this->client->get( 'businesses/' . absint( $business_id ) . '/members' );
    }

    /**
     * Update a business member.
     *
     * @since    1.0.0
     * @param    int       $business_id    The business ID.
     * @param    int       $member_id      The member ID.
     * @param    array     $member_data    Member fields to update.
     * @return   Dialyra_API_Response  The API response containing member data or error.
     */
    public function update_business_member( $business_id, $member_id, $member_data ) {
        return $this->client->put(
            'businesses/' . absint( $business_id ) . '/members/' . absint( $member_id ),
            $this->sanitize_allowed_payload( $member_data, array( 'role', 'status' ) )
        );
    }

    /**
     * Delete a business member.
     *
     * @since    1.0.0
     * @param    int       $business_id    The business ID.
     * @param    int       $member_id      The member ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_business_member( $business_id, $member_id ) {
        return $this->client->delete( 'businesses/' . absint( $business_id ) . '/members/' . absint( $member_id ) );
    }

    /**
     * Get business information.
     *
     * @since    1.0.0
     * @return   Dialyra_API_Response  The API response containing business data or error.
     */
    public function get_business_info() {
        return $this->client->get( 'business' );
    }

    // Access Tokens

    /**
     * Create an access token for a business.
     *
     * @since    1.0.0
     * @param    array     $token_data    Access token fields.
     * @return   Dialyra_API_Response  The API response containing token data or error.
     */
    public function create_access_token( $token_data ) {
        return $this->client->post( 'access-tokens', $this->sanitize_access_token_payload( $token_data ) );
    }

    /**
     * Get access tokens.
     *
     * @since    1.0.0
     * @param    array     $query_params    Optional query parameters.
     * @return   Dialyra_API_Response  The API response containing token list data or error.
     */
    public function get_access_tokens( $query_params = array() ) {
        return $this->client->get( 'access-tokens', $this->sanitize_payload( $query_params ) );
    }

    /**
     * Get a specific access token.
     *
     * @since    1.0.0
     * @param    int       $token_id    Access token ID.
     * @return   Dialyra_API_Response  The API response containing token data or error.
     */
    public function get_access_token( $token_id ) {
        return $this->client->get( 'access-tokens/' . absint( $token_id ) );
    }

    /**
     * Revoke an access token.
     *
     * @since    1.0.0
     * @param    int       $token_id    Access token ID.
     * @return   Dialyra_API_Response  The API response containing revoked token data or error.
     */
    public function revoke_access_token( $token_id ) {
        return $this->client->post( 'access-tokens/' . absint( $token_id ) . '/revoke' );
    }

    /**
     * Delete an access token.
     *
     * @since    1.0.0
     * @param    int       $token_id    Access token ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_access_token( $token_id ) {
        return $this->client->delete( 'access-tokens/' . absint( $token_id ) );
    }

    // Agents

    /**
     * Get a list of agents.
     *
     * @since    1.0.0
     * @return   Dialyra_API_Response  The API response containing agents data or error.
     */
    public function get_agents() {
        return $this->client->get( 'agents' );
    }

    // Flows

    /**
     * Create a draft flow.
     *
     * @since    1.0.0
     * @return   Dialyra_API_Response  The API response containing flows data or error.
     */
    public function create_flow( $flow_data ) {
        return $this->client->post( 'flows', $this->sanitize_flow_payload( $flow_data ) );
    }

    /**
     * Create and publish a complete flow in one request.
     *
     * @since    1.0.0
     * @param    array     $flow_data    Full flow payload with flow, nodes, and edges.
     * @return   Dialyra_API_Response  The API response containing published flow data or error.
     */
    public function create_and_publish_flow( $flow_data ) {
        return $this->client->post( 'flows/create-and-publish', $this->sanitize_payload( $flow_data ) );
    }

    /**
     * Get a list of flows.
     *
     * @since    1.0.0
     * @param    array     $query_params    Optional query params, e.g. business_id and status.
     * @return   Dialyra_API_Response  The API response containing flows data or error.
     */
    public function get_flows( $query_params = array() ) {
        return $this->client->get( 'flows', $this->sanitize_payload( $query_params ) );
    }

    /**
     * Get a specific flow by ID.
     *
     * @since    1.0.0
     * @param    string    $flow_id    The ID of the flow.
     * @return   Dialyra_API_Response  The API response containing flow data or error.
     */
    public function get_flow( $flow_id ) {
        return $this->client->get( 'flows/' . absint( $flow_id ) );
    }

    /**
     * Update a draft flow.
     *
     * @since    1.0.0
     * @param    int       $flow_id      The flow ID.
     * @param    array     $flow_data    Flow fields to update.
     * @return   Dialyra_API_Response  The API response containing flow data or error.
     */
    public function update_flow( $flow_id, $flow_data ) {
        return $this->client->put( 'flows/' . absint( $flow_id ), $this->sanitize_flow_payload( $flow_data ) );
    }

    /**
     * Archive a flow.
     *
     * @since    1.0.0
     * @param    int       $flow_id    The flow ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_flow( $flow_id ) {
        return $this->client->delete( 'flows/' . absint( $flow_id ) );
    }

    /**
     * Validate a flow.
     *
     * @since    1.0.0
     * @param    int       $flow_id    The flow ID.
     * @return   Dialyra_API_Response  The API response containing validation data or error.
     */
    public function validate_flow( $flow_id ) {
        return $this->client->post( 'flows/' . absint( $flow_id ) . '/validate' );
    }

    /**
     * Publish a flow.
     *
     * @since    1.0.0
     * @param    int       $flow_id    The flow ID.
     * @return   Dialyra_API_Response  The API response containing published flow data or error.
     */
    public function publish_flow( $flow_id ) {
        return $this->client->post( 'flows/' . absint( $flow_id ) . '/publish' );
    }

    /**
     * Duplicate a flow.
     *
     * @since    1.0.0
     * @param    int       $flow_id      The source flow ID.
     * @param    array     $flow_data    Optional duplicate fields.
     * @return   Dialyra_API_Response  The API response containing duplicated flow data or error.
     */
    public function duplicate_flow( $flow_id, $flow_data = array() ) {
        return $this->client->post( 'flows/' . absint( $flow_id ) . '/duplicate', $this->sanitize_allowed_payload( $flow_data, array( 'name', 'description' ) ) );
    }

    /**
     * Create a flow node.
     *
     * @since    1.0.0
     * @param    int       $flow_id      The flow ID.
     * @param    array     $node_data    Flow node fields.
     * @return   Dialyra_API_Response  The API response containing node data or error.
     */
    public function create_flow_node( $flow_id, $node_data ) {
        return $this->client->post( 'flows/' . absint( $flow_id ) . '/nodes', $this->sanitize_flow_node_payload( $node_data ) );
    }

    /**
     * Get flow nodes.
     *
     * @since    1.0.0
     * @param    int       $flow_id    The flow ID.
     * @return   Dialyra_API_Response  The API response containing node data or error.
     */
    public function get_flow_nodes( $flow_id ) {
        return $this->client->get( 'flows/' . absint( $flow_id ) . '/nodes' );
    }

    /**
     * Get a flow node.
     *
     * @since    1.0.0
     * @param    int       $node_id    The node ID.
     * @return   Dialyra_API_Response  The API response containing node data or error.
     */
    public function get_flow_node( $node_id ) {
        return $this->client->get( 'flow-nodes/' . absint( $node_id ) );
    }

    /**
     * Update a flow node.
     *
     * @since    1.0.0
     * @param    int       $node_id      The node ID.
     * @param    array     $node_data    Node fields to update.
     * @return   Dialyra_API_Response  The API response containing node data or error.
     */
    public function update_flow_node( $node_id, $node_data ) {
        return $this->client->put( 'flow-nodes/' . absint( $node_id ), $this->sanitize_flow_node_payload( $node_data ) );
    }

    /**
     * Delete a flow node.
     *
     * @since    1.0.0
     * @param    int       $node_id    The node ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_flow_node( $node_id ) {
        return $this->client->delete( 'flow-nodes/' . absint( $node_id ) );
    }

    /**
     * Create a flow edge.
     *
     * @since    1.0.0
     * @param    int       $flow_id      The flow ID.
     * @param    array     $edge_data    Flow edge fields.
     * @return   Dialyra_API_Response  The API response containing edge data or error.
     */
    public function create_flow_edge( $flow_id, $edge_data ) {
        return $this->client->post( 'flows/' . absint( $flow_id ) . '/edges', $this->sanitize_flow_edge_payload( $edge_data ) );
    }

    /**
     * Get flow edges.
     *
     * @since    1.0.0
     * @param    int       $flow_id    The flow ID.
     * @return   Dialyra_API_Response  The API response containing edge data or error.
     */
    public function get_flow_edges( $flow_id ) {
        return $this->client->get( 'flows/' . absint( $flow_id ) . '/edges' );
    }

    /**
     * Update a flow edge.
     *
     * @since    1.0.0
     * @param    int       $edge_id      The edge ID.
     * @param    array     $edge_data    Edge fields to update.
     * @return   Dialyra_API_Response  The API response containing edge data or error.
     */
    public function update_flow_edge( $edge_id, $edge_data ) {
        return $this->client->put( 'flow-edges/' . absint( $edge_id ), $this->sanitize_flow_edge_payload( $edge_data ) );
    }

    /**
     * Delete a flow edge.
     *
     * @since    1.0.0
     * @param    int       $edge_id    The edge ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_flow_edge( $edge_id ) {
        return $this->client->delete( 'flow-edges/' . absint( $edge_id ) );
    }

    /**
     * Sanitize a business payload.
     *
     * @since    1.0.0
     * @param    array     $business_data    Raw business data.
     * @return   array
     */
    private function sanitize_business_payload( $business_data ) {
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

        if ( isset( $payload['email'] ) ) {
            $payload['email'] = sanitize_email( $payload['email'] );
        }

        return $payload;
    }

    /**
     * Sanitize an access token payload.
     *
     * @since    1.0.0
     * @param    array     $token_data    Raw token data.
     * @return   array
     */
    private function sanitize_access_token_payload( $token_data ) {
        $payload = $this->sanitize_allowed_payload( $token_data, array(
            'name',
            'business_id',
            'expires_days',
            'scopes',
        ) );

        if ( isset( $payload['business_id'] ) ) {
            $payload['business_id'] = absint( $payload['business_id'] );
        }

        if ( isset( $payload['expires_days'] ) ) {
            $payload['expires_days'] = absint( $payload['expires_days'] );
        }

        if ( isset( $payload['scopes'] ) && is_array( $payload['scopes'] ) ) {
            $payload['scopes'] = array_values( array_filter( array_map( 'sanitize_text_field', $payload['scopes'] ) ) );
        }

        return $payload;
    }

    /**
     * Sanitize an inbound call config payload.
     *
     * @since    1.0.0
     * @param    array     $config_data    Raw config data.
     * @return   array
     */
    private function sanitize_inbound_call_config_payload( $config_data ) {
        $payload = $this->sanitize_allowed_payload( $config_data, array(
            'enabled',
            'flow_id',
            'fallback_mode',
            'metadata',
        ) );

        if ( isset( $payload['flow_id'] ) ) {
            $payload['flow_id'] = absint( $payload['flow_id'] );
        }

        return $payload;
    }

    /**
     * Sanitize a flow payload.
     *
     * @since    1.0.0
     * @param    array     $flow_data    Raw flow data.
     * @return   array
     */
    private function sanitize_flow_payload( $flow_data ) {
        $payload = $this->sanitize_allowed_payload( $flow_data, array(
            'business_id',
            'name',
            'description',
            'status',
        ) );

        if ( isset( $payload['business_id'] ) ) {
            $payload['business_id'] = absint( $payload['business_id'] );
        }

        return $payload;
    }

    /**
     * Sanitize a flow node payload.
     *
     * @since    1.0.0
     * @param    array     $node_data    Raw node data.
     * @return   array
     */
    private function sanitize_flow_node_payload( $node_data ) {
        $payload = $this->sanitize_allowed_payload( $node_data, array(
            'node_key',
            'node_type',
            'name',
            'config',
            'position_x',
            'position_y',
            'is_start',
        ) );

        if ( isset( $payload['position_x'] ) ) {
            $payload['position_x'] = floatval( $payload['position_x'] );
        }

        if ( isset( $payload['position_y'] ) ) {
            $payload['position_y'] = floatval( $payload['position_y'] );
        }

        return $payload;
    }

    /**
     * Sanitize a flow edge payload.
     *
     * @since    1.0.0
     * @param    array     $edge_data    Raw edge data.
     * @return   array
     */
    private function sanitize_flow_edge_payload( $edge_data ) {
        $payload = $this->sanitize_allowed_payload( $edge_data, array(
            'source_node_id',
            'target_node_id',
            'source_node_key',
            'target_node_key',
            'condition_type',
            'condition_value',
            'priority',
            'label',
        ) );

        foreach ( array( 'source_node_id', 'target_node_id', 'priority' ) as $integer_field ) {
            if ( isset( $payload[ $integer_field ] ) ) {
                $payload[ $integer_field ] = absint( $payload[ $integer_field ] );
            }
        }

        return $payload;
    }

    /**
     * Sanitize payload fields and keep only allowed keys.
     *
     * @since    1.0.0
     * @param    array     $data             Raw payload data.
     * @param    array     $allowed_fields   Allowed field names.
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
     * Sanitize a payload recursively.
     *
     * @since    1.0.0
     * @param    mixed     $data    Raw payload data.
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
     * @param    mixed     $value    Raw payload value.
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
