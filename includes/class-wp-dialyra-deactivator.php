<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://triizync.com
 * @since      1.0.0
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/includes
 * @author     Trizync Solution <trizyncsolution@gmail.com>
 */
class Wp_Dialyra_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/constant.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/events/class-dialyra-events.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/events/class-dialyra-hook-names.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/entrypoints/class-dialyra-scheduler-entrypoints.php';

		Dialyra_Scheduler_Entrypoints::deactivate();

	}

}
