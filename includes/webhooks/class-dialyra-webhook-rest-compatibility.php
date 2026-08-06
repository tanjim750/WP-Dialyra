<?php

/**
 * Dialyra webhook REST compatibility handler.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/webhooks
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Webhook_REST_Compatibility {

	/**
	 * Exact webhook REST route.
	 *
	 * @var string
	 */
	const ROUTE = '/dialyra/v1/webhooks/call-events';

	/**
	 * Allow only the public Dialyra webhook through generic REST auth blocks.
	 *
	 * @param mixed $result Existing authentication result.
	 * @return mixed
	 */
	public function allow_webhook_route( $result ) {
		if ( ! $this->is_dialyra_webhook_request() ) {
			return $result;
		}

		if ( null === $result || true === $result ) {
			return $result;
		}

		if ( is_wp_error( $result ) && $this->is_generic_rest_block( $result ) ) {
			return null;
		}

		return $result;
	}

	/**
	 * Detect the exact Dialyra webhook request route.
	 *
	 * @return bool
	 */
	private function is_dialyra_webhook_request() {
		$route = '';

		if ( isset( $_GET['rest_route'] ) ) {
			$route = wp_unslash( $_GET['rest_route'] );
		} elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$route = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
			$route = $this->strip_wp_json_prefix( $route );
		}

		$route = $this->normalize_route( $route );

		return self::ROUTE === $route;
	}

	/**
	 * Strip the WordPress REST URL prefix from a request path.
	 *
	 * @param string $path Request path.
	 * @return string
	 */
	private function strip_wp_json_prefix( $path ) {
		$path     = '/' . ltrim( (string) $path, '/' );
		$rest_url = rest_url();
		$rest_path = $rest_url ? wp_parse_url( $rest_url, PHP_URL_PATH ) : '/wp-json/';
		$rest_path = '/' . trim( (string) $rest_path, '/' );

		if ( $rest_path && 0 === strpos( $path, $rest_path ) ) {
			return substr( $path, strlen( $rest_path ) );
		}

		if ( 0 === strpos( $path, '/wp-json' ) ) {
			return substr( $path, strlen( '/wp-json' ) );
		}

		return $path;
	}

	/**
	 * Normalize route formatting for exact comparison.
	 *
	 * @param string $route Route value.
	 * @return string
	 */
	private function normalize_route( $route ) {
		$route = rawurldecode( (string) $route );
		$route = strtok( $route, '?' );
		$route = '/' . trim( $route, '/' );

		return untrailingslashit( $route );
	}

	/**
	 * Check if a WP_Error is a generic REST-authentication block.
	 *
	 * @param WP_Error $error REST auth error.
	 * @return bool
	 */
	private function is_generic_rest_block( WP_Error $error ) {
		return in_array(
			$error->get_error_code(),
			array(
				'rest_disabled',
				'rest_not_logged_in',
				'rest_login_required',
				'rest_cannot_access',
				'rest_forbidden',
				'rest_cookie_invalid_nonce',
			),
			true
		);
	}
}
