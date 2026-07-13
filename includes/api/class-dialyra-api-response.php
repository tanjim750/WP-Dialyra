<?php

/**
 * Dialyra API Response.
 *
 * Normalizes API responses into a consistent structure.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/api
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Dialyra_API_Response {

    /**
     * Whether the API request was successful.
     *
     * @since    1.0.0
     * @access   private
     * @var      bool          $success        True if successful, false otherwise.
     */
    private $success;

    /**
     * The HTTP status code of the response.
     *
     * @since    1.0.0
     * @access   private
     * @var      int           $status_code    The HTTP status code.
     */
    private $status_code;

    /**
     * The type of error, if any (e.g., 'unauthenticated', 'network_error').
     *
     * @since    1.0.0
     * @access   private
     * @var      string|null   $error_type     The error type.
     */
    private $error_type;

    /**
     * A human-readable message.
     *
     * @since    1.0.0
     * @access   private
     * @var      string        $message        A descriptive message.
     */
    private $message;

    /**
     * The data returned by the API.
     *
     * @since    1.0.0
     * @access   private
     * @var      array|null    $data           The API response data.
     */
    private $data;

    /**
     * An array of detailed errors, typically for validation failures.
     *
     * @since    1.0.0
     * @access   private
     * @var      array         $errors         Detailed error messages.
     */
    private $errors;

    /**
     * A URL for redirection, if provided by the API.
     *
     * @since    1.0.0
     * @access   private
     * @var      string|null   $redirect_url   A URL for redirection.
     */
    private $redirect_url;

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    array|null    $data           The raw API response data.
     * @param    int           $status_code    The HTTP status code.
     * @param    string|null   $error_message  Optional initial error message (e.g., from WP_Error).
     * @param    string|null   $error_type     Optional initial error type.
     * @param    string|null   $raw_body       Optional raw response body to parse detailed errors.
     */
    public function __construct( $data, $status_code, $error_message = null, $error_type = null, $raw_body = null ) {
        $this->status_code = $status_code;
        $this->data        = $data;
        $this->errors      = array();
        $this->redirect_url = null;

        // Determine success based on status code.
        $this->success = ( $status_code >= 200 && $status_code < 300 && is_null( $error_message ) );

        // Map error type.
        $this->error_type = $error_type ? $error_type : $this->map_error_type( $status_code );

        // Set message and errors.
        if ( ! $this->success ) {
            if ( ! is_null( $error_message ) ) {
                $this->message = $error_message;
            } elseif ( isset( $data['message'] ) ) {
                $this->message = $data['message'];
            } elseif ( isset( $data['error'] ) ) {
                $this->message = $data['error'];
            } elseif ( 405 === $status_code ) {
                $this->message = esc_html__( 'Method not allowed. Please check that this action is using the correct API endpoint and HTTP method.', 'wp-dialyra' );
            } else {
                $this->message = esc_html__( 'An unknown error occurred.', 'wp-dialyra' );
            }

            // Preserve validation error details if available in the raw data.
            if ( isset( $data['errors'] ) && is_array( $data['errors'] ) ) {
                $this->errors = $data['errors'];
            } elseif ( isset( $data['validation']['errors'] ) && is_array( $data['validation']['errors'] ) ) {
                $this->errors = $data['validation']['errors'];
            }
        } else {
            $this->message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Request successful.', 'wp-dialyra' );
        }

        // Handle redirect_url from data if present.
        if ( isset( $data['redirect_url'] ) && is_string( $data['redirect_url'] ) ) {
            $this->redirect_url = esc_url_raw( $data['redirect_url'] );
        }
    }

    /**
     * Map HTTP status codes to normalized error types.
     *
     * @since    1.0.0
     * @param    int       $status_code    The HTTP status code.
     * @return   string    The normalized error type.
     */
    private function map_error_type( $status_code ) {
        if ( $status_code >= 200 && $status_code < 300 ) {
            return null; // Not an error.
        }

        switch ( $status_code ) {
            case 0: // Custom code for network/timeout errors before HTTP response.
                return 'network_error';
            case 400:
                return 'bad_request';
            case 401:
                return 'unauthenticated';
            case 403:
                return 'forbidden';
            case 404:
                return 'not_found';
            case 405:
                return 'method_not_allowed';
            case 409:
                return 'conflict';
            case 422:
                return 'validation_error';
            case 429:
                return 'rate_limited';
            case 500:
            case 501:
            case 502:
            case 503:
            case 504:
            case 505:
                return 'server_error';
            default:
                return 'unknown_error';
        }
    }

    /**
     * Check if the API request was successful.
     *
     * @since    1.0.0
     * @return   bool    True if successful, false otherwise.
     */
    public function is_successful() {
        return $this->success;
    }

    /**
     * Check if the API request resulted in an error.
     *
     * @since    1.0.0
     * @return   bool    True if there was an error, false otherwise.
     */
    public function is_error() {
        return ! $this->success;
    }

    /**
     * Get the API response data.
     *
     * @since    1.0.0
     * @return   array|null    The API response data.
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Get the HTTP status code.
     *
     * @since    1.0.0
     * @return   int    The HTTP status code.
     */
    public function get_status_code() {
        return $this->status_code;
    }

    /**
     * Get the error type.
     *
     * @since    1.0.0
     * @return   string|null    The error type.
     */
    public function get_error_type() {
        return $this->error_type;
    }

    /**
     * Get the human-readable message.
     *
     * @since    1.0.0
     * @return   string    The message.
     */
    public function get_message() {
        return $this->message;
    }

    /**
     * Get detailed errors.
     *
     * @since    1.0.0
     * @return   array    An array of detailed errors.
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get the redirect URL.
     *
     * @since    1.0.0
     * @return   string|null    The redirect URL.
     */
    public function get_redirect_url() {
        return $this->redirect_url;
    }

    /**
     * Convert the response object to an array.
     *
     * @since    1.0.0
     * @return   array    The response as an associative array.
     */
    public function to_array() {
        return array(
            'success'       => $this->is_successful(),
            'status_code'   => $this->get_status_code(),
            'error_type'    => $this->get_error_type(),
            'message'       => $this->get_message(),
            'data'          => $this->get_data(),
            'errors'        => $this->get_errors(),
            'redirect_url'  => $this->get_redirect_url(),
        );
    }
}
