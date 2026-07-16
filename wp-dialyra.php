<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://triizync.com
 * @since             1.0.0
 * @package           Wp_Dialyra
 *
 * @wordpress-plugin
 * Plugin Name:       WP Dialyra
 * Plugin URI:        https://dialyra.com
 * Description:       The purpose of the WP Dialyra is to connect a WooCommerce store with Dialyra so the store can automatically and manually place customer calls around orders.
 * Version:           1.0.0
 * Author:            Trizync Solution
 * Author URI:        https://triizync.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-dialyra
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WP_DIALYRA_VERSION', '1.0.0' );

/**
 * Default SIP domain agents use when registering extensions in softphone apps.
 */
define( 'WP_DIALYRA_SIP_DOMAIN', 'dialyra.com' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-dialyra-activator.php
 */
function activate_wp_dialyra() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-dialyra-activator.php';
	Wp_Dialyra_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-dialyra-deactivator.php
 */
function deactivate_wp_dialyra() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-dialyra-deactivator.php';
	Wp_Dialyra_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_dialyra' );
register_deactivation_hook( __FILE__, 'deactivate_wp_dialyra' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-dialyra.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_dialyra() {

	$plugin = new Wp_Dialyra();
	$plugin->run();

}
run_wp_dialyra();
