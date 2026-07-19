<?php

/**
 * Dialyra WooCommerce event entrypoints.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/entrypoints
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_WooCommerce_Entrypoints {

	/**
	 * Audit log repository.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Audit_Log_Repository|null
	 */
	private $audit_log_repository;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_Audit_Log_Repository|null    $audit_log_repository    Optional audit logger.
	 */
	public function __construct( $audit_log_repository = null ) {
		$this->audit_log_repository = $audit_log_repository;
	}

	/**
	 * Forward WooCommerce new-order events into Dialyra's internal event system.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    mixed    $order       WooCommerce order object.
	 */
	public function handle_new_order( $order_id, $order = null ) {
		$this->audit_entrypoint( 'woocommerce_new_order', $order_id );
		$this->dispatch_order_created( $order_id );
	}

	/**
	 * Forward checkout-created orders into Dialyra's internal event system.
	 *
	 * Some checkout flows populate final billing/order data after
	 * woocommerce_new_order, so this hook gives the instant trigger a second safe
	 * built-in entrypoint.
	 *
	 * @since    1.0.0
	 * @param    mixed    $order    WooCommerce order object.
	 */
	public function handle_checkout_order_created( $order ) {
		$order_id = $this->get_order_id( $order );
		$this->audit_entrypoint( 'woocommerce_checkout_order_created', $order_id );
		$this->dispatch_order_created( $order_id );
	}

	/**
	 * Forward WooCommerce Blocks checkout orders into Dialyra's event system.
	 *
	 * @since    1.0.0
	 * @param    mixed    $order    WooCommerce order object.
	 */
	public function handle_store_api_checkout_order_processed( $order ) {
		$order_id = $this->get_order_id( $order );
		$this->audit_entrypoint( 'woocommerce_store_api_checkout_order_processed', $order_id );
		$this->dispatch_order_created( $order_id );
	}

	/**
	 * Forward classic checkout order-meta updates into Dialyra's event system.
	 *
	 * This hook fires after checkout data is saved, which is often the safest
	 * point to read billing phone and order items.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    mixed    $data        Checkout data.
	 */
	public function handle_checkout_update_order_meta( $order_id, $data = null ) {
		$this->audit_entrypoint( 'woocommerce_checkout_update_order_meta', $order_id );
		$this->dispatch_order_created( $order_id );
	}

	/**
	 * Forward admin order saves into Dialyra's event system.
	 *
	 * Manual/admin-created orders may not be call-ready until this hook fires.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    mixed    $post        Admin post object.
	 */
	public function handle_admin_order_saved( $order_id, $post = null ) {
		$this->audit_entrypoint( 'woocommerce_process_shop_order_meta', $order_id );
		$this->dispatch_order_created( $order_id );
	}

	/**
	 * Forward payment-complete orders as a final safety net.
	 *
	 * @since    1.0.0
	 * @param    int    $order_id    WooCommerce order ID.
	 */
	public function handle_payment_complete( $order_id ) {
		$this->audit_entrypoint( 'woocommerce_payment_complete', $order_id );
		$this->dispatch_order_created( $order_id );
	}

	/**
	 * Forward WooCommerce thank-you page orders into Dialyra's event system.
	 *
	 * This is useful for gateways/checkouts where order data is fully available
	 * only after the customer reaches the thank-you step.
	 *
	 * @since    1.0.0
	 * @param    int    $order_id    WooCommerce order ID.
	 */
	public function handle_thankyou( $order_id ) {
		$this->audit_entrypoint( 'woocommerce_thankyou', $order_id );
		$this->dispatch_order_created( $order_id );
	}

	/**
	 * Forward saved/updated WooCommerce orders into Dialyra as a settled-data pass.
	 *
	 * Admin-created orders and some gateways can finish billing phone/items after
	 * the first new-order hook. This hook gives automatic triggers a reliable pass
	 * after the order object has been persisted.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    mixed    $order       WooCommerce order object.
	 */
	public function handle_order_updated( $order_id, $order = null ) {
		$this->audit_entrypoint( 'woocommerce_update_order', $order_id );
		$this->dispatch_order_created( $order_id );
	}

	/**
	 * Forward WooCommerce status changes into Dialyra's internal event system.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id      WooCommerce order ID.
	 * @param    string    $old_status    Previous WooCommerce status.
	 * @param    string    $new_status    New WooCommerce status.
	 * @param    mixed     $order         WooCommerce order object.
	 */
	public function handle_order_status_changed( $order_id, $old_status, $new_status, $order = null ) {
		do_action(
			Dialyra_Hook_Names::get_or_default( 'order', 'order_status_changed', 'dialyra_order_status_changed' ),
			absint( $order_id ),
			sanitize_key( $old_status ),
			sanitize_key( $new_status )
		);
	}

	/**
	 * Dispatch the internal order-created event and schedule a settled-data pass.
	 *
	 * @since    1.0.0
	 * @param    int    $order_id    WooCommerce order ID.
	 */
	private function dispatch_order_created( $order_id ) {
		$order_id = absint( $order_id );

		if ( ! $order_id ) {
			return;
		}

		$hook_name = Dialyra_Hook_Names::get_or_default( 'order', 'order_created', 'dialyra_order_created' );

		$this->audit_entrypoint( $hook_name, $order_id );
		do_action( $hook_name, $order_id );

		$this->schedule_settled_order_check( $order_id, $hook_name );
	}

	/**
	 * Log entrypoint execution into the plugin audit table.
	 *
	 * @since    1.0.0
	 * @param    string    $hook_name    Hook that fired.
	 * @param    int       $order_id     WooCommerce order ID.
	 */
	private function audit_entrypoint( $hook_name, $order_id ) {
		if ( ! $this->audit_log_repository || ! method_exists( $this->audit_log_repository, 'log' ) ) {
			return;
		}

		$this->audit_log_repository->log(
			'entrypoint_fired',
			sprintf( 'WooCommerce hook fired: %s', sanitize_key( $hook_name ) ),
			array(
				'order_id'  => absint( $order_id ),
				'hook_name' => sanitize_key( $hook_name ),
			),
			'info',
			'entrypoint'
		);
	}

	/**
	 * Get an order ID from either a WC_Order object or scalar ID.
	 *
	 * @since    1.0.0
	 * @param    mixed    $order    WooCommerce order object or ID.
	 * @return   int
	 */
	private function get_order_id( $order ) {
		if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
			return absint( $order->get_id() );
		}

		return absint( $order );
	}

	/**
	 * Schedule a one-time safety pass after WooCommerce finishes saving order data.
	 *
	 * Admin-created orders can fire the new-order hook before billing phone/items
	 * are fully persisted. The delayed pass uses the same internal hook and is
	 * protected by order-level active-call checks, so a successful instant call is
	 * not duplicated.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id     WooCommerce order ID.
	 * @param    string    $hook_name    Internal Dialyra order-created hook.
	 */
	private function schedule_settled_order_check( $order_id, $hook_name ) {
		$order_id = absint( $order_id );

		if ( ! $order_id || ! function_exists( 'wp_schedule_single_event' ) || ! function_exists( 'wp_next_scheduled' ) ) {
			return;
		}

		$args = array( $order_id );

		if ( wp_next_scheduled( $hook_name, $args ) ) {
			return;
		}

		wp_schedule_single_event( time() + 30, $hook_name, $args );
	}
}
