<?php

/**
 * Dialyra order action listener.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/orders
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Order_Action_Listener {

	/**
	 * Order meta manager.
	 *
	 * @since    1.0.0
	 * @var      Dialyra_Order_Meta_Manager
	 */
	private $meta_manager;

	/**
	 * Construct listener.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->meta_manager = new Dialyra_Order_Meta_Manager();
	}

	/**
	 * Read completed call events and dispatch order action hook.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Normalized Dialyra event.
	 */
	public function handle_call_event( $event ) {
		$event = is_array( $event ) ? $event : array();

		if ( 'call.completed' !== ( $event['event_type'] ?? '' ) ) {
			return;
		}

		$order_id     = absint( $event['order_id'] ?? 0 );
		$order_action = sanitize_key( $event['order_action'] ?? 'none' );

		if ( ! $order_id ) {
			return;
		}

		do_action( Dialyra_Hook_Names::get_or_default( 'order', 'order_action_received', 'dialyra_order_action_received' ), $order_id, $order_action, $event );
	}

	/**
	 * Process confirmed/cancelled order actions.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id        WooCommerce order ID.
	 * @param    string    $order_action    Dialyra order action.
	 * @param    array     $event           Normalized event.
	 */
	public function handle_order_action( $order_id, $order_action, $event ) {
		$order_id     = absint( $order_id );
		$order_action = sanitize_key( $order_action );
		$event        = is_array( $event ) ? $event : array();

		if ( 'confirmed' === $order_action ) {
			do_action( Dialyra_Hook_Names::get_or_default( 'order', 'order_confirmed', 'dialyra_order_confirmed' ), $order_id, $event );
		} elseif ( 'cancelled' === $order_action ) {
			do_action( Dialyra_Hook_Names::get_or_default( 'order', 'order_cancelled', 'dialyra_order_cancelled' ), $order_id, $event );
		}

		do_action( Dialyra_Hook_Names::get_or_default( 'order', 'order_action_processed', 'dialyra_order_action_processed' ), $order_id, $order_action, $event );
	}

	/**
	 * Read failed/no-answer/busy call events and dispatch call status hooks.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Normalized Dialyra event.
	 */
	public function handle_call_status_event( $event ) {
		$event    = is_array( $event ) ? $event : array();
		$order_id = absint( $event['order_id'] ?? 0 );

		if ( ! $order_id ) {
			return;
		}

		$call_status = $this->normalize_call_status( $event );

		if ( 'no_answer' === $call_status ) {
			do_action( Dialyra_Hook_Names::get_or_default( 'call', 'call_no_answer', 'dialyra_call_no_answer' ), $order_id, $event );
		} elseif ( 'busy' === $call_status ) {
			do_action( Dialyra_Hook_Names::get_or_default( 'call', 'call_busy', 'dialyra_call_busy' ), $order_id, $event );
		} elseif ( 'failed' === $call_status ) {
			do_action( Dialyra_Hook_Names::get_or_default( 'call', 'call_failed', 'dialyra_call_failed' ), $order_id, $event );
		}
	}

	/**
	 * Process a confirmed order hook.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    array    $event       Normalized event.
	 */
	public function process_confirmed_order( $order_id, $event ) {
		$this->process_mapped_order_status( $order_id, $event, 'confirmed_status', 'confirmed_note', 'processing' );
	}

	/**
	 * Process a cancelled order hook.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    array    $event       Normalized event.
	 */
	public function process_cancelled_order( $order_id, $event ) {
		$this->process_mapped_order_status( $order_id, $event, 'cancelled_status', 'cancelled_note', 'cancelled' );
	}

	/**
	 * Process a no-answer call hook.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    array    $event       Normalized event.
	 */
	public function process_no_answer_call( $order_id, $event ) {
		$this->process_mapped_order_status( $order_id, $event, 'no_answer_status', 'no_answer_note', 'no_change' );
	}

	/**
	 * Process a busy call hook.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    array    $event       Normalized event.
	 */
	public function process_busy_call( $order_id, $event ) {
		$this->process_mapped_order_status( $order_id, $event, 'busy_status', 'busy_note', 'no_change' );
	}

	/**
	 * Process a failed call hook.
	 *
	 * @since    1.0.0
	 * @param    int      $order_id    WooCommerce order ID.
	 * @param    array    $event       Normalized event.
	 */
	public function process_failed_call( $order_id, $event ) {
		$this->process_mapped_order_status( $order_id, $event, 'failed_status', 'failed_note', 'no_change' );
	}

	/**
	 * Apply an order status/note mapping to an order.
	 *
	 * @since    1.0.0
	 * @param    int       $order_id        WooCommerce order ID.
	 * @param    array     $event           Normalized event.
	 * @param    string    $status_key      Mapping status key.
	 * @param    string    $note_key        Mapping note key.
	 * @param    string    $fallback_status Fallback order status.
	 */
	private function process_mapped_order_status( $order_id, $event, $status_key, $note_key, $fallback_status ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( absint( $order_id ) );

		if ( ! $order ) {
			return;
		}

		$mapping = $this->get_order_status_mapping();
		$status  = $this->normalize_order_status( $mapping[ $status_key ] ?? $fallback_status, $fallback_status );
		$note    = $this->get_mapping_note( $mapping, $note_key );

		if ( 'no_change' === $status ) {
			if ( $note && method_exists( $order, 'add_order_note' ) ) {
				$order->add_order_note( $note );
			}
		} else {
			$order->update_status( $status, $note );
		}

		$this->meta_manager->update_latest_call_meta( $order, is_array( $event ) ? $event : array() );

		if ( method_exists( $order, 'save' ) ) {
			$order->save();
		}
	}

	/**
	 * Get saved order status mapping merged with defaults.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function get_order_status_mapping() {
		$defaults = class_exists( 'Wp_Dialyra_Utils' ) ? Wp_Dialyra_Utils::get_order_status_mapping_defaults() : array(
			'confirmed_status' => 'processing',
			'cancelled_status' => 'cancelled',
			'no_answer_status' => 'no_change',
			'busy_status'      => 'no_change',
			'failed_status'    => 'no_change',
			'confirmed_note'   => defined( 'WP_DIALYRA_DEFAULT_ORDER_CONFIRMED_NOTE' ) ? WP_DIALYRA_DEFAULT_ORDER_CONFIRMED_NOTE : __( 'Dialyra call confirmed the order.', 'wp-dialyra' ),
			'cancelled_note'   => defined( 'WP_DIALYRA_DEFAULT_ORDER_CANCELLED_NOTE' ) ? WP_DIALYRA_DEFAULT_ORDER_CANCELLED_NOTE : __( 'Dialyra call cancelled the order.', 'wp-dialyra' ),
			'no_answer_note'   => defined( 'WP_DIALYRA_DEFAULT_CALL_NO_ANSWER_NOTE' ) ? WP_DIALYRA_DEFAULT_CALL_NO_ANSWER_NOTE : __( 'Dialyra call ended with no answer.', 'wp-dialyra' ),
			'busy_note'        => defined( 'WP_DIALYRA_DEFAULT_CALL_BUSY_NOTE' ) ? WP_DIALYRA_DEFAULT_CALL_BUSY_NOTE : __( 'Dialyra call reached a busy line.', 'wp-dialyra' ),
			'failed_note'      => defined( 'WP_DIALYRA_DEFAULT_CALL_FAILED_NOTE' ) ? WP_DIALYRA_DEFAULT_CALL_FAILED_NOTE : __( 'Dialyra call failed.', 'wp-dialyra' ),
		);
		$settings = defined( 'WP_DIALYRA_OPTION_SETUP_SETTINGS' ) ? get_option( WP_DIALYRA_OPTION_SETUP_SETTINGS, array() ) : array();
		$mapping  = is_array( $settings ) && is_array( $settings['order_status_map'] ?? null ) ? $settings['order_status_map'] : array();

		return array_merge( $defaults, $mapping );
	}

	/**
	 * Get configured order status mapping note.
	 *
	 * @since    1.0.0
	 * @param    array     $mapping    Status mapping.
	 * @param    string    $key        Mapping note key.
	 * @return   string
	 */
	private function get_mapping_note( $mapping, $key ) {
		$note = ! empty( $mapping[ $key ] ) ? sanitize_text_field( $mapping[ $key ] ) : '';

		return $note;
	}

	/**
	 * Normalize a mapped WooCommerce status.
	 *
	 * @since    1.0.0
	 * @param    string    $status           Raw status.
	 * @param    string    $fallback_status  Fallback status.
	 * @return   string
	 */
	private function normalize_order_status( $status, $fallback_status ) {
		$status = sanitize_key( $status );

		if ( 'no_change' === $status ) {
			return 'no_change';
		}

		if ( 0 === strpos( $status, 'wc-' ) ) {
			$status = substr( $status, 3 );
		}

		return $status ? $status : sanitize_key( $fallback_status );
	}

	/**
	 * Normalize Dialyra call status for custom hook dispatching.
	 *
	 * @since    1.0.0
	 * @param    array    $event    Normalized Dialyra event.
	 * @return   string
	 */
	private function normalize_call_status( $event ) {
		$status = strtolower( sanitize_text_field( $event['call_status'] ?? '' ) );
		$status = str_replace( array( '-', ' ' ), '_', $status );

		if ( in_array( $status, array( 'no_answer', 'busy', 'failed' ), true ) ) {
			return $status;
		}

		return 'call.failed' === ( $event['event_type'] ?? '' ) ? 'failed' : '';
	}
}
