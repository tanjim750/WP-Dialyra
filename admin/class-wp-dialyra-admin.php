<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://triizync.com
 * @since      1.0.0
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin
 * @author     Trizync Solution <trizyncsolution@gmail.com>
 */
class Wp_Dialyra_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Dialyra_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Dialyra_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'pages/assets/css/wp-dialyra-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Dialyra_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Dialyra_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'pages/assets/js/wp-dialyra-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Remove unrelated admin notices from the WP Dialyra page.
	 *
	 * @since    1.0.0
	 */
	public function remove_admin_notices() {

		if ( ! $this->is_plugin_admin_page() ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_all_actions( 'network_admin_notices' );

	}

	/**
	 * Register the WP Dialyra admin menu page.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		add_menu_page(
			__( 'WP Dialyra', 'wp-dialyra' ),
			__( 'WP Dialyra', 'wp-dialyra' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-phone',
			56
		);

	}

	/**
	 * Render the WP Dialyra admin menu page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {

		require_once plugin_dir_path( __FILE__ ) . 'pages/wp-dialyra-admin-display.php';

	}

	/**
	 * Check whether the current admin screen belongs to WP Dialyra.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	private function is_plugin_admin_page() {

		return isset( $_GET['page'] ) && $this->plugin_name === sanitize_key( wp_unslash( $_GET['page'] ) );

	}

}
