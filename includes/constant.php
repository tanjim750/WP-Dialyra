<?php

/**
 * Shared Dialyra constants.
 *
 * Keeps WordPress option keys in one place so storage keys stay consistent
 * across auth, business, flow, setup, and settings screens.
 *
 * @package Wp_Dialyra
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'WP_DIALYRA_OPTION_ACCESS_TOKEN' ) ) {
	define( 'WP_DIALYRA_OPTION_ACCESS_TOKEN', 'dialyra_access_token' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_REFRESH_TOKEN' ) ) {
	define( 'WP_DIALYRA_OPTION_REFRESH_TOKEN', 'dialyra_refresh_token' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_BUSINESS_ID' ) ) {
	define( 'WP_DIALYRA_OPTION_BUSINESS_ID', 'dialyra_business_id' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_USER_INFO' ) ) {
	define( 'WP_DIALYRA_OPTION_USER_INFO', 'dialyra_user_info' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_SITE_ACCESS_TOKEN' ) ) {
	define( 'WP_DIALYRA_OPTION_SITE_ACCESS_TOKEN', 'dialyra_site_access_token' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_SETUP_SETTINGS' ) ) {
	define( 'WP_DIALYRA_OPTION_SETUP_SETTINGS', 'dialyra_setup_settings' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_BUSINESS_DATA' ) ) {
	define( 'WP_DIALYRA_OPTION_BUSINESS_DATA', 'dialyra_business_data' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_DEFAULT_FLOW_ID' ) ) {
	define( 'WP_DIALYRA_OPTION_DEFAULT_FLOW_ID', 'dialyra_default_flow_id' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_DEFAULT_FLOW_DATA' ) ) {
	define( 'WP_DIALYRA_OPTION_DEFAULT_FLOW_DATA', 'dialyra_default_flow_data' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_FLOW_DRAFT_JSON' ) ) {
	define( 'WP_DIALYRA_OPTION_FLOW_DRAFT_JSON', 'dialyra_flow_draft_json' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX' ) ) {
	define( 'WP_DIALYRA_OPTION_FLOW_SOURCE_PREFIX', 'dialyra_flow_source_' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_FLOW_PRODUCT_ASSIGNMENTS_TABLE_VERSION' ) ) {
	define( 'WP_DIALYRA_OPTION_FLOW_PRODUCT_ASSIGNMENTS_TABLE_VERSION', 'dialyra_flow_product_assignments_table_version' );
}

if ( ! defined( 'WP_DIALYRA_WP_OPTION_ADMIN_EMAIL' ) ) {
	define( 'WP_DIALYRA_WP_OPTION_ADMIN_EMAIL', 'admin_email' );
}

if ( ! defined( 'WP_DIALYRA_WP_OPTION_DATE_FORMAT' ) ) {
	define( 'WP_DIALYRA_WP_OPTION_DATE_FORMAT', 'date_format' );
}

if ( ! defined( 'WP_DIALYRA_WP_OPTION_TIME_FORMAT' ) ) {
	define( 'WP_DIALYRA_WP_OPTION_TIME_FORMAT', 'time_format' );
}

if ( ! defined( 'WP_DIALYRA_WP_OPTION_WC_DEFAULT_COUNTRY' ) ) {
	define( 'WP_DIALYRA_WP_OPTION_WC_DEFAULT_COUNTRY', 'woocommerce_default_country' );
}
