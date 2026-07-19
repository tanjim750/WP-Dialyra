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

if ( ! defined( 'WP_DIALYRA_OPTION_SITE_ACCESS_TOKEN_CACHE' ) ) {
	define( 'WP_DIALYRA_OPTION_SITE_ACCESS_TOKEN_CACHE', 'dialyra_site_access_token_cache' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_SETUP_SETTINGS' ) ) {
	define( 'WP_DIALYRA_OPTION_SETUP_SETTINGS', 'dialyra_setup_settings' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_CALL_TRIGGER_MODE' ) ) {
	define( 'WP_DIALYRA_OPTION_CALL_TRIGGER_MODE', 'dialyra_call_trigger_mode' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_BUSINESS_HOURS' ) ) {
	define( 'WP_DIALYRA_OPTION_BUSINESS_HOURS', 'dialyra_business_hours' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_MAX_CONCURRENT_CALLS' ) ) {
	define( 'WP_DIALYRA_OPTION_MAX_CONCURRENT_CALLS', 'dialyra_max_concurrent_calls' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_RETRY_POLICY' ) ) {
	define( 'WP_DIALYRA_OPTION_RETRY_POLICY', 'dialyra_retry_policy' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_SKIP_CALL_STATUSES' ) ) {
	define( 'WP_DIALYRA_OPTION_SKIP_CALL_STATUSES', 'dialyra_skip_call_statuses' );
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

if ( ! defined( 'WP_DIALYRA_OPTION_CALL_QUEUE_TABLE_VERSION' ) ) {
	define( 'WP_DIALYRA_OPTION_CALL_QUEUE_TABLE_VERSION', 'dialyra_call_queue_table_version' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_RETRY_QUEUE_TABLE_VERSION' ) ) {
	define( 'WP_DIALYRA_OPTION_RETRY_QUEUE_TABLE_VERSION', 'dialyra_retry_queue_table_version' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_CALL_LOGS_TABLE_VERSION' ) ) {
	define( 'WP_DIALYRA_OPTION_CALL_LOGS_TABLE_VERSION', 'dialyra_call_logs_table_version' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_AUDIT_LOGS_TABLE_VERSION' ) ) {
	define( 'WP_DIALYRA_OPTION_AUDIT_LOGS_TABLE_VERSION', 'dialyra_audit_logs_table_version' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA' ) ) {
	define( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_DATA', 'dialyra_business_webhook_data' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_CREDENTIALS' ) ) {
	define( 'WP_DIALYRA_OPTION_BUSINESS_WEBHOOK_CREDENTIALS', 'dialyra_business_webhook_credentials' );
}

if ( ! defined( 'WP_DIALYRA_OPTION_WEBHOOK_EVENTS_TABLE_VERSION' ) ) {
	define( 'WP_DIALYRA_OPTION_WEBHOOK_EVENTS_TABLE_VERSION', 'dialyra_webhook_events_table_version' );
}

if ( ! defined( 'WP_DIALYRA_WEBHOOK_NAME_PREFIX' ) ) {
	define( 'WP_DIALYRA_WEBHOOK_NAME_PREFIX', 'dwp#221' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_ORDER_CONFIRMED_STATUS' ) ) {
	define( 'WP_DIALYRA_DEFAULT_ORDER_CONFIRMED_STATUS', 'processing' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_ORDER_CANCELLED_STATUS' ) ) {
	define( 'WP_DIALYRA_DEFAULT_ORDER_CANCELLED_STATUS', 'cancelled' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_CALL_NO_ANSWER_STATUS' ) ) {
	define( 'WP_DIALYRA_DEFAULT_CALL_NO_ANSWER_STATUS', 'no_change' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_CALL_BUSY_STATUS' ) ) {
	define( 'WP_DIALYRA_DEFAULT_CALL_BUSY_STATUS', 'no_change' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_CALL_FAILED_STATUS' ) ) {
	define( 'WP_DIALYRA_DEFAULT_CALL_FAILED_STATUS', 'no_change' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_SKIP_CALL_STATUSES' ) ) {
	define( 'WP_DIALYRA_DEFAULT_SKIP_CALL_STATUSES', array( 'completed', 'cancelled', 'draft', 'refunded' ) );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_MODE' ) ) {
	define( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_MODE', 'instant' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_ORDER_STATUS' ) ) {
	define( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_ORDER_STATUS', 'processing' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_DELAY_MINUTES' ) ) {
	define( 'WP_DIALYRA_DEFAULT_CALL_TRIGGER_DELAY_MINUTES', 5 );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_MODE' ) ) {
	define( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_MODE', 'always_active' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_DAYS' ) ) {
	define( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_DAYS', array( 'all' ) );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_OPEN_TIME' ) ) {
	define( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_OPEN_TIME', '09:00' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_CLOSE_TIME' ) ) {
	define( 'WP_DIALYRA_DEFAULT_BUSINESS_HOURS_CLOSE_TIME', '18:00' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_MAX_CONCURRENT_CALLS' ) ) {
	define( 'WP_DIALYRA_DEFAULT_MAX_CONCURRENT_CALLS', 1 );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_RETRY_MAX_ATTEMPTS' ) ) {
	define( 'WP_DIALYRA_DEFAULT_RETRY_MAX_ATTEMPTS', 2 );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_RETRY_DELAY_MINUTES' ) ) {
	define( 'WP_DIALYRA_DEFAULT_RETRY_DELAY_MINUTES', 15 );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_RETRY_ONLY_DURING_BUSINESS_HOURS' ) ) {
	define( 'WP_DIALYRA_DEFAULT_RETRY_ONLY_DURING_BUSINESS_HOURS', true );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_ORDER_CONFIRMED_NOTE' ) ) {
	define( 'WP_DIALYRA_DEFAULT_ORDER_CONFIRMED_NOTE', 'Dialyra call confirmed the order.' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_ORDER_CANCELLED_NOTE' ) ) {
	define( 'WP_DIALYRA_DEFAULT_ORDER_CANCELLED_NOTE', 'Dialyra call cancelled the order.' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_CALL_NO_ANSWER_NOTE' ) ) {
	define( 'WP_DIALYRA_DEFAULT_CALL_NO_ANSWER_NOTE', 'Dialyra call ended with no answer.' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_CALL_BUSY_NOTE' ) ) {
	define( 'WP_DIALYRA_DEFAULT_CALL_BUSY_NOTE', 'Dialyra call reached a busy line.' );
}

if ( ! defined( 'WP_DIALYRA_DEFAULT_CALL_FAILED_NOTE' ) ) {
	define( 'WP_DIALYRA_DEFAULT_CALL_FAILED_NOTE', 'Dialyra call failed.' );
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
