<?php

/**
 * Dialyra flow resolver.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/flow
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Flow_Resolver {

	/**
	 * Flow manager.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Flow_Manager
	 */
	private $flow_manager;

	/**
	 * Product assignment manager.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Flow_Product_Assignment_Manager
	 */
	private $product_assignment_manager;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_Flow_Manager                       $flow_manager                  Flow manager.
	 * @param    Dialyra_Flow_Product_Assignment_Manager    $product_assignment_manager    Product assignment manager.
	 */
	public function __construct( Dialyra_Flow_Manager $flow_manager, Dialyra_Flow_Product_Assignment_Manager $product_assignment_manager ) {
		$this->flow_manager               = $flow_manager;
		$this->product_assignment_manager = $product_assignment_manager;
	}

	/**
	 * Resolve the correct Dialyra flow for a WooCommerce order.
	 *
	 * @since    1.0.0
	 * @param    int    $order_id       WooCommerce order ID.
	 * @param    int    $business_id    Dialyra business ID.
	 * @return   array
	 */
	public function resolve_for_order( $order_id, $business_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return $this->failure( 'woocommerce_unavailable', __( 'WooCommerce is not available.', 'wp-dialyra' ) );
		}

		$order = wc_get_order( absint( $order_id ) );

		if ( ! $order ) {
			return $this->failure( 'order_not_found', __( 'WooCommerce order was not found.', 'wp-dialyra' ) );
		}

		$product_ids      = $this->get_order_product_ids( $order );
		$assigned_flow_id = $this->resolve_product_flow_id( $business_id, $product_ids );

		if ( $assigned_flow_id && $this->is_flow_available( $assigned_flow_id ) ) {
			return $this->success( $assigned_flow_id, 'product', $product_ids );
		}

		$default_flow_id = $this->flow_manager->get_default_flow_id();

		if ( $default_flow_id ) {
			return $this->success( $default_flow_id, 'default', $product_ids );
		}

		return $this->failure( 'flow_not_configured', __( 'No product-specific or default Dialyra flow is configured.', 'wp-dialyra' ) );
	}

	/**
	 * Resolve unique product-specific flow ID.
	 *
	 * @since    1.0.0
	 * @param    int      $business_id    Dialyra business ID.
	 * @param    array    $product_ids    Product IDs.
	 * @return   int
	 */
	private function resolve_product_flow_id( $business_id, $product_ids ) {
		if ( empty( $product_ids ) ) {
			return 0;
		}

		$assignments = $this->product_assignment_manager->get_flow_ids_for_products( $business_id, $product_ids );
		$flow_ids    = array_values( array_unique( array_filter( array_map( 'absint', array_values( $assignments ) ) ) ) );

		return 1 === count( $flow_ids ) ? absint( $flow_ids[0] ) : 0;
	}

	/**
	 * Get product IDs from an order.
	 *
	 * @since    1.0.0
	 * @param    WC_Order    $order    WooCommerce order.
	 * @return   array
	 */
	private function get_order_product_ids( $order ) {
		$product_ids = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) ) {
				continue;
			}

			$product_id = absint( $item->get_product_id() );

			if ( $product_id ) {
				$product_ids[] = $product_id;
			}
		}

		return array_values( array_unique( $product_ids ) );
	}

	/**
	 * Check whether a flow is available in Dialyra.
	 *
	 * @since    1.0.0
	 * @param    int    $flow_id    Flow ID.
	 * @return   bool
	 */
	private function is_flow_available( $flow_id ) {
		$response = $this->flow_manager->get_flow( $flow_id );

		if ( $response && $response->is_successful() ) {
			return true;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'WP Dialyra: product-specific flow %d is unavailable; falling back to default flow.', absint( $flow_id ) ) );
		}

		return false;
	}

	/**
	 * Build successful resolver result.
	 *
	 * @since    1.0.0
	 * @param    int       $flow_id    Flow ID.
	 * @param    string    $source     Resolution source.
	 * @return   array
	 */
	private function success( $flow_id, $source, $product_ids = array() ) {
		return array(
			'success'     => true,
			'flow_id'     => absint( $flow_id ),
			'source'      => sanitize_key( $source ),
			'product_ids' => is_array( $product_ids ) ? array_values( array_filter( array_unique( array_map( 'absint', $product_ids ) ) ) ) : array(),
		);
	}

	/**
	 * Build failed resolver result.
	 *
	 * @since    1.0.0
	 * @param    string    $error_type    Error type.
	 * @param    string    $message       Error message.
	 * @return   array
	 */
	private function failure( $error_type, $message ) {
		return array(
			'success'    => false,
			'error_type' => sanitize_key( $error_type ),
			'message'    => sanitize_text_field( $message ),
		);
	}
}
