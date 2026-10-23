<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package WpSecureOs
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user explicitly requested complete data deletion.
$delete_all = get_option( 'wpsecureos_delete_data_on_uninstall', false );

// Always clean up transient cache and scheduled crons.
delete_transient( 'wpsecureos_core_checksums' );
delete_transient( 'wpsecureos_vuln_feed_cache' );
delete_transient( 'wpsecureos_inventory_cache' );
delete_transient( 'wpsecureos_system_health' );
wp_clear_scheduled_hook( 'wpsecureos_daily_security_scan' );
wp_clear_scheduled_hook( 'wpsecureos_hourly_integrity_check' );

if ( $delete_all ) {
	global $wpdb;

	// Drop custom tables only if user explicitly consented.
	$tables = array(
		$wpdb->prefix . 'wpsecureos_events',
		$wpdb->prefix . 'wpsecureos_scans',
		$wpdb->prefix . 'wpsecureos_jobs',
		$wpdb->prefix . 'wpsecureos_integrity',
		$wpdb->prefix . 'wpsecureos_backups',
	);

	foreach ( $tables as $table ) {
		// Table names are safe-listed and prefixed above.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	// Delete options.
	delete_option( 'wpsecureos_settings' );
	delete_option( 'wpsecureos_db_version' );
	delete_option( 'wpsecureos_installed_time' );
	delete_option( 'wpsecureos_central_token' );
	delete_option( 'wpsecureos_dismissed_notices' );
	delete_option( 'wpsecureos_delete_data_on_uninstall' );
}
