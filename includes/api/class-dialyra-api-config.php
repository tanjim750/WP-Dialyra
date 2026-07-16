<?php

/**
 * Dialyra API Configuration.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/api
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Dialyra_API_Config {

    /**
     * The base URL for the Dialyra API.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $base_url    The base URL for the Dialyra API.
     */
    private $base_url;

    /**
     * The API version or prefix (e.g., 'v1', 'v2').
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_version    The API version or prefix.
     */
    private $api_version;

    /**
     * The default request timeout in seconds.
     *
     * @since    1.0.0
     * @access   private
     * @var      int       $timeout        The default request timeout.
     */
    private $timeout;

    /**
     * Common headers for API requests.
     *
     * @since    1.0.0
     * @access   private
     * @var      array     $common_headers An associative array of common headers.
     */
    private $common_headers;

    /**
     * Constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->base_url    = defined( 'DIALYRA_API_BASE_URL' ) ? DIALYRA_API_BASE_URL : 'http://127.0.0.1:5001/api';
        $this->api_version = defined( 'DIALYRA_API_VERSION' ) ? DIALYRA_API_VERSION : 'v2';
        $this->timeout     = defined( 'DIALYRA_API_TIMEOUT' ) ? absint( DIALYRA_API_TIMEOUT ) : 30;

        $this->common_headers = array(
            'User-Agent'   => 'WP-Dialyra-Plugin/' . WP_DIALYRA_VERSION,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        );
    }

    /**
     * Get the full base API URL including version.
     *
     * @since    1.0.0
     * @return   string    The full base API URL.
     */
    public function get_full_base_url() {
        return trailingslashit( esc_url_raw( $this->base_url ) ) . trim( sanitize_text_field( $this->api_version ), '/' );
    }

    /**
     * Get the default request timeout.
     *
     * @since    1.0.0
     * @return   int    The timeout in seconds.
     */
    public function get_timeout() {
        return $this->timeout;
    }

    /**
     * Get common API headers.
     *
     * @since    1.0.0
     * @return   array     An associative array of common headers.
     */
    public function get_common_headers() {
        return $this->common_headers;
    }

    /**
     * Set a common API header.
     *
     * @since    1.0.0
     * @param    string    $key      The header key.
     * @param    string    $value    The header value.
     */
    public function set_common_header( $key, $value ) {
        $this->common_headers[ $key ] = $value;
    }

    /**
     * Remove a common API header.
     *
     * @since    1.0.0
     * @param    string    $key      The header key to remove.
     */
    public function remove_common_header( $key ) {
        unset( $this->common_headers[ $key ] );
    }
}
