<?php

/**
 * Flows list page view.
 *
 * @package    Wp_Dialyra
 * @subpackage Wp_Dialyra/admin/pages/views
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$wp_dialyra_flows = array(
	array(
		'name'       => __( 'Order Confirmation Flow', 'wp-dialyra' ),
		'summary'    => __( 'Confirms COD orders, handles cancellation, and transfers customers to billing.', 'wp-dialyra' ),
		'status'     => 'published',
		'is_default' => true,
		'product'    => __( 'All products', 'wp-dialyra' ),
		'created'    => __( 'Jun 04, 2026', 'wp-dialyra' ),
	),
	array(
		'name'       => __( 'Delivery Follow-up', 'wp-dialyra' ),
		'summary'    => __( 'Checks delivery preference, captures retry-later responses, and routes exceptions.', 'wp-dialyra' ),
		'status'     => 'draft',
		'is_default' => false,
		'product'    => __( '3 products', 'wp-dialyra' ),
		'created'    => __( 'Jun 18, 2026', 'wp-dialyra' ),
	),
	array(
		'name'       => __( 'Winback Offers', 'wp-dialyra' ),
		'summary'    => __( 'Shares promotional offers and sends interested customers to the support team.', 'wp-dialyra' ),
		'status'     => 'paused',
		'is_default' => false,
		'product'    => __( 'Summer Sale category', 'wp-dialyra' ),
		'created'    => __( 'Jul 05, 2026', 'wp-dialyra' ),
	),
);
?>

<section class="wp-dialyra-flows">
	<div class="wp-dialyra-flows__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Flows', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Manage every customer call flow from one clean library.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Create, review, preview, and monitor menu-based IVR flows before they reach WooCommerce customers.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-flows__actions">
			<a class="wp-dialyra-button wp-dialyra-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-preview' ) ); ?>"><?php esc_html_e( 'Preview Latest', 'wp-dialyra' ); ?></a>
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-builder' ) ); ?>"><?php esc_html_e( '+ Create Flow', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<div class="wp-dialyra-flow-summary" aria-label="<?php esc_attr_e( 'Flow summary', 'wp-dialyra' ); ?>">
		<div>
			<span><?php esc_html_e( 'Published', 'wp-dialyra' ); ?></span>
			<strong>1</strong>
			<small><?php esc_html_e( 'Currently callable', 'wp-dialyra' ); ?></small>
		</div>
		<div>
			<span><?php esc_html_e( 'Drafts', 'wp-dialyra' ); ?></span>
			<strong>1</strong>
			<small><?php esc_html_e( 'Needs review', 'wp-dialyra' ); ?></small>
		</div>
		<div>
			<span><?php esc_html_e( 'Default Flow', 'wp-dialyra' ); ?></span>
			<strong><?php esc_html_e( 'Order', 'wp-dialyra' ); ?></strong>
			<small><?php esc_html_e( 'Used when no product rule matches', 'wp-dialyra' ); ?></small>
		</div>
		<div>
			<span><?php esc_html_e( 'Product Rules', 'wp-dialyra' ); ?></span>
			<strong>2</strong>
			<small><?php esc_html_e( 'Flows assigned to products', 'wp-dialyra' ); ?></small>
		</div>
	</div>

	<section class="wp-dialyra-flows-panel">
		<div class="wp-dialyra-flows-panel__head">
			<div>
				<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Flow Library', 'wp-dialyra' ); ?></p>
				<h3><?php esc_html_e( 'Saved flows', 'wp-dialyra' ); ?></h3>
			</div>

			<div class="wp-dialyra-flow-filters">
				<label>
					<span><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></span>
					<select name="flow_status">
						<option><?php esc_html_e( 'All statuses', 'wp-dialyra' ); ?></option>
						<option><?php esc_html_e( 'Published', 'wp-dialyra' ); ?></option>
						<option><?php esc_html_e( 'Draft', 'wp-dialyra' ); ?></option>
						<option><?php esc_html_e( 'Paused', 'wp-dialyra' ); ?></option>
					</select>
				</label>
				<label>
					<span><?php esc_html_e( 'Search', 'wp-dialyra' ); ?></span>
					<input name="flow_search" type="search" placeholder="<?php esc_attr_e( 'Search flow name...', 'wp-dialyra' ); ?>">
				</label>
			</div>
		</div>

		<div class="wp-dialyra-flow-table" role="table" aria-label="<?php esc_attr_e( 'Saved flow library', 'wp-dialyra' ); ?>">
			<div class="wp-dialyra-flow-table__row wp-dialyra-flow-table__row--head" role="row">
				<span role="columnheader"><?php esc_html_e( 'Name', 'wp-dialyra' ); ?></span>
				<span role="columnheader"><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></span>
				<span role="columnheader"><?php esc_html_e( 'Is Default', 'wp-dialyra' ); ?></span>
				<span role="columnheader"><?php esc_html_e( 'Product', 'wp-dialyra' ); ?></span>
				<span role="columnheader"><?php esc_html_e( 'Created Date', 'wp-dialyra' ); ?></span>
				<span role="columnheader"><?php esc_html_e( 'Actions', 'wp-dialyra' ); ?></span>
			</div>

			<?php foreach ( $wp_dialyra_flows as $wp_dialyra_flow ) : ?>
				<article class="wp-dialyra-flow-table__row wp-dialyra-flow-table__row--<?php echo esc_attr( $wp_dialyra_flow['status'] ); ?>" role="row">
					<div class="wp-dialyra-flow-table__name" role="cell" data-label="<?php esc_attr_e( 'Name', 'wp-dialyra' ); ?>">
						<div>
							<h4><?php echo esc_html( $wp_dialyra_flow['name'] ); ?></h4>
							<p><?php echo esc_html( $wp_dialyra_flow['summary'] ); ?></p>
						</div>
					</div>

					<div role="cell" data-label="<?php esc_attr_e( 'Status', 'wp-dialyra' ); ?>">
						<span class="wp-dialyra-flow-list__status"><?php echo esc_html( ucfirst( $wp_dialyra_flow['status'] ) ); ?></span>
					</div>

					<div role="cell" data-label="<?php esc_attr_e( 'Is Default', 'wp-dialyra' ); ?>">
						<?php if ( $wp_dialyra_flow['is_default'] ) : ?>
							<span class="wp-dialyra-default-pill wp-dialyra-default-pill--active"><?php esc_html_e( 'Default', 'wp-dialyra' ); ?></span>
						<?php else : ?>
							<span class="wp-dialyra-default-pill"><?php esc_html_e( 'No', 'wp-dialyra' ); ?></span>
						<?php endif; ?>
					</div>

					<div role="cell" data-label="<?php esc_attr_e( 'Product', 'wp-dialyra' ); ?>">
						<span class="wp-dialyra-product-pill"><?php echo esc_html( $wp_dialyra_flow['product'] ); ?></span>
					</div>

					<div role="cell" data-label="<?php esc_attr_e( 'Created Date', 'wp-dialyra' ); ?>">
						<strong class="wp-dialyra-flow-date"><?php echo esc_html( $wp_dialyra_flow['created'] ); ?></strong>
					</div>

					<div class="wp-dialyra-flow-list__actions" role="cell" data-label="<?php esc_attr_e( 'Actions', 'wp-dialyra' ); ?>">
						<button class="wp-dialyra-icon-action wp-dialyra-icon-action--danger" type="button" aria-label="<?php esc_attr_e( 'Delete flow', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Delete', 'wp-dialyra' ); ?>">
							<span class="dashicons dashicons-trash" aria-hidden="true"></span>
						</button>
						<a class="wp-dialyra-icon-action" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-preview' ) ); ?>" aria-label="<?php esc_attr_e( 'Preview flow', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Preview', 'wp-dialyra' ); ?>">
							<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
						</a>
						<a class="wp-dialyra-icon-action" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-builder' ) ); ?>" aria-label="<?php esc_attr_e( 'Edit flow', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Edit', 'wp-dialyra' ); ?>">
							<span class="dashicons dashicons-edit" aria-hidden="true"></span>
						</a>
						<button class="wp-dialyra-icon-action wp-dialyra-icon-action--success" type="button" aria-label="<?php esc_attr_e( 'Select as default flow', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Set default', 'wp-dialyra' ); ?>">
							<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
						</button>
						<button class="wp-dialyra-icon-action" type="button" aria-label="<?php esc_attr_e( 'Select specific product', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Products', 'wp-dialyra' ); ?>" data-dialyra-open-product-picker>
							<span class="dashicons dashicons-products" aria-hidden="true"></span>
						</button>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<div class="wp-dialyra-product-picker" data-dialyra-product-picker hidden>
		<div class="wp-dialyra-product-picker__overlay" data-dialyra-close-product-picker></div>
		<section class="wp-dialyra-product-picker__panel" role="dialog" aria-modal="true" aria-labelledby="wp-dialyra-product-picker-title">
			<div class="wp-dialyra-product-picker__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Product Targeting', 'wp-dialyra' ); ?></p>
					<h3 id="wp-dialyra-product-picker-title"><?php esc_html_e( 'Select specific products', 'wp-dialyra' ); ?></h3>
					<p><?php esc_html_e( 'Choose products that should use this flow instead of the default flow.', 'wp-dialyra' ); ?></p>
				</div>
				<button class="wp-dialyra-icon-action" type="button" aria-label="<?php esc_attr_e( 'Close product picker', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Close', 'wp-dialyra' ); ?>" data-dialyra-close-product-picker>
					<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
				</button>
			</div>

			<div class="wp-dialyra-product-picker__modes">
				<label><input type="radio" name="product_assignment_mode" checked> <span><?php esc_html_e( 'Specific products', 'wp-dialyra' ); ?></span></label>
				<label><input type="radio" name="product_assignment_mode"> <span><?php esc_html_e( 'Specific categories', 'wp-dialyra' ); ?></span></label>
				<label><input type="radio" name="product_assignment_mode"> <span><?php esc_html_e( 'All products', 'wp-dialyra' ); ?></span></label>
			</div>

			<div class="wp-dialyra-product-picker__grid">
				<div class="wp-dialyra-product-picker__search">
					<label for="wp-dialyra-product-search"><?php esc_html_e( 'Search products', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-product-search" type="search" placeholder="<?php esc_attr_e( 'Search by product name or SKU...', 'wp-dialyra' ); ?>">

					<div class="wp-dialyra-product-options">
						<label><input type="checkbox" checked> <span><?php esc_html_e( 'Premium Cotton Panjabi', 'wp-dialyra' ); ?></span><em>SKU-PCP-101</em></label>
						<label><input type="checkbox" checked> <span><?php esc_html_e( 'Classic Saree Collection', 'wp-dialyra' ); ?></span><em>SKU-CSC-220</em></label>
						<label><input type="checkbox"> <span><?php esc_html_e( 'Leather Wallet Gift Box', 'wp-dialyra' ); ?></span><em>SKU-LWG-044</em></label>
						<label><input type="checkbox"> <span><?php esc_html_e( 'Summer Sale Bundle', 'wp-dialyra' ); ?></span><em>SKU-SSB-702</em></label>
					</div>
				</div>

				<aside class="wp-dialyra-product-picker__selected">
					<span><?php esc_html_e( 'Selected products', 'wp-dialyra' ); ?></span>
					<strong>2</strong>
					<p><?php esc_html_e( 'These products will call customers using this flow when matched on order items.', 'wp-dialyra' ); ?></p>
					<div>
						<em><?php esc_html_e( 'Premium Cotton Panjabi', 'wp-dialyra' ); ?></em>
						<em><?php esc_html_e( 'Classic Saree Collection', 'wp-dialyra' ); ?></em>
					</div>
				</aside>
			</div>

			<div class="wp-dialyra-product-picker__foot">
				<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-close-product-picker><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></button>
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button"><?php esc_html_e( 'Save Product Assignment', 'wp-dialyra' ); ?></button>
			</div>
		</section>
	</div>
</section>
