<?php

/**
 * Call queue page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<section class="wp-dialyra-call-queue">
	<div class="wp-dialyra-call-queue__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Call Queue', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Control waiting calls, retries, and idle-slot processing.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'See what is waiting, cancel queued calls safely, retry failed attempts manually, and process the next call when capacity opens.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-call-queue__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="#wp-dialyra-waiting-calls"><?php esc_html_e( 'View Queue', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<div class="wp-dialyra-queue-strip">
		<div>
			<span><?php esc_html_e( 'Waiting', 'wp-dialyra' ); ?></span>
			<strong>03</strong>
		</div>
		<div>
			<span><?php esc_html_e( 'Retry ready', 'wp-dialyra' ); ?></span>
			<strong>02</strong>
		</div>
		<div>
			<span><?php esc_html_e( 'Idle slots', 'wp-dialyra' ); ?></span>
			<strong>01</strong>
		</div>
		<div>
			<span><?php esc_html_e( 'Paused', 'wp-dialyra' ); ?></span>
			<strong>00</strong>
		</div>
	</div>

	<div class="wp-dialyra-call-queue__grid">
		<section id="wp-dialyra-waiting-calls" class="wp-dialyra-queue-panel wp-dialyra-queue-panel--wide">
			<div class="wp-dialyra-queue-panel__head wp-dialyra-queue-panel__head--split">
				<div class="wp-dialyra-queue-panel__title">
					<span aria-hidden="true">01</span>
					<div>
						<h3><?php esc_html_e( 'Waiting calls', 'wp-dialyra' ); ?></h3>
						<p><?php esc_html_e( 'Queued calls ready for the next available automation slot.', 'wp-dialyra' ); ?></p>
					</div>
				</div>
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Process idle slot', 'wp-dialyra' ); ?></button>
			</div>

			<div class="wp-dialyra-queue-table" role="table" aria-label="<?php esc_attr_e( 'Waiting calls table', 'wp-dialyra' ); ?>">
				<div role="row">
					<span><?php esc_html_e( 'Order', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Customer', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Phone', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Flow', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Queued age', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Priority', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></span>
					<span><?php esc_html_e( 'Actions', 'wp-dialyra' ); ?></span>
				</div>

				<div role="row">
					<span><a href="<?php echo esc_url( admin_url( 'post.php?post=1048&action=edit' ) ); ?>">#1048</a><small><?php esc_html_e( 'WooCommerce order', 'wp-dialyra' ); ?></small></span>
					<span><strong><?php esc_html_e( 'Rahim Ahmed', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'COD confirmation', 'wp-dialyra' ); ?></small></span>
					<span><strong>01631596697</strong><small><?php esc_html_e( 'outbound', 'wp-dialyra' ); ?></small></span>
					<span><code>Order Confirm</code><small><?php esc_html_e( 'default flow', 'wp-dialyra' ); ?></small></span>
					<span><strong>02:15</strong><small><?php esc_html_e( 'waiting', 'wp-dialyra' ); ?></small></span>
					<span><em class="wp-dialyra-result wp-dialyra-result--warning"><?php esc_html_e( 'high', 'wp-dialyra' ); ?></em></span>
					<span><em class="wp-dialyra-result wp-dialyra-result--muted"><?php esc_html_e( 'queued', 'wp-dialyra' ); ?></em></span>
					<span class="wp-dialyra-queue-table__actions">
						<button class="wp-dialyra-queue-action wp-dialyra-queue-action--process" type="button" aria-label="<?php esc_attr_e( 'Process order 1048', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Process now', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-controls-play" aria-hidden="true"></span></button>
						<button class="wp-dialyra-queue-action wp-dialyra-queue-action--cancel" type="button" aria-label="<?php esc_attr_e( 'Cancel order 1048 call', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Cancel queued call', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span></button>
					</span>
				</div>

				<div role="row">
					<span><a href="<?php echo esc_url( admin_url( 'post.php?post=1049&action=edit' ) ); ?>">#1049</a><small><?php esc_html_e( 'WooCommerce order', 'wp-dialyra' ); ?></small></span>
					<span><strong><?php esc_html_e( 'Mim Chowdhury', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'Payment follow-up', 'wp-dialyra' ); ?></small></span>
					<span><strong>017XXXXXXXX</strong><small><?php esc_html_e( 'outbound', 'wp-dialyra' ); ?></small></span>
					<span><code>COD Verify</code><small><?php esc_html_e( 'billing flow', 'wp-dialyra' ); ?></small></span>
					<span><strong>06:40</strong><small><?php esc_html_e( 'waiting', 'wp-dialyra' ); ?></small></span>
					<span><em class="wp-dialyra-result wp-dialyra-result--muted"><?php esc_html_e( 'normal', 'wp-dialyra' ); ?></em></span>
					<span><em class="wp-dialyra-result wp-dialyra-result--muted"><?php esc_html_e( 'queued', 'wp-dialyra' ); ?></em></span>
					<span class="wp-dialyra-queue-table__actions">
						<button class="wp-dialyra-queue-action wp-dialyra-queue-action--process" type="button" aria-label="<?php esc_attr_e( 'Process order 1049', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Process now', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-controls-play" aria-hidden="true"></span></button>
						<button class="wp-dialyra-queue-action wp-dialyra-queue-action--cancel" type="button" aria-label="<?php esc_attr_e( 'Cancel order 1049 call', 'wp-dialyra' ); ?>" title="<?php esc_attr_e( 'Cancel queued call', 'wp-dialyra' ); ?>"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span></button>
					</span>
				</div>
			</div>
		</section>

		<section class="wp-dialyra-queue-panel">
			<div class="wp-dialyra-queue-panel__head">
				<span aria-hidden="true">02</span>
				<div>
					<h3><?php esc_html_e( 'Retry queue', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Failed or busy calls waiting for another attempt.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-retry-list">
				<article>
					<div>
						<h4><?php esc_html_e( 'Order #1047', 'wp-dialyra' ); ?></h4>
						<p><?php esc_html_e( 'Busy result · retry window open', 'wp-dialyra' ); ?></p>
					</div>
					<em class="wp-dialyra-result wp-dialyra-result--warning"><?php esc_html_e( 'attempt 2', 'wp-dialyra' ); ?></em>
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Retry now', 'wp-dialyra' ); ?></button>
				</article>

				<article>
					<div>
						<h4><?php esc_html_e( 'Order #1045', 'wp-dialyra' ); ?></h4>
						<p><?php esc_html_e( 'No answer · manual retry allowed', 'wp-dialyra' ); ?></p>
					</div>
					<em class="wp-dialyra-result wp-dialyra-result--danger"><?php esc_html_e( 'attempt 3', 'wp-dialyra' ); ?></em>
					<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Retry now', 'wp-dialyra' ); ?></button>
				</article>
			</div>
		</section>

		<section class="wp-dialyra-queue-panel">
			<div class="wp-dialyra-queue-panel__head">
				<span aria-hidden="true">03</span>
				<div>
					<h3><?php esc_html_e( 'Slot processor', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Run the next eligible queued call only when concurrency capacity is idle.', 'wp-dialyra' ); ?></p>
				</div>
			</div>

			<div class="wp-dialyra-slot-meter">
				<div>
					<span><?php esc_html_e( 'Active calls', 'wp-dialyra' ); ?></span>
					<strong>0 / 1</strong>
				</div>
				<div>
					<span><?php esc_html_e( 'Next call', 'wp-dialyra' ); ?></span>
					<strong>#1048</strong>
				</div>
				<div>
					<span><?php esc_html_e( 'Readiness', 'wp-dialyra' ); ?></span>
					<strong><?php esc_html_e( 'Idle', 'wp-dialyra' ); ?></strong>
				</div>
			</div>

			<div class="wp-dialyra-queue-panel__footer wp-dialyra-queue-panel__footer--split">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Process next call', 'wp-dialyra' ); ?></button>
				<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button"><?php esc_html_e( 'Refresh queue', 'wp-dialyra' ); ?></button>
			</div>
		</section>
	</div>
</section>
