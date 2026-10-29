<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package MaxSafe
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user explicitly requested complete data deletion.
$delete_all = get_option( 'maxsafe_delete_data_on_uninstall', false ) || get_option( 'wpsecureos_delete_data_on_uninstall', false );

// Always clean up transient cache and scheduled crons.
delete_transient( 'maxsafe_core_checksums' );
delete_transient( 'maxsafe_vuln_feed_cache' );
delete_transient( 'maxsafe_inventory_cache' );
delete_transient( 'maxsafe_system_health' );
delete_transient( 'wpsecureos_core_checksums' );
delete_transient( 'wpsecureos_vuln_feed_cache' );
delete_transient( 'wpsecureos_inventory_cache' );
delete_transient( 'wpsecureos_system_health' );

wp_clear_scheduled_hook( 'maxsafe_daily_security_scan' );
wp_clear_scheduled_hook( 'maxsafe_hourly_integrity_check' );
wp_clear_scheduled_hook( 'wpsecureos_daily_security_scan' );
wp_clear_scheduled_hook( 'wpsecureos_hourly_integrity_check' );

if ( $delete_all ) {
	global $wpdb;

	// Drop custom tables only if user explicitly consented.
	$tables = array(
		$wpdb->prefix . 'maxsafe_events',
		$wpdb->prefix . 'maxsafe_scans',
		$wpdb->prefix . 'maxsafe_jobs',
		$wpdb->prefix . 'maxsafe_integrity',
		$wpdb->prefix . 'maxsafe_backups',
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
	delete_option( 'maxsafe_settings' );
	delete_option( 'maxsafe_db_version' );
	delete_option( 'maxsafe_installed_time' );
	delete_option( 'maxsafe_license' );
	delete_option( 'maxsafe_central_token' );
	delete_option( 'maxsafe_central_connector' );
	delete_option( 'maxsafe_dismissed_notices' );
	delete_option( 'maxsafe_delete_data_on_uninstall' );

	delete_option( 'wpsecureos_settings' );
	delete_option( 'wpsecureos_db_version' );
	delete_option( 'wpsecureos_installed_time' );
	delete_option( 'wpsecureos_license' );
	delete_option( 'wpsecureos_central_token' );
	delete_option( 'wpsecureos_central_connector' );
	delete_option( 'wpsecureos_dismissed_notices' );
	delete_option( 'wpsecureos_delete_data_on_uninstall' );
}
