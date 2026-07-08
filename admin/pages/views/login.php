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

		<form class="wp-dialyra-login__form" method="post" action="#">
			<div class="wp-dialyra-field">
				<label for="wp-dialyra-email"><?php esc_html_e( 'Email address', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-email" name="dialyra_email" type="email" autocomplete="email" placeholder="<?php esc_attr_e( 'you@example.com', 'wp-dialyra' ); ?>">
			</div>

			<div class="wp-dialyra-field">
				<label for="wp-dialyra-password"><?php esc_html_e( 'Password', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-password" name="dialyra_password" type="password" autocomplete="current-password" placeholder="<?php esc_attr_e( 'Enter your password', 'wp-dialyra' ); ?>">
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
