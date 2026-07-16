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

if ( ! defined( 'WP_DIALYRA_OPTION_ACCESS_TOKEN' ) ) {
	require_once dirname( __DIR__ ) . '/constant.php';
}

class Dialyra_Auth_Manager {

    const ACCESS_TOKEN_OPTION   = WP_DIALYRA_OPTION_ACCESS_TOKEN;
    const REFRESH_TOKEN_OPTION  = WP_DIALYRA_OPTION_REFRESH_TOKEN;
    const BUSINESS_ID_OPTION    = WP_DIALYRA_OPTION_BUSINESS_ID;
    const USER_INFO_OPTION      = WP_DIALYRA_OPTION_USER_INFO;
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
        return update_option( self::ACCESS_TOKEN_OPTION, sanitize_text_field( $token ), false );
    }

    /**
     * Retrieve the access token.
     *
     * @since    1.0.0
     * @return   string|false    The access token, or false if not found.
     */
    public static function get_access_token() {
        return get_option( self::ACCESS_TOKEN_OPTION );
    }

    /**
     * Remove the access token.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public static function remove_access_token() {
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
        return update_option( self::REFRESH_TOKEN_OPTION, sanitize_text_field( $token ), false );
    }

    /**
     * Retrieve the refresh token.
     *
     * @since    1.0.0
     * @return   string|false    The refresh token, or false if not found.
     */
    public static function get_refresh_token() {
        return get_option( self::REFRESH_TOKEN_OPTION );
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
        delete_option( self::SITE_TOKEN_OPTION );
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
