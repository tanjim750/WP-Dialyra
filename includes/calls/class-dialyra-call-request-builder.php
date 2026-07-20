<?php

/**
 * Dialyra call request builder.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/calls
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Call_Request_Builder {

	/**
	 * Build a runtime originate request for a WooCommerce order.
	 *
	 * @since    1.0.0
	 * @param    WC_Order    $order      WooCommerce order.
	 * @param    int         $flow_id    Dialyra flow ID.
	 * @return   array
	 */
	public function build_order_call_request( $order, $flow_id ) {
		$phone = $this->normalize_phone( is_object( $order ) && method_exists( $order, 'get_billing_phone' ) ? $order->get_billing_phone() : '' );

		if ( ! $phone ) {
			return array(
				'success'    => false,
				'error_type' => 'invalid_phone',
				'message'    => __( 'The order does not contain a valid phone number.', 'wp-dialyra' ),
			);
		}

		$flow_id = absint( $flow_id );

		if ( ! $flow_id ) {
			return array(
				'success'    => false,
				'error_type' => 'flow_not_configured',
				'message'    => __( 'No product-specific or default Dialyra flow is configured.', 'wp-dialyra' ),
			);
		}

		return array(
			'success' => true,
			'payload' => array(
				'phone'             => $phone,
				'flow_id'           => $flow_id,
				'webhook_variables' => array(
					'order_id'     => (string) $order->get_id(),
					'order_action' => 'none',
				),
			),
		);
	}

	/**
	 * Normalize and validate a phone number for Dialyra runtime originate.
	 *
	 * @since    1.0.0
	 * @param    string    $phone    Raw phone number.
	 * @return   string
	 */
	private function normalize_phone( $phone ) {
		$phone = trim( sanitize_text_field( $phone ) );

		if ( '' === $phone ) {
			return '';
		}

		$phone  = preg_replace( '/(?!^\+)[^\d]/', '', $phone );
		$digits = preg_replace( '/\D/', '', $phone );

		if ( strlen( $digits ) < 7 || strlen( $digits ) > 15 ) {
			return '';
		}

		return $phone;
	}
}
