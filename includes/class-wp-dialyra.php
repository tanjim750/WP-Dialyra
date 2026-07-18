<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://triizync.com
 * @since      1.0.0
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/includes
 * @author     Trizync Solution <trizyncsolution@gmail.com>
 */
class Wp_Dialyra {

	/**
	 * Singleton plugin instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wp_Dialyra
	 */
	private static $instance = null;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Dialyra_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The Dialyra API config object.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_API_Config
	 */
	protected $api_config;

	/**
	 * The Dialyra API client object.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_API_Client
	 */
	protected $api_client;

	/**
	 * The Dialyra API endpoints object.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_API_Endpoints
	 */
	protected $api_endpoints;

	/**
	 * The Dialyra business manager object.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_Business_Manager
	 */
	protected $business_manager;

	/**
	 * The Dialyra flow manager object.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_Flow_Manager
	 */
	protected $flow_manager;

	/**
	 * The Dialyra flow product assignment manager object.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_Flow_Product_Assignment_Manager
	 */
	protected $flow_product_assignment_manager;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		self::$instance = $this;

		if ( defined( 'WP_DIALYRA_VERSION' ) ) {
			$this->version = WP_DIALYRA_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wp-dialyra';

		$this->load_dependencies();
		$this->define_api_services();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Get the singleton plugin instance.
	 *
	 * @since     1.0.0
	 * @return    Wp_Dialyra|null    The plugin instance.
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Define shared API services.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_api_services() {
		$this->api_config       = new Dialyra_API_Config();
		$this->api_client       = new Dialyra_API_Client( $this->api_config );
		$this->api_endpoints    = new Dialyra_API_Endpoints( $this->api_client );
		$this->business_manager = new Dialyra_Business_Manager( $this->api_client, $this->api_endpoints );
		$this->flow_manager     = new Dialyra_Flow_Manager( $this->api_endpoints );
		$this->flow_product_assignment_manager = new Dialyra_Flow_Product_Assignment_Manager();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Dialyra_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Dialyra_i18n. Defines internationalization functionality.
	 * - Wp_Dialyra_Admin. Defines all hooks for the admin area.
	 * - Wp_Dialyra_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * Shared Dialyra API, auth, setup, and manager services.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/constant.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/api/class-dialyra-api-config.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/api/class-dialyra-api-response.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/api/class-dialyra-api-client.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/api/class-dialyra-api-endpoints.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/auth/class-dialyra-auth-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/business/class-dialyra-business-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/flow/class-dialyra-frontend-flow-json-builder.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/flow/class-dialyra-flow-compiler.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/flow/class-dialyra-flow-graph-decompiler.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/flow/class-dialyra-flow-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/flow/class-dialyra-flow-product-assignment-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/utils.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-dialyra-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-dialyra-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-dialyra-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-dialyra-public.php';

		$this->loader = new Wp_Dialyra_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Dialyra_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Dialyra_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wp_Dialyra_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'in_admin_header', $plugin_admin, 'remove_admin_notices', 1 );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_post_wp_dialyra_stream_audio', $plugin_admin, 'stream_audio_asset' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Dialyra_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Dialyra_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get the API endpoints service.
	 *
	 * @since     1.0.0
	 * @return    Dialyra_API_Endpoints
	 */
	public function get_api_endpoints() {
		return $this->api_endpoints;
	}

	/**
	 * Get the business manager service.
	 *
	 * @since     1.0.0
	 * @return    Dialyra_Business_Manager
	 */
	public function get_business_manager() {
		return $this->business_manager;
	}

	/**
	 * Get the flow manager service.
	 *
	 * @since     1.0.0
	 * @return    Dialyra_Flow_Manager
	 */
	public function get_flow_manager() {
		return $this->flow_manager;
	}

	/**
	 * Get the flow product assignment manager service.
	 *
	 * @since     1.0.0
	 * @return    Dialyra_Flow_Product_Assignment_Manager
	 */
	public function get_flow_product_assignment_manager() {
		return $this->flow_product_assignment_manager;
	}

}
