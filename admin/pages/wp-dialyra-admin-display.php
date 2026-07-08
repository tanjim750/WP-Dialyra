<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://triizync.com
 * @since      1.0.0
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages
 */

$wp_dialyra_pages = array(
	'dashboard' => 'dashboard.php',
	'login'     => 'login.php',
	'settings'  => 'settings.php',
);

$wp_dialyra_current_page = isset( $_GET['p'] ) ? sanitize_key( wp_unslash( $_GET['p'] ) ) : 'dashboard';

if ( ! isset( $wp_dialyra_pages[ $wp_dialyra_current_page ] ) ) {
	$wp_dialyra_current_page = 'dashboard';
}

$wp_dialyra_page_path = plugin_dir_path( __FILE__ ) . 'views/' . $wp_dialyra_pages[ $wp_dialyra_current_page ];
?>

<div class="wrap wp-dialyra-admin">
	<header class="wp-dialyra-header">
		<div class="wp-dialyra-header__brand">
			<div class="wp-dialyra-header__mark" aria-hidden="true">
				<span></span>
			</div>
			<div>
				<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'WooCommerce Call Automation', 'wp-dialyra' ); ?></p>
				<h1><?php esc_html_e( 'WP Dialyra', 'wp-dialyra' ); ?></h1>
			</div>
		</div>

		<div class="wp-dialyra-header__actions" aria-label="<?php esc_attr_e( 'Dialyra quick status', 'wp-dialyra' ); ?>">
			<?php if ( 'login' !== $wp_dialyra_current_page ) : ?>
				<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>"><?php esc_html_e( 'Settings', 'wp-dialyra' ); ?></a>
			<?php endif; ?>
			<span class="wp-dialyra-status wp-dialyra-status--pending"><?php esc_html_e( 'Not connected', 'wp-dialyra' ); ?></span>
		</div>
	</header>

	<main class="wp-dialyra-main">
		<?php require $wp_dialyra_page_path; ?>
	</main>

	<footer class="wp-dialyra-footer">
		<div>
			<strong><?php esc_html_e( 'WP Dialyra', 'wp-dialyra' ); ?></strong>
			<span><?php esc_html_e( 'Version 1.0.0', 'wp-dialyra' ); ?></span>
		</div>
		<div class="wp-dialyra-footer__meta">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>"><?php esc_html_e( 'Settings', 'wp-dialyra' ); ?></a>
			<span><?php esc_html_e( 'API health: Not connected', 'wp-dialyra' ); ?></span>
			<span><?php esc_html_e( 'Webhook: Waiting setup', 'wp-dialyra' ); ?></span>
		</div>
	</footer>
</div>
