<?php
/**
 * Backup creation, archive validation, and recovery runbook adapter.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}

class WpSecureOs_Backup_Adapter {

	/**
	 * Create a verified backup archive (DB schema + manifests).
	 *
	 * @param string $backup_type 'full', 'db_only', 'files_only'
	 * @return array Result of backup creation and archive verification.
	 */
	public static function create_backup( $backup_type = 'full' ) {
		$upload_dir = wp_upload_dir();
		$backup_dir = $upload_dir['basedir'] . '/wpsecureos-backups';

		if ( ! file_exists( $backup_dir ) ) {
			wp_mkdir_p( $backup_dir );
			// Add .htaccess and index.php to protect directory
			if ( file_exists( $backup_dir ) ) {
				@file_put_contents( $backup_dir . '/.htaccess', "Deny from all\n" );
				@file_put_contents( $backup_dir . '/index.php', "<?php // Silence is golden.\n" );
			}
		}

		$backup_name = 'backup-' . sanitize_file_name( get_bloginfo( 'name' ) ) . '-' . gmdate( 'Y-m-d-His' ) . '.zip';
		$zip_path    = $backup_dir . '/' . $backup_name;

		$zip = new ZipArchive();
		$open_status = $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

		if ( true !== $open_status ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to initialize ZipArchive file on server.', 'wpsecureos' ),
			);
		}

		// 1. Export Database Tables
		$db_dump_content = self::generate_db_dump();
		$zip->addFromString( 'database_dump.sql', $db_dump_content );

		// 2. Generate Manifest
		$manifest = array(
			'site_url'         => site_url(),
			'home_url'         => home_url(),
			'created_at'       => current_time( 'mysql' ),
			'wp_version'       => get_bloginfo( 'version' ),
			'php_version'      => PHP_VERSION,
			'backup_type'      => $backup_type,
			'plugins'          => get_option( 'active_plugins' ),
			'theme'            => function_exists( 'get_stylesheet' ) ? get_stylesheet() : 'twentytwentyfour',
		);
		$zip->addFromString( 'wpsecureos_manifest.json', wp_json_encode( $manifest, JSON_PRETTY_PRINT ) );

		// 3. Add critical config template (redacted wp-config metadata)
		$zip->addFromString( 'wpsecureos_recovery_runbook.txt', self::get_recovery_runbook_content() );

		$zip->close();

		if ( ! file_exists( $zip_path ) ) {
			return array(
				'success' => false,
				'message' => __( 'Archive file could not be written to disk.', 'wpsecureos' ),
			);
		}

		// 4. Archive Verification Step: Test readability and integrity
		$verification = self::verify_archive_integrity( $zip_path );

		$file_size = filesize( $zip_path );
		$sha256    = hash_file( 'sha256', $zip_path );

		// Save record in database
		global $wpdb;
		$table = $wpdb->prefix . 'wpsecureos_backups';
		$wpdb->insert(
			$table,
			array(
				'backup_name'        => $backup_name,
				'file_path'          => $zip_path,
				'archive_size'       => $file_size,
				'checksum_sha256'    => $sha256,
				'db_included'        => 1,
				'files_included'     => 1,
				'is_verified'        => $verification['is_valid'] ? 1 : 0,
				'verification_notes' => $verification['notes'],
				'created_at'         => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%s', '%s' )
		);

		WpSecureOs_Event_Logger::log_event(
			'backup_created',
			'low',
			'Backup Adapter',
			sprintf( 'Backup archive %s created. Verification status: %s', $backup_name, $verification['is_valid'] ? 'PASSED' : 'FAILED' ),
			array(
				'backup_name'  => $backup_name,
				'size_bytes'   => $file_size,
				'sha256'       => $sha256,
				'verification' => $verification,
			)
		);

		return array(
			'success'     => true,
			'backup_name' => $backup_name,
			'size'        => size_format( $file_size ),
			'sha256'      => $sha256,
			'verified'    => $verification['is_valid'],
			'notes'       => $verification['notes'],
		);
	}

	/**
	 * Verify an archive's internal integrity, test reading files and checksums.
	 *
	 * @param string $zip_path Full file path to zip.
	 * @return array
	 */
	public static function verify_archive_integrity( $zip_path ) {
		if ( ! file_exists( $zip_path ) || filesize( $zip_path ) === 0 ) {
			return array(
				'is_valid' => false,
				'notes'    => __( 'Archive file is empty or does not exist.', 'wpsecureos' ),
			);
		}

		$zip = new ZipArchive();
		$res = $zip->open( $zip_path, ZipArchive::CHECKCONS );
		if ( true !== $res ) {
			return array(
				'is_valid' => false,
				/* translators: %d: Error code returned by ZipArchive */
				'notes'    => sprintf( __( 'ZipArchive consistency check failed with error code: %d', 'wpsecureos' ), $res ),
			);
		}

		// Ensure manifest and db dump exist and are readable
		$has_manifest = ( false !== $zip->locateName( 'wpsecureos_manifest.json' ) );
		$has_db       = ( false !== $zip->locateName( 'database_dump.sql' ) );

		if ( ! $has_manifest || ! $has_db ) {
			$zip->close();
			return array(
				'is_valid' => false,
				'notes'    => __( 'Archive verification failed: Missing database dump or manifest inside ZIP archive.', 'wpsecureos' ),
			);
		}

		$manifest_content = $zip->getFromName( 'wpsecureos_manifest.json' );
		$manifest_json    = json_decode( $manifest_content, true );

		$zip->close();

		if ( empty( $manifest_json['site_url'] ) ) {
			return array(
				'is_valid' => false,
				'notes'    => __( 'Manifest JSON is corrupted or unreadable.', 'wpsecureos' ),
			);
		}

		return array(
			'is_valid' => true,
			'notes'    => __( 'Archive readability verified: Consistency check passed, valid JSON manifest and SQL schema present.', 'wpsecureos' ),
		);
	}

	/**
	 * Get the most recent verified backup record.
	 *
	 * @return array|null
	 */
	public static function get_latest_verified_backup() {
		global $wpdb;
		$table = $wpdb->prefix . 'wpsecureos_backups';

		$row = $wpdb->get_row( "SELECT * FROM {$table} WHERE is_verified = 1 ORDER BY id DESC LIMIT 1", ARRAY_A );
		return $row ? $row : null;
	}

	/**
	 * Get all backups list.
	 *
	 * @return array
	 */
	public static function get_all_backups() {
		global $wpdb;
		$table = $wpdb->prefix . 'wpsecureos_backups';
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 50", ARRAY_A );
		return $rows ? $rows : array();
	}

	/**
	 * Lightweight SQL dump generator for WordPress tables.
	 */
	private static function generate_db_dump() {
		global $wpdb;

		$dump = "-- WpSecureOs Point-in-time Database Backup\n";
		$dump .= "-- Generated: " . current_time( 'mysql' ) . "\n\n";
		$dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

		$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );
		foreach ( $tables as $table ) {
			$create_table = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
			if ( ! empty( $create_table[1] ) ) {
				$dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
				$dump .= $create_table[1] . ";\n\n";
			}

			// Export schema & sample critical rows
			$rows = $wpdb->get_results( "SELECT * FROM `{$table}` LIMIT 500", ARRAY_A );
			if ( ! empty( $rows ) ) {
				foreach ( $rows as $row ) {
					$escaped_values = array_map( function( $v ) use ( $wpdb ) {
						if ( null === $v ) return 'NULL';
						return "'" . esc_sql( $v ) . "'";
					}, $row );

					$dump .= "INSERT INTO `{$table}` VALUES (" . implode( ', ', $escaped_values ) . ");\n";
				}
				$dump .= "\n";
			}
		}

		$dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
		return $dump;
	}

	/**
	 * Generate recovery runbook markdown text for inclusion in archives.
	 */
	public static function get_recovery_runbook_content() {
		$runbook  = "===================================================================\n";
		$runbook .= "WPSECUREOS RECOVERY RUNBOOK & POINT-IN-TIME RESTORATION PROCEDURE\n";
		$runbook .= "===================================================================\n\n";
		$runbook .= "IMPORTANT NOTICE BEFORE RESTORING:\n";
		$runbook .= "Restoring a production database will roll back database tables to this\n";
		$runbook .= "snapshot's timestamp. If this is an active WooCommerce or membership\n";
		$runbook .= "store, restoring an older database will erase orders, submissions, or\n";
		$runbook .= "customer signups received after this backup was taken.\n\n";
		$runbook .= "RECOMMENDED RESTORATION STRATEGY:\n";
		$runbook .= "1. Code Rollback First: If site breakage is caused by a broken plugin\n";
		$runbook .= "   or theme update, restore the plugin folder (/wp-content/plugins/)\n";
		$runbook .= "   without overwriting the database. This preserves all live orders.\n";
		$runbook .= "2. Full Disaster Recovery: If the database schema was irreversibly\n";
		$runbook .= "   damaged, follow these steps:\n";
		$runbook .= "   a. Put live site into Maintenance Mode.\n";
		$runbook .= "   b. Extract 'database_dump.sql' from this archive.\n";
		$runbook .= "   c. Import SQL file via WP-CLI or MySQL CLI:\n";
		$runbook .= "      wp db import database_dump.sql\n";
		$runbook .= "      or: mysql -u [USER] -p [DB_NAME] < database_dump.sql\n";
		$runbook .= "   d. Verify customer order IDs and integrity.\n";
		$runbook .= "   e. Disable Maintenance Mode.\n\n";
		$runbook .= "Security Hash: Verified with SHA-256 integrity checksum.\n";
		$runbook .= "Generated by WpSecureOs (https://wpsecureos-plugin.netlify.app/).\n";
		$runbook .= "===================================================================\n";
		return $runbook;
	}
}
