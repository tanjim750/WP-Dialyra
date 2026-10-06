<?php

/**
 * Dialyra Authentication Manager.
 *
 * Manages plugin authentication state and handles redirects.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/auth
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! defined( 'WP_DIALYRA_OPTION_ACCESS_TOKEN' ) || ! defined( 'WP_DIALYRA_OPTION_AUTH_BASE_URL' ) ) {
	require_once dirname( __DIR__ ) . '/constant.php';
}

class Dialyra_Auth_Manager {

    const ACCESS_TOKEN_OPTION   = WP_DIALYRA_OPTION_ACCESS_TOKEN;
    const REFRESH_TOKEN_OPTION  = WP_DIALYRA_OPTION_REFRESH_TOKEN;
    const BUSINESS_ID_OPTION    = WP_DIALYRA_OPTION_BUSINESS_ID;
    const USER_INFO_OPTION      = WP_DIALYRA_OPTION_USER_INFO;
    const LAST_AUTH_CHECK_OPTION = WP_DIALYRA_OPTION_LAST_AUTH_CHECK_AT;
    const AUTH_BASE_URL_OPTION  = WP_DIALYRA_OPTION_AUTH_BASE_URL;
    const SITE_TOKEN_OPTION     = WP_DIALYRA_OPTION_SITE_ACCESS_TOKEN;
    const SETUP_SETTINGS_OPTION = WP_DIALYRA_OPTION_SETUP_SETTINGS;

    /**
     * Save the access token.
     *
     * @since    1.0.0
     * @param    string    $token    The access token.
     * @return   bool      True on success, false on failure.
     */
    public static function save_access_token( $token ) {
        $saved = update_option( self::ACCESS_TOKEN_OPTION, self::normalize_token( $token ), false );
        update_option( self::AUTH_BASE_URL_OPTION, self::current_api_base_url(), false );

        return $saved;
    }

    /**
     * Retrieve the access token.
     *
     * @since    1.0.0
     * @return   string|false    The access token, or false if not found.
     */
    public static function get_access_token() {
        if ( self::auth_base_url_changed() ) {
            self::clear_authentication();

            return false;
        }

        return self::normalize_token( get_option( self::ACCESS_TOKEN_OPTION ) );
    }

    /**
     * Remove the access token.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public static function remove_access_token() {
        delete_option( self::AUTH_BASE_URL_OPTION );

        return delete_option( self::ACCESS_TOKEN_OPTION );
    }

    /**
     * Save the refresh token.
     *
     * @since    1.0.0
     * @param    string    $token    The refresh token.
     * @return   bool      True on success, false on failure.
     */
    public static function save_refresh_token( $token ) {
        return update_option( self::REFRESH_TOKEN_OPTION, self::normalize_token( $token ), false );
    }

    /**
     * Retrieve the refresh token.
     *
     * @since    1.0.0
     * @return   string|false    The refresh token, or false if not found.
     */
    public static function get_refresh_token() {
        return self::normalize_token( get_option( self::REFRESH_TOKEN_OPTION ) );
    }

    /**
     * Remove the refresh token.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public static function remove_refresh_token() {
        return delete_option( self::REFRESH_TOKEN_OPTION );
    }

    /**
     * Save authenticated user information.
     *
     * @since    1.0.0
     * @param    array     $user_info    Authenticated user data.
     * @return   bool      True on success, false on failure.
     */
    public static function save_user_info( $user_info ) {
        return update_option( self::USER_INFO_OPTION, self::sanitize_data( $user_info ), false );
    }

    /**
     * Retrieve authenticated user information.
     *
     * @since    1.0.0
     * @return   array|false    Authenticated user data, or false if not found.
     */
    public static function get_user_info() {
        return get_option( self::USER_INFO_OPTION );
    }

    /**
     * Remove authenticated user information.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public static function remove_user_info() {
        return delete_option( self::USER_INFO_OPTION );
    }

    /**
     * Save the connected business ID.
     *
     * @since    1.0.0
     * @param    string    $business_id    The connected business ID.
     * @return   bool      True on success, false on failure.
     */
    public static function save_business_id( $business_id ) {
        return update_option( self::BUSINESS_ID_OPTION, absint( $business_id ), false );
    }

    /**
     * Retrieve the connected business ID.
     *
     * @since    1.0.0
     * @return   string|false    The business ID, or false if not found.
     */
    public static function get_business_id() {
        return get_option( self::BUSINESS_ID_OPTION );
    }

    /**
     * Remove the connected business ID.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public static function remove_business_id() {
        return delete_option( self::BUSINESS_ID_OPTION );
    }

    /**
     * Check whether the plugin is authenticated.
     *
     * @since    1.0.0
     * @return   bool    True if authenticated, false otherwise.
     */
    public static function is_authenticated() {
        return (bool) self::get_access_token() && (bool) self::get_business_id();
    }

    /**
     * Check whether the required plugin setup is complete.
     *
     * Default flow is intentionally optional.
     *
     * @since    1.0.0
     * @return   bool    True if required setup is complete, false otherwise.
     */
    public static function is_setup_complete() {
        $business_id = absint( self::get_business_id() );

        if ( ! self::is_logged_in() || ! $business_id ) {
            return false;
        }

        $site_token = get_option( self::SITE_TOKEN_OPTION, array() );
        $site_token = is_array( $site_token ) ? $site_token : array();

        if ( empty( $site_token['token'] ) || empty( $site_token['business_id'] ) || absint( $site_token['business_id'] ) !== $business_id ) {
            return false;
        }

        $setup_settings = get_option( self::SETUP_SETTINGS_OPTION, array() );
        $setup_settings = is_array( $setup_settings ) ? $setup_settings : array();

        if ( empty( $setup_settings['business_id'] ) || absint( $setup_settings['business_id'] ) !== $business_id ) {
            return false;
        }

        return ! empty( $setup_settings['call_trigger']['mode'] );
    }

    /**
     * Check whether a Dialyra user session exists locally.
     *
     * @since    1.0.0
     * @return   bool    True if logged in, false otherwise.
     */
    public static function is_logged_in() {
        return (bool) self::get_access_token();
    }

    /**
     * Clear all local authentication data.
     *
     * @since    1.0.0
     */
    public static function clear_authentication() {
        self::remove_access_token();
        self::remove_refresh_token();
        self::remove_business_id();
        self::remove_user_info();
        delete_option( self::LAST_AUTH_CHECK_OPTION );
        delete_option( self::SITE_TOKEN_OPTION );
    }

    /**
     * Mark the current time as the latest successful auth validation.
     *
     * @since    1.0.0
     * @return   bool
     */
    public static function touch_auth_check() {
        return update_option( self::LAST_AUTH_CHECK_OPTION, time(), false );
    }

    /**
     * Determine whether the auth session should be checked again.
     *
     * @since    1.0.0
     * @return   bool
     */
    public static function should_validate_session() {
        $last_checked_at = absint( get_option( self::LAST_AUTH_CHECK_OPTION, 0 ) );
        $interval        = defined( 'WP_DIALYRA_AUTH_CHECK_INTERVAL' ) ? absint( WP_DIALYRA_AUTH_CHECK_INTERVAL ) : 15 * 60;

        return ! $last_checked_at || ( time() - $last_checked_at ) >= max( 60, $interval );
    }

    /**
     * Validate the stored API session when the check interval has elapsed.
     *
     * Temporary network/API availability failures are treated as soft failures
     * so an otherwise valid local session is not destroyed by a transient outage.
     *
     * @since    1.0.0
     * @param    Dialyra_API_Endpoints|null    $api_endpoints    API endpoints service.
     * @param    bool                          $force            Whether to skip interval cache.
     * @return   array
     */
    public static function validate_session_if_due( $api_endpoints = null, $force = false ) {
        if ( ! self::is_logged_in() ) {
            return self::auth_validation_result( false, 'missing_token' );
        }

        if ( ! $force && ! self::should_validate_session() ) {
            return self::auth_validation_result( true, 'cached' );
        }

        if ( ! $api_endpoints || ! method_exists( $api_endpoints, 'get_me' ) ) {
            return self::auth_validation_result( true, 'service_unavailable' );
        }

        $response = $api_endpoints->get_me();

        if ( $response && method_exists( $response, 'is_successful' ) && $response->is_successful() ) {
            if ( self::response_contains_inactive_account( $response->get_data() ) ) {
                self::clear_authentication();

                return self::auth_validation_result( false, 'inactive_account' );
            }

            self::store_auth_me_response( $response->get_data() );
            self::touch_auth_check();

            return self::auth_validation_result( true, 'validated' );
        }

        $status_code = $response && method_exists( $response, 'get_status_code' ) ? absint( $response->get_status_code() ) : 0;

        if ( in_array( $status_code, array( 401, 404 ), true ) && self::refresh_access_token( $api_endpoints ) ) {
            $retry_response = $api_endpoints->get_me();

            if ( $retry_response && method_exists( $retry_response, 'is_successful' ) && $retry_response->is_successful() ) {
                if ( self::response_contains_inactive_account( $retry_response->get_data() ) ) {
                    self::clear_authentication();

                    return self::auth_validation_result( false, 'inactive_account' );
                }

                self::store_auth_me_response( $retry_response->get_data() );
                self::touch_auth_check();

                return self::auth_validation_result( true, 'refreshed' );
            }
        }

        if ( in_array( $status_code, array( 401, 403, 404 ), true ) ) {
            self::clear_authentication();

            return self::auth_validation_result( false, self::auth_failure_reason_for_status( $status_code ) );
        }

        return self::auth_validation_result( true, 'soft_failure' );
    }

    /**
     * Get the URL for the Dialyra login page.
     *
     * @since    1.0.0
     * @return   string    The login page URL.
     */
    public static function get_login_url() {
        return admin_url( 'admin.php?page=wp-dialyra&p=login' );
    }

    /**
     * Get the URL for the Dialyra access denied page.
     *
     * @since    1.0.0
     * @return   string    The access denied page URL.
     */
    public static function get_access_denied_url() {
        // Assuming you have an access denied page or will implement one.
        return admin_url( 'admin.php?page=wp-dialyra&p=login' );
    }

    /**
     * Handle redirects for unauthenticated users.
     *
     * @since    1.0.0
     */
    public static function handle_unauthenticated_redirect() {
        if ( ! self::is_logged_in() && ! self::is_login_page() ) {
            self::clear_authentication();
            wp_safe_redirect( self::get_login_url() );
            exit;
        }

        if ( self::is_logged_in() && ! self::is_setup_complete() && ! self::is_setup_page() && ! self::is_login_page() ) {
            wp_safe_redirect( admin_url( 'admin.php?page=wp-dialyra&p=setup' ) );
            exit;
        }
    }

    /**
     * Check if the current page is the login page.
     *
     * @since    1.0.0
     * @return   bool    True if on login page, false otherwise.
     */
    private static function is_login_page() {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        $subpage = isset( $_GET['p'] ) ? sanitize_key( wp_unslash( $_GET['p'] ) ) : '';

        return 'wp-dialyra' === $page && 'login' === $subpage;
    }

    /**
     * Check if the current page is the setup page.
     *
     * @since    1.0.0
     * @return   bool    True if on setup page, false otherwise.
     */
    private static function is_setup_page() {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        $subpage = isset( $_GET['p'] ) ? sanitize_key( wp_unslash( $_GET['p'] ) ) : '';

        return 'wp-dialyra' === $page && 'setup' === $subpage;
    }

    /**
     * Refresh the access token using the stored refresh token.
     *
     * @since    1.0.0
     * @param    Dialyra_API_Endpoints    $api_endpoints    API endpoints service.
     * @return   bool
     */
    private static function refresh_access_token( $api_endpoints ) {
        $refresh_token = self::get_refresh_token();

        if ( ! $refresh_token || ! $api_endpoints || ! method_exists( $api_endpoints, 'refresh_token' ) ) {
            return false;
        }

        $response = $api_endpoints->refresh_token( $refresh_token );

        if ( ! $response || ! method_exists( $response, 'is_successful' ) || ! $response->is_successful() ) {
            return false;
        }

        $data = self::unwrap_response_data( $response->get_data() );

        if ( empty( $data['access_token'] ) ) {
            return false;
        }

        self::save_access_token( $data['access_token'] );

        if ( ! empty( $data['refresh_token'] ) ) {
            self::save_refresh_token( $data['refresh_token'] );
        }

        return true;
    }

    /**
     * Store user and business details from /auth/me.
     *
     * @since    1.0.0
     * @param    array|null    $data    API response data.
     */
    private static function store_auth_me_response( $data ) {
        $data = self::unwrap_response_data( $data );

        if ( ! is_array( $data ) ) {
            return;
        }

        if ( ! empty( $data['user'] ) && is_array( $data['user'] ) ) {
            self::save_user_info( $data['user'] );
        }

        if ( array_key_exists( 'business', $data ) ) {
            if ( ! empty( $data['business'] ) && is_array( $data['business'] ) ) {
                self::save_business_via_manager( $data['business'], 'auth_check' );
            } else {
                self::clear_business_via_manager();
            }
        }
    }

    /**
     * Save business data through the business manager when available.
     *
     * This keeps token and webhook resubscription hooks centralized.
     *
     * @since    1.0.0
     * @param    array     $business_data    Business data.
     * @param    string    $source           Change source.
     */
    private static function save_business_via_manager( $business_data, $source = 'auth_check' ) {
        $plugin           = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
        $business_manager = $plugin && method_exists( $plugin, 'get_business_manager' ) ? $plugin->get_business_manager() : null;

        if ( $business_manager && method_exists( $business_manager, 'save_connected_business_data' ) ) {
            $business_manager->save_connected_business_data( $business_data, $source );
            return;
        }

        if ( ! empty( $business_data['id'] ) ) {
            self::save_business_id( $business_data['id'] );
        }

        if ( defined( 'WP_DIALYRA_OPTION_BUSINESS_DATA' ) ) {
            update_option( WP_DIALYRA_OPTION_BUSINESS_DATA, self::sanitize_data( $business_data ), false );
        }
    }

    /**
     * Clear business data through the business manager when available.
     *
     * @since    1.0.0
     */
    private static function clear_business_via_manager() {
        $plugin           = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
        $business_manager = $plugin && method_exists( $plugin, 'get_business_manager' ) ? $plugin->get_business_manager() : null;

        if ( $business_manager && method_exists( $business_manager, 'clear_connected_business' ) ) {
            $business_manager->clear_connected_business();
            return;
        }

        self::remove_business_id();

        if ( defined( 'WP_DIALYRA_OPTION_BUSINESS_DATA' ) ) {
            delete_option( WP_DIALYRA_OPTION_BUSINESS_DATA );
        }
    }

    /**
     * Check whether /auth/me returned an inactive account or business.
     *
     * @since    1.0.0
     * @param    array|null    $data    API response data.
     * @return   bool
     */
    private static function response_contains_inactive_account( $data ) {
        $data = self::unwrap_response_data( $data );

        if ( ! is_array( $data ) ) {
            return false;
        }

        $user_status = ! empty( $data['user']['status'] ) ? sanitize_key( $data['user']['status'] ) : 'active';

        if ( in_array( $user_status, array( 'inactive', 'suspended', 'deleted' ), true ) ) {
            return true;
        }

        $business_status = ! empty( $data['business']['status'] ) ? sanitize_key( $data['business']['status'] ) : 'active';

        return in_array( $business_status, array( 'inactive', 'suspended', 'deleted' ), true );
    }

    /**
     * Unwrap API data objects that use a nested data envelope.
     *
     * @since    1.0.0
     * @param    mixed    $data    API response data.
     * @return   mixed
     */
    private static function unwrap_response_data( $data ) {
        if ( is_array( $data ) && isset( $data['data'] ) && is_array( $data['data'] ) ) {
            return $data['data'];
        }

        return $data;
    }

    /**
     * Build a normalized auth validation result.
     *
     * @since    1.0.0
     * @param    bool      $valid     Whether session remains valid.
     * @param    string    $reason    Reason code.
     * @return   array
     */
    private static function auth_validation_result( $valid, $reason ) {
        return array(
            'valid'  => (bool) $valid,
            'reason' => sanitize_key( $reason ),
        );
    }

    /**
     * Map hard auth status codes to redirect reason codes.
     *
     * @since    1.0.0
     * @param    int    $status_code    HTTP status code.
     * @return   string
     */
    private static function auth_failure_reason_for_status( $status_code ) {
        $status_code = absint( $status_code );

        if ( 401 === $status_code ) {
            return 'unauthorized';
        }

        if ( 403 === $status_code ) {
            return 'forbidden';
        }

        if ( 404 === $status_code ) {
            return 'auth_not_found';
        }

        return 'expired';
    }

    /**
     * Normalize saved bearer tokens for consistent Authorization headers.
     *
     * @since    1.0.0
     * @param    mixed    $token    Raw token.
     * @return   string
     */
    private static function normalize_token( $token ) {
        $token = is_string( $token ) ? trim( $token ) : '';
        $token = preg_replace( '/^Bearer\s+/i', '', $token );

        return sanitize_text_field( trim( $token ) );
    }

    /**
     * Get the currently configured API base URL.
     *
     * @since    1.0.0
     * @return   string
     */
    private static function current_api_base_url() {
        return defined( 'DIALYRA_API_BASE_URL' ) ? untrailingslashit( esc_url_raw( DIALYRA_API_BASE_URL ) ) : '';
    }

    /**
     * Check whether saved auth belongs to a different API environment.
     *
     * @since    1.0.0
     * @return   bool
     */
    private static function auth_base_url_changed() {
        $saved_base_url   = untrailingslashit( esc_url_raw( get_option( self::AUTH_BASE_URL_OPTION, '' ) ) );
        $current_base_url = self::current_api_base_url();

        return '' !== $saved_base_url && '' !== $current_base_url && $saved_base_url !== $current_base_url;
    }

    /**
     * Sanitize nested data before saving it locally.
     *
     * @since    1.0.0
     * @param    mixed    $data    Data to sanitize.
     * @return   mixed
     */
    private static function sanitize_data( $data ) {
        if ( is_array( $data ) ) {
            return array_map( array( __CLASS__, 'sanitize_data' ), $data );
        }

        if ( is_bool( $data ) || is_int( $data ) || is_float( $data ) || is_null( $data ) ) {
            return $data;
        }

        return sanitize_text_field( $data );
    }
}
