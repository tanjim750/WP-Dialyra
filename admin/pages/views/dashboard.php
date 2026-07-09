<?php

/**
 * Dashboard page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<section class="wp-dialyra-dashboard">
	<div class="wp-dialyra-dashboard__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Control Room', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Monitor every WooCommerce call from one calm command center.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Track active calls, queued orders, retries, balance, and customer confirmations as Dialyra automates your order follow-up workflow.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-dashboard__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=login' ) ); ?>"><?php esc_html_e( 'Reconnect', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=test-tools' ) ); ?>"><?php esc_html_e( 'Test Call', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<div class="wp-dialyra-dashboard__stats" aria-label="<?php esc_attr_e( 'Dialyra dashboard summary', 'wp-dialyra' ); ?>">
		<a class="wp-dialyra-stat wp-dialyra-stat--blue" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>">
			<span><?php esc_html_e( 'Today’s Calls', 'wp-dialyra' ); ?></span>
			<strong>42</strong>
			<small><?php esc_html_e( '+12% from yesterday', 'wp-dialyra' ); ?></small>
		</a>

		<a class="wp-dialyra-stat wp-dialyra-stat--mint" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>">
			<span><?php esc_html_e( 'Confirmed Orders', 'wp-dialyra' ); ?></span>
			<strong>28</strong>
			<small><?php esc_html_e( '66.7% confirmation rate', 'wp-dialyra' ); ?></small>
		</a>

		<a class="wp-dialyra-stat wp-dialyra-stat--gold" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=queue-calls' ) ); ?>">
			<span><?php esc_html_e( 'Waiting Queue', 'wp-dialyra' ); ?></span>
			<strong>7</strong>
			<small><?php esc_html_e( 'Next slot in 03:20', 'wp-dialyra' ); ?></small>
		</a>

		<a class="wp-dialyra-stat wp-dialyra-stat--rose" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=queue-calls' ) ); ?>">
			<span><?php esc_html_e( 'Failed / Retry', 'wp-dialyra' ); ?></span>
			<strong>5</strong>
			<small><?php esc_html_e( '3 retries scheduled', 'wp-dialyra' ); ?></small>
		</a>
	</div>

	<div class="wp-dialyra-dashboard__grid">
		<section class="wp-dialyra-panel wp-dialyra-panel--wide">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Automation Flow', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Order calling pipeline', 'wp-dialyra' ); ?></h3>
				</div>
				<a class="wp-dialyra-chip wp-dialyra-chip--active" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flows' ) ); ?>"><?php esc_html_e( 'Running', 'wp-dialyra' ); ?></a>
			</div>

			<div class="wp-dialyra-flow">
				<div class="wp-dialyra-flow__step">
					<span>01</span>
					<strong><?php esc_html_e( 'New Order', 'wp-dialyra' ); ?></strong>
					<small><?php esc_html_e( 'WooCommerce trigger', 'wp-dialyra' ); ?></small>
				</div>
				<div class="wp-dialyra-flow__step">
					<span>02</span>
					<strong><?php esc_html_e( 'Rule Check', 'wp-dialyra' ); ?></strong>
					<small><?php esc_html_e( 'Status + hours', 'wp-dialyra' ); ?></small>
				</div>
				<div class="wp-dialyra-flow__step">
					<span>03</span>
					<strong><?php esc_html_e( 'Dialyra Call', 'wp-dialyra' ); ?></strong>
					<small><?php esc_html_e( 'Selected flow', 'wp-dialyra' ); ?></small>
				</div>
				<div class="wp-dialyra-flow__step">
					<span>04</span>
					<strong><?php esc_html_e( 'Webhook Result', 'wp-dialyra' ); ?></strong>
					<small><?php esc_html_e( 'Update order', 'wp-dialyra' ); ?></small>
				</div>
			</div>
		</section>

		<section class="wp-dialyra-panel wp-dialyra-balance">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Balance', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Call credit', 'wp-dialyra' ); ?></h3>
				</div>
				<a class="wp-dialyra-chip" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=settings' ) ); ?>"><?php esc_html_e( 'Settings', 'wp-dialyra' ); ?></a>
			</div>
			<strong>৳ 1,240.50</strong>
			<div class="wp-dialyra-meter" aria-hidden="true"><span style="width: 68%;"></span></div>
			<p><?php esc_html_e( 'Healthy balance. Low balance warning starts below ৳ 250.', 'wp-dialyra' ); ?></p>
		</section>

		<section class="wp-dialyra-panel">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Live Queue', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Calls waiting', 'wp-dialyra' ); ?></h3>
				</div>
				<a class="wp-dialyra-chip" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=queue-calls' ) ); ?>"><?php esc_html_e( 'FIFO', 'wp-dialyra' ); ?></a>
			</div>

			<ul class="wp-dialyra-queue">
				<li>
					<strong>#1048</strong>
					<span><?php esc_html_e( 'Processing order', 'wp-dialyra' ); ?></span>
					<em><?php esc_html_e( 'Queued', 'wp-dialyra' ); ?></em>
				</li>
				<li>
					<strong>#1047</strong>
					<span><?php esc_html_e( 'Retry after busy', 'wp-dialyra' ); ?></span>
					<em><?php esc_html_e( '02:15', 'wp-dialyra' ); ?></em>
				</li>
				<li>
					<strong>#1045</strong>
					<span><?php esc_html_e( 'No answer retry', 'wp-dialyra' ); ?></span>
					<em><?php esc_html_e( '06:40', 'wp-dialyra' ); ?></em>
				</li>
			</ul>
		</section>

		<section class="wp-dialyra-panel">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Performance', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Call outcomes', 'wp-dialyra' ); ?></h3>
				</div>
				<a class="wp-dialyra-chip" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>"><?php esc_html_e( 'View all', 'wp-dialyra' ); ?></a>
			</div>

			<div class="wp-dialyra-rings">
				<div>
					<strong>84%</strong>
					<span><?php esc_html_e( 'Answer rate', 'wp-dialyra' ); ?></span>
				</div>
				<div>
					<strong>67%</strong>
					<span><?php esc_html_e( 'Confirmed', 'wp-dialyra' ); ?></span>
				</div>
			</div>

			<div class="wp-dialyra-bars">
				<span style="height: 62%;"></span>
				<span style="height: 84%;"></span>
				<span style="height: 54%;"></span>
				<span style="height: 72%;"></span>
				<span style="height: 46%;"></span>
				<span style="height: 78%;"></span>
				<span style="height: 68%;"></span>
			</div>
		</section>

		<section class="wp-dialyra-panel wp-dialyra-panel--tall">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Departments', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Routing groups', 'wp-dialyra' ); ?></h3>
				</div>
				<a class="wp-dialyra-chip" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=departments' ) ); ?>"><?php esc_html_e( 'View all', 'wp-dialyra' ); ?></a>
			</div>

			<div class="wp-dialyra-department-card">
				<div class="wp-dialyra-department-card__top">
					<div>
						<h4><?php esc_html_e( 'Billing Department', 'wp-dialyra' ); ?></h4>
						<p><?php esc_html_e( 'Billing and payment support', 'wp-dialyra' ); ?></p>
					</div>
					<em class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'Active', 'wp-dialyra' ); ?></em>
				</div>

				<dl class="wp-dialyra-detail-list">
					<div>
						<dt><?php esc_html_e( 'Strategy', 'wp-dialyra' ); ?></dt>
						<dd><code>least_busy</code></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Default language', 'wp-dialyra' ); ?></dt>
						<dd><span class="wp-dialyra-tag">bn</span></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Created', 'wp-dialyra' ); ?></dt>
						<dd><?php esc_html_e( '2026-06-04 10:00', 'wp-dialyra' ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Updated', 'wp-dialyra' ); ?></dt>
						<dd><?php esc_html_e( '2026-06-04 10:00', 'wp-dialyra' ); ?></dd>
					</div>
				</dl>
			</div>
		</section>

		<section class="wp-dialyra-panel wp-dialyra-panel--wide">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Recent Activity', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Latest call history', 'wp-dialyra' ); ?></h3>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=call-history' ) ); ?>"><?php esc_html_e( 'View all', 'wp-dialyra' ); ?></a>
			</div>

			<div class="wp-dialyra-table" role="table" aria-label="<?php esc_attr_e( 'Latest Dialyra calls', 'wp-dialyra' ); ?>">
				<div role="row">
					<span role="columnheader"><?php esc_html_e( 'Order', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Customer', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Flow', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Result', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Cost', 'wp-dialyra' ); ?></span>
				</div>
				<div role="row">
					<span role="cell">#1048</span>
					<span role="cell"><?php esc_html_e( 'Rahim Ahmed', 'wp-dialyra' ); ?></span>
					<span role="cell"><?php esc_html_e( 'Order Confirm', 'wp-dialyra' ); ?></span>
					<span role="cell"><em class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'Confirmed', 'wp-dialyra' ); ?></em></span>
					<span role="cell">৳ 3.20</span>
				</div>
				<div role="row">
					<span role="cell">#1047</span>
					<span role="cell"><?php esc_html_e( 'Mim Chowdhury', 'wp-dialyra' ); ?></span>
					<span role="cell"><?php esc_html_e( 'COD Verify', 'wp-dialyra' ); ?></span>
					<span role="cell"><em class="wp-dialyra-result wp-dialyra-result--warning"><?php esc_html_e( 'Busy', 'wp-dialyra' ); ?></em></span>
					<span role="cell">৳ 1.70</span>
				</div>
				<div role="row">
					<span role="cell">#1045</span>
					<span role="cell"><?php esc_html_e( 'Nusrat Jahan', 'wp-dialyra' ); ?></span>
					<span role="cell"><?php esc_html_e( 'Order Confirm', 'wp-dialyra' ); ?></span>
					<span role="cell"><em class="wp-dialyra-result wp-dialyra-result--danger"><?php esc_html_e( 'No Answer', 'wp-dialyra' ); ?></em></span>
					<span role="cell">৳ 2.10</span>
				</div>
			</div>
		</section>

		<section class="wp-dialyra-panel wp-dialyra-panel--wide">
			<div class="wp-dialyra-panel__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Agents', 'wp-dialyra' ); ?></p>
					<h3><?php esc_html_e( 'Support agent directory', 'wp-dialyra' ); ?></h3>
				</div>
				<a class="wp-dialyra-chip" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=agents' ) ); ?>"><?php esc_html_e( 'View all', 'wp-dialyra' ); ?></a>
			</div>

			<div class="wp-dialyra-data-table wp-dialyra-data-table--agents" role="table" aria-label="<?php esc_attr_e( 'Dialyra agents', 'wp-dialyra' ); ?>">
				<div role="row">
					<span role="columnheader"><?php esc_html_e( 'Agent', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Contact', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'SIP', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Calls', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Skills', 'wp-dialyra' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'Metadata', 'wp-dialyra' ); ?></span>
				</div>
				<div role="row">
					<span role="cell">
						<strong><?php esc_html_e( 'Support Agent 1', 'wp-dialyra' ); ?></strong>
						<small>agent1@example.com</small>
					</span>
					<span role="cell">+8801XXXXXXXXX</span>
					<span role="cell"><code>1001</code></span>
					<span role="cell">
						<em class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'Active', 'wp-dialyra' ); ?></em>
						<em class="wp-dialyra-result wp-dialyra-result--muted"><?php esc_html_e( 'Offline', 'wp-dialyra' ); ?></em>
					</span>
					<span role="cell">0 / 1</span>
					<span role="cell">
						<span class="wp-dialyra-tag">bn</span>
						<span class="wp-dialyra-tag">en</span>
					</span>
					<span role="cell"><code>team: support</code></span>
				</div>
			</div>
		</section>
	</div>
</section>
