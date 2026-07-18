<?php

/**
 * Dialyra API Client.
 *
 * Handles all HTTP requests to the Dialyra API.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/api
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Dialyra_API_Client {

    /**
     * The API configuration object.
     *
     * @since    1.0.0
     * @access   private
     * @var      Dialyra_API_Config    $config    The API configuration object.
     */
    private $config;

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    Dialyra_API_Config    $config    The API configuration object.
     */
    public function __construct( Dialyra_API_Config $config ) {
        $this->config = $config;
    }

    /**
     * Send a GET request.
     *
     * @since    1.0.0
     * @param    string    $endpoint      The API endpoint.
     * @param    array     $query_params  Optional query parameters.
     * @param    bool      $authenticated Whether the request requires authentication.
     * @return   Dialyra_API_Response  A structured API response object.
     */
    public function get( $endpoint, $query_params = array(), $authenticated = true ) {
        $url = add_query_arg( $query_params, $this->build_url( $endpoint ) );
        return $this->request( 'GET', $url, array(), $authenticated );
    }

    /**
     * Send a POST request.
     *
     * @since    1.0.0
     * @param    string    $endpoint      The API endpoint.
     * @param    array     $body          The request body.
     * @param    bool      $authenticated Whether the request requires authentication.
     * @return   Dialyra_API_Response  A structured API response object.
     */
    public function post( $endpoint, $body = array(), $authenticated = true ) {
        return $this->request( 'POST', $this->build_url( $endpoint ), array( 'body' => $body ), $authenticated );
    }

    /**
     * Send a POST request authenticated with a business access token.
     *
     * @since    1.0.0
     * @param    string    $endpoint        The API endpoint.
     * @param    array     $body            The request body.
     * @param    string    $access_token    Business access token.
     * @return   Dialyra_API_Response  A structured API response object.
     */
    public function post_with_business_access_token( $endpoint, $body = array(), $access_token = '' ) {
        return $this->post_with_business_access_token_to_version( 'v2', $endpoint, $body, $access_token );
    }

    /**
     * Send a POST request to a specific API version with a business access token.
     *
     * @since    1.0.0
     * @param    string    $api_version     API version.
     * @param    string    $endpoint        The API endpoint.
     * @param    array     $body            The request body.
     * @param    string    $access_token    Business access token.
     * @return   Dialyra_API_Response  A structured API response object.
     */
    public function post_with_business_access_token_to_version( $api_version, $endpoint, $body = array(), $access_token = '' ) {
        $access_token = is_string( $access_token ) ? trim( $access_token ) : '';

        if ( '' === $access_token ) {
            return new Dialyra_API_Response( null, 401, esc_html__( 'Business access token missing.', 'wp-dialyra' ), 'unauthenticated' );
        }

        return $this->request(
            'POST',
            $this->build_versioned_url( $api_version, $endpoint ),
            array(
                'body'    => $body,
                'headers' => array(
                    'X-Dialyra-Access-Token' => $access_token,
                ),
            ),
            false
        );
    }

    /**
     * Send a multipart POST request.
     *
     * @since    1.0.0
     * @param    string    $endpoint      The API endpoint.
     * @param    array     $fields        Text fields.
     * @param    array     $files         File fields keyed by field name.
     * @param    bool      $authenticated Whether the request requires authentication.
     * @return   Dialyra_API_Response  A structured API response object.
     */
    public function post_multipart( $endpoint, $fields = array(), $files = array(), $authenticated = true ) {
        $boundary = wp_generate_password( 24, false, false );
        $body = $this->build_multipart_body( $fields, $files, $boundary );

        return $this->request(
            'POST',
            $this->build_url( $endpoint ),
            array(
                'body'    => $body,
                'headers' => array(
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                ),
                'raw_body' => true,
            ),
            $authenticated
        );
    }

    /**
     * Send a PUT request.
     *
     * @since    1.0.0
     * @param    string    $endpoint      The API endpoint.
     * @param    array     $body          The request body.
     * @param    bool      $authenticated Whether the request requires authentication.
     * @return   Dialyra_API_Response  A structured API response object.
     */
    public function put( $endpoint, $body = array(), $authenticated = true ) {
        return $this->request( 'PUT', $this->build_url( $endpoint ), array( 'body' => $body ), $authenticated );
    }

    /**
     * Send a PATCH request.
     *
     * @since    1.0.0
     * @param    string    $endpoint      The API endpoint.
     * @param    array     $body          The request body.
     * @param    bool      $authenticated Whether the request requires authentication.
     * @return   Dialyra_API_Response  A structured API response object.
     */
    public function patch( $endpoint, $body = array(), $authenticated = true ) {
        return $this->request( 'PATCH', $this->build_url( $endpoint ), array( 'body' => $body ), $authenticated );
    }

    /**
     * Send a DELETE request.
     *
     * @since    1.0.0
     * @param    string    $endpoint      The API endpoint.
     * @param    array     $body          The request body (optional, for DELETE with body).
     * @param    bool      $authenticated Whether the request requires authentication.
     * @return   Dialyra_API_Response  A structured API response object.
     */
    public function delete( $endpoint, $body = array(), $authenticated = true ) {
        return $this->request( 'DELETE', $this->build_url( $endpoint ), array( 'body' => $body ), $authenticated );
    }

    /**
     * Send a raw GET request for binary responses.
     *
     * @since    1.0.0
     * @param    string    $endpoint      The API endpoint.
     * @param    array     $query_params  Optional query parameters.
     * @param    bool      $authenticated Whether the request requires authentication.
     * @return   array|WP_Error
     */
    public function get_raw( $endpoint, $query_params = array(), $authenticated = true ) {
        $url = add_query_arg( $query_params, $this->build_url( $endpoint ) );
        $headers = $this->config->get_common_headers();
        $headers['Accept'] = '*/*';

        if ( $authenticated ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'auth/class-dialyra-auth-manager.php';
            $access_token = Dialyra_Auth_Manager::get_access_token();

            if ( ! $access_token ) {
                return new WP_Error( 'unauthenticated', esc_html__( 'Authentication token missing.', 'wp-dialyra' ) );
            }

            $headers['Authorization'] = 'Bearer ' . $access_token;
        }

        unset( $headers['Content-Type'] );

        return wp_remote_get(
            esc_url_raw( $url ),
            array(
                'headers' => $headers,
                'timeout' => $this->config->get_timeout(),
            )
        );
    }

    /**
     * Build a full API URL from a relative endpoint.
     *
     * @since    1.0.0
     * @param    string    $endpoint    The API endpoint.
     * @return   string
     */
    private function build_url( $endpoint ) {
        return trailingslashit( $this->config->get_full_base_url() ) . ltrim( $endpoint, '/' );
    }

    /**
     * Build a full API URL for a specific API version.
     *
     * @since    1.0.0
     * @param    string    $api_version    API version.
     * @param    string    $endpoint       The API endpoint.
     * @return   string
     */
    private function build_versioned_url( $api_version, $endpoint ) {
        $base_url = trailingslashit( esc_url_raw( defined( 'DIALYRA_API_BASE_URL' ) ? DIALYRA_API_BASE_URL : 'http://127.0.0.1:5001/api' ) );

        return $base_url . trim( sanitize_text_field( $api_version ), '/' ) . '/' . ltrim( $endpoint, '/' );
    }

    /**
     * Send a generic HTTP request to the Dialyra API.
     *
     * @since    1.0.0
     * @param    string    $method        The HTTP method (GET, POST, PUT, DELETE, PATCH).
     * @param    string    $url           The full URL for the request.
     * @param    array     $args          Optional arguments for the request (body, headers, etc.).
     * @param    bool      $authenticated Whether the request requires authentication.
     * @return   Dialyra_API_Response  A structured API response object.
     */
    public function request( $method, $url, $args = array(), $authenticated = true ) {
        $headers = array_merge( $this->config->get_common_headers(), isset( $args['headers'] ) ? $args['headers'] : array() );

        if ( $authenticated ) {
            // Include the Auth_Manager here. This class needs to be loaded by now.
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'auth/class-dialyra-auth-manager.php';
            $access_token = Dialyra_Auth_Manager::get_access_token();
            if ( $access_token ) {
                $headers['Authorization'] = 'Bearer ' . $access_token;
            } else {
                // If authentication is required but no token, return an unauthenticated error.
                return new Dialyra_API_Response( null, 401, esc_html__( 'Authentication token missing.', 'wp-dialyra' ), 'unauthenticated' );
            }
        }

        $request_args = array(
            'method'  => strtoupper( $method ),
            'headers' => $headers,
            'timeout' => $this->config->get_timeout(),
        );

        if ( isset( $args['body'] ) ) {
            $request_args['body'] = ! empty( $args['raw_body'] ) ? $args['body'] : wp_json_encode( $args['body'] );
        }

        // Validate URL before sending request.
        $parsed_url = wp_parse_url( $url );
        if ( ! $parsed_url || ! isset( $parsed_url['scheme'] ) || ! in_array( $parsed_url['scheme'], array( 'http', 'https' ), true ) ) {
            return new Dialyra_API_Response( null, 0, esc_html__( 'Invalid API URL provided.', 'wp-dialyra' ), 'bad_request' );
        }

        $response = wp_remote_request( esc_url_raw( $url ), $request_args );

        if ( is_wp_error( $response ) ) {
            return new Dialyra_API_Response( null, 0, $response->get_error_message(), 'network_error' );
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        $body      = wp_remote_retrieve_body( $response );
        $data      = json_decode( $body, true );

        // Handle cases where JSON decoding fails but response is not WP_Error.
        if ( ( JSON_ERROR_NONE !== json_last_error() ) && ( ! empty( $body ) ) ) {
            if ( 405 === $http_code ) {
                return new Dialyra_API_Response( null, $http_code, esc_html__( 'Method not allowed. Please check that this action is using the correct API endpoint and HTTP method.', 'wp-dialyra' ), 'method_not_allowed' );
            }

            return new Dialyra_API_Response( null, $http_code, esc_html__( 'Invalid JSON response from API.', 'wp-dialyra' ), 'invalid_response' );
        }

        return new Dialyra_API_Response( $data, $http_code, null, null, $body );
    }

    /**
     * Build a multipart/form-data request body.
     *
     * @since    1.0.0
     * @param    array     $fields      Text fields.
     * @param    array     $files       File fields.
     * @param    string    $boundary    Multipart boundary.
     * @return   string
     */
    private function build_multipart_body( $fields, $files, $boundary ) {
        $body = '';

        foreach ( $fields as $name => $value ) {
            $body .= '--' . $boundary . "\r\n";
            $body .= 'Content-Disposition: form-data; name="' . sanitize_key( $name ) . '"' . "\r\n\r\n";
            $body .= sanitize_text_field( $value ) . "\r\n";
        }

        foreach ( $files as $name => $file ) {
            if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
                continue;
            }

            $filename = ! empty( $file['name'] ) ? sanitize_file_name( $file['name'] ) : 'audio.wav';
            $mime_type = ! empty( $file['type'] ) ? sanitize_text_field( $file['type'] ) : 'application/octet-stream';
            $contents = file_get_contents( $file['tmp_name'] );

            if ( false === $contents ) {
                continue;
            }

            $body .= '--' . $boundary . "\r\n";
            $body .= 'Content-Disposition: form-data; name="' . sanitize_key( $name ) . '"; filename="' . $filename . '"' . "\r\n";
            $body .= 'Content-Type: ' . $mime_type . "\r\n\r\n";
            $body .= $contents . "\r\n";
        }

        $body .= '--' . $boundary . "--\r\n";

        return $body;
    }
}
