<?php
/**
 * Fired during plugin activation.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Activator {

	/**
	 * Run all activation routines.
	 */
	public static function activate() {
		self::create_tables();
		self::set_default_options();
		MaxSafe_Capabilities::register_capabilities();
		self::schedule_cron_jobs();
	}

	/**
	 * Create or update custom database tables using dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Migrate legacy wpsecureos tables if they exist
		$legacy_tables = array(
			'wpsecureos_events'    => 'maxsafe_events',
			'wpsecureos_scans'     => 'maxsafe_scans',
			'wpsecureos_jobs'      => 'maxsafe_jobs',
			'wpsecureos_integrity' => 'maxsafe_integrity',
			'wpsecureos_backups'   => 'maxsafe_backups',
		);
		foreach ( $legacy_tables as $old_name => $new_name ) {
			$old_table = $wpdb->prefix . $old_name;
			$new_table = $wpdb->prefix . $new_name;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$has_old = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $old_table ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$has_new = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $new_table ) );
			if ( $has_old === $old_table && $has_new !== $new_table ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "RENAME TABLE `{$old_table}` TO `{$new_table}`" );
			}
		}

		// 1. Audit and security events log.
		$table_events = $wpdb->prefix . 'maxsafe_events';
		$sql_events   = "CREATE TABLE {$table_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_type varchar(64) NOT NULL,
			severity varchar(20) NOT NULL DEFAULT 'low',
			actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			ip_address varchar(45) NOT NULL DEFAULT '',
			component_ref varchar(128) NOT NULL DEFAULT '',
			message text NOT NULL,
			metadata_json longtext,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY event_type (event_type),
			KEY severity (severity),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $sql_events );

		// 2. Security and health scans.
		$table_scans = $wpdb->prefix . 'maxsafe_scans';
		$sql_scans   = "CREATE TABLE {$table_scans} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			scan_type varchar(32) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'running',
			started_at datetime NOT NULL,
			completed_at datetime DEFAULT NULL,
			summary_json longtext,
			source_version varchar(32) DEFAULT '',
			PRIMARY KEY  (id),
			KEY scan_type (scan_type),
			KEY status (status)
		) {$charset_collate};";
		dbDelta( $sql_scans );

		// 3. Staging and update orchestrator jobs.
		$table_jobs = $wpdb->prefix . 'maxsafe_jobs';
		$sql_jobs   = "CREATE TABLE {$table_jobs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			job_type varchar(64) NOT NULL,
			status varchar(32) NOT NULL DEFAULT 'queued',
			idempotency_key varchar(64) NOT NULL,
			current_step varchar(64) NOT NULL DEFAULT 'init',
			payload_json longtext,
			result_json longtext,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY idempotency_key (idempotency_key),
			KEY status (status)
		) {$charset_collate};";
		dbDelta( $sql_jobs );

		// 4. File integrity baseline table.
		$table_integrity = $wpdb->prefix . 'maxsafe_integrity';
		$sql_integrity   = "CREATE TABLE {$table_integrity} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			file_path_relative varchar(255) NOT NULL,
			hash_algorithm varchar(16) NOT NULL DEFAULT 'md5',
			hash_value varchar(128) NOT NULL,
			baseline_version varchar(32) NOT NULL,
			status varchar(32) NOT NULL DEFAULT 'clean',
			checked_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY file_path_relative (file_path_relative(191)),
			KEY status (status)
		) {$charset_collate};";
		dbDelta( $sql_integrity );

		// 5. Backup metadata & verification records.
		$table_backups = $wpdb->prefix . 'maxsafe_backups';
		$sql_backups   = "CREATE TABLE {$table_backups} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			backup_name varchar(128) NOT NULL,
			file_path varchar(255) NOT NULL,
			archive_size bigint(20) unsigned NOT NULL DEFAULT 0,
			checksum_sha256 varchar(64) NOT NULL DEFAULT '',
			db_included tinyint(1) NOT NULL DEFAULT 1,
			files_included tinyint(1) NOT NULL DEFAULT 1,
			is_verified tinyint(1) NOT NULL DEFAULT 0,
			verification_notes text,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY is_verified (is_verified)
		) {$charset_collate};";
		dbDelta( $sql_backups );

		update_option( 'maxsafe_db_version', '1.0.0' );
	}

	/**
	 * Set up default configuration options.
	 */
	public static function set_default_options() {
		// Migrate legacy settings if available
		if ( ! get_option( 'maxsafe_settings' ) ) {
			$legacy_settings = get_option( 'wpsecureos_settings' );
			if ( ! empty( $legacy_settings ) && is_array( $legacy_settings ) ) {
				if ( isset( $legacy_settings['backup_storage_path'] ) && 'wp-content/uploads/wpsecureos-backups' === $legacy_settings['backup_storage_path'] ) {
					$legacy_settings['backup_storage_path'] = 'wp-content/uploads/maxsafe-backups';
				}
				update_option( 'maxsafe_settings', $legacy_settings );
			} else {
				$defaults = array(
					'vulnerability_feed_provider' => 'wpscan_compatible',
					'vulnerability_feed_api_key'  => '',
					'backup_retention_count'      => 5,
					'backup_storage_path'         => 'wp-content/uploads/maxsafe-backups',
					'staging_mode'                => 'simulated_local',
					'visual_diff_threshold'       => 0.05,
					'critical_urls'               => array(
						home_url( '/' ),
						home_url( '/wp-login.php' ),
					),
					'rate_limit_login_attempts'   => 5,
					'rate_limit_lockout_minutes'  => 15,
					'alert_email'                 => get_option( 'admin_email' ),
					'enable_login_audit'          => 1,
					'enable_uploads_scanner'      => 1,
				);
				update_option( 'maxsafe_settings', $defaults );
			}
		}

		if ( ! get_option( 'maxsafe_license' ) && get_option( 'wpsecureos_license' ) ) {
			update_option( 'maxsafe_license', get_option( 'wpsecureos_license' ) );
		}

		if ( ! get_option( 'maxsafe_installed_time' ) ) {
			$legacy_time = get_option( 'wpsecureos_installed_time' );
			update_option( 'maxsafe_installed_time', $legacy_time ? $legacy_time : current_time( 'mysql' ) );
		}
	}

	/**
	 * Register scheduled background cron jobs.
	 */
	public static function schedule_cron_jobs() {
		if ( ! wp_next_scheduled( 'maxsafe_daily_security_scan' ) ) {
			wp_schedule_event( time(), 'daily', 'maxsafe_daily_security_scan' );
		}
	}
}
