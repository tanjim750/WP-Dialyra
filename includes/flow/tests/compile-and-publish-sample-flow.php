#!/usr/bin/env php
<?php

/**
 * Compile the sample frontend UI flow and publish it to Dialyra.
 *
 * Usage:
 *   php includes/flow/tests/compile-and-publish-sample-flow.php
 *   php includes/flow/tests/compile-and-publish-sample-flow.php --dry-run
 *   php includes/flow/tests/compile-and-publish-sample-flow.php --business-id=11 --token=JWT
 *   php includes/flow/tests/compile-and-publish-sample-flow.php --host=http://127.0.0.1:5001/
 *   php includes/flow/tests/compile-and-publish-sample-flow.php --fixture=/path/to/ui-flow.json
 *
 * The fixture is menu-oriented UI JSON. The script normalizes it with
 * Dialyra_Frontend_Flow_Json_Builder, compiles it with Dialyra_Flow_Compiler,
 * then sends the compiled graph payload directly to
 * /api/v2/flows/create-and-publish. It is intentionally WordPress-independent.
 *
 * @package Wp_Dialyra
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( "This test runner must be executed from CLI.\n" );
}

$options = getopt(
	'',
	array(
		'business-id::',
		'dry-run',
		'fixture::',
		'host::',
		'token::',
	)
);

$fixture_path = ! empty( $options['fixture'] ) ? $options['fixture'] : __DIR__ . '/sample-ui-flow.json';
$dry_run      = array_key_exists( 'dry-run', $options );
$business_id  = ! empty( $options['business-id'] ) ? abs( intval( $options['business-id'] ) ) : null;
$host         = ! empty( $options['host'] ) ? $options['host'] : ( getenv( 'DIALYRA_TEST_HOST' ) ?: 'http://127.0.0.1:5001/' );
$token        = ! empty( $options['token'] ) ? $options['token'] : getenv( 'DIALYRA_ACCESS_TOKEN' );

wp_dialyra_test_bootstrap_wordpress_helpers();

if ( ! class_exists( 'Dialyra_Frontend_Flow_Json_Builder' ) ) {
	require_once dirname( __DIR__ ) . '/class-dialyra-frontend-flow-json-builder.php';
}

if ( ! class_exists( 'Dialyra_Flow_Compiler' ) ) {
	require_once dirname( __DIR__ ) . '/class-dialyra-flow-compiler.php';
}

if ( ! file_exists( $fixture_path ) ) {
	wp_dialyra_test_fail( 'Fixture file not found: ' . $fixture_path );
}

$raw_fixture = file_get_contents( $fixture_path );
$ui_flow     = json_decode( $raw_fixture, true );

if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $ui_flow ) ) {
	wp_dialyra_test_fail( 'Fixture JSON is invalid: ' . json_last_error_msg() );
}

$builder      = new Dialyra_Frontend_Flow_Json_Builder();
$build_result = $builder->build( $ui_flow );

if ( empty( $build_result['valid'] ) ) {
	wp_dialyra_test_print_result( 'Frontend UI flow validation failed.', $build_result );
	exit( 1 );
}

$compiler       = new Dialyra_Flow_Compiler();
$compile_result = $compiler->compile( $build_result['flow'], $business_id );

if ( empty( $compile_result['valid'] ) ) {
	wp_dialyra_test_print_result( 'Flow graph compilation failed.', $compile_result );
	exit( 1 );
}

$payload = $compile_result['payload'];

wp_dialyra_test_print_summary( $payload, $compile_result );

if ( $dry_run ) {
	echo "\nDry run enabled. Compiled payload:\n";
	echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
	exit( 0 );
}

if ( empty( $token ) ) {
	wp_dialyra_test_fail( 'Missing token. Pass --token=JWT or set DIALYRA_ACCESS_TOKEN.' );
}

$response = wp_dialyra_test_post_json(
	wp_dialyra_test_api_url( $host, 'api/v2/flows/create-and-publish' ),
	$payload,
	$token
);

if ( $response['status_code'] < 200 || $response['status_code'] >= 300 ) {
	wp_dialyra_test_print_result( 'Create-and-publish failed.', $response );
	exit( 1 );
}

wp_dialyra_test_print_result( 'Create-and-publish succeeded.', $response );

/**
 * Bootstrap only the WordPress helpers needed by the flow classes.
 */
function wp_dialyra_test_bootstrap_wordpress_helpers() {
	if ( ! defined( 'WPINC' ) ) {
		define( 'WPINC', true );
	}

	if ( ! function_exists( '__' ) ) {
		function __( $message, $domain = null ) {
			return $message;
		}
	}

	if ( ! function_exists( 'wp_unslash' ) ) {
		function wp_unslash( $value ) {
			return $value;
		}
	}

	if ( ! function_exists( 'sanitize_text_field' ) ) {
		function sanitize_text_field( $value ) {
			return trim( strip_tags( (string) $value ) );
		}
	}

	if ( ! function_exists( 'sanitize_key' ) ) {
		function sanitize_key( $value ) {
			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
		}
	}

	if ( ! function_exists( 'absint' ) ) {
		function absint( $value ) {
			return abs( intval( $value ) );
		}
	}

	if ( ! function_exists( 'wp_json_encode' ) ) {
		function wp_json_encode( $data, $flags = 0, $depth = 512 ) {
			return json_encode( $data, $flags, $depth );
		}
	}
}

/**
 * Build direct API URL.
 *
 * @param    string    $host       API host.
 * @param    string    $endpoint   Endpoint path.
 * @return   string
 */
function wp_dialyra_test_api_url( $host, $endpoint ) {
	return rtrim( $host, '/' ) . '/' . ltrim( $endpoint, '/' );
}

/**
 * POST JSON using PHP streams so this test does not require WordPress or cURL.
 *
 * @param    string    $url      URL.
 * @param    array     $payload  Request payload.
 * @param    string    $token    Bearer token.
 * @return   array
 */
function wp_dialyra_test_post_json( $url, $payload, $token ) {
	$body = wp_json_encode( $payload );

	$context = stream_context_create(
		array(
			'http' => array(
				'method'        => 'POST',
				'ignore_errors' => true,
				'timeout'       => 30,
				'header'        => implode(
					"\r\n",
					array(
						'Content-Type: application/json',
						'Accept: application/json',
						'Authorization: Bearer ' . $token,
					)
				),
				'content'       => $body,
			),
		)
	);

	$response_body = file_get_contents( $url, false, $context );
	$status_code   = 0;
	$headers       = function_exists( 'http_get_last_response_headers' ) ? http_get_last_response_headers() : array();
	$headers       = is_array( $headers ) ? $headers : array();

	foreach ( $headers as $header ) {
		if ( preg_match( '/^HTTP\/\\S+\\s+(\\d+)/', $header, $matches ) ) {
			$status_code = intval( $matches[1] );
			break;
		}
	}

	$decoded = is_string( $response_body ) ? json_decode( $response_body, true ) : null;

	return array(
		'url'         => $url,
		'status_code' => $status_code,
		'headers'     => $headers,
		'body'        => JSON_ERROR_NONE === json_last_error() ? $decoded : $response_body,
	);
}

/**
 * Print compiled graph summary.
 *
 * @param    array    $payload          Compiled payload.
 * @param    array    $compile_result   Compiler result.
 */
function wp_dialyra_test_print_summary( $payload, $compile_result ) {
	echo "Frontend flow compiled successfully.\n";
	echo 'Flow: ' . ( $payload['flow']['name'] ?? '' ) . "\n";
	echo 'Business ID: ' . ( $payload['flow']['business_id'] ?? '' ) . "\n";
	echo 'Start node: ' . ( $payload['start_node_key'] ?? '' ) . "\n";
	echo 'Nodes: ' . count( $payload['nodes'] ?? array() ) . "\n";
	echo 'Edges: ' . count( $payload['edges'] ?? array() ) . "\n";

	if ( ! empty( $compile_result['warnings'] ) ) {
		echo "Warnings:\n";
		echo wp_json_encode( $compile_result['warnings'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
	}
}

/**
 * Print structured result.
 *
 * @param    string    $title    Result title.
 * @param    mixed     $data     Result data.
 */
function wp_dialyra_test_print_result( $title, $data ) {
	echo $title . "\n";
	echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
}

/**
 * Print error and exit.
 *
 * @param    string    $message    Error message.
 */
function wp_dialyra_test_fail( $message ) {
	fwrite( STDERR, $message . "\n" );
	exit( 1 );
}
