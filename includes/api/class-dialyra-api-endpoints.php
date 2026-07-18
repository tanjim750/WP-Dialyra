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
        return $this->client->post( 'auth/users', $this->sanitize_user_payload( $user_data ) );
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

    // Business Webhooks

    /**
     * Create a business webhook subscription.
     *
     * @since    1.0.0
     * @param    array     $webhook_data    Webhook subscription fields.
     * @return   Dialyra_API_Response  The API response.
     */
    public function create_business_webhook( $webhook_data ) {
        return $this->client->post( 'business-webhooks', $this->sanitize_business_webhook_payload( $webhook_data ) );
    }

    /**
     * Get business webhook subscriptions.
     *
     * @since    1.0.0
     * @param    array     $query_params    Optional query parameters.
     * @return   Dialyra_API_Response  The API response.
     */
    public function get_business_webhooks( $query_params = array() ) {
        return $this->client->get( 'business-webhooks', $this->sanitize_payload( $query_params ) );
    }

    /**
     * Update a business webhook subscription.
     *
     * @since    1.0.0
     * @param    int       $webhook_id      Webhook subscription ID.
     * @param    array     $webhook_data    Webhook subscription fields.
     * @return   Dialyra_API_Response  The API response.
     */
    public function update_business_webhook( $webhook_id, $webhook_data ) {
        return $this->client->put( 'business-webhooks/' . absint( $webhook_id ), $this->sanitize_business_webhook_payload( $webhook_data ) );
    }

    /**
     * Pause a business webhook subscription.
     *
     * @since    1.0.0
     * @param    int       $webhook_id    Webhook subscription ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function pause_business_webhook( $webhook_id ) {
        return $this->client->post( 'business-webhooks/' . absint( $webhook_id ) . '/pause' );
    }

    /**
     * Resume a business webhook subscription.
     *
     * @since    1.0.0
     * @param    int       $webhook_id    Webhook subscription ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function resume_business_webhook( $webhook_id ) {
        return $this->client->post( 'business-webhooks/' . absint( $webhook_id ) . '/resume' );
    }

    /**
     * Disable a business webhook subscription.
     *
     * @since    1.0.0
     * @param    int       $webhook_id    Webhook subscription ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_business_webhook( $webhook_id ) {
        return $this->client->delete( 'business-webhooks/' . absint( $webhook_id ) );
    }

    /**
     * Test a business webhook subscription.
     *
     * @since    1.0.0
     * @param    int       $webhook_id    Webhook subscription ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function test_business_webhook( $webhook_id ) {
        return $this->client->post( 'business-webhooks/' . absint( $webhook_id ) . '/test' );
    }

    /**
     * Originate a runtime call using a business access token.
     *
     * @since    1.0.0
     * @param    array     $payload         Originate payload.
     * @param    string    $access_token    Business access token.
     * @return   Dialyra_API_Response  The API response.
     */
    public function originate_call( $payload, $access_token ) {
        return $this->client->post_with_business_access_token_to_version( 'v3', 'runtime/calls/originate', $this->sanitize_call_originate_payload( $payload ), $access_token );
    }

    // Agents

    /**
     * Get a list of agents.
     *
     * @since    1.0.0
     * @param    array     $query_params    Optional query params, e.g. business_id.
     * @return   Dialyra_API_Response  The API response containing agents data or error.
     */
    public function get_agents( $query_params = array() ) {
        return $this->client->get( 'agents', $this->sanitize_payload( $query_params ) );
    }

    /**
     * Create an agent.
     *
     * @since    1.0.0
     * @param    array     $agent_data    Agent fields.
     * @return   Dialyra_API_Response  The API response containing agent data or error.
     */
    public function create_agent( $agent_data ) {
        return $this->client->post( 'agents', $this->sanitize_agent_payload( $agent_data ) );
    }

    /**
     * Get an agent.
     *
     * @since    1.0.0
     * @param    int       $agent_id    Agent ID.
     * @return   Dialyra_API_Response  The API response containing agent data or error.
     */
    public function get_agent( $agent_id ) {
        return $this->client->get( 'agents/' . absint( $agent_id ) );
    }

    /**
     * Update an agent profile.
     *
     * @since    1.0.0
     * @param    int       $agent_id      Agent ID.
     * @param    array     $agent_data    Agent profile fields.
     * @return   Dialyra_API_Response  The API response containing agent data or error.
     */
    public function update_agent( $agent_id, $agent_data ) {
        return $this->client->put( 'agents/' . absint( $agent_id ), $this->sanitize_agent_update_payload( $agent_data ) );
    }

    /**
     * Delete an agent.
     *
     * @since    1.0.0
     * @param    int       $agent_id    Agent ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_agent( $agent_id ) {
        return $this->client->delete( 'agents/' . absint( $agent_id ) );
    }

    /**
     * Update agent availability.
     *
     * @since    1.0.0
     * @param    int       $agent_id               Agent ID.
     * @param    string    $availability_status    Availability status.
     * @return   Dialyra_API_Response  The API response containing agent data or error.
     */
    public function update_agent_availability( $agent_id, $availability_status ) {
        return $this->client->post( 'agents/' . absint( $agent_id ) . '/availability', array(
            'availability_status' => sanitize_key( $availability_status ),
        ) );
    }

    /**
     * Create or update an agent SIP extension.
     *
     * @since    1.0.0
     * @param    array     $extension_data    Extension fields.
     * @return   Dialyra_API_Response  The API response containing extension data or error.
     */
    public function create_agent_extension( $extension_data ) {
        return $this->client->post( 'agents/extensions', $this->sanitize_agent_extension_payload( $extension_data ) );
    }

    /**
     * Get agent extensions.
     *
     * @since    1.0.0
     * @param    array     $query_params    Optional query params, e.g. business_id.
     * @return   Dialyra_API_Response  The API response containing extension data or error.
     */
    public function get_agent_extensions( $query_params = array() ) {
        return $this->client->get( 'agents/extensions', $this->sanitize_payload( $query_params ) );
    }

    /**
     * Get an agent extension row.
     *
     * @since    1.0.0
     * @param    int       $extension_id    Extension assignment ID.
     * @return   Dialyra_API_Response  The API response containing extension data or error.
     */
    public function get_agent_extension( $extension_id ) {
        return $this->client->get( 'agents/extensions/' . absint( $extension_id ) );
    }

    /**
     * Bind an extension row to an agent user.
     *
     * @since    1.0.0
     * @param    int       $extension_id    Extension assignment ID.
     * @param    array     $bind_data       Bind fields.
     * @return   Dialyra_API_Response  The API response containing extension data or error.
     */
    public function bind_agent_extension( $extension_id, $bind_data ) {
        return $this->client->post( 'agents/extensions/' . absint( $extension_id ) . '/bind', $this->sanitize_agent_extension_bind_payload( $bind_data ) );
    }

    /**
     * Update an extension row active state.
     *
     * @since    1.0.0
     * @param    int       $extension_id      Extension assignment ID.
     * @param    array     $extension_data    Extension fields.
     * @return   Dialyra_API_Response  The API response containing extension data or error.
     */
    public function update_agent_extension( $extension_id, $extension_data ) {
        return $this->client->put( 'agents/extensions/' . absint( $extension_id ), $this->sanitize_agent_extension_update_payload( $extension_data ) );
    }

    /**
     * Delete an extension row and realtime SIP identity.
     *
     * @since    1.0.0
     * @param    int       $extension_id    Extension assignment ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_agent_extension( $extension_id ) {
        return $this->client->delete( 'agents/extensions/' . absint( $extension_id ) );
    }

    /**
     * Assign an existing extension to an agent.
     *
     * @since    1.0.0
     * @param    int       $agent_id         Agent ID.
     * @param    array     $extension_data   Extension assignment fields.
     * @return   Dialyra_API_Response  The API response containing agent data or error.
     */
    public function assign_agent_extension( $agent_id, $extension_data ) {
        return $this->client->put( 'agents/' . absint( $agent_id ) . '/extensions', $this->sanitize_agent_extension_assignment_payload( $extension_data ) );
    }

    // Departments

    /**
     * Create a department.
     *
     * @since    1.0.0
     * @param    array     $department_data    Department fields.
     * @return   Dialyra_API_Response  The API response containing department data or error.
     */
    public function create_department( $department_data ) {
        return $this->client->post( 'departments', $this->sanitize_department_payload( $department_data ) );
    }

    /**
     * Get departments.
     *
     * @since    1.0.0
     * @param    array     $query_params    Optional query params, e.g. business_id.
     * @return   Dialyra_API_Response  The API response containing department list data or error.
     */
    public function get_departments( $query_params = array() ) {
        return $this->client->get( 'departments', $this->sanitize_payload( $query_params ) );
    }

    /**
     * Get a department.
     *
     * @since    1.0.0
     * @param    int       $department_id    Department ID.
     * @return   Dialyra_API_Response  The API response containing department data or error.
     */
    public function get_department( $department_id ) {
        return $this->client->get( 'departments/' . absint( $department_id ) );
    }

    /**
     * Update a department.
     *
     * @since    1.0.0
     * @param    int       $department_id      Department ID.
     * @param    array     $department_data    Department fields.
     * @return   Dialyra_API_Response  The API response containing department data or error.
     */
    public function update_department( $department_id, $department_data ) {
        return $this->client->put( 'departments/' . absint( $department_id ), $this->sanitize_department_payload( $department_data ) );
    }

    /**
     * Delete a department.
     *
     * @since    1.0.0
     * @param    int       $department_id    Department ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_department( $department_id ) {
        return $this->client->delete( 'departments/' . absint( $department_id ) );
    }

    /**
     * Get department agent mappings.
     *
     * @since    1.0.0
     * @param    int       $department_id    Department ID.
     * @return   Dialyra_API_Response  The API response containing mapping data or error.
     */
    public function get_department_agents( $department_id ) {
        return $this->client->get( 'departments/' . absint( $department_id ) . '/agents' );
    }

    /**
     * Add or update a department agent mapping.
     *
     * @since    1.0.0
     * @param    int       $department_id    Department ID.
     * @param    array     $mapping_data     Mapping fields.
     * @return   Dialyra_API_Response  The API response containing mapping data or error.
     */
    public function add_department_agent( $department_id, $mapping_data ) {
        return $this->client->post( 'departments/' . absint( $department_id ) . '/agents', $this->sanitize_department_agent_payload( $mapping_data ) );
    }

    /**
     * Update a department agent mapping.
     *
     * @since    1.0.0
     * @param    int       $department_id    Department ID.
     * @param    array     $mapping_data     Mapping fields.
     * @return   Dialyra_API_Response  The API response containing mapping data or error.
     */
    public function update_department_agent( $department_id, $mapping_data ) {
        return $this->client->put( 'departments/' . absint( $department_id ) . '/agents', $this->sanitize_department_agent_payload( $mapping_data ) );
    }

    /**
     * Remove an agent from a department.
     *
     * @since    1.0.0
     * @param    int       $department_id    Department ID.
     * @param    int       $agent_id         Agent ID.
     * @return   Dialyra_API_Response  The API response.
     */
    public function delete_department_agent( $department_id, $agent_id ) {
        return $this->client->delete( 'departments/' . absint( $department_id ) . '/agents/' . absint( $agent_id ) );
    }

    /**
     * Get a department schedule.
     *
     * @since    1.0.0
     * @param    int       $department_id    Department ID.
     * @return   Dialyra_API_Response  The API response containing schedule data or error.
     */
    public function get_department_schedule( $department_id ) {
        return $this->client->get( 'departments/' . absint( $department_id ) . '/schedule' );
    }

    /**
     * Create a department schedule.
     *
     * @since    1.0.0
     * @param    int       $department_id    Department ID.
     * @param    array     $schedule_data    Schedule fields.
     * @return   Dialyra_API_Response  The API response containing schedule data or error.
     */
    public function create_department_schedule( $department_id, $schedule_data ) {
        return $this->client->post( 'departments/' . absint( $department_id ) . '/schedule', $this->sanitize_department_schedule_payload( $schedule_data ) );
    }

    /**
     * Update a department schedule.
     *
     * @since    1.0.0
     * @param    int       $department_id    Department ID.
     * @param    array     $schedule_data    Schedule fields.
     * @return   Dialyra_API_Response  The API response containing schedule data or error.
     */
    public function update_department_schedule( $department_id, $schedule_data ) {
        return $this->client->put( 'departments/' . absint( $department_id ) . '/schedule', $this->sanitize_department_schedule_payload( $schedule_data ) );
    }

    /**
     * Set department schedule mode.
     *
     * @since    1.0.0
     * @param    int       $department_id    Department ID.
     * @param    string    $mode             Availability mode.
     * @return   Dialyra_API_Response  The API response containing schedule data or error.
     */
    public function set_department_schedule_mode( $department_id, $mode ) {
        return $this->client->post( 'departments/' . absint( $department_id ) . '/schedule/' . sanitize_key( $mode ) );
    }

    /**
     * Get department live readiness.
     *
     * @since    1.0.0
     * @param    int       $department_id    Department ID.
     * @return   Dialyra_API_Response  The API response containing live readiness data or error.
     */
    public function get_department_live( $department_id ) {
        return $this->client->get( 'departments/' . absint( $department_id ) . '/live' );
    }

    // Audio Assets

    /**
     * Upload an audio asset.
     *
     * @since    1.0.0
     * @param    array    $audio_data    Audio fields.
     * @param    array    $file          Uploaded file array.
     * @return   Dialyra_API_Response
     */
    public function upload_audio_asset( $audio_data, $file ) {
        return $this->client->post_multipart(
            'audio-assets/upload',
            $this->sanitize_audio_asset_payload( $audio_data ),
            array(
                'file' => $file,
            )
        );
    }

    /**
     * Get audio assets.
     *
     * @since    1.0.0
     * @param    array    $query_params    Optional query params.
     * @return   Dialyra_API_Response
     */
    public function get_audio_assets( $query_params = array() ) {
        return $this->client->get( 'audio-assets', $this->sanitize_payload( $query_params ) );
    }

    /**
     * Update an audio asset.
     *
     * @since    1.0.0
     * @param    int      $audio_asset_id    Audio asset ID.
     * @param    array    $audio_data        Audio fields.
     * @return   Dialyra_API_Response
     */
    public function update_audio_asset( $audio_asset_id, $audio_data ) {
        return $this->client->put( 'audio-assets/' . absint( $audio_asset_id ), $this->sanitize_audio_asset_payload( $audio_data ) );
    }

    /**
     * Delete an audio asset.
     *
     * @since    1.0.0
     * @param    int    $audio_asset_id    Audio asset ID.
     * @return   Dialyra_API_Response
     */
    public function delete_audio_asset( $audio_asset_id ) {
        return $this->client->delete( 'audio-assets/' . absint( $audio_asset_id ) );
    }

    /**
     * Stream an audio asset.
     *
     * @since    1.0.0
     * @param    int    $audio_asset_id    Audio asset ID.
     * @return   array|WP_Error
     */
    public function stream_audio_asset( $audio_asset_id ) {
        return $this->client->get_raw( 'audio-assets/' . absint( $audio_asset_id ) . '/stream' );
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
     * Sanitize a user creation payload.
     *
     * @since    1.0.0
     * @param    array     $user_data    Raw user data.
     * @return   array
     */
    private function sanitize_user_payload( $user_data ) {
        $payload = $this->sanitize_allowed_payload( $user_data, array(
            'full_name',
            'email',
            'password',
            'role',
            'business_id',
            'membership_role',
        ) );

        if ( isset( $payload['email'] ) ) {
            $payload['email'] = sanitize_email( $payload['email'] );
        }

        if ( isset( $payload['business_id'] ) ) {
            $payload['business_id'] = absint( $payload['business_id'] );
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
     * Sanitize a business webhook payload.
     *
     * @since    1.0.0
     * @param    array     $webhook_data    Raw webhook data.
     * @return   array
     */
    private function sanitize_business_webhook_payload( $webhook_data ) {
        $payload = $this->sanitize_allowed_payload( $webhook_data, array(
            'business_id',
            'name',
            'url',
            'event_types',
            'timeout_seconds',
            'status',
            'secret',
            'rotate_secret',
        ) );

        if ( isset( $payload['business_id'] ) ) {
            $payload['business_id'] = absint( $payload['business_id'] );
        }

        if ( isset( $payload['url'] ) ) {
            $payload['url'] = esc_url_raw( $payload['url'] );
        }

        if ( isset( $payload['event_types'] ) && is_array( $payload['event_types'] ) ) {
            $payload['event_types'] = array_values( array_filter( array_map( 'sanitize_text_field', $payload['event_types'] ) ) );
        }

        if ( isset( $payload['timeout_seconds'] ) ) {
            $payload['timeout_seconds'] = absint( $payload['timeout_seconds'] );
        }

        if ( isset( $payload['rotate_secret'] ) ) {
            $payload['rotate_secret'] = (bool) $payload['rotate_secret'];
        }

        return $payload;
    }

    /**
     * Sanitize a runtime call originate payload.
     *
     * @since    1.0.0
     * @param    array     $payload_data    Raw originate payload.
     * @return   array
     */
    private function sanitize_call_originate_payload( $payload_data ) {
        $payload = $this->sanitize_allowed_payload( $payload_data, array(
            'phone',
            'flow_id',
            'webhook_variables',
        ) );

        if ( isset( $payload['phone'] ) ) {
            $payload['phone'] = sanitize_text_field( $payload['phone'] );
        }

        if ( isset( $payload['flow_id'] ) ) {
            $payload['flow_id'] = absint( $payload['flow_id'] );
        }

        if ( isset( $payload['webhook_variables'] ) && is_array( $payload['webhook_variables'] ) ) {
            $payload['webhook_variables'] = $this->sanitize_payload( $payload['webhook_variables'] );
        }

        return $payload;
    }

    /**
     * Sanitize an audio asset payload.
     *
     * @since    1.0.0
     * @param    array    $audio_data    Raw audio data.
     * @return   array
     */
    private function sanitize_audio_asset_payload( $audio_data ) {
        $payload = $this->sanitize_allowed_payload( $audio_data, array(
            'business_id',
            'name',
            'category',
        ) );

        if ( isset( $payload['business_id'] ) ) {
            $payload['business_id'] = absint( $payload['business_id'] );
        }

        return $payload;
    }

    /**
     * Sanitize an agent create payload.
     *
     * @since    1.0.0
     * @param    array     $agent_data    Raw agent data.
     * @return   array
     */
    private function sanitize_agent_payload( $agent_data ) {
        $payload = $this->sanitize_allowed_payload( $agent_data, array(
            'business_id',
            'user_id',
            'name',
            'email',
            'phone',
            'sip_extension',
            'status',
            'availability_status',
            'max_concurrent_calls',
            'current_active_calls',
            'skills',
            'metadata',
        ) );

        foreach ( array( 'business_id', 'user_id', 'max_concurrent_calls', 'current_active_calls' ) as $integer_field ) {
            if ( isset( $payload[ $integer_field ] ) ) {
                $payload[ $integer_field ] = absint( $payload[ $integer_field ] );
            }
        }

        if ( isset( $payload['email'] ) ) {
            $payload['email'] = sanitize_email( $payload['email'] );
        }

        return $payload;
    }

    /**
     * Sanitize an agent profile update payload.
     *
     * @since    1.0.0
     * @param    array     $agent_data    Raw agent data.
     * @return   array
     */
    private function sanitize_agent_update_payload( $agent_data ) {
        $payload = $this->sanitize_allowed_payload( $agent_data, array(
            'name',
            'phone',
            'status',
            'availability_status',
            'max_concurrent_calls',
            'skills',
        ) );

        if ( isset( $payload['max_concurrent_calls'] ) ) {
            $payload['max_concurrent_calls'] = max( 1, absint( $payload['max_concurrent_calls'] ) );
        }

        return $payload;
    }

    /**
     * Sanitize an agent SIP extension payload.
     *
     * @since    1.0.0
     * @param    array     $extension_data    Raw extension data.
     * @return   array
     */
    private function sanitize_agent_extension_payload( $extension_data ) {
        $payload = $this->sanitize_allowed_payload( $extension_data, array(
            'business_id',
            'user_id',
            'extension',
            'password',
            'display_name',
            'transport',
            'context',
            'allow',
            'dtmf_mode',
            'max_contacts',
            'qualify_frequency',
            'remove_existing',
        ) );

        foreach ( array( 'business_id', 'user_id', 'max_contacts', 'qualify_frequency' ) as $integer_field ) {
            if ( isset( $payload[ $integer_field ] ) ) {
                $payload[ $integer_field ] = absint( $payload[ $integer_field ] );
            }
        }

        if ( isset( $payload['remove_existing'] ) ) {
            $payload['remove_existing'] = (bool) $payload['remove_existing'];
        }

        return $payload;
    }

    /**
     * Sanitize an extension bind payload.
     *
     * @since    1.0.0
     * @param    array     $bind_data    Raw bind data.
     * @return   array
     */
    private function sanitize_agent_extension_bind_payload( $bind_data ) {
        $payload = $this->sanitize_allowed_payload( $bind_data, array(
            'user_id',
            'is_primary',
        ) );

        if ( isset( $payload['user_id'] ) ) {
            $payload['user_id'] = absint( $payload['user_id'] );
        }

        if ( isset( $payload['is_primary'] ) ) {
            $payload['is_primary'] = (bool) $payload['is_primary'];
        }

        return $payload;
    }

    /**
     * Sanitize an extension active-state update payload.
     *
     * @since    1.0.0
     * @param    array     $extension_data    Raw extension data.
     * @return   array
     */
    private function sanitize_agent_extension_update_payload( $extension_data ) {
        $payload = $this->sanitize_allowed_payload( $extension_data, array(
            'is_active',
        ) );

        if ( isset( $payload['is_active'] ) ) {
            $payload['is_active'] = (bool) $payload['is_active'];
        }

        return $payload;
    }

    /**
     * Sanitize an existing extension assignment payload.
     *
     * @since    1.0.0
     * @param    array     $extension_data    Raw extension data.
     * @return   array
     */
    private function sanitize_agent_extension_assignment_payload( $extension_data ) {
        $payload = $this->sanitize_allowed_payload( $extension_data, array(
            'extension',
            'transfer',
        ) );

        if ( isset( $payload['transfer'] ) ) {
            $payload['transfer'] = (bool) $payload['transfer'];
        }

        return $payload;
    }

    /**
     * Sanitize a department payload.
     *
     * @since    1.0.0
     * @param    array     $department_data    Raw department data.
     * @return   array
     */
    private function sanitize_department_payload( $department_data ) {
        $payload = $this->sanitize_allowed_payload( $department_data, array(
            'business_id',
            'name',
            'description',
            'status',
            'strategy',
            'metadata',
        ) );

        if ( isset( $payload['business_id'] ) ) {
            $payload['business_id'] = absint( $payload['business_id'] );
        }

        return $payload;
    }

    /**
     * Sanitize a department-agent mapping payload.
     *
     * @since    1.0.0
     * @param    array     $mapping_data    Raw mapping data.
     * @return   array
     */
    private function sanitize_department_agent_payload( $mapping_data ) {
        $payload = $this->sanitize_allowed_payload( $mapping_data, array(
            'agent_id',
            'priority',
            'is_active',
        ) );

        if ( isset( $payload['agent_id'] ) ) {
            $payload['agent_id'] = absint( $payload['agent_id'] );
        }

        if ( isset( $payload['priority'] ) ) {
            $payload['priority'] = max( 1, absint( $payload['priority'] ) );
        }

        if ( isset( $payload['is_active'] ) ) {
            $payload['is_active'] = (bool) $payload['is_active'];
        }

        return $payload;
    }

    /**
     * Sanitize a department schedule payload.
     *
     * @since    1.0.0
     * @param    array     $schedule_data    Raw schedule data.
     * @return   array
     */
    private function sanitize_department_schedule_payload( $schedule_data ) {
        $payload = $this->sanitize_allowed_payload( $schedule_data, array(
            'availability_mode',
            'timezone',
            'weekly_hours',
            'holiday_overrides',
            'is_active',
            'metadata',
        ) );

        if ( isset( $payload['is_active'] ) ) {
            $payload['is_active'] = (bool) $payload['is_active'];
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
