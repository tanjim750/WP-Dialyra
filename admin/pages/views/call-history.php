<?php

/**
 * Call history page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<section class="wp-dialyra-call-history">
	<div class="wp-dialyra-call-history__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Call History', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Review completed and failed calls with WooCommerce context.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Filter sessions by order, status, date, or phone number, then inspect duration, result, billing, DTMF, retries, and order linkage.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-call-history__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="#wp-dialyra-call-log"><?php esc_html_e( 'View Calls', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<section class="wp-dialyra-call-history-panel">
		<div class="wp-dialyra-call-history-panel__head">
			<span aria-hidden="true">01</span>
			<div>
				<h3><?php esc_html_e( 'Filters', 'wp-dialyra' ); ?></h3>
				<p><?php esc_html_e( 'Narrow the call list by WooCommerce order, call state, date range, or customer phone.', 'wp-dialyra' ); ?></p>
			</div>
		</div>

		<form class="wp-dialyra-call-filters" method="get" action="#">
			<input type="hidden" name="page" value="wp-dialyra">
			<input type="hidden" name="p" value="call-history">

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-filter-order"><?php esc_html_e( 'Order ID', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-filter-order" name="order_id" type="search" value="1048">
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-filter-status"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></label>
				<select id="wp-dialyra-filter-status" name="status">
					<option><?php esc_html_e( 'all', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'completed', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'failed', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'busy', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'no_answer', 'wp-dialyra' ); ?></option>
					<option><?php esc_html_e( 'canceled', 'wp-dialyra' ); ?></option>
				</select>
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-filter-date"><?php esc_html_e( 'Started date', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-filter-date" name="started_date" type="date" value="2026-06-04">
			</div>

			<div class="wp-dialyra-settings-row">
				<label for="wp-dialyra-filter-phone"><?php esc_html_e( 'Phone number', 'wp-dialyra' ); ?></label>
				<input id="wp-dialyra-filter-phone" name="phone" type="search" value="01631596697">
			</div>

			<div class="wp-dialyra-call-filters__actions">
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Apply filters', 'wp-dialyra' ); ?></button>
				<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button"><?php esc_html_e( 'Reset', 'wp-dialyra' ); ?></button>
			</div>
		</form>
	</section>

	<section id="wp-dialyra-call-log" class="wp-dialyra-call-history-panel wp-dialyra-call-history-panel--table">
		<div class="wp-dialyra-call-history-panel__head wp-dialyra-call-history-panel__head--split">
			<div class="wp-dialyra-call-history-panel__title">
				<span aria-hidden="true">02</span>
				<div>
					<h3><?php esc_html_e( 'Call log', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Completed, failed, and no-answer sessions with order links and billing result.', 'wp-dialyra' ); ?></p>
				</div>
			</div>
			<em class="wp-dialyra-result wp-dialyra-result--muted"><?php esc_html_e( 'View only', 'wp-dialyra' ); ?></em>
		</div>

		<div class="wp-dialyra-call-table" role="table" aria-label="<?php esc_attr_e( 'Call history table', 'wp-dialyra' ); ?>">
			<div role="row">
				<span><?php esc_html_e( 'Order ID', 'wp-dialyra' ); ?></span>
				<span><?php esc_html_e( 'Customer name', 'wp-dialyra' ); ?></span>
				<span><?php esc_html_e( 'Number', 'wp-dialyra' ); ?></span>
				<span><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></span>
				<span><?php esc_html_e( 'Duration', 'wp-dialyra' ); ?></span>
				<span><?php esc_html_e( 'Cost', 'wp-dialyra' ); ?></span>
				<span><?php esc_html_e( 'DTMF', 'wp-dialyra' ); ?></span>
				<span><?php esc_html_e( 'From number', 'wp-dialyra' ); ?></span>
				<span><?php esc_html_e( 'Retries', 'wp-dialyra' ); ?></span>
				<span><?php esc_html_e( 'Started time', 'wp-dialyra' ); ?></span>
			</div>

			<div role="row">
				<span><a href="<?php echo esc_url( admin_url( 'post.php?post=1048&action=edit' ) ); ?>">#1048</a><small><?php esc_html_e( 'Linked order', 'wp-dialyra' ); ?></small></span>
				<span><strong><?php esc_html_e( 'Rahim Ahmed', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'Order confirm flow', 'wp-dialyra' ); ?></small></span>
				<span><strong>01631596697</strong><small><?php esc_html_e( 'dialed outbound', 'wp-dialyra' ); ?></small></span>
				<span><em class="wp-dialyra-result wp-dialyra-result--success"><?php esc_html_e( 'completed', 'wp-dialyra' ); ?></em><small><?php esc_html_e( 'answer', 'wp-dialyra' ); ?></small></span>
				<span><strong>03:38</strong><small><?php esc_html_e( 'billsec 03:32', 'wp-dialyra' ); ?></small></span>
				<span><strong>৳ 7.10</strong><small><?php esc_html_e( 'charged', 'wp-dialyra' ); ?></small></span>
				<span><code>1</code><small><?php esc_html_e( 'node 301', 'wp-dialyra' ); ?></small></span>
				<span><code>1000</code><small><?php esc_html_e( 'trunk 5', 'wp-dialyra' ); ?></small></span>
				<span><strong>1</strong><small><?php esc_html_e( 'attempt', 'wp-dialyra' ); ?></small></span>
				<span><strong><?php esc_html_e( '2026-06-04 10:00', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'ANSWERED', 'wp-dialyra' ); ?></small></span>
			</div>

			<div role="row">
				<span><a href="<?php echo esc_url( admin_url( 'post.php?post=1047&action=edit' ) ); ?>">#1047</a><small><?php esc_html_e( 'Linked order', 'wp-dialyra' ); ?></small></span>
				<span><strong><?php esc_html_e( 'Mim Chowdhury', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'COD verify flow', 'wp-dialyra' ); ?></small></span>
				<span><strong>017XXXXXXXX</strong><small><?php esc_html_e( 'dialed outbound', 'wp-dialyra' ); ?></small></span>
				<span><em class="wp-dialyra-result wp-dialyra-result--warning"><?php esc_html_e( 'busy', 'wp-dialyra' ); ?></em><small><?php esc_html_e( 'no_answer', 'wp-dialyra' ); ?></small></span>
				<span><strong>00:00</strong><small><?php esc_html_e( 'billsec 00:00', 'wp-dialyra' ); ?></small></span>
				<span><strong>৳ 0.00</strong><small><?php esc_html_e( 'released', 'wp-dialyra' ); ?></small></span>
				<span><code>—</code><small><?php esc_html_e( 'none', 'wp-dialyra' ); ?></small></span>
				<span><code>1000</code><small><?php esc_html_e( 'trunk 5', 'wp-dialyra' ); ?></small></span>
				<span><strong>2</strong><small><?php esc_html_e( 'retry after busy', 'wp-dialyra' ); ?></small></span>
				<span><strong><?php esc_html_e( '2026-06-04 10:08', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'BUSY', 'wp-dialyra' ); ?></small></span>
			</div>

			<div role="row">
				<span><a href="<?php echo esc_url( admin_url( 'post.php?post=1045&action=edit' ) ); ?>">#1045</a><small><?php esc_html_e( 'Linked order', 'wp-dialyra' ); ?></small></span>
				<span><strong><?php esc_html_e( 'Nusrat Jahan', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'Order confirm flow', 'wp-dialyra' ); ?></small></span>
				<span><strong>018XXXXXXXX</strong><small><?php esc_html_e( 'dialed outbound', 'wp-dialyra' ); ?></small></span>
				<span><em class="wp-dialyra-result wp-dialyra-result--danger"><?php esc_html_e( 'failed', 'wp-dialyra' ); ?></em><small><?php esc_html_e( 'no_answer', 'wp-dialyra' ); ?></small></span>
				<span><strong>00:00</strong><small><?php esc_html_e( 'billsec 00:00', 'wp-dialyra' ); ?></small></span>
				<span><strong>৳ 0.00</strong><small><?php esc_html_e( 'blocked', 'wp-dialyra' ); ?></small></span>
				<span><code>—</code><small><?php esc_html_e( 'none', 'wp-dialyra' ); ?></small></span>
				<span><code>1000</code><small><?php esc_html_e( 'trunk 5', 'wp-dialyra' ); ?></small></span>
				<span><strong>3</strong><small><?php esc_html_e( 'max retry', 'wp-dialyra' ); ?></small></span>
				<span><strong><?php esc_html_e( '2026-06-04 10:15', 'wp-dialyra' ); ?></strong><small><?php esc_html_e( 'NO ANSWER', 'wp-dialyra' ); ?></small></span>
			</div>
		</div>
	</section>
</section>
