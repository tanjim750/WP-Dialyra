<?php

/**
 * Dialyra scheduler entrypoints.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/entrypoints
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Scheduler_Entrypoints {

	const GROUP = 'wp-dialyra';

	/**
	 * Initial/deferred call queue processor.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Dialyra_Call_Queue_Processor
	 */
	private $call_queue_processor;

	/**
	 * Retry queue processor.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      mixed
	 */
	private $retry_queue_processor;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    Dialyra_Call_Queue_Processor    $call_queue_processor     Call queue processor.
	 * @param    mixed                           $retry_queue_processor    Retry queue processor.
	 */
	public function __construct( $call_queue_processor, $retry_queue_processor = null ) {
		$this->call_queue_processor  = $call_queue_processor;
		$this->retry_queue_processor = $retry_queue_processor;
	}

	/**
	 * Add the one-minute WP-Cron fallback interval.
	 *
	 * @since    1.0.0
	 * @param    array    $schedules    Cron schedules.
	 * @return   array
	 */
	public function add_minute_schedule( $schedules ) {
		return self::add_static_minute_schedule( $schedules );
	}

	/**
	 * Add the one-minute WP-Cron fallback interval statically.
	 *
	 * @since    1.0.0
	 * @param    array    $schedules    Cron schedules.
	 * @return   array
	 */
	public static function add_static_minute_schedule( $schedules ) {
		$schedules['dialyra_every_minute'] = array(
			'interval' => MINUTE_IN_SECONDS,
			'display'  => __( 'Every minute for Dialyra queue processing', 'wp-dialyra' ),
		);

		return $schedules;
	}

	/**
	 * Ensure Dialyra recurring scheduler jobs exist.
	 *
	 * @since    1.0.0
	 */
	public function ensure_recurring_actions() {
		self::ensure_action( self::get_call_queue_hook() );
		self::ensure_action( self::get_retry_queue_hook() );
	}

	/**
	 * Forward scheduled call-queue execution to the queue processor.
	 *
	 * @since    1.0.0
	 * @return   array|null
	 */
	public function process_call_queue() {
		return $this->call_queue_processor ? $this->call_queue_processor->process_due_queue( 10 ) : null;
	}

	/**
	 * Forward scheduled retry-queue execution to the retry processor.
	 *
	 * @since    1.0.0
	 * @return   mixed|null
	 */
	public function process_retry_queue() {
		if ( ! $this->retry_queue_processor ) {
			return null;
		}

		if ( method_exists( $this->retry_queue_processor, 'process_next' ) ) {
			return $this->retry_queue_processor->process_next();
		}

		if ( method_exists( $this->retry_queue_processor, 'process_due_queue' ) ) {
			return $this->retry_queue_processor->process_due_queue();
		}

		return null;
	}

	/**
	 * Ensure recurring scheduler jobs exist without needing an instance.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_static_minute_schedule' ) );

		self::ensure_action( self::get_call_queue_hook() );
		self::ensure_action( self::get_retry_queue_hook() );
	}

	/**
	 * Unschedule Dialyra-owned recurring scheduler jobs.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		self::unschedule_action( self::get_call_queue_hook() );
		self::unschedule_action( self::get_retry_queue_hook() );
	}

	/**
	 * Get call queue processing hook.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public static function get_call_queue_hook() {
		return class_exists( 'Dialyra_Hook_Names' ) ? Dialyra_Hook_Names::get_or_default( 'scheduler', 'process_call_queue', 'dialyra_process_call_queue' ) : 'dialyra_process_call_queue';
	}

	/**
	 * Get retry queue processing hook.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public static function get_retry_queue_hook() {
		return class_exists( 'Dialyra_Hook_Names' ) ? Dialyra_Hook_Names::get_or_default( 'scheduler', 'process_retry_queue', 'dialyra_process_retry_queue' ) : 'dialyra_process_retry_queue';
	}

	/**
	 * Ensure a single recurring action exists.
	 *
	 * @since    1.0.0
	 * @param    string    $hook    Scheduled hook.
	 */
	private static function ensure_action( $hook ) {
		if ( function_exists( 'as_next_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( ! as_next_scheduled_action( $hook, array(), self::GROUP ) ) {
				as_schedule_recurring_action( time() + MINUTE_IN_SECONDS, MINUTE_IN_SECONDS, $hook, array(), self::GROUP );
			}

			return;
		}

		if ( ! wp_next_scheduled( $hook ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'dialyra_every_minute', $hook );
		}
	}

	/**
	 * Unschedule a Dialyra recurring action.
	 *
	 * @since    1.0.0
	 * @param    string    $hook    Scheduled hook.
	 */
	private static function unschedule_action( $hook ) {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $hook, array(), self::GROUP );
		}

		wp_clear_scheduled_hook( $hook );
	}
}
