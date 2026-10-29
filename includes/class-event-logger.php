<?php
/**
 * Security Event Logger & Login Rate Limiting.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Event_Logger {

	/**
	 * Log a security or administrative audit event.
	 *
	 * @param string $event_type   Type identifier (e.g. 'login_failed', 'update_approved').
	 * @param string $severity     Severity: 'low', 'medium', 'high', 'critical'.
	 * @param string $component    Component reference (e.g. 'Core', 'Risk Assessor').
	 * @param string $message      Human readable message.
	 * @param array  $metadata     Optional context metadata.
	 * @return int|bool Inserted event ID or false.
	 */
	public static function log_event( $event_type, $severity, $component, $message, $metadata = array() ) {
		global $wpdb;

		$table = $wpdb->prefix . 'maxsafe_events';
		$user_id = get_current_user_id();
		$ip = self::get_client_ip();

		// Redact any accidental credentials or secrets in metadata
		$clean_metadata = self::redact_secrets( $metadata );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'maxsafe_events',
			array(
				'event_type'    => sanitize_text_field( $event_type ),
				'severity'      => sanitize_text_field( $severity ),
				'actor_user_id' => (int) $user_id,
				'ip_address'    => sanitize_text_field( $ip ),
				'component_ref' => sanitize_text_field( $component ),
				'message'       => sanitize_text_field( $message ),
				'metadata_json' => wp_json_encode( $clean_metadata ),
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Retrieve events with pagination and filtering.
	 *
	 * @param int    $page     Page number.
	 * @param int    $per_page Items per page.
	 * @param string $severity Optional severity filter.
	 * @param string $search   Optional search term.
	 * @return array
	 */
	public static function get_events( $page = 1, $per_page = 20, $severity = '', $search = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'maxsafe_events';

		$where = array( '1=1' );
		$args  = array();

		if ( ! empty( $severity ) ) {
			$where[] = 'severity = %s';
			$args[]  = $severity;
		}

		if ( ! empty( $search ) ) {
			$where[] = '(message LIKE %s OR component_ref LIKE %s OR ip_address LIKE %s)';
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$args[]  = $like;
			$args[]  = $like;
			$args[]  = $like;
		}

		$where_clause = implode( ' AND ', $where );
		$offset       = ( $page - 1 ) * $per_page;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter
		// Count total
		if ( ! empty( $args ) ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$wpdb->prefix}maxsafe_events WHERE {$where_clause}", ...$args ) );
		} else {
			$total = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}maxsafe_events WHERE 1=1" );
		}

		// Query records
		$query_args = array_merge( $args, array( $per_page, $offset ) );
		$rows       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}maxsafe_events WHERE {$where_clause} ORDER BY id DESC LIMIT %d OFFSET %d",
				...$query_args
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array(
			'items'       => $rows ? $rows : array(),
			'total'       => $total,
			'total_pages' => ceil( $total / $per_page ),
			'current_page'=> $page,
		);
	}

	/**
	 * Get sanitized client IP.
	 */
	public static function get_client_ip() {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$parts = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip    = trim( $parts[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '127.0.0.1';
	}

	/**
	 * Recursively scrub sensitive keys from metadata logs.
	 */
	public static function redact_secrets( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$sensitive_keys = array( 'password', 'pass', 'secret', 'token', 'auth_key', 'nonce', 'salt', 'api_key', 'cookie' );
		$clean = array();

		foreach ( $data as $k => $v ) {
			$lower_k = strtolower( $k );
			$is_sensitive = false;
			foreach ( $sensitive_keys as $s ) {
				if ( false !== strpos( $lower_k, $s ) ) {
					$is_sensitive = true;
					break;
				}
			}

			if ( $is_sensitive ) {
				$clean[ $k ] = '[REDACTED]';
			} elseif ( is_array( $v ) ) {
				$clean[ $k ] = self::redact_secrets( $v );
			} else {
				$clean[ $k ] = $v;
			}
		}

		return $clean;
	}

	/**
	 * Authentication hook: Monitor failed logins and rate limit.
	 */
	public static function handle_failed_login( $username ) {
		$ip = self::get_client_ip();
		$settings = get_option( 'maxsafe_settings', array() );
		if ( empty( $settings ) ) {
			$settings = get_option( 'wpsecureos_settings', array() );
		}
		$limit    = isset( $settings['rate_limit_login_attempts'] ) ? (int) $settings['rate_limit_login_attempts'] : 5;
		$lockout  = isset( $settings['rate_limit_lockout_minutes'] ) ? (int) $settings['rate_limit_lockout_minutes'] : 15;

		$transient_key = 'maxsafe_login_fails_' . md5( $ip );
		$fails = (int) get_transient( $transient_key );
		$fails++;
		set_transient( $transient_key, $fails, $lockout * MINUTE_IN_SECONDS );

		self::log_event(
			'login_failed',
			( $fails >= $limit ) ? 'high' : 'low',
			'Authentication',
			sprintf( 'Failed login attempt for username: %s (Failure count: %d)', sanitize_text_field( $username ), $fails ),
			array( 'username' => $username, 'failure_count' => $fails )
		);
	}

	/**
	 * Check if an IP is currently locked out from logging in.
	 */
	public static function is_ip_locked_out( $ip ) {
		$settings = get_option( 'maxsafe_settings', array() );
		if ( empty( $settings ) ) {
			$settings = get_option( 'wpsecureos_settings', array() );
		}
		$limit    = isset( $settings['rate_limit_login_attempts'] ) ? (int) $settings['rate_limit_login_attempts'] : 5;
		$fails    = (int) get_transient( 'maxsafe_login_fails_' . md5( $ip ) );
		if ( ! $fails ) {
			$fails = (int) get_transient( 'wpsecureos_login_fails_' . md5( $ip ) );
		}
		return ( $fails >= $limit );
	}
}
