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

$wp_dialyra_plugin               = class_exists( 'Wp_Dialyra' ) ? Wp_Dialyra::get_instance() : null;
$wp_dialyra_flow_manager         = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_flow_manager' ) ? $wp_dialyra_plugin->get_flow_manager() : null;
$wp_dialyra_business_manager     = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_business_manager' ) ? $wp_dialyra_plugin->get_business_manager() : null;
$wp_dialyra_flow_product_manager = $wp_dialyra_plugin && method_exists( $wp_dialyra_plugin, 'get_flow_product_assignment_manager' ) ? $wp_dialyra_plugin->get_flow_product_assignment_manager() : null;
$wp_dialyra_business_id          = class_exists( 'Dialyra_Auth_Manager' ) ? absint( Dialyra_Auth_Manager::get_business_id() ) : 0;
$wp_dialyra_assignments          = array();
$wp_dialyra_notice_error         = '';
$wp_dialyra_notice_success       = '';

if ( $wp_dialyra_flow_product_manager && $wp_dialyra_business_id ) {
	if ( method_exists( $wp_dialyra_flow_product_manager, 'migrate_legacy_option' ) ) {
		$wp_dialyra_flow_product_manager->migrate_legacy_option( $wp_dialyra_business_id );
	}

	$wp_dialyra_assignments = $wp_dialyra_flow_product_manager->get_assignments_by_flow( $wp_dialyra_business_id );
}

$wp_dialyra_extract_response_items = static function ( $response ) {
	if ( ! $response || ! is_object( $response ) || ! method_exists( $response, 'is_successful' ) || ! $response->is_successful() || ! method_exists( $response, 'get_data' ) ) {
		return array();
	}

	$data = $response->get_data();
	$data = is_array( $data ) ? $data : array();

	if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
		$data = $data['data'];
	}

	foreach ( array( 'items', 'flows', 'results', 'data' ) as $container_key ) {
		if ( isset( $data[ $container_key ] ) && is_array( $data[ $container_key ] ) ) {
			return $data[ $container_key ];
		}
	}

	if ( isset( $data[0] ) && is_array( $data[0] ) ) {
		return $data;
	}

	return array();
};

$wp_dialyra_normalize_flow = static function ( $flow ) {
	$flow = is_array( $flow ) ? $flow : array();

	return array(
		'id'          => isset( $flow['id'] ) ? absint( $flow['id'] ) : 0,
		'name'        => ! empty( $flow['name'] ) ? sanitize_text_field( $flow['name'] ) : __( 'Untitled flow', 'wp-dialyra' ),
		'description' => ! empty( $flow['description'] ) ? sanitize_text_field( $flow['description'] ) : __( 'No description added yet.', 'wp-dialyra' ),
		'status'      => ! empty( $flow['status'] ) ? sanitize_key( $flow['status'] ) : 'draft',
		'created_at'  => ! empty( $flow['created_at'] ) ? sanitize_text_field( $flow['created_at'] ) : '',
		'updated_at'  => ! empty( $flow['updated_at'] ) ? sanitize_text_field( $flow['updated_at'] ) : '',
		'version'     => isset( $flow['version'] ) ? absint( $flow['version'] ) : 0,
	);
};

$wp_dialyra_product_list = array();

if ( function_exists( 'wc_get_products' ) ) {
	$wp_dialyra_wc_products = wc_get_products(
		array(
			'limit'  => 100,
			'status' => array( 'publish', 'private', 'draft' ),
			'return' => 'objects',
		)
	);

	foreach ( $wp_dialyra_wc_products as $wp_dialyra_product ) {
		if ( ! is_object( $wp_dialyra_product ) || ! method_exists( $wp_dialyra_product, 'get_id' ) ) {
			continue;
		}

		$wp_dialyra_product_list[] = array(
			'id'   => absint( $wp_dialyra_product->get_id() ),
			'name' => sanitize_text_field( $wp_dialyra_product->get_name() ),
			'sku'  => method_exists( $wp_dialyra_product, 'get_sku' ) ? sanitize_text_field( $wp_dialyra_product->get_sku() ) : '',
		);
	}
} elseif ( post_type_exists( 'product' ) ) {
	$wp_dialyra_product_posts = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( $wp_dialyra_product_posts as $wp_dialyra_product_id ) {
		$wp_dialyra_product_list[] = array(
			'id'   => absint( $wp_dialyra_product_id ),
			'name' => sanitize_text_field( get_the_title( $wp_dialyra_product_id ) ),
			'sku'  => sanitize_text_field( get_post_meta( $wp_dialyra_product_id, '_sku', true ) ),
		);
	}
}

$wp_dialyra_product_names = array();

foreach ( $wp_dialyra_product_list as $wp_dialyra_product ) {
	if ( ! empty( $wp_dialyra_product['id'] ) ) {
		$wp_dialyra_product_names[ absint( $wp_dialyra_product['id'] ) ] = $wp_dialyra_product['name'];
	}
}

if ( 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) && isset( $_POST['wp_dialyra_flows_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_dialyra_flows_nonce'] ), 'wp-dialyra-flows' ) ) {
	$wp_dialyra_action  = isset( $_POST['wp_dialyra_flow_action'] ) ? sanitize_key( wp_unslash( $_POST['wp_dialyra_flow_action'] ) ) : '';
	$wp_dialyra_flow_id = isset( $_POST['flow_id'] ) ? absint( wp_unslash( $_POST['flow_id'] ) ) : 0;

	if ( ! $wp_dialyra_business_id ) {
		$wp_dialyra_notice_error = esc_html__( 'Connect a business before managing flows.', 'wp-dialyra' );
	} elseif ( ! $wp_dialyra_flow_manager ) {
		$wp_dialyra_notice_error = esc_html__( 'Flow service is not available.', 'wp-dialyra' );
	} elseif ( 'delete_flow' === $wp_dialyra_action ) {
		if ( ! $wp_dialyra_flow_id ) {
			$wp_dialyra_notice_error = esc_html__( 'Choose a valid flow to delete.', 'wp-dialyra' );
		} elseif ( method_exists( $wp_dialyra_flow_manager, 'get_default_flow_id' ) && $wp_dialyra_flow_id === $wp_dialyra_flow_manager->get_default_flow_id() ) {
			$wp_dialyra_notice_error = esc_html__( 'Default flow cannot be deleted. Select another default flow first, then delete this flow.', 'wp-dialyra' );
		} else {
			$wp_dialyra_flow_has_product_bindings = ! empty( $wp_dialyra_assignments[ $wp_dialyra_flow_id ] );
			$response = $wp_dialyra_flow_manager->delete_flow( $wp_dialyra_flow_id );

			if ( $response && $response->is_successful() ) {
				if ( $wp_dialyra_flow_has_product_bindings && $wp_dialyra_flow_product_manager ) {
					$wp_dialyra_flow_product_manager->delete_flow_assignments( $wp_dialyra_business_id, $wp_dialyra_flow_id );
					unset( $wp_dialyra_assignments[ $wp_dialyra_flow_id ] );
				}

				if ( defined( 'WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX' ) ) {
					delete_option( WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX . $wp_dialyra_business_id . '_' . $wp_dialyra_flow_id );
				}

				$wp_dialyra_notice_success = $wp_dialyra_flow_has_product_bindings ? esc_html__( 'Flow deleted successfully. Product bindings were removed from this store.', 'wp-dialyra' ) : esc_html__( 'Flow deleted successfully.', 'wp-dialyra' );
			} else {
				$wp_dialyra_notice_error = $response && method_exists( $response, 'get_message' ) ? $response->get_message() : esc_html__( 'Flow delete failed.', 'wp-dialyra' );
			}
		}
	} elseif ( 'set_default_flow' === $wp_dialyra_action ) {
		if ( ! $wp_dialyra_flow_id ) {
			$wp_dialyra_notice_error = esc_html__( 'Choose a valid flow to make default.', 'wp-dialyra' );
		} else {
			$response = $wp_dialyra_flow_manager->set_default_flow( $wp_dialyra_flow_id );

			if ( $response && $response->is_successful() ) {
				if ( $wp_dialyra_business_manager && method_exists( $wp_dialyra_business_manager, 'save_setup_settings' ) ) {
					$wp_dialyra_business_manager->save_setup_settings(
						array(
							'business_id'     => $wp_dialyra_business_id,
							'default_flow_id' => $wp_dialyra_flow_id,
						)
					);
				}

				$wp_dialyra_notice_success = esc_html__( 'Default flow updated successfully.', 'wp-dialyra' );
			} else {
				$wp_dialyra_notice_error = $response && method_exists( $response, 'get_message' ) ? $response->get_message() : esc_html__( 'Default flow update failed.', 'wp-dialyra' );
			}
		}
	} elseif ( 'save_product_assignment' === $wp_dialyra_action ) {
		$product_ids = isset( $_POST['product_ids'] ) && is_array( $_POST['product_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['product_ids'] ) ) : array();
		$product_ids = array_values( array_filter( array_unique( $product_ids ) ) );

		if ( ! $wp_dialyra_flow_id ) {
			$wp_dialyra_notice_error = esc_html__( 'Choose a valid flow before saving products.', 'wp-dialyra' );
		} elseif ( ! $wp_dialyra_flow_product_manager ) {
			$wp_dialyra_notice_error = esc_html__( 'Product assignment storage is not available.', 'wp-dialyra' );
		} elseif ( empty( $product_ids ) ) {
			if ( $wp_dialyra_flow_product_manager->set_flow_products( $wp_dialyra_business_id, $wp_dialyra_flow_id, array() ) ) {
				unset( $wp_dialyra_assignments[ $wp_dialyra_flow_id ] );
				$wp_dialyra_notice_success = esc_html__( 'Product assignment cleared.', 'wp-dialyra' );
			} else {
				$wp_dialyra_notice_error = esc_html__( 'Product assignment update failed.', 'wp-dialyra' );
			}
		} else {
			if ( $wp_dialyra_flow_product_manager->set_flow_products( $wp_dialyra_business_id, $wp_dialyra_flow_id, $product_ids ) ) {
				$wp_dialyra_assignments[ $wp_dialyra_flow_id ] = $product_ids;
				$wp_dialyra_notice_success = esc_html__( 'Product assignment saved.', 'wp-dialyra' );
			} else {
				$wp_dialyra_notice_error = esc_html__( 'Product assignment update failed.', 'wp-dialyra' );
			}
		}
	}
}

$wp_dialyra_status_filter = isset( $_GET['flow_status'] ) ? sanitize_key( wp_unslash( $_GET['flow_status'] ) ) : 'all';
$wp_dialyra_search_filter = isset( $_GET['flow_search'] ) ? sanitize_text_field( wp_unslash( $_GET['flow_search'] ) ) : '';
$wp_dialyra_allowed_statuses = array( 'all', 'published', 'draft', 'paused' );
$wp_dialyra_status_filter = in_array( $wp_dialyra_status_filter, $wp_dialyra_allowed_statuses, true ) ? $wp_dialyra_status_filter : 'all';
$wp_dialyra_flows = array();

if ( $wp_dialyra_flow_manager && $wp_dialyra_business_id ) {
	$query_args = array( 'business_id' => $wp_dialyra_business_id );

	if ( 'all' !== $wp_dialyra_status_filter ) {
		$query_args['status'] = $wp_dialyra_status_filter;
	}

	$response = $wp_dialyra_flow_manager->get_flows( $query_args );

	if ( $response && $response->is_successful() ) {
		$wp_dialyra_flows = array_values(
			array_filter(
				array_map( $wp_dialyra_normalize_flow, $wp_dialyra_extract_response_items( $response ) ),
				static function ( $flow ) use ( $wp_dialyra_search_filter ) {
					if ( empty( $flow['id'] ) ) {
						return false;
					}

					if ( 'archived' === $flow['status'] ) {
						return false;
					}

					if ( '' === $wp_dialyra_search_filter ) {
						return true;
					}

					return false !== stripos( $flow['name'], $wp_dialyra_search_filter ) || false !== stripos( $flow['description'], $wp_dialyra_search_filter );
				}
			)
		);
	} elseif ( empty( $wp_dialyra_notice_error ) ) {
		$wp_dialyra_notice_error = $response && method_exists( $response, 'get_message' ) ? $response->get_message() : esc_html__( 'Unable to fetch flows.', 'wp-dialyra' );
	}
} elseif ( empty( $wp_dialyra_notice_error ) ) {
	$wp_dialyra_notice_error = esc_html__( 'Connect a business before managing flows.', 'wp-dialyra' );
}

$wp_dialyra_default_flow_id = $wp_dialyra_flow_manager && method_exists( $wp_dialyra_flow_manager, 'get_default_flow_id' ) ? $wp_dialyra_flow_manager->get_default_flow_id() : 0;
$wp_dialyra_default_name    = __( 'Not selected', 'wp-dialyra' );
$wp_dialyra_published_count = 0;
$wp_dialyra_draft_count     = 0;

foreach ( $wp_dialyra_flows as $wp_dialyra_flow ) {
	if ( 'published' === $wp_dialyra_flow['status'] ) {
		++$wp_dialyra_published_count;
	}

	if ( 'draft' === $wp_dialyra_flow['status'] ) {
		++$wp_dialyra_draft_count;
	}

	if ( absint( $wp_dialyra_flow['id'] ) === $wp_dialyra_default_flow_id ) {
		$wp_dialyra_default_name = $wp_dialyra_flow['name'];
	}
}

$wp_dialyra_active_product_rules = $wp_dialyra_flow_product_manager ? $wp_dialyra_flow_product_manager->count_flow_rules( $wp_dialyra_business_id ) : 0;
$wp_dialyra_format_date = static function ( $date_value ) {
	if ( empty( $date_value ) ) {
		return __( 'Not available', 'wp-dialyra' );
	}

	$timestamp = strtotime( $date_value );

	if ( ! $timestamp ) {
		return sanitize_text_field( $date_value );
	}

	return wp_date( get_option( WP_DIALYRA_WP_OPTION_DATE_FORMAT ), $timestamp );
};

$wp_dialyra_product_label = static function ( $flow_id ) use ( $wp_dialyra_assignments, $wp_dialyra_product_names ) {
	$flow_id = absint( $flow_id );

	if ( empty( $wp_dialyra_assignments[ $flow_id ] ) || ! is_array( $wp_dialyra_assignments[ $flow_id ] ) ) {
		return __( 'No product rule', 'wp-dialyra' );
	}

	$product_ids = array_values( array_filter( array_map( 'absint', $wp_dialyra_assignments[ $flow_id ] ) ) );

	if ( 1 === count( $product_ids ) ) {
		$product_id = $product_ids[0];

		return isset( $wp_dialyra_product_names[ $product_id ] ) ? $wp_dialyra_product_names[ $product_id ] : sprintf( __( 'Product #%d', 'wp-dialyra' ), $product_id );
	}

	return sprintf( _n( '%d product', '%d products', count( $product_ids ), 'wp-dialyra' ), count( $product_ids ) );
};

?>

<section class="wp-dialyra-flows">
	<div class="wp-dialyra-flows__hero">
		<div>
			<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Flows', 'wp-dialyra' ); ?></p>
			<h2><?php esc_html_e( 'Manage every customer call flow from one clean library.', 'wp-dialyra' ); ?></h2>
			<p><?php esc_html_e( 'Create, review, preview, and monitor menu-based IVR flows before they reach WooCommerce customers.', 'wp-dialyra' ); ?></p>
		</div>

		<div class="wp-dialyra-flows__actions">
			<a class="wp-dialyra-button wp-dialyra-button--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-builder' ) ); ?>"><?php esc_html_e( '+ Create Flow', 'wp-dialyra' ); ?></a>
		</div>
	</div>

	<?php if ( $wp_dialyra_notice_error ) : ?>
		<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--error">
			<span class="dashicons dashicons-warning" aria-hidden="true"></span>
			<p><?php echo esc_html( $wp_dialyra_notice_error ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $wp_dialyra_notice_success ) : ?>
		<div class="wp-dialyra-fuse-warning wp-dialyra-fuse-warning--success">
			<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
			<p><?php echo esc_html( $wp_dialyra_notice_success ); ?></p>
		</div>
	<?php endif; ?>

	<div class="wp-dialyra-flow-summary" aria-label="<?php esc_attr_e( 'Flow summary', 'wp-dialyra' ); ?>">
		<div>
			<span><?php esc_html_e( 'Published', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $wp_dialyra_published_count ) ); ?></strong>
			<small><?php esc_html_e( 'Currently callable', 'wp-dialyra' ); ?></small>
		</div>
		<div>
			<span><?php esc_html_e( 'Drafts', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $wp_dialyra_draft_count ) ); ?></strong>
			<small><?php esc_html_e( 'Needs review', 'wp-dialyra' ); ?></small>
		</div>
		<div>
			<span><?php esc_html_e( 'Default Flow', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( $wp_dialyra_default_name ); ?></strong>
			<small><?php esc_html_e( 'Used when no product rule matches', 'wp-dialyra' ); ?></small>
		</div>
		<div>
			<span><?php esc_html_e( 'Product Rules', 'wp-dialyra' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $wp_dialyra_active_product_rules ) ); ?></strong>
			<small><?php esc_html_e( 'Flows assigned to products', 'wp-dialyra' ); ?></small>
		</div>
	</div>

	<section class="wp-dialyra-flows-panel">
		<div class="wp-dialyra-flows-panel__head">
			<div>
				<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Flow Library', 'wp-dialyra' ); ?></p>
				<h3><?php esc_html_e( 'Saved flows', 'wp-dialyra' ); ?></h3>
			</div>

			<form class="wp-dialyra-flow-filters" method="get">
				<input type="hidden" name="page" value="wp-dialyra">
				<input type="hidden" name="p" value="flows">
				<label>
					<span><?php esc_html_e( 'Status', 'wp-dialyra' ); ?></span>
					<select name="flow_status">
						<option value="all" <?php selected( $wp_dialyra_status_filter, 'all' ); ?>><?php esc_html_e( 'All statuses', 'wp-dialyra' ); ?></option>
						<option value="published" <?php selected( $wp_dialyra_status_filter, 'published' ); ?>><?php esc_html_e( 'Published', 'wp-dialyra' ); ?></option>
						<option value="draft" <?php selected( $wp_dialyra_status_filter, 'draft' ); ?>><?php esc_html_e( 'Draft', 'wp-dialyra' ); ?></option>
						<option value="paused" <?php selected( $wp_dialyra_status_filter, 'paused' ); ?>><?php esc_html_e( 'Paused', 'wp-dialyra' ); ?></option>
					</select>
				</label>
				<label>
					<span><?php esc_html_e( 'Search', 'wp-dialyra' ); ?></span>
					<input name="flow_search" type="search" value="<?php echo esc_attr( $wp_dialyra_search_filter ); ?>" placeholder="<?php esc_attr_e( 'Search flow name...', 'wp-dialyra' ); ?>">
				</label>
				<button class="wp-dialyra-button wp-dialyra-button--primary wp-dialyra-flow-filter-button" type="submit"><?php esc_html_e( 'Filter', 'wp-dialyra' ); ?></button>
			</form>
		</div>

		<?php if ( empty( $wp_dialyra_flows ) ) : ?>
			<div class="wp-dialyra-empty-card">
				<span class="dashicons dashicons-networking" aria-hidden="true"></span>
				<h4><?php esc_html_e( 'No flows found', 'wp-dialyra' ); ?></h4>
				<p><?php esc_html_e( 'Create and publish your first call flow from the visual builder. Saved drafts are restored automatically.', 'wp-dialyra' ); ?></p>
				<a class="wp-dialyra-button wp-dialyra-button--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-builder' ) ); ?>"><?php esc_html_e( 'Create Flow', 'wp-dialyra' ); ?></a>
			</div>
		<?php else : ?>
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
					<?php
					$wp_dialyra_flow_id = absint( $wp_dialyra_flow['id'] );
					$wp_dialyra_selected_products = ! empty( $wp_dialyra_assignments[ $wp_dialyra_flow_id ] ) && is_array( $wp_dialyra_assignments[ $wp_dialyra_flow_id ] ) ? array_values( array_filter( array_map( 'absint', $wp_dialyra_assignments[ $wp_dialyra_flow_id ] ) ) ) : array();
					$wp_dialyra_flow_status_class = sanitize_html_class( $wp_dialyra_flow['status'] );
					?>
					<article class="wp-dialyra-flow-table__row wp-dialyra-flow-table__row--<?php echo esc_attr( $wp_dialyra_flow_status_class ); ?>" role="row">
						<div class="wp-dialyra-flow-table__name" role="cell" data-label="<?php esc_attr_e( 'Name', 'wp-dialyra' ); ?>">
							<div>
								<h4><?php echo esc_html( $wp_dialyra_flow['name'] ); ?></h4>
								<p><?php echo esc_html( $wp_dialyra_flow['description'] ); ?></p>
							</div>
						</div>

						<div role="cell" data-label="<?php esc_attr_e( 'Status', 'wp-dialyra' ); ?>">
							<span class="wp-dialyra-flow-list__status"><?php echo esc_html( ucwords( str_replace( '_', ' ', $wp_dialyra_flow['status'] ) ) ); ?></span>
						</div>

						<div role="cell" data-label="<?php esc_attr_e( 'Is Default', 'wp-dialyra' ); ?>">
							<?php if ( $wp_dialyra_flow_id === $wp_dialyra_default_flow_id ) : ?>
								<span class="wp-dialyra-default-pill wp-dialyra-default-pill--active"><?php esc_html_e( 'Default', 'wp-dialyra' ); ?></span>
							<?php else : ?>
								<span class="wp-dialyra-default-pill"><?php esc_html_e( 'No', 'wp-dialyra' ); ?></span>
							<?php endif; ?>
						</div>

						<div role="cell" data-label="<?php esc_attr_e( 'Product', 'wp-dialyra' ); ?>">
							<span class="wp-dialyra-product-pill"><?php echo esc_html( $wp_dialyra_product_label( $wp_dialyra_flow_id ) ); ?></span>
						</div>

						<div role="cell" data-label="<?php esc_attr_e( 'Created Date', 'wp-dialyra' ); ?>">
							<strong class="wp-dialyra-flow-date"><?php echo esc_html( $wp_dialyra_format_date( $wp_dialyra_flow['created_at'] ) ); ?></strong>
						</div>

						<div class="wp-dialyra-flow-list__actions" role="cell" data-label="<?php esc_attr_e( 'Actions', 'wp-dialyra' ); ?>">
							<form method="post" class="wp-dialyra-inline-action">
								<?php wp_nonce_field( 'wp-dialyra-flows', 'wp_dialyra_flows_nonce' ); ?>
								<input type="hidden" name="wp_dialyra_flow_action" value="delete_flow">
								<input type="hidden" name="flow_id" value="<?php echo esc_attr( $wp_dialyra_flow_id ); ?>">
								<button class="wp-dialyra-icon-action wp-dialyra-icon-action--danger" type="button" aria-label="<?php esc_attr_e( 'Delete flow', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Delete', 'wp-dialyra' ); ?>" data-dialyra-flow-delete-open data-dialyra-flow-name="<?php echo esc_attr( $wp_dialyra_flow['name'] ); ?>" data-dialyra-flow-is-default="<?php echo esc_attr( $wp_dialyra_flow_id === $wp_dialyra_default_flow_id ? '1' : '0' ); ?>">
									<span class="dashicons dashicons-trash" aria-hidden="true"></span>
								</button>
							</form>
							<a class="wp-dialyra-icon-action" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-dialyra&p=flow-builder&flow_id=' . $wp_dialyra_flow_id ) ); ?>" aria-label="<?php esc_attr_e( 'Edit flow', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Edit', 'wp-dialyra' ); ?>">
								<span class="dashicons dashicons-edit" aria-hidden="true"></span>
							</a>
							<form method="post" class="wp-dialyra-inline-action">
								<?php wp_nonce_field( 'wp-dialyra-flows', 'wp_dialyra_flows_nonce' ); ?>
								<input type="hidden" name="wp_dialyra_flow_action" value="set_default_flow">
								<input type="hidden" name="flow_id" value="<?php echo esc_attr( $wp_dialyra_flow_id ); ?>">
								<button class="wp-dialyra-icon-action wp-dialyra-icon-action--success" type="submit" aria-label="<?php esc_attr_e( 'Select as default flow', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Set default', 'wp-dialyra' ); ?>" <?php disabled( $wp_dialyra_flow_id, $wp_dialyra_default_flow_id ); ?>>
									<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
								</button>
							</form>
							<button class="wp-dialyra-icon-action" type="button" aria-label="<?php esc_attr_e( 'Select specific product', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Products', 'wp-dialyra' ); ?>" data-dialyra-open-product-picker data-dialyra-flow-id="<?php echo esc_attr( $wp_dialyra_flow_id ); ?>" data-dialyra-flow-name="<?php echo esc_attr( $wp_dialyra_flow['name'] ); ?>" data-dialyra-selected-products="<?php echo esc_attr( implode( ',', $wp_dialyra_selected_products ) ); ?>">
								<span class="dashicons dashicons-products" aria-hidden="true"></span>
							</button>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>

	<div class="wp-dialyra-product-picker" data-dialyra-product-picker hidden>
		<div class="wp-dialyra-product-picker__overlay" data-dialyra-close-product-picker></div>
		<form class="wp-dialyra-product-picker__panel" method="post" role="dialog" aria-modal="true" aria-labelledby="wp-dialyra-product-picker-title">
			<?php wp_nonce_field( 'wp-dialyra-flows', 'wp_dialyra_flows_nonce' ); ?>
			<input type="hidden" name="wp_dialyra_flow_action" value="save_product_assignment">
			<input type="hidden" name="flow_id" value="" data-dialyra-product-flow-id>

			<div class="wp-dialyra-product-picker__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Product Targeting', 'wp-dialyra' ); ?></p>
					<h3 id="wp-dialyra-product-picker-title"><?php esc_html_e( 'Select specific products', 'wp-dialyra' ); ?></h3>
					<p><span data-dialyra-product-flow-name><?php esc_html_e( 'Choose products that should use this flow instead of the default flow.', 'wp-dialyra' ); ?></span></p>
				</div>
				<button class="wp-dialyra-icon-action" type="button" aria-label="<?php esc_attr_e( 'Close product picker', 'wp-dialyra' ); ?>" data-tooltip="<?php esc_attr_e( 'Close', 'wp-dialyra' ); ?>" data-dialyra-close-product-picker>
					<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
				</button>
			</div>

			<div class="wp-dialyra-product-picker__grid">
				<div class="wp-dialyra-product-picker__search">
					<label for="wp-dialyra-product-search"><?php esc_html_e( 'Search products', 'wp-dialyra' ); ?></label>
					<input id="wp-dialyra-product-search" type="search" placeholder="<?php esc_attr_e( 'Search by product name or SKU...', 'wp-dialyra' ); ?>" data-dialyra-product-search>

					<div class="wp-dialyra-product-options">
						<?php if ( empty( $wp_dialyra_product_list ) ) : ?>
							<div class="wp-dialyra-product-options__empty">
								<strong><?php esc_html_e( 'No WooCommerce products found', 'wp-dialyra' ); ?></strong>
								<p><?php esc_html_e( 'Create products first, then return here to assign a flow to specific products.', 'wp-dialyra' ); ?></p>
							</div>
						<?php else : ?>
							<?php foreach ( $wp_dialyra_product_list as $wp_dialyra_product ) : ?>
								<label data-dialyra-product-option data-dialyra-product-text="<?php echo esc_attr( strtolower( $wp_dialyra_product['name'] . ' ' . $wp_dialyra_product['sku'] ) ); ?>">
									<input type="checkbox" name="product_ids[]" value="<?php echo esc_attr( $wp_dialyra_product['id'] ); ?>" data-dialyra-product-checkbox>
									<span><?php echo esc_html( $wp_dialyra_product['name'] ); ?></span>
									<em><?php echo esc_html( $wp_dialyra_product['sku'] ? $wp_dialyra_product['sku'] : '#' . $wp_dialyra_product['id'] ); ?></em>
								</label>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>

				<aside class="wp-dialyra-product-picker__selected">
					<span><?php esc_html_e( 'Selected products', 'wp-dialyra' ); ?></span>
					<strong data-dialyra-selected-product-count>0</strong>
					<p><?php esc_html_e( 'These products will call customers using this flow when matched on order items.', 'wp-dialyra' ); ?></p>
					<div data-dialyra-selected-product-list></div>
				</aside>
			</div>

			<div class="wp-dialyra-product-picker__foot">
				<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-close-product-picker><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></button>
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="submit"><?php esc_html_e( 'Save Product Assignment', 'wp-dialyra' ); ?></button>
			</div>
		</form>
	</div>

	<div id="wp-dialyra-flow-delete-dialog" class="wp-dialyra-dialog" role="dialog" aria-modal="true" aria-labelledby="wp-dialyra-flow-delete-dialog-title" hidden data-dialyra-dialog>
		<div class="wp-dialyra-dialog__backdrop" data-dialyra-dialog-close></div>
		<div class="wp-dialyra-dialog__panel wp-dialyra-dialog__panel--danger">
			<div class="wp-dialyra-dialog__head">
				<div>
					<p class="wp-dialyra-eyebrow"><?php esc_html_e( 'Flow Library', 'wp-dialyra' ); ?></p>
					<h3 id="wp-dialyra-flow-delete-dialog-title"><?php esc_html_e( 'Delete flow?', 'wp-dialyra' ); ?></h3>
				</div>
				<button class="wp-dialyra-dialog__close" type="button" data-dialyra-dialog-close aria-label="<?php esc_attr_e( 'Close dialog', 'wp-dialyra' ); ?>">
					<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
				</button>
			</div>

			<p class="wp-dialyra-dialog__warning" data-dialyra-flow-delete-message>
				<?php esc_html_e( 'This archives the flow and removes local product targeting for it. Confirm only when this flow should no longer be selectable.', 'wp-dialyra' ); ?>
			</p>

			<div class="wp-dialyra-agent-panel__footer">
				<button class="wp-dialyra-button wp-dialyra-button--ghost" type="button" data-dialyra-dialog-close><?php esc_html_e( 'Cancel', 'wp-dialyra' ); ?></button>
				<button class="wp-dialyra-button wp-dialyra-button--primary" type="button" data-dialyra-flow-delete-confirm><?php esc_html_e( 'Delete flow', 'wp-dialyra' ); ?></button>
			</div>
		</div>
	</div>
</section>
