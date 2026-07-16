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
	 * Stream an authenticated Dialyra audio asset through WordPress admin.
	 *
	 * @since    1.0.0
	 */
	public function stream_audio_asset() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to stream this audio asset.', 'wp-dialyra' ), '', array( 'response' => 403 ) );
		}

		$audio_asset_id = isset( $_GET['audio_asset_id'] ) ? absint( wp_unslash( $_GET['audio_asset_id'] ) ) : 0;

		if ( ! $audio_asset_id || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wp-dialyra-stream-audio-' . $audio_asset_id ) ) {
			wp_die( esc_html__( 'Invalid audio stream request.', 'wp-dialyra' ), '', array( 'response' => 400 ) );
		}

		$plugin = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
		$api_endpoints = $plugin ? $plugin->get_api_endpoints() : null;

		if ( ! $api_endpoints ) {
			wp_die( esc_html__( 'Audio service is not available.', 'wp-dialyra' ), '', array( 'response' => 503 ) );
		}

		$response = $api_endpoints->stream_audio_asset( $audio_asset_id );

		if ( is_wp_error( $response ) ) {
			wp_die( esc_html( $response->get_error_message() ), '', array( 'response' => 502 ) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			wp_die( esc_html__( 'Unable to stream audio from Dialyra.', 'wp-dialyra' ), '', array( 'response' => $status_code ? $status_code : 502 ) );
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$content_length = wp_remote_retrieve_header( $response, 'content-length' );
		$body = wp_remote_retrieve_body( $response );

		if ( ! headers_sent() ) {
			header( 'Content-Type: ' . ( $content_type ? $content_type : 'audio/wav' ) );
			header( 'Content-Disposition: inline; filename="dialyra-audio-' . $audio_asset_id . '.wav"' );

			if ( $content_length ) {
				header( 'Content-Length: ' . absint( $content_length ) );
			}
		}

		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;

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
