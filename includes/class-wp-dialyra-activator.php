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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/flow/class-dialyra-flow-product-assignment-manager.php';

		Dialyra_Flow_Product_Assignment_Manager::install_table();
	}

}
