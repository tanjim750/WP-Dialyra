<?php

/**
 * Dialyra Flow Product Assignment Manager.
 *
 * Stores product-to-flow targeting in a dedicated WordPress database table.
 *
 * @package Wp_Dialyra
 * @subpackage Wp_Dialyra/includes/flow
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dialyra_Flow_Product_Assignment_Manager {

	const TABLE_VERSION = '1.0.0';

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->maybe_install_table();
	}

	/**
	 * Create or update the assignment table.
	 *
	 * @since    1.0.0
	 */
	public static function install_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			business_id bigint(20) unsigned NOT NULL,
			flow_id bigint(20) unsigned NOT NULL,
			product_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY business_flow_product (business_id, flow_id, product_id),
			KEY business_product (business_id, product_id),
			KEY business_flow (business_id, flow_id)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( self::get_table_version_option(), self::TABLE_VERSION, false );
	}

	/**
	 * Get the assignment table name.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'dialyra_flow_product_assignments';
	}

	/**
	 * Get all assignments grouped by flow ID.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Business ID.
	 * @return   array
	 */
	public function get_assignments_by_flow( $business_id ) {
		global $wpdb;

		$business_id = absint( $business_id );

		if ( ! $business_id ) {
			return array();
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT flow_id, product_id FROM ' . self::get_table_name() . ' WHERE business_id = %d ORDER BY flow_id ASC, product_id ASC',
				$business_id
			),
			ARRAY_A
		);

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return array();
		}

		$assignments = array();

		foreach ( $rows as $row ) {
			$flow_id    = isset( $row['flow_id'] ) ? absint( $row['flow_id'] ) : 0;
			$product_id = isset( $row['product_id'] ) ? absint( $row['product_id'] ) : 0;

			if ( ! $flow_id || ! $product_id ) {
				continue;
			}

			if ( ! isset( $assignments[ $flow_id ] ) ) {
				$assignments[ $flow_id ] = array();
			}

			$assignments[ $flow_id ][] = $product_id;
		}

		return $assignments;
	}

	/**
	 * Save the complete product list for one flow.
	 *
	 * @since    1.0.0
	 * @param    int      $business_id    Business ID.
	 * @param    int      $flow_id        Flow ID.
	 * @param    array    $product_ids    Product IDs.
	 * @return   bool
	 */
	public function set_flow_products( $business_id, $flow_id, $product_ids ) {
		global $wpdb;

		$business_id = absint( $business_id );
		$flow_id     = absint( $flow_id );
		$product_ids = is_array( $product_ids ) ? array_values( array_filter( array_unique( array_map( 'absint', $product_ids ) ) ) ) : array();

		if ( ! $business_id || ! $flow_id ) {
			return false;
		}

		$table_name = self::get_table_name();
		$now        = current_time( 'mysql' );

		$deleted = $wpdb->delete(
			$table_name,
			array(
				'business_id' => $business_id,
				'flow_id'     => $flow_id,
			),
			array( '%d', '%d' )
		);

		if ( false === $deleted ) {
			return false;
		}

		foreach ( $product_ids as $product_id ) {
			$inserted = $wpdb->insert(
				$table_name,
				array(
					'business_id' => $business_id,
					'flow_id'     => $flow_id,
					'product_id'  => $product_id,
					'created_at'  => $now,
					'updated_at'  => $now,
				),
				array( '%d', '%d', '%d', '%s', '%s' )
			);

			if ( false === $inserted ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Delete all product assignments for one flow.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Business ID.
	 * @param    int    $flow_id        Flow ID.
	 * @return   bool
	 */
	public function delete_flow_assignments( $business_id, $flow_id ) {
		global $wpdb;

		$business_id = absint( $business_id );
		$flow_id     = absint( $flow_id );

		if ( ! $business_id || ! $flow_id ) {
			return false;
		}

		$wpdb->delete(
			self::get_table_name(),
			array(
				'business_id' => $business_id,
				'flow_id'     => $flow_id,
			),
			array( '%d', '%d' )
		);

		return true;
	}

	/**
	 * Count flows that have product rules.
	 *
	 * @since    1.0.0
	 * @param    int    $business_id    Business ID.
	 * @return   int
	 */
	public function count_flow_rules( $business_id ) {
		global $wpdb;

		$business_id = absint( $business_id );

		if ( ! $business_id ) {
			return 0;
		}

		return absint(
			$wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(DISTINCT flow_id) FROM ' . self::get_table_name() . ' WHERE business_id = %d',
					$business_id
				)
			)
		);
	}

	/**
	 * Migrate legacy option-based assignments into the table.
	 *
	 * @since    1.0.0
	 * @param    int       $business_id    Business ID.
	 * @param    string    $option_key     Legacy option key.
	 * @return   bool
	 */
	public function migrate_legacy_option( $business_id, $option_key = 'dialyra_flow_product_assignments' ) {
		$business_id = absint( $business_id );

		if ( ! $business_id || $this->count_flow_rules( $business_id ) > 0 ) {
			return false;
		}

		$legacy_assignments = get_option( $option_key, array() );

		if ( ! is_array( $legacy_assignments ) || empty( $legacy_assignments ) ) {
			return false;
		}

		foreach ( $legacy_assignments as $flow_id => $product_ids ) {
			if ( ! is_array( $product_ids ) ) {
				continue;
			}

			$this->set_flow_products( $business_id, absint( $flow_id ), $product_ids );
		}

		delete_option( $option_key );

		return true;
	}

	/**
	 * Install table if this code was deployed after plugin activation.
	 *
	 * @since    1.0.0
	 */
	private function maybe_install_table() {
		$installed_version = get_option( self::get_table_version_option(), '' );

		if ( self::TABLE_VERSION !== $installed_version ) {
			self::install_table();
		}
	}

	/**
	 * Get table version option key.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private static function get_table_version_option() {
		return defined( 'WP_DIALYRA_OPTION_FLOW_PRODUCT_ASSIGNMENTS_TABLE_VERSION' ) ? WP_DIALYRA_OPTION_FLOW_PRODUCT_ASSIGNMENTS_TABLE_VERSION : 'dialyra_flow_product_assignments_table_version';
	}
}
