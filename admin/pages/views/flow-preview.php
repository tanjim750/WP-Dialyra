<?php

/**
 * Flow preview page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$wp_dialyra_preview_menus = array(
	array(
		'name'        => __( 'Main Menu', 'wp-dialyra' ),
		'type'        => __( 'Start menu', 'wp-dialyra' ),
		'description' => __( 'First message customers hear after the call connects.', 'wp-dialyra' ),
		'message'     => __( 'Press 1 to confirm your order, press 2 to cancel, press 3 to talk to support.', 'wp-dialyra' ),
		'status'      => 'success',
		'actions'     => array(
			array(
				'key'      => '1',
				'label'    => __( 'Confirm order', 'wp-dialyra' ),
				'detail'   => __( 'Order is confirmed, then flow ends.', 'wp-dialyra' ),
				'next'     => __( 'End Flow', 'wp-dialyra' ),
				'variant'  => 'success',
			),
			array(
				'key'      => '2',
				'label'    => __( 'Cancel order', 'wp-dialyra' ),
				'detail'   => __( 'Order cancellation branch with confirmation message.', 'wp-dialyra' ),
				'next'     => __( 'Hangup', 'wp-dialyra' ),
				'variant'  => 'danger',
			),
			array(
				'key'      => '3',
				'label'    => __( 'Transfer department', 'wp-dialyra' ),
				'detail'   => __( 'Customer is routed to Billing Department.', 'wp-dialyra' ),
				'next'     => __( 'Billing Department', 'wp-dialyra' ),
				'variant'  => 'blue',
			),
		),
	),
	array(
		'name'        => __( 'Order Info', 'wp-dialyra' ),
		'type'        => __( 'Secondary menu', 'wp-dialyra' ),
		'description' => __( 'Answers common questions about product and delivery.', 'wp-dialyra' ),
		'message'     => __( 'Press 1 for product info, press 2 for delivery charge, press 3 to return to main menu.', 'wp-dialyra' ),
		'status'      => 'warning',
		'actions'     => array(
			array(
				'key'      => '1',
				'label'    => __( 'Product Info', 'wp-dialyra' ),
				'detail'   => __( 'Plays product details using text to speech.', 'wp-dialyra' ),
				'next'     => __( 'Repeat Current Menu', 'wp-dialyra' ),
				'variant'  => 'blue',
			),
			array(
				'key'      => '2',
				'label'    => __( 'Delivery Charge', 'wp-dialyra' ),
				'detail'   => __( 'Plays delivery charge message.', 'wp-dialyra' ),
				'next'     => __( 'Repeat Current Menu', 'wp-dialyra' ),
				'variant'  => 'blue',
			),
			array(
				'key'      => '3',
				'label'    => __( 'Main Menu', 'wp-dialyra' ),
				'detail'   => __( 'Returns customer to the start menu.', 'wp-dialyra' ),
				'next'     => __( 'Go To Menu', 'wp-dialyra' ),
				'variant'  => 'success',
			),
		),
	),
	array(
		'name'        => __( 'Offers', 'wp-dialyra' ),
		'type'        => __( 'Secondary menu', 'wp-dialyra' ),
		'description' => __( 'Promotional message branch.', 'wp-dialyra' ),
		'message'     => __( 'Today you have a special offer. Press 1 to hear again or press 2 to end the call.', 'wp-dialyra' ),
		'status'      => 'success',
		'actions'     => array(
			array(
				'key'      => '1',
				'label'    => __( 'Repeat offer', 'wp-dialyra' ),
				'detail'   => __( 'Repeats current menu for the customer.', 'wp-dialyra' ),
				'next'     => __( 'Repeat Current Menu', 'wp-dialyra' ),
				'variant'  => 'blue',
			),
			array(
				'key'      => '2',
				'label'    => __( 'Hangup', 'wp-dialyra' ),
				'detail'   => __( 'Ends the promotional call branch.', 'wp-dialyra' ),
				'next'     => __( 'Hangup', 'wp-dialyra' ),
				'variant'  => 'danger',
			),
		),
	),
);
?>

<section class="wp-dialyra-flow-preview-page">
	<div class="wp-dialyra-flow-preview-page__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Flow Preview', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Review the customer journey before publishing.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Preview menus, messages, keypad choices, fallback behavior, and transfer outcomes exactly as a store owner should understand them.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-flow-preview-page__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-builder' ) ); ?>"><?php esc_html_e( 'Back to Builder', 'wp-dialyra' ); ?></a>
			<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button"><?php esc_html_e( 'Save Draft', 'wp-dialyra' ); ?></button>
			<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Publish', 'wp-dialyra' ); ?></button>
		</div>
	</div>

	<div class="wp-dialyra-flow-preview-page__grid">
		<aside class="wp-dialyra-preview-map">
			<div class="wp-dialyra-preview-card__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Tree View', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Menu path', 'wp-dialyra' ); ?></h3>
				</div>
				<span class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'Draft ready', 'wp-dialyra' ); ?></span>
			</div>

			<div class="wp-dialyra-preview-tree" aria-label="<?php esc_attr_e( 'Flow menu preview tree', 'wp-dialyra' ); ?>">
				<div class="wp-dialyra-preview-tree__node wp-dialyra-preview-tree__node--start">
					<strong><?php esc_html_e( 'Main Menu', 'wp-dialyra' ); ?></strong>
					<span><?php esc_html_e( 'Start', 'wp-dialyra' ); ?></span>
				</div>
				<div class="wp-dialyra-preview-tree__branch">
					<span><?php esc_html_e( '1', 'wp-dialyra' ); ?></span>
					<div>
						<strong><?php esc_html_e( 'Confirm order', 'wp-dialyra' ); ?></strong>
						<small><?php esc_html_e( 'End Flow', 'wp-dialyra' ); ?></small>
					</div>
				</div>
				<div class="wp-dialyra-preview-tree__branch">
					<span><?php esc_html_e( '2', 'wp-dialyra' ); ?></span>
					<div>
						<strong><?php esc_html_e( 'Cancel order', 'wp-dialyra' ); ?></strong>
						<small><?php esc_html_e( 'Hangup', 'wp-dialyra' ); ?></small>
					</div>
				</div>
				<div class="wp-dialyra-preview-tree__branch">
					<span><?php esc_html_e( '3', 'wp-dialyra' ); ?></span>
					<div>
						<strong><?php esc_html_e( 'Billing Department', 'wp-dialyra' ); ?></strong>
						<small><?php esc_html_e( 'Transfer department', 'wp-dialyra' ); ?></small>
					</div>
				</div>
				<div class="wp-dialyra-preview-tree__branch wp-dialyra-preview-tree__branch--soft">
					<span><?php esc_html_e( 'Timeout', 'wp-dialyra' ); ?></span>
					<div>
						<strong><?php esc_html_e( 'Repeat Current Menu', 'wp-dialyra' ); ?></strong>
						<small><?php esc_html_e( '1 retry allowed', 'wp-dialyra' ); ?></small>
					</div>
				</div>
			</div>
		</aside>

		<div class="wp-dialyra-preview-stack">
			<section class="wp-dialyra-preview-card">
				<div class="wp-dialyra-preview-card__head">
					<div>
						<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Call Script', 'wp-dialyra' ); ?></p>
						<h3><?php esc_html_e( 'What the customer hears', 'wp-dialyra' ); ?></h3>
					</div>
				</div>

				<div class="wp-dialyra-preview-phone">
					<div class="wp-dialyra-preview-phone__screen">
						<span><?php esc_html_e( 'TTS · Bangla · Female warm', 'wp-dialyra' ); ?></span>
						<p><?php esc_html_e( 'Press 1 to confirm your order, press 2 to cancel, press 3 to talk to support.', 'wp-dialyra' ); ?></p>
					</div>
					<div class="wp-dialyra-preview-phone__keys" aria-hidden="true">
						<span>1</span>
						<span>2</span>
						<span>3</span>
					</div>
				</div>
			</section>

			<section class="wp-dialyra-preview-card">
				<div class="wp-dialyra-preview-card__head">
					<div>
						<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Menu Details', 'wp-dialyra' ); ?></p>
						<h3><?php esc_html_e( 'Configured menus', 'wp-dialyra' ); ?></h3>
					</div>
					<span><?php echo esc_html( count( $wp_dialyra_preview_menus ) ); ?> <?php esc_html_e( 'menus', 'wp-dialyra' ); ?></span>
				</div>

				<div class="wp-dialyra-preview-menu-grid">
					<?php foreach ( $wp_dialyra_preview_menus as $wp_dialyra_menu ) : ?>
						<article class="wp-dialyra-preview-menu">
							<header>
								<div>
									<h4><?php echo esc_html( $wp_dialyra_menu['name'] ); ?></h4>
									<p><?php echo esc_html( $wp_dialyra_menu['description'] ); ?></p>
								</div>
								<em class="wp-dialyra-result wp-dialyra-result--<?php echo esc_attr( $wp_dialyra_menu['status'] ); ?>"><?php echo esc_html( $wp_dialyra_menu['type'] ); ?></em>
							</header>

							<div class="wp-dialyra-preview-menu__message">
								<span><?php esc_html_e( 'Message', 'wp-dialyra' ); ?></span>
								<p><?php echo esc_html( $wp_dialyra_menu['message'] ); ?></p>
							</div>

							<div class="wp-dialyra-preview-actions">
								<?php foreach ( $wp_dialyra_menu['actions'] as $wp_dialyra_action ) : ?>
									<div class="wp-dialyra-preview-action wp-dialyra-preview-action--<?php echo esc_attr( $wp_dialyra_action['variant'] ); ?>">
										<span><?php echo esc_html( $wp_dialyra_action['key'] ); ?></span>
										<div>
											<strong><?php echo esc_html( $wp_dialyra_action['label'] ); ?></strong>
											<small><?php echo esc_html( $wp_dialyra_action['detail'] ); ?></small>
										</div>
										<em><?php echo esc_html( $wp_dialyra_action['next'] ); ?></em>
									</div>
								<?php endforeach; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="wp-dialyra-preview-card">
				<div class="wp-dialyra-preview-card__head">
					<div>
						<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Fallbacks', 'wp-dialyra' ); ?></p>
						<h3><?php esc_html_e( 'Retry and transfer outcomes', 'wp-dialyra' ); ?></h3>
					</div>
				</div>

				<div class="wp-dialyra-preview-fallbacks">
					<div>
						<strong><?php esc_html_e( 'Invalid input', 'wp-dialyra' ); ?></strong>
						<span><?php esc_html_e( 'Message → repeat current menu', 'wp-dialyra' ); ?></span>
					</div>
					<div>
						<strong><?php esc_html_e( 'Timeout', 'wp-dialyra' ); ?></strong>
						<span><?php esc_html_e( 'Message → repeat current menu', 'wp-dialyra' ); ?></span>
					</div>
					<div>
						<strong><?php esc_html_e( 'Transfer Timeout', 'wp-dialyra' ); ?></strong>
						<span><?php esc_html_e( 'Message → go to Main Menu', 'wp-dialyra' ); ?></span>
					</div>
					<div>
						<strong><?php esc_html_e( 'Transfer Failed', 'wp-dialyra' ); ?></strong>
						<span><?php esc_html_e( 'Message → hangup', 'wp-dialyra' ); ?></span>
					</div>
				</div>
			</section>
		</div>
	</div>
</section>
