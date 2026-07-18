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
	 * The Dialyra call originate service.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_Call_Originate_Service
	 */
	protected $call_originate_service;

	/**
	 * The Dialyra local call log repository.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_Call_Log_Repository
	 */
	protected $call_log_repository;

	/**
	 * The Dialyra call trigger manager.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_Call_Trigger_Manager
	 */
	protected $call_trigger_manager;

	/**
	 * The Dialyra call queue processor.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_Call_Queue_Processor
	 */
	protected $call_queue_processor;

	/**
	 * The Dialyra retry repository.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_Retry_Repository
	 */
	protected $retry_repository;

	/**
	 * The Dialyra retry queue processor.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dialyra_Retry_Queue_Processor
	 */
	protected $retry_queue_processor;

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
		$this->define_webhook_hooks();
		$this->define_entrypoint_hooks();

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
		$this->call_log_repository = new Dialyra_Call_Log_Repository();
		$this->call_originate_service = new Dialyra_Call_Originate_Service(
			$this->api_endpoints,
			$this->business_manager,
			new Dialyra_Flow_Resolver( $this->flow_manager, $this->flow_product_assignment_manager ),
			new Dialyra_Call_Request_Builder(),
			$this->call_log_repository
		);

		$call_queue_repository = new Dialyra_Call_Queue_Repository();
		$call_eligibility      = new Dialyra_Call_Eligibility();
		$business_hours        = new Dialyra_Business_Hours();
		$this->retry_repository = new Dialyra_Retry_Repository();
		$this->call_queue_processor = new Dialyra_Call_Queue_Processor(
			$call_queue_repository,
			$call_eligibility,
			$business_hours,
			$this->call_originate_service
		);

		$this->call_trigger_manager = new Dialyra_Call_Trigger_Manager(
			$this->business_manager,
			$call_queue_repository,
			$call_eligibility,
			$business_hours,
			$this->call_originate_service,
			$this->flow_manager,
			$this->flow_product_assignment_manager
		);

		$this->retry_queue_processor = new Dialyra_Retry_Queue_Processor(
			$this->retry_repository,
			new Dialyra_Retry_Policy(),
			$call_eligibility,
			$business_hours,
			$this->call_originate_service
		);
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/flow/class-dialyra-flow-resolver.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/events/class-dialyra-events.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/events/class-dialyra-hook-names.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/webhooks/class-dialyra-webhook-signature.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/webhooks/class-dialyra-webhook-idempotency.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/webhooks/class-dialyra-webhook-event-normalizer.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/webhooks/class-dialyra-webhook-controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/webhooks/class-dialyra-webhook-subscription-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/orders/class-dialyra-order-meta-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/orders/class-dialyra-order-action-listener.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/calls/class-dialyra-call-request-builder.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/calls/class-dialyra-call-log-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/calls/class-dialyra-call-originate-service.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/triggers/class-dialyra-business-hours.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/triggers/class-dialyra-call-eligibility.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/triggers/class-dialyra-call-queue-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/triggers/class-dialyra-call-queue-processor.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/triggers/class-dialyra-call-trigger-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/entrypoints/class-dialyra-woocommerce-entrypoints.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/entrypoints/class-dialyra-scheduler-entrypoints.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/retries/class-dialyra-retry-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/retries/class-dialyra-retry-policy.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/retries/class-dialyra-retry-queue-processor.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/retries/class-dialyra-retry-registrar.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/retries/class-dialyra-retry-listener.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/calls/class-dialyra-call-sync-listener.php';
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
	 * Register webhook transport and internal event listener hooks.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_webhook_hooks() {

		$webhook_controller  = new Dialyra_Webhook_Controller();
		$order_listener      = new Dialyra_Order_Action_Listener();
		$retry_listener      = new Dialyra_Retry_Listener();
		$retry_registrar     = new Dialyra_Retry_Registrar( $this->retry_repository );
		$call_sync_listener  = new Dialyra_Call_Sync_Listener();

		$this->loader->add_action( 'rest_api_init', $webhook_controller, 'register_routes' );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'webhook', 'call_event_received' ), $this->call_log_repository, 'handle_call_event', 5 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'webhook', 'call_event_received' ), $order_listener, 'handle_call_event' );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'webhook', 'call_event_received' ), $order_listener, 'handle_call_status_event' );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'order', 'order_action_received' ), $order_listener, 'handle_order_action', 10, 3 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'order', 'order_confirmed' ), $order_listener, 'process_confirmed_order', 10, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'order', 'order_cancelled' ), $order_listener, 'process_cancelled_order', 10, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_no_answer' ), $order_listener, 'process_no_answer_call', 10, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_busy' ), $order_listener, 'process_busy_call', 10, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_failed' ), $order_listener, 'process_failed_call', 10, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_unauthorized' ), $this->call_trigger_manager, 'handle_unauthorized_call', 10, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_billing_blocked' ), $this->call_trigger_manager, 'handle_billing_blocked_call', 10, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_invalid_flow' ), $this->call_trigger_manager, 'handle_invalid_flow_call', 10, 3 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_originate_error' ), $this->call_trigger_manager, 'handle_originate_api_error', 10, 3 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'webhook', 'call_event_received' ), $retry_listener, 'handle_call_event' );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_originate_failed' ), $retry_listener, 'handle_originate_failure', 10, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'webhook', 'call_event_received' ), $retry_registrar, 'handle_call_event' );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_originate_failed' ), $retry_registrar, 'handle_originate_failure', 10, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_no_answer' ), $retry_registrar, 'handle_no_answer_call', 20, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_busy' ), $retry_registrar, 'handle_busy_call', 20, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'call', 'call_failed' ), $retry_registrar, 'handle_failed_call', 20, 2 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'webhook', 'call_event_received' ), $call_sync_listener, 'handle_call_event' );

	}

	/**
	 * Register external entrypoint and scheduler hooks.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_entrypoint_hooks() {

		$woocommerce_entrypoints = new Dialyra_WooCommerce_Entrypoints();
		$scheduler_entrypoints   = new Dialyra_Scheduler_Entrypoints( $this->call_queue_processor, $this->retry_queue_processor );

		$this->loader->add_action( 'woocommerce_new_order', $woocommerce_entrypoints, 'handle_new_order', 20, 2 );
		$this->loader->add_action( 'woocommerce_checkout_order_created', $woocommerce_entrypoints, 'handle_checkout_order_created', 20, 1 );
		$this->loader->add_action( 'woocommerce_store_api_checkout_order_processed', $woocommerce_entrypoints, 'handle_store_api_checkout_order_processed', 20, 1 );
		$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $woocommerce_entrypoints, 'handle_checkout_update_order_meta', 20, 2 );
		$this->loader->add_action( 'woocommerce_process_shop_order_meta', $woocommerce_entrypoints, 'handle_admin_order_saved', 20, 2 );
		$this->loader->add_action( 'woocommerce_payment_complete', $woocommerce_entrypoints, 'handle_payment_complete', 20, 1 );
		$this->loader->add_action( 'woocommerce_thankyou', $woocommerce_entrypoints, 'handle_thankyou', 20, 1 );
		$this->loader->add_action( 'woocommerce_update_order', $woocommerce_entrypoints, 'handle_order_updated', 20, 2 );
		$this->loader->add_action( 'woocommerce_order_status_changed', $woocommerce_entrypoints, 'handle_order_status_changed', 20, 4 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'business', 'business_changed' ), $this->business_manager, 'handle_connected_business_changed', 10, 4 );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'order', 'order_created' ), $this->call_trigger_manager, 'handle_new_order' );
		$this->loader->add_action( Dialyra_Hook_Names::get( 'order', 'order_status_changed' ), $this->call_trigger_manager, 'handle_order_status_changed', 10, 3 );
		$this->loader->add_action( 'init', $this, 'debug_entrypoint_hook_registration', 99 );
		$this->loader->add_filter( 'cron_schedules', $scheduler_entrypoints, 'add_minute_schedule' );
		$this->loader->add_action( 'init', $scheduler_entrypoints, 'ensure_recurring_actions' );
		$this->loader->add_action( Dialyra_Scheduler_Entrypoints::get_call_queue_hook(), $scheduler_entrypoints, 'process_call_queue' );
		$this->loader->add_action( Dialyra_Scheduler_Entrypoints::get_retry_queue_hook(), $scheduler_entrypoints, 'process_retry_queue' );

	}

	/**
	 * Log whether Dialyra entrypoint hooks are registered.
	 *
	 * This runs only when WP_DEBUG is enabled and helps verify hook wiring on a
	 * live WooCommerce site without changing normal plugin behavior.
	 *
	 * @since    1.0.0
	 */
	public function debug_entrypoint_hook_registration() {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$hooks = array(
			'woocommerce_new_order',
			'woocommerce_checkout_order_created',
			'woocommerce_store_api_checkout_order_processed',
			'woocommerce_checkout_update_order_meta',
			'woocommerce_process_shop_order_meta',
			'woocommerce_payment_complete',
			'woocommerce_thankyou',
			'woocommerce_update_order',
			'woocommerce_order_status_changed',
			Dialyra_Hook_Names::get_or_default( 'order', 'order_created', 'dialyra_order_created' ),
			Dialyra_Hook_Names::get_or_default( 'order', 'order_status_changed', 'dialyra_order_status_changed' ),
		);

		foreach ( $hooks as $hook_name ) {
			error_log(
				sprintf(
					'WP Dialyra: hook audit [%s] registered=%s',
					sanitize_key( $hook_name ),
					has_action( $hook_name ) ? 'yes' : 'no'
				)
			);
		}
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

	/**
	 * Get the call originate service.
	 *
	 * @since     1.0.0
	 * @return    Dialyra_Call_Originate_Service
	 */
	public function get_call_originate_service() {
		return $this->call_originate_service;
	}

	/**
	 * Get the call trigger manager.
	 *
	 * @since     1.0.0
	 * @return    Dialyra_Call_Trigger_Manager
	 */
	public function get_call_trigger_manager() {
		return $this->call_trigger_manager;
	}

}
