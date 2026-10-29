<?php
/**
 * Core File Integrity Monitor and Suspicious Executable Scanner.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Integrity_Monitor {

	/**
	 * Run complete integrity verification.
	 *
	 * @return array
	 */
	public static function run_integrity_check() {
		$core_check   = self::verify_wordpress_core_checksums();
		$upload_check = self::scan_uploads_for_executables();

		$summary = array(
			'checked_at'        => current_time( 'mysql' ),
			'core_status'       => $core_check['status'],
			'core_modified'     => $core_check['modified'],
			'core_missing'      => $core_check['missing'],
			'core_total_files'  => $core_check['total_checked'],
			'suspicious_uploads'=> $upload_check['suspicious_files'],
			'is_clean'          => ( 'clean' === $core_check['status'] && empty( $upload_check['suspicious_files'] ) ),
		);

		// Record in custom integrity table
		global $wpdb;
		$table_integrity = $wpdb->prefix . 'maxsafe_integrity';

		foreach ( $core_check['modified'] as $m_file ) {
			$wpdb->replace(
				$table_integrity,
				array(
					'file_path_relative' => sanitize_text_field( $m_file ),
					'hash_algorithm'     => 'md5',
					'hash_value'         => 'MODIFIED',
					'baseline_version'   => get_bloginfo( 'version' ),
					'status'             => 'modified',
					'checked_at'         => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		if ( ! $summary['is_clean'] ) {
			MaxSafe_Event_Logger::log_event(
				'integrity_violation_detected',
				'high',
				'Integrity Monitor',
				sprintf(
					'Integrity check: %d modified core files, %d missing core files, %d suspicious files in uploads.',
					count( $core_check['modified'] ),
					count( $core_check['missing'] ),
					count( $upload_check['suspicious_files'] )
				),
				$summary
			);
		}

		return $summary;
	}

	/**
	 * Fetch official WordPress core checksums from api.wordpress.org and verify local files.
	 *
	 * @return array
	 */
	public static function verify_wordpress_core_checksums() {
		global $wp_version, $wp_local_package;

		$locale   = ! empty( $wp_local_package ) ? $wp_local_package : get_locale();
		$api_url  = sprintf( 'https://api.wordpress.org/core/checksums/1.0/?version=%s&locale=%s', rawurlencode( $wp_version ), rawurlencode( $locale ) );

		$checksums = get_transient( 'maxsafe_core_checksums' );
		if ( false === $checksums || ! is_array( $checksums ) ) {
			$checksums = get_transient( 'wpsecureos_core_checksums' );
		}
		if ( false === $checksums || ! is_array( $checksums ) ) {
			$response = wp_remote_get(
				$api_url,
				array(
					'timeout'    => 15,
					'user-agent' => 'MaxSafe/' . ( defined( 'MAXSAFE_VERSION' ) ? MAXSAFE_VERSION : '1.1.0' ) . '; ' . home_url( '/' ),
				)
			);

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return array(
					'status'        => 'unknown',
					'message'       => __( 'Could not reach official api.wordpress.org checksums service.', 'maxsafe' ),
					'modified'      => array(),
					'missing'       => array(),
					'total_checked' => 0,
				);
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( empty( $body['checksums'] ) || ! is_array( $body['checksums'] ) ) {
				return array(
					'status'        => 'unknown',
					'message'       => __( 'Invalid checksums payload received from official API.', 'maxsafe' ),
					'modified'      => array(),
					'missing'       => array(),
					'total_checked' => 0,
				);
			}

			$checksums = $body['checksums'];
			set_transient( 'maxsafe_core_checksums', $checksums, DAY_IN_SECONDS );
		}

		$modified = array();
		$missing  = array();
		$checked  = 0;

		$base_dir = ABSPATH;

		foreach ( $checksums as $file => $expected_md5 ) {
			// Skip wp-content files as themes/plugins/languages vary per installation.
			if ( 0 === strpos( $file, 'wp-content/' ) ) {
				continue;
			}

			$checked++;
			$local_path = $base_dir . $file;

			if ( ! file_exists( $local_path ) ) {
				$missing[] = $file;
				continue;
			}

			// Compute md5
			$actual_md5 = md5_file( $local_path );
			if ( $actual_md5 !== $expected_md5 ) {
				$modified[] = $file;
			}
		}

		$status = ( empty( $modified ) && empty( $missing ) ) ? 'clean' : 'warning';

		return array(
			'status'        => $status,
			'modified'      => $modified,
			'missing'       => $missing,
			'total_checked' => $checked,
		);
	}

	/**
	 * Scan uploads directory for unexpected PHP or executable files.
	 * NOTE: Files are flagged as signals ONLY. No automatic file deletion is performed.
	 *
	 * @return array
	 */
	public static function scan_uploads_for_executables() {
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'];

		$suspicious = array();
		if ( ! is_dir( $base_dir ) ) {
			return array( 'suspicious_files' => array() );
		}

		$extensions = array( 'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'cgi', 'pl', 'py', 'sh' );
		$files      = self::scan_dir_recursive( $base_dir, $extensions, 200 ); // scan budget: 200 matches

		foreach ( $files as $f ) {
			$relative = str_replace( ABSPATH, '', $f );
			$suspicious[] = array(
				'path'        => $relative,
				'size'        => filesize( $f ),
				'modified_at' => gmdate( 'Y-m-d H:i:s', filemtime( $f ) ),
				'reason'      => __( 'Executable script located in public uploads directory.', 'maxsafe' ),
			);
		}

		return array( 'suspicious_files' => $suspicious );
	}

	/**
	 * Safe recursive directory traversal with scan budget to prevent memory exhaustion.
	 */
	private static function scan_dir_recursive( $dir, $target_exts, $limit = 200, &$results = array() ) {
		if ( count( $results ) >= $limit || ! is_dir( $dir ) || ! is_readable( $dir ) ) {
			return $results;
		}

		$items = scandir( $dir );
		if ( false === $items ) {
			return $results;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$full_path = $dir . DIRECTORY_SEPARATOR . $item;
			if ( is_link( $full_path ) ) {
				continue; // Prevent symlink escape
			}

			if ( is_dir( $full_path ) ) {
				self::scan_dir_recursive( $full_path, $target_exts, $limit, $results );
			} elseif ( is_file( $full_path ) ) {
				$ext = strtolower( pathinfo( $full_path, PATHINFO_EXTENSION ) );
				if ( in_array( $ext, $target_exts, true ) ) {
					$results[] = $full_path;
					if ( count( $results ) >= $limit ) {
						break;
					}
				}
			}
		}

		return $results;
	}
}
