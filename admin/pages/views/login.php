<?php

/**
 * Login page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$wp_dialyra_login_error = null;
$wp_dialyra_login_email = '';

if ( class_exists( 'Dialyra_Auth_Manager' ) && Dialyra_Auth_Manager::is_logged_in() ) {
	$wp_dialyra_redirect_url = Dialyra_Auth_Manager::get_business_id() ? admin_url( 'admin.php?page=wp-dialyra&p=dashboard' ) : admin_url( 'admin.php?page=wp-dialyra&p=setup' );
	wp_safe_redirect( $wp_dialyra_redirect_url );
	exit;
}

if ( 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) && isset( $_POST['wp_dialyra_login_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_dialyra_login_nonce'] ), 'wp-dialyra-login' ) ) {
	$wp_dialyra_login_email = isset( $_POST['dialyra_email'] ) ? sanitize_email( wp_unslash( $_POST['dialyra_email'] ) ) : '';
	$wp_dialyra_password = isset( $_POST['dialyra_password'] ) ? wp_unslash( $_POST['dialyra_password'] ) : '';

	if ( empty( $wp_dialyra_login_email ) || empty( $wp_dialyra_password ) ) {
		$wp_dialyra_login_error = esc_html__( 'Email and password are required.', 'wp-dialyra' );
	} else {
		$wp_dialyra_plugin = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
		$wp_dialyra_endpoints = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_api_endpoints() : null;
		$wp_dialyra_response = $wp_dialyra_endpoints ? $wp_dialyra_endpoints->login( $wp_dialyra_login_email, $wp_dialyra_password ) : false;

		if ( $wp_dialyra_response && $wp_dialyra_response->is_successful() ) {
			$wp_dialyra_login_data = $wp_dialyra_response->get_data();
			$wp_dialyra_login_data = is_array( $wp_dialyra_login_data ) ? $wp_dialyra_login_data : array();

			if ( isset( $wp_dialyra_login_data['data'] ) && is_array( $wp_dialyra_login_data['data'] ) ) {
				$wp_dialyra_login_data = $wp_dialyra_login_data['data'];
			}

			if ( ! empty( $wp_dialyra_login_data['access_token'] ) ) {
				Dialyra_Auth_Manager::save_access_token( $wp_dialyra_login_data['access_token'] );
			}

			if ( ! empty( $wp_dialyra_login_data['refresh_token'] ) ) {
				Dialyra_Auth_Manager::save_refresh_token( $wp_dialyra_login_data['refresh_token'] );
			}

			if ( ! empty( $wp_dialyra_login_data['user'] ) && is_array( $wp_dialyra_login_data['user'] ) ) {
				Dialyra_Auth_Manager::save_user_info( $wp_dialyra_login_data['user'] );
			}

			if ( ! empty( $wp_dialyra_login_data['business'] ) && is_array( $wp_dialyra_login_data['business'] ) ) {
				$wp_dialyra_business_manager = $wp_dialyra_plugin ? $wp_dialyra_plugin->get_business_manager() : null;

				if ( $wp_dialyra_business_manager ) {
					$wp_dialyra_business_manager->save_connected_business_data( $wp_dialyra_login_data['business'], 'login' );
				}
			}

			wp_safe_redirect( Dialyra_Auth_Manager::get_business_id() ? admin_url( 'admin.php?page=wp-dialyra&p=dashboard' ) : admin_url( 'admin.php?page=wp-dialyra&p=setup' ) );
			exit;
		}

		$wp_dialyra_login_error = $wp_dialyra_response ? $wp_dialyra_response->get_message() : esc_html__( 'Login service is not available.', 'wp-dialyra' );
	}
}
?>

<section class="wp-dialyra-login">
	<div class="wp-dialyra-login__story">
		<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Secure Setup', 'wp-dialyra' ); ?></p>
		<h2><?php esc_html_e( 'Connect Dialyra to start calling WooCommerce customers.', 'wp-dialyra' ); ?></h2>
		<p><?php esc_html_e( 'Sign in with your Dialyra account. We will connect this store, prepare the business workspace, and keep your call automation ready for orders.', 'wp-dialyra' ); ?></p>

		<ul class="wp-dialyra-login__checks">
			<li><?php esc_html_e( 'Auto-connect this WordPress site with your Dialyra business.', 'wp-dialyra' ); ?></li>
			<li><?php esc_html_e( 'Generate a secure access token for API requests.', 'wp-dialyra' ); ?></li>
			<li><?php esc_html_e( 'Prepare WooCommerce order calling workflows.', 'wp-dialyra' ); ?></li>
		</ul>
	</div>

	<div class="wp-dialyra-login__card">
		<div class="wp-dialyra-login__card-head">
			<span class="wp-dialyra-login__icon" aria-hidden="true"></span>
			<div>
				<h3><?php esc_html_e( 'Dialyra Login', 'wp-dialyra' ); ?></h3>
				<p><?php esc_html_e( 'Use your Dialyra credentials to continue.', 'wp-dialyra' ); ?></p>
			</div>
		</div>

		<?php if ( ! empty( $wp_dialyra_login_error ) ) : ?>
			<div class="wp-dialyra-notice wp-dialyra-notice--error">
				<p><?php echo esc_html( $wp_dialyra_login_error ); ?></p>
			</div>
		<?php endif; ?>

		<form class="wp-dialyra-login__form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=login' ) ); ?>">
			<?php wp_nonce_field( 'wp-dialyra-login', 'wp_dialyra_login_nonce' ); ?>

			<div class="wp-dialyra-field">
				<label for="wp-dialyra-email"><?php esc_html_e( 'Email address', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-email" name="dialyra_email" type="email" autocomplete="email" placeholder="<?php esc_attr_e( 'you@example.com', 'wp-dialyra' ); ?>" value="<?php echo esc_attr( $wp_dialyra_login_email ); ?>" required>
			</div>

			<div class="wp-dialyra-field">
				<label for="wp-dialyra-password"><?php esc_html_e( 'Password', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-password" name="dialyra_password" type="password" autocomplete="current-password" placeholder="<?php esc_attr_e( 'Enter your password', 'wp-dialyra' ); ?>" required>
			</div>

			<div class="wp-dialyra-login__options">
				<label>
					<input name="dialyra_remember" type="checkbox" value="1">
					<span><?php esc_html_e( 'Remember this connection', 'wp-dialyra' ); ?></span>
				</label>
				<a href="#"><?php esc_html_e( 'Forgot password?', 'wp-dialyra' ); ?></a>
			</div>

			<button class="wp-dialyra-button wp-dialyra-button--primary wp-dialyra-login__submit" type="submit">
				<?php esc_html_e( 'Connect Dialyra', 'wp-dialyra' ); ?>
			</button>
		</form>
	</div>
</section>
