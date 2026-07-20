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

}
