<?php

/**
 * Fired during plugin activation
 *
 * @link       https://triizync.com
 * @since      1.0.0
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/includes
 * @author     Trizync Solution <trizyncsolution@gmail.com>
 */
class Wp_Dialyra_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/constant.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/events/class-dialyra-events.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/events/class-dialyra-hook-names.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/flow/class-dialyra-flow-product-assignment-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/webhooks/class-dialyra-webhook-idempotency.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/audit/class-dialyra-audit-log-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/calls/class-dialyra-call-log-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/triggers/class-dialyra-call-queue-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/retries/class-dialyra-retry-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/entrypoints/class-dialyra-scheduler-entrypoints.php';

		Dialyra_Flow_Product_Assignment_Manager::install_table();
		Dialyra_Webhook_Idempotency::install_table();
		if ( Dialyra_Audit_Log_Repository::is_enabled() ) {
			Dialyra_Audit_Log_Repository::install_table();
		}
		Dialyra_Call_Log_Repository::install_table();
		Dialyra_Call_Queue_Repository::install_table();
		Dialyra_Retry_Repository::install_table();
		Dialyra_Scheduler_Entrypoints::activate();
	}

}
